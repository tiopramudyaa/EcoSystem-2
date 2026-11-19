{{--
    Reimbursement Settings: Objects and funds (Project / Office / Support).

    Variables: $objectFlow (ObjectFlowSetting) · $officeRows · $fundPools · $projectOptions · $roles · $employees
    Inherited from settings.blade.php: $input · $label · $hint
--}}
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $roleSelect = function (string $name, $selected) use ($roles, $input) {
        $html = '<select name="' . $name . '" class="' . $input . '"><option value="">- none -</option>';
        foreach ($roles as $role) {
            $html .= '<option value="' . $role->id . '"' . ((string) $selected === (string) $role->id ? ' selected' : '') . '>' . e($role->name) . '</option>';
        }

        return $html . '</select>';
    };
    $personSelect = function (string $name, $selected) use ($employees, $input) {
        $html = '<select name="' . $name . '" data-searchable="true" data-search-placeholder="Search name…" class="' . $input . '"><option value="">- none -</option>';
        foreach ($employees as $employee) {
            $html .= '<option value="' . $employee->employee_id . '"' . ((string) $selected === (string) $employee->employee_id ? ' selected' : '') . '>' . e($employee->fullNameLabel()) . '</option>';
        }

        return $html . '</select>';
    };
    $check = 'mt-0.5 w-4 h-4 rounded border-gray-300 text-red-800 focus:ring-red-800';
@endphp

