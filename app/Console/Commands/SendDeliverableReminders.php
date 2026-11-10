<?php

namespace App\Console\Commands;

use App\Models\TeamsGuestUser;
use App\Models\Ticket;
use App\Models\TicketDeliverableReminder;
use App\Services\PowerAutomateService;
use App\Services\Teams\TeamsGuestService;
use App\Support\TicketDocumentStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reminder harian ke group chat Teams tiket yang sudah Closed tetapi dokumen
 * deliverable MANDATORY-nya belum lengkap, dengan @mention PIC tiket (flow 8).
 *
 * Seperti tickets:open-reminders, daftar tiketnya dihitung ulang tiap kali
 * jalan dari keadaan saat ini (status closed + TicketDocumentStatus
 * incomplete). Jadi "berhenti kalau dokumen sudah lengkap" tidak butuh kode
 * pembatal: begitu dokumen terakhir diunggah, atau tiket dibuka lagi, tiketnya
 * hilang sendiri dari hasil query.
 *
 * Dijadwalkan tiap jam; command-nya sendiri yang menjaga jendela jam kirim dan
 * "paling banyak sekali per hari kalender" (ticket_deliverable_reminders).
 * Pengiriman yang GAGAL tidak dicatat, jadi dicoba lagi jam berikutnya.
 */
class SendDeliverableReminders extends Command
{
    /** Garis pemisah header dan isi, sama dengan pesan outbox (flow 7). */
    private const DIVIDER = '-----------------------------------------';

    protected $signature = 'tickets:deliverable-reminders
                            {--dry-run : Tampilkan tiket yang akan dikirimi reminder tanpa memanggil Power Automate}
                            {--ticket= : Hanya proses satu ticket_id (untuk uji flow); tetap tunduk pada aturan sekali sehari}
                            {--ignore-window : Abaikan jendela jam kirim (untuk uji manual)}
                            {--limit= : Override jumlah tiket maksimum per run (default dari config)}';

    protected $description = 'Kirim reminder harian ke group chat Teams untuk tiket Closed yang dokumen deliverable mandatory-nya belum lengkap (flow 8)';

