<?php

namespace App\Services\ObjectFund;

use App\Models\DeliveryProject;
use App\Models\Employee;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Reimbursement\ReimbursementApprovalStep;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan penyetuju per Object saat dokumen dikirim.
 *
 *  Project : Project Admin dan Project Manager (urutan dan keikutsertaan diatur).
 *  Support : Ticket Lead, lalu Support Manager bila diaktifkan.
 *  Office  : penanggung jawab cabang dari pengaturan (role lalu orang).
 *
 * Aturan bersama: orang yang sama hanya diminta sekali, pemohon yang menjadi
 * penyetuju dilewati dan dicatat, penyetuju nonaktif menolak pengiriman, dan
 * peran yang kosong memakai pengganti dari pengaturan (role lalu orang). Hasil
 * dibekukan ke dokumen oleh ReimbursementService::snapshotSteps().
 */
class ObjectApproverResolver
{
    /** @var array<int, true> */
    private array $seen = [];

    /** @var array<int, string> */
    private array $skipped = [];

    private int $requesterId = 0;

    /**
     * @param  array<int, array<string, mixed>>  $items  baris item yang sudah dinormalisasi
     * @return array{steps: array<int, array<string, mixed>>, error: ?string, skipped: array<int, string>}
     */
    public function resolve(array $items, int $requesterId, ObjectFlowSetting $settings): array
    {
        $this->seen        = [];
        $this->skipped     = [];
        $this->requesterId = $requesterId;

        $projects = $this->distinct($items, 'project', 'delivery_project_id');
        $tickets  = $this->distinct($items, 'support', 'ticket_id');
        $branches = $this->distinct($items, 'branch', 'branch_id');

        $steps = [];

        try {
            foreach ($projects as $id) {
                array_push($steps, ...$this->projectSteps($id, $settings));
            }
            foreach ($tickets as $id) {
                array_push($steps, ...$this->supportSteps($id, $settings));
            }
            foreach ($branches as $id) {
                array_push($steps, ...$this->officeSteps($id));
            }
        } catch (\DomainException $e) {
            return ['steps' => [], 'error' => $e->getMessage(), 'skipped' => $this->skipped];
        }

        return ['steps' => $steps, 'error' => null, 'skipped' => $this->skipped];
    }

    /** @return array<int, int> */
    private function distinct(array $items, string $costCenter, string $column): array
    {
        $ids = [];

        foreach ($items as $item) {
            if (($item['cost_center_type'] ?? null) === $costCenter && !empty($item[$column])) {
                $ids[(int) $item[$column]] = (int) $item[$column];
            }
        }

        return array_values($ids);
    }

    // ── Project ─────────────────────────────────────────────────────────────

    private function projectSteps(int $projectId, ObjectFlowSetting $s): array
    {
        $project = DeliveryProject::find($projectId);

        if (!$project) {
            throw new \DomainException('The selected project no longer exists. Choose another project.');
        }

        if ($project->is_closed) {
            throw new \DomainException('Project "' . $project->name . '" is closed. Choose a project that is still running.');
        }

        $slots = [
            'admin'   => ['enabled' => $s->project_admin_enabled,   'label' => 'Project Admin',   'id' => $project->project_admin_id ?: $this->pivotEmployee($projectId, ['Project Admin'])],
            'manager' => ['enabled' => $s->project_manager_enabled, 'label' => 'Project Manager', 'id' => $project->project_manager_id ?: $this->pivotEmployee($projectId, ['Project Manager', 'Lead'])],
        ];

        $order = $s->project_order === ObjectFlowSetting::ORDER_MANAGER_FIRST ? ['manager', 'admin'] : ['admin', 'manager'];
        $out   = [];

        foreach ($order as $key) {
            if (!$slots[$key]['enabled']) {
                continue;
            }

            $step = $this->slot(
                $slots[$key]['id'] ? (int) $slots[$key]['id'] : null,
                $slots[$key]['label'] . ': ' . $project->name,
                'Project "' . $project->name . '" has no ' . $slots[$key]['label'],
                $s->project_fallback_employee_id,
                $s->project_fallback_role_id,
                'Ask Finance to set a fallback approver in Reimbursement Settings.'
            );

            if ($step) {
                $out[] = $step;
            }
        }

        return $out;
    }

    /** @param array<int, string> $roles */
    private function pivotEmployee(int $projectId, array $roles): ?int
    {
        $id = DB::table('delivery_project_employee')
            ->where('delivery_projects_id', $projectId)
            ->whereIn('role', $roles)
            ->whereNotNull('employee_id')
            ->orderBy('id')
            ->value('employee_id');

        return $id ? (int) $id : null;
    }

