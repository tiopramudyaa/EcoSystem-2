{{-- Global export field picker.
     Usage: ExportPicker.open({ type: 'ticket', title: 'Export Support Tickets', onConfirm: (cols) => {...} })
     `cols` = array nama kolom yang dipilih, atau null kalau semua kolom (export penuh seperti biasa).
     Template disimpan per karyawan lewat /export-templates/{type}.
     CSS memakai prefix .xp- dan berdiri sendiri (tidak bergantung pada kelas Tailwind yang ter-compile).
     z-index 9500: di atas header tabel sticky dan sidebar, tapi DI BAWAH panel dropdown kustom
     (select-enhance.js memindahkan panel ke <body> dengan z-index 9999) dan konfirmasi global (9990).
     Dropdown Template memakai gaya custom-dd aplikasi lewat select-enhance.js. --}}
<style>
    #exportPickerModal { display: none; position: fixed; inset: 0; z-index: 9500; align-items: center; justify-content: center; padding: 16px;
        background: rgba(17, 24, 39, .55); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    #exportPickerModal.xp-open { display: flex; }
    #exportPickerModal .xp-card { background: #fff; width: 100%; max-width: 640px; max-height: calc(100vh - 32px); display: flex; flex-direction: column;
        border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, .35); overflow: hidden; font-family: inherit; }
    #exportPickerModal .xp-head { display: flex; align-items: flex-start; gap: 12px; padding: 18px 22px; border-bottom: 1px solid #eef0f3; }
    #exportPickerModal .xp-icon { flex: none; width: 40px; height: 40px; border-radius: 10px; background: #e8f5ec; color: #1e7e46; display: flex; align-items: center; justify-content: center; }
    #exportPickerModal .xp-title { margin: 0; font-size: 15px; font-weight: 700; color: #111827; line-height: 1.3; }
    #exportPickerModal .xp-sub { margin: 2px 0 0; font-size: 12px; color: #6b7280; line-height: 1.4; }
    #exportPickerModal .xp-x { margin-left: auto; flex: none; border: 0; background: transparent; color: #9ca3af; cursor: pointer; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    #exportPickerModal .xp-x:hover { background: #f3f4f6; color: #374151; }
    #exportPickerModal .xp-body { padding: 18px 22px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px; }
    #exportPickerModal .xp-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
    #exportPickerModal .xp-row { display: flex; gap: 8px; align-items: center; }
    #exportPickerModal .xp-row .se-wrap { flex: 1; min-width: 0; }
    #exportPickerModal .se-btn { height: 40px; border-radius: 10px; font-size: 13px; color: #111827; padding: 0 12px; }
    #exportPickerModal .se-btn:focus { box-shadow: 0 0 0 3px rgba(185, 28, 28, .15); }
    #exportPickerModal .se-label.is-placeholder { color: #111827; }
    #exportPickerModal .xp-input, #exportPickerModal .xp-select { width: 100%; height: 40px; border: 1px solid #d1d5db; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #111827; background: #fff; outline: none; box-sizing: border-box; }
    #exportPickerModal .xp-input:focus, #exportPickerModal .xp-select:focus { border-color: #b91c1c; box-shadow: 0 0 0 3px rgba(185, 28, 28, .12); }
    #exportPickerModal .xp-btn { height: 40px; padding: 0 16px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; white-space: nowrap; transition: background .15s, opacity .15s; }
    #exportPickerModal .xp-btn:hover { background: #f9fafb; }
    #exportPickerModal .xp-btn-danger { color: #b91c1c; border-color: #fecaca; padding: 0 12px; }
    #exportPickerModal .xp-btn-danger:hover { background: #fef2f2; }
    #exportPickerModal .xp-btn-primary { border: 0; color: #fff; padding: 0 22px; background: linear-gradient(135deg, rgb(var(--primary-dark-rgb, 153, 27, 27)), rgb(var(--primary-rgb, 220, 38, 38))); }
    #exportPickerModal .xp-btn-primary:hover { background: linear-gradient(135deg, rgb(var(--primary-dark-rgb, 153, 27, 27)), rgb(var(--primary-rgb, 220, 38, 38))); }
    #exportPickerModal .xp-btn-primary:hover { opacity: .9; }
    #exportPickerModal .xp-btn-primary[disabled] { opacity: .45; cursor: not-allowed; }
    #exportPickerModal .xp-hidden { display: none !important; }
    #exportPickerModal .xp-fields-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
    #exportPickerModal .xp-pill { font-size: 11px; font-weight: 600; color: #b91c1c; background: #fef2f2; border-radius: 999px; padding: 3px 10px; }
    #exportPickerModal .xp-links { font-size: 12px; display: flex; gap: 12px; }
    #exportPickerModal .xp-link { border: 0; background: none; padding: 0; color: #2563eb; cursor: pointer; font-size: 12px; font-weight: 600; }
    #exportPickerModal .xp-link:hover { text-decoration: underline; }
    #exportPickerModal .xp-search { margin-bottom: 8px; }
    #exportPickerModal .xp-fields { border: 1px solid #e5e7eb; border-radius: 12px; padding: 8px; background: #fafafa; max-height: 280px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
    #exportPickerModal .xp-field { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 8px; background: #fff; border: 1px solid transparent; font-size: 13px; color: #374151; cursor: pointer; user-select: none; min-width: 0; }
    #exportPickerModal .xp-field:hover { border-color: #e5e7eb; }
    #exportPickerModal .xp-field.xp-on { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    #exportPickerModal .xp-field input { accent-color: #b91c1c; width: 15px; height: 15px; margin: 0; flex: none; cursor: pointer; }
    #exportPickerModal .xp-field span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #exportPickerModal .xp-empty { grid-column: 1 / -1; text-align: center; padding: 18px 0; font-size: 12px; color: #9ca3af; }
    #exportPickerModal .xp-save { background: #f9fafb; border: 1px solid #eef0f3; border-radius: 12px; padding: 14px; }
    #exportPickerModal .xp-scope { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
    #exportPickerModal .xp-scope label { display: flex; align-items: flex-start; gap: 9px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; background: #fff; cursor: pointer; min-width: 0; }
    #exportPickerModal .xp-scope label.xp-on { border-color: #b91c1c; background: #fef2f2; box-shadow: 0 0 0 3px rgba(185, 28, 28, .08); }
    #exportPickerModal .xp-scope input { accent-color: #b91c1c; margin: 3px 0 0; flex: none; cursor: pointer; }
    #exportPickerModal .xp-scope b { display: block; font-size: 13px; color: #111827; }
    #exportPickerModal .xp-scope small { display: block; font-size: 11px; color: #6b7280; line-height: 1.35; margin-top: 1px; }
    #exportPickerModal .xp-owner { margin: 8px 0 0; font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 7px 10px; line-height: 1.4; }
    #exportPickerModal .xp-hint { margin: 6px 0 0; font-size: 12px; color: #6b7280; line-height: 1.4; }
    #exportPickerModal .xp-msg { min-height: 16px; margin: 6px 0 0; font-size: 12px; font-weight: 600; }
    #exportPickerModal .xp-msg.ok { color: #15803d; }
    #exportPickerModal .xp-msg.err { color: #b91c1c; }
    #exportPickerModal .xp-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 22px; border-top: 1px solid #eef0f3; background: #fff; }
    #exportPickerModal .xp-foot-note { font-size: 12px; color: #6b7280; }
    #exportPickerModal .xp-foot-actions { display: flex; gap: 8px; }
    @media (max-width: 560px) {
        #exportPickerModal .xp-fields { grid-template-columns: 1fr; }
        #exportPickerModal .xp-scope { grid-template-columns: 1fr; }
        #exportPickerModal .xp-foot { flex-direction: column-reverse; align-items: stretch; }
        #exportPickerModal .xp-foot-actions { justify-content: flex-end; }
    }
