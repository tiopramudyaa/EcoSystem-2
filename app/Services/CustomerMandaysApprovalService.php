<?php

namespace App\Services;

use App\Models\CustomerMandays;
use App\Models\CustomerMandaysApproval;
use App\Models\CustomerMandaysApprovalStep;
use App\Models\CustomerMandaysDetail;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approval bertingkat Customer Mandays (Level 1 → Level 2 → siap dikirim ke customer).
 *
 * Satu-satunya tempat yang memutuskan siapa boleh approve/reject (canAct()).
 * Controller dan view hanya menampilkan tombol / meneruskan permintaan.
 *
 * Gerbang bisnis mengembalikan ['allowed' => bool, 'reason' => string, ...] dan
 * tidak melempar exception untuk penolakan bisnis.
 */
class CustomerMandaysApprovalService
{
    /** Konfigurasi langkah, urut Level 1, Level 2. */
    public function steps(): Collection
    {
        return CustomerMandaysApprovalStep::ordered()->get();
    }

    /** Pesan galat bila konfigurasi belum lengkap, atau null bila siap dipakai. */
    public function configurationProblem(?Collection $steps = null): ?string
    {
        $steps ??= $this->steps();

        if ($steps->count() < CustomerMandaysApprovalStep::LEVELS) {
            return 'The Customer Mandays approval flow is not configured yet. Ask an administrator to set it up in Management > Flow.';
        }

        foreach ($steps as $step) {
            $hasRole  = $step->approver_type === CustomerMandaysApprovalStep::TYPE_ROLE && $step->approver_role_id;
            $hasNames = $step->approver_type === CustomerMandaysApprovalStep::TYPE_EMPLOYEE && !empty($step->approver_employee_ids);
            if (!$hasRole && !$hasNames) {
                return "Approval level {$step->order_seq} has no approver. Ask an administrator to set it up in Management > Flow.";
            }
        }

        return null;
    }

    // =========================================================================
    // SUBMIT
    // =========================================================================

    /**
     * PIC submit draft → salin konfigurasi ke snapshot, proposal menunggu Level 1.
     *
     * @return array{allowed: bool, reason: string}
     */
    public function submit(CustomerMandays $proposal, Ticket $ticket, int $actorId, string $actorName): array
    {
        if ($proposal->status !== CustomerMandays::STATUS_DRAFT) {
            return ['allowed' => false, 'reason' => 'No draft to submit.'];
        }

        $steps = $this->steps();
        if ($problem = $this->configurationProblem($steps)) {
            return ['allowed' => false, 'reason' => $problem];
        }

        // Riwayat yang sudah bertindak tidak boleh ditulis ulang (R7).
        if ($proposal->approvals()->where('status', '!=', CustomerMandaysApproval::STATUS_WAITING)->exists()) {
            return ['allowed' => false, 'reason' => 'This version already has an approval history. Create a new version instead.'];
        }

        $first = $steps->first();

        DB::transaction(function () use ($proposal, $ticket, $steps, $first) {
            $proposal->approvals()->delete();

            foreach ($steps as $step) {
                CustomerMandaysApproval::create([
                    'customer_mandays_id'   => $proposal->id,
                    'order_seq'             => $step->order_seq,
                    'step_name'             => $step->name,
                    'approver_type'         => $step->approver_type,
                    'approver_role_id'      => $step->approver_role_id,
                    'approver_employee_ids' => $step->approver_employee_ids,
                    'status'                => CustomerMandaysApproval::STATUS_WAITING,
                ]);
            }

            $proposal->update([
                'status'                   => CustomerMandays::STATUS_PENDING_APPROVAL,
                'current_step_order'       => $first->order_seq,
                'submitted_to_customer_at' => now(),
            ]);
            $ticket->update(['mandays_proposal_status' => CustomerMandays::STATUS_PENDING_APPROVAL]);
        });

        $proposal->load('approvals');
        if ($current = $proposal->currentApproval()) {
            $this->notifyApprovers($ticket, $current, $actorId, $actorName);
        }

        return ['allowed' => true, 'reason' => ''];
    }

    // =========================================================================
    // KEWENANGAN
    // =========================================================================

