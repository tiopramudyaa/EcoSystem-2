@extends('dashboard')

@section('title', 'Menu Access')
@section('page-title', 'Menu Access')

@section('content')
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-sitemap text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Menu List</h2>
                <p class="text-sm text-gray-500">Every menu that can be granted to a role, with its slug. Read only.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="expandAll()" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold bg-white text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                <i class="fas fa-angles-down mr-1.5"></i> Expand All
            </button>
            <button onclick="collapseAll()" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold bg-white text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                <i class="fas fa-angles-up mr-1.5"></i> Collapse All
            </button>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-800 flex items-center justify-center"><i class="fas fa-list"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statTotal">-</p><p class="text-xs text-gray-500 mt-1">Total menus</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center"><i class="fas fa-layer-group"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statGroup">-</p><p class="text-xs text-gray-500 mt-1">Groups</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fas fa-file-alt"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statPage">-</p><p class="text-xs text-gray-500 mt-1">Pages</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center"><i class="fas fa-bolt"></i></div>
            <div><p class="text-2xl font-bold text-gray-900 leading-none" id="statFunction">-</p><p class="text-xs text-gray-500 mt-1">Functions / buttons</p></div>
        </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            <div class="flex gap-3">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="filterSearch" placeholder="Search menu name..."
                        oninput="applyFilter()"
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-red-800/30 focus:border-red-800">
                </div>
                <button onclick="resetFilter()"
                    class="px-4 py-2.5 bg-white text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">
                    Reset
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2" id="typeChips">
                    <button type="button" class="pm-chip active" data-t="all" onclick="setTypeFilter('all')">All</button>
                    <button type="button" class="pm-chip" data-t="group" onclick="setTypeFilter('group')">Groups</button>
                    <button type="button" class="pm-chip" data-t="page" onclick="setTypeFilter('page')">Pages</button>
                    <button type="button" class="pm-chip" data-t="function" onclick="setTypeFilter('function')">Functions</button>
                </div>
                <p class="text-xs text-gray-400">Click a slug to copy it.</p>
            </div>
        </div>
        <div id="menuList"></div>
    </div>
</div>