</style>

<div id="exportPickerModal" role="dialog" aria-modal="true" aria-labelledby="exportPickerTitle">
    <div class="xp-card">
        <div class="xp-head">
            <div class="xp-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9.5 12.5l4 5M13.5 12.5l-4 5"/></svg>
            </div>
            <div>
                <h3 id="exportPickerTitle" class="xp-title">Export</h3>
                <p class="xp-sub">Choose the fields to include in the Excel file. The filters on the list still apply.</p>
            </div>
            <button type="button" id="exportPickerClose" class="xp-x" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="xp-body">
            <div>
                <label for="exportPickerTemplate" class="xp-label">Template</label>
                <div class="xp-row">
                    <select id="exportPickerTemplate" class="xp-select"></select>
                    <button type="button" id="exportPickerDelete" class="xp-btn xp-btn-danger xp-hidden" title="Delete this template">Delete</button>
                </div>
            </div>

            <div>
                <div class="xp-fields-head">
                    <span class="xp-label" style="margin:0">Fields <span id="exportPickerCount" class="xp-pill" style="margin-left:6px"></span></span>
                    <span class="xp-links">
                        <button type="button" id="exportPickerAll" class="xp-link">Select all</button>
                        <button type="button" id="exportPickerNone" class="xp-link">Unselect all</button>
                    </span>
                </div>
                <input id="exportPickerSearch" type="text" class="xp-input xp-search" placeholder="Search fields" autocomplete="off">
                <div id="exportPickerFields" class="xp-fields"></div>
            </div>

            <div class="xp-save">
                <label for="exportPickerName" class="xp-label">Save these fields as a template</label>
                <div class="xp-row">
                    <input id="exportPickerName" type="text" maxlength="80" placeholder="Template name" class="xp-input" autocomplete="off">
                    <button type="button" id="exportPickerSave" class="xp-btn">Save</button>
                </div>
                <div class="xp-scope" id="exportPickerScope" role="radiogroup" aria-label="Template visibility">
                    <label data-scope="personal">
                        <input type="radio" name="exportPickerScope" value="personal" checked>
                        <span><b>Personal</b><small>Only you can see and use it</small></span>
                    </label>
                    <label data-scope="global">
                        <input type="radio" name="exportPickerScope" value="global">
                        <span><b>Global</b><small>Everyone who can export this list can use it</small></span>
                    </label>
                </div>
                <p id="exportPickerOwner" class="xp-owner xp-hidden"></p>
                <p class="xp-hint">Next time, pick the template above and the fields are ticked for you. Only the person who created a template can change it.</p>
                <p id="exportPickerMsg" class="xp-msg"></p>
            </div>
        </div>

        <div class="xp-foot">
            <span id="exportPickerFootNote" class="xp-foot-note"></span>
            <div class="xp-foot-actions">
                <button type="button" id="exportPickerCancel" class="xp-btn">Cancel</button>
                <button type="button" id="exportPickerGo" class="xp-btn xp-btn-primary">Export</button>
            </div>
        </div>
    </div>
