{{-- The add / edit dialog of Content and Settings, and the widgets its fields use. Expects what catalog.blade.php defines: $tab, $key, $section, $payload, $defaults, $categories.
     Every control is the app's own — no browser date / time / colour / number boxes and no plain textarea for an article:
       text · url      plain input                         select    the global dropdown (public/js/select-enhance.js)
       html            rich-text editor (bold, italic, underline, lists, headings, links…)
       date            calendar (flatpickr, "09 Oct 2026")   time      time field (public/js/time-field.js)
       number          − / + stepper                        bool      switch
       color           swatches + hex                       tone      named colour swatches (red · amber · violet · blue · green · gray)
     The dialog closes only through X / Cancel and asks first when something was changed. --}}
@php
    use App\Models\Compliance\ComplianceOption;
    use App\Support\Compliance\Tones;

    $fieldOptions = function ($f) use ($categories) {
        $o = $f['options'] ?? [];
        if ($o === 'categories') return $categories->all();
        if (is_string($o) && str_starts_with($o, 'list:')) return ComplianceOption::choices(substr($o, 5));
        return $o;
    };
    $wide = collect($section['fields'])->contains(fn ($f) => $f['type'] === 'html');
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 bg-white';
    $label = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
    $palette = ['#0F7B6C', '#2563EB', '#7C3AED', '#DB2777', '#DC2626', '#EA580C', '#D97706', '#65A30D', '#0891B2', '#475569', '#92400E', '#111827'];
@endphp

