{{-- Legal Basis register (Register Dasar Hukum) — the second half of the Legal page. Expects $basis (LegalBasisService::registerPage). --}}
@php extract($basis); @endphp
@php
    $canCreate = $canDo('finance.legal-basis', 'create');
    $canEdit = $canDo('finance.legal-basis', 'edit');
    $canDelete = $canDo('finance.legal-basis', 'delete');
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 bg-white';
    $label = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
    $tones = ['blue' => 'bg-blue-50 text-blue-600', 'amber' => 'bg-amber-50 text-amber-600', 'green' => 'bg-green-50 text-green-600', 'gray' => 'bg-gray-100 text-gray-500'];
    $confTone = ['high' => 'text-green-700', 'medium' => 'text-amber-700', 'low' => 'text-red-600'];
    // Every version of every rule on this page: the "Changes" button compares a version with the one before it (same rule key).
    $allVersions = collect($rows)->flatten(1);
    $versionsOf = $allVersions->groupBy('rule_key');
    $versionData = $allVersions->map(fn ($x) => [
        'id' => $x->id, 'rule_key' => $x->rule_key, 'label' => $x->label, 'title' => $x->title, 'article' => $x->article, 'effective_date' => substr((string) $x->effective_date, 0, 10),
        'last_amended_date' => $x->last_amended_date ? substr((string) $x->last_amended_date, 0, 10) : null, 'amended_by' => $x->amended_by, 'source_url' => $x->source_url,
        'confidence' => $x->confidence, 'notes' => $x->notes, 'payload' => $x->payload,
    ])->values();
