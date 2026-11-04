{{-- The editable list of Content and Settings: mini sidebar to choose the list, the table, and the add / edit dialog. Expects $tab ('content' | 'settings'), $sections, $key, $section, $rows, $categories, $counts (+ $followUpDays on Settings). --}}
@php
    use App\Models\Compliance\ComplianceOption;
    use App\Support\Compliance\Tones;

    $canCreate = $canDo(config('compliance.slugs.manage'), 'create');
    $canEdit = $canDo(config('compliance.slugs.manage'), 'edit');
    $canDelete = $canDo(config('compliance.slugs.manage'), 'delete');
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 bg-white';
    $label = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
    $doDont = ['do' => 'Do', 'dont' => "Don't"];
    // A list ordered by sort_order is re-ordered in its table: up / down buttons and a drag handle. `new` (the first report status) stays first.
    $reorder = ($section['order'][0] ?? '') === 'sort_order';
    $pinned = fn ($r) => $key === 'statuses' && $r->isSystem();

    // What the edit form is filled with, per row (dates as Y-m-d, times as H:i).
    $payload = $rows->mapWithKeys(fn ($r) => [$r->id => collect($section['fields'])->map(function ($f, $name) use ($r) {
        $v = $r->{$name};
        if ($v instanceof \Carbon\CarbonInterface) return $v->format('Y-m-d');
        if ($f['type'] === 'time') return $v ? substr($v, 0, 5) : '';
        if ($f['type'] === 'bool') return (bool) $v;
        return $v;
    })->all()]);
    $defaults = collect($section['fields'])->map(fn ($f) => $f['default'] ?? ($f['type'] === 'bool' ? false : ''))->all();
@endphp