<style>
    .pm-row { display: flex; align-items: center; gap: 10px; padding: 9px 20px 9px 16px; border-bottom: 1px solid #f3f4f6; transition: background .12s; }
    .pm-row:hover { background: #fafafa; }
    .pm-row.pm-group { background: #f9fafb; }
    .pm-row.pm-group:hover { background: #f3f4f6; }
    .pm-ico { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .pm-slug { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; color: #6b7280; background: #f3f4f6; padding: 3px 8px; border-radius: 6px; cursor: pointer; transition: all .12s; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border: 0; }
    .pm-slug:hover { background: #fee2e2; color: #991b1b; }
    .pm-chip { font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 999px; border: 1px solid #e5e7eb; color: #4b5563; background: #fff; cursor: pointer; transition: all .12s; }
    .pm-chip:hover { border-color: #fca5a5; color: #991b1b; }
    .pm-chip.active { background: #991b1b; border-color: #991b1b; color: #fff; }
</style>

<script>
let menusFlat  = [];
let menuTree   = [];
let expandedSet = new Set();
let typeFilter = 'all';

async function loadData() {
    const res = await fetch('/api/menus/all');
    menusFlat = (await res.json()).data || [];
    menuTree  = buildTree(menusFlat, null);
    menuTree.forEach(n => expandedSet.add(n.slug));
    updateStats();
    renderList();
}

function buildTree(items, parentId) {
    return items
        .filter(m => m.parent_id == parentId)
        .sort((a, b) => (a.order_seq || 0) - (b.order_seq || 0))
        .map(m => ({ ...m, children: buildTree(items, m.id) }));
}

function updateStats() {
    const count = t => menusFlat.filter(m => m.type === t).length;
    document.getElementById('statTotal').textContent = menusFlat.length;
    document.getElementById('statGroup').textContent = count('group');
    document.getElementById('statPage').textContent = count('page');
    document.getElementById('statFunction').textContent = count('function');
}

function descendantCount(node) {
    return (node.children || []).reduce((n, c) => n + 1 + descendantCount(c), 0);
}

function renderList() {
    const search  = document.getElementById('filterSearch').value.toLowerCase().trim();
    const container = document.getElementById('menuList');
    container.innerHTML = '';

    let hasAny = false;
    menuTree.forEach(node => {
        hasAny = renderNode(node, 0, container, search) || hasAny;
    });

    if (!hasAny) {
        container.innerHTML = `<div class="px-4 py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3"><i class="fas fa-search"></i></div>
            <p class="text-sm font-medium text-gray-700">No menus found</p>
            <p class="text-xs text-gray-400 mt-1">Try another keyword or filter.</p></div>`;
    }
}

function renderNode(node, depth, container, search) {
    if (!nodeOrDescendantMatches(node, search)) return false;

    const hasChildren = node.children && node.children.length > 0;
    const isExpanded  = expandedSet.has(node.slug);

    const typeStyle = {
        group:    { icon: 'fa-layer-group', box: 'bg-purple-50 text-purple-500', textCls: 'font-semibold text-gray-900', label: 'Group' },
        page:     { icon: 'fa-file-alt',    box: 'bg-blue-50 text-blue-500',     textCls: 'font-medium text-gray-800',   label: 'Page' },
        function: { icon: 'fa-bolt',        box: 'bg-orange-50 text-orange-500', textCls: 'text-gray-700',               label: 'Function' },
    }[node.type] || { icon: 'fa-circle', box: 'bg-gray-100 text-gray-400', textCls: 'text-gray-700', label: '' };

    const div = document.createElement('div');
    div.className = `pm-row ${node.type === 'group' ? 'pm-group' : ''}`;
    div.style.paddingLeft = `${16 + depth * 24}px`;

    div.innerHTML = `
        ${hasChildren
            ? `<button onclick="toggleNode('${escHtml(node.slug)}')" aria-label="Toggle"
                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-red-800 flex-shrink-0 focus:outline-none">
                   <i class="fas ${isExpanded ? 'fa-chevron-down' : 'fa-chevron-right'} text-[10px]"></i>
               </button>`
            : `<span class="w-5 flex-shrink-0"></span>`
        }
        <span class="pm-ico ${typeStyle.box}"><i class="fas ${typeStyle.icon}"></i></span>
        <span class="text-sm ${typeStyle.textCls} leading-tight min-w-0 truncate">${escHtml(node.name)}</span>
        ${hasChildren ? `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">${descendantCount(node)} items</span>` : ''}
        <span class="ml-auto flex items-center gap-3 flex-shrink-0">
            ${typeStyle.label ? `<span class="hidden sm:inline text-[10px] uppercase tracking-wide font-semibold text-gray-400">${typeStyle.label}</span>` : ''}
            <button type="button" class="pm-slug" title="Copy slug" data-slug="${escHtml(node.slug)}" onclick="copySlug(this)">${escHtml(node.slug)}</button>
        </span>
    `;

    container.appendChild(div);

    if (hasChildren && isExpanded) {
        node.children.forEach(child => renderNode(child, depth + 1, container, search));
    }

    return true;
}

// Tampil jika node atau salah satu turunannya cocok dengan pencarian dan filter tipe.
function nodeOrDescendantMatches(node, search) {
    const textOk = !search || node.name.toLowerCase().includes(search) || node.slug.toLowerCase().includes(search);
    const typeOk = typeFilter === 'all' || node.type === typeFilter;
    if (textOk && typeOk) return true;
    return (node.children || []).some(c => nodeOrDescendantMatches(c, search));
}

function toggleNode(slug) {
    expandedSet.has(slug) ? expandedSet.delete(slug) : expandedSet.add(slug);
    renderList();
}

function expandAll() {
    menusFlat.forEach(m => expandedSet.add(m.slug));
    renderList();
}

function collapseAll() {
    expandedSet.clear();
    renderList();
}

function applyFilter() {
    menusFlat.forEach(m => expandedSet.add(m.slug));
    renderList();
}

function setTypeFilter(t) {
    typeFilter = t;
    document.querySelectorAll('#typeChips .pm-chip').forEach(b => b.classList.toggle('active', b.dataset.t === t));
    if (t !== 'all') menusFlat.forEach(m => expandedSet.add(m.slug));
    renderList();
}

function resetFilter() {
    document.getElementById('filterSearch').value = '';
    typeFilter = 'all';
    document.querySelectorAll('#typeChips .pm-chip').forEach(b => b.classList.toggle('active', b.dataset.t === 'all'));
    expandedSet.clear();
    menuTree.forEach(n => expandedSet.add(n.slug));
    renderList();
}

async function copySlug(btn) {
    const slug = btn.dataset.slug;
    try {
        await navigator.clipboard.writeText(slug);
        if (typeof showToast === 'function') showToast(`Copied: ${slug}`, 'success');
    } catch (e) {
        if (typeof showToast === 'function') showToast('Could not copy. Select the text and copy it manually.', 'error');
    }
}

function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML;
}

loadData();
</script>
@endsection