    // ── Support ─────────────────────────────────────────────────────────────

    private function supportSteps(int $ticketId, ObjectFlowSetting $s): array
    {
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            throw new \DomainException('The selected ticket no longer exists. Choose another ticket.');
        }

        $out = [];

        if ($s->support_lead_enabled) {
            $step = $this->slot(
                $ticket->ticket_lead_id ? (int) $ticket->ticket_lead_id : null,
                'Ticket Lead: ' . $ticket->ticket_number,
                'Ticket ' . $ticket->ticket_number . ' has no Ticket Lead',
                $s->support_fallback_employee_id,
                $s->support_fallback_role_id,
                'Ask the ticket owner to assign a lead, or set a fallback approver in Reimbursement Settings.'
            );

            if ($step) {
                $out[] = $step;
            }
        }

        if ($s->support_manager_enabled) {
            $managerId = DB::table('delivery_support_activities as a')
                ->join('delivery_support as d', 'd.id', '=', 'a.delivery_support_id')
                ->where('a.ticket_id', $ticketId)
                ->whereNotNull('d.support_manager_id')
                ->value('d.support_manager_id');

            // Support Manager bersifat opsional: tiket tanpa kontrak Support
            // tidak punya manajer, dan itu bukan alasan menolak pengiriman.
            if ($managerId) {
                $step = $this->slot((int) $managerId, 'Support Manager: ' . $ticket->ticket_number, '', null, null, '');
                if ($step) {
                    $out[] = $step;
                }
            }
        }

        return $out;
    }

    // ── Office ──────────────────────────────────────────────────────────────

    private function officeSteps(int $branchId): array
    {
        $setting = OfficeSetting::with('branch')->where('branch_id', $branchId)->first();
        $name    = $setting?->branch?->name ?? ('#' . $branchId);

        if (!$setting || (!$setting->approver_employee_id && !$setting->approver_role_id)) {
            throw new \DomainException('Branch "' . $name . '" has no responsible person yet. Ask Finance to set one in Reimbursement Settings, then submit again.');
        }

        $step = $this->slot(
            $setting->approver_employee_id ? (int) $setting->approver_employee_id : null,
            'Branch Approver: ' . $name,
            'Branch "' . $name . '" has no responsible person',
            null,
            $setting->approver_role_id,
            'Ask Finance to set one in Reimbursement Settings.'
        );

        return $step ? [$step] : [];
    }

    // ── Penyelesaian satu peran ─────────────────────────────────────────────

    /**
     * Satu peran menjadi satu langkah, atau null bila dilewati.
     *
     * @return array<string, mixed>|null
     */
    private function slot(?int $employeeId, string $name, string $missingMessage, ?int $fallbackEmployee, ?int $fallbackRole, string $hint): ?array
    {
        if ($employeeId !== null) {
            return $this->employeeStep($employeeId, $name);
        }

        if ($fallbackEmployee) {
            return $this->employeeStep((int) $fallbackEmployee, $name);
        }

        if ($fallbackRole) {
            return [
                'name'                  => $name,
                'approver_type'         => ReimbursementApprovalStep::TYPE_ROLE,
                'approver_role_id'      => (int) $fallbackRole,
                'approver_employee_ids' => null,
                'signs_as'              => null,
            ];
        }

        if ($missingMessage === '') {
            return null;
        }

        throw new \DomainException($missingMessage . ' and no fallback approver is set. ' . $hint);
    }

    /** @return array<string, mixed>|null */
    private function employeeStep(int $employeeId, string $name): ?array
    {
        $employee = Employee::with('basicData')->find($employeeId);

        if (!$employee || !$employee->is_active) {
            $who = $employee ? $employee->fullNameLabel() : 'Employee #' . $employeeId;

            throw new \DomainException($name . ' is assigned to ' . $who . ', who is no longer active. Ask an administrator to replace them, then submit again.');
        }

        if ($employeeId === $this->requesterId) {
            $this->skipped[] = $name . ' skipped because the approver is the requester.';

            return null;
        }

        if (isset($this->seen[$employeeId])) {
            return null;
        }

        $this->seen[$employeeId] = true;

        return [
            'name'                  => $name,
            'approver_type'         => ReimbursementApprovalStep::TYPE_EMPLOYEE,
            'approver_role_id'      => null,
            'approver_employee_ids' => [$employeeId],
            'signs_as'              => null,
        ];
    }
}