@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@include('hr-general.inventory.components.modal-helpers', ['dates' => true])
<style>
    /* switch — the table toggle and the dialog's yes/no fields */
    .cs-sw { position: relative; display: inline-block; flex-shrink: 0; width: 2.25rem; height: 1.25rem; border-radius: 9999px; background: #d1d5db; border: 0; cursor: pointer; transition: background .15s; vertical-align: middle; }
    .cs-sw-dot { position: absolute; top: .125rem; left: .125rem; width: 1rem; height: 1rem; border-radius: 9999px; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: transform .15s; }
    .cs-sw[aria-checked="true"], .cs-check:checked + .cs-sw { background: var(--primary-color, #0f7b6c); }
    .cs-sw[aria-checked="true"] .cs-sw-dot, .cs-check:checked + .cs-sw .cs-sw-dot { transform: translateX(1rem); }
    .cs-sw:disabled { opacity: .5; cursor: not-allowed; }
    .cs-sw.is-busy { opacity: .6; pointer-events: none; }
    .cs-check:focus-visible + .cs-sw, .cs-sw:focus-visible { outline: 2px solid var(--primary-color, #0f7b6c); outline-offset: 2px; }

    /* colour swatches (tone and hex colour) */
    .cs-swatch { width: 1.75rem; height: 1.75rem; border-radius: 9999px; border: 2px solid #fff; box-shadow: 0 0 0 1px #d1d5db; cursor: pointer; transition: transform .1s; }
    .cs-swatch:hover { transform: scale(1.1); }
    .cs-swatch[aria-pressed="true"] { box-shadow: 0 0 0 2px #111827; }
    .cs-tone { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .65rem .3rem .4rem; border: 1px solid #e5e7eb; border-radius: 9999px; font-size: .75rem; font-weight: 600; color: #374151; background: #fff; cursor: pointer; }
    .cs-tone:hover { background: #f9fafb; }
    .cs-tone[aria-pressed="true"] { border-color: #111827; background: #f3f4f6; }
    .cs-tone i { width: .9rem; height: .9rem; border-radius: 9999px; display: inline-block; }

    /* number stepper */
    .cs-step { display: inline-flex; align-items: stretch; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fff; overflow: hidden; }
    .cs-step:focus-within { box-shadow: 0 0 0 2px #c7d2fe; }
    .cs-step button { width: 2.25rem; color: #6b7280; background: #f9fafb; border: 0; font-size: .8rem; cursor: pointer; }
    .cs-step button:hover { background: #f3f4f6; color: #111827; }
    .cs-step input { width: 4rem; text-align: center; border: 0; border-left: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; font-size: .875rem; font-weight: 600; padding: .5rem .25rem; outline: none; font-variant-numeric: tabular-nums; }

    /* rich-text editor */
    .rt { border: 1px solid #e5e7eb; border-radius: .5rem; background: #fff; overflow: hidden; }
    .rt:focus-within { box-shadow: 0 0 0 2px #c7d2fe; border-color: #a5b4fc; }
    .rt.rt-invalid { border-color: #dc2626; box-shadow: 0 0 0 1px #dc2626; }
    .rt-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .125rem; padding: .35rem .4rem; background: #f9fafb; border-bottom: 1px solid #f3f4f6; }
    .rt-btn { min-width: 1.9rem; height: 1.9rem; padding: 0 .4rem; border: 0; border-radius: .375rem; background: transparent; color: #4b5563; font-size: .78rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .25rem; font-weight: 600; }
    .rt-btn:hover { background: #e5e7eb; color: #111827; }
    .rt-btn[aria-pressed="true"] { background: var(--primary-color, #0f7b6c); color: #fff; }
    .rt-sep { width: 1px; height: 1.25rem; background: #e5e7eb; margin: 0 .25rem; }
    .rt-link { display: none; align-items: center; gap: .4rem; padding: .4rem; background: #fffbeb; border-bottom: 1px solid #fde68a; }
    .rt-link.is-open { display: flex; }
    .rt-link input { flex: 1; border: 1px solid #e5e7eb; border-radius: .375rem; padding: .3rem .5rem; font-size: .8rem; }
    .rt-body { min-height: 15rem; max-height: 48vh; overflow-y: auto; padding: .85rem 1rem; font-size: .875rem; line-height: 1.7; color: #374151; outline: none; }
    .rt-body:empty:before { content: attr(data-placeholder); color: #9ca3af; }
    .rt-body h3 { font-size: 1.05rem; font-weight: 700; color: #111827; margin: 1rem 0 .4rem; }
    .rt-body h4 { font-size: .95rem; font-weight: 700; color: #111827; margin: .8rem 0 .3rem; }
    .rt-body p { margin: 0 0 .7rem; }
    .rt-body ul { list-style: disc; padding-left: 1.5rem; margin: 0 0 .7rem; }
    .rt-body ol { list-style: decimal; padding-left: 1.5rem; margin: 0 0 .7rem; }
    .rt-body blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; margin: 0 0 .7rem; }
    .rt-body a { color: #4f46e5; text-decoration: underline; }
    .rt-hint { padding: .3rem .75rem; font-size: .68rem; color: #9ca3af; border-top: 1px solid #f3f4f6; background: #fcfcfd; }
    .rt-err { color: #dc2626; font-size: .72rem; margin-top: .25rem; }
</style>
@endonce

<div id="complianceModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center p-4" style="display: none;">
    <form id="complianceForm" method="POST" class="bg-white rounded-xl shadow-xl w-full {{ $wide ? 'max-w-4xl' : 'max-w-2xl' }} max-h-[92vh] flex flex-col" data-unsaved-ignore novalidate>@csrf
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between">
            <h3 class="text-sm font-bold text-gray-800" id="complianceTitle">Add {{ $section['noun'] }}</h3>
            <button type="button" onclick="closeCompliance()" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-5 py-4 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
            @foreach($section['fields'] as $name => $f)
                @php $required = str_contains($f['rules'], 'required'); $id = "cf_{$name}"; @endphp
                <div class="{{ ($f['span'] ?? 1) === 2 || in_array($f['type'], ['tone', 'color'], true) ? 'md:col-span-2' : '' }}">
                    @if($f['type'] === 'bool')
                        <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer md:mt-5">
                            <input type="checkbox" name="{{ $name }}" value="1" data-f="{{ $name }}" class="cs-check sr-only">
                            <span class="cs-sw" aria-hidden="true"><span class="cs-sw-dot"></span></span>
                            {{ $f['label'] }}
                        </label>
                    @else
                        <label class="{{ $label }}" for="{{ $id }}">{{ $f['label'] }}@if($required) <span class="text-red-500">*</span>@endif</label>
                        @switch($f['type'])
                            @case('select')
                                <select id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" class="{{ $input }}">
                                    @if(!empty($f['blank']))<option value="">—</option>@endif
                                    @foreach($fieldOptions($f) as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach
                                </select>
                                @break
                            @case('html')
                                <textarea id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" data-rich @if($required) data-rich-required @endif class="hidden"></textarea>
                                @break
                            @case('textarea')
                                <textarea id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" rows="{{ $f['rows'] ?? 3 }}" class="{{ $input }}"></textarea>
                                @break
                            @case('number')
                                @php
                                    preg_match('/min:(\d+)/', $f['rules'], $mn); preg_match('/max:(\d+)/', $f['rules'], $mx);
                                @endphp
                                <div class="cs-step" data-stepper data-min="{{ $mn[1] ?? 0 }}" data-max="{{ $mx[1] ?? 9999 }}" data-step="{{ $f['step'] ?? 1 }}">
                                    <button type="button" data-dir="-1" aria-label="Decrease"><i class="fas fa-minus"></i></button>
                                    <input id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" type="text" inputmode="numeric" autocomplete="off">
                                    <button type="button" data-dir="1" aria-label="Increase"><i class="fas fa-plus"></i></button>
                                </div>
                                @break
                            @case('date')
                                <input id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" type="text" data-date placeholder="Select date" class="{{ $input }}">
                                @break
                            @case('time')
                                <input id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" type="text" data-time-field data-label="{{ $f['label'] }}" data-presets="{{ $name === 'end_time' ? '10:00,12:00,15:00,17:00' : '09:00,10:00,13:00,14:00' }}">
                                @break
                            @case('tone')
                                <input type="hidden" id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}">
                                <div class="flex flex-wrap gap-2" data-tone-field role="group" aria-label="{{ $f['label'] }}">
                                    @foreach(Tones::LABELS as $tone => $text)
                                        <button type="button" class="cs-tone" data-tone="{{ $tone }}" aria-pressed="false"><i style="background: {{ Tones::SWATCH[$tone] }}"></i>{{ $text }}</button>
                                    @endforeach
                                </div>
                                @break
                            @case('color')
                                <div class="flex flex-wrap items-center gap-2" data-color-field>
                                    @foreach($palette as $hex)<button type="button" class="cs-swatch" data-hex="{{ $hex }}" style="background: {{ $hex }}" aria-label="{{ $hex }}" aria-pressed="false"></button>@endforeach
                                    <input id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" type="text" maxlength="7" placeholder="#0F7B6C" autocomplete="off" class="w-28 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-mono uppercase focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                </div>
                                @break
                            @default
                                <input id="{{ $id }}" name="{{ $name }}" data-f="{{ $name }}" type="text" @if($f['type'] === 'url') inputmode="url" placeholder="https://" @endif class="{{ $input }}">
                        @endswitch
                    @endif
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" onclick="closeCompliance()" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
            <button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90"><i class="fas fa-floppy-disk mr-1"></i> Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script src="/js/time-field.js?v={{ @filemtime(public_path('js/time-field.js')) }}"></script>
<script>
(function () {
    const modal = document.getElementById('complianceModal'), form = document.getElementById('complianceForm');
    const DATA = @json($payload), DEFAULTS = @json($defaults);
    const store = @json(route("compliance.manage.{$tab}.store", $key)), base = store, NOUN = @json($section['noun']);
    const set = (el, v) => el.dispatchEvent(new CustomEvent('cs:set', { detail: v }));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // ── yes / no switch in the table: saves at once ─────────────────────────────────────────────
    document.querySelectorAll('[data-toggle]').forEach(btn => btn.addEventListener('click', async () => {
        const next = btn.getAttribute('aria-checked') !== 'true';
        const apply = on => {
            btn.setAttribute('aria-checked', on ? 'true' : 'false');
            if (btn.dataset.field === 'is_active') btn.closest('tr')?.classList.toggle('opacity-60', !on);
        };
        apply(next); btn.classList.add('is-busy');
        try {
            const body = new FormData(); body.append('field', btn.dataset.field); body.append('value', next ? '1' : '0');
            const res = await fetch(btn.dataset.url, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' } });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.message || 'It could not be saved.');
            apply(!!json.value);
        } catch (e) {
            apply(!next);
            (window.showNotification || alert)(e.message || 'It could not be saved.', 'error');
        } finally { btn.classList.remove('is-busy'); }
    }));

    // ── number stepper ──────────────────────────────────────────────────────────────────────────
    form.querySelectorAll('[data-stepper]').forEach(box => {
        const input = box.querySelector('input'), min = +box.dataset.min, max = +box.dataset.max, step = +box.dataset.step || 1;
        const clamp = n => Math.min(max, Math.max(min, n));
        input.addEventListener('input', () => { input.value = input.value.replace(/\D/g, ''); });
        input.addEventListener('blur', () => { if (input.value !== '') input.value = clamp(+input.value); });
        input.addEventListener('keydown', e => { if (e.key === 'ArrowUp' || e.key === 'ArrowDown') { e.preventDefault(); bump(e.key === 'ArrowUp' ? 1 : -1); } });
        const bump = dir => { input.value = clamp((input.value === '' ? min : +input.value) + dir * step); input.dispatchEvent(new Event('change', { bubbles: true })); };
        box.querySelectorAll('button').forEach(b => b.addEventListener('click', () => bump(+b.dataset.dir)));
    });

    // ── named colour (tone) and hex colour pickers ──────────────────────────────────────────────
    form.querySelectorAll('[data-tone-field]').forEach(group => {
        const hidden = group.previousElementSibling;
        const paint = () => group.querySelectorAll('[data-tone]').forEach(b => b.setAttribute('aria-pressed', b.dataset.tone === hidden.value ? 'true' : 'false'));
        group.addEventListener('click', e => { const b = e.target.closest('[data-tone]'); if (b) { hidden.value = b.dataset.tone; paint(); } });
        hidden.addEventListener('cs:set', e => { hidden.value = e.detail || ''; paint(); });
    });
    form.querySelectorAll('[data-color-field]').forEach(box => {
        const input = box.querySelector('input');
        const paint = () => box.querySelectorAll('[data-hex]').forEach(b => b.setAttribute('aria-pressed', b.dataset.hex === input.value.toUpperCase() ? 'true' : 'false'));
        box.addEventListener('click', e => { const b = e.target.closest('[data-hex]'); if (b) { input.value = b.dataset.hex; paint(); } });
        input.addEventListener('input', () => { input.value = ('#' + input.value.replace(/[^0-9a-f]/gi, '')).toUpperCase().slice(0, 7); paint(); });
        input.addEventListener('cs:set', e => { input.value = e.detail || ''; paint(); });
    });

    // ── rich-text editor ────────────────────────────────────────────────────────────────────────
    const BTN = [
        ['undo', 'rotate-left', 'Undo'], ['redo', 'rotate-right', 'Redo'], '|',
        ['h3', 'heading', 'Heading', 'Heading'], ['h4', 'heading', 'Sub-heading', 'Sub'], ['p', 'paragraph', 'Normal text'], '|',
        ['bold', 'bold', 'Bold (Ctrl+B)'], ['italic', 'italic', 'Italic (Ctrl+I)'], ['underline', 'underline', 'Underline (Ctrl+U)'], ['strikeThrough', 'strikethrough', 'Strikethrough'], '|',
        ['insertUnorderedList', 'list-ul', 'Bulleted list'], ['insertOrderedList', 'list-ol', 'Numbered list'], ['blockquote', 'quote-right', 'Quote'], '|',
        ['link', 'link', 'Add link'], ['unlink', 'link-slash', 'Remove link'], ['clear', 'eraser', 'Clear formatting'],
    ];
    form.querySelectorAll('textarea[data-rich]').forEach(area => {
        const wrap = document.createElement('div');
        wrap.className = 'rt';
        wrap.innerHTML = '<div class="rt-bar" role="toolbar" aria-label="Text formatting">' + BTN.map(b => b === '|' ? '<span class="rt-sep"></span>'
            : '<button type="button" class="rt-btn" data-cmd="' + b[0] + '" title="' + b[2] + '" aria-label="' + b[2] + '" aria-pressed="false"><i class="fas fa-' + b[1] + '"></i>' + (b[3] ? '<span>' + b[3] + '</span>' : '') + '</button>').join('') + '</div>'
            + '<div class="rt-link"><i class="fas fa-link text-gray-400 text-xs"></i><input type="text" inputmode="url" placeholder="https://…" aria-label="Link address"><button type="button" class="rt-btn" data-link-ok>Apply</button><button type="button" class="rt-btn" data-link-cancel>Cancel</button></div>'
            + '<div class="rt-body" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="Write the article here. Select text, then use the buttons above."></div>'
            + '<p class="rt-hint">Select text, then use the buttons. Pasted text comes in as plain text so the article stays tidy.</p>';
        area.after(wrap);
        const body = wrap.querySelector('.rt-body'), linkBar = wrap.querySelector('.rt-link'), linkInput = linkBar.querySelector('input');
        const err = document.createElement('p'); err.className = 'rt-err hidden'; err.textContent = 'Write the article text.'; wrap.after(err);
        let range = null;

        const sync = () => { area.value = body.innerHTML.replace(/<div>/g, '<p>').replace(/<\/div>/g, '</p>').replace(/^<br>$/, ''); wrap.classList.remove('rt-invalid'); err.classList.add('hidden'); };
        const load = html => {
            html = String(html || '').trim();
            body.innerHTML = html && !/<\/?(p|h3|h4|ul|ol|li|blockquote|table)\b/i.test(html) ? '<p>' + html.replace(/\n{2,}/g, '</p><p>').replace(/\n/g, '<br>') + '</p>' : html;
            sync(); refresh();
        };
        const exec = (cmd, val) => { body.focus(); document.execCommand(cmd, false, val); sync(); refresh(); };
        const block = tag => { const cur = String(document.queryCommandValue('formatBlock')).toLowerCase().replace(/[<>]/g, ''); exec('formatBlock', cur === tag ? 'p' : tag); };
        const refresh = () => wrap.querySelectorAll('.rt-btn[data-cmd]').forEach(b => {
            const c = b.dataset.cmd; let on = false;
            try {
                if (['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'].includes(c)) on = document.queryCommandState(c);
                else if (['h3', 'h4', 'p', 'blockquote'].includes(c)) on = String(document.queryCommandValue('formatBlock')).toLowerCase().replace(/[<>]/g, '') === c;
            } catch (e) { /* the command is not available */ }
            b.setAttribute('aria-pressed', on && document.activeElement === body ? 'true' : 'false');
        });

        wrap.querySelector('.rt-bar').addEventListener('mousedown', e => { if (e.target.closest('.rt-btn')) e.preventDefault(); });   // keep the selection in the text
        wrap.querySelector('.rt-bar').addEventListener('click', e => {
            const b = e.target.closest('.rt-btn'); if (!b) return;
            const c = b.dataset.cmd;
            if (['h3', 'h4', 'p', 'blockquote'].includes(c)) { body.focus(); block(c); }
            else if (c === 'link') { const s = getSelection(); range = s.rangeCount ? s.getRangeAt(0).cloneRange() : null; linkBar.classList.add('is-open'); linkInput.value = ''; linkInput.focus(); }
            else if (c === 'clear') { exec('removeFormat'); exec('formatBlock', 'p'); }
            else exec(c);
        });
        const closeLink = () => { linkBar.classList.remove('is-open'); body.focus(); };
        linkBar.querySelector('[data-link-cancel]').addEventListener('click', closeLink);
        const applyLink = () => {
            let url = linkInput.value.trim();
            if (url && !/^(https?:\/\/|mailto:)/i.test(url)) url = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url) ? 'mailto:' + url : 'https://' + url;
            body.focus();
            if (range) { const s = getSelection(); s.removeAllRanges(); s.addRange(range); }
            if (url && /^(https?:\/\/|mailto:)/i.test(url)) {
                if (getSelection().isCollapsed) document.execCommand('insertHTML', false, '<a href="' + esc(url) + '">' + esc(url) + '</a>');
                else document.execCommand('createLink', false, url);
            }
            linkBar.classList.remove('is-open'); sync();
        };
        linkBar.querySelector('[data-link-ok]').addEventListener('click', applyLink);
        linkInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applyLink(); } if (e.key === 'Escape') { e.stopPropagation(); closeLink(); } });

        body.addEventListener('input', sync);
        body.addEventListener('keyup', refresh); body.addEventListener('mouseup', refresh); body.addEventListener('focus', refresh); body.addEventListener('blur', () => setTimeout(refresh, 0));
        body.addEventListener('paste', e => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text/plain').replace(/\r/g, '').trim();
            if (!text) return;
            document.execCommand('insertHTML', false, text.split(/\n{2,}/).map(p => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>').join(''));
            sync();
        });
        area.addEventListener('cs:set', e => load(e.detail));
        area._richEmpty = () => body.textContent.trim() === '';
        area._richBody = body; area._richWrap = wrap; area._richErr = err;
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); document.execCommand('styleWithCSS', false, false); } catch (e) { /* ignore */ }
    });

    // ── fill the dialog ─────────────────────────────────────────────────────────────────────────
    function fill(el, v) {
        if (el.type === 'checkbox') { el.checked = !!v; return; }
        if (el.hasAttribute('data-date')) { el._flatpickr ? el._flatpickr.setDate(v || null, false) : (el.value = v || ''); return; }
        if (el.hasAttribute('data-time-field')) { window.setTimeField ? window.setTimeField(el, v || '') : (el.value = v || ''); return; }
        if (el.tagName === 'SELECT') {
            el.querySelectorAll('option[data-temp]').forEach(o => o.remove());
            const text = String(v ?? '');
            if (text !== '' && !Array.from(el.options).some(o => o.value === text)) {
                const extra = new Option(text + ' (switched off)', text); extra.dataset.temp = '1'; el.add(extra);   // keeps an old choice instead of silently changing it
            }
            el.value = text; el.dispatchEvent(new Event('change', { bubbles: true }));   // keeps the dropdown enhancer in step
            return;
        }
        el.value = v ?? '';
        set(el, v ?? '');
    }
    const snapshot = () => JSON.stringify(Array.from(new FormData(form).entries()).filter(([k]) => k !== '_token'));

    window.openCompliance = function (id) {
        window.invDates?.(form);
        const values = id ? DATA[id] : DEFAULTS;
        form.querySelectorAll('[data-f]').forEach(el => { fill(el, values[el.dataset.f]); set(el, values[el.dataset.f] ?? ''); });
        form.querySelectorAll('.rt-invalid').forEach(w => w.classList.remove('rt-invalid'));
        form.querySelectorAll('.rt-err').forEach(p => p.classList.add('hidden'));
        document.getElementById('complianceTitle').textContent = (id ? 'Edit ' : 'Add ') + NOUN;
        form.action = id ? base + '/' + id + '/update' : store;
        modal.style.display = 'flex'; modal.classList.remove('hidden');
        modal._snapshot = snapshot();
        setTimeout(() => form.querySelector('input[type="text"]:not([data-date]):not([data-time-field]):not([data-stepper] input)')?.focus(), 50);
    };
    window.closeCompliance = async function () {
        if (snapshot() !== modal._snapshot) {
            const message = 'You have changed this form and not saved it yet. If you close now, the changes are lost.';
            const leave = window.showConfirm ? await window.showConfirm(message, 'Discard changes?', 'danger', { okText: 'Discard', cancelText: 'Keep editing' }) : window.confirm(message);
            if (!leave) return;
        }
        modal.style.display = 'none'; modal.classList.add('hidden');
    };

    // ── submit: the text of an article is required but its editor is not a form control ─────────
    form.addEventListener('submit', e => {
        const missing = Array.from(form.querySelectorAll('textarea[data-rich-required]')).filter(a => a._richEmpty());
        missing.forEach(a => { a._richWrap.classList.add('rt-invalid'); a._richErr.classList.remove('hidden'); });
        if (missing.length) { e.preventDefault(); missing[0]._richBody.focus(); return; }
        for (const el of form.querySelectorAll('[data-f]')) {
            const req = el.closest('div')?.querySelector('label span.text-red-500');
            if (req && !el.matches('textarea[data-rich]') && el.type !== 'checkbox' && String(el.value).trim() === '') {
                e.preventDefault(); el.focus?.(); (window.showNotification || alert)('Fill in the required fields (marked *).', 'error'); return;
            }
        }
    });
})();
</script>
@endpush
