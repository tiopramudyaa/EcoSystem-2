@extends('dashboard')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-shield text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Role Management</h2>
                <p class="text-sm text-gray-500">Create roles and choose which menus each role can open.</p>
            </div>
        </div>
        <button onclick="openCreateRoleModal()" class="inline-flex items-center justify-center px-4 py-2.5 primary-gradient text-white text-sm font-semibold rounded-xl shadow-sm hover:opacity-90 transition-all duration-200">
            <i class="fas fa-plus mr-2 text-xs"></i> Add Role
        </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center"><i class="fas fa-layer-group"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statRoles">-</p><p class="text-xs text-gray-500 mt-1">Total roles</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-users"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statMembers">-</p><p class="text-xs text-gray-500 mt-1">Role assignments</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i class="fas fa-user-slash"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statUnused">-</p><p class="text-xs text-gray-500 mt-1">Roles without members</p></div>
        </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="roleSearch" placeholder="Search role name or description..."
                    oninput="filterRoles()"
                    class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
            </div>
            <p class="text-xs text-gray-500" id="roleResultCount"></p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-16">ID</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Members</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Modal: Create / Edit Role ──────────────────────────────────────────── -->
<div id="roleModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-800 flex items-center justify-center"><i class="fas fa-user-shield"></i></div>
                <h3 id="roleModalTitle" class="text-base font-bold text-gray-900">Add Role</h3>
            </div>
            <button onclick="closeModal('roleModal')" aria-label="Close" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="roleForm" onsubmit="submitRole(event)" class="p-6 space-y-4">
            <input type="hidden" id="roleId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input type="text" id="roleName" required maxlength="100"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800"
                    placeholder="e.g. Project Manager">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                <textarea id="roleDescription" maxlength="255" rows="3"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800 resize-none"
                    placeholder="Optional description..."></textarea>
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeModal('roleModal')" class="px-4 py-2.5 bg-white text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">Cancel</button>
                <button type="submit" class="px-5 py-2.5 primary-gradient text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Members ─────────────────────────────────────────────────────── -->
<div id="membersModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden">
        <div class="flex justify-between items-start gap-4 px-6 pt-5 pb-4 flex-shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-users"></i></div>
                <div class="min-w-0">
                    <h3 id="membersModalTitle" class="text-base font-bold text-gray-900 leading-tight truncate">Members</h3>
                    <p id="membersModalSub" class="text-sm text-gray-500">Members of this role</p>
                </div>
            </div>
            <button onclick="closeModal('membersModal')" aria-label="Close" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="membersSearchWrap" class="hidden px-6 pb-3 flex-shrink-0">
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="memberSearch" placeholder="Search name or ECI..." oninput="renderMembers()"
                    class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
            </div>
        </div>
        <div id="membersModalBody" class="border-t border-gray-100 overflow-y-auto flex-1 px-6 py-2">
            <p class="text-gray-400 text-sm text-center py-8">Loading...</p>
        </div>
    </div>
</div>

<!-- ── Modal: Custom Confirm ───────────────────────────────────────────────── -->
<div id="confirmModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm animate-in">
        <div class="p-6 text-center">
            <div id="confirmIconWrap" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4">
                <i id="confirmIcon" class="text-xl"></i>
            </div>
            <h3 id="confirmTitle" class="text-base font-semibold text-gray-800 mb-2"></h3>
            <p id="confirmMessage" class="text-sm text-gray-500 mb-6 leading-relaxed"></p>
            <div class="flex gap-3">
                <button id="confirmCancelBtn"
                    class="flex-1 border border-gray-200 rounded-xl py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button id="confirmOkBtn"
                    class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Menu Access ─────────────────────────────────────────────────── -->
