{{-- Form langkah persetujuan (tambah / ubah). Param: $action, $step (null = baru), $submit, $roles, $employees. --}}
@php
    $uid = 'step' . ($step?->id ?? 'new');
    $type = old('approver_type', $step?->approver_type ?? 'role');
    $picked = array_map('intval', old('approver_employee_ids', $step?->approver_employee_ids ?? []));
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';
    $label = 'block text-xs font-semibold text-gray-600 mb-1';
@endphp
<form method="POST" action="{{ $action }}" class="grid md:grid-cols-2 gap-3" id="{{ $uid }}">@csrf
    <div><label class="{{ $label }}">Step name</label><input name="name" value="{{ old('name', $step?->name) }}" maxlength="100" class="{{ $input }}" placeholder="e.g. Compliance Review" required></div>
    <div><label class="{{ $label }}">Order</label><input type="number" name="order_seq" min="1" max="20" value="{{ old('order_seq', $step?->order_seq) }}" class="{{ $input }}" placeholder="Next free number"></div>
    <div>
        <label class="{{ $label }}">Decided by</label>
        <select name="approver_type" class="{{ $input }}" data-step-type>
            <option value="role" @selected($type === 'role')>A position</option>
            <option value="employee" @selected($type === 'employee')>Named people</option>
        </select>
    </div>
    <div data-step-role class="{{ $type === 'role' ? '' : 'hidden' }}">
        <label class="{{ $label }}">Position</label>
        <select name="approver_role_id" data-searchable="true" data-search-placeholder="Search position…" class="{{ $input }}">
            <option value="">Choose a position…</option>
            @foreach($roles as $role)<option value="{{ $role->id }}" @selected((int) old('approver_role_id', $step?->approver_role_id) === (int) $role->id)>{{ $role->name }}</option>@endforeach
        </select>
    </div>
    <div data-step-people class="md:col-span-2 {{ $type === 'employee' ? '' : 'hidden' }}">
        <label class="{{ $label }}">People (hold Ctrl / Cmd to pick several)</label>
        <input type="search" data-people-filter placeholder="Search name or employee ID…" autocomplete="off" class="{{ $input }} mb-2">
        <select name="approver_employee_ids[]" multiple size="6" class="{{ $input }}">
            @foreach($employees as $e)<option value="{{ $e['id'] }}" @selected(in_array((int) $e['id'], $picked, true))>{{ $e['name'] }}</option>@endforeach
        </select>
    </div>
    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $step?->is_active ?? true)) class="rounded primary-text"> Active
    </label>
    <div class="md:col-span-2"><button class="px-3.5 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">{{ $submit }}</button></div>
</form>
<script>
(function () {
    var form = document.getElementById(@json($uid));
    if (!form) return;
    var type = form.querySelector('[data-step-type]'), role = form.querySelector('[data-step-role]'), people = form.querySelector('[data-step-people]');
    // Kotak cari untuk daftar pilihan ganda: opsi yang tidak cocok disembunyikan (pilihan yang sudah dicentang tetap tersimpan)
    var pf = form.querySelector('[data-people-filter]'), ps = form.querySelector('select[multiple]');
    if (pf && ps) pf.addEventListener('input', function () {
        var q = pf.value.trim().toLowerCase();
        Array.prototype.forEach.call(ps.options, function (o) { o.hidden = q !== '' && o.textContent.toLowerCase().indexOf(q) === -1; });
    });
    type.addEventListener('change', function () {
        role.classList.toggle('hidden', type.value !== 'role');
        people.classList.toggle('hidden', type.value !== 'employee');
    });
})();
</script>
