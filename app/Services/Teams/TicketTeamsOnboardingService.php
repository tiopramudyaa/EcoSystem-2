<?php

namespace App\Services\Teams;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\PowerAutomateService;
use Illuminate\Support\Facades\Log;

/**
 * "Tiket baru lahir" di sisi Microsoft Teams: group chat tiket dibuat lewat
 * Graph, pesan pertama diteruskan ke grup, lalu flow 5 (ticket_validated)
 * memposting kartu tiket + chat ke lead modul.
 *
 * Dulu hidup sebagai StagingTicketController::notifyTeamsTicketValidated(),
 * jadi hanya tiket yang lewat staging (email, Jarvies, portal customer) yang
 * mendapat group chat. Dipindah ke sini supaya tiket yang dibuat LANGSUNG dari
 * EcoSystem (admin create, helpdesk create) mendapat perlakuan yang sama
 * persis — isi method di bawah sengaja tidak diubah dari versi controller.
 *
 * Semua kegagalan di sini non-fatal: tiket yang sudah tersimpan tidak boleh
 * gagal gara-gara Teams/Power Automate.
 */
class TicketTeamsOnboardingService
{
    /** Pesan pertama berasal dari customer (email / Jarvies) — jalur staging. */
    public const FIRST_MESSAGE_CUSTOMER = 'customer';

    /** Pesan pertama adalah email yang dikirim karyawan ke customer — jalur create langsung. */
    public const FIRST_MESSAGE_AGENT = 'agent';