<div class="w-full px-1 lg:px-2">
    @include('legal.compliance.management.components.tabs')
    @include('partials.accent-remap')
    @include('legal.contracts.components.confirm-forms')
    @include('hr-general.recruitment.components.form-errors')

    @php $hasNav = count($sections) > 1; @endphp
    <div class="{{ $hasNav ? 'lg:grid lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-6 items-start space-y-4 lg:space-y-0' : '' }}">
    @if($hasNav)
        {{-- Mini sidebar: the lists of this tab. Becomes a scrollable row of chips on small screens. --}}
        <nav aria-label="{{ $tab === 'settings' ? 'Settings' : 'Content sections' }}" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden lg:sticky lg:top-24">
            <p class="hidden lg:block px-4 py-3 bg-gray-50 border-b border-gray-100 text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ $tab === 'settings' ? 'Settings' : 'Sections' }}</p>
            <div class="flex lg:block gap-1 p-1.5 lg:p-1.5 overflow-x-auto lg:overflow-visible">
                @php $lastGroup = null; @endphp
                @foreach($sections as $k => $s)
                    @if(!empty($s['group']) && $s['group'] !== $lastGroup)
                        @php $lastGroup = $s['group']; @endphp
                        <p class="hidden lg:block px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $s['group'] }}</p>
                    @endif
                    <a href="{{ route("compliance.manage.{$tab}", ['section' => $k]) }}" @if($key === $k) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs whitespace-nowrap lg:whitespace-normal transition-all {{ $key === $k ? 'primary-gradient text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <i class="fas fa-{{ $s['icon'] }} w-4 text-center"></i>
                        <span class="flex-1">{{ $s['label'] }}</span>
                        <span class="text-[10px] font-bold rounded-full px-1.5 py-0.5 {{ $key === $k ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $counts[$k] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    <div class="space-y-5 min-w-0">
        @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif
        @error('compliance')<div class="text-xs bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3"><i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}</div>@enderror

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-{{ $section['icon'] }} text-gray-400"></i> {{ $section['label'] }} <span class="text-xs font-medium text-gray-400">({{ $counts[$key] }})</span></h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('compliance.index') }}" class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"><i class="fas fa-arrow-up-right-from-square mr-1"></i> Open My Compliance</a>
                @if($canCreate)<button type="button" onclick="openCompliance()" class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm"><i class="fas fa-plus mr-1"></i> Add {{ $section['noun'] }}</button>@endif
            </div>
        </div>

        @if(!empty($section['hint']))
            <p class="text-xs text-gray-500 -mt-2">The choices of a dropdown in the Content tab: {{ $section['hint'] }}. What you add here appears in that dropdown at once. A choice that is already used cannot be deleted — switch it off and it is no longer offered. Built-in choices can be renamed and recoloured but not deleted.</p>
        @endif

        @if($key === 'statuses')
            <p class="text-xs text-gray-500 -mt-2">The steps a compliance report moves through. They fill the status dropdown when a report is followed up, the Reports filters and the export. <strong>New</strong> is where every report starts and always stays; a status that <em>closes the report</em> stops the follow-up clock.</p>
            <form method="POST" action="{{ route('compliance.manage.settings.follow-up') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-end gap-3" data-unsaved-ignore>@csrf
                <div>
                    <label class="{{ $label }}" for="followUpDays"><i class="fas fa-clock mr-1"></i> Follow-up deadline (days)</label>
                    <input id="followUpDays" name="follow_up_days" type="number" min="1" max="365" value="{{ old('follow_up_days', $followUpDays) }}" class="{{ $input }} w-32" @disabled(!$canEdit)>
                    @error('follow_up_days')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <p class="text-[11px] text-gray-400 flex-1 min-w-[14rem] pb-2">Reporters are told to expect an answer within this time; a report that is still open after it shows as past follow-up. Applies to reports sent from now on.</p>
                @if($canEdit)<button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm"><i class="fas fa-floppy-disk mr-1"></i> Save</button>@endif
            </form>
        @endif

        @if($reorder && $canEdit && $rows->count() > 1)
            <p class="text-[11px] text-gray-500 -mt-2"><i class="fas fa-arrows-up-down mr-1 text-gray-400"></i> Change the order here: use the <i class="fas fa-chevron-up text-[9px]"></i> <i class="fas fa-chevron-down text-[9px]"></i> buttons to move an item up or down, or drag it by the <i class="fas fa-grip-vertical text-[9px]"></i> handle. It saves at once and is the order people see.@if($key === 'statuses') <strong>New</strong> always stays first.@endif</p>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <tr>@if($reorder)<th class="px-3 py-3 w-28">Order</th>@endif @foreach($section['columns'] as $c)<th class="px-4 py-3">{{ $c[1] }}</th>@endforeach<th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700" @if($reorder && $canEdit) data-reorder-url="{{ route("compliance.manage.{$tab}.reorder", $key) }}" @endif>
                        @forelse($rows as $r)
                            <tr class="align-top {{ isset($r->is_active) && !$r->is_active ? 'opacity-60' : '' }}" data-id="{{ $r->id }}" @if($pinned($r)) data-pinned @endif>
                                @if($reorder)
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-1.5">
                                            @if($canEdit && !$pinned($r))
                                                <span class="cs-grip text-gray-300 hover:text-gray-500 cursor-grab px-0.5" data-grip title="Drag to move" aria-hidden="true"><i class="fas fa-grip-vertical"></i></span>
                                            @else
                                                <span class="px-0.5 text-gray-200" aria-hidden="true"><i class="fas {{ $pinned($r) ? 'fa-lock' : 'fa-grip-vertical' }}"></i></span>
                                            @endif
                                            <span class="w-5 text-center text-[11px] font-bold text-gray-400 tabular-nums" data-position>{{ $loop->iteration }}</span>
                                            @if($canEdit && !$pinned($r))
                                                <span class="inline-flex flex-col gap-px">
                                                    <button type="button" class="cs-move" data-move="-1" title="Move up" aria-label="Move up"><i class="fas fa-chevron-up"></i></button>
                                                    <button type="button" class="cs-move" data-move="1" title="Move down" aria-label="Move down"><i class="fas fa-chevron-down"></i></button>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                @foreach($section['columns'] as [$attr, $head, $kind])
                                    @php $v = data_get($r, $attr); @endphp
                                    <td class="px-4 py-3 {{ $loop->first ? 'font-semibold text-gray-800' : '' }} max-w-md">
                                        @if($kind === 'toggle')
                                            <button type="button" role="switch" class="cs-sw" aria-checked="{{ $v ? 'true' : 'false' }}" aria-label="{{ $head }}: {{ $r->title ?? $r->name ?? $r->label }}"
                                                    data-toggle data-field="{{ $attr }}" data-url="{{ route("compliance.manage.{$tab}.toggle", [$key, $r->id]) }}" @disabled(!$canEdit)
                                                    title="{{ $canEdit ? 'Click to switch' : 'You may not change this' }}"><span class="cs-sw-dot"></span></button>
                                        @elseif($kind === 'badge')
                                            @if($attr === 'tone')
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ Tones::badge($v) }}">{{ Tones::LABELS[$v] ?? ucfirst((string) $v) }}</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $v === 'do' ? Tones::BADGE['green'] : Tones::BADGE['red'] }}">{{ $doDont[$v] ?? ucfirst((string) $v) }}</span>
                                            @endif
                                        @elseif(str_starts_with($kind, 'option:'))
                                            @php $list = substr($kind, 7); @endphp
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ComplianceOption::badge($list, $v) }}">{{ ComplianceOption::label($list, $v) }}</span>
                                        @elseif($kind === 'date')
                                            {{ $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y') : '—' }}
                                        @elseif($attr === 'label' && isset($r->tone))
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ Tones::badge($r->tone) }}">{{ $v }}</span>@if(($r->is_system ?? false) || ($key === 'statuses' && $r->isSystem()))<span class="ml-1.5 text-[10px] font-medium text-gray-400" title="Built in">built in</span>@endif
                                        @else
                                            {{ \Illuminate\Support\Str::limit((string) ($v ?? '—'), 90) }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3"><div class="flex justify-end gap-1.5">
                                    @if($canEdit)<button type="button" onclick="openCompliance({{ $r->id }})" class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-[11px] font-semibold hover:bg-gray-50">Edit</button>@endif
                                    @if($canDelete && !($key === 'statuses' && $r->isSystem()) && !($r->is_system ?? false))<form method="POST" action="{{ route("compliance.manage.{$tab}.destroy", [$key, $r->id]) }}" data-confirm="Delete this {{ $section['noun'] }}? This cannot be undone." data-unsaved-ignore>@csrf<button class="px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 text-[11px] font-semibold hover:bg-red-50">Delete</button></form>@endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($section['columns']) + 1 + ($reorder ? 1 : 0) }}" class="px-6 py-12 text-center text-sm text-gray-500">No {{ $section['noun'] }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>

