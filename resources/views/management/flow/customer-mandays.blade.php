@extends('dashboard')

@section('title', 'Customer Mandays Approval')
@section('page-title', 'Customer Mandays Approval')

@section('content')
@php
    $oldSteps = old('steps');
    $rowsData = $steps->values()->map(function ($s, $i) use ($oldSteps) {
        $o = $oldSteps[$i] ?? null;
        return [
            'id'           => (int) ($o['id'] ?? $s->id),
            'name'         => $o['name'] ?? $s->name,
            'role_id'      => isset($o) ? ($o['role_id'] ?? '') : ($s->approver_role_id ?? ''),
            'employee_ids' => array_map('intval', isset($o) ? ($o['employee_ids'] ?? []) : ($s->approver_employee_ids ?? [])),
        ];
    })->all();
@endphp

<div class="bg-white rounded-xl p-6 shadow-sm">
    <div class="mb-6 pb-4 border-b-2 border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900">Approval Workflow</h2>
        <p class="text-sm text-gray-500 mt-1">
            Customer Mandays proposals go through these levels top to bottom before Helpdesk sends them to the customer.
            One person per level is enough. Changes are applied together when you press <strong>Save Workflow</strong>.
        </p>
    </div>

    @if($steps->count() !== \App\Models\CustomerMandaysApprovalStep::LEVELS)
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            The approval levels are missing. Run the database migrations, then reload this page.
        </div>
    @else
    <form id="cmFlowForm" method="POST" action="{{ route('management.flow.customer-mandays.sync') }}">
        @csrf

        <div class="hidden md:grid grid-cols-12 gap-4 px-4 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
            <div class="col-span-1">Order</div>
            <div class="col-span-3">Step Name</div>
            <div class="col-span-3">Position</div>
            <div class="col-span-5">Name</div>
        </div>

        <div id="cmFlowRows" class="space-y-3"></div>

        <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div class="text-xs text-gray-500 space-y-1">
                <p>Levels run top to bottom; one person per level is enough. Empty names means everyone holding the position can approve.</p>
                <p>Choosing names limits the level to those people. The position then only helps you find them.</p>
                @if($openCount > 0)
                <p class="text-amber-700"><i class="fas fa-info-circle mr-1"></i>{{ $openCount }} proposal(s) are waiting for approval now. They keep the approvers they were submitted with.</p>
                @endif
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all whitespace-nowrap">
                <i class="fas fa-save mr-2"></i> Save Workflow
            </button>
        </div>
    </form>
    @endif
</div>