<div class="bg-white rounded-xl p-6 shadow-sm space-y-8" id="object-settings">
    <div class="pb-3 border-b-2 border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900">Objects and Funds</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Each reimbursement item is charged to an <strong>Office</strong>, a <strong>Project</strong>, or a <strong>Support</strong> ticket.
            The Object decides who approves the document and which fund is checked. Turning this off returns reimbursement to the previous behaviour; no data is deleted.
        </p>
    </div>

    {{-- ── Setup checklist and quick guide ──────────────────────────────── --}}
    @php
        $missingOffice = $officeRows->filter(fn ($o) => !$o['approver_role_id'] && !$o['approver_employee_id'])->pluck('label');
        $fundedOffices = $officeRows->where('fund_active', true)->count();
        $hasProjectFallback = $objectFlow->project_fallback_role_id || $objectFlow->project_fallback_employee_id;
        $hasSupportFallback = $objectFlow->support_fallback_role_id || $objectFlow->support_fallback_employee_id;
        $namedCategories = $fundPools->filter(fn ($r) => $r['pool']->category !== '')->count();
        $steps = [
            [(bool) ($objectFlow->enabled || $objectFlow->cash_advance_enabled), 'Switch on', 'Reimbursement: ' . ($objectFlow->enabled ? 'on' : 'off') . '. Cash advance and report: ' . ($objectFlow->cash_advance_enabled ? 'on' : 'off') . '. Off means the previous behaviour. Tick the switches below and save when you are ready.'],
            [$missingOffice->isEmpty() && $officeRows->isNotEmpty(), 'Every office has a responsible person', $missingOffice->isEmpty() ? 'All offices are set.' : 'Missing for: ' . $missingOffice->take(5)->implode(', ') . ($missingOffice->count() > 5 ? ' and ' . ($missingOffice->count() - 5) . ' more' : '') . '. Documents charged to these offices are refused until it is set.'],
            [(bool) ($hasProjectFallback && $hasSupportFallback), 'Fallback approvers', ($hasProjectFallback && $hasSupportFallback) ? 'Set for projects and support.' : 'Recommended: set a fallback role or person for projects and for support, so a project without an Admin, or a ticket without a Lead, does not block submissions.'],
            [$fundPools->isNotEmpty() || $fundedOffices > 0, 'Funds (optional)', 'Office funds active: ' . $fundedOffices . '. Project and ticket allocations: ' . $fundPools->count() . ($namedCategories ? ' (' . $namedCategories . ' with a category)' : '') . '. Anything without a fund is simply not checked.'],
        ];
    @endphp
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Setup checklist</h3>
        <ul class="space-y-2">
            @foreach($steps as [$done, $title, $detail])
                <li class="flex items-start gap-3 text-sm">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">{{ $done ? '✓' : '!' }}</span>
                    <span><span class="font-semibold text-gray-800">{{ $title }}.</span> <span class="text-gray-600">{{ $detail }}</span></span>
                </li>
            @endforeach
        </ul>

        <details class="mt-4 group">
            <summary class="cursor-pointer text-sm font-semibold text-red-800">How it works and how to use it</summary>
            <div class="mt-3 space-y-3 text-sm text-gray-700">
                <p><strong>What the requester does.</strong> On each item they choose an <em>Object</em>: an Office, a Project, or a Support ticket. They never choose approvers; the form shows who will review.</p>
                <p><strong>Who approves.</strong> Project: Project Admin, then Project Manager. Support: Ticket Lead (and the Support Manager if on). Office: the responsible person you set for that office. Then the steps in Approval Workflow (for example Accounting and Cashier).</p>
                <p><strong>What a fund is.</strong> A fund is the amount that may be reimbursed for one Object. It goes down only when a document is fully approved. If an amount is above the remaining fund, the policy decides: <em>Warn</em> asks the requester for a reason, <em>Block</em> refuses.</p>
                <p><strong>What a category is.</strong> A category is only a label you choose for an allocation, such as Transport or Accommodation. Requesters see a <em>Fund category</em> choice only for a project or ticket that has at least one named allocation. If you do not need to split a fund, leave the category empty: one general allocation covers everything.</p>
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Set a responsible person for every office, and fallback approvers.</li>
                    <li>Optional: switch on an office fund, or add allocations for a project or ticket.</li>
                    <li>Choose Warn or Block for each Object.</li>
                    <li>Tick "Use Objects and funds for new reimbursements" and save.</li>
                    <li>Test with one document per Object, approve it, and check the remaining fund in the allocation table.</li>
                </ol>
                <p class="text-xs text-gray-500">Switching this off later returns reimbursement to the previous behaviour. Documents already submitted keep their own flow, and no data is deleted.</p>
            </div>
        </details>
    </div>

    {{-- ── Flow and policy ──────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('general.reimbursement.settings.objects.flow') }}" class="space-y-6" data-unsaved-label="Object flow">
        @csrf

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $objectFlow->enabled)) class="{{ $check }}">
            <span>
                <span class="block text-sm font-semibold text-gray-800">Use Objects and funds for new reimbursements</span>
                <span class="block text-xs text-gray-500">When on, requesters choose an Object per item and approvers are assigned automatically. Documents already submitted keep their current flow.</span>
            </span>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="cash_advance_enabled" value="1" @checked(old('cash_advance_enabled', $objectFlow->cash_advance_enabled)) class="{{ $check }}">
            <span>
                <span class="block text-sm font-semibold text-gray-800">Use Objects and funds for new cash advances (and their reports)</span>
                <span class="block text-xs text-gray-500">A separate switch, so each module can be turned on when you are ready. A cash advance is charged to one Object. Its report follows the same Object and reviewers, and the fund settles to the amount actually spent.</span>
            </span>
        </label>

        <section>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Project approvals</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="project_admin_enabled" value="1" @checked(old('project_admin_enabled', $objectFlow->project_admin_enabled)) class="{{ $check }}">
                        <span class="text-sm text-gray-800">Project Admin approves</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="project_manager_enabled" value="1" @checked(old('project_manager_enabled', $objectFlow->project_manager_enabled)) class="{{ $check }}">
                        <span class="text-sm text-gray-800">Project Manager approves</span>
                    </label>
                </div>
                <div>
                    <label class="{{ $label }}">Order</label>
                    <select name="project_order" class="{{ $input }}">
                        <option value="admin_first" @selected(old('project_order', $objectFlow->project_order) === 'admin_first')>Project Admin, then Project Manager</option>
                        <option value="manager_first" @selected(old('project_order', $objectFlow->project_order) === 'manager_first')>Project Manager, then Project Admin</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Fallback role</label>
                    {!! $roleSelect('project_fallback_role_id', old('project_fallback_role_id', $objectFlow->project_fallback_role_id)) !!}
                    <p class="{{ $hint }}">Anyone holding this role may approve when a project has no Admin or Manager.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Fallback person</label>
                    {!! $personSelect('project_fallback_employee_id', old('project_fallback_employee_id', $objectFlow->project_fallback_employee_id)) !!}
                    <p class="{{ $hint }}">A named person takes priority over the role. Choose the role first, then the person.</p>
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Support approvals</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="support_lead_enabled" value="1" @checked(old('support_lead_enabled', $objectFlow->support_lead_enabled)) class="{{ $check }}">
                        <span class="text-sm text-gray-800">Ticket Lead approves</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="support_manager_enabled" value="1" @checked(old('support_manager_enabled', $objectFlow->support_manager_enabled)) class="{{ $check }}">
                        <span>
                            <span class="block text-sm text-gray-800">Support Manager approves after the Ticket Lead</span>
                            <span class="block text-xs text-gray-400">Skipped for tickets that are not part of a Support contract.</span>
                        </span>
                    </label>
                </div>
                <div></div>
                <div>
                    <label class="{{ $label }}">Fallback role</label>
                    {!! $roleSelect('support_fallback_role_id', old('support_fallback_role_id', $objectFlow->support_fallback_role_id)) !!}
                </div>
                <div>
                    <label class="{{ $label }}">Fallback person</label>
                    {!! $personSelect('support_fallback_employee_id', old('support_fallback_employee_id', $objectFlow->support_fallback_employee_id)) !!}
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-1">When an amount exceeds the remaining fund</h3>
            <p class="text-xs text-gray-500 mb-3"><strong>Warn</strong> accepts the document and asks the requester for a reason. <strong>Block</strong> refuses it. An Object without a fund is never checked.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach(['policy_project' => 'Project', 'policy_office' => 'Office', 'policy_support' => 'Support'] as $field => $title)
                    <div>
                        <label class="{{ $label }}">{{ $title }}</label>
                        <select name="{{ $field }}" class="{{ $input }}">
                            <option value="warn" @selected(old($field, $objectFlow->{$field}) === 'warn')>Warn</option>
                            <option value="block" @selected(old($field, $objectFlow->{$field}) === 'block')>Block</option>
                        </select>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all">Save flow and policy</button>
        </div>
    </form>

    {{-- ── Office funds ─────────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('general.reimbursement.settings.objects.offices') }}" class="border-t border-gray-100 pt-6" data-unsaved-label="Office funds">
        @csrf
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-1">Office funds and responsible person</h3>
        <p class="text-xs text-gray-500 mb-4">
            Every office needs a responsible person, who approves reimbursements charged to it. The monthly fund is optional: when it is off, the office is not checked against a fund.
        </p>

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full text-sm min-w-[760px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-3 py-3">Office</th>
                        <th class="px-3 py-3">Monthly fund</th>
                        <th class="px-3 py-3">Responsible role</th>
                        <th class="px-3 py-3">Responsible person</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($officeRows as $i => $office)
                        <tr class="align-top">
                            <td class="px-3 py-3 font-medium text-gray-900">
                                {{ $office['label'] }}
                                <input type="hidden" name="offices[{{ $i }}][branch_id]" value="{{ $office['branch_id'] }}">
                            </td>
                            <td class="px-3 py-3">
                                <label class="flex items-center gap-2 text-sm mb-2">
                                    <input type="checkbox" name="offices[{{ $i }}][fund_active]" value="1" @checked(old("offices.$i.fund_active", $office['fund_active'])) class="w-4 h-4 rounded border-gray-300 text-red-800 focus:ring-red-800">
                                    Fund active
                                </label>
                                <input type="number" min="0" step="1000" name="offices[{{ $i }}][monthly_amount]" placeholder="Amount per month (IDR)"
                                       value="{{ old("offices.$i.monthly_amount", $office['monthly_amount'] ?: '') }}" class="{{ $input }}">
                            </td>
                            <td class="px-3 py-3">{!! $roleSelect("offices[$i][approver_role_id]", old("offices.$i.approver_role_id", $office['approver_role_id'])) !!}</td>
                            <td class="px-3 py-3">{!! $personSelect("offices[$i][approver_employee_id]", old("offices.$i.approver_employee_id", $office['approver_employee_id'])) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-sm text-gray-400">No active office exists yet. Add a branch first in the Attendance branch list.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($officeRows->isNotEmpty())
            <div class="pt-4">
                <button type="submit" class="px-5 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all">Save offices</button>
            </div>
        @endif
    </form>

    {{-- ── Project and ticket allocations ───────────────────────────────── --}}
    <div class="border-t border-gray-100 pt-6">
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-1">Project and ticket fund allocations</h3>
        <p class="text-xs text-gray-500 mb-4">
            An allocation is the amount that may be reimbursed for a project or a ticket, per category. Funds decrease when a document is fully approved.
            A project or ticket without an allocation is not checked. These allocations are separate from the project cost plan and never change it.
        </p>

        <div class="overflow-x-auto border border-gray-200 rounded-lg mb-5">
            <table class="w-full text-sm min-w-[760px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-3 py-3">Object</th>
                        <th class="px-3 py-3">Category</th>
                        <th class="px-3 py-3 text-right">Used</th>
                        <th class="px-3 py-3 text-right">Remaining</th>
                        <th class="px-3 py-3">Allocation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fundPools as $row)
                        @php($pool = $row['pool'])
                        <tr class="align-top">
                            <td class="px-3 py-3">
                                <span class="font-medium text-gray-900">{{ $pool->ref_label ?: ucfirst($pool->object_type) . ' #' . $pool->ref_id }}</span>
                                <span class="block text-xs text-gray-400">{{ ucfirst($pool->object_type) }}</span>
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $pool->category !== '' ? $pool->category : 'General' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-700">{{ $rp($row['balance']['committed']) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold {{ $row['balance']['available'] < 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $rp($row['balance']['available']) }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('general.reimbursement.settings.objects.pools.update', $pool) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="number" name="amount" min="1" step="1000" required value="{{ (int) $pool->amount }}" class="w-36 px-2 py-1.5 border border-gray-300 rounded text-sm text-right">
                                    <label class="flex items-center gap-1 text-xs text-gray-600">
                                        <input type="checkbox" name="is_active" value="1" @checked($pool->is_active) class="w-4 h-4 rounded border-gray-300 text-red-800 focus:ring-red-800"> Active
                                    </label>
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-800 border border-red-200 rounded-lg hover:bg-red-50">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-gray-400">No allocation yet. Projects and tickets are not checked against a fund until one is added.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('general.reimbursement.settings.objects.pools.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end" id="poolForm">
            @csrf
            <div>
                <label class="{{ $label }}">Object</label>
                <select name="object_type" id="poolType" class="{{ $input }}">
                    <option value="project" @selected(old('object_type') !== 'support')>Project</option>
                    <option value="support" @selected(old('object_type') === 'support')>Support ticket</option>
                </select>
            </div>
            <div class="md:col-span-2" id="poolProjectWrap">
                <label class="{{ $label }}">Project</label>
                <select name="project_id" data-searchable="true" data-search-placeholder="Search project…" class="{{ $input }}">
                    <option value="">- choose a project -</option>
                    @foreach($projectOptions as $project)
                        <option value="{{ $project['id'] }}" @selected((string) old('project_id') === (string) $project['id'])>{{ $project['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2" id="poolTicketWrap" hidden>
                <label class="{{ $label }}">Ticket number</label>
                <input type="text" name="ticket_number" maxlength="50" value="{{ old('ticket_number') }}" placeholder="Full ticket number" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Category (optional)</label>
                <input type="text" name="category" maxlength="60" value="{{ old('category') }}" placeholder="e.g. Transport" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Allocation (IDR)</label>
                <input type="number" name="amount" min="1" step="1000" required value="{{ old('amount') }}" class="{{ $input }}">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all">Add allocation</button>
            </div>
        </form>
        <p class="{{ $hint }} mt-2">Leave the category empty for one general allocation that covers every category without its own allocation.</p>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const type = document.getElementById('poolType');
    const projectWrap = document.getElementById('poolProjectWrap');
    const ticketWrap = document.getElementById('poolTicketWrap');
    if (!type) return;

    function sync() {
        const isSupport = type.value === 'support';
        projectWrap.hidden = isSupport;
        ticketWrap.hidden = !isSupport;
    }

    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