@endphp
    @include('partials.accent-remap')
    @include('legal.contracts.components.confirm-forms')
    @include('hr-general.recruitment.components.form-errors')
    @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif

    <div id="basis-note" class="pst-section bg-amber-50 border border-amber-200 text-amber-900 rounded-xl px-5 py-4 flex items-start gap-3">
        <span class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0"><i class="fas fa-scale-balanced"></i></span>
        <div class="text-xs"><p class="text-sm font-bold">Read this before relying on a rule</p>
            <p class="mt-1">The entries were written from the preparer's knowledge and cross-checked on <strong>19 Oct 2026</strong> against secondary sources (law-firm, tax and BPJS publications) — <strong>the official text has not been opened</strong>. Every entry stays <strong>Needs review</strong> until a person confirms it. HR, Legal or Tax must compare it with the official regulation, fill in the source link, correct any value, and then mark it <strong>Reviewed</strong>. Changing the content of an entry sends it back to Needs review. The register changes a Payroll number only where you switched Payroll (PPh 21 / BPJS rates, overtime multipliers) or Final Settlement to read from it — see the <strong>Settings</strong> tab. A reviewed rule should be reviewed again after 12 months; its <strong>owner</strong> is reminded in the notification bell.</p></div>
    </div>

    <div id="basis-summary" class="pst-section grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([['Entries', $counts['total'], 'book', 'blue'], ['Needs review', $counts['needs_review'], 'triangle-exclamation', 'amber'], ['Reviewed', $counts['reviewed'], 'circle-check', 'green'], ['Review overdue (12+ months)' . ($counts['due_soon'] ? ' · ' . $counts['due_soon'] . ' due within 30 days' : ''), $counts['stale'], 'hourglass-half', $counts['stale'] ? 'amber' : 'gray']] as [$text, $n, $icon, $tone])
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $tones[$tone] }}"><i class="fas fa-{{ $icon }}"></i></span>
                <div><p class="text-2xl font-bold text-gray-800 leading-none">{{ $n }}</p><p class="text-[11px] text-gray-500 mt-1">{{ $text }}</p></div>
            </div>
        @endforeach
    </div>

    <div id="basis-register" class="pst-section flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('finance.legal-basis.index') }}#basis-register" class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $module === '' ? 'primary-gradient text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50' }}">All</a>
            @foreach($modules as $k => $v)<a href="{{ route('finance.legal-basis.index', ['module' => $k]) }}#basis-register" class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $module === $k ? 'primary-gradient text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $v }}</a>@endforeach
        </div>
        @if($canCreate)<button type="button" onclick="openLegal()" class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm"><i class="fas fa-plus mr-1"></i> Add rule or new version</button>@endif
    </div>

    @forelse($rows as $mod => $list)
        <div id="reg-{{ $mod }}" class="pst-section bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-gray-800">{{ $modules[$mod] ?? $mod }}</h3>
                @if($canEdit && count($pending[$mod] ?? []))
                    <form method="POST" action="{{ route('finance.legal-basis.review-module', $mod) }}" data-confirm="Accept ALL rules of {{ $modules[$mod] ?? $mod }} as reviewed? Do this only after Legal (or the responsible person) has checked them against the official regulation." data-confirm-title="Accept review" data-confirm-ok="Accept all" data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg border border-green-300 text-green-700 text-[11px] font-semibold hover:bg-green-50">Accept review (all)</button></form>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <tr><th class="px-4 py-3">Rule</th><th class="px-4 py-3">Regulation</th><th class="px-4 py-3">In force since</th><th class="px-4 py-3">Last amended</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Confidence</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach($list as $r)
                            <tr class="align-top {{ $r->status === 'superseded' ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3 font-mono text-[11px] text-gray-600 whitespace-nowrap">{{ $r->rule_key }}
                                    @if($hasOwners)<span class="block font-sans text-[10px] {{ trim((string) ($r->owner_name ?? '')) !== '' ? 'text-gray-500' : 'text-gray-300' }}" title="Owner: the person responsible for keeping this rule up to date"><i class="far fa-user mr-0.5"></i>{{ trim((string) ($r->owner_name ?? '')) !== '' ? trim($r->owner_name) : 'No owner' }}</span>@endif</td>
                                <td class="px-4 py-3"><span class="font-semibold text-gray-800">{{ $r->label }}</span><span class="block text-[11px] text-gray-500 max-w-md">{{ $r->title }}</span>
                                    @if($r->article)<span class="block text-[10px] text-gray-400 max-w-md">{{ $r->article }}</span>@endif
                                    @if($r->source_url)<a href="{{ $r->source_url }}" target="_blank" rel="noopener" class="text-[10px] primary-text hover:underline"><i class="fas fa-arrow-up-right-from-square mr-0.5"></i> source</a>@else<span class="block text-[10px] text-amber-600">No source link yet</span>@endif
                                    @if($r->notes)<span class="block text-[10px] text-gray-400 max-w-md mt-0.5 italic">{{ $r->notes }}</span>@endif</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->effective_date)->format('d M Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $r->last_amended_date ? \Illuminate\Support\Carbon::parse($r->last_amended_date)->format('d M Y') : '—' }}@if($r->amended_by)<span class="block text-[10px] text-gray-400 max-w-[14rem] whitespace-normal">{{ $r->amended_by }}</span>@endif</td>
                                <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badges[$r->status] ?? '' }}">{{ $statuses[$r->status] ?? $r->status }}</span>
                                    @if($r->reviewed_at)<span class="block text-[10px] text-gray-400">{{ trim($r->reviewer) }} · {{ \Illuminate\Support\Carbon::parse($r->reviewed_at)->format('d M Y') }}</span>@endif
                                    @if($r->review_days_left !== null)
                                        @if($r->review_days_left < 0)<span class="block text-[10px] font-semibold text-red-600" title="Reviewed again by {{ $r->review_due_at }}"><i class="fas fa-hourglass-end mr-0.5"></i>Review overdue by {{ abs($r->review_days_left) }} day{{ abs($r->review_days_left) === 1 ? '' : 's' }}</span>
                                        @elseif($r->review_days_left <= 30)<span class="block text-[10px] font-semibold text-amber-600" title="Reviewed again by {{ $r->review_due_at }}"><i class="fas fa-hourglass-half mr-0.5"></i>Review due in {{ $r->review_days_left }} day{{ $r->review_days_left === 1 ? '' : 's' }}</span>@endif
                                    @endif</td>
                                <td class="px-4 py-3 text-[11px] font-semibold {{ $confTone[$r->confidence] ?? '' }}">{{ $confidence[$r->confidence] ?? $r->confidence }}</td>
                                <td class="px-4 py-3"><div class="flex justify-end flex-wrap gap-1.5">
                                    @if(($versionsOf[$r->rule_key] ?? collect())->contains(fn ($x) => substr((string) $x->effective_date, 0, 10) < substr((string) $r->effective_date, 0, 10)))<button type="button" onclick="showLegalChanges({{ $r->id }})" class="px-2.5 py-1.5 rounded-lg border border-sky-300 text-sky-700 text-[11px] font-semibold hover:bg-sky-50" title="What changed compared with the previous version of this rule"><i class="fas fa-code-compare mr-1"></i>Changes</button>@endif
                                    @if($canEdit && $r->status === 'needs_review')<form method="POST" action="{{ route('finance.legal-basis.review', $r->id) }}" data-confirm="Mark {{ $r->rule_key }} ({{ $r->label }}) as reviewed? Confirm you compared it with the official text." data-confirm-title="Mark as Reviewed" data-confirm-ok="Reviewed" data-confirm-variant="primary" data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg bg-green-600 text-white text-[11px] font-semibold hover:opacity-90">Mark reviewed</button></form>@endif
                                    @if($canEdit && $r->status === 'active')<form method="POST" action="{{ route('finance.legal-basis.needs-review', $r->id) }}" data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-600 text-[11px] font-semibold hover:bg-gray-50">Needs review</button></form>@endif
                                    @if($canEdit && $r->status === 'superseded')<form method="POST" action="{{ route('finance.legal-basis.restore', $r->id) }}" data-confirm="Restore this version? It goes back to “Needs review” and calculators use it again." data-confirm-title="Restore" data-confirm-ok="Restore" data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg border border-green-300 text-green-700 text-[11px] font-semibold hover:bg-green-50">Restore</button></form>@endif
                                    @if($canEdit && $r->status !== 'superseded')<button type="button" class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-[11px] font-semibold hover:bg-gray-50" data-legal="{{ json_encode($r) }}" onclick="openLegal(JSON.parse(this.dataset.legal))">Edit</button>@endif
                                    @if($canDelete && $r->status !== 'superseded')<form method="POST" action="{{ route('finance.legal-basis.supersede', $r->id) }}" data-confirm="Mark this version superseded? It is no longer used by calculators." data-confirm-title="Supersede" data-confirm-ok="Supersede" data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg border border-red-300 text-red-600 text-[11px] font-semibold hover:bg-red-50">Supersede</button></form>@endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-12 text-center text-sm text-gray-500">No legal-basis entry in this module yet.</div>
    @endforelse