<style>
    .cmf-row { border: 1px solid rgba(107,114,128,.25); border-radius: .75rem; }
    .cmf-badge { width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; color: #fff; }
    .cmf-arrow { color: #9ca3af; line-height: 1; }
    .cmf-arrow:hover:not(:disabled) { color: #4b5563; }
    .cmf-arrow:disabled { opacity: .25; cursor: default; }
    .cmf-picker-panel { max-height: 16rem; overflow-y: auto; }
    .cmf-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .5rem; border-radius: 9999px; font-size: .75rem; background: rgba(107,114,128,.12); }
</style>

<script>
(function () {
    const ROLES     = @json($roles->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name])->values());
    const EMPLOYEES = @json($employees);
    const ROLE_COUNTS = @json($roleMemberCounts);
    let rows = @json($rowsData);

    const container = document.getElementById('cmFlowRows');
    if (!container) return;

    const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const empById = Object.fromEntries(EMPLOYEES.map(e => [e.id, e]));

    function hintFor(row) {
        if (row.employee_ids.length) {
            const roleNames = [...new Set(row.employee_ids.flatMap(id => empById[id]?.roles || []))];
            return roleNames.length ? 'Names hold: ' + roleNames.join(', ') : '';
        }
        if (row.role_id) {
            const n = ROLE_COUNTS[row.role_id] || 0;
            return n === 1 ? '1 active person holds this position' : `${n} active people hold this position`;
        }
        return 'Choose a position, or at least one name.';
    }

    function render() {
        container.innerHTML = rows.map((row, i) => {
            const roleOptions = ['<option value="">Any position (by name)</option>']
                .concat(ROLES.map(r => `<option value="${r.id}" ${String(r.id) === String(row.role_id) ? 'selected' : ''}>${esc(r.name)}</option>`))
                .join('');
            const chips = row.employee_ids.length
                ? row.employee_ids.map(id => `<span class="cmf-chip">${esc(empById[id]?.name || ('#' + id))}
                        <button type="button" class="text-gray-500 hover:text-red-700" onclick="cmfRemoveName(${i}, ${id})" aria-label="Remove">&times;</button></span>`).join(' ')
                : '<span class="text-gray-400 text-sm">Everyone holding the position</span>';
            const hidden = row.employee_ids.map(id => `<input type="hidden" name="steps[${i}][employee_ids][]" value="${id}">`).join('');

            return `
            <div class="cmf-row p-4">
                <input type="hidden" name="steps[${i}][id]" value="${row.id}">
                ${hidden}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                    <div class="md:col-span-1 flex items-center gap-2">
                        <span class="cmf-badge primary-gradient">${i + 1}</span>
                        <div class="flex flex-col">
                            <button type="button" class="cmf-arrow" onclick="cmfMove(${i}, -1)" ${i === 0 ? 'disabled' : ''} aria-label="Move up"><i class="fas fa-caret-up"></i></button>
                            <button type="button" class="cmf-arrow" onclick="cmfMove(${i}, 1)" ${i === rows.length - 1 ? 'disabled' : ''} aria-label="Move down"><i class="fas fa-caret-down"></i></button>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="md:hidden block text-[11px] font-semibold text-gray-500 uppercase mb-1">Step Name</label>
                        <input type="text" name="steps[${i}][name]" value="${esc(row.name)}" maxlength="100" required
                            oninput="cmfSetName(${i}, this.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-800">
                    </div>
                    <div class="md:col-span-3">
                        <label class="md:hidden block text-[11px] font-semibold text-gray-500 uppercase mb-1">Position</label>
                        <select name="steps[${i}][role_id]" onchange="cmfSetRole(${i}, this.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-800">${roleOptions}</select>
                        <p class="text-[11px] text-gray-500 mt-1">${esc(hintFor(row))}</p>
                    </div>
                    <div class="md:col-span-5 relative">
                        <label class="md:hidden block text-[11px] font-semibold text-gray-500 uppercase mb-1">Name</label>
                        <div class="w-full min-h-[2.5rem] px-3 py-2 border border-gray-300 rounded-lg flex flex-wrap gap-1 items-center cursor-pointer"
                             onclick="cmfTogglePicker(${i}, event)">
                            ${chips}
                            <i class="fas fa-chevron-down text-xs text-gray-400 ml-auto"></i>
                        </div>
                        <div id="cmfPicker${i}" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                            <div class="p-2 border-b border-gray-100">
                                <input type="text" placeholder="Search name..." oninput="cmfFilter(${i}, this.value)"
                                    class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-red-800">
                            </div>
                            <div class="cmf-picker-panel p-1" id="cmfPickerList${i}"></div>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function pickerList(i, q = '') {
        const row = rows[i];
        const query = q.trim().toLowerCase();
        // Bila posisi dipilih, tampilkan pemegang posisi itu lebih dulu.
        const roleName = ROLES.find(r => String(r.id) === String(row.role_id))?.name;
        const list = EMPLOYEES
            .filter(e => !query || e.name.toLowerCase().includes(query))
            .sort((a, b) => (roleName ? (b.roles.includes(roleName) - a.roles.includes(roleName)) : 0));
        const el = document.getElementById('cmfPickerList' + i);
        el.innerHTML = list.length ? list.slice(0, 200).map(e => `
            <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                <input type="checkbox" ${row.employee_ids.includes(e.id) ? 'checked' : ''} onchange="cmfToggleName(${i}, ${e.id}, this.checked)">
                <span class="flex-1">${esc(e.name)}</span>
                <span class="text-[10px] text-gray-400 truncate max-w-[45%]">${esc(e.roles.join(', '))}</span>
            </label>`).join('') : '<p class="px-2 py-3 text-xs text-gray-400">No names found.</p>';
    }

    let openPicker = null;
    window.cmfTogglePicker = (i, ev) => {
        if (ev.target.closest('button')) return;
        const el = document.getElementById('cmfPicker' + i);
        const willOpen = el.classList.contains('hidden');
        document.querySelectorAll('[id^="cmfPicker"]:not([id^="cmfPickerList"])').forEach(p => p.classList.add('hidden'));
        if (willOpen) { el.classList.remove('hidden'); pickerList(i); openPicker = i; el.querySelector('input')?.focus(); }
        else openPicker = null;
    };
    window.cmfFilter = (i, q) => pickerList(i, q);
    window.cmfToggleName = (i, id, on) => {
        const set = new Set(rows[i].employee_ids);
        on ? set.add(id) : set.delete(id);
        rows[i].employee_ids = [...set];
        const keep = openPicker, q = document.querySelector(`#cmfPicker${i} input`)?.value || '';
        render();
        if (keep === i) { document.getElementById('cmfPicker' + i).classList.remove('hidden'); const inp = document.querySelector(`#cmfPicker${i} input`); inp.value = q; pickerList(i, q); }
    };
    window.cmfRemoveName = (i, id) => { rows[i].employee_ids = rows[i].employee_ids.filter(x => x !== id); render(); };
    window.cmfSetRole = (i, v) => { rows[i].role_id = v; render(); };
    window.cmfSetName = (i, v) => { rows[i].name = v; };
    window.cmfMove = (i, d) => {
        const j = i + d;
        if (j < 0 || j >= rows.length) return;
        [rows[i], rows[j]] = [rows[j], rows[i]];
        render();
    };

    document.addEventListener('click', (e) => {
        if (openPicker === null) return;
        if (!e.target.closest('#cmFlowRows .relative')) {
            document.getElementById('cmfPicker' + openPicker)?.classList.add('hidden');
            openPicker = null;
        }
    });

    render();
})();
</script>
@endsection