<style>
    .ma-switch { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
    .ma-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
    .ma-switch input:disabled { cursor: not-allowed; }
    .ma-switch .ma-track { position: absolute; inset: 0; background: #d1d5db; border-radius: 999px; transition: background .15s; }
    .ma-switch .ma-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .15s; }
    .ma-switch input:checked + .ma-track { background: #991b1b; }
    .ma-switch input:checked + .ma-track::after { transform: translateX(16px); }
    .ma-switch input:focus-visible + .ma-track { box-shadow: 0 0 0 3px rgba(153,27,27,.25); }
    .ma-switch input:disabled + .ma-track { opacity: .45; }
    .ma-row { display: flex; align-items: center; gap: 10px; padding: 9px 20px 9px 16px; border-bottom: 1px solid #f3f4f6; transition: background .12s; }
    .ma-row:hover { background: #fafafa; }
    .ma-row.ma-group { background: #f9fafb; }
    .ma-row.ma-group:hover { background: #f3f4f6; }
    .ma-ico { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .ma-chip { font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 999px; border: 1px solid #e5e7eb; color: #4b5563; background: #fff; cursor: pointer; transition: all .12s; }
    .ma-chip:hover { border-color: #fca5a5; color: #991b1b; }
    .ma-chip.active { background: #991b1b; border-color: #991b1b; color: #fff; }
</style>
<div id="menuAccessModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 flex-shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-800 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-900 leading-tight">Menu Access</h3>
                    <p class="text-sm text-gray-500 truncate">Role: <span id="menuAccessRole" class="font-semibold text-gray-700"></span></p>
                </div>
            </div>
            <button onclick="closeModal('menuAccessModal')" aria-label="Close"
                class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Summary -->
        <div class="px-6 pb-4 flex-shrink-0">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                <span><span id="menuAccessCount" class="font-semibold text-gray-800">0</span> of <span id="menuAccessTotal">0</span> menus granted</span>
                <span id="menuAccessPct" class="font-semibold text-red-800">0%</span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                <div id="menuAccessBar" class="h-full bg-red-800 rounded-full transition-all duration-300" style="width:0%"></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="px-6 py-3 border-y border-gray-100 bg-gray-50/60 flex-shrink-0 space-y-3">
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="menuSearch" placeholder="Search menu name..." oninput="filterMenuRows()"
                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2" id="menuFilterChips">
                    <button type="button" class="ma-chip active" data-f="all" onclick="setMenuFilter('all')">All</button>
                    <button type="button" class="ma-chip" data-f="granted" onclick="setMenuFilter('granted')">Granted</button>
                    <button type="button" class="ma-chip" data-f="denied" onclick="setMenuFilter('denied')">Not granted</button>
                </div>
                <div class="flex items-center gap-3 text-xs font-medium text-gray-500">
                    <button type="button" onclick="expandAllMenus(true)" class="hover:text-red-800 transition"><i class="fas fa-angles-down mr-1"></i>Expand all</button>
                    <button type="button" onclick="expandAllMenus(false)" class="hover:text-red-800 transition"><i class="fas fa-angles-up mr-1"></i>Collapse all</button>
                </div>
            </div>
        </div>

        <div id="menuAccessList" class="overflow-y-auto flex-1">
            <div class="px-4 py-10 text-center text-gray-400 text-sm">Loading...</div>
        </div>
    </div>
</div>

<script>
let rolesData = [];
let allMenusData = [];
let currentRoleId = null;
let roleMenuPermissions = {};

// ── Load ─────────────────────────────────────────────────────────────────────

async function loadRoles() {
    const res = await fetch('/api/roles');
    const json = await res.json();
    rolesData = json.data || [];
    renderRoles();
}

async function loadAllMenus() {
    const res = await fetch('/api/menus/all');
    const json = await res.json();
    allMenusData = json.data || [];
}

// ── Filter ────────────────────────────────────────────────────────────────────

function filterRoles() {
    renderRoles();
}

// ── Render ────────────────────────────────────────────────────────────────────

function updateRoleStats() {
    const members = rolesData.reduce((sum, r) => sum + (Number(r.employees_count) || 0), 0);
    document.getElementById('statRoles').textContent = rolesData.length;
    document.getElementById('statMembers').textContent = members;
    document.getElementById('statUnused').textContent = rolesData.filter(r => !Number(r.employees_count)).length;
}

function roleInitials(name) {
    const parts = String(name || '?').trim().split(/\s+/).filter(Boolean);
    const letters = parts.length > 1 ? parts[0][0] + parts[1][0] : (parts[0] || '?').slice(0, 2);
    return letters.toUpperCase();
}

function renderRoles() {
    const tbody = document.getElementById('rolesTableBody');
    const query = (document.getElementById('roleSearch')?.value || '').toLowerCase().trim();
    const filtered = query
        ? rolesData.filter(r =>
            r.name.toLowerCase().includes(query) ||
            (r.description || '').toLowerCase().includes(query))
        : rolesData;

    updateRoleStats();
    document.getElementById('roleResultCount').textContent =
        rolesData.length ? `Showing ${filtered.length} of ${rolesData.length} roles` : '';

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-5 py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3"><i class="fas fa-search"></i></div>
            <p class="text-sm font-medium text-gray-700">${query ? 'No roles match your search' : 'No roles found'}</p>
            <p class="text-xs text-gray-400 mt-1">${query ? 'Try another keyword.' : 'Use Add Role to create the first one.'}</p></td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map(role => {
        const count = Number(role.employees_count) || 0;
        return `
        <tr class="hover:bg-gray-50/70 transition-colors">
            <td class="px-5 py-3.5 text-gray-400 text-xs">${role.id}</td>
            <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-red-50 text-red-800 text-xs font-bold flex items-center justify-center flex-shrink-0">${escHtml(roleInitials(role.name))}</span>
                    <span class="font-semibold text-gray-900">${escHtml(role.name)}</span>
                </div>
            </td>
            <td class="px-5 py-3.5 text-gray-500 max-w-md">${role.description ? escHtml(role.description) : '<span class="text-gray-300">No description</span>'}</td>
            <td class="px-5 py-3.5">
                <button onclick="openEmployeesModal(${role.id})" title="View members"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition-colors ${count ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'}">
                    <i class="fas fa-user text-[10px]"></i>
                    ${count} member${count !== 1 ? 's' : ''}
                </button>
            </td>
            <td class="px-5 py-3.5">
                <div class="flex items-center justify-end gap-2">
                    <button onclick="openMenuAccessModal(${role.id}, '${escHtml(role.name)}')"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-red-50 text-red-800 border border-red-100 rounded-lg hover:bg-red-100 transition-colors">
                        <i class="fas fa-key mr-1.5"></i> Menu Access
                    </button>
                    <button onclick="openEditRoleModal(${role.id})" title="Edit role" aria-label="Edit role"
                        class="w-8 h-8 flex items-center justify-center text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-800 transition-colors">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    <button onclick="deleteRole(${role.id}, '${escHtml(role.name)}')" title="Delete role" aria-label="Delete role"
                        class="w-8 h-8 flex items-center justify-center text-red-500 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-200 transition-colors">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Create / Edit Role ────────────────────────────────────────────────────────

function openCreateRoleModal() {
    document.getElementById('roleModalTitle').textContent = 'Add Role';
    document.getElementById('roleId').value = '';
    document.getElementById('roleName').value = '';
    document.getElementById('roleDescription').value = '';
    openModal('roleModal');
}

function openEditRoleModal(id) {
    const role = rolesData.find(r => r.id === id);
    if (!role) return;
    document.getElementById('roleModalTitle').textContent = 'Edit Role';
    document.getElementById('roleId').value = role.id;
    document.getElementById('roleName').value = role.name;
    document.getElementById('roleDescription').value = role.description || '';
    openModal('roleModal');
}

async function submitRole(e) {
    e.preventDefault();
    const id = document.getElementById('roleId').value;
    const payload = {
        name:        document.getElementById('roleName').value.trim(),
        description: document.getElementById('roleDescription').value.trim(),
    };
    const url    = id ? `/api/roles/${id}` : '/api/roles';
    const method = id ? 'PUT' : 'POST';

    const res  = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(payload) });
    const json = await res.json();

    if (json.success) {
        closeModal('roleModal');
        showToast(id ? 'Role updated successfully.' : 'Role added successfully.', 'success');
        loadRoles();
    } else {
        showToast(json.message || 'Failed to save role.', 'error');
    }
}

async function deleteRole(id, name) {
    const ok = await customConfirm({
        title:    `Delete Role?`,
        message:  `You are about to delete the role "${name}". This action cannot be undone.`,
        okLabel:  'Yes, Delete',
        okClass:  'bg-red-600 hover:bg-red-700',
        icon:     'fas fa-trash',
        iconBg:   'bg-red-50',
        iconColor:'text-red-500',
    });
    if (!ok) return;
    const res  = await fetch(`/api/roles/${id}/delete`, { method: 'POST', headers: jsonHeaders() });
    const json = await res.json();
    if (json.success) {
        showToast(`Role "${name}" deleted successfully.`, 'success');
        loadRoles();
    } else {
        showToast(json.message || 'Failed to delete role.', 'error');
    }
}

// ── Members Modal ─────────────────────────────────────────────────────────────

let currentMembers = [];

async function openEmployeesModal(id) {
    const role = rolesData.find(r => r.id === id);
    document.getElementById('membersModalTitle').textContent = role?.name ?? 'Members';
    document.getElementById('membersModalSub').textContent = 'Loading members...';
    document.getElementById('membersSearchWrap').classList.add('hidden');
    document.getElementById('memberSearch').value = '';
    document.getElementById('membersModalBody').innerHTML = '<p class="text-gray-400 text-sm text-center py-8">Loading...</p>';
    openModal('membersModal');

    try {
        const res  = await fetch(`/api/roles/${id}/employees`);
        const json = await res.json();
        currentMembers = json.data || [];
    } catch (e) {
        document.getElementById('membersModalSub').textContent = 'Could not load members';
        document.getElementById('membersModalBody').innerHTML = '<p class="text-red-500 text-sm text-center py-8">Failed to load data.</p>';
        return;
    }

    document.getElementById('membersModalSub').textContent =
        `${currentMembers.length} member${currentMembers.length !== 1 ? 's' : ''}`;
    // Pencarian hanya berguna untuk role besar.
    document.getElementById('membersSearchWrap').classList.toggle('hidden', currentMembers.length <= 8);
    renderMembers();
}

function renderMembers() {
    const body  = document.getElementById('membersModalBody');
    const query = (document.getElementById('memberSearch')?.value || '').toLowerCase().trim();

    if (!currentMembers.length) {
        body.innerHTML = `<div class="py-10 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3"><i class="fas fa-user-slash"></i></div>
            <p class="text-sm font-medium text-gray-700">No members yet</p>
            <p class="text-xs text-gray-400 mt-1">Assign this role to people in Master &rsaquo; Employee.</p></div>`;
        return;
    }

    const list = query
        ? currentMembers.filter(m => (m.full_name || '').toLowerCase().includes(query) || (m.eci || '').toLowerCase().includes(query))
        : currentMembers;

    if (!list.length) {
        body.innerHTML = '<p class="text-gray-400 text-sm text-center py-8">No members match your search.</p>';
        return;
    }

    body.innerHTML = `<ul class="divide-y divide-gray-100">${list.map(m => `
        <li class="py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-red-50 text-red-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                ${escHtml(roleInitials(m.full_name || m.eci || '?'))}
            </span>
            <p class="text-sm font-semibold text-gray-900 flex-1 min-w-0 truncate">${escHtml(m.full_name || '-')}</p>
            ${m.eci ? `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 flex-shrink-0">${escHtml(m.eci)}</span>` : ''}
        </li>`).join('')}</ul>`;
}

// ── Menu Access Modal ─────────────────────────────────────────────────────────

let menuTree = [];
let expandedMenus = new Set();
let savingMenu = {};
let currentRoleName = '';
let menuFilter = 'all';

async function openMenuAccessModal(id, roleName) {
    currentRoleId = id;
    currentRoleName = roleName;
    document.getElementById('menuAccessRole').textContent = roleName;
    document.getElementById('menuSearch').value = '';
    menuFilter = 'all';
    syncFilterChips();
    document.getElementById('menuAccessList').innerHTML = '<div class="px-4 py-10 text-center text-gray-400 text-sm">Loading...</div>';
    openModal('menuAccessModal');

    const [permRes, menuRes] = await Promise.all([
        fetch(`/api/roles/${id}/permissions`),
        allMenusData.length ? Promise.resolve(null) : fetch('/api/menus/all'),
    ]);

    const permJson = await permRes.json();
    if (menuRes) {
        allMenusData = (await menuRes.json()).data || [];
    }

    roleMenuPermissions = {};
    (permJson.data || []).forEach(m => { roleMenuPermissions[m.id] = m.pivot; });

    menuTree = buildMenuTree(allMenusData, null);
    expandedMenus.clear();
    menuTree.forEach(n => expandedMenus.add(n.id)); // expand level 1
    renderMenuAccessTree();
}

function buildMenuTree(items, parentId) {
    return items
        .filter(m => m.parent_id == parentId)
        .sort((a, b) => (a.order_seq || 0) - (b.order_seq || 0))
        .map(m => ({ ...m, children: buildMenuTree(items, m.id) }));
}

function menuHasAccess(id) { return !!(roleMenuPermissions[id]?.can_view); }

// [granted, total] untuk semua turunan (tanpa node itu sendiri)
function menuDescendantCounts(node) {
    let g = 0, t = 0;
    (node.children || []).forEach(c => {
        t++; if (menuHasAccess(c.id)) g++;
        const [cg, ct] = menuDescendantCounts(c);
        g += cg; t += ct;
    });
    return [g, t];
}

function updateMenuSummary() {
    const total = allMenusData.length;
    const granted = allMenusData.filter(m => menuHasAccess(m.id)).length;
    const pct = total ? Math.round(granted / total * 100) : 0;
    document.getElementById('menuAccessCount').textContent = granted;
    document.getElementById('menuAccessTotal').textContent = total;
    document.getElementById('menuAccessPct').textContent = pct + '%';
    document.getElementById('menuAccessBar').style.width = pct + '%';
}

function syncFilterChips() {
    document.querySelectorAll('#menuFilterChips .ma-chip').forEach(b => b.classList.toggle('active', b.dataset.f === menuFilter));
}

function setMenuFilter(f) {
    menuFilter = f;
    syncFilterChips();
    filterMenuRows();
}

function expandAllMenus(open) {
    expandedMenus.clear();
    if (open) allMenusData.forEach(m => expandedMenus.add(m.id));
    renderMenuAccessTree();
}

function renderMenuAccessTree() {
    const search = document.getElementById('menuSearch').value.toLowerCase().trim();
    const container = document.getElementById('menuAccessList');
    container.innerHTML = '';
    updateMenuSummary();

    let hasAny = false;
    menuTree.forEach(node => {
        hasAny = renderMenuNode(node, 0, container, search) || hasAny;
    });

    if (!hasAny) {
        container.innerHTML = `<div class="px-4 py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3"><i class="fas fa-search"></i></div>
            <p class="text-sm font-medium text-gray-700">No menus found</p>
            <p class="text-xs text-gray-400 mt-1">Try another keyword or filter.</p></div>`;
    }
}

function renderMenuNode(node, depth, container, search, parentAccessible = true) {
    if (!menuNodeMatches(node, search)) return false;

    const hasChildren  = node.children && node.children.length > 0;
    const isExpanded   = expandedMenus.has(node.id);
    const hasAccess    = menuHasAccess(node.id);
    const isDisabled   = !parentAccessible; // disable jika parent tidak punya akses

    const typeStyle = {
        group:    { icon: 'fa-layer-group', box: 'bg-purple-50 text-purple-500', textCls: 'font-semibold text-gray-900', label: 'Group' },
        page:     { icon: 'fa-file-alt',    box: 'bg-blue-50 text-blue-500',     textCls: 'font-medium text-gray-800',   label: 'Page' },
        function: { icon: 'fa-bolt',        box: 'bg-orange-50 text-orange-500', textCls: 'text-gray-700',               label: 'Action' },
    }[node.type] || { icon: 'fa-circle', box: 'bg-gray-100 text-gray-400', textCls: 'text-gray-700', label: '' };

    const labelCls = isDisabled ? 'text-gray-400' : typeStyle.textCls;
    const [dg, dt] = hasChildren ? menuDescendantCounts(node) : [0, 0];

    const div = document.createElement('div');
    div.className = `ma-row ${node.type === 'group' ? 'ma-group' : ''}`;
    div.style.paddingLeft = `${16 + depth * 24}px`;
    if (isDisabled) {
        div.style.opacity = '.55';
        div.title = 'Parent menu is inactive - enable the parent first';
    }

    div.innerHTML = `
        ${hasChildren
            ? `<button onclick="toggleMenuNode(${node.id})" aria-label="Toggle"
                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-red-800 flex-shrink-0 focus:outline-none">
                   <i class="fas ${isExpanded ? 'fa-chevron-down' : 'fa-chevron-right'} text-[10px]"></i>
               </button>`
            : `<span class="w-5 flex-shrink-0"></span>`
        }
        <span class="ma-ico ${typeStyle.box}"><i class="fas ${typeStyle.icon}"></i></span>
        <span class="text-sm ${labelCls} flex-1 leading-tight min-w-0 truncate">${escHtml(node.name)}</span>
        ${typeStyle.label ? `<span class="hidden sm:inline text-[10px] uppercase tracking-wide font-semibold text-gray-400">${typeStyle.label}</span>` : ''}
        ${hasChildren ? `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full ${dg === dt ? 'bg-green-50 text-green-700' : (dg ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500')}">${dg}/${dt}</span>` : ''}
        <label class="ma-switch" title="${escHtml(node.name)}">
            <input type="checkbox" ${hasAccess ? 'checked' : ''} ${isDisabled ? 'disabled' : ''}
                onchange="toggleMenuAccess(${node.id}, this)"
                title="${escHtml(node.name)}">
            <span class="ma-track"></span>
        </label>
    `;

    container.appendChild(div);

    if (hasChildren && isExpanded) {
        // Child accessible hanya jika parent ini juga accessible
        const childAccessible = parentAccessible && hasAccess;
        node.children.forEach(child => renderMenuNode(child, depth + 1, container, search, childAccessible));
    }

    return true;
}

// Node tampil jika dirinya atau salah satu turunannya cocok dengan pencarian dan filter.
function menuNodeMatches(node, search) {
    const nameOk = !search || node.name.toLowerCase().includes(search);
    const has = menuHasAccess(node.id);
    const filterOk = menuFilter === 'all' || (menuFilter === 'granted' ? has : !has);
    if (nameOk && filterOk) return true;
    return (node.children || []).some(c => menuNodeMatches(c, search));
}

function toggleMenuNode(id) {
    expandedMenus.has(id) ? expandedMenus.delete(id) : expandedMenus.add(id);
    renderMenuAccessTree();
}

function filterMenuRows() {
    if (document.getElementById('menuSearch').value || menuFilter !== 'all') {
        allMenusData.forEach(m => expandedMenus.add(m.id));
    }
    renderMenuAccessTree();
}

async function toggleMenuAccess(menuId, checkbox) {
    const key = `${currentRoleId}-${menuId}`;
    if (savingMenu[key]) { checkbox.checked = !checkbox.checked; return; }

    const grant = checkbox.checked;
    const menuName = checkbox.title || 'menu ini';
    const roleName = currentRoleName;
    const node = findMenuNode(menuTree, menuId);
    const hasChildren = !!(node?.children?.length);

    if (grant) {
        const message = hasChildren
            ? `Grant access to "${menuName}" and all its sub-items for role "${roleName}"?`
            : `Grant access to "${menuName}" for role "${roleName}"?`;
        const confirmed = await customConfirm({
            title:    'Grant Access?',
            message,
            okLabel:  'Yes, Grant',
            okClass:  'bg-green-600 hover:bg-green-700',
            icon:     'fas fa-key',
            iconBg:   'bg-green-50',
            iconColor:'text-green-600',
        });
        if (!confirmed) { checkbox.checked = false; return; }
    } else {
        const confirmed = await customConfirm({
            title:    'Revoke Access?',
            message:  `Revoke access to "${menuName}" from role "${roleName}"?`,
            okLabel:  'Yes, Revoke',
            okClass:  'bg-red-600 hover:bg-red-700',
            icon:     'fas fa-ban',
            iconBg:   'bg-red-50',
            iconColor:'text-red-500',
        });
        if (!confirmed) { checkbox.checked = true; return; }
    }

    savingMenu[key] = true;
    checkbox.disabled = true;
    let ok = false;
    try {
        if (grant) {
            const res = await fetch(`/api/roles/${currentRoleId}/permissions/${menuId}`, {
                method: 'PUT', headers: jsonHeaders(),
                body: JSON.stringify({ can_view: true, can_create: false, can_edit: false, can_delete: false }),
            });
            ok = (await res.json()).success;
        } else {
            const res = await fetch(`/api/roles/${currentRoleId}/permissions/${menuId}/revoke`, {
                method: 'POST', headers: jsonHeaders(),
            });
            ok = (await res.json()).success;
        }
    } catch(e) { ok = false; }

    if (!ok) {
        checkbox.checked = !grant;
        showToast('Failed to update access. Please try again.', 'error');
    } else {
        if (grant) {
            roleMenuPermissions[menuId] = { can_view: true, can_create: false, can_edit: false, can_delete: false };
            if (hasChildren) await cascadeGrantChildren(menuId);
            showToast(`Access to "${menuName}"${hasChildren ? ' and all sub-items' : ''} granted successfully.`, 'success');
        } else {
            delete roleMenuPermissions[menuId];
            await cascadeRevokeChildren(menuId);
            showToast(`Access to "${menuName}" revoked successfully.`, 'success');
        }
        renderMenuAccessTree();
    }

    checkbox.disabled = false;
    delete savingMenu[key];
}

// Kumpulkan semua descendant ID yang belum punya akses, lalu grant paralel
async function cascadeGrantChildren(parentId) {
    const toGrant = [];
    function collect(pId) {
        const n = findMenuNode(menuTree, pId);
        if (!n?.children?.length) return;
        for (const child of n.children) {
            if (!roleMenuPermissions[child.id]) toGrant.push(child.id);
            collect(child.id);
        }
    }
    collect(parentId);
    if (!toGrant.length) return;

    await Promise.all(toGrant.map(async id => {
        try {
            const res = await fetch(`/api/roles/${currentRoleId}/permissions/${id}`, {
                method: 'PUT', headers: jsonHeaders(),
                body: JSON.stringify({ can_view: true, can_create: false, can_edit: false, can_delete: false }),
            });
            if ((await res.json()).success) {
                roleMenuPermissions[id] = { can_view: true, can_create: false, can_edit: false, can_delete: false };
            }
        } catch(e) {}
    }));
}

// Kumpulkan semua descendant ID yang punya akses, lalu revoke paralel
async function cascadeRevokeChildren(parentId) {
    const toRevoke = [];
    function collect(pId) {
        const n = findMenuNode(menuTree, pId);
        if (!n?.children?.length) return;
        for (const child of n.children) {
            if (roleMenuPermissions[child.id]) toRevoke.push(child.id);
            collect(child.id);
        }
    }
    collect(parentId);
    if (!toRevoke.length) return;

    await Promise.all(toRevoke.map(async id => {
        try {
            await fetch(`/api/roles/${currentRoleId}/permissions/${id}/revoke`, {
                method: 'POST', headers: jsonHeaders(),
            });
        } catch(e) {}
        delete roleMenuPermissions[id];
    }));
}

// Rekursif beri akses semua children (dan panggil API grant)
async function cascadeGrantChildren(parentId) {
    const node = findMenuNode(menuTree, parentId);
    if (!node || !node.children?.length) return;

    for (const child of node.children) {
        if (!roleMenuPermissions[child.id]) {
            try {
                const res = await fetch(`/api/roles/${currentRoleId}/permissions/${child.id}`, {
                    method: 'PUT', headers: jsonHeaders(),
                    body: JSON.stringify({ can_view: true, can_create: false, can_edit: false, can_delete: false }),
                });
                if ((await res.json()).success) {
                    roleMenuPermissions[child.id] = { can_view: true, can_create: false, can_edit: false, can_delete: false };
                }
            } catch(e) {}
        }
        // Rekursif ke grandchildren
        await cascadeGrantChildren(child.id);
    }
}

function findMenuNode(nodes, id) {
    for (const n of nodes) {
        if (n.id === id) return n;
        const found = findMenuNode(n.children || [], id);
        if (found) return found;
    }
    return null;
}

// ── Custom Confirm Dialog ─────────────────────────────────────────────────────

function customConfirm({ title, message, okLabel = 'Confirm', okClass = 'bg-red-600 hover:bg-red-700', icon = 'fas fa-exclamation-triangle', iconBg = 'bg-red-50', iconColor = 'text-red-500' } = {}) {
    return new Promise(resolve => {
        const modal      = document.getElementById('confirmModal');
        const titleEl    = document.getElementById('confirmTitle');
        const msgEl      = document.getElementById('confirmMessage');
        const okBtn      = document.getElementById('confirmOkBtn');
        const cancelBtn  = document.getElementById('confirmCancelBtn');
        const iconWrap   = document.getElementById('confirmIconWrap');
        const iconEl     = document.getElementById('confirmIcon');

        titleEl.textContent  = title || '';
        msgEl.textContent    = message || '';
        okBtn.textContent    = okLabel;
        okBtn.className      = `flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition ${okClass}`;
        iconWrap.className   = `w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 ${iconBg}`;
        iconEl.className     = `${icon} text-xl ${iconColor}`;

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

// Close modal on backdrop click
['roleModal','membersModal','menuAccessModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// Init
loadRoles();

// Escape dan klik di luar kartu menutup modal (modal konfirmasi diurus sendiri).
const _closableModals = ['roleModal', 'membersModal', 'menuAccessModal'];
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!document.getElementById('confirmModal').classList.contains('hidden')) return;
    _closableModals.forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('hidden')) closeModal(id);
    });
});
_closableModals.forEach(id => {
    document.getElementById(id)?.addEventListener('mousedown', function (e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endsection