{{-- Changes between two versions of a rule (read-only; everyone who can see the Register can open it) --}}
<div id="legalDiffModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center p-4" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="legalDiffTitle">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[92vh] flex flex-col">
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
            <div><h3 class="text-sm font-bold text-gray-800" id="legalDiffTitle">Changes</h3><p class="text-[11px] text-gray-500" id="legalDiffSub"></p></div>
            <button type="button" onclick="closeLegalChanges()" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-5 py-4 overflow-y-auto"><div id="legalDiffBody" class="text-xs"></div></div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end"><button type="button" onclick="closeLegalChanges()" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Close</button></div>
    </div>
</div>
<script type="application/json" id="legalVersionData">@json($versionData)</script>
@push('scripts')
<script>
(function () {
    const data = JSON.parse(document.getElementById('legalVersionData').textContent || '[]'), modal = document.getElementById('legalDiffModal');
    const $ = id => document.getElementById(id);
    const LABEL = {
        up_to: 'Up to (Rp)', rate: 'Rate (%)', brackets: 'Brackets', max_monthly: 'Maximum per month (Rp)', surcharge: 'Surcharge (%)', employer: 'Employer share (%)', employee: 'Employee share (%)',
        jkk_min: 'JKK minimum (%)', jkk_max: 'JKK maximum (%)', jkm: 'JKM (%)', monthly_divisor: 'Monthly divisor', max_years: 'Maximum term (years)', months_per_year: 'Months per year of service',
        min_service_months: 'Minimum service (months)', severance: 'Severance pay', service_award: 'Service award pay', reasons: 'Reasons for ending employment', label: 'Name', article: 'Article',
        leave: 'Pays out unused leave', six_day: 'Six-day week (days)', five_day: 'Five-day week (days)', gross_over: 'Monthly income over (Rp)', gross_upto: 'Up to (Rp)', annual_amount: 'Amount per year (Rp)',
        code: 'Code', description: 'Description', min_base: 'Minimum wage base (Rp)', wage_cap: 'Wage ceiling (Rp)', categories: 'TER categories', codes: 'PTKP codes', hours: 'Hours in this band',
        multiplier: 'Pay multiplier (×)', workday: 'Working day', rest_day_5: 'Rest day or holiday, 5-day week', rest_day_6: 'Rest day or holiday, 6-day week'
    };
    const TUPLE = { severance: ['Years of service up to', 'Months of pay'], service_award: ['Years of service up to', 'Months of pay'] };
    const PCT = /^(rate|surcharge|employer|employee|jkk_min|jkk_max|jkm)$/, MONEY = /^(up_to|max_monthly|annual_amount|gross_over|gross_upto|min_base|wage_cap)$/;
    const human = k => LABEL[k] || (/^[A-Z]$/.test(String(k)) ? 'Category ' + k : String(k).replace(/_/g, ' ').replace(/^./, c => c.toUpperCase()));
    const num = n => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 6 }).format(n);
    const show = (key, v) => {
        if (v === undefined) { return '—'; }
        if (v === null) { return key === 'hours' ? 'All remaining hours' : 'No limit'; }
        if (typeof v === 'boolean') { return v ? 'Yes' : 'No'; }
        if (typeof v === 'number') { return PCT.test(key) ? num(Math.round(v * 1e8) / 1e6) + '%' : (MONEY.test(key) ? 'Rp ' + num(v) : num(v)); }
        return String(v);
    };
    // Flatten a payload to { "Brackets › #2 › Rate (%)": {key, value} }
    function flat(v, path, key, out) {
        if (Array.isArray(v)) {
            const tuple = v.length && v.every(r => Array.isArray(r));
            v.forEach((r, i) => {
                if (tuple) { r.forEach((c, j) => { const head = (TUPLE[key] || [])[j] || ('Value ' + (j + 1)); out[path.concat('row ' + (i + 1), head).join(' › ')] = { key: j === 0 && TUPLE[key] ? 'up_to_years' : 'v', value: c }; }); }
                else { flat(r, path.concat('#' + (i + 1)), key, out); }
            });
        } else if (v && typeof v === 'object') {
            Object.keys(v).forEach(k => flat(v[k], path.concat(human(k)), k, out));
        } else { out[path.join(' › ')] = { key: key, value: v }; }
        return out;
    }
    const META = [['label', 'Regulation'], ['title', 'Title / subject'], ['article', 'Article(s)'], ['effective_date', 'In force since'], ['last_amended_date', 'Last amended'],
        ['amended_by', 'Amended by'], ['source_url', 'Official source link'], ['confidence', 'Confidence'], ['notes', 'Notes']];
    function cell(text, cls) { const td = document.createElement('td'); td.className = 'px-3 py-1.5 align-top ' + (cls || ''); td.textContent = text; return td; }
    window.showLegalChanges = function (id) {
        const cur = data.find(x => x.id === id); if (!cur) { return; }
        const prev = data.filter(x => x.rule_key === cur.rule_key && x.effective_date < cur.effective_date).sort((a, b) => b.effective_date.localeCompare(a.effective_date))[0];
        $('legalDiffTitle').textContent = cur.rule_key + ': what changed';
        $('legalDiffSub').textContent = prev ? 'In force since ' + prev.effective_date + ' (previous)  →  ' + cur.effective_date + ' (this version)' : '';
        const body = $('legalDiffBody'); body.textContent = '';
        const rows = [];
        if (prev) {
            META.forEach(([k, label]) => { const a = prev[k] || '', b = cur[k] || ''; if (String(a) !== String(b)) { rows.push([label, a === '' ? '—' : a, b === '' ? '—' : b, 'meta']); } });
            const pa = prev.payload ? flat(JSON.parse(prev.payload), [], '', {}) : {}, pb = cur.payload ? flat(JSON.parse(cur.payload), [], '', {}) : {};
            Object.keys(Object.assign({}, pa, pb)).forEach(p => {
                const a = pa[p], b = pb[p], k = (b || a).key;
                if (!a || !b || show(k, a.value) !== show(k, b.value)) { rows.push([p, a ? show(k, a.value) : '— (not in the previous version)', b ? show(k, b.value) : '— (removed)', 'value']); }
            });
        }
        if (!rows.length) {
            const p = document.createElement('p'); p.className = 'text-gray-500 py-6 text-center'; p.textContent = prev ? 'These two versions hold the same information.' : 'There is no earlier version of this rule.'; body.appendChild(p);
        } else {
            const note = document.createElement('p'); note.className = 'text-[11px] text-gray-500 mb-2'; note.textContent = rows.length + ' difference' + (rows.length === 1 ? '' : 's') + ' (regulation details first, then calculation values).'; body.appendChild(note);
            const wrap = document.createElement('div'); wrap.className = 'overflow-x-auto border border-gray-200 rounded-lg';
            const t = document.createElement('table'); t.className = 'w-full text-xs text-left';
            t.innerHTML = '<thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500"><tr><th class="px-3 py-2">What</th><th class="px-3 py-2">Previous version</th><th class="px-3 py-2">This version</th></tr></thead>';
            const tb = document.createElement('tbody'); tb.className = 'divide-y divide-gray-100';
            rows.slice(0, 300).forEach(r => { const tr = document.createElement('tr'); tr.appendChild(cell(r[0], 'font-semibold text-gray-700')); tr.appendChild(cell(r[1], 'text-red-700 bg-red-50/40')); tr.appendChild(cell(r[2], 'text-green-700 bg-green-50/40')); tb.appendChild(tr); });
            t.appendChild(tb); wrap.appendChild(t); body.appendChild(wrap);
            if (rows.length > 300) { const m = document.createElement('p'); m.className = 'text-[11px] text-gray-400 mt-2'; m.textContent = 'Showing the first 300 of ' + rows.length + ' differences.'; body.appendChild(m); }
        }
        modal.style.display = 'flex'; modal.classList.remove('hidden');
    };
    window.closeLegalChanges = () => { modal.style.display = 'none'; modal.classList.add('hidden'); };
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) { window.closeLegalChanges(); } });
})();
</script>
@endpush

