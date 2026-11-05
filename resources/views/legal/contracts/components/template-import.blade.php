{{--
    Impor template kontrak dari berkas JSON (hasil "Export templates"): pilih berkas → tinjau tiap template (baru / sudah ada / sama /
    bermasalah) dan pilih tindakannya → simpan → laporan. Tidak ada yang disimpan sebelum langkah simpan.
--}}
<dialog id="tplImportDialog" class="rounded-xl p-0 w-full max-w-4xl backdrop:bg-black/40"
        data-preview-url="{{ route('general.contracts.templates.import.preview') }}"
        data-apply-url="{{ route('general.contracts.templates.import.apply') }}">
    <div class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Import contract templates</h3>
                <p class="text-xs text-gray-500 mt-0.5">Bring templates from @if(($importHtml ?? true) && ($importJson ?? true))a <strong>backup file</strong> or an <strong>HTML file</strong>@elseif($importHtml ?? true)an <strong>HTML file</strong>@else a <strong>backup file</strong>@endif made by <strong>Export templates</strong> (from this or another system). Nothing is saved until you confirm.</p>
            </div>
            <button type="button" data-ti-close class="text-gray-400 hover:text-gray-700" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>

        <p id="tiError" class="hidden text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2" role="alert"></p>

        {{-- Step 1 --}}
        <div data-ti-step="file" class="space-y-3">
            <div class="text-xs bg-sky-50 border border-sky-200 text-sky-900 rounded-lg px-4 py-3 space-y-1">
                <p>The file holds the template <strong>text</strong> (with its placeholders), paper settings and the default signer — no employee data.</p>
                <p>Each template is checked the same way as when you save it in the editor. A template with the same name and contract type can be <strong>updated</strong> or added as a <strong>copy</strong>.</p>
            </div>
            <input type="file" id="tiFile" accept="{{ implode(',', array_filter([($importJson ?? true) ? '.json,application/json' : null, ($importHtml ?? true) ? '.html,.htm,text/html' : null])) }}" class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2">
            <p class="text-xs text-gray-400">
                @if($importJson ?? true)Backup file (.json): one or many templates. @endif
                @if($importHtml ?? true)HTML file: one template, from Export &rsaquo; HTML. @endif
                Up to 2 MB. A PDF is for reading only and cannot be imported.</p>
            <div class="flex justify-end gap-2">
                <button type="button" data-ti-close class="px-4 py-2 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancel</button>
                <button type="button" id="tiCheck" class="px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90">Check file</button>
            </div>
        </div>

        {{-- Step 2 --}}
        <div data-ti-step="review" class="hidden space-y-3">
            <p id="tiSummary" class="text-sm text-gray-700"></p>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <tr><th class="px-3 py-2">Template in the file</th><th class="px-3 py-2">In this system</th><th class="px-3 py-2">Notes</th><th class="px-3 py-2 w-48">What to do</th></tr>
                    </thead>
                    <tbody id="tiRows" class="divide-y divide-gray-100 text-gray-700"></tbody>
                </table>
            </div>
            <div class="flex justify-between gap-2">
                <button type="button" id="tiBack" class="px-4 py-2 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Choose another file</button>
                <button type="button" id="tiApply" class="px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed">Import</button>
            </div>
        </div>

        {{-- Step 3 --}}
        <div data-ti-step="done" class="hidden space-y-3">
            <p id="tiDoneSummary" class="text-sm font-semibold text-gray-800"></p>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500"><tr><th class="px-3 py-2">Template</th><th class="px-3 py-2 w-28">Result</th><th class="px-3 py-2">Details</th></tr></thead>
                    <tbody id="tiDoneRows" class="divide-y divide-gray-100 text-gray-700"></tbody>
                </table>
            </div>
            <div class="flex justify-end"><button type="button" id="tiDone" class="px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90">Close and refresh the list</button></div>
        </div>
    </div>
</dialog>

