/**
 * Pemilih Object tunggal (Office / Project / Support) untuk Cash Advance.
 *
 * Markup: resources/views/hr-general/cash-advance/components/object-picker.blade.php
 * (container [data-object-picker] dengan data-lookup-base). Pembantu server:
 * ReimbursementObjectLookupController (tiket, kategori, pratinjau penyetuju).
 *
 * Perilaku yang sama dengan item-rows-script milik Reimbursement:
 *  - hanya kontrol sesuai jenis yang dikirim (yang lain dinonaktifkan);
 *  - kotak tiket terhubung ke master tiket: terbaru saat dibuka, dicari saat mengetik;
 *  - kategori dana muncul hanya bila project atau tiket punya kategori;
 *  - pratinjau penyetuju dan kotak alasan melebihi dana.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-object-picker]');
    if (!root) return;

    var form = root.closest('form');
    var base = root.dataset.lookupBase || '';
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var typeEl = root.querySelector('.js-object-type');
    var wrap = root.querySelector('.js-category-wrap');
    var category = root.querySelector('.js-category');
    var ticketInput = root.querySelector('.js-ticket-input');
    var ticketId = root.querySelector('.js-ticket-id');
    var ticketLabel = root.querySelector('.js-ticket-label');
    var panel = root.querySelector('.js-ticket-panel');
    var preview = document.getElementById('objectPreview');
    var timer = null;
    var ticketTimer = null;

    function json(url, options) {
        return fetch(url, Object.assign({
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }, options || {})).then(function (res) { return res.json(); });
    }

    function field(name) {
        return form ? form.querySelector('[name="' + name + '"]') : null;
    }

    function refValue(type) {
        if (type === 'project') return (root.querySelector('.js-project') || {}).value || '';
        if (type === 'support') return ticketId.value || '';
        return '';
    }

    function sync() {
        var type = typeEl.value;

        root.querySelectorAll('[data-for]').forEach(function (box) {
            var active = box.dataset.for === type;
            box.hidden = !active;
            box.querySelectorAll('select, input').forEach(function (el) {
                if (el.type === 'hidden' && !el.classList.contains('js-ticket-id')) return;
                el.disabled = !active;
                if (el.classList.contains('js-object-target')) el.required = active;
            });
        });

        var wantsCategory = type === 'project' || type === 'support';
        category.disabled = !wantsCategory;

        if (!wantsCategory) {
            wrap.hidden = true;
            category.value = '';
        } else {
            loadCategories();
        }

        queuePreview();
    }

    function loadCategories() {
        var type = typeEl.value;
        var ref = refValue(type);

        if (!base || !ref) { wrap.hidden = true; category.value = ''; return; }

        json(base + '/categories?type=' + encodeURIComponent(type) + '&ref=' + encodeURIComponent(ref)).then(function (res) {
            var names = (res && res.data) || [];
            var wanted = category.value || category.dataset.selected || '';

            category.innerHTML = '<option value="">- none -</option>' + names.map(function (n) {
                var safe = String(n).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                return '<option value="' + safe + '">' + safe + '</option>';
            }).join('');

            category.value = names.indexOf(wanted) !== -1 ? wanted : '';
            wrap.hidden = names.length === 0;
        }).catch(function () { wrap.hidden = true; });
    }

    // ── Kotak tiket ─────────────────────────────────────────────────────────
    function note(text) {
        var p = document.createElement('p');
        p.className = 'px-3 py-2 text-xs text-gray-500';
        p.textContent = text;
        return p;
    }

    function renderTickets(items, loading) {
        panel.innerHTML = '';

        if (loading) {
            panel.appendChild(note('Searching...'));
        } else if (!items.length) {
            panel.appendChild(note('No ticket found. Try the ticket number or a word from its description.'));
        } else {
            items.forEach(function (t) {
                var option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'js-ticket-option block w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-0';
                option.dataset.id = t.id;
                option.dataset.number = t.number;

                var number = document.createElement('span');
                number.className = 'font-semibold text-gray-900';
                number.textContent = t.number;

                var detail = document.createElement('span');
                detail.className = 'block text-xs text-gray-500 truncate';
                detail.textContent = (t.description || 'No description') + (t.status ? ' · ' + t.status : '');

                option.appendChild(number);
                option.appendChild(detail);
                panel.appendChild(option);
            });
        }

        panel.hidden = false;
        ticketInput.setAttribute('aria-expanded', 'true');
    }

    function closePanel() {
        panel.hidden = true;
        ticketInput.setAttribute('aria-expanded', 'false');
    }

    function fetchTickets(delay) {
        clearTimeout(ticketTimer);
        ticketTimer = setTimeout(function () {
            if (!base) return;
            renderTickets([], true);

            json(base + '/tickets?q=' + encodeURIComponent(ticketInput.value.trim())).then(function (res) {
                renderTickets((res && res.data) || [], false);
            }).catch(function () {
                panel.innerHTML = '';
                panel.appendChild(note('The tickets could not be loaded. Close and open the box to try again.'));
                panel.hidden = false;
            });
        }, delay);
    }

    ticketInput.addEventListener('focus', function () { fetchTickets(0); });

    ticketInput.addEventListener('input', function () {
        // Mengubah teks membatalkan tiket yang sudah dipilih: harus dipilih ulang dari daftar.
        ticketId.value = '';
        fetchTickets(250);
        queuePreview();
    });

    ticketInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closePanel();
    });

    panel.addEventListener('click', function (event) {
        var option = event.target.closest('.js-ticket-option');
        if (!option) return;

        ticketInput.value = option.dataset.number;
        ticketId.value = option.dataset.id;
        if (ticketLabel) ticketLabel.value = option.dataset.number;
        closePanel();
        loadCategories();
        queuePreview();
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-for="support"]')) closePanel();
    });

    // ── Pratinjau penyetuju dan alasan melebihi dana ────────────────────────
    function parseAmount(text) {
        var digits = String(text || '').replace(/[^0-9]/g, '');
        return digits === '' ? 0 : Number(digits);
    }

    function queuePreview() {
        if (!preview || !base) return;
        clearTimeout(timer);
        timer = setTimeout(runPreview, 450);
    }

    function runPreview() {
        var type = typeEl.value;
        var stepsBox = preview.querySelector('[data-preview-steps]');
        var noteBox = preview.querySelector('[data-preview-note]');
        var reasonBox = document.getElementById('overAllocationBox');
        var branch = field('charged_branch_id');
        var project = field('charged_project_id');
        var hasTarget = (type === 'branch' && branch && branch.value) || (type === 'project' && project && project.value) || (type === 'support' && ticketId.value);

        if (!hasTarget) {
            stepsBox.innerHTML = '<span class="text-gray-400">Choose an Object to see who will review this cash advance.</span>';
            noteBox.textContent = '';
            return;
        }

        var amount = field('amount');
        var dateEl = field('request_date');
        var employee = field('employee_id');

        json(base + '/preview', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                items: [{
                    cost_center_type: type,
                    branch_id: branch ? branch.value : '',
                    delivery_project_id: project ? project.value : '',
                    ticket_id: ticketId.value,
                    fund_category: category.disabled ? '' : category.value,
                    amount: parseAmount(amount ? amount.value : 0)
                }],
                request_date: dateEl ? dateEl.value : '',
                employee_id: employee ? employee.value : '',
                module: 'cash_advance'
            })
        }).then(function (res) {
            var d = (res && res.data) || {};
            if (!d.enabled) return;

            stepsBox.innerHTML = '';

            if (d.error) {
                var err = document.createElement('span');
                err.className = 'text-red-700';
                err.textContent = d.error;
                stepsBox.appendChild(err);
            } else {
                (d.steps || []).forEach(function (name, i) {
                    var chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1 px-2 py-1 rounded bg-white border border-gray-200 text-xs text-gray-700';
                    chip.textContent = (i + 1) + '. ' + name;
                    stepsBox.appendChild(chip);
                    stepsBox.appendChild(document.createTextNode(' '));
                });
            }

            var notes = (d.skipped || []).slice();
            if (d.blocked) notes.push(d.blocked);
            noteBox.textContent = notes.join(' ');
            noteBox.className = 'text-xs mt-2 ' + (d.blocked ? 'text-red-700' : 'text-gray-500');

            if (reasonBox) {
                var textarea = reasonBox.querySelector('textarea');
                var keep = d.needs_reason || textarea.value.trim() !== '';
                reasonBox.hidden = !keep;
                textarea.required = !!d.needs_reason;
            }
        }).catch(function () {
            stepsBox.innerHTML = '<span class="text-gray-400">The reviewer preview could not be loaded. You can still submit; reviewers are assigned when you submit.</span>';
        });
    }

    typeEl.addEventListener('change', sync);
    root.addEventListener('change', function (event) {
        if (event.target.classList.contains('js-project')) loadCategories();
        queuePreview();
    });

    ['amount', 'request_date', 'employee_id'].forEach(function (name) {
        var el = field(name);
        if (el) {
            el.addEventListener('input', queuePreview);
            el.addEventListener('change', queuePreview);
        }
    });

    sync();
})();
