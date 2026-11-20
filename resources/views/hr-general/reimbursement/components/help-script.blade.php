{{--
    Perilaku ikon bantuan (?) di Reimbursement Settings. Sertakan SEKALI per halaman.
    - Satu popover bersama (position: fixed), diisi dengan menyalin isi tersembunyi milik ikon (cloneNode, tanpa innerHTML).
    - Tutup: Esc, klik di luar, tombol tutup. Fokus kembali ke ikon.
    - "Use recommended" hanya mengisi input pada form yang ditunjuk; tidak pernah menyimpan.
    - Layar sempit: menjadi lembar bawah.
--}}
@push('scripts')
<script>
(function () {
    if (window.__ecHelpTips) return;
    window.__ecHelpTips = true;

    var pop = document.createElement('div');
    pop.id = 'help-popover';
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-modal', 'false');
    pop.hidden = true;
    pop.className = 'print:hidden fixed z-[60] bg-white border border-gray-200 rounded-xl shadow-2xl p-4 text-left overflow-y-auto';
    pop.style.maxHeight = '80vh';
    pop.style.position = 'fixed';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Close help');
    closeBtn.className = 'absolute top-2 right-2 w-7 h-7 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100';
    closeBtn.textContent = '×';
    var content = document.createElement('div');
    content.className = 'pr-6';
    pop.appendChild(closeBtn);
    pop.appendChild(content);
    document.body.appendChild(pop);

    var current = null;   // the button that opened the popover

    function place() {
        if (!current) return;
        var vw = window.innerWidth, vh = window.innerHeight;

        if (vw < 640) {   // bottom sheet
            pop.style.left = '8px'; pop.style.right = '8px'; pop.style.width = 'auto';
            pop.style.top = 'auto'; pop.style.bottom = '8px';
            return;
        }

        var r = current.getBoundingClientRect();
        var w = Math.min(380, vw - 24);
        pop.style.width = w + 'px'; pop.style.right = 'auto'; pop.style.bottom = 'auto';
        pop.style.left = Math.max(12, Math.min(r.left, vw - w - 12)) + 'px';

        var h = pop.offsetHeight;
        var below = r.bottom + 8;
        pop.style.top = (below + h > vh - 12 && r.top - 8 - h > 12 ? r.top - 8 - h : Math.max(12, Math.min(below, vh - h - 12))) + 'px';
    }

    function close(returnFocus) {
        if (pop.hidden) return;
        pop.hidden = true;
        content.textContent = '';
        if (current) {
            current.setAttribute('aria-expanded', 'false');
            if (returnFocus) current.focus();
        }
        current = null;
    }

    function open(button) {
        var body = document.getElementById(button.dataset.helpOpen);
        if (!body) return;
        close(false);

        Array.prototype.forEach.call(body.childNodes, function (n) { content.appendChild(n.cloneNode(true)); });
        var title = content.querySelector('[data-help-title]');
        if (title) { title.id = 'help-popover-title'; pop.setAttribute('aria-labelledby', 'help-popover-title'); }

        current = button;
        current._body = body;
        button.setAttribute('aria-expanded', 'true');
        pop.hidden = false;
        place();
        closeBtn.focus({ preventScroll: true });
    }

    function flash(el) {
        el.classList.add('ring-2', 'ring-green-500');
        setTimeout(function () { el.classList.remove('ring-2', 'ring-green-500'); }, 2500);
    }

    function apply(body) {
        var values;
        try { values = JSON.parse(body.dataset.helpValues || '{}'); } catch (e) { values = {}; }
        var form = body.dataset.helpForm ? document.getElementById(body.dataset.helpForm) : null;
        if (!form) return;

        var changed = 0;
        Object.keys(values).forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el || el.disabled) return;

            if (el.type === 'checkbox') {
                if (el.checked === !!values[name]) return;
                el.checked = !!values[name];
            } else {
                if (String(el.value) === String(values[name])) return;
                el.value = String(values[name]);
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            flash(el.closest('div, label') || el);
            changed++;
        });

        var message = changed
            ? 'Recommended values filled in. Review them, then click the Save button to apply.'
            : 'Already set to the recommended values. Nothing to change.';
        if (typeof window.showToast === 'function') window.showToast(message, 'info', 4000);
    }

    document.addEventListener('click', function (event) {
        var opener = event.target.closest && event.target.closest('[data-help-open]');
        if (opener) {
            event.preventDefault();
            if (current === opener) { close(true); } else { open(opener); }
            return;
        }

        if (!pop.hidden && pop.contains(event.target)) {
            if (event.target === closeBtn) { close(true); return; }
            var applyBtn = event.target.closest('[data-help-apply]');
            if (applyBtn && current && current._body) { apply(current._body); close(false); }
            return;
        }

        if (!pop.hidden) close(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !pop.hidden) { event.preventDefault(); close(true); }
    });

    var raf = 0;
    function schedule() { if (raf || pop.hidden) return; raf = requestAnimationFrame(function () { raf = 0; place(); }); }
    window.addEventListener('resize', schedule);
    window.addEventListener('scroll', schedule, true);

    // First-visit banner: dismissible, remembered per browser (storage may be unavailable).
    var banner = document.getElementById('helpBanner');
    if (banner) {
        var dismissed = false;
        try { dismissed = localStorage.getItem('rb-help-banner-v1') === '1'; } catch (e) {}
        banner.hidden = dismissed;
        var dismiss = banner.querySelector('[data-help-banner-close]');
        if (dismiss) dismiss.addEventListener('click', function () {
            banner.hidden = true;
            try { localStorage.setItem('rb-help-banner-v1', '1'); } catch (e) {}
        });
    }
})();
</script>
@endpush
