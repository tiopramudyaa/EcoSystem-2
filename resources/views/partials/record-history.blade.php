{{--
    "History" panel — who changed what on a record, from the audit trail.

    @include('partials.record-history', ['type' => 'employee', 'id' => $employee->id])
    Types: employee, project, ticket (see RecordHistoryController::TYPES). The panel loads once, when window.loadRecordHistory('<dom id>')
    is called — call it the first time the tab is shown, so a hidden tab costs no request.
--}}
@php $panelId = 'recordHistory-' . $type . '-' . $id; @endphp
<div id="{{ $panelId }}" data-url="{{ route('record-history', ['type' => $type, 'id' => $id]) }}" class="space-y-3">
    <div class="mb-2 pb-2 border-b border-gray-200">
        <h3 class="text-base font-semibold text-gray-900">History</h3>
        <p class="text-xs text-gray-500 mt-0.5">Changes made to this record, newest first. Pay, bank and ID details are not listed here.</p>
    </div>
    <div data-role="body" aria-live="polite"><p class="text-sm text-gray-400 py-6 text-center">Loading history…</p></div>
</div>

@once
<script>
window.loadRecordHistory = function (panelId) {
    var panel = document.getElementById(panelId);
    if (!panel || panel.dataset.loaded) return;
    panel.dataset.loaded = '1';
    var body = panel.querySelector('[data-role=body]');
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function empty(msg) { body.innerHTML = '<p class="text-sm text-gray-400 py-6 text-center">' + esc(msg) + '</p>'; }

    fetch(panel.dataset.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (d) {
            if (!d.data.length) return empty('No changes have been recorded for this record yet.');
            body.innerHTML = d.data.map(function (e) {
                var rows = e.changes.map(function (c) {
                    return '<tr><td class="py-1 pr-4 text-gray-500 align-top">' + esc(c.field) + '</td>' +
                        '<td class="py-1 pr-4 text-gray-400 line-through align-top break-all">' + (c.from == null ? '—' : esc(c.from)) + '</td>' +
                        '<td class="py-1 text-gray-900 align-top break-all">' + (c.to == null ? '—' : esc(c.to)) + '</td></tr>';
                }).join('');
                return '<div class="border border-gray-200 rounded-lg p-4">' +
                    '<div class="flex flex-wrap items-center gap-2 text-sm">' +
                    '<span class="px-2 py-0.5 rounded-full text-xs font-semibold ' + esc(e.color) + '">' + esc(e.event) + '</span>' +
                    '<span class="font-semibold text-gray-900">' + esc(e.section) + '</span>' +
                    '<span class="text-gray-500">by ' + esc(e.who) + '</span>' +
                    '<span class="ml-auto text-xs text-gray-400">' + esc(e.when) + '</span></div>' +
                    (rows ? '<table class="mt-2 text-xs w-full"><thead><tr class="text-left text-gray-400"><th class="font-medium pr-4">Field</th><th class="font-medium pr-4">Before</th><th class="font-medium">After</th></tr></thead><tbody>' + rows + '</tbody></table>' : '') +
                    '</div>';
            }).join('') + (d.truncated ? '<p class="text-xs text-gray-400 text-center">Showing the latest 100 changes.</p>' : '');
        })
        .catch(function (s) { panel.dataset.loaded = ''; empty(s === 403 ? 'You do not have access to this history.' : 'History could not be loaded.'); });
};
</script>
@endonce