    /**
     * @return array{allowed: bool, reason: string, approval: ?CustomerMandaysApproval}
     */
    public function canAct(CustomerMandays $proposal, int $actorId): array
    {
        $deny = fn (string $reason) => ['allowed' => false, 'reason' => $reason, 'approval' => null];

        if (!$proposal->isInApproval()) {
            return $deny('This proposal is not waiting for internal approval.');
        }

        $proposal->loadMissing('approvals');
        $approval = $proposal->currentApproval();

        if (!$approval || !$approval->isWaiting()) {
            return $deny('This approval level has already been decided.');
        }

        $employee = Employee::find($actorId);
        if (!$employee) {
            return $deny('Unauthenticated.');
        }

        if (!$approval->allows($actorId, $employee->getRoleIds())) {
            return $deny("You are not an approver for \"{$approval->step_name}\".");
        }

        // Self-approval diizinkan (keputusan produk), jadi tidak ada gerbang pemohon di sini.

        return ['allowed' => true, 'reason' => '', 'approval' => $approval];
    }

    /**
     * Proposal (versi terbaru tiap ticket) yang levelnya sedang menunggu karyawan ini.
     * Memakai aturan yang sama dengan canAct(), jadi daftar ini tidak pernah memuat
     * proposal yang tidak boleh ia putuskan.
     *
     * @return Collection<int, CustomerMandays>
     */
    public function pendingFor(int $actorId): Collection
    {
        $employee = Employee::find($actorId);
        if (!$employee) {
            return collect();
        }
        $roleIds = $employee->getRoleIds();

        return $this->openLatestQuery()
            ->with('approvals')
            ->orderBy('updated_at')
            ->get()
            ->filter(function (CustomerMandays $p) use ($actorId, $roleIds) {
                $approval = $p->currentApproval();
                return $approval && $approval->isWaiting() && $approval->allows($actorId, $roleIds);
            })
            ->values();
    }

    /** Jumlah proposal yang ikut tersinkron bila Flow disimpan sekarang (untuk info di halaman Flow). */
    public function openProposalCount(): int
    {
        return $this->openLatestQuery()->count();
    }

    // =========================================================================
    // SINKRON KONFIGURASI KE PROPOSAL BERJALAN
    // =========================================================================

    /**
     * Terapkan konfigurasi Flow terbaru ke level yang BELUM diputuskan pada proposal
     * yang sedang menunggu approval (versi terbaru tiap ticket). Level approved,
     * rejected, dan skipped tidak disentuh, begitu pula status, level aktif, angka
     * mandays, dan versi proposal.
     *
     * Dipanggil di DALAM transaksi penyimpanan Flow. Notifikasi untuk penerima baru
     * di level aktif dikirim setelah commit; kegagalannya tidak membatalkan apa pun (R9).
     *
     * @param  Collection<int, CustomerMandaysApprovalStep>  $steps
     * @return array{proposals: int, levels: int, changes: array<int, array<string, mixed>>}
     */
    public function syncOpenProposals(Collection $steps, int $actorId, string $actorName): array
    {
        $stepsByOrder = $steps->keyBy(fn ($s) => (int) $s->order_seq);

        $proposals = $this->openLatestQuery()->with('ticket')->get()->keyBy('id');
        if ($proposals->isEmpty()) {
            return ['proposals' => 0, 'levels' => 0, 'changes' => []];
        }

        // Kunci baris yang akan dinilai agar approve/reject yang berjalan bersamaan menunggu.
        $rows = CustomerMandaysApproval::query()
            ->whereIn('customer_mandays_id', $proposals->keys())
            ->where('status', CustomerMandaysApproval::STATUS_WAITING)
            ->orderBy('customer_mandays_id')
            ->orderBy('order_seq')
            ->lockForUpdate()
            ->get();

        $changes = [];
        $pending = [];

        foreach ($rows as $row) {
            $step = $stepsByOrder->get((int) $row->order_seq);
            if (!$step) {
                continue;
            }

            $before = $this->definitionOf($row->step_name, $row->approver_type, $row->approver_role_id, $row->approver_employee_ids);
            $after  = $this->definitionOf($step->name, $step->approver_type, $step->approver_role_id, $step->approver_employee_ids);
            if ($before === $after) {
                continue;
            }

            $proposal = $proposals->get($row->customer_mandays_id);
            $isActive = (int) $row->order_seq === (int) $proposal->current_step_order;
            $recipientsBefore = $isActive ? $this->recipientsFor($row) : [];

            // Disalin sama persis dengan submit().
            $values = [
                'step_name'             => $step->name,
                'approver_type'         => $step->approver_type,
                'approver_role_id'      => $step->approver_role_id,
                'approver_employee_ids' => $step->approver_employee_ids,
            ];

            // Update bersyarat: level yang baru saja diputuskan orang lain tidak tertimpa.
            $affected = CustomerMandaysApproval::whereKey($row->id)
                ->where('status', CustomerMandaysApproval::STATUS_WAITING)
                ->update(array_merge($values, [
                    'approver_employee_ids' => $values['approver_employee_ids'] !== null
                        ? json_encode($values['approver_employee_ids'])
                        : null,
                ]));
            if ($affected === 0) {
                continue;
            }

            $row->forceFill($values);

            $newRecipients = [];
            if ($isActive) {
                $newRecipients = array_values(array_diff($this->recipientsFor($row), $recipientsBefore));
                if ($newRecipients && $proposal->ticket) {
                    $pending[] = [$proposal->ticket, clone $row, $newRecipients];
                }
            }

            $changes[] = [
                'customer_mandays_id' => (int) $proposal->id,
                'ticket_id'           => (int) $proposal->ticket_id,
                'order_seq'           => (int) $row->order_seq,
                'active_level'        => $isActive,
                'before'              => $before,
                'after'               => $after,
                'notified'            => $newRecipients,
            ];
        }

        if ($pending) {
            DB::afterCommit(function () use ($pending, $actorId, $actorName) {
                foreach ($pending as [$ticket, $approval, $ids]) {
                    $this->notifyApprovers($ticket, $approval, $actorId, $actorName, $ids);
                }
            });
        }

        return [
            'proposals' => count(array_unique(array_column($changes, 'customer_mandays_id'))),
            'levels'    => count($changes),
            'changes'   => $changes,
        ];
    }