@push('scripts')
<script>
(function () {
    var dlg = document.getElementById('tplImportDialog');
    var open = document.getElementById('tplImportOpen');
    if (!dlg || !open) { return; }
    var $ = function (id) { return document.getElementById(id); };
    var steps = dlg.querySelectorAll('[data-ti-step]'), errBox = $('tiError'), file = $('tiFile');
    var items = [], canEdit = false;
    var LABEL = { create: 'Add as new', update: 'Update the existing one', copy: 'Add as a copy', skip: 'Skip' };

    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (text !== undefined) { e.textContent = text; } return e; }
    function show(name) { steps.forEach(function (s) { s.classList.toggle('hidden', s.dataset.tiStep !== name); }); }
    function fail(msg) { errBox.textContent = msg || ''; errBox.classList.toggle('hidden', !msg); }
    function token() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }
    function post(url, fd) {
        return fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token() }, body: fd }).then(function (r) {
            return r.json().catch(function () { return { success: false, message: 'The server sent an unexpected answer (HTTP ' + r.status + '). Reload the page and try again.' }; }).then(function (j) {
                if (!j.success && !j.message) { j.message = (j.errors && j.errors.file && j.errors.file[0]) || j.message || 'Request failed (HTTP ' + r.status + ').'; }
                return j;
            });
        });
    }
    function reset() { items = []; file.value = ''; fail(''); show('file'); }

    open.addEventListener('click', function () { reset(); dlg.showModal(); });
    dlg.querySelectorAll('[data-ti-close]').forEach(function (b) { b.addEventListener('click', function () { dlg.close(); }); });
    $('tiBack').addEventListener('click', reset);
    $('tiDone').addEventListener('click', function () { window.location.reload(); });

    $('tiCheck').addEventListener('click', function () {
        fail('');
        if (!file.files.length) { fail('Choose a file first.'); return; }
        var btn = this; btn.disabled = true; btn.textContent = 'Checking...';
        var fd = new FormData(); fd.append('file', file.files[0]);
        post(dlg.dataset.previewUrl, fd).then(function (j) {
            if (!j.success) { fail(j.message); return; }
            items = j.items; canEdit = !!j.canEdit; renderReview(); show('review');
        }).catch(function () { fail('Could not reach the server. Check your connection and try again.'); })
          .finally(function () { btn.disabled = false; btn.textContent = 'Check file'; });
    });

    function renderReview() {
        var rows = $('tiRows'); rows.textContent = '';
        items.forEach(function (it) {
            var tr = el('tr', 'align-top');
            var c1 = el('td', 'px-3 py-2');
            c1.appendChild(el('span', 'font-semibold text-gray-800', it.name));
            if (it.system) { c1.appendChild(el('span', 'ml-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[9px] font-bold', 'System Default')); }
            c1.appendChild(el('span', 'block text-[11px] text-gray-400', it.type_label + ' · ' + it.applies_to + ' · ' + it.chars + ' characters' + (it.status === 'inactive' ? ' · Inactive' : '')));
            var c2 = el('td', 'px-3 py-2');
            if (it.errors.length) { c2.appendChild(el('span', 'text-gray-400', '-')); }
            else if (it.match) { c2.appendChild(el('span', it.identical ? 'text-gray-500' : 'text-amber-700 font-semibold', it.identical ? 'Already here, identical' : 'Already here: “' + it.match.name + '”')); }
            else { c2.appendChild(el('span', 'text-green-700 font-semibold', 'New')); }
            var c3 = el('td', 'px-3 py-2 space-y-1');
            it.errors.forEach(function (m) { c3.appendChild(el('p', 'text-red-600', m)); });
            it.warnings.forEach(function (m) { c3.appendChild(el('p', 'text-amber-700', m)); });
            if (!it.errors.length && !it.warnings.length) { c3.appendChild(el('span', 'text-gray-300', '-')); }
            var c4 = el('td', 'px-3 py-2'), sel = el('select', 'w-full border border-gray-300 rounded-md px-2 py-1.5 text-xs bg-white');
            sel.dataset.index = it.index;
            // Dropdown kustom aplikasi dipasang di luar <dialog> (yang tampil di lapisan paling atas), jadi daftarnya tertutup: pakai pilihan bawaan peramban.
            sel.setAttribute('data-no-enhance', '');
            it.options.forEach(function (o) {
                var op = el('option', '', LABEL[o]); op.value = o;
                if (o === 'update' && !canEdit) { op.disabled = true; op.textContent = LABEL[o] + ' (needs Edit permission)'; }
                sel.appendChild(op);
            });
            sel.value = (it.default === 'update' && !canEdit) ? 'skip' : it.default;
            sel.disabled = it.errors.length > 0;
            sel.addEventListener('change', refreshApply);
            c4.appendChild(sel);
            [c1, c2, c3, c4].forEach(function (c) { tr.appendChild(c); });
            rows.appendChild(tr);
        });
        var bad = items.filter(function (i) { return i.errors.length; }).length;
        $('tiSummary').textContent = items.length + ' template' + (items.length === 1 ? '' : 's') + ' in the file' + (bad ? ', ' + bad + ' with a problem that must be fixed in the file (they are skipped).' : '.') + ' Choose what to do with each one, then Import.';
        refreshApply();
    }
    function choices() {
        var out = {};
        $('tiRows').querySelectorAll('select').forEach(function (s) { out[s.dataset.index] = s.value; });
        return out;
    }
    function refreshApply() {
        var n = Object.values(choices()).filter(function (v) { return v !== 'skip'; }).length;
        var b = $('tiApply'); b.disabled = n === 0; b.textContent = n ? 'Import ' + n + ' template' + (n === 1 ? '' : 's') : 'Nothing to import';
    }

    $('tiApply').addEventListener('click', function () {
        fail('');
        var btn = this; btn.disabled = true; btn.textContent = 'Importing...';
        var fd = new FormData(); fd.append('file', file.files[0]); fd.append('decisions', JSON.stringify(choices()));
        post(dlg.dataset.applyUrl, fd).then(function (j) {
            if (!j.success) { fail(j.message); refreshApply(); return; }
            var r = j.report, rows = $('tiDoneRows'); rows.textContent = '';
            $('tiDoneSummary').textContent = r.created + ' added, ' + r.updated + ' updated, ' + r.skipped + ' skipped' + (r.failed ? ', ' + r.failed + ' failed' : '') + '.';
            r.rows.forEach(function (x) {
                var tr = el('tr', 'align-top');
                tr.appendChild(el('td', 'px-3 py-2 font-semibold text-gray-800', x.name));
                tr.appendChild(el('td', 'px-3 py-2 font-semibold ' + (x.result === 'failed' ? 'text-red-600' : (x.result === 'skipped' ? 'text-gray-400' : 'text-green-700')), x.result.charAt(0).toUpperCase() + x.result.slice(1)));
                tr.appendChild(el('td', 'px-3 py-2 ' + (x.result === 'failed' ? 'text-red-600' : 'text-gray-500'), x.message));
                rows.appendChild(tr);
            });
            show('done');
        }).catch(function () { fail('Could not reach the server. Nothing may have been saved: reload the page and check the list.'); refreshApply(); });
    });
})();
</script>
@endpush