    public function handle(PowerAutomateService $powerAutomate, TeamsGuestService $guests): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !$powerAutomate->isFlowReady(PowerAutomateService::FLOW_TICKET_DELIVERABLE_REMINDER)) {
            // Bukan error: begini caranya fitur ini dimatikan.
            $this->line('Flow ticket_deliverable_reminder tidak aktif — tidak ada yang dikirim.');
            return self::SUCCESS;
        }

        $config = (array) config('services.power_automate.deliverable_reminder', []);

        if (!$this->option('ignore-window') && !$this->insideWindow($config['send_from'] ?? null, $config['send_until'] ?? null)) {
            $this->line('Di luar jendela jam kirim reminder dokumen deliverable.');
            return self::SUCCESS;
        }

        $tickets = $this->dueTickets(
            max(0, (int) ($config['grace_days'] ?? 0)),
            max(0, (int) ($config['max_count'] ?? 0)),
            max(1, (int) ($this->option('limit') ?? $config['batch_limit'] ?? 50)),
            $this->parseSince($config['since'] ?? null),
            $this->option('ticket') ? (int) $this->option('ticket') : null
        );

        if ($tickets->isEmpty()) {
            $this->line('Tidak ada tiket closed dengan dokumen deliverable belum lengkap yang jatuh tempo reminder.');
            return self::SUCCESS;
        }

        // Batch: satu kali hitung untuk seluruh kandidat, bukan per tiket.
        $missingByTicket = TicketDocumentStatus::missingByTicket($tickets->pluck('ticket_id'));

        $sent = $failed = $noPic = 0;

        foreach ($tickets as $ticket) {
            $missing = $missingByTicket[(int) $ticket->ticket_id] ?? [];

            // Query sudah menyaring incomplete; ini hanya pagar kalau dokumen
            // terakhir diunggah di antara query dan loop.
            if ($missing === []) {
                continue;
            }

            $count = (int) ($ticket->getAttribute('sent_count') ?? 0) + 1;
            $pics  = $this->pics($ticket, $powerAutomate, $guests);

            if ($pics === []) {
                $noPic++;
            }

            $payload = $this->payload($ticket, $missing, $pics, $count, $powerAutomate);

            if ($dryRun) {
                $this->line(sprintf(
                    '[dry-run] #%s closed %s -> kurang: %s | PIC: %s (reminder ke-%d)',
                    $payload['ticket']['number'],
                    $payload['reminder']['closed_date'],
                    implode(', ', $missing),
                    $pics === [] ? '(tanpa PIC — tanpa mention)' : implode(', ', array_column($pics, 'teams_id')),
                    $count
                ));
                $sent++;
                continue;
            }

            if (!$powerAutomate->dispatch(PowerAutomateService::FLOW_TICKET_DELIVERABLE_REMINDER, $payload)) {
                $failed++;
                continue;
            }

            $this->recordSent((int) $ticket->ticket_id);
            $sent++;
        }

        $this->info(sprintf(
            'Reminder dokumen deliverable: %d terkirim (%d tanpa PIC), %d gagal.',
            $sent,
            $noPic,
            $failed
        ));

        return self::SUCCESS;
    }

    /**
     * Tiket closed, punya group chat Teams, dokumen mandatory belum lengkap, dan
     * belum dikirimi reminder hari ini.
     *
     * Incomplete disaring DI QUERY (TicketDocumentStatus::applyFilter) supaya
     * batch_limit tidak habis dipakai tiket yang dokumennya sudah lengkap.
     */
    private function dueTickets(int $graceDays, int $maxCount, int $batchLimit, ?Carbon $since, ?int $ticketId)
    {
        // end_date diisi saat status jadi closed (kolom DATE). Tiket closed lama
        // yang belum punya end_date jatuh ke tanggal updated_at.
        $closedDate = 'COALESCE(ticket.end_date, DATE(ticket.updated_at))';

        $query = Ticket::query()
            ->join('ticket_teams_chat as c', 'c.ticket_id', '=', 'ticket.ticket_id')
            ->leftJoin('ticket_deliverable_reminders as dr', 'dr.ticket_id', '=', 'ticket.ticket_id')
            ->where('ticket.status', 'closed')
            ->where(function ($q) {
                $q->whereNull('ticket.is_hidden')->orWhere('ticket.is_hidden', false);
            })
            ->whereRaw("{$closedDate} <= ?", [today()->subDays($graceDays)->toDateString()])
            ->when($since, fn ($q) => $q->whereRaw("{$closedDate} >= ?", [$since->toDateString()]))
            ->when($ticketId, fn ($q) => $q->where('ticket.ticket_id', $ticketId))
            ->when($maxCount > 0, function ($q) use ($maxCount) {
                $q->where(function ($w) use ($maxCount) {
                    $w->whereNull('dr.sent_count')->orWhere('dr.sent_count', '<', $maxCount);
                });
            })
            // Sekali per hari kalender.
            ->where(function ($q) {
                $q->whereNull('dr.last_sent_at')->orWhere('dr.last_sent_at', '<', today());
            });

        TicketDocumentStatus::applyFilter($query, TicketDocumentStatus::INCOMPLETE);

        return $query
            ->orderByRaw('dr.last_sent_at IS NULL DESC')
            ->orderBy('dr.last_sent_at')
            ->limit($batchLimit)
            ->select('ticket.*', 'dr.sent_count', 'c.chat_id as teams_chat_id', 'c.topic as teams_chat_topic')
            ->get();
    }

    /**
     * PIC tiket (ticket_lead_id) dalam bentuk siap mention. Kosong kalau tiket
     * belum punya PIC, PIC-nya tanpa email kerja, atau guest yang belum pernah
     * diundang — pesannya tetap dikirim, hanya tanpa mention.
     *
     * Guest TIDAK diundang dari sini: PIC yang sudah ada di grup pasti sudah
     * punya baris teams_guest_users; reminder bukan tempat memulai undangan.
     *
     * @return list<array{employee_id:int,name:?string,email:string,is_guest:bool,teams_id:string}>
     */
    private function pics(Ticket $ticket, PowerAutomateService $powerAutomate, TeamsGuestService $guests): array
    {
        if (!$ticket->ticket_lead_id) {
            return [];
        }

        $person = $powerAutomate->employeeContact((int) $ticket->ticket_lead_id);
        if (!$person) {
            return [];
        }

        $isGuest = $guests->isExternal($person['email']);
        $teamsId = $isGuest
            ? TeamsGuestUser::where('email', mb_strtolower(trim($person['email'])))->value('aad_object_id')
            : $person['email'];

        if (!is_string($teamsId) || $teamsId === '') {
            Log::info('tickets:deliverable-reminders: PIC guest belum dikenal Teams, dikirim tanpa mention', [
                'ticket_id' => $ticket->ticket_id,
                'email'     => $person['email'],
            ]);
            return [];
        }

        return [[
            'employee_id' => $person['employee_id'],
            'name'        => $person['name'],
            'email'       => $person['email'],
            'is_guest'    => $isGuest,
            'teams_id'    => $teamsId,
        ]];
    }

    /**
     * Payload flow 8. Pola teksnya mengikuti flow 6: `mention_html` dipasang
     * SESUDAH token @mention PIC, `message_html` dipakai utuh saat tidak ada
     * yang bisa di-mention. Keduanya HTML karena field Message konektor Teams
     * mengabaikan "\n".
     *
     * @param  array<int,string>  $missing
     * @param  list<array<string,mixed>>  $pics
     */
    private function payload(Ticket $ticket, array $missing, array $pics, int $count, PowerAutomateService $powerAutomate): array
    {
        $ticketPayload = $powerAutomate->ticketPayload($ticket);
        $closedAt      = $ticket->end_date ? Carbon::parse($ticket->end_date) : Carbon::parse($ticket->updated_at);
        $closedDays    = (int) $closedAt->copy()->startOfDay()->diffInDays(today());

        $since = 'Tiket ini sudah Closed sejak ' . $closedAt->format('d M Y')
            . ($closedDays > 0 ? ' (' . $closedDays . ' hari)' : ' (hari ini)')
            . ', tetapi dokumen deliverable mandatory berikut belum diunggah:';

        $body = $since . "\n"
            . implode("\n", array_map(static fn ($n) => '• ' . $n, $missing)) . "\n\n"
            . 'Mohon segera dilengkapi di menu Deliverable Documents pada tiket. '
            . 'Reminder ini dikirim setiap hari sampai semua dokumen mandatory lengkap (reminder ke-' . $count . ').';

        $picLine = $pics === []
            ? 'PIC tiket belum ditentukan — mohon tim menentukan PIC dan melengkapi dokumennya.'
            : 'PIC: ' . ($pics[0]['name'] ?? $pics[0]['email']);

        $message     = 'Reminder Dokumen Deliverable · EcoSystem' . "\n" . self::DIVIDER . "\n" . $picLine . "\n\n" . $body;
        $mentionText = 'mohon segera melengkapi dokumen deliverable tiket ini.' . "\n\n" . $body;

        // Nama tautan, bukan URL mentah: URL panjang di badan pesan yang dulu
        // ditolak (keputusan meeting 18 Sep 2026).
        $link = '<br><br><a href="' . e($ticketPayload['url']) . '">Buka tiket di EcoSystem</a>';

        return [
            'ticket'   => $ticketPayload,
            'chat'     => [
                // Chat id yang tersimpan saat group chat dibuat. Flow 8 memakai
                // ini langsung di "Post message in a chat or channel".
                'id'     => (string) $ticket->getAttribute('teams_chat_id'),
                'topic'  => (string) ($ticket->getAttribute('teams_chat_topic') ?? ''),
                'number' => (string) ($ticketPayload['number'] ?? ''),
            ],
            'pics'     => $pics,
            'missing_documents' => array_values($missing),
            'reminder' => [
                'count'       => $count,
                'is_first'    => $count === 1,
                'closed_date' => $closedAt->toDateString(),
                'closed_days' => $closedDays,
            ],
            'message'      => $message,
            'message_html' => nl2br(e($message), false) . $link,
            'mention_text' => $mentionText,
            'mention_html' => nl2br(e($mentionText), false) . $link,
        ];
    }

    private function recordSent(int $ticketId): void
    {
        $state = TicketDeliverableReminder::firstOrNew(['ticket_id' => $ticketId]);

        $state->sent_count    = (int) $state->sent_count + 1;
        $state->first_sent_at = $state->first_sent_at ?? now();
        $state->last_sent_at  = now();
        $state->save();
    }

    /**
     * Jendela "HH:MM"–"HH:MM" dalam zona waktu aplikasi. Nilai yang tidak bisa
     * dibaca diabaikan (dianggap tanpa batas) daripada mematikan reminder
     * secara diam-diam.
     */
    private function insideWindow($from, $until): bool
    {
        try {
            $now = now();
            if (trim((string) $from) !== '' && $now->lt(today()->setTimeFromTimeString((string) $from))) {
                return false;
            }
            if (trim((string) $until) !== '' && $now->gt(today()->setTimeFromTimeString((string) $until))) {
                return false;
            }
        } catch (\Throwable $e) {
            $this->warn('POWER_AUTOMATE_DELIVERABLE_REMINDER_FROM/UNTIL tidak valid — jendela jam diabaikan.');
        }

        return true;
    }

    private function parseSince($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            $this->warn("POWER_AUTOMATE_DELIVERABLE_REMINDER_SINCE tidak valid ('{$value}') — batas tanggal diabaikan.");
            return null;
        }
    }
}
