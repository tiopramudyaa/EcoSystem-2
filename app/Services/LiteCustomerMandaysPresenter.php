<?php

namespace App\Services;

use App\Models\CustomerMandays;
use App\Models\Employee;
use App\Models\Ticket;

/**
 * Bentuk data Customer Mandays untuk Lite API (PWA).
 *
 * Siapa yang boleh melihat mengikuti panel di halaman ticket web
 * (resources/views/ticket/show.blade.php): ticket harus sudah punya PIC, dan
 * user adalah penyetuju level yang sedang menunggu, pemegang izin Review
 * Mandays Proposal, atau pemegang izin Head Mandays. Siapa yang boleh
 * bertindak tetap diputuskan CustomerMandaysApprovalService::canAct().
 */
class LiteCustomerMandaysPresenter
{
    public function __construct(private CustomerMandaysApprovalService $approvalService)
    {
    }

    public function latestProposal(Ticket $ticket): ?CustomerMandays
    {
        return CustomerMandays::where('ticket_id', $ticket->ticket_id)
            ->latestVersion()
            ->with(['details', 'approvals', 'proposedByAgent.basicData'])
            ->first();
    }

    /** @return array{allowed: bool, reason: ?string} */
    public function gate(?CustomerMandays $proposal, int $employeeId): array
    {
        if (!$proposal) {
            return ['allowed' => false, 'reason' => 'No proposal found.'];
        }

        $gate = $this->approvalService->canAct($proposal, $employeeId);

        return ['allowed' => $gate['allowed'], 'reason' => $gate['allowed'] ? null : $gate['reason']];
    }

    public function canView(?Employee $employee, Ticket $ticket, bool $canAct): bool
    {
        if (!$employee || $ticket->ticket_lead_id === null) {
            return false;
        }

        return $canAct
            || $employee->canAccessMenu('ticket.review-mandays')
            || $employee->canAccessMenu('ticket.head-mandays');
    }

    /**
     * Ringkasan untuk GET /api/lite/tickets/{id}. Null bila user tidak berhak melihat,
     * supaya PWA cukup memeriksa null untuk menyembunyikan bagian ini.
     */
    public function summary(Ticket $ticket, int $employeeId): ?array
    {
        $proposal = $this->latestProposal($ticket);
        $gate     = $this->gate($proposal, $employeeId);

        if (!$this->canView(Employee::find($employeeId), $ticket, $gate['allowed'])) {
            return null;
        }

        return [
            'mandays_id'    => $proposal?->id,
            'version'       => $proposal?->version,
            'status'        => $proposal?->status ?? 'none',
            'total_mandays' => $proposal ? (float) $proposal->total_mandays : null,
            'step_order'    => $proposal?->current_step_order,
            'waiting_step'  => $proposal ? $this->approvalService->currentStepName($proposal) : null,
            'can_act'       => $gate['allowed'],
        ];
    }

    /** Detail lengkap untuk layar approval di PWA. */
    public function detail(CustomerMandays $p): array
    {
        $proposedBy = $p->proposedByAgent?->basicData;

        // Grid Activity × Module, urutan activity mengikuti urutan input.
        $modules = $p->details->pluck('module')->unique()->sort()->values()->all();
        $rows    = [];
        foreach ($p->details as $d) {
            $act = $d->activity ?? 'General';
            $rows[$act] ??= ['activity' => $act, 'mandays' => [], 'total' => 0.0];
            $rows[$act]['mandays'][$d->module] = ($rows[$act]['mandays'][$d->module] ?? 0) + (float) $d->mandays;
            $rows[$act]['total'] += (float) $d->mandays;
        }
        $columnTotals = [];
        foreach ($modules as $mod) {
            $columnTotals[$mod] = (float) $p->details->where('module', $mod)->sum(fn ($d) => (float) $d->mandays);
        }

        return [
            'mandays_id'       => $p->id,
            'version'          => $p->version,
            'status'           => $p->status,
            'step_order'       => $p->current_step_order,
            'waiting_step'     => $this->approvalService->currentStepName($p),
            'total_mandays'    => (float) $p->total_mandays,
            'description'      => $p->description,
            'proposal_notes'   => $p->proposal_notes,
            'proposed_by'      => $proposedBy ? trim(($proposedBy->first_name ?? '') . ' ' . ($proposedBy->last_name ?? '')) : null,
            'proposed_at'      => $p->proposed_at?->toISOString(),
            'customer_notes'   => $p->customer_notes,
            'modules'          => $modules,
            'rows'             => array_values($rows),
            'column_totals'    => $columnTotals,
            // Bentuk sama dengan body `details` saat approve, untuk mengisi grid yang bisa diedit.
            'details'          => $p->details->map(fn ($d) => [
                'activity' => $d->activity,
                'module'   => $d->module,
                'mandays'  => (float) $d->mandays,
            ])->values()->all(),
            'approval_trail'   => $this->approvalService->trail($p),
        ];
    }
}
