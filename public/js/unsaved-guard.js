/**
 * Unsaved-changes guard + optional draft autosave for long forms. Opt in per form:
 *
 *   <form data-unsaved-guard>                       warn before leaving the page with edits
 *   <form data-unsaved-guard data-autosave="key">   also keep a local draft and offer to restore it
 *
 * Passwords, files and hidden / token fields are never stored. The draft lives in this browser only and is dropped on submit.
 * Modals can opt in the same way: closeModalGuarded(modalEl, closeFn) asks before closing a modal whose form has edits.
 */
(function () {
    var SKIP = { password: 1, file: 1, hidden: 1, submit: 1, button: 1, reset: 1 };
    var TTL = 7 * 24 * 3600 * 1000;

    function fields(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            return el.name && !SKIP[el.type] && el.name !== '_token' && !el.disabled;
        });
    }
    function snapshot(form) {
        var data = {};
        fields(form).forEach(function (el) {
            if (el.type === 'checkbox') { (data[el.name] = data[el.name] || []); if (el.checked) data[el.name].push(el.value); }
            else if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; }
            else if (el.multiple) { data[el.name] = Array.prototype.map.call(el.selectedOptions, function (o) { return o.value; }); }
            else data[el.name] = el.value;
        });
        return data;
    }
    function apply(form, data) {
        fields(form).forEach(function (el) {
            if (!(el.name in data)) return;
            var v = data[el.name];
            if (el.type === 'checkbox') el.checked = Array.isArray(v) && v.indexOf(el.value) > -1;
            else if (el.type === 'radio') el.checked = v === el.value;
            else if (el.multiple) Array.prototype.forEach.call(el.options, function (o) { o.selected = v.indexOf(o.value) > -1; });
            else el.value = v;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
    function store(op, key, val) {
        try {
            if (op === 'get') return window.localStorage.getItem(key);
            if (op === 'set') window.localStorage.setItem(key, val);
            else window.localStorage.removeItem(key);
        } catch (e) { /* storage blocked: guard still works, draft does not */ }
        return null;
    }

    var guarded = [];

    function init(form) {
        var key = form.dataset.autosave ? 'draft:' + location.pathname + ':' + form.dataset.autosave : null;
        var baseline = JSON.stringify(snapshot(form)), submitting = false, timer = null;
        var entry = { form: form, dirty: function () { return !submitting && JSON.stringify(snapshot(form)) !== baseline; } };
        guarded.push(entry);

        function save() {
            if (!key || !entry.dirty()) return;
            store('set', key, JSON.stringify({ at: Date.now(), data: snapshot(form) }));
            status('Draft saved ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
        }
        function status(text) { var s = form.querySelector('[data-autosave-status]'); if (s) s.textContent = text; }

        if (key) {
            var raw = store('get', key), saved = null;
            try { saved = raw && JSON.parse(raw); } catch (e) { /* corrupt draft */ }
            if (saved && Date.now() - saved.at < TTL && JSON.stringify(saved.data) !== baseline) {
                var bar = document.createElement('div');
                bar.setAttribute('role', 'status');
                bar.className = 'mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900';
                bar.innerHTML = '<span class="flex-1">An unsaved draft from ' + new Date(saved.at).toLocaleString() + ' was found.</span>' +
                    '<button type="button" data-a="restore" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white">Restore draft</button>' +
                    '<button type="button" data-a="discard" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-amber-300">Discard</button>';
                bar.addEventListener('click', function (e) {
                    var a = e.target.dataset && e.target.dataset.a;
                    if (a === 'restore') apply(form, saved.data);
                    if (a) { if (a === 'discard') store('del', key); bar.remove(); }
                });
                form.insertBefore(bar, form.firstChild);
            }
            var queue = function () { clearTimeout(timer); timer = setTimeout(save, 1500); };
            form.addEventListener('input', queue);
            form.addEventListener('change', queue);
        }

        form.addEventListener('submit', function () {
            submitting = true;
            if (key) store('del', key);
            // A submit stopped by validation / ajax must re-arm the guard.
            setTimeout(function () { submitting = false; }, 4000);
        });
        form.addEventListener('reset', function () { if (key) store('del', key); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-unsaved-guard]').forEach(init);
    });

    window.addEventListener('beforeunload', function (e) {
        if (guarded.some(function (g) { return g.dirty(); })) { e.preventDefault(); e.returnValue = ''; }
    });

    /** Ask before closing a modal holding edits to a guarded form; runs closeFn when confirmed or when nothing changed. */
    window.closeModalGuarded = function (modal, closeFn) {
        var dirty = guarded.some(function (g) { return modal && modal.contains(g.form) && g.dirty(); });
        if (!dirty) return closeFn();
        var msg = 'You have unsaved changes. Close without saving?';
        if (typeof window.showConfirm === 'function') {
            window.showConfirm(msg, 'Unsaved changes', 'danger').then(function (ok) { if (ok) closeFn(); });
        } else if (window.confirm(msg)) {
            closeFn();
        }
    };
})();
