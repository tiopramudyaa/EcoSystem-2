{{-- ═════════════════════════════════════════════════════════════════
     TIMESHEET DETAIL SIDE PANEL (Project, Non Project, Support and Office entries)
     Dibuka dari menu klik kanan baris timesheet ("Detail"). Data diambil
     dari baris yang sudah dimuat di list (array `timesheets` milik
     public/js/calendar-timesheets.js), jadi tidak ada request tambahan.
     Pola dan gaya mengikuti ticket/partials/detail-panel.blade.php.
═════════════════════════════════════════════════════════════════ --}}
{{-- Layout kritis memakai inline style (bukan class arbitrary Tailwind) karena CSS
     dibangun lewat Vite dan class arbitrary baru tidak ada di build sebelum di-rebuild. --}}
<div id="tsContextMenu"
    class="hidden fixed bg-white border border-gray-200 rounded-xl shadow-xl py-1 min-w-[170px]"
    style="z-index:9999;pointer-events:auto;">
    <button type="button" id="tsCtxDetail"
        class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors text-left">
        <i class="fas fa-circle-info text-gray-400 w-3.5"></i>
        Detail
    </button>
</div>

<div id="tsDetailOverlay" class="hidden"
    style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9995;"
    onclick="closeTsDetailPanel()"></div>

<aside id="tsDetailPanel"
    class="bg-gray-50 shadow-2xl flex flex-col"
    style="position:fixed;top:0;right:0;height:100%;width:100%;max-width:540px;z-index:9996;transform:translateX(100%);transition:transform .3s ease;visibility:hidden;"
    aria-hidden="true">

    <header class="bg-white border-b border-gray-200 shrink-0 px-6 pt-5 pb-4">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Timesheet Detail</p>
                <div class="flex items-center gap-2.5 mt-1 flex-wrap">
                    <h3 id="tsdEmployee" class="text-xl font-bold text-gray-900"></h3>
                    <span id="tsdStatus"></span>
                </div>
                <p id="tsdSubtitle" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" id="tsdReviewBtn" onclick="tsDetailReview()"
                    class="hidden inline-flex items-center justify-center px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition whitespace-nowrap">
                    Review
                </button>
                <button type="button" onclick="closeTsDetailPanel()"
                    class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition"
                    aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
        <section class="bg-white border border-gray-200 rounded-xl">
            <h4 class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-gray-100">Entry</h4>
            <div id="tsdEntry" class="px-5 py-4 grid grid-cols-2 gap-x-4 gap-y-4"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl">
            <h4 id="tsdProjectTitle" class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-gray-100">Project and Activity</h4>
            <div id="tsdProject" class="px-5 py-4 grid grid-cols-2 gap-x-4 gap-y-4"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl">
            <h4 class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-gray-100">Work Description</h4>
            <div id="tsdWork" class="px-5 py-4 grid grid-cols-2 gap-x-4 gap-y-4"></div>
        </section>

        <section id="tsdGpsSection" class="bg-white border border-gray-200 rounded-xl">
            <h4 class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-gray-100">GPS Location</h4>
            <div class="px-5 py-4">
                <div id="tsdGps" class="grid grid-cols-2 gap-x-4 gap-y-4"></div>
                <div id="tsdMap" class="mt-4 rounded-lg overflow-hidden border border-gray-200 hidden"></div>
            </div>
        </section>

        <section id="tsdReviewSection" class="bg-white border border-gray-200 rounded-xl hidden">
            <h4 class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-gray-100">Review</h4>
            <div id="tsdReview" class="px-5 py-4 grid grid-cols-2 gap-x-4 gap-y-4"></div>
        </section>
    </div>
</aside>

