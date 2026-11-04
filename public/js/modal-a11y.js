/**
 * Keyboard + screen-reader behaviour for the app's hand-built modals, without touching each modal's own open / close code.
 *
 * A modal here is a full-screen overlay (`.fixed.inset-0`) that shows and hides by toggling the `hidden` class. When one opens we:
 *  - mark it role="dialog" aria-modal="true", named by its first heading;
 *  - move focus inside, keep Tab / Shift+Tab inside, and give focus back to the opener when it closes;
 *
 * It never closes a modal on Escape or outside click: by the app's convention a modal closes only through its X / Cancel button.
 * Modals that already carry role="dialog" keep their attributes; only the focus handling is added.
 */
(function () {
    var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]):not([type=hidden]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
    var stack = []; // open modals, topmost last

    function isOverlay(el) {
        return el.nodeType === 1 && el.classList.contains('fixed') && el.classList.contains('inset-0') && el.id !== 'gsOverlay';
    }
    function visible(el) { return el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0; }
    function focusables(modal) { return Array.prototype.filter.call(modal.querySelectorAll(FOCUSABLE), visible); }

    function opened(modal) {
        if (stack.indexOf(modal) > -1) return;
        var items = focusables(modal);
        if (!items.length) return; // not a dialog (e.g. a plain backdrop)
        if (!modal.hasAttribute('role')) modal.setAttribute('role', 'dialog');
        if (!modal.hasAttribute('aria-modal')) modal.setAttribute('aria-modal', 'true');
        if (!modal.hasAttribute('aria-label') && !modal.hasAttribute('aria-labelledby')) {
            var h = modal.querySelector('h1,h2,h3,h4');
            if (h) { if (!h.id) h.id = 'modal-title-' + Math.random().toString(36).slice(2, 8); modal.setAttribute('aria-labelledby', h.id); }
        }
        modal._a11yOpener = document.activeElement;
        stack.push(modal);
        // Prefer the first field over a close "×" button.
        var first = items.filter(function (el) { return /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName); })[0] || items[0];
        requestAnimationFrame(function () { if (!modal.contains(document.activeElement)) first.focus(); });
    }

    function closed(modal) {
        var at = stack.indexOf(modal);
        if (at < 0) return;
        stack.splice(at, 1);
        var back = modal._a11yOpener;
        modal._a11yOpener = null;
        var stillValid = back && document.contains(back) && back.focus;
        var top = stack[stack.length - 1];
        if (stillValid && (!top || top.contains(back))) back.focus();
    }

    function sync(el) {
        if (!isOverlay(el)) return;
        if (el.classList.contains('hidden') || !visible(el)) closed(el); else opened(el);
    }

    new MutationObserver(function (list) {
        list.forEach(function (m) {
            if (m.type === 'attributes') sync(m.target);
            else m.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                sync(n);
                if (n.querySelectorAll) n.querySelectorAll('.fixed.inset-0').forEach(sync);
            });
        });
    }).observe(document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style'] });

    document.addEventListener('keydown', function (e) {
        var modal = stack[stack.length - 1];
        if (!modal) return;
        if (!document.contains(modal) || !visible(modal)) { closed(modal); return; }

        if (e.key !== 'Tab') return;
        // A dropdown panel is moved to <body> while open; its own keyboard handling owns Tab there.
        if (document.activeElement && document.activeElement.closest && document.activeElement.closest('.se-panel,.custom-dd-panel')) return;
        var items = focusables(modal);
        if (!items.length) return;
        var firstEl = items[0], lastEl = items[items.length - 1], active = document.activeElement;
        if (!modal.contains(active)) { e.preventDefault(); firstEl.focus(); }
        else if (e.shiftKey && active === firstEl) { e.preventDefault(); lastEl.focus(); }
        else if (!e.shiftKey && active === lastEl) { e.preventDefault(); firstEl.focus(); }
    });
})();
