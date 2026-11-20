<?php

namespace App\Http\Controllers\HR_General;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Branch;
use App\Models\DeliveryProject;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectAccessRule;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Ticket;
use App\Services\ObjectFund\ObjectAccessPolicy;
use App\Services\ObjectFund\ProjectBudgetReader;
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
            'access_project'               => ['required', Rule::in(ObjectAccessPolicy::MODES)],
            'access_support'               => ['required', Rule::in(ObjectAccessPolicy::MODES)],
        ], [
            'access_project.in' => 'Choose who can charge costs to projects.',
            'access_support.in' => 'Choose who can charge costs to tickets.',
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
        app(ObjectAccessPolicy::class)->forget();

        Log::info('Reimbursement object flow settings updated.', [
            'actor_id' => session('user.id'), 'before' => $before, 'after' => $settings->only(array_keys($data)),
        ]);

        return back()->with('success', 'Flow and policy saved. New documents use them from now on.');
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
            'amount'      => ['nullable', 'numeric', 'gt:0', 'max:99999999999999'],
            'source'      => ['nullable', Rule::in([FundPool::SOURCE_MANUAL, FundPool::SOURCE_PROJECT_PLAN])],
            'source_cost_id' => ['nullable', 'integer'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ], [
            'amount.gt'       => 'The allocation must be more than zero.',
        ]);

        $source = $data['source'] ?? FundPool::SOURCE_MANUAL;

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
        $costId   = null;
        $amount   = isset($data['amount']) ? (float) $data['amount'] : 0.0;

        if ($source === FundPool::SOURCE_PROJECT_PLAN) {
            if ($data['object_type'] !== FundPool::TYPE_PROJECT) {
                return back()->withInput()->with('error', 'Only project allocations can follow the project plan. Tickets have no plan budget, so enter the amount.');
            }

            $row = app(ProjectBudgetReader::class)->leafCosts($ref)->firstWhere('id', (int) ($data['source_cost_id'] ?? 0));

            if (!$row || $row->amount <= 0) {
                return back()->withInput()->with('error', 'Choose a plan cost line that has a budget or released amount, or switch to entering the amount.');
            }

            $costId   = (int) $row->id;
            $amount   = (float) $row->amount;
            $category = $category !== '' ? $category : (string) $row->name;
        } elseif ($amount <= 0) {
            return back()->withInput()->with('error', 'Enter the allocation amount.');
        }

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
            'amount'      => $amount,
            'source'      => $source,
            'source_cost_id' => $costId,
            'status'      => FundPool::STATUS_APPROVED,
            'proposed_by' => session('user.id'),
            'approved_by' => session('user.id'),
            'approved_at' => now(),
            'notes'       => $data['notes'] ?? null,
        ]);

        Log::info('Reimbursement fund allocation created.', ['actor_id' => session('user.id'), 'object' => $data['object_type'], 'ref' => $ref, 'category' => $category, 'amount' => $amount, 'source' => $source]);

        return back()->with('success', 'Allocation for ' . $label . ' saved.');
    }

    /** Ubah nominal atau aktif/nonaktif sebuah alokasi. */
    public function updatePool(Request $request, FundPool $pool)
    {
        // Alokasi yang mengikuti rencana project: nominal dibaca dari sana; hanya aktif/nonaktif atau putus tautan.
        if ($pool->isLinked() && !$request->boolean('unlink')) {
            $pool->update(['is_active' => $request->boolean('is_active')]);

            return back()->with('success', 'Allocation for ' . $pool->label() . ' updated. Its amount follows the project plan.');
        }

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
            'amount'         => $data['amount'],
            'is_active'      => $request->boolean('is_active'),
            'source'         => FundPool::SOURCE_MANUAL,
            'source_cost_id' => null,
        ]);

        Log::info('Reimbursement fund allocation changed.', ['actor_id' => session('user.id'), 'pool_id' => $pool->id, 'before' => $before, 'after' => (float) $data['amount']]);

        return back()->with('success', 'Allocation for ' . $pool->label() . ' updated.');
    }
    /** Baris rencana biaya sebuah project, untuk dipilih sebagai sumber alokasi. */
    public function projectCosts(Request $request)
    {
        $projectId = (int) $request->query('project_id');
        $rows      = app(ProjectBudgetReader::class)->leafCosts($projectId)
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => trim($r->code . ' ' . $r->name), 'amount' => $r->amount, 'cost_type' => $r->cost_type]);

        return response()->json(['success' => true, 'message' => '', 'data' => $rows->values()]);
    }

    /** Pengecualian aturan akses untuk satu project atau tiket. */
    public function storeAccessRule(Request $request)
    {
        $data = $request->validate([
            'object_type'   => ['required', Rule::in([FundPool::TYPE_PROJECT, FundPool::TYPE_SUPPORT])],
            'project_id'    => ['nullable', 'integer', 'exists:delivery_projects,id'],
            'ticket_number' => ['nullable', 'string', 'max:50'],
            'mode'          => ['required', Rule::in(ObjectAccessPolicy::MODES)],
            'notes'         => ['nullable', 'string', 'max:255'],
        ], ['mode.in' => 'Choose who can charge to it.']);

        if ($data['object_type'] === FundPool::TYPE_PROJECT) {
            $project = $data['project_id'] ? DeliveryProject::find($data['project_id']) : null;

            if (!$project) {
                return back()->withInput()->with('error', 'Choose the project this exception is for.');
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

        ObjectAccessRule::updateOrCreate(
            ['object_type' => $data['object_type'], 'ref_id' => $ref],
            ['ref_label' => $label, 'mode' => $data['mode'], 'notes' => $data['notes'] ?? null, 'updated_by' => session('user.id')]
        );
        app(ObjectAccessPolicy::class)->forget();

        Log::info('Reimbursement access exception saved.', ['actor_id' => session('user.id'), 'object' => $data['object_type'], 'ref' => $ref, 'mode' => $data['mode']]);

        return back()->with('success', 'Exception for ' . $label . ' saved: ' . ObjectAccessPolicy::MODE_LABELS[$data['mode']] . '.');
    }

    public function destroyAccessRule(ObjectAccessRule $rule)
    {
        $label = $rule->ref_label;
        $rule->delete();
        app(ObjectAccessPolicy::class)->forget();

        Log::info('Reimbursement access exception removed.', ['actor_id' => session('user.id'), 'object' => $rule->object_type, 'ref' => $rule->ref_id]);

        return back()->with('success', 'Exception for ' . $label . ' removed. The default rule applies again.');
    }
}