<script>
    (function() {
        const panel = document.getElementById('tsDetailPanel');
        const overlay = document.getElementById('tsDetailOverlay');
        const menu = document.getElementById('tsContextMenu');
        let ctxId = null;     // timesheet under the right-click
        let openId = null;    // timesheet shown in the panel

        const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        } [c]));

        const EMPTY = '<span class="text-gray-300">-</span>';
        const text = v => (v === null || v === undefined || v === '') ? EMPTY : esc(v);

        const STATUS_PILL = {
            draft: ['Draft', 'bg-gray-100 text-gray-700'],
            submitted: ['Submitted', 'bg-yellow-100 text-yellow-700'],
            approved: ['Approved', 'bg-green-100 text-green-700'],
            rejected: ['Rejected', 'bg-red-100 text-red-700'],
        };

        const pill = (label, cls) =>
            `<span class="inline-block px-2.5 py-0.5 rounded-md text-xs font-semibold ${cls}">${esc(label)}</span>`;

        // "Tue, 10 Nov 2026"
        function fmtDate(v) {
            if (!v) return null;
            const d = new Date(String(v).slice(0, 10) + 'T00:00:00');
            if (isNaN(d)) return String(v);
            return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        }

        // "10 Nov 2026 14:05" from a server "Y-m-d H:i:s" string
        function fmtDateTime(v) {
            if (!v) return null;
            const d = new Date(String(v).replace(' ', 'T'));
            if (isNaN(d)) return String(v);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' +
                d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        }

        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        function field(label, valueHtml, wide) {
            return `<div class="min-w-0 ${wide ? 'col-span-2' : ''}">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">${esc(label)}</p>
                <div class="mt-1 text-sm font-medium text-gray-900 break-words">${valueHtml}</div>
            </div>`;
        }

        function find(id) {
            return (typeof timesheets !== 'undefined' ? timesheets : []).find(x => String(x.id) === String(id));
        }

        function mapsLink(lat, lng, label) {
            return `<a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">${esc(label)}</a>`;
        }

        // project | support | office, from the same fields the list uses.
        function typeOf(t) {
            if (t.delivery_projects_id) return 'project';
            if (t.is_non_project) return 'non_project';
            if (t.ticket_id) return 'support';
            return 'office';
        }

        const ACTIVITY_TYPES = {
            development: 'Development', meeting: 'Meeting', documentation: 'Documentation',
            testing: 'Testing', support: 'Support', training: 'Training', other: 'Other',
        };

        function render(t) {
            const type = typeOf(t);
            const st = STATUS_PILL[t.status];
            const typeLabel = { project: 'Project', non_project: 'Non Project', support: 'Support', office: 'Office' }[type];

            document.getElementById('tsdEmployee').textContent = t.employee_name || 'Timesheet';
            document.getElementById('tsdStatus').innerHTML = st ? pill(st[0], st[1]) : '';
            document.getElementById('tsdSubtitle').textContent = typeLabel + ' timesheet' +
                (type === 'project' && t.project_name ? ' - ' + t.project_name : '') +
                (type === 'support' && t.ticket_number ? ' - Ticket #' + t.ticket_number : '');

            const start = t.start_time ? String(t.start_time).slice(0, 5) : '';
            const end = t.end_time ? String(t.end_time).slice(0, 5) : '';
            const hours = t.duration_minutes != null ? formatMdTrim(t.duration_minutes / 60) + ' h' : null;
            const period = (t.period_month && t.period_year) ? `${MONTHS[t.period_month - 1]} ${t.period_year}` : null;

            const entry = [
                field('Employee', text(t.employee_name), true),
                field('Submit Date', text(fmtDate(t.date))),
                type === 'support' ? field('Activity Date', text(fmtDate(t.activity_date))) : field('Period', text(period)),
                field('Time', text(start && end ? `${start} - ${end}` : null)),
                field('Duration', text(hours)),
            ];
            if (type === 'support') entry.push(field('Period', text(period)));
            entry.push(field('Created', text(fmtDateTime(t.created_at)), type !== 'support'));
            document.getElementById('tsdEntry').innerHTML = entry.join('');

            const presence = t.presence ? t.presence.charAt(0).toUpperCase() + t.presence.slice(1) : null;
            const projectEl = document.getElementById('tsdProject');
            const titleEl = document.getElementById('tsdProjectTitle');

            if (type === 'project') {
                titleEl.textContent = 'Project and Activity';
                const actName = t.activity?.name || t.activity_name;
                const activity = t.is_without_activity
                    ? pill('No activity', 'bg-amber-100 text-amber-800')
                    : text(actName);
                projectEl.innerHTML = [
                    field('Project', text(t.project_name), true),
                    field('Activity', activity, true),
                    field('Presence', text(presence)),
                    field('Location', text(t.location)),
                ].join('');
            } else if (type === 'non_project') {
                titleEl.textContent = 'Activity';
                projectEl.innerHTML = [
                    field('Presence', text(presence)),
                    field('Location', text(t.location)),
                ].join('');
            } else if (type === 'support') {
                titleEl.textContent = 'Ticket';
                const ticketLink = t.ticket_number
                    ? `<a href="/ticket/${encodeURIComponent(t.ticket_id)}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">#${esc(t.ticket_number)}</a>`
                    : text(t.ticket_id);
                projectEl.innerHTML = [
                    field('Ticket', ticketLink),
                    field('Type', text(t.ticket_type)),
                    field('Ticket Description', text(t.ticket_description), true),
                    field('Customer', text(t.customer_name), true),
                    field('Quota MD', text(t.jatah_md != null ? formatMdTrim(t.jatah_md) : null)),
                    field('MD Consumed', text(t.md_consumed != null ? formatMdTrim(t.md_consumed) : null)),
                    field('Presence', text(presence)),
                    field('Location', text(t.location)),
                ].join('');
            } else {
                titleEl.textContent = 'Activity';
                projectEl.innerHTML = [
                    field('Activity Type', text(ACTIVITY_TYPES[t.activity_type] || t.activity_type)),
                    field('Billable', text(t.is_billable === undefined || t.is_billable === null ? null : (t.is_billable ? 'Yes' : 'No'))),
                    field('Presence', text(presence)),
                    field('Location', text(t.location)),
                ].join('');
            }

            document.getElementById('tsdWork').innerHTML = [
                field('Activity Detail', t.description ? `<span class="whitespace-pre-line">${esc(t.description)}</span>` : EMPTY, true),
                t.notes ? field('Notes', `<span class="whitespace-pre-line">${esc(t.notes)}</span>`, true) : '',
            ].join('');

            // GPS is captured on Project and Non Project entries only.
            const hasGps = type === 'project' || type === 'non_project';
            document.getElementById('tsdGpsSection').classList.toggle('hidden', !hasGps);
            if (hasGps) renderGps(t); else document.getElementById('tsdMap').innerHTML = '';

            renderReview(t);

            const reviewBtn = document.getElementById('tsdReviewBtn');
            const canReview = (window.isApprovalMode || window.isHoSMode) && t.status === 'submitted';
            reviewBtn.classList.toggle('hidden', !canReview);
        }

        function renderGps(t) {
            const gpsEl = document.getElementById('tsdGps');
            const mapEl = document.getElementById('tsdMap');

            if (t.gps_latitude == null || t.gps_longitude == null) {
                gpsEl.innerHTML = '<p class="col-span-2 text-sm text-gray-400">No GPS location was captured for this entry.</p>';
                mapEl.classList.add('hidden');
                mapEl.innerHTML = '';
                return;
            }

            const lat = Number(t.gps_latitude), lng = Number(t.gps_longitude);
            const coords = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            const acc = t.gps_accuracy != null ? `±${Math.round(t.gps_accuracy)} m` : null;

            const nonProject = !!t.is_non_project;
            let projectLoc = EMPTY, distance = EMPTY;
            if (nonProject) {
                // no project, so nothing to compare the GPS with
            } else if (t.project_latitude != null && t.project_longitude != null) {
                const pLat = Number(t.project_latitude), pLng = Number(t.project_longitude);
                projectLoc = mapsLink(pLat, pLng, `${pLat.toFixed(6)}, ${pLng.toFixed(6)}`);
                const m = _tsDistanceMeters(lat, lng, pLat, pLng);
                distance = esc(m < 1000 ? `${Math.round(m)} m` : `${(m / 1000).toFixed(1)} km`);
            } else {
                projectLoc = '<span class="text-gray-400 font-normal">Not set in the project\'s Location Information</span>';
            }

            gpsEl.innerHTML = [
                field('Address', text(t.gps_address), true),
                field('Coordinates', mapsLink(lat, lng, coords)),
                field('Accuracy', text(acc)),
                nonProject ? '' : field('Project Location', projectLoc, true),
                nonProject ? '' : field('Distance from Project', distance, true),
            ].join('');

            mapEl.innerHTML = `<iframe title="GPS location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="width:100%;height:240px;border:0;"
                src="https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed"></iframe>`;
            mapEl.classList.remove('hidden');
        }

        function renderReview(t) {
            const section = document.getElementById('tsdReviewSection');
            const rows = [];
            if (t.status === 'approved') {
                rows.push(field('Approved by', text(t.approver_name)));
                rows.push(field('Approved at', text(fmtDateTime(t.approved_at))));
            }
            if (t.status === 'rejected') {
                rows.push(field('Rejection Reason', text(t.rejection_reason), true));
            }
            section.classList.toggle('hidden', rows.length === 0);
            document.getElementById('tsdReview').innerHTML = rows.join('');
        }

        // ── Context menu ──────────────────────────────────────────────────
        window.openTsContextMenu = function(e, id) {
            e.preventDefault();
            e.stopPropagation();
            ctxId = id;

            const vw = window.innerWidth, vh = window.innerHeight;
            const mw = 180, mh = 60;
            let x = e.clientX, y = e.clientY;
            if (x + mw > vw) x = vw - mw - 8;
            if (y + mh > vh) y = vh - mh - 8;

            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            menu.classList.remove('hidden');
        };

        document.getElementById('tsCtxDetail').addEventListener('click', function() {
            const id = ctxId;
            menu.classList.add('hidden');
            ctxId = null;
            if (id) openTsDetailPanel(id);
        });

        document.addEventListener('click', function() {
            menu.classList.add('hidden');
            ctxId = null;
        });
        window.addEventListener('scroll', function() { menu.classList.add('hidden'); }, true);

        // ── Panel ─────────────────────────────────────────────────────────
        window.openTsDetailPanel = function(id) {
            const t = find(id);
            if (!t) return;
            openId = t.id;
            render(t);

            overlay.classList.remove('hidden');
            panel.style.visibility = 'visible';
            panel.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => { panel.style.transform = 'translateX(0)'; });
            panel.querySelector('.overflow-y-auto').scrollTop = 0;
        };

        window.closeTsDetailPanel = function() {
            openId = null;
            panel.style.transform = 'translateX(100%)';
            panel.setAttribute('aria-hidden', 'true');
            overlay.classList.add('hidden');
            // Drop the map so a closed panel does not keep a Google Maps frame alive.
            const mapEl = document.getElementById('tsdMap');
            mapEl.innerHTML = '';
            setTimeout(() => {
                if (panel.getAttribute('aria-hidden') === 'true') panel.style.visibility = 'hidden';
            }, 300);
        };

        // "Review" hands over to the existing approve / reject dialog.
        window.tsDetailReview = function() {
            const id = openId;
            if (!id) return;
            closeTsDetailPanel();
            if (typeof openApproveModal === 'function') openApproveModal(id);
        };

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            menu.classList.add('hidden');
            if (panel.getAttribute('aria-hidden') === 'false') closeTsDetailPanel();
        });
    })();
</script>
