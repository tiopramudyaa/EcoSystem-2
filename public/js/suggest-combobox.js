/**
 * suggest-combobox.js — kolom isian dengan daftar saran yang bisa dicari (pengganti <datalist>).
 *
 * Pakai:
 *   <input type="text" name="customer_name" data-suggest-source="#customerData" data-suggest-label="customer">
 *   <script type="application/json" id="customerData">["PT A","PT B"]</script>
 *
 * - Isian tetap BEBAS: nilai yang tidak ada di daftar tetap boleh disimpan (ditandai "new").
 * - Mode KETAT (data-suggest-strict="#hiddenId"): sumber berisi [{"value":1,"label":"Nama"}], pilihan disimpan ke input tersembunyi
 *   (mis. employee_id); tidak ada nilai baru, teks yang tidak cocok dikembalikan ke pilihan terakhir.
 * - Panel dipasang di <body> (position: fixed) agar tidak terpotong kartu/overflow, membuka ke atas bila ruang bawah sempit.
 * - Keyboard: ↑ ↓ memilih, Enter menetapkan, Esc / Tab menutup. Tampilan mengikuti panel dropdown proyek (select-enhance.js).
 */
(function () {
    'use strict';

    var MAX_SHOWN = 60;
    var styled = false;

    function injectStyle() {
        if (styled) return;
        styled = true;
        var css = [
            '.sc-wrap{position:relative}',
            '.sc-wrap>input{padding-right:2.25rem}',
            '.sc-toggle{position:absolute;right:.25rem;top:50%;transform:translateY(-50%);width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;border:0;background:transparent;color:#9ca3af;border-radius:.375rem;cursor:pointer}',
            '.sc-toggle:hover{color:#4b5563;background:#f3f4f6}',
            '.sc-toggle svg{width:1rem;height:1rem;transition:transform .15s ease}',
            '.sc-wrap.is-open .sc-toggle svg{transform:rotate(180deg)}',
            '.sc-panel{position:fixed;z-index:9999;display:none;flex-direction:column;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;box-shadow:0 12px 32px -8px rgba(15,23,42,.25),0 4px 10px -4px rgba(15,23,42,.12);overflow:hidden;font-size:.875rem}',
            '.sc-panel.is-open{display:flex}',
            '.sc-head{padding:.5rem .875rem;font-size:.6875rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#9ca3af;background:#f9fafb;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;gap:.5rem}',
            '.sc-list{overflow-y:auto;overscroll-behavior:contain;max-height:16rem;padding:.25rem 0;scrollbar-width:thin;scrollbar-color:#d1d5db transparent}',
            '.sc-item{display:flex;align-items:center;gap:.625rem;padding:.5rem .875rem;color:#4b5563;cursor:pointer;line-height:1.25rem}',
            '.sc-item .sc-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}',
            '.sc-item.is-active{background:#f3f4f6;color:#111827}',
            '.sc-item mark{background:transparent;font-weight:700;color:inherit}',
            '.sc-item .sc-check{width:1rem;height:1rem;flex:none}',
            '.sc-item .sc-badge{flex:none;font-size:.6875rem;font-weight:600;padding:.0625rem .4375rem;border-radius:9999px;background:#ecfdf5;color:#047857}',
            '.sc-new{border-top:1px solid #f3f4f6}',
            '.sc-empty{padding:.875rem;text-align:center;color:#9ca3af}',
            '.sc-foot{padding:.375rem .875rem;font-size:.6875rem;color:#9ca3af;background:#f9fafb;border-top:1px solid #f3f4f6}'
        ].join('');
        var el = document.createElement('style');
        el.textContent = css;
        document.head.appendChild(el);
    }

    var CHEVRON = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>';
    var CHECK = '<svg class="sc-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>';
    var PLUS = '<svg class="sc-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>';

    function init(input) {
        var src = document.querySelector(input.dataset.suggestSource);
        var items = [];
        try { items = JSON.parse(src ? src.textContent : '[]') || []; } catch (e) { items = []; }
        var hidden = input.dataset.suggestStrict ? document.querySelector(input.dataset.suggestStrict) : null;
        var strict = !!hidden;
        items = items.map(function (s) { return (s && typeof s === 'object') ? { v: String(s.value), l: String(s.label) } : { v: String(s), l: String(s) }; }).filter(function (o) { return o.l; });
        if (!items.length) return;
        var selected = null;
        if (strict) { items.forEach(function (o) { if (o.v === String(hidden.value)) selected = o; }); if (selected) input.value = selected.l; }

        injectStyle();
        var noun = input.dataset.suggestLabel || 'item';
        var plural = input.dataset.suggestPlural || (noun + 's');
        var uid = 'sc' + Math.random().toString(36).slice(2, 8);

        var wrap = document.createElement('div');
        wrap.className = 'sc-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', uid);
        input.removeAttribute('list');

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'sc-toggle';
        toggle.tabIndex = -1;
        toggle.setAttribute('aria-label', 'Show ' + noun + ' list');
        toggle.innerHTML = CHEVRON;
        wrap.appendChild(toggle);

        var panel = document.createElement('div');
        panel.className = 'sc-panel';
        var head = document.createElement('div'); head.className = 'sc-head';
        var list = document.createElement('div'); list.className = 'sc-list'; list.id = uid; list.setAttribute('role', 'listbox');
        var foot = document.createElement('div'); foot.className = 'sc-foot';
        panel.appendChild(head); panel.appendChild(list); panel.appendChild(foot);
        document.body.appendChild(panel);

        var active = -1, rows = [], open = false, browsing = false;

        function place() {
            var r = input.getBoundingClientRect();
            panel.style.width = r.width + 'px';
            panel.style.left = r.left + 'px';
            var below = window.innerHeight - r.bottom, wanted = Math.min(panel.offsetHeight || 300, 340);
            if (below < wanted + 12 && r.top > below) {
                panel.style.top = 'auto'; panel.style.bottom = (window.innerHeight - r.top + 6) + 'px';
                list.style.maxHeight = Math.max(120, Math.min(256, r.top - 90)) + 'px';
            } else {
                panel.style.bottom = 'auto'; panel.style.top = (r.bottom + 6) + 'px';
                list.style.maxHeight = Math.max(120, Math.min(256, below - 90)) + 'px';
            }
        }

        function highlight(el, text, q) {
            var i = q ? text.toLowerCase().indexOf(q) : -1;
            if (i < 0) { el.textContent = text; return; }
            el.appendChild(document.createTextNode(text.slice(0, i)));
            var m = document.createElement('mark'); m.className = 'primary-text'; m.textContent = text.slice(i, i + q.length);
            el.appendChild(m);
            el.appendChild(document.createTextNode(text.slice(i + q.length)));
        }

        function choose(value) {
            if (strict) { var hit = items.filter(function (o) { return o.l === value; })[0]; if (hit) { selected = hit; hidden.value = hit.v; hidden.dispatchEvent(new Event('change', { bubbles: true })); } }
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        }

        function setActive(i, scroll) {
            if (!rows.length) { active = -1; return; }
            active = (i + rows.length) % rows.length;
            rows.forEach(function (r, k) { r.el.classList.toggle('is-active', k === active); });
            input.setAttribute('aria-activedescendant', rows[active].el.id);
            if (scroll !== false) rows[active].el.scrollIntoView({ block: 'nearest' });
        }

        function render() {
            var raw = browsing ? '' : input.value.trim(), q = raw.toLowerCase();
            var starts = [], contains = [];
            items.forEach(function (o) {
                var l = o.l.toLowerCase();
                if (!q) starts.push(o);
                else if (l.indexOf(q) === 0) starts.push(o);
                else if (l.indexOf(q) > 0) contains.push(o);
            });
            var found = starts.concat(contains), shown = found.slice(0, MAX_SHOWN);
            var exact = items.some(function (o) { return o.l.toLowerCase() === q; });

            list.textContent = ''; rows = [];
            shown.forEach(function (o, k) {
                var s = o.l;
                var el = document.createElement('div');
                el.className = 'sc-item'; el.id = uid + '-' + k; el.setAttribute('role', 'option');
                var t = document.createElement('span'); t.className = 'sc-text'; t.title = s; highlight(t, s, q);
                el.appendChild(t);
                if (strict ? (selected && selected.v === o.v) : s.toLowerCase() === q) { var b = document.createElement('span'); b.className = 'sc-badge'; b.textContent = 'Selected'; el.appendChild(b); }
                el.addEventListener('mousedown', function (e) { e.preventDefault(); choose(s); });
                el.addEventListener('mousemove', function () { if (active !== k) setActive(k, false); });
                list.appendChild(el); rows.push({ el: el, value: s });
            });
            if (raw && !exact && !strict) {
                var n = document.createElement('div');
                n.className = 'sc-item sc-new'; n.id = uid + '-new'; n.setAttribute('role', 'option');
                n.insertAdjacentHTML('beforeend', PLUS);
                var nt = document.createElement('span'); nt.className = 'sc-text'; nt.textContent = 'Use “' + raw + '” as a new ' + noun;
                n.appendChild(nt);
                n.addEventListener('mousedown', function (e) { e.preventDefault(); choose(raw); });
                list.appendChild(n); rows.push({ el: n, value: raw });
            }
            if (!shown.length) { var em = document.createElement('div'); em.className = 'sc-empty'; em.textContent = raw ? 'No ' + noun + ' matches “' + raw + '”' : 'No ' + plural + ' yet'; list.appendChild(em); }

            head.textContent = '';
            var l1 = document.createElement('span'); l1.textContent = plural.charAt(0).toUpperCase() + plural.slice(1);
            var l2 = document.createElement('span'); l2.textContent = q ? found.length + ' of ' + items.length : items.length + ' total';
            head.appendChild(l1); head.appendChild(l2);
            foot.textContent = found.length > MAX_SHOWN ? 'Showing the first ' + MAX_SHOWN + ' — keep typing to narrow down' : '↑ ↓ to move · Enter to select · Esc to close';
            foot.style.display = '';

            active = -1;
            var selIdx = strict && selected ? shown.indexOf(selected) : -1;
            if (rows.length) setActive(selIdx >= 0 ? selIdx : (raw && !shown.length ? rows.length - 1 : 0), selIdx >= 0);
            if (open) place();
        }

        function show(typing) { if (open) return; open = true; browsing = typing !== true; render(); panel.classList.add('is-open'); wrap.classList.add('is-open'); input.setAttribute('aria-expanded', 'true'); place(); }
        function close() {
            if (strict && selected && input.value !== selected.l) input.value = selected.l; // teks tak cocok → kembali ke pilihan terakhir
            if (!open) return; open = false; panel.classList.remove('is-open'); wrap.classList.remove('is-open'); input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); }

        input.addEventListener('focus', show);
        input.addEventListener('click', show);
        input.addEventListener('input', function () { browsing = false; if (!open) show(true); else render(); });
        input.addEventListener('focus', function () { if (strict) input.select(); });
        input.addEventListener('blur', function () { setTimeout(function () { if (strict && !open && selected && input.value !== selected.l) input.value = selected.l; close(); }, 120); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); if (!open) show(); else setActive(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); if (!open) show(); else setActive(active - 1); }
            else if (e.key === 'Enter' && open && active >= 0) { e.preventDefault(); choose(rows[active].value); }
            else if (e.key === 'Escape' && open) { e.preventDefault(); close(); }
            else if (e.key === 'Tab') close();
        });
        toggle.addEventListener('mousedown', function (e) { e.preventDefault(); if (open) close(); else { input.focus(); show(); } });
        window.addEventListener('resize', function () { if (open) place(); });
        window.addEventListener('scroll', function (e) { if (open && !panel.contains(e.target)) place(); }, true);
    }

    function boot() { document.querySelectorAll('input[data-suggest-source]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