    /**
     * Kirim event "tiket divalidasi" ke Power Automate.
     *
     * Payload sudah memuat daftar lead modul beserta email kerjanya, jadi flow di
     * Power Automate cukup melakukan dua hal (post kartu ke channel, kirim chat
     * ke tiap lead) tanpa perlu menebak siapa penerimanya — pemetaan modul ->
     * lead adalah pengetahuan EcoSystem, bukan pengetahuan flow.
     *
     * Tiket yang modulnya belum punya lead tetap dikirim: kartunya masih berguna
     * di channel tim, dan flow bisa memilih melewati bagian chat pribadi dengan
     * memeriksa `lead_emails` yang kosong.
     *
     * @param  array   $sessionUser       user yang memvalidasi / membuat tiket
     * @param  string  $firstMessageKind  FIRST_MESSAGE_CUSTOMER | FIRST_MESSAGE_AGENT
     * @param  string  $logContext        awalan pesan log, mis. "StagingTicketController@approve"
     */
    public function onboard(
        Ticket $ticket,
        array $sessionUser,
        ?TicketMessage $firstMessage = null,
        string $firstMessageKind = self::FIRST_MESSAGE_CUSTOMER,
        string $logContext = 'TicketTeamsOnboarding'
    ): void {
        try {
            $powerAutomate = app(PowerAutomateService::class);

            if (!$powerAutomate->isFlowReady(PowerAutomateService::FLOW_TICKET_VALIDATED)) {
                return;
            }

            $payloadTicket = $powerAutomate->ticketPayload($ticket);
            $leads         = $powerAutomate->moduleLeads($payloadTicket['module_id'], $payloadTicket['module']);

            $leadEmails    = $powerAutomate->leadEmails($leads);

            $chatPayload = $powerAutomate->ticketChatPayload(
                $payloadTicket,
                $leadEmails,
                $sessionUser['email'] ?? null
            );

            // Group chat dibuat EcoSystem sendiri lewat Graph, bukan oleh flow 5.
            // Gunanya kita MEMEGANG chat_id: tanpa itu, sinkronisasi pesan masuk
            // mustahil dan flow 6 terpaksa mencari chatnya lagi dengan mencocokkan
            // nama grup. Lihat docs/teams-chat-sync-design.md §4.
            //
            // try/catch sendiri, terpisah dari dispatch di bawah: Teams yang
            // bermasalah tidak boleh ikut membatalkan notifikasi Power Automate
            // yang selama ini sudah jalan.
            // Guest yang ditunda karena belum dikenal Teams (lihat
            // TeamsChatService::createForTicket). Mereka belum anggota grup saat
            // flow 5 memposting kartu, jadi tidak ikut di-@mention.
            $pendingGuests = [];

            try {
                $teamsChat = app(TeamsChatService::class)->createForTicket(
                    (int) $ticket->ticket_id,
                    $chatPayload['topic'],
                    $chatPayload['members']
                );

                if ($teamsChat) {
                    $chatPayload['id'] = $teamsChat->chat_id;

                    // Penanda untuk flow 5: chatnya SUDAH ada, jangan bikin lagi.
                    // Tanpa Condition atas flag ini di sisi Power Automate, satu
                    // tiket akan punya DUA grup — satu dari sini, satu dari flow.
                    $chatPayload['created_by_ecosystem'] = true;

                    $pendingGuests = app(TeamsChatService::class)
                        ->pendingGuestEmails((int) $ticket->ticket_id);

                    // Pesan pertama tiket ikut diteruskan ke grup. Hanya bisa di
                    // sini: sebelum titik ini tiket belum punya group chat, jadi
                    // jalur antre outbox akan melewatinya. Lampirannya harus
                    // sudah tersimpan sebelum method ini dipanggil.
                    if ($firstMessage) {
                        try {
                            $outbox = app(TeamsOutboxService::class);

                            if ($firstMessageKind === self::FIRST_MESSAGE_AGENT) {
                                $outbox->queueAgentReply($firstMessage);
                            } else {
                                $outbox->queueCustomerMessage($firstMessage);
                            }
                        } catch (\Throwable $e) {
                            $who = $firstMessageKind === self::FIRST_MESSAGE_AGENT ? '' : ' customer';

                            Log::warning($logContext . ': gagal mengantre pesan pertama' . $who . ' ke Teams (non-fatal)', [
                                'ticket_id'  => $ticket->ticket_id,
                                'message_id' => $firstMessage->id,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning($logContext . ': gagal membuat group chat Teams lewat Graph (non-fatal)', [
                    'ticket_id' => $ticket->ticket_id,
                    'error'     => $e->getMessage(),
                ]);
            }

            $powerAutomate->dispatchAfterResponse(
                PowerAutomateService::FLOW_TICKET_VALIDATED,
                [
                    'ticket'       => $payloadTicket,
                    'module_leads' => $leads,
                    'lead_emails'  => $leadEmails,
                    // Rujukan user untuk "Get an @mention token" di flow 5:
                    // email untuk lead internal, object id untuk lead guest.
                    // Konektor menolak alamat Gmail guest tetapi menerima object
                    // id-nya (terbukti 20 Okt 2026). Guest yang tidak bisa
                    // di-resolve dibuang supaya loop mention tidak berhenti;
                    // begitu juga guest yang masih ditunda — mereka belum
                    // anggota grup saat kartu ini diposting.
                    'lead_teams_ids' => array_values(array_filter(array_map(
                        static fn ($email) => app(TeamsGuestService::class)->teamsUserRef((string) $email),
                        array_filter(
                            $leadEmails,
                            static fn ($email) => !in_array(mb_strtolower(trim((string) $email)), $pendingGuests, true)
                        )
                    ))),
                    // Bahan group chat tiket (aksi Teams "Create a chat"):
                    // `chat.topic` jadi nama grup sekaligus kunci yang dipakai
                    // flow "ticket member added" untuk menemukan grup ini lagi
                    // lewat aksi "List chats". `chat.members_csv` berisi lead
                    // modul PLUS tim Delivery Support tiket ini (Delivery Owner,
                    // Support Manager, CO PM, Support Admin) PLUS pemegang role
                    // penjaga (Delivery Support Head dan Delivery Support
                    // Service Helpdesk).
                    'chat'         => $chatPayload,
                    // Identitas delivery support tiket + timnya, untuk baris
                    // "Delivery Support" di Adaptive Card. Emailnya sendiri
                    // sudah ikut lewat chat.members_csv di atas.
                    'delivery_support' => $powerAutomate->deliverySupportPayload(
                        (int) $ticket->ticket_id
                    ),
                    'validated_by' => [
                        'id'    => $sessionUser['id'] ?? null,
                        'name'  => $sessionUser['name'] ?? null,
                        'email' => $sessionUser['email'] ?? null,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            Log::warning($logContext . ': gagal menyiapkan notifikasi Teams (non-fatal)', [
                'ticket_id' => $ticket->ticket_id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