    // =========================================================================
    // APPROVE / REJECT
    // =========================================================================

    /**
     * Approve level yang sedang menunggu. Penyetuju boleh mengubah angka MD
     * ($details) sebelum menyetujui; perubahan disimpan dalam transaksi yang sama.
     *
     * @param  array<int, array{activity: ?string, module: string, mandays: float|int|string}>|null  $details
     * @return array{allowed: bool, reason: string, completed: bool}
     */
    public function approve(CustomerMandays $proposal, Ticket $ticket, int $actorId, string $actorName, ?array $details = null, ?string $notes = null): array
    {
        $gate = $this->canAct($proposal, $actorId);
        if (!$gate['allowed']) {
            return ['allowed' => false, 'reason' => $gate['reason'], 'completed' => false];
        }

        if ($details !== null) {
            $total = collect($details)->sum(fn ($d) => (float) ($d['mandays'] ?? 0));
            if ($total <= 0) {
                return ['allowed' => false, 'reason' => 'Total mandays must be greater than 0.', 'completed' => false];
            }
        }

        /** @var CustomerMandaysApproval $approval */
        $approval  = $gate['approval'];
        $oldTotal  = (float) $proposal->total_mandays;

        $completed = DB::transaction(function () use ($proposal, $ticket, $approval, $actorId, $details, $notes) {
            if ($details !== null) {
                $this->replaceDetails($proposal, $details);
            }

            $approval->update([
                'status'   => CustomerMandaysApproval::STATUS_APPROVED,
                'acted_by' => $actorId,
                'acted_at' => now(),
                'notes'    => $notes ?: null,
            ]);

            $next = $proposal->approvals()
                ->where('order_seq', '>', $approval->order_seq)
                ->where('status', CustomerMandaysApproval::STATUS_WAITING)
                ->orderBy('order_seq')
                ->first();

            if ($next) {
                $proposal->update(['current_step_order' => $next->order_seq]);
                return false;
            }

            // Lolos semua level → siap dikirim ke customer oleh Helpdesk.
            $proposal->update([
                'status'             => CustomerMandays::STATUS_PENDING_HELPDESK,
                'current_step_order' => null,
            ]);
            $ticket->update(['mandays_proposal_status' => CustomerMandays::STATUS_PENDING_HELPDESK]);
            return true;
        });

        Log::info('Customer mandays approval: approved', [
            'customer_mandays_id' => $proposal->id,
            'ticket_id'           => $ticket->ticket_id,
            'step'                => $approval->step_name,
            'actor_id'            => $actorId,
            'total_before'        => $oldTotal,
            'total_after'         => (float) $proposal->fresh()->total_mandays,
            'completed'           => $completed,
        ]);

        $proposal->unsetRelation('approvals');
        $proposal->refresh();

        if ($completed) {
            $this->notifyReadyToSend($ticket, $actorId, $actorName);
        } elseif ($next = $proposal->currentApproval()) {
            $this->notifyApprovers($ticket, $next, $actorId, $actorName);
        }

        return ['allowed' => true, 'reason' => '', 'completed' => $completed];
    }