@if($canCreate || $canEdit)
<div id="legalModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center p-4" style="display: none;">
    <form id="legalForm" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[92vh] flex flex-col" data-unsaved-ignore>@csrf
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between"><div><h3 class="text-sm font-bold text-gray-800" id="legalTitle">Add rule or new version</h3>
            <p class="text-[11px] text-gray-400">A new effective date makes a new version; the older one stays in the history.</p></div>
            <button type="button" onclick="closeLegal()" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button></div>
        <div class="px-5 py-4 overflow-y-auto grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="{{ $label }}" for="lKey">Rule key</label><input id="lKey" name="rule_key" required maxlength="60" pattern="[a-z0-9_.]+" placeholder="e.g. bpjs.jp" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="lMod">Module</label><select id="lMod" name="module" class="{{ $input }}">@foreach($modules as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div><label class="{{ $label }}" for="lConf">Your confidence</label><select id="lConf" name="confidence" class="{{ $input }}">@foreach($confidence as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div><label class="{{ $label }}" for="lType">Regulation type</label><select id="lType" name="regulation_type" class="{{ $input }}">@foreach($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div><label class="{{ $label }}" for="lNum">Number</label><input id="lNum" name="number" required maxlength="40" placeholder="e.g. 35" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="lYear">Year</label><input id="lYear" type="number" name="year" required min="1945" max="2100" class="{{ $input }}"></div>
            <div class="md:col-span-3"><label class="{{ $label }}" for="lTitle">Title / subject</label><input id="lTitle" name="title" required maxlength="400" class="{{ $input }}"></div>
            <div class="md:col-span-3"><label class="{{ $label }}" for="lArt">Article(s)</label><input id="lArt" name="article" maxlength="400" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="lEff">In force since</label><input id="lEff" type="date" name="effective_date" required class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="lAm">Last amended</label><input id="lAm" type="date" name="last_amended_date" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="lUrl">Official source link</label><input id="lUrl" type="url" name="source_url" maxlength="500" placeholder="https://" class="{{ $input }}"></div>
            <div class="md:col-span-3"><label class="{{ $label }}" for="lBy">Amended by</label><input id="lBy" name="amended_by" maxlength="400" placeholder="Regulation(s) that changed this one" class="{{ $input }}"></div>
            @if($hasOwners)
            <div class="md:col-span-3"><label class="{{ $label }}" for="lOwner">Owner (responsible person)</label>
                <select id="lOwner" name="owner_employee_id" data-searchable="true" data-search-placeholder="Search person…" class="{{ $input }}"><option value="">No owner</option>@foreach($ownerOptions as $oid => $oname)<option value="{{ $oid }}">{{ $oname }}</option>@endforeach</select>
                <p class="text-[10px] text-gray-400 mt-1">Keeps this rule up to date. The owner is reminded in the notification bell when its yearly review is due. Changing only the owner does not send the rule back to “Needs review”.</p></div>
            @endif
            {{-- Calculation values (rates, brackets, tables) as a form: the calculators read them, so a change here changes their results. --}}
            <div class="md:col-span-3" id="lPayWrap">
                <span class="{{ $label }}">Calculation values</span>
                <input type="hidden" id="lPay" name="payload">
                <div id="lPayEditor" class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3 text-xs"></div>
                <p id="lPayNote" class="text-[11px] mt-1.5 rounded-md px-2.5 py-1.5"></p>
                <p class="text-[10px] text-gray-400 mt-1">A change is saved as a version that must be reviewed before it counts. Percentages are typed as percent (5 means 5%). Leave “No limit” empty.</p>
            </div>
            <div class="md:col-span-3"><label class="{{ $label }}" for="lNotes">Notes</label><textarea id="lNotes" name="notes" rows="2" maxlength="3000" class="{{ $input }}"></textarea></div>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2"><button type="button" onclick="closeLegal()" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button><button class="px-5 py-2 text-xs font-semibold text-white primary-gradient rounded-lg hover:opacity-90">Save</button></div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id), modal = $('legalModal');
    const store = @json(route('finance.legal-basis.store')), base = @json(url('legal/basis'));
    window.openLegal = function (r) {
        const f = $('legalForm'); f.reset();
        $('legalTitle').textContent = r ? 'Edit ' + r.rule_key : 'Add rule or new version';
        f.action = r ? base + '/' + r.id + '/update' : store;
        if (r) {
            $('lKey').value = r.rule_key; $('lMod').value = r.module; $('lConf').value = r.confidence; $('lType').value = r.regulation_type; $('lNum').value = r.number; $('lYear').value = r.year;
            $('lTitle').value = r.title; $('lArt').value = r.article || ''; $('lEff').value = (r.effective_date || '').substring(0, 10); $('lAm').value = (r.last_amended_date || '').substring(0, 10);
            $('lUrl').value = r.source_url || ''; $('lBy').value = r.amended_by || ''; $('lNotes').value = r.notes || '';
            if ($('lOwner')) { $('lOwner').value = r.owner_employee_id || ''; }
        }
        // The app's custom dropdowns follow a select only when it fires "change": setting .value (or reset()) does not, so the Module box kept
        // showing the first option (“PPh 21”) for every rule. Tell them.
        ['lMod', 'lConf', 'lType', 'lOwner'].forEach(id => { if ($(id)) { $(id).dispatchEvent(new Event('change', { bubbles: true })); } });
        mountRuleData(r && r.payload ? r.payload : null);
        modal.style.display = 'flex'; modal.classList.remove('hidden');
    };
    window.closeLegal = () => { modal.style.display = 'none'; modal.classList.add('hidden'); };

    // ── Calculation values as a form (no raw JSON for the user) ──────────────
    // The stored value is a JSON document; the editor draws it as labelled fields, tables and groups, and writes it back into
    // the hidden `payload` input on save. A shape the editor does not know is shown read-only and left untouched.
    const LABEL = {
        up_to: 'Up to (Rp)', rate: 'Rate (%)', brackets: 'Brackets', max_monthly: 'Maximum per month (Rp)', surcharge: 'Surcharge (%)',
        employer: 'Employer share (%)', employee: 'Employee share (%)', jkk_min: 'JKK minimum (%)', jkk_max: 'JKK maximum (%)', jkm: 'JKM (%)',
        monthly_divisor: 'Monthly divisor (hours)', max_years: 'Maximum term (years)', months_per_year: 'Months per year of service', min_service_months: 'Minimum service (months)',
        severance: 'Severance pay', service_award: 'Service award pay', reasons: 'Reasons for ending employment', label: 'Name', article: 'Article', leave: 'Pays out unused leave',
        six_day: 'Six-day work week (days)', five_day: 'Five-day work week (days)', pkwt_comp: 'PKWT compensation',
        gross_over: 'Monthly income over (Rp)', gross_upto: 'Up to (Rp)', annual_amount: 'Amount per year (Rp)', code: 'Code', description: 'Description',
        min_base: 'Minimum wage base (Rp)', wage_cap: 'Wage ceiling (Rp)', categories: 'TER categories', codes: 'PTKP codes',
        hours: 'Hours in this band', multiplier: 'Pay multiplier (×)', workday: 'Working day', rest_day_5: 'Rest day or public holiday — 5-day work week',
        rest_day_6: 'Rest day or public holiday — 6-day work week'
    };
    const COLS = ['code', 'description', 'gross_over', 'gross_upto', 'up_to', 'annual_amount', 'hours', 'multiplier', 'rate'];   // preferred column order in tables
    const TUPLE_HEAD = { severance: ['Years of service up to', 'Months of pay'], service_award: ['Years of service up to', 'Months of pay'] };
    const PCT = /^(rate|surcharge|employer|employee|jkk_min|jkk_max|jkm)$/;
    const MONEY = /^(up_to|max_monthly|annual_amount|gross_over|gross_upto|min_base|wage_cap)$/;
    // Indonesian number text → number. 60.000.000 / 60000000 / 1.234,56 / 5,5 / 5.5 are all fine; anything else is null.
    function parseNum(text) {
        let s = String(text).replace(/\s|Rp|%/gi, '');
        if (s === '' || !/^[-+]?[\d.,]+$/.test(s)) { return null; }
        if (s.indexOf(',') !== -1) { s = s.replace(/\./g, '').replace(',', '.'); }
        else if (/^\d{1,3}(\.\d{3})+$/.test(s)) { s = s.replace(/\./g, ''); }
        const n = parseFloat(s);
        return isFinite(n) ? n : null;
    }
    const fmtMoney = n => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n);
    const fmtPlain = n => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 6, useGrouping: false }).format(n);
    const human = k => LABEL[k] || (/^[A-Z]$/.test(String(k)) ? 'Category ' + k : null) || String(k).replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());
    const round = (n, d) => Math.round(n * Math.pow(10, d)) / Math.pow(10, d);
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text !== undefined) e.textContent = text; return e; };
    const INPUT = 'w-full border border-gray-200 rounded-md px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200';
    let getter = null, raw = null;

    function leaf(key, val) {
        if (typeof val === 'boolean') {
            const cb = el('input'); cb.type = 'checkbox'; cb.checked = val; cb.className = 'h-4 w-4 rounded border-gray-300';
            return { el: cb, get: () => cb.checked };
        }
        if (typeof val === 'string') {
            const i = el('input', INPUT); i.type = 'text'; i.value = val; i.maxLength = 300;
            return { el: i, get: () => i.value };
        }
        if (val === null || typeof val === 'number') {
            // Friendly number box: Rp prefix + thousand separators for money, % suffix for rates, plain for the rest. Accepts 60.000.000 or
            // 60000000, 5,5 or 5.5; shows a clear message instead of silently dropping what cannot be read.
            const pct = PCT.test(key), money = MONEY.test(key);
            const wrap = el('div', 'relative'), i = el('input', INPUT + (money ? ' pl-8' : '') + (pct ? ' pr-7' : '') + ' text-right');
            i.type = 'text'; i.inputMode = 'decimal'; i.autocomplete = 'off'; i.spellcheck = false;
            i.placeholder = val === null ? (key === 'hours' ? 'All remaining' : 'No limit') : (money ? '0' : '');
            i.required = val !== null;
            const msg = el('p', 'hidden text-[10px] text-red-600 mt-0.5', 'Use numbers only, for example ' + (money ? '60.000.000' : (pct ? '5,5' : '12')) + '.');
            const show = n => n === null ? '' : (money ? fmtMoney(n) : fmtPlain(pct ? round(n * 100, 4) : n));
            i.value = show(val);
            if (money) { wrap.appendChild(el('span', 'absolute left-2 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-gray-400 pointer-events-none', 'Rp')); }
            wrap.appendChild(i);
            if (pct) { wrap.appendChild(el('span', 'absolute right-2 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-gray-400 pointer-events-none', '%')); }
            const box = el('div'); box.appendChild(wrap); box.appendChild(msg);
            const read = () => { const t = i.value.trim(); if (t === '') return { n: null, bad: false }; const n = parseNum(t); return { n: n, bad: n === null }; };
            const mark = bad => { i.classList.toggle('border-red-400', bad); i.classList.toggle('border-gray-200', !bad); msg.classList.toggle('hidden', !bad); i.setCustomValidity(bad ? 'Use numbers only.' : ''); };
            i.addEventListener('input', () => mark(read().bad));
            i.addEventListener('blur', () => { const r = read(); if (!r.bad && r.n !== null) { i.value = money ? fmtMoney(r.n) : fmtPlain(r.n); } mark(r.bad); });
            i.addEventListener('focus', () => { i.select(); });
            return { el: box, get: () => { const r = read(); if (r.bad) { mark(true); throw new Error('number'); } if (r.n === null) return null; return pct ? round(r.n / 100, 8) : r.n; } };
        }
        throw new Error('unsupported');
    }
    const isTuple = a => Array.isArray(a) && a.length && a.every(r => Array.isArray(r) && r.length === a[0].length && r.every(v => v === null || typeof v === 'number'));
    const isRows = a => Array.isArray(a) && a.length && a.every(r => r && typeof r === 'object' && !Array.isArray(r));

    function table(heads, rows, makeRow, blank) {
        const box = el('div', 'overflow-x-auto'), t = el('table', 'w-full text-xs'), hr = el('tr', 'text-[10px] uppercase tracking-wide text-gray-500');
        heads.forEach(h => hr.appendChild(el('th', 'text-left font-semibold px-1 pb-1', h))); hr.appendChild(el('th'));
        const th = el('thead'); th.appendChild(hr); t.appendChild(th);
        const body = el('tbody'); t.appendChild(body); box.appendChild(t);
        const list = [];
        const add = vals => {
            const tr = el('tr'), cells = makeRow(vals), rec = { cells };
            cells.forEach(c => { const td = el('td', 'px-1 py-1 align-middle'); td.appendChild(c.el); tr.appendChild(td); });
            const td = el('td', 'px-1 py-1 w-8'), x = el('button', 'text-gray-400 hover:text-red-600', '✕'); x.type = 'button'; x.title = 'Remove this row'; x.setAttribute('aria-label', 'Remove this row');
            x.addEventListener('click', () => { if (list.length > 1) { tr.remove(); list.splice(list.indexOf(rec), 1); } });
            td.appendChild(x); tr.appendChild(td); body.appendChild(tr); list.push(rec);
        };
        rows.forEach(add);
        const more = el('button', 'mt-1 text-[11px] font-semibold text-indigo-700 hover:underline', '+ Add a row'); more.type = 'button';
        more.addEventListener('click', () => add(blank()));
        const wrap = el('div'); wrap.appendChild(box); wrap.appendChild(more);
        return { el: wrap, rows: list };
    }

    function node(key, val) {
        if (isTuple(val)) {
            const n = val[0].length, heads = TUPLE_HEAD[key] || Array.from({ length: n }, (_, i) => 'Value ' + (i + 1));
            const t = table(heads, val, r => r.map((v, j) => leaf(TUPLE_HEAD[key] ? (j === 0 ? 'up_to_years' : 'months') : 'v' + j, v)), () => Array(n).fill(null).map((_, j) => j === 0 ? 0 : 0));
            return { el: t.el, get: () => t.rows.map(r => r.cells.map(c => c.get())) };
        }
        if (isRows(val)) {
            const keys = Object.keys(val[0]).sort((a, b) => (COLS.indexOf(a) < 0 ? 99 : COLS.indexOf(a)) - (COLS.indexOf(b) < 0 ? 99 : COLS.indexOf(b)));
            const t = table(keys.map(human), val, r => keys.map(k => node(k, r[k])), () => Object.fromEntries(keys.map(k => [k, typeof val[0][k] === 'string' ? '' : (val[0][k] === null ? null : 0)])));
            return { el: t.el, get: () => t.rows.map(r => Object.fromEntries(keys.map((k, i) => [k, r.cells[i].get()]))) };
        }
        if (val && typeof val === 'object' && !Array.isArray(val)) {
            const box = el('div', 'space-y-2'), parts = {};
            Object.keys(val).forEach(k => {
                const v = val[k], complex = v && typeof v === 'object';
                const g = node(k, v);
                if (complex) {
                    const d = el('details', 'rounded-md border border-gray-200 bg-white'); d.open = Object.keys(val).length <= 2;
                    const title = (v && typeof v === 'object' && typeof v.label === 'string' && v.label) ? v.label : human(k);
                    d.appendChild(el('summary', 'cursor-pointer select-none px-3 py-2 font-semibold text-gray-700', title));
                    const inner = el('div', 'p-3'); inner.appendChild(g.el); d.appendChild(inner); box.appendChild(d);
                } else {
                    const row = el('label', 'grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-3 items-center');
                    row.appendChild(el('span', 'text-[11px] font-semibold text-gray-600', human(k)));
                    const cell = el('div', 'sm:col-span-2'); cell.appendChild(g.el); row.appendChild(cell); box.appendChild(row);
                }
                parts[k] = g;
            });
            return { el: box, get: () => Object.fromEntries(Object.keys(parts).map(k => [k, parts[k].get()])) };
        }
        return leaf(key, val);
    }

    // Only Offboarding (final settlement) reads these values to calculate. The other modules keep their own rates (Payroll: Finance › Payroll › Settings),
    // so for them the values are a reference copy of the regulation — say so, instead of promising an effect that does not exist.
    function payNote() {
        const note = $('lPayNote'), mod = $('lMod').value, key = ($('lKey').value || '').trim();
        // Offboarding reads these four rules; its leave-payout divisor comes from Offboarding Settings (the register only cites it).
        const live = mod === 'offboarding' && key !== 'offboarding.leave_divisor';
        note.className = 'text-[11px] mt-1.5 rounded-md px-2.5 py-1.5 ' + (live ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-sky-50 border border-sky-200 text-sky-900');
        if (mod === 'overtime' && key === 'overtime.multipliers') {
            note.className = 'text-[11px] mt-1.5 rounded-md px-2.5 py-1.5 bg-amber-50 border border-amber-200 text-amber-900';
            note.textContent = 'Payroll READS these multipliers only while “Overtime multipliers” is switched ON in Legal Basis › Settings. While it is off, Payroll uses the built-in standard (PP 35/2021). Overtime is paid at all only if it is switched on in Payroll Settings.';
        } else if (live) {
            note.textContent = 'These values are READ by the final-settlement calculator (severance, service award, PKWT compensation, severance tax). Changing them changes the amounts calculated for leavers.';
        } else if (mod === 'offboarding') {
            note.textContent = 'This rule is a REFERENCE for the leave-payout divisor. The number actually used is set in Offboarding Settings.';
        } else {
            note.textContent = 'For this module the values are a REFERENCE copy of the regulation. They do not change any calculation: ' + (mod === 'contract' ? 'the contract rules keep their own limits.' : 'Payroll uses the rates set in Payroll Settings (Finance menu).');
        }
    }
    $('lMod').addEventListener('change', payNote);
    $('lKey').addEventListener('input', payNote);

    window.mountRuleData = function (payload) {
        payNote();
        const host = $('lPayEditor'); host.textContent = ''; getter = null; raw = payload || null; $('lPay').value = payload || '';
        if (!payload) { host.appendChild(el('p', 'text-gray-500', 'This rule has no calculation values. It is a reference to the regulation only.')); return; }
        try {
            const data = JSON.parse(payload), n = node('', data);
            host.appendChild(n.el); getter = n.get;
        } catch (e) {
            host.textContent = '';
            host.appendChild(el('p', 'text-amber-700', 'These calculation values have a layout this form cannot show, so they are kept as they are. Ask the system administrator to change them.'));
        }
    };
    $('legalForm').addEventListener('submit', function (e) {
        if (getter) {
            try { $('lPay').value = JSON.stringify(getter()); }
            catch (err) { e.preventDefault(); alert('Some calculation values are not valid. Check the numbers and try again.'); }
        } else { $('lPay').value = raw || ''; }
    });
})();
</script>
@endpush
@endif
