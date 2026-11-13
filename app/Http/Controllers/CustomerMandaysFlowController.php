<?php

namespace App\Http\Controllers;

use App\Models\CustomerMandays;
use App\Models\CustomerMandaysApprovalStep;
use App\Models\Employee;
use App\Models\EmployeeRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Management > Flow > Customer Mandays Approval.
 *
 * Mengatur SIAPA penyetuju Level 1 dan Level 2 (jumlah level tetap 2). Disimpan
 * sekaligus lewat satu tombol "Save Workflow". Perubahan hanya berlaku untuk
 * proposal yang disubmit setelahnya; proposal berjalan memegang snapshot sendiri.
 */
class CustomerMandaysFlowController extends Controller
{
    public function page()
    {
        $steps = CustomerMandaysApprovalStep::ordered()->get();

        $roles = EmployeeRole::orderBy('name')->get(['id', 'name']);

        $employees = Employee::with(['basicData', 'roles'])
            ->where('is_active', true)
            ->get()
            ->map(fn (Employee $e) => [
                'id'    => (int) $e->employee_id,
                'name'  => trim(($e->basicData?->first_name ?? '') . ' ' . ($e->basicData?->last_name ?? '')) ?: ('#' . $e->employee_id),
                'roles' => $e->roles->pluck('name')->values()->all(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $roleMemberCounts = DB::table('employee_role_assignment')
            ->join('employee', 'employee.employee_id', '=', 'employee_role_assignment.employee_id')
            ->where('employee.is_active', true)
            ->select('role_id', DB::raw('COUNT(DISTINCT employee_role_assignment.employee_id) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $openCount = CustomerMandays::where('status', CustomerMandays::STATUS_PENDING_APPROVAL)->count();

        return view('management.flow.customer-mandays', [
            'steps'            => $steps,
            'roles'            => $roles,
            'employees'        => $employees,
            'roleMemberCounts' => $roleMemberCounts,
            'openCount'        => $openCount,
        ]);
    }

    /** POST — simpan Level 1 & Level 2 sekaligus. Urutan baris di layar = urutan level. */
    public function sync(Request $request)
    {
        $rows = $request->input('steps', []);

        if (!is_array($rows) || count($rows) !== CustomerMandaysApprovalStep::LEVELS) {
            return back()->with('error', 'The flow must have exactly ' . CustomerMandaysApprovalStep::LEVELS . ' approval levels.');
        }

        $existing = CustomerMandaysApprovalStep::ordered()->get()->keyBy('id');
        $errors   = [];
        $clean    = [];

        foreach (array_values($rows) as $i => $row) {
            $n      = $i + 1;
            $id     = (int) ($row['id'] ?? 0);
            $name   = trim((string) ($row['name'] ?? ''));
            $roleId = !empty($row['role_id']) ? (int) $row['role_id'] : null;
            $empIds = collect($row['employee_ids'] ?? [])->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();

            if (!$existing->has($id)) {
                $errors[] = "Level {$n}: this level no longer exists. Reload the page and try again.";
                continue;
            }
            if ($name === '' || mb_strlen($name) > 100) {
                $errors[] = "Level {$n}: fill in a step name (at most 100 characters).";
            }
            if ($roleId && !EmployeeRole::whereKey($roleId)->exists()) {
                $errors[] = "Level {$n}: the chosen position no longer exists.";
            }
            if ($empIds && Employee::whereIn('employee_id', $empIds)->count() !== count($empIds)) {
                $errors[] = "Level {$n}: one of the chosen names no longer exists.";
            }
            if (!$roleId && !$empIds) {
                $errors[] = "Level {$n}: choose a position, or at least one name.";
            }
            if ($roleId && !$empIds && !DB::table('employee_role_assignment')->where('role_id', $roleId)->exists()) {
                $errors[] = "Level {$n}: nobody holds that position, so nobody could approve this level.";
            }

            $clean[] = [
                'id'                    => $id,
                'order_seq'             => $n,
                'name'                  => $name,
                'approver_type'         => $empIds ? CustomerMandaysApprovalStep::TYPE_EMPLOYEE : CustomerMandaysApprovalStep::TYPE_ROLE,
                'approver_role_id'      => $roleId,
                'approver_employee_ids' => $empIds ?: null,
            ];
        }

        if (count(array_unique(array_column($clean, 'id'))) !== count($clean)) {
            $errors[] = 'The same level was sent twice. Reload the page and try again.';
        }

        if ($errors) {
            return back()->withInput()->with('error', implode(' ', $errors));
        }

        $before = $existing->map(fn ($s) => $s->only(['order_seq', 'name', 'approver_type', 'approver_role_id', 'approver_employee_ids']))->all();

        DB::transaction(function () use ($clean) {
            // Pindahkan dulu ke rentang sementara agar unique(order_seq) tidak bentrok saat ditukar.
            foreach ($clean as $row) {
                CustomerMandaysApprovalStep::whereKey($row['id'])->update(['order_seq' => 100 + $row['order_seq']]);
            }
            foreach ($clean as $row) {
                CustomerMandaysApprovalStep::find($row['id'])->update(collect($row)->except('id')->all());
            }
        });

        Log::info('Customer mandays approval flow updated', [
            'actor_id' => session('user')['id'] ?? null,
            'before'   => $before,
            'after'    => $clean,
        ]);

        return redirect()
            ->route('management.flow.customer-mandays.index')
            ->with('success', 'Workflow saved. It applies to proposals submitted from now on.');
    }
}