    /**
     * Reject level yang sedang menunggu → proposal ditutup (rejected), level sisa
     * jadi skipped, PIC merevisi sebagai versi baru.
     *
     * @return array{allowed: bool, reason: string}
     */
    public function reject(CustomerMandays $proposal, Ticket $ticket, int $actorId, string $actorName, string $notes): array
    {
        $gate = $this->canAct($proposal, $actorId);
        if (!$gate['allowed']) {
            return ['allowed' => false, 'reason' => $gate['reason']];
        }

        /** @var CustomerMandaysApproval $approval */
        $approval = $gate['approval'];

        DB::transaction(function () use ($proposal, $ticket, $approval, $actorId, $notes) {
            $approval->update([
                'status'   => CustomerMandaysApproval::STATUS_REJECTED,
                'acted_by' => $actorId,
                'acted_at' => now(),
                'notes'    => $notes,
            ]);

            $this->skipRemaining($proposal);

            $proposal->update([
                'status'             => CustomerMandays::STATUS_REJECTED,
                'current_step_order' => null,
            ]);
            $ticket->update(['mandays_proposal_status' => CustomerMandays::STATUS_REJECTED]);
        });

        Log::info('Customer mandays approval: rejected', [
            'customer_mandays_id' => $proposal->id,
            'ticket_id'           => $ticket->ticket_id,
            'step'                => $approval->step_name,
            'actor_id'            => $actorId,
        ]);

        $this->notifyRejected($ticket, $proposal, $approval->step_name, $notes, $actorId, $actorName);

        return ['allowed' => true, 'reason' => ''];
    }

    /** Tandai semua level yang masih menunggu sebagai skipped (dipakai saat reject / cancel). */
    public function skipRemaining(CustomerMandays $proposal): void
    {
        $proposal->approvals()
            ->where('status', CustomerMandaysApproval::STATUS_WAITING)
            ->update(['status' => CustomerMandaysApproval::STATUS_SKIPPED, 'updated_at' => now()]);
    }

    // =========================================================================
    // TAMPILAN
    // =========================================================================

