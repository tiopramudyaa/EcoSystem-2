{{-- Import Delivery Project dari Excel. Dibuka lewat openProjectImportModal().
     Dipakai di daftar Project dan form Create Project (izin Add New).
     Aturan per baris: App\Services\DeliveryProjectImporter. --}}
<div id="projectImportModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50" onclick="closeProjectImportModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="primary-gradient px-6 py-4 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-semibold text-white">Import Projects from Excel</h3>
                <button type="button" onclick="closeProjectImportModal()" class="text-white/80 hover:text-white transition-colors" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                {{-- Langkah 1: template --}}
                <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-sm text-blue-900">
                        <p>One row is one project. Fill the columns marked <span class="font-semibold">*</span>. Rows that are exactly the same as an earlier row, or whose Customer, IO Number and Project Name already exist, are reported and skipped.</p>
                        <a href="{{ route('projects.import.template') }}" class="inline-flex items-center gap-1.5 mt-2 font-semibold text-blue-700 hover:text-blue-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Template
                        </a>
                    </div>
                </div>

                {{-- Langkah 2: file --}}
                <div id="projImportPick">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">File <span class="text-red-500">*</span></label>
                    <div id="projImportDrop"
                         class="border-2 border-dashed border-gray-300 rounded-lg py-7 px-4 text-center cursor-pointer hover:border-red-300 hover:bg-red-50/30 transition-all duration-200"
                         onclick="document.getElementById('projImportFile').click()"
                         ondragover="event.preventDefault();this.classList.add('border-red-400','bg-red-50/40')"
                         ondragleave="this.classList.remove('border-red-400','bg-red-50/40')"
                         ondrop="projImportDrop(event)">
                        <svg class="w-9 h-9 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm font-medium text-gray-500" id="projImportLabel">Click or drag &amp; drop an .xlsx, .xls or .csv file (max 10 MB)</p>
                        <input type="file" id="projImportFile" class="hidden" accept=".xlsx,.xls,.csv"
                               onchange="projImportFileChosen(this.files[0])">
                    </div>
                </div>

                <div id="projImportProgress" class="hidden">
                    <div class="flex items-center gap-2 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-4 py-2.5">
                        <svg class="animate-spin w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span>Importing, please wait...</span>
                    </div>
                </div>

                {{-- Hasil --}}
                <div id="projImportResult" class="hidden space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" data-filter="imported" onclick="projImportFilter('imported')" class="proj-imp-chip text-left rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                            <p class="text-2xl font-bold text-green-700" id="projImpCountImported">0</p>
                            <p class="text-xs font-medium text-green-800">Imported</p>
                        </button>
                        <button type="button" data-filter="duplicate" onclick="projImportFilter('duplicate')" class="proj-imp-chip text-left rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-2xl font-bold text-amber-700" id="projImpCountDuplicate">0</p>
                            <p class="text-xs font-medium text-amber-800">Duplicates skipped</p>
                        </button>
                        <button type="button" data-filter="error" onclick="projImportFilter('error')" class="proj-imp-chip text-left rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                            <p class="text-2xl font-bold text-red-700" id="projImpCountError">0</p>
                            <p class="text-xs font-medium text-red-800">Rows with errors</p>
                        </button>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span id="projImpFilterLabel">All rows</span>
                        <button type="button" onclick="projImportFilter(null)" class="hover:text-gray-800">Show all</button>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="max-h-72 overflow-y-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                        <th class="px-3 py-2 w-16">Row</th>
                                        <th class="px-3 py-2 w-28">Result</th>
                                        <th class="px-3 py-2">Information</th>
                                    </tr>
                                </thead>
                                <tbody id="projImpRows" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 flex-shrink-0">
                <button type="button" onclick="closeProjectImportModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                    Close
                </button>
                <button type="button" id="projImportBtn" onclick="submitProjectImport()" disabled
                        class="inline-flex items-center gap-2 px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    let file = null;
    let importedCount = 0;
    const STATUS = {
        imported:  ['Imported', 'bg-green-100 text-green-800'],
        duplicate: ['Duplicate', 'bg-amber-100 text-amber-800'],
        error:     ['Error', 'bg-red-100 text-red-800'],
    };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = id => document.getElementById(id);
    const toast = (msg, type) => (typeof showNotification === 'function') ? showNotification(msg, type)
        : (typeof showToast === 'function' ? showToast(msg, type) : alert(msg));

    function reset() {
        file = null;
        $('projImportFile').value = '';
        $('projImportLabel').textContent = 'Click or drag & drop an .xlsx, .xls or .csv file (max 10 MB)';
        $('projImportBtn').disabled = true;
        $('projImportProgress').classList.add('hidden');
        $('projImportResult').classList.add('hidden');
        $('projImportPick').classList.remove('hidden');
    }

    window.openProjectImportModal = function () {
        reset();
        importedCount = 0;
        $('projectImportModal').classList.remove('hidden');
    };

    // Ada project baru → muat ulang daftar (atau pindah ke daftar dari form Create).
    window.closeProjectImportModal = function () {
        $('projectImportModal').classList.add('hidden');
        if (importedCount > 0) {
            window.location.href = @json(route('projects.index'));
        }
    };

    window.projImportFileChosen = function (f) {
        if (!f) return;
        if (!/\.(xlsx|xls|csv)$/i.test(f.name)) { toast('Use an .xlsx, .xls or .csv file.', 'error'); return; }
        if (f.size > 10 * 1024 * 1024) { toast('The file may not be larger than 10 MB.', 'error'); return; }
        file = f;
        $('projImportLabel').textContent = f.name;
        $('projImportBtn').disabled = false;
        $('projImportResult').classList.add('hidden');
    };

    window.projImportDrop = function (e) {
        e.preventDefault();
        $('projImportDrop').classList.remove('border-red-400', 'bg-red-50/40');
        projImportFileChosen(e.dataTransfer.files[0]);
    };

    window.projImportFilter = function (status) {
        document.querySelectorAll('#projImpRows tr').forEach(tr => {
            tr.classList.toggle('hidden', !!status && tr.dataset.status !== status);
        });
        document.querySelectorAll('.proj-imp-chip').forEach(c => {
            c.classList.toggle('ring-2', c.dataset.filter === status);
            c.classList.toggle('ring-offset-1', c.dataset.filter === status);
        });
        $('projImpFilterLabel').textContent = status ? (STATUS[status][0] + ' rows') : 'All rows';
    };

    window.submitProjectImport = async function () {
        if (!file) return;
        const btn = $('projImportBtn');
        btn.disabled = true;
        $('projImportProgress').classList.remove('hidden');

        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', @json(csrf_token()));

        try {
            const res  = await fetch(@json(route('projects.import')), { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                const msg = data.errors && !Array.isArray(data.errors) && typeof data.errors === 'object'
                    ? Object.values(data.errors).flat()[0]
                    : (data.message || 'The import failed.');
                throw new Error(msg);
            }

            importedCount += data.imported;
            $('projImpCountImported').textContent  = data.imported;
            $('projImpCountDuplicate').textContent = data.duplicates;
            $('projImpCountError').textContent     = data.errors;
            $('projImpRows').innerHTML = (data.rows || []).map(r => {
                const [label, cls] = STATUS[r.status] || [r.status, 'bg-gray-100 text-gray-700'];
                return `<tr data-status="${esc(r.status)}">
                    <td class="px-3 py-2 text-gray-500">${esc(r.row)}</td>
                    <td class="px-3 py-2"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold ${cls}">${esc(label)}</span></td>
                    <td class="px-3 py-2 text-gray-700">${esc(r.message)}</td>
                </tr>`;
            }).join('') || '<tr><td colspan="3" class="px-3 py-4 text-center text-gray-400">No data rows found.</td></tr>';
            projImportFilter(null);
            $('projImportResult').classList.remove('hidden');
            $('projImportPick').classList.add('hidden');
            toast(data.message, data.errors > 0 ? 'warning' : 'success');
        } catch (err) {
            toast(err.message || 'The import failed.', 'error');
            btn.disabled = false;
        } finally {
            $('projImportProgress').classList.add('hidden');
        }
    };

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !$('projectImportModal').classList.contains('hidden')) closeProjectImportModal();
    });
})();
</script>