@if($canCreate || $canEdit)
    @include('legal.compliance.management.components.catalog-form')
@endif

@if($reorder && $canEdit)
<style>
    .cs-move { width: 1.35rem; height: .9rem; line-height: 1; font-size: .55rem; color: #6b7280; background: #f3f4f6; border: 0; border-radius: .25rem; cursor: pointer; }
    .cs-move:hover:not(:disabled) { background: #e5e7eb; color: #111827; }
    .cs-move:disabled { opacity: .3; cursor: default; }
    tr.cs-dragging { opacity: .45; background: #eff6ff; }
    tr.cs-saved td { animation: cs-flash 1s ease-out; }
    @keyframes cs-flash { from { background: #dcfce7; } to { background: transparent; } }
</style>
@push('scripts')
<script>
(function () {
    const body = document.querySelector('tbody[data-reorder-url]');
    if (!body) return;
    const rowsOf = () => Array.from(body.querySelectorAll(':scope > tr[data-id]'));
    const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
    let timer = null;

    const renumber = () => {
        const rows = rowsOf();
        rows.forEach((tr, i) => {
            tr.querySelector('[data-position]').textContent = i + 1;
            const pinnedAbove = rows[i - 1] && rows[i - 1].hasAttribute('data-pinned');
            const up = tr.querySelector('[data-move="-1"]'), down = tr.querySelector('[data-move="1"]');
            if (up) up.disabled = i === 0 || pinnedAbove;
            if (down) down.disabled = i === rows.length - 1;
        });
    };

    const save = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const ids = rowsOf().map(tr => tr.dataset.id);
            try {
                const form = new FormData(); ids.forEach(id => form.append('ids[]', id));
                const res = await fetch(body.dataset.reorderUrl, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                (window.showNotification || (() => {}))('Order saved.', 'success');
            } catch (e) {
                (window.showNotification || alert)('The order could not be saved. The page will reload.', 'error');
                setTimeout(() => location.reload(), 1200);
            }
        }, 350);
    };

    const flash = tr => { tr.classList.remove('cs-saved'); void tr.offsetWidth; tr.classList.add('cs-saved'); };

    // up / down buttons
    body.addEventListener('click', e => {
        const b = e.target.closest('[data-move]'); if (!b || b.disabled) return;
        const tr = b.closest('tr'), dir = +b.dataset.move;
        const other = dir < 0 ? tr.previousElementSibling : tr.nextElementSibling;
        if (!other || other.hasAttribute('data-pinned') && dir < 0) return;
        dir < 0 ? body.insertBefore(tr, other) : body.insertBefore(other, tr);
        renumber(); flash(tr); save();
        b.focus();
    });

    // drag by the handle
    let dragging = null;
    body.addEventListener('mousedown', e => { const g = e.target.closest('[data-grip]'); if (g) g.closest('tr').draggable = true; });
    body.addEventListener('dragstart', e => {
        const tr = e.target.closest('tr'); if (!tr || !tr.draggable) return;
        dragging = tr; tr.classList.add('cs-dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', tr.dataset.id);
    });
    body.addEventListener('dragover', e => {
        if (!dragging) return;
        e.preventDefault();
        const over = e.target.closest('tr[data-id]'); if (!over || over === dragging) return;
        const after = e.clientY > over.getBoundingClientRect().top + over.offsetHeight / 2;
        const ref = after ? over.nextElementSibling : over;
        if (ref && ref.hasAttribute('data-pinned')) return;   // nothing goes above the pinned first row
        if (ref !== dragging) body.insertBefore(dragging, ref);
    });
    body.addEventListener('dragend', () => {
        if (!dragging) return;
        dragging.classList.remove('cs-dragging'); dragging.draggable = false;
        renumber(); flash(dragging); save(); dragging = null;
    });
    renumber();
})();
</script>
@endpush
@endif
