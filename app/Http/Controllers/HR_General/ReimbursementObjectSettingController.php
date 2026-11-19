<?php

namespace App\Http\Controllers\HR_General;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Branch;
use App\Models\DeliveryProject;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Pengaturan konsep Object (Project / Office / Support) untuk Reimbursement:
 * alur dan kebijakan, dana dan penanggung jawab per cabang, dan alokasi dana
 * project atau tiket. Dijaga slug `general.settings.reimbursement`.
 *
 * Mematikan saklar `enabled` mengembalikan Reimbursement ke perilaku lama
 * tanpa menghapus data apa pun.
 */
class ReimbursementObjectSettingController extends Controller
{
    /** Alur dan kebijakan. */
    public function updateFlow(Request $request)
    {
        $data = $request->validate([
            'project_order'                => ['required', Rule::in([ObjectFlowSetting::ORDER_ADMIN_FIRST, ObjectFlowSetting::ORDER_MANAGER_FIRST])],
            'project_fallback_role_id'     => ['nullable', 'integer', 'exists:employee_role,id'],
            'project_fallback_employee_id' => ['nullable', 'integer', 'exists:employee,employee_id'],
            'support_fallback_role_id'     => ['nullable', 'integer', 'exists:employee_role,id'],
            'support_fallback_employee_id' => ['nullable', 'integer', 'exists:employee,employee_id'],
            'policy_project'               => ['required', Rule::in(ObjectFlowSetting::POLICIES)],
            'policy_office'                => ['required', Rule::in(ObjectFlowSetting::POLICIES)],
            'policy_support'               => ['required', Rule::in(ObjectFlowSetting::POLICIES)],
        ], [
            'policy_project.in' => 'Choose Warn or Block for project funds.',
            'policy_office.in'  => 'Choose Warn or Block for office funds.',
            'policy_support.in' => 'Choose Warn or Block for support funds.',
        ]);

        foreach (['enabled', 'cash_advance_enabled', 'project_admin_enabled', 'project_manager_enabled', 'support_lead_enabled', 'support_manager_enabled'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        if (($data['enabled'] || $data['cash_advance_enabled']) && !$data['project_admin_enabled'] && !$data['project_manager_enabled']) {
            return back()->withInput()->with('error', 'Turn on at least one project approval step (Project Admin or Project Manager), or projects would have no approver.');
        }

        $data['updated_by'] = session('user.id');

        $settings = ObjectFlowSetting::current();
        $before   = $settings->only(array_keys($data));
        $settings->update($data);
        ObjectFlowSetting::forgetCache();

        Log::info('Reimbursement object flow settings updated.', [
            'actor_id' => session('user.id'), 'before' => $before, 'after' => $settings->only(array_keys($data)),
        ]);

        return back()->with('success', 'Object and fund settings saved.');
    }

    /** Dana dan penanggung jawab per cabang. Master cabang tidak diubah. */
    public function syncOffices(Request $request)
    {
        $rows = $request->validate([
            'offices'                        => ['required', 'array'],
            'offices.*.branch_id'            => ['required', 'integer', 'exists:branches,id'],
            'offices.*.monthly_amount'       => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'offices.*.approver_role_id'     => ['nullable', 'integer', 'exists:employee_role,id'],
            'offices.*.approver_employee_id' => ['nullable', 'integer', 'exists:employee,employee_id'],
        ], [
            'offices.*.monthly_amount.numeric' => 'The monthly amount must be a number.',
            'offices.*.monthly_amount.min'     => 'The monthly amount cannot be negative.',
        ])['offices'];

        $problems = [];

        DB::transaction(function () use ($rows, $request, &$problems) {
            foreach ($rows as $key => $row) {
                $active = $request->boolean("offices.{$key}.fund_active");
                $amount = (float) ($row['monthly_amount'] ?? 0);

                if ($active && $amount <= 0) {
                    $name = Branch::find($row['branch_id'])?->name ?? ('#' . $row['branch_id']);
                    $problems[] = "{$name}: turn the fund off, or enter an amount above zero.";
                    continue;
                }

                OfficeSetting::updateOrCreate(
                    ['branch_id' => (int) $row['branch_id']],
                    [
                        'fund_active'          => $active,
                        'monthly_amount'       => $amount,
                        'approver_role_id'     => $row['approver_role_id'] ?? null,
                        'approver_employee_id' => $row['approver_employee_id'] ?? null,
                    ]
                );
            }
        });

        if ($problems !== []) {
            return back()->withInput()->with('error', 'Some offices were not saved. ' . implode(' ', $problems));
        }

        return back()->with('success', 'Office funds and responsible persons saved.');
    }

    /** Alokasi baru untuk project atau tiket. */
    public function storePool(Request $request)
    {
        $data = $request->validate([
            'object_type' => ['required', Rule::in([FundPool::TYPE_PROJECT, FundPool::TYPE_SUPPORT])],
            'project_id'  => ['nullable', 'integer', 'exists:delivery_projects,id'],
            'ticket_number' => ['nullable', 'string', 'max:50'],
            'category'    => ['nullable', 'string', 'max:60'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:99999999999999'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ], [
            'amount.gt'       => 'The allocation must be more than zero.',
            'amount.required' => 'Enter the allocation amount.',
        ]);

        if ($data['object_type'] === FundPool::TYPE_PROJECT) {
            $project = $data['project_id'] ? DeliveryProject::find($data['project_id']) : null;

            if (!$project) {
                return back()->withInput()->with('error', 'Choose the project this allocation is for.');
            }

            $ref   = (int) $project->id;
            $label = trim(($project->io_number ? $project->io_number . ' - ' : '') . $project->name);
        } else {
            $ticket = Ticket::where('ticket_number', trim((string) ($data['ticket_number'] ?? '')))->first();

            if (!$ticket) {
                return back()->withInput()->with('error', 'Ticket "' . trim((string) ($data['ticket_number'] ?? '')) . '" was not found. Type the full ticket number.');
            }

            $ref   = (int) $ticket->ticket_id;
            $label = (string) $ticket->ticket_number;
        }

        $category = trim((string) ($data['category'] ?? ''));

        $exists = FundPool::where('object_type', $data['object_type'])->where('ref_id', $ref)
            ->where('category', $category)->where('period', '')->exists();

        if ($exists) {
            return back()->withInput()->with('error', $label . ($category !== '' ? ' / ' . $category : '') . ' already has an allocation. Edit the existing one instead.');
        }

        FundPool::create([
            'object_type' => $data['object_type'],
            'ref_id'      => $ref,
            'ref_label'   => $label,
            'category'    => $category,
            'period'      => '',
            'amount'      => $data['amount'],
            'status'      => FundPool::STATUS_APPROVED,
            'proposed_by' => session('user.id'),
            'approved_by' => session('user.id'),
            'approved_at' => now(),
            'notes'       => $data['notes'] ?? null,
        ]);

        Log::info('Reimbursement fund allocation created.', ['actor_id' => session('user.id'), 'object' => $data['object_type'], 'ref' => $ref, 'category' => $category, 'amount' => $data['amount']]);

        return back()->with('success', 'Allocation for ' . $label . ' saved.');
    }

    /** Ubah nominal atau aktif/nonaktif sebuah alokasi. */
    public function updatePool(Request $request, FundPool $pool)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999999999'],
        ], ['amount.gt' => 'The allocation must be more than zero.']);

        $committed = (float) $pool->entries()->where('status', 'committed')->sum('amount');

        // Alokasi di bawah yang sudah terpakai membuat sisa negatif tanpa sebab
        // yang jelas; ditolak dengan angka yang membantu pengguna memperbaikinya.
        if ((float) $data['amount'] < $committed) {
            return back()->with('error', 'The allocation cannot be lower than what approved documents already used (Rp ' . number_format($committed, 0, ',', '.') . ').');
        }

        $before = (float) $pool->amount;
        $pool->update([
            'amount'    => $data['amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        Log::info('Reimbursement fund allocation changed.', ['actor_id' => session('user.id'), 'pool_id' => $pool->id, 'before' => $before, 'after' => (float) $data['amount']]);

        return back()->with('success', 'Allocation for ' . $pool->label() . ' updated.');
    }
}
