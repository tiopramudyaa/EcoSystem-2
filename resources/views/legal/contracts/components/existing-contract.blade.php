{{--
    "Add existing contract" / "Edit details": a contract signed on paper, recorded with its scanned PDF. The scan IS the document;
    there is no template, no generated text, no salary here and no electronic signing. Needs the "Import Existing Contract" permission
    plus the Create (Edit) box of Contracts. Rules live in ExistingContractImporter; the page only collects the values.
--}}
@php
    $exInput = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200';
    $exLabel = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
    $exReopen = session('reopen_existing');
@endphp
<dialog id="existingDialog" class="rounded-xl p-0 w-full max-w-3xl backdrop:bg-black/40"
        data-store-url="{{ route('general.contracts.existing.store', $employee->employee_id) }}"
        data-max-mb="{{ round(\App\Services\Contracts\ExistingContractImporter::maxKb() / 1024) }}">
    <form method="POST" enctype="multipart/form-data" id="existingForm" class="flex flex-col max-h-[88vh]" data-unsaved-ignore>
        @csrf
        <div class="px-6 pt-5 pb-3 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800" id="existingTitle">Add existing contract</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Record a contract that was signed on paper for <strong>{{ $employee->name }}</strong> ({{ $employee->eci }}). The scanned PDF becomes the contract document.</p>
            </div>
            <button type="button" data-ex-close class="text-gray-400 hover:text-gray-700" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="px-6 py-4 overflow-y-auto space-y-4">
            <div class="text-[11px] bg-sky-50 border border-sky-200 text-sky-900 rounded-lg px-3 py-2 space-y-0.5">
                <p><i class="fas fa-circle-info mr-1"></i> Use the number and dates printed on the paper. Nothing here changes the employee’s salary or payroll, and the contract does not enter electronic signing.</p>
                <p>Only <strong>Active</strong> (still running) or <strong>Terminated</strong> (already ended). An employee can have one running contract at a time.</p>
            </div>
            <p id="exError" class="hidden text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2" role="alert"></p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="{{ $exLabel }}" for="exNumber">Contract number (as printed)</label><input id="exNumber" name="contract_number" maxlength="100" required class="{{ $exInput }}"></div>
                <div><label class="{{ $exLabel }}" for="exType">Contract type</label>
                    <select id="exType" name="contract_type" required class="{{ $exInput }}" data-no-enhance>
                        @foreach($types as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                    </select></div>
                <div><label class="{{ $exLabel }}" for="exStart">Start date</label><input type="date" id="exStart" name="start_date" required class="{{ $exInput }}"></div>
                <div><label class="{{ $exLabel }}" for="exEnd">End date</label><input type="date" id="exEnd" name="end_date" class="{{ $exInput }}"><p class="text-[10px] text-gray-400 mt-1">Required for PKWT and Internship; empty for PKWTT.</p></div>
                <div><label class="{{ $exLabel }}" for="exSigned">Signed date</label><input type="date" id="exSigned" name="signed_date" class="{{ $exInput }}"></div>
                <div><label class="{{ $exLabel }}" for="exStatus">Status</label>
                    <select id="exStatus" name="status" required class="{{ $exInput }}" data-no-enhance>
                        <option value="active">Active — still running</option>
                        <option value="terminated">Terminated — already ended</option>
                    </select></div>
                <div><label class="{{ $exLabel }}" for="exPos">Position</label><input id="exPos" name="position" list="exPosList" maxlength="255" class="{{ $exInput }}" placeholder="As on the contract"></div>
                <div><label class="{{ $exLabel }}" for="exPosType">Position type</label>
                    <select id="exPosType" name="position_type" class="{{ $exInput }}" data-no-enhance><option value="">Not specified</option>
                        @foreach($positionTypes as $pt)<option value="{{ $pt }}">{{ $pt }}</option>@endforeach</select></div>
                <div><label class="{{ $exLabel }}" for="exDept">Department</label><input id="exDept" name="department" list="exDeptList" maxlength="255" class="{{ $exInput }}"></div>
                <div><label class="{{ $exLabel }}" for="exLoc">Work location</label><input id="exLoc" name="work_location" maxlength="255" class="{{ $exInput }}"></div>
                <div class="md:col-span-2"><label class="{{ $exLabel }}" for="exNotes">Notes</label><textarea id="exNotes" name="notes" rows="2" maxlength="2000" class="{{ $exInput }}"></textarea></div>
                <div class="md:col-span-2">
                    <label class="{{ $exLabel }}" for="exScan">Scanned contract (PDF)</label>
                    <input type="file" id="exScan" name="scan" accept="application/pdf,.pdf" class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2">
                    <p class="text-[10px] text-gray-400 mt-1" id="exScanHelp">PDF only, up to {{ round(\App\Services\Contracts\ExistingContractImporter::maxKb() / 1024) }} MB. Kept privately; only holders of “View Salary” can open it.</p>
                </div>
            </div>
            <datalist id="exPosList">@foreach($positions as $p)<option value="{{ $p }}">@endforeach</datalist>
            <datalist id="exDeptList">@foreach($departments as $d)<option value="{{ $d }}">@endforeach</datalist>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" data-ex-close class="px-4 py-2 bg-white text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancel</button>
            <button type="submit" id="exSubmit" class="px-5 py-2 text-xs font-semibold text-white primary-gradient rounded-lg hover:opacity-90">Save contract</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(function () {
    var dlg = document.getElementById('existingDialog');
    if (!dlg) { return; }
    var $ = function (id) { return document.getElementById(id); };
    var form = $('existingForm'), err = $('exError'), editing = false;
    var map = { exNumber: 'number', exType: 'type', exStart: 'start', exEnd: 'end', exSigned: 'signed', exStatus: 'status', exPos: 'position', exPosType: 'positionType', exDept: 'department', exLoc: 'location', exNotes: 'notes' };

    function fail(msg) { err.textContent = msg || ''; err.classList.toggle('hidden', !msg); }
    function fill(c) {
        form.reset(); fail('');
        Object.keys(map).forEach(function (id) { var v = c ? c[map[id]] : null; if (v !== null && v !== undefined) { $(id).value = v; } });
        if (!c) { $('exStatus').value = 'active'; }
    }
    function openAdd() {
        editing = false; fill(null);
        form.action = dlg.dataset.storeUrl; $('existingTitle').textContent = 'Add existing contract';
        $('exScanHelp').textContent = 'PDF only, up to ' + dlg.dataset.maxMb + ' MB. Kept privately; only holders of “View Salary” can open it.';
        $('exScan').required = true; $('exSubmit').textContent = 'Save contract'; dlg.showModal();
    }
    function openEdit(c) {
        editing = true; fill(c);
        form.action = c.update; $('existingTitle').textContent = 'Edit details · ' + c.number;
        $('exScan').required = false; $('exSubmit').textContent = 'Save changes';
        $('exScanHelp').textContent = 'Current scan: ' + (c.scan || 'none') + '. Choose a PDF only to replace it (up to ' + dlg.dataset.maxMb + ' MB).';
        dlg.showModal();
    }

    var openBtn = $('existingOpen');
    if (openBtn) { openBtn.addEventListener('click', openAdd); }
    document.querySelectorAll('[data-existing-edit]').forEach(function (b) {
        b.addEventListener('click', function () { openEdit(JSON.parse(b.dataset.existingEdit)); });
    });
    dlg.querySelectorAll('[data-ex-close]').forEach(function (b) { b.addEventListener('click', function () { dlg.close(); }); });

    // Pemeriksaan ringan sebelum kirim; aturan sebenarnya dijaga server.
    form.addEventListener('submit', function (e) {
        fail('');
        var f = $('exScan').files[0], max = parseInt(dlg.dataset.maxMb, 10) * 1024 * 1024;
        if (f && f.size > max) { e.preventDefault(); fail('The PDF is larger than ' + dlg.dataset.maxMb + ' MB. Scan at a lower resolution or compress it.'); return; }
        if (f && !/\.pdf$/i.test(f.name)) { e.preventDefault(); fail('The scan must be a PDF file.'); return; }
        $('exSubmit').disabled = true; $('exSubmit').textContent = 'Saving...';
    });

    @if($exReopen)
        // A rule refused the save: open the dialog again with what was typed (the file must be chosen again).
        (function () {
            var old = @json(old());
            var mode = @json($exReopen['mode'] ?? 'add');
            if (mode === 'edit') {
                var wanted = {{ (int) ($exReopen['id'] ?? 0) }}, found = null;
                document.querySelectorAll('[data-existing-edit]').forEach(function (b) { var c = JSON.parse(b.dataset.existingEdit); if (c.id === wanted) { found = c; } });
                if (found) { openEdit(found); } else { openAdd(); }
            } else { openAdd(); }
            Object.keys(map).forEach(function (id) { var k = ({ exNumber: 'contract_number', exType: 'contract_type', exStart: 'start_date', exEnd: 'end_date', exSigned: 'signed_date', exStatus: 'status', exPos: 'position', exPosType: 'position_type', exDept: 'department', exLoc: 'work_location', exNotes: 'notes' })[id]; if (old[k] !== undefined && old[k] !== null) { $(id).value = old[k]; } });
            @if($errors->has('contract'))
            fail(@json($errors->first('contract')));
            @endif
        })();
    @endif
})();
</script>
@endpush