    /** Jejak approval untuk ditampilkan di modal (Approval Trail). */
    public function trail(CustomerMandays $proposal): array
    {
        $proposal->loadMissing(['approvals.actor.basicData', 'approvals.role']);

        return $proposal->approvals->map(function (CustomerMandaysApproval $a) {
            $actor = $a->actor?->basicData;
            return [
                'order_seq'      => $a->order_seq,
                'step_name'      => $a->step_name,
                'status'         => $a->status,
                'approver_label' => $a->approverLabel(),
                'acted_by_name'  => $actor ? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) : null,
                'acted_at'       => $a->acted_at?->toISOString(),
                'notes'          => $a->notes,
            ];
        })->values()->all();
    }

    /** Nama level yang sedang menunggu, untuk label "Waiting {name}". */
    public function currentStepName(CustomerMandays $proposal): ?string
    {
        $proposal->loadMissing('approvals');
        return $proposal->currentApproval()?->step_name;
    }

    // =========================================================================
    // INTERNAL
    // =========================================================================

    /** Proposal yang sedang di approval internal dan merupakan versi terbaru ticket-nya. */
    private function openLatestQuery()
    {
        return CustomerMandays::query()
            ->where('status', CustomerMandays::STATUS_PENDING_APPROVAL)
            ->whereNotNull('current_step_order')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('customer_mandays as newer')
                  ->whereColumn('newer.ticket_id', 'customer_mandays.ticket_id')
                  ->whereColumn('newer.version', '>', 'customer_mandays.version');
            });
    }

    /** Definisi satu level dalam bentuk normal agar bisa dibandingkan (id karyawan int, unik, terurut). */
    private function definitionOf(?string $name, ?string $type, $roleId, $employeeIds): array
    {
        $ids = array_values(array_unique(array_map('intval', is_array($employeeIds) ? $employeeIds : [])));
        sort($ids);

        return [
            'step_name'             => (string) $name,
            'approver_type'         => (string) $type,
            'approver_role_id'      => $roleId !== null ? (int) $roleId : null,
            'approver_employee_ids' => $ids,
        ];
    }

    private function replaceDetails(CustomerMandays $proposal, array $details): void
    {
        $proposal->details()->delete();

        $total = 0;
        foreach ($details as $d) {
            $md = (float) ($d['mandays'] ?? 0);
            if ($md <= 0) {
                continue;
            }
            CustomerMandaysDetail::create([
                'customer_mandays_id' => $proposal->id,
                'activity'            => $d['activity'] ?? null,
                'module'              => $d['module'],
                'mandays'             => $md,
            ]);
            $total += $md;
        }

        $proposal->update(['total_mandays' => $total]);
    }

    /** employee_id aktif yang termasuk penyetuju sebuah snapshot langkah. */
    private function recipientsFor(CustomerMandaysApproval $approval): array
    {
        if ($approval->approver_type === CustomerMandaysApprovalStep::TYPE_EMPLOYEE) {
            $ids = array_map('intval', $approval->approver_employee_ids ?? []);
        } elseif ($approval->approver_role_id) {
            $ids = DB::table('employee_role_assignment')
                ->where('role_id', $approval->approver_role_id)
                ->pluck('employee_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            return [];
        }

        return Employee::whereIn('employee_id', array_unique($ids))
            ->where('is_active', true)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param  int[]|null  $onlyIds  batasi penerima (dipakai sinkron Flow: hanya penyetuju baru) */
    private function notifyApprovers(Ticket $ticket, CustomerMandaysApproval $approval, int $fromId, string $fromName, ?array $onlyIds = null): void
    {
        $ticketNum = $ticket->ticket_number ?? $ticket->ticket_id;
        $this->send(
            $ticket,
            $onlyIds ?? $this->recipientsFor($approval),
            'customer_mandays_pending_approval',
            $fromId,
            $fromName,
            "Ticket #{$ticketNum}: Customer mandays proposal waiting for your approval ({$approval->step_name})"
        );
    }

    /** Sama seperti notifikasi submit lama: semua pemegang izin review mendapat kabar siap dikirim. */
    private function notifyReadyToSend(Ticket $ticket, int $fromId, string $fromName): void
    {
        try {
            $ids = Employee::query()
                ->whereHas('roles.menus', function ($m) {
                    $m->where('menu.slug', 'ticket.review-mandays')
                      ->where('menu.is_active', true)
                      ->where('role_menu.can_view', true);
                })
                ->where('is_active', true)
                ->pluck('employee_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            Log::warning('CustomerMandaysApprovalService::notifyReadyToSend failed', ['error' => $e->getMessage()]);
            return;
        }

        $ticketNum = $ticket->ticket_number ?? $ticket->ticket_id;
        $this->send(
            $ticket,
            $ids,
            'customer_mandays_proposed',
            $fromId,
            $fromName,
            "Ticket #{$ticketNum}: Customer mandays proposal approved internally, ready to send to the customer"
        );
    }

    private function notifyRejected(Ticket $ticket, CustomerMandays $proposal, string $stepName, string $notes, int $fromId, string $fromName): void
    {
        $ids = array_filter([
            $ticket->ticket_lead_id ? (int) $ticket->ticket_lead_id : null,
            $proposal->proposed_by_agent_id ? (int) $proposal->proposed_by_agent_id : null,
        ]);

        $ticketNum = $ticket->ticket_number ?? $ticket->ticket_id;
        $this->send(
            $ticket,
            $ids,
            'customer_mandays_rejected',
            $fromId,
            $fromName,
            "Ticket #{$ticketNum}: Customer mandays proposal rejected at {$stepName}: " . mb_substr($notes, 0, 80)
        );
    }

    /** Kegagalan notifikasi tidak boleh membatalkan approval (R9). */
    private function send(Ticket $ticket, array $employeeIds, string $type, int $fromId, string $fromName, string $preview): void
    {
        try {
            foreach (array_unique($employeeIds) as $empId) {
                Notification::create([
                    'employee_id'      => $empId,
                    // ticket_id disimpan agar notifikasi Lite/PWA bisa membuka ticket
                    // langsung dan push menampilkan nomor ticket.
                    'ticket_id'        => $ticket->ticket_id,
                    'type'             => $type,
                    'from_employee_id' => $fromId,
                    'from_name'        => $fromName,
                    'preview'          => $preview,
                    'link'             => "/ticket/{$ticket->ticket_id}",
                    'is_read'          => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CustomerMandaysApprovalService notification failed', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }
}
