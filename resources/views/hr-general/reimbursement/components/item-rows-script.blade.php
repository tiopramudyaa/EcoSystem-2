{{--
    Perilaku tabel baris biaya. Satu berkas dipakai bersama form pengajuan,
    "New RB", dan Edit - supaya ketiganya tidak pernah berbeda perilakunya.

    Diharapkan berada di dalam @push('scripts').
--}}
<script>
(function () {
    const tbody      = document.getElementById('itemRows');
    const template   = document.getElementById('itemRowTemplate');
    const addButton  = document.getElementById('addItemRow');
    const totalCell  = document.getElementById('itemsTotal');
    const currencyEl = document.getElementById('reimbursementCurrency');
    const maxItems   = {{ (int) $settings->max_items_per_request }};   // 0 = tanpa batas

    if (!tbody || !template) return;

    // ── Konsep Object: jenis, target, kategori, dan pratinjau penyetuju ─────
    // Aktif hanya bila form merender sel Object. Satu kumpulan perilaku untuk
    // form pengajuan, "New RB", dan Edit.
    const lookupBase = @json($lookupBase ?? null);
    const ownerParam = () => {
        const owner = document.querySelector('select[name="employee_id"]');
        return owner && owner.value ? '&employee_id=' + encodeURIComponent(owner.value) : '';
    };
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const preview = document.getElementById('objectPreview');
    let previewTimer = null;

    function json(url, options) {
        return fetch(url, Object.assign({ headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }, options || {}))
            .then(function (res) { return res.json(); });
    }

    function syncObjectRow(row) {
        const cell = row.querySelector('.js-object-cell');
        if (!cell) return;

        const type = cell.querySelector('.js-object-type').value;

        cell.querySelectorAll('[data-for]').forEach(function (box) {
            const active = box.dataset.for === type;
            box.hidden = !active;
            // Hanya kontrol yang aktif dikirim dan divalidasi browser.
            box.querySelectorAll('select, input').forEach(function (el) {
                el.disabled = !active;
                if (el.classList.contains('js-object-target')) el.required = active;
            });
        });

        const category = cell.querySelector('.js-category');
        const wrap = cell.querySelector('.js-category-wrap');
        const showCategory = type === 'project' || type === 'support';
        category.disabled = !showCategory;
        if (!showCategory) { wrap.hidden = true; category.value = ''; }
        if (showCategory) loadCategories(row);
    }

    function loadCategories(row) {
        const cell = row.querySelector('.js-object-cell');
        const type = cell.querySelector('.js-object-type').value;
        const category = cell.querySelector('.js-category');
        const wrap = cell.querySelector('.js-category-wrap');
        const ref = type === 'project'
            ? (cell.querySelector('.js-project') || {}).value
            : (cell.querySelector('.js-ticket-id') || {}).value;

        if (!lookupBase || !ref) { wrap.hidden = true; category.value = ''; return; }

        json(lookupBase + '/categories?type=' + encodeURIComponent(type) + '&ref=' + encodeURIComponent(ref) + ownerParam()).then(function (res) {
            const names = (res && res.data) || [];
            const wanted = category.value || category.dataset.selected || '';
            category.innerHTML = '<option value="">- none -</option>' + names.map(function (n) {
                return '<option value="' + String(n).replace(/"/g, '&quot;') + '">' + String(n).replace(/</g, '&lt;') + '</option>';
            }).join('');
            category.value = names.indexOf(wanted) !== -1 ? wanted : '';
            wrap.hidden = names.length === 0;
        }).catch(function () { wrap.hidden = true; });
    }

    // ── Pilihan tiket: kotak pencarian yang terhubung ke master tiket ───────
    let ticketTimer = null;

    function panelNote(text) {
        const p = document.createElement('p');
        p.className = 'px-3 py-2 text-xs text-gray-500';
        p.textContent = text;
        return p;
    }

    function renderTicketPanel(input, items, loading) {
        const row = input.closest('.js-item-row');
        const panel = row.querySelector('.js-ticket-panel');
        panel.innerHTML = '';

        if (loading) {
            panel.appendChild(panelNote('Searching...'));
        } else if (!items.length) {
            panel.appendChild(panelNote('No ticket found. Try the ticket number or a word from its description.'));
        } else {
            items.forEach(function (t) {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'js-ticket-option block w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-0';
                option.dataset.id = t.id;
                option.dataset.number = t.number;

                const number = document.createElement('span');
                number.className = 'font-semibold text-gray-900';
                number.textContent = t.number;

                const detail = document.createElement('span');
                detail.className = 'block text-xs text-gray-500 truncate';
                detail.textContent = (t.description || 'No description') + (t.status ? ' · ' + t.status : '');

                option.appendChild(number);
                option.appendChild(detail);
                panel.appendChild(option);
            });
        }

        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function closeTicketPanel(row) {
        const panel = row.querySelector('.js-ticket-panel');
        const input = row.querySelector('.js-ticket-input');
        if (panel) panel.hidden = true;
        if (input) input.setAttribute('aria-expanded', 'false');
    }

    function fetchTickets(input, delay) {
        clearTimeout(ticketTimer);

        ticketTimer = setTimeout(function () {
            if (!lookupBase) return;
            renderTicketPanel(input, [], true);

            json(lookupBase + '/tickets?q=' + encodeURIComponent(input.value.trim()) + ownerParam()).then(function (res) {
                renderTicketPanel(input, (res && res.data) || [], false);
            }).catch(function () {
                const panel = input.closest('.js-item-row').querySelector('.js-ticket-panel');
                panel.innerHTML = '';
                panel.appendChild(panelNote('The tickets could not be loaded. Close and open the box to try again.'));
                panel.hidden = false;
            });
        }, delay);
    }

    function chooseTicket(row, option) {
        row.querySelector('.js-ticket-input').value = option.dataset.number;
        row.querySelector('.js-ticket-id').value = option.dataset.id;
        closeTicketPanel(row);
        loadCategories(row);
        queuePreview();
    }

    tbody.addEventListener('focusin', function (event) {
        if (event.target.classList.contains('js-ticket-input')) fetchTickets(event.target, 0);
    });

    tbody.addEventListener('click', function (event) {
        const option = event.target.closest('.js-ticket-option');
        if (option) chooseTicket(option.closest('.js-item-row'), option);
    });

    tbody.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && event.target.classList.contains('js-ticket-input')) {
            closeTicketPanel(event.target.closest('.js-item-row'));
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-for="support"]')) return;
        tbody.querySelectorAll('.js-item-row').forEach(closeTicketPanel);
    });
    function collectItems() {
        return Array.prototype.map.call(tbody.querySelectorAll('.js-item-row'), function (row) {
            const get = function (suffix) {
                const el = row.querySelector('[name$="[' + suffix + ']"]:not(:disabled)');
                return el ? el.value : '';
            };
            return {
                cost_center_type: get('cost_center_type'),
                branch_id: get('branch_id'),
                delivery_project_id: get('delivery_project_id'),
                ticket_id: get('ticket_id'),
                fund_category: get('fund_category'),
                amount: get('amount'),
            };
        });
    }

    function queuePreview() {
        if (!preview || !lookupBase) return;
        clearTimeout(previewTimer);
        previewTimer = setTimeout(runPreview, 450);
    }

    function runPreview() {
        const items = collectItems().filter(function (r) {
            return r.branch_id || r.delivery_project_id || r.ticket_id;
        });
        const stepsBox = preview.querySelector('[data-preview-steps]');
        const noteBox = preview.querySelector('[data-preview-note]');
        const reasonBox = document.getElementById('overAllocationBox');

        if (items.length === 0) {
            stepsBox.innerHTML = '<span class="text-gray-400">Choose an Object on each item to see who will review this document.</span>';
            noteBox.textContent = '';
            return;
        }

        const dateEl = document.querySelector('[name="request_date"]');
        const empEl = document.querySelector('[name="employee_id"]');

        json(lookupBase + '/preview', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ items: items, request_date: dateEl ? dateEl.value : '', employee_id: empEl ? empEl.value : '' }),
        }).then(function (res) {
            const d = (res && res.data) || {};
            if (!d.enabled) return;

            if (d.error) {
                stepsBox.innerHTML = '<span class="text-red-700"></span>';
                stepsBox.firstChild.textContent = d.error;
            } else {
                stepsBox.innerHTML = (d.steps || []).map(function (name, i) {
                    const el = document.createElement('span');
                    el.className = 'inline-flex items-center gap-1 px-2 py-1 rounded bg-white border border-gray-200 text-xs text-gray-700';
                    el.textContent = (i + 1) + '. ' + name;
                    return el.outerHTML;
                }).join(' ');
            }

            const notes = (d.skipped || []).slice();
            if (d.blocked) notes.push(d.blocked);
            noteBox.textContent = notes.join(' ');
            noteBox.className = 'text-xs mt-2 ' + (d.blocked ? 'text-red-700' : 'text-gray-500');

            if (reasonBox) {
                const keep = d.needs_reason || reasonBox.querySelector('textarea').value.trim() !== '';
                reasonBox.hidden = !keep;
                reasonBox.querySelector('textarea').required = !!d.needs_reason;
            }
        }).catch(function () {
            stepsBox.innerHTML = '<span class="text-gray-400">The reviewer preview could not be loaded. You can still submit; reviewers are assigned when you submit.</span>';
        });
    }

    tbody.addEventListener('change', function (event) {
        const row = event.target.closest('.js-item-row');
        if (!row || !row.querySelector('.js-object-cell')) return;

        if (event.target.classList.contains('js-object-type')) syncObjectRow(row);
        if (event.target.classList.contains('js-project')) loadCategories(row);
        queuePreview();
    });

    tbody.addEventListener('input', function (event) {
        if (event.target.classList.contains('js-ticket-input')) {
            // Mengubah teks membatalkan tiket yang sudah dipilih: harus dipilih ulang dari daftar.
            event.target.closest('.js-item-row').querySelector('.js-ticket-id').value = '';
            fetchTickets(event.target, 250);
        }
        if (event.target.classList.contains('js-amount')) queuePreview();
    });

    const dateField = document.querySelector('[name="request_date"]');
    if (dateField) dateField.addEventListener('change', queuePreview);

    // Kunci acak, BUKAN indeks berurutan. Dengan indeks berurutan, menghapus
    // baris di tengah membuat nomornya bolong dan baris berikutnya tertimpa.
    function newKey() {
        return 'r' + Math.random().toString(36).slice(2, 10);
    }

    function currentCurrency() {
        return currencyEl && currencyEl.value ? currencyEl.value : 'IDR';
    }

    function formatMoney(value) {
        const code = currentCurrency();

        if (code === 'IDR') {
            return 'Rp ' + Math.round(value).toLocaleString('id-ID');
        }

        return code + ' ' + value.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function syncCurrencyLabels() {
        document.querySelectorAll('.js-currency-code').forEach(function (el) {
            el.textContent = currentCurrency();
        });
    }

    function recalcTotal() {
        let total = 0;

        tbody.querySelectorAll('.js-amount').forEach(function (input) {
            const value = parseFloat(input.value);
            if (!isNaN(value) && value > 0) {
                // Dibulatkan per baris lalu dijumlahkan, sama seperti di server
                // (Keputusan D115): total harus sama dengan jumlah angka yang
                // terbaca, bukan hasil aritmetika penuh.
                total += Math.round(value * 100) / 100;
            }
        });

        totalCell.textContent = formatMoney(total);
    }

    function renumber() {
        tbody.querySelectorAll('.js-item-row').forEach(function (row, index) {
            row.querySelector('.js-line-no').textContent = index + 1;
        });

        // Baris terakhir tidak boleh dihapus: dokumen tanpa item tidak sah, dan
        // tombol yang selalu terlihat tapi kadang ditolak lebih membingungkan
        // daripada tombol yang hilang.
        const rows = tbody.querySelectorAll('.js-item-row');
        rows.forEach(function (row) {
            const button = row.querySelector('.js-remove-row');
            if (button) button.classList.toggle('invisible', rows.length === 1);
        });

        if (addButton && maxItems > 0) {
            addButton.disabled = rows.length >= maxItems;
            addButton.classList.toggle('opacity-50', addButton.disabled);
            addButton.classList.toggle('cursor-not-allowed', addButton.disabled);
        }
    }

    function addRow() {
        const key  = newKey();
        const html = template.innerHTML.replace(/__KEY__/g, key);

        const holder = document.createElement('tbody');
        holder.innerHTML = html.trim();

        const row = holder.querySelector('tr');
        row.dataset.key = key;
        tbody.appendChild(row);

        syncObjectRow(row);
        renumber();
        recalcTotal();
        queuePreview();

        const first = row.querySelector('input[type="text"]');
        if (first) first.focus();
    }

    tbody.addEventListener('input', function (event) {
        if (event.target.classList.contains('js-amount')) recalcTotal();
    });

    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.js-remove-row');
        if (!button) return;

        if (tbody.querySelectorAll('.js-item-row').length === 1) return;

        button.closest('.js-item-row').remove();
        renumber();
        recalcTotal();
        queuePreview();
    });

    if (addButton) addButton.addEventListener('click', addRow);

    if (currencyEl) {
        currencyEl.addEventListener('change', function () {
            syncCurrencyLabels();
            recalcTotal();
        });
    }

    syncCurrencyLabels();
    tbody.querySelectorAll('.js-item-row').forEach(syncObjectRow);
    renumber();
    recalcTotal();
    queuePreview();
})();
</script>
