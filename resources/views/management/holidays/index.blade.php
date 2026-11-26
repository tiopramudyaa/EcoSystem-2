@extends('dashboard')

@section('title', 'Holidays')
@section('page-title', 'Holidays')

@section('content')
<style>
    .hd-switch { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
    .hd-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
    .hd-switch .hd-track { position: absolute; inset: 0; background: #d1d5db; border-radius: 999px; transition: background .15s; }
    .hd-switch .hd-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .15s; }
    .hd-switch input:checked + .hd-track { background: #991b1b; }
    .hd-switch input:checked + .hd-track::after { transform: translateX(16px); }
    .hd-switch input:focus-visible + .hd-track { box-shadow: 0 0 0 3px rgba(153,27,27,.25); }
    .hd-chip { font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 999px; border: 1px solid #e5e7eb; color: #4b5563; background: #fff; cursor: pointer; transition: all .12s; }
    .hd-chip:hover { border-color: #fca5a5; color: #991b1b; }
    .hd-chip.active { background: #991b1b; border-color: #991b1b; color: #fff; }
    .hd-tile { width: 46px; flex-shrink: 0; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; text-align: center; background: #fff; }
    .hd-tile .m { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: #991b1b; padding: 2px 0; }
    .hd-tile .d { font-size: 17px; font-weight: 700; color: #111827; line-height: 1.5; }
    .hd-tile.off .m { background: #9ca3af; }
</style>
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-days text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Holidays</h2>
                <p class="text-sm text-gray-500">Manage national holidays, collective leave, and custom holidays used by the system calendar &amp; date pickers.</p>
            </div>
        </div>
        <button onclick="openCreateHolidayModal()" class="inline-flex items-center justify-center px-4 py-2.5 primary-gradient text-white text-sm font-semibold rounded-xl shadow-sm hover:opacity-90 transition-all duration-200">
            <i class="fas fa-plus mr-2 text-xs"></i> Add Holiday
        </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center"><i class="fas fa-calendar-check"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statTotal">-</p><p class="text-xs text-gray-500 mt-1">Holidays listed</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"><i class="fas fa-flag"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statNational">-</p><p class="text-xs text-gray-500 mt-1">National</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i class="fas fa-umbrella-beach"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statCuti">-</p><p class="text-xs text-gray-500 mt-1">Collective leave</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-star"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statCustom">-</p><p class="text-xs text-gray-500 mt-1">Custom</p></div>
        </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <select id="yearFilter" onchange="loadHolidays()"
                    class="w-full sm:w-44 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
                    <option value="">All Years</option>
                </select>
                <div class="relative flex-1 sm:max-w-sm">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="holidaySearch" placeholder="Search holiday name..."
                        oninput="renderHolidays()"
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2" id="holidayTypeChips">
                    <button type="button" class="hd-chip active" data-t="all" onclick="setHolidayType('all')">All</button>
                    <button type="button" class="hd-chip" data-t="national" onclick="setHolidayType('national')">National Holiday</button>
                    <button type="button" class="hd-chip" data-t="cuti_bersama" onclick="setHolidayType('cuti_bersama')">Collective Leave</button>
                    <button type="button" class="hd-chip" data-t="custom" onclick="setHolidayType('custom')">Custom Holiday</button>
                </div>
                <p class="text-xs text-gray-500" id="holidayResultCount"></p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="holidaysTableBody" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Modal: Create / Edit Holiday ────────────────────────────────────────── -->
<div id="holidayModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-800 flex items-center justify-center"><i class="fas fa-calendar-days"></i></div>
                <h3 id="holidayModalTitle" class="text-base font-bold text-gray-900">Add Holiday</h3>
            </div>
            <button onclick="closeModal('holidayModal')" aria-label="Close" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="holidayForm" onsubmit="submitHoliday(event)" class="p-6 space-y-4">
            <input type="hidden" id="holidayId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                <input type="date" id="holidayDate" required
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input type="text" id="holidayName" required maxlength="255"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800"
                    placeholder="e.g. Independence Day">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                <select id="holidayType" required
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
                    <option value="national">National Holiday</option>
                    <option value="cuti_bersama">Collective Leave</option>
                    <option value="custom">Custom Holiday</option>
                </select>
            </div>
            <div class="flex items-center justify-between gap-3 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                <label for="holidayActive" class="text-sm text-gray-700 cursor-pointer">
                    <span class="font-semibold">Active</span>
                    <span class="block text-xs text-gray-500">Applied to calendar &amp; date pickers</span>
                </label>
                <label class="hd-switch">
                    <input type="checkbox" id="holidayActive" checked>
                    <span class="hd-track"></span>
                </label>
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeModal('holidayModal')" class="px-4 py-2.5 bg-white text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">Cancel</button>
                <button type="submit" class="px-5 py-2.5 primary-gradient text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Custom Confirm ───────────────────────────────────────────────── -->
<div id="confirmModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="p-6 text-center">
            <div id="confirmIconWrap" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4">
                <i id="confirmIcon" class="text-xl"></i>
            </div>
            <h3 id="confirmTitle" class="text-base font-semibold text-gray-800 mb-2"></h3>
            <p id="confirmMessage" class="text-sm text-gray-500 mb-6 leading-relaxed"></p>
            <div class="flex gap-3">
                <button id="confirmCancelBtn" class="flex-1 border border-gray-200 rounded-xl py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</button>
                <button id="confirmOkBtn" class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
let holidaysData = [];

const TYPE_BADGE = {
    national:     { label: 'National Holiday', cls: 'bg-red-50 text-red-700 border border-red-200' },
    cuti_bersama: { label: 'Collective Leave', cls: 'bg-amber-50 text-amber-700 border border-amber-200' },
    custom:       { label: 'Custom Holiday',   cls: 'bg-blue-50 text-blue-700 border border-blue-200' },
};
const DAY_NAMES = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// ── Load ─────────────────────────────────────────────────────────────────────

async function loadHolidays() {
    const year = document.getElementById('yearFilter').value;
    const url  = year ? `/api/management/holidays?year=${year}` : '/api/management/holidays';
    const res  = await fetch(url);
    const json = await res.json();
    holidaysData = json.data || [];
    populateYearFilter(json.years || []);
    renderHolidays();
}

function populateYearFilter(years) {
    const sel = document.getElementById('yearFilter');
    const current = sel.value;
    // Ensure the current year is always an available option
    const nowY = new Date().getFullYear();
    const set = new Set(years.map(Number));
    set.add(nowY);
    const sorted = [...set].sort((a, b) => b - a);
    sel.innerHTML = '<option value="">All Years</option>' +
        sorted.map(y => `<option value="${y}">${y}</option>`).join('');
    sel.value = current;
}

// ── Render ────────────────────────────────────────────────────────────────────

let holidayTypeFilter = 'all';

function setHolidayType(t) {
    holidayTypeFilter = t;
    document.querySelectorAll('#holidayTypeChips .hd-chip').forEach(b => b.classList.toggle('active', b.dataset.t === t));
    renderHolidays();
}

function updateHolidayStats() {
    const n = t => holidaysData.filter(h => h.type === t).length;
    document.getElementById('statTotal').textContent = holidaysData.length;
    document.getElementById('statNational').textContent = n('national');
    document.getElementById('statCuti').textContent = n('cuti_bersama');
    document.getElementById('statCustom').textContent = n('custom');
}

// "Today", "Tomorrow", "in 12 days" or "Past" (null bila hari libur tidak aktif).
function relativeLabel(d) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const diff = Math.round((d - today) / 86400000);
    if (diff === 0) return { text: 'Today', cls: 'bg-red-800 text-white' };
    if (diff === 1) return { text: 'Tomorrow', cls: 'bg-red-50 text-red-700' };
    if (diff > 1 && diff <= 60) return { text: `in ${diff} days`, cls: 'bg-red-50 text-red-700' };
    if (diff < 0) return { text: 'Past', cls: 'bg-gray-100 text-gray-400' };
    return null;
}

function renderHolidays() {
    const tbody = document.getElementById('holidaysTableBody');
    const query = (document.getElementById('holidaySearch')?.value || '').toLowerCase().trim();
    const filtered = holidaysData.filter(h =>
        (holidayTypeFilter === 'all' || h.type === holidayTypeFilter) &&
        (!query || h.name.toLowerCase().includes(query)));

    updateHolidayStats();
    document.getElementById('holidayResultCount').textContent =
        holidaysData.length ? `Showing ${filtered.length} of ${holidaysData.length} holidays` : '';

    if (!filtered.length) {
        const filtering = query || holidayTypeFilter !== 'all';
        tbody.innerHTML = `<tr><td colspan="5" class="px-5 py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3"><i class="fas ${filtering ? 'fa-search' : 'fa-calendar'}"></i></div>
            <p class="text-sm font-medium text-gray-700">${filtering ? 'No holidays match your filters' : 'No holidays yet'}</p>
            <p class="text-xs text-gray-400 mt-1">${filtering ? 'Try another keyword or type.' : 'Use Add Holiday to create the first one.'}</p></td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(h => {
        const badge = TYPE_BADGE[h.type] || { label: h.type, cls: 'bg-gray-100 text-gray-700 border border-gray-200' };
        const d = new Date(h.date + 'T00:00:00');
        const dayName = DAY_NAMES[d.getDay()];
        const month = d.toLocaleDateString('en-GB', { month: 'short' });
        const dateLabel = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
        const rel = h.is_active ? relativeLabel(d) : null;
        return `
        <tr class="hover:bg-gray-50/70 transition-colors ${h.is_active ? '' : 'opacity-60'}">
            <td class="px-5 py-3.5 whitespace-nowrap">
                <div class="flex items-center gap-3">
                    <div class="hd-tile ${h.is_active ? '' : 'off'}"><div class="m">${month}</div><div class="d">${d.getDate()}</div></div>
                    <div>
                        <p class="font-semibold text-gray-900">${dateLabel}</p>
                        <p class="text-xs text-gray-500">${dayName}</p>
                    </div>
                </div>
            </td>
            <td class="px-5 py-3.5 text-gray-800">
                <span class="font-medium">${escHtml(h.name)}</span>
                ${rel ? `<span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ${rel.cls}">${rel.text}</span>` : ''}
            </td>
            <td class="px-5 py-3.5"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${badge.cls}">${badge.label}</span></td>
            <td class="px-5 py-3.5">
                ${h.is_active
                    ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Active</span>'
                    : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Inactive</span>'}
            </td>
            <td class="px-5 py-3.5">
                <div class="flex items-center justify-end gap-2">
                    <button onclick="openEditHolidayModal(${h.id})" title="Edit holiday" aria-label="Edit holiday"
                        class="w-8 h-8 flex items-center justify-center text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-800 transition-colors">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    <button onclick="deleteHoliday(${h.id})" title="Delete holiday" aria-label="Delete holiday"
                        class="w-8 h-8 flex items-center justify-center text-red-500 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-200 transition-colors">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Create / Edit ──────────────────────────────────────────────────────────────

function openCreateHolidayModal() {
    document.getElementById('holidayModalTitle').textContent = 'Add Holiday';
    document.getElementById('holidayId').value = '';
    document.getElementById('holidayDate').value = '';
    document.getElementById('holidayName').value = '';
    document.getElementById('holidayType').value = 'national';
    document.getElementById('holidayActive').checked = true;
    openModal('holidayModal');
}

function openEditHolidayModal(id) {
    const h = holidaysData.find(x => x.id === id);
    if (!h) return;
    document.getElementById('holidayModalTitle').textContent = 'Edit Holiday';
    document.getElementById('holidayId').value = h.id;
    document.getElementById('holidayDate').value = h.date;
    document.getElementById('holidayName').value = h.name;
    document.getElementById('holidayType').value = h.type;
    document.getElementById('holidayActive').checked = !!h.is_active;
    openModal('holidayModal');
}

async function submitHoliday(e) {
    e.preventDefault();
    const id = document.getElementById('holidayId').value;
    const payload = {
        date:      document.getElementById('holidayDate').value,
        name:      document.getElementById('holidayName').value.trim(),
        type:      document.getElementById('holidayType').value,
        is_active: document.getElementById('holidayActive').checked,
    };
    const url    = id ? `/api/management/holidays/${id}` : '/api/management/holidays';
    const method = id ? 'PUT' : 'POST';

    const res  = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(payload) });
    const json = await res.json();

    if (json.success) {
        closeModal('holidayModal');
        showToast(id ? 'Holiday updated successfully.' : 'Holiday added successfully.', 'success');
        loadHolidays();
    } else {
        showToast(json.message || 'Failed to save holiday.', 'error');
    }
}

async function deleteHoliday(id) {
    const h = holidaysData.find(x => x.id === id);
    const ok = await customConfirm({
        title:     'Delete Holiday?',
        message:   `You are about to delete "${h?.name ?? ''}" (${h?.date ?? ''}). This action cannot be undone.`,
        okLabel:   'Yes, Delete',
        okClass:   'bg-red-600 hover:bg-red-700',
        icon:      'fas fa-trash',
        iconBg:    'bg-red-50',
        iconColor: 'text-red-500',
    });
    if (!ok) return;
    const res  = await fetch(`/api/management/holidays/${id}/delete`, { method: 'POST', headers: jsonHeaders() });
    const json = await res.json();
    if (json.success) {
        showToast('Holiday deleted successfully.', 'success');
        loadHolidays();
    } else {
        showToast(json.message || 'Failed to delete holiday.', 'error');
    }
}

// ── Custom Confirm ──────────────────────────────────────────────────────────────

function customConfirm({ title, message, okLabel = 'Confirm', okClass = 'bg-red-600 hover:bg-red-700', icon = 'fas fa-exclamation-triangle', iconBg = 'bg-red-50', iconColor = 'text-red-500' } = {}) {
    return new Promise(resolve => {
        const modal     = document.getElementById('confirmModal');
        const okBtn     = document.getElementById('confirmOkBtn');
        const cancelBtn = document.getElementById('confirmCancelBtn');
        document.getElementById('confirmTitle').textContent   = title || '';
        document.getElementById('confirmMessage').textContent = message || '';
        okBtn.textContent  = okLabel;
        okBtn.className    = `flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition ${okClass}`;
        document.getElementById('confirmIconWrap').className = `w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 ${iconBg}`;
        document.getElementById('confirmIcon').className     = `${icon} text-xl ${iconColor}`;
        modal.classList.remove('hidden');
        function done(val) {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            resolve(val);
        }
        const onOk     = () => done(true);
        const onCancel = () => done(false);
        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
    });
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function openModal(id)  { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    };
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

document.getElementById('holidayModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeModal('holidayModal');
});
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    if (!document.getElementById('confirmModal').classList.contains('hidden')) return;
    closeModal('holidayModal');
});

// Init
document.getElementById('yearFilter').value = '';
loadHolidays();
</script>
@endsection