</div>

<script>
window.ExportPicker = (function () {
    const ALL = '';                       // value opsi "All fields"
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = id => document.getElementById(id);
    const lsKey = type => 'exportPicker.lastTemplate.' + type;

    let state = null; // { type, columns, templates, onConfirm }

    function lsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { v === null ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} }

    function say(text, ok) {
        const el = $('exportPickerMsg');
        el.textContent = text || '';
        el.className = 'xp-msg ' + (text ? (ok ? 'ok' : 'err') : '');
    }

    function show(on) {
        $('exportPickerModal').classList.toggle('xp-open', on);
        if (on) { try { $('exportPickerGo').focus(); } catch (e) {} }
    }

    function checkboxes() { return Array.from($('exportPickerFields').querySelectorAll('input[type=checkbox]')); }
    function checkedCols() { return checkboxes().filter(i => i.checked).map(i => i.value); }

    function setChecked(cols) {
        const set = new Set(cols);
        checkboxes().forEach(i => { i.checked = set.has(i.value); });
        refresh();
    }

    function refresh() {
        const n = checkedCols().length;
        const total = state ? state.columns.length : 0;
        $('exportPickerCount').textContent = n + ' of ' + total + ' selected';
        $('exportPickerFootNote').textContent = n === total && total > 0 ? 'Full export (all fields)' : n + ' field' + (n === 1 ? '' : 's') + ' will be exported';
        $('exportPickerGo').disabled = n === 0;
        $('exportPickerFields').querySelectorAll('.xp-field').forEach(l => {
            l.classList.toggle('xp-on', l.querySelector('input').checked);
        });
    }

    function applySearch() {
        const q = $('exportPickerSearch').value.trim().toLowerCase();
        let visible = 0;
        $('exportPickerFields').querySelectorAll('.xp-field').forEach(l => {
            const match = !q || l.textContent.toLowerCase().includes(q);
            l.classList.toggle('xp-hidden', !match);
            if (match) visible++;
        });
        let empty = $('exportPickerFields').querySelector('.xp-empty');
        if (!visible) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'xp-empty';
                empty.textContent = 'No field matches your search.';
                $('exportPickerFields').appendChild(empty);
            }
        } else if (empty) { empty.remove(); }
    }

    function renderFields() {
        const box = $('exportPickerFields');
        box.replaceChildren();
        state.columns.forEach(col => {
            const label = document.createElement('label');
            label.className = 'xp-field';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = col;
            cb.addEventListener('change', refresh);
            const span = document.createElement('span');
            span.textContent = col;
            span.title = col;
            label.append(cb, span);
            box.appendChild(label);
        });
        $('exportPickerSearch').value = '';
    }

    function renderTemplates(selectedId) {
        const sel = $('exportPickerTemplate');
        sel.replaceChildren();
        const base = document.createElement('option');
        base.value = ALL;
        base.textContent = 'All fields (default)';
        sel.appendChild(base);
        state.templates.forEach(t => {
            const o = document.createElement('option');
            o.value = String(t.id);
            o.textContent = templateLabel(t);
            sel.appendChild(o);
        });
        sel.value = selectedId !== undefined && selectedId !== null ? String(selectedId) : ALL;
        if (sel.value !== String(selectedId ?? ALL)) sel.value = ALL;
        applyTemplate();
    }

    function templateLabel(t) {
        if (t.scope !== 'global') return t.name + ' (Personal)';
        return t.name + (t.owner_name === 'You' ? ' (Global)' : ' (Global, by ' + t.owner_name + ')');
    }

    function scopeValue() {
        const r = document.querySelector('#exportPickerScope input:checked');
        return r ? r.value : 'personal';
    }

    function setScope(v) {
        document.querySelectorAll('#exportPickerScope input').forEach(i => { i.checked = i.value === v; });
        syncScope();
    }

    function syncScope() {
        document.querySelectorAll('#exportPickerScope label').forEach(l => {
            l.classList.toggle('xp-on', l.querySelector('input').checked);
        });
    }

    function currentTemplate() {
        const id = $('exportPickerTemplate').value;
        return id === ALL ? null : state.templates.find(t => String(t.id) === id) || null;
    }

    function applyTemplate() {
        const t = currentTemplate();
        setChecked(t ? t.columns : state.columns);
        $('exportPickerDelete').classList.toggle('xp-hidden', !(t && t.can_manage));
        $('exportPickerName').value = t ? t.name : '';
        setScope(t ? t.scope : 'personal');
        const note = $('exportPickerOwner');
        const foreign = t && t.owner_name !== 'You';
        note.classList.toggle('xp-hidden', !foreign);
        note.textContent = foreign ? 'Global template created by ' + t.owner_name + '. Only the owner can change it. To keep your own version, save it under another name.' : '';
        lsSet(lsKey(state.type), t ? String(t.id) : null);
        say('');
    }

    async function api(url, body) {
        const res = await fetch(url, {
            method: body ? 'POST' : 'GET',
            headers: Object.assign({ 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body ? { 'Content-Type': 'application/json' } : {}),
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined,
        });
        let data = {};
        try { data = await res.json(); } catch (e) {}
        if (!res.ok || data.success === false) {
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            throw new Error(first || data.message || 'Request failed');
        }
        return data;
    }

    async function save() {
        const name = $('exportPickerName').value.trim();
        const cols = checkedCols();
        if (!name) { say('Enter a template name.'); return; }
        if (!cols.length) { say('Select at least one field.'); return; }
        // Menimpa hanya template milik sendiri; nama dicocokkan tanpa membedakan huruf besar/kecil.
        const existing = state.templates.find(t => t.owner_name === 'You' && t.name.toLowerCase() === name.toLowerCase());
        try {
            const data = await api('/export-templates/' + encodeURIComponent(state.type), {
                name: existing ? existing.name : name, scope: scopeValue(), columns: cols,
            });
            const t = data.template;
            const i = state.templates.findIndex(x => x.id === t.id);
            if (i >= 0) state.templates[i] = t; else state.templates.push(t);
            // Personal dulu, lalu Global; di tiap kelompok urut nama.
            state.templates.sort((a, b) => (a.scope === 'global') - (b.scope === 'global') || a.name.localeCompare(b.name));
            renderTemplates(t.id);
            say('Template saved.', true);
        } catch (e) { say(e.message); }
    }

    async function remove() {
        const t = currentTemplate();
        if (!t) return;
        // Konfirmasi global punya z-index lebih rendah, jadi sembunyikan jendela ini sebentar.
        show(false);
        const ok = typeof showConfirm === 'function'
            ? await showConfirm('Delete ' + (t.scope === 'global' ? 'Global ' : '') + 'template "' + t.name + '"?' + (t.scope === 'global' ? ' Other people will no longer be able to use it.' : ''), 'Delete template', 'danger')
            : true;
        show(true);
        if (!ok) return;
        try {
            await api('/export-templates/' + t.id + '/delete', {});
            state.templates = state.templates.filter(x => x.id !== t.id);
            renderTemplates(ALL);
            say('Template deleted.', true);
        } catch (e) { say(e.message); }
    }

    function confirmExport() {
        const cols = checkedCols();
        if (!cols.length) { say('Select at least one field.'); return; }
        const cb = state.onConfirm;
        // Semua kolom terpilih = export penuh (null), seperti sebelum fitur ini.
        const result = cols.length === state.columns.length ? null : cols;
        show(false);
        if (typeof cb === 'function') cb(result);
    }

    async function open(opts) {
        state = { type: opts.type, columns: [], templates: [], onConfirm: opts.onConfirm };
        $('exportPickerTitle').textContent = opts.title || 'Export';
        $('exportPickerFields').replaceChildren();
        $('exportPickerCount').textContent = '';
        $('exportPickerFootNote').textContent = '';
        $('exportPickerGo').disabled = true;
        $('exportPickerSearch').value = '';
        say('Loading fields...', true);
        show(true);
        try {
            const data = await api('/export-templates/' + encodeURIComponent(opts.type));
            state.columns = data.columns;
            state.templates = data.templates;
            renderFields();
            renderTemplates(lsGet(lsKey(opts.type)));
            say('');
        } catch (e) {
            say('Could not load fields: ' + e.message);
        }
    }

    function init() {
        $('exportPickerClose').addEventListener('click', () => show(false));
        $('exportPickerCancel').addEventListener('click', () => show(false));
        $('exportPickerModal').addEventListener('mousedown', e => { if (e.target.id === 'exportPickerModal') show(false); });
        // Capture phase: Esc yang menutup dropdown template tidak ikut menutup jendela.
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape' || !$('exportPickerModal').classList.contains('xp-open')) return;
            if (document.querySelector('.se-wrap.is-open')) return;
            show(false);
        }, true);
        $('exportPickerTemplate').addEventListener('change', applyTemplate);
        $('exportPickerAll').addEventListener('click', () => setChecked(state.columns));
        $('exportPickerNone').addEventListener('click', () => setChecked([]));
        $('exportPickerSearch').addEventListener('input', applySearch);
        $('exportPickerSave').addEventListener('click', save);
        document.querySelectorAll('#exportPickerScope input').forEach(i => i.addEventListener('change', syncScope));
        $('exportPickerName').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save(); } });
        $('exportPickerDelete').addEventListener('click', remove);
        $('exportPickerGo').addEventListener('click', confirmExport);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

    return {
        open,
        /** Tambahkan pilihan kolom ke URLSearchParams. */
        appendTo(params, cols) { if (cols) cols.forEach(c => params.append('cols[]', c)); return params; },
    };
})();
</script>
