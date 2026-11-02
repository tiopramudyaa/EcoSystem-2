{{-- Quick search palette (Ctrl+K / "/"): employees, tickets, projects. Data comes from route global-search, already limited by menu access. --}}
<div id="gsOverlay" class="hidden fixed inset-0 z-[90] flex items-start justify-center pt-24 px-4" style="background-color: rgba(0,0,0,0.5);"
     role="dialog" aria-modal="true" aria-label="Quick search">
    <div class="w-full max-w-xl bg-white rounded-xl shadow-xl overflow-hidden" id="gsPanel">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200">
            <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
            <input id="gsInput" type="text" autocomplete="off" spellcheck="false"
                   class="flex-1 py-4 text-sm outline-none bg-transparent"
                   placeholder="Search employees, tickets, projects…"
                   role="combobox" aria-expanded="true" aria-controls="gsList" aria-autocomplete="list" aria-label="Search">
            <kbd class="text-[10px] text-gray-400 border border-gray-200 rounded px-1.5 py-0.5">Esc</kbd>
        </div>
        <ul id="gsList" role="listbox" aria-label="Search results" class="max-h-96 overflow-y-auto py-2"></ul>
        <p id="gsStatus" class="px-4 pb-3 text-xs text-gray-400" aria-live="polite">Type at least 2 characters.</p>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('gsOverlay');
    if (!overlay) return;
    var input = document.getElementById('gsInput'), list = document.getElementById('gsList'), status = document.getElementById('gsStatus');
    var url = @json(route('global-search')), timer = null, seq = 0, active = -1, opener = null;

    function options() { return list.querySelectorAll('[role=option]'); }
    function setActive(i) {
        var o = options();
        if (!o.length) { active = -1; input.removeAttribute('aria-activedescendant'); return; }
        active = (i + o.length) % o.length;
        o.forEach(function (el, n) { el.setAttribute('aria-selected', n === active ? 'true' : 'false'); el.classList.toggle('bg-red-50', n === active); });
        input.setAttribute('aria-activedescendant', o[active].id);
        o[active].scrollIntoView({ block: 'nearest' });
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function render(groups) {
        list.innerHTML = ''; var n = 0;
        groups.forEach(function (g) {
            var h = document.createElement('li'); h.setAttribute('role', 'presentation');
            h.className = 'px-4 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400';
            h.textContent = g.label; list.appendChild(h);
            g.items.forEach(function (it) {
                var li = document.createElement('li'); li.setAttribute('role', 'option'); li.id = 'gsOpt' + (n++);
                li.setAttribute('aria-selected', 'false'); li.dataset.url = it.url;
                li.className = 'flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-red-50';
                li.innerHTML = '<i class="fas ' + esc(g.icon) + ' w-4 text-gray-400" aria-hidden="true"></i>' +
                    '<span class="min-w-0"><span class="block text-sm text-gray-900 truncate">' + esc(it.title) + '</span>' +
                    '<span class="block text-xs text-gray-500 truncate">' + esc(it.hint) + '</span></span>';
                li.addEventListener('mousemove', function () { setActive(parseInt(li.id.slice(5), 10)); });
                li.addEventListener('click', function () { window.location.href = it.url; });
                list.appendChild(li);
            });
        });
        status.textContent = n ? n + ' result' + (n > 1 ? 's' : '') + '. Use ↑ ↓ and Enter.' : 'No matches.';
        setActive(n ? 0 : -1);
    }

    function search() {
        var q = input.value.trim(), mine = ++seq;
        if (q.length < 2) { list.innerHTML = ''; status.textContent = 'Type at least 2 characters.'; return; }
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) { if (mine === seq) render(d.groups || []); })
            .catch(function () { if (mine === seq) { list.innerHTML = ''; status.textContent = 'Search is unavailable right now.'; } });
    }

    window.openGlobalSearch = function () {
        opener = document.activeElement; overlay.classList.remove('hidden'); input.value = ''; list.innerHTML = '';
        status.textContent = 'Type at least 2 characters.'; input.focus();
    };
    function close() { overlay.classList.add('hidden'); if (opener && opener.focus) opener.focus(); }

    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 200); });
    overlay.addEventListener('mousedown', function (e) { if (!document.getElementById('gsPanel').contains(e.target)) close(); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
        else if (e.key === 'Enter') { var o = options()[active]; if (o) { e.preventDefault(); window.location.href = o.dataset.url; } }
        else if (e.key === 'Tab') { e.preventDefault(); } // single focus target: keep focus inside the dialog
    });
    document.addEventListener('keydown', function (e) {
        var open = !overlay.classList.contains('hidden');
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open ? close() : window.openGlobalSearch(); }
        else if (e.key === 'Escape' && open) { e.preventDefault(); close(); }
    });
})();
</script>
