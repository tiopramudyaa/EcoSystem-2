@extends('dashboard')

@section('title', 'Seasonal Theme')
@section('page-title', 'Seasonal Theme')
@section('page-subtitle', __('ui.seasonal.admin.subtitle'))

@section('content')
@php
    $stCatalog = $catalog;
    $stThemeKeys = array_values(array_filter(array_keys($stCatalog), fn ($k) => $k !== 'none'));
    $stYear = (int) now()->format('Y');

    // Saran tanggal untuk tahun berjalan dari katalog. Jendela yang melewati pergantian
    // tahun (Tahun Baru) berakhir di tahun berikutnya. null = tanggalnya berpindah tiap tahun.
    $stSuggest = [];
    foreach ($stThemeKeys as $k) {
        $w = $stCatalog[$k]['window'] ?? null;
        $stSuggest[$k] = $w
            ? ['start' => $stYear . '-' . $w[0], 'end' => ($w[1] < $w[0] ? $stYear + 1 : $stYear) . '-' . $w[1]]
            : null;
    }

    $stSelected = old('theme', $current['theme']);
    $stMode = old('mode', $current['mode']);
    $stHasSaved = count($current['schedule'] ?? []) > 0;
    if (old('schedule') !== null) {
        $stRows = array_values(old('schedule'));
        $stPrefilled = false;
    } elseif ($stHasSaved) {
        $stRows = array_values($current['schedule']);
        $stPrefilled = false;
    } else {
        $stRows = [];
        foreach ($stThemeKeys as $k) {
            if ($stSuggest[$k]) {
                $stRows[] = ['theme' => $k, 'start' => $stSuggest[$k]['start'], 'end' => $stSuggest[$k]['end']];
            }
        }
        $stPrefilled = count($stRows) > 0;
    }
    $stLabels = [];
    foreach ($stThemeKeys as $k) {
        $stLabels[$k] = __($stCatalog[$k]['label_key']);
    }
    $stActiveLabel = $activeToday === 'none'
        ? __('ui.seasonal.admin.active_none')
        : __($stCatalog[$activeToday]['label_key'] ?? 'ui.seasonal.themes.none');
@endphp
<style>
    .st-card { border-color:#e5e7eb; background:transparent; }
    .st-card:hover { border-color:var(--st-card-accent); }
    .st-card.is-selected { border-color:var(--st-card-accent); box-shadow:0 0 0 1px var(--st-card-accent); }
    /* Warna label mengikuti tema terang/gelap halaman (var(--text-color)), bukan hex tetap. */
    .st-card .st-card-label { color:var(--text-color, #111827); opacity:.68; }
    .st-card.is-selected .st-card-label { color:var(--text-color, #111827); opacity:1; }
    .st-mode { border-color:#e5e7eb; }
    .st-mode:has(input:checked) { border-color:#374151; box-shadow:0 0 0 1px #374151; }
</style>
<div class="space-y-6">

    {{-- Flash session sengaja TIDAK dirender di sini: dashboard.blade.php sudah
         menampilkannya lewat showToast(). Merendernya ulang membuat toast dobel. --}}
    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="fas fa-circle-exclamation mt-0.5 text-red-500"></i>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
        <i class="fas fa-circle-info mt-0.5 text-xs text-indigo-500"></i>
        <p class="text-xs leading-relaxed text-indigo-900">{{ __('ui.seasonal.admin.info') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.seasonal-theme.update') }}" class="space-y-6" id="seasonalAdminForm">
        @csrf

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                    <i class="fas fa-palette text-sm"></i>
                </div>
                <div class="min-w-0 flex-1 basis-44">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('ui.seasonal.admin.theme_title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('ui.seasonal.admin.theme_hint') }} {{ __('ui.seasonal.admin.theme_manual') }}</p>
                </div>
                <span class="shrink-0 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-600">
                    {{ __('ui.seasonal.admin.active_today') }}: <strong class="font-semibold text-gray-800">{{ $stActiveLabel }}</strong>
                </span>
            </header>

            <div class="p-6">
                <div class="flex flex-wrap items-center gap-3" id="adminThemePicker">
                    @include('partials.seasonal-icons')
                    @foreach($stCatalog as $sk => $meta)
                        <button type="button" data-season-choice="{{ $sk }}" onclick="selectGlobalSeasonalTheme('{{ $sk }}', this)"
                            style="--st-card-accent:{{ $meta['accent'] }}"
                            class="st-card flex flex-col items-center gap-2 w-28 p-3 border-2 rounded-xl transition-all {{ $stSelected === $sk ? 'is-selected' : '' }}">
                            @if($meta['palette'])
                                <div class="st-tile relative w-full h-12" style="background:linear-gradient(135deg, {{ $meta['palette']['login_from'] }} 55%, {{ $meta['palette']['login_to'] }} 170%)">
                                    @include('partials.seasonal-icon', ['name' => 'icon-' . $sk, 'theme' => $sk, 'size' => 38])
                                    @include('partials.seasonal-poster', ['theme' => $sk])
                                </div>
                            @else
                                <div class="st-tile w-full h-12 bg-gray-100"><i class="fas {{ $meta['icon'] }} text-gray-400"></i></div>
                            @endif
                            <span class="st-card-label text-xs font-medium">{{ __($meta['label_key']) }}</span>
                        </button>
                    @endforeach
                </div>
                @include('partials.seasonal-preview', ['picker' => '#adminThemePicker', 'globalKey' => 'none'])
                <input type="hidden" name="theme" id="globalSeasonalThemeInput" value="{{ $stSelected }}">
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                    <i class="fas fa-calendar-days text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('ui.seasonal.admin.mode_title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('ui.seasonal.admin.mode_hint') }}</p>
                </div>
            </header>

            <div class="space-y-5 p-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach(['manual', 'auto'] as $m)
                        <label class="st-mode flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition">
                            <input type="radio" name="mode" value="{{ $m }}" {{ $stMode === $m ? 'checked' : '' }}
                                   onchange="toggleSeasonalMode()" class="mt-0.5 accent-gray-700">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">{{ __('ui.seasonal.mode.' . $m) }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500">{{ __('ui.seasonal.mode.' . $m . '_hint') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div id="seasonalSchedulePanel" class="space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ __('ui.seasonal.schedule.title') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('ui.seasonal.schedule.hint') }}</p>
                        @if($stPrefilled)
                            <p class="mt-1 text-xs text-gray-500">{{ __('ui.seasonal.schedule.suggested', ['year' => $stYear]) }}</p>
                        @endif
                    </div>
                    <div id="scheduleRows" class="space-y-2"></div>
                    <p id="scheduleEmpty" class="hidden rounded-lg border border-dashed border-gray-300 px-4 py-3 text-xs text-gray-500">{{ __('ui.seasonal.schedule.empty') }}</p>
                    <button type="button" onclick="addScheduleRow()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                        <i class="fas fa-plus text-[10px]"></i> {{ __('ui.seasonal.schedule.add') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                    <i class="fas fa-music text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('ui.seasonal.admin.music_title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('ui.seasonal.admin.music_hint') }}</p>
                </div>
            </header>

            <div class="p-6 space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <select name="sound" id="globalSoundSelect"
                        class="min-w-0 flex-1 basis-44 rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-700 focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="" {{ !$activeSound ? 'selected' : '' }}>{{ __('ui.seasonal.admin.music_none') }}</option>
                        @foreach($availableSounds as $file)
                            <option value="{{ $file }}" {{ $activeSound === $file ? 'selected' : '' }}>{{ $file }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="previewGlobalSound()"
                        class="flex-shrink-0 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                        <i class="fas fa-play text-xs"></i> {{ __('ui.seasonal.admin.preview') }}
                    </button>
                </div>
                <audio id="globalSoundPreview" preload="none"></audio>

                <div>
                    <label class="flex items-center justify-between text-xs font-medium text-gray-600 mb-1.5">
                        <span>{{ __('ui.seasonal.admin.volume') }}</span>
                        <span id="globalVolumeLabel">{{ $activeVolume }}%</span>
                    </label>
                    <input type="range" name="volume" id="globalVolumeInput" min="0" max="100" value="{{ $activeVolume }}"
                        oninput="document.getElementById('globalVolumeLabel').textContent = this.value + '%'"
                        class="w-full accent-gray-700">
                    <p class="text-xs text-gray-400 mt-1">{{ __('ui.seasonal.admin.volume_hint') }}</p>
                </div>

                {{-- Upload BUKAN <form> bersarang (form di dalam form tidak valid HTML -
                     section ini ada di dalam form Save utama): pakai fetch() langsung ke
                     endpoint upload terpisah, lalu reload supaya file baru masuk daftar. --}}
                <div class="rounded-lg border border-dashed border-gray-300 p-4">
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ __('ui.seasonal.admin.upload_title') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="file" id="globalSoundUploadInput" accept=".mp3,.wav,.ogg,.aac"
                            class="flex-1 text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                        <button type="button" onclick="uploadGlobalSound()" id="globalSoundUploadBtn"
                            class="flex-shrink-0 rounded-lg bg-gray-700 px-3 py-2.5 text-sm font-medium text-white hover:bg-gray-800 transition">
                            <i class="fas fa-upload text-xs"></i> {{ __('ui.seasonal.admin.upload') }}
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ __('ui.seasonal.admin.upload_hint') }}
                        <a href="{{ route('admin.sounds') }}" class="text-gray-700 underline hover:text-gray-900">Notif Sounds</a>
                    </p>
                </div>
            </div>
        </section>

        @php
            $stLogin = $current['login'] ?? ['theme' => 'inherit', 'scope' => 'both'];
            $stLoginTheme = old('login.theme', $stLogin['theme']);
            $stLoginScope = old('login.scope', $stLogin['scope']);
        @endphp
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" id="loginThemeCard">
            <header class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                    <i class="fas fa-right-to-bracket text-sm"></i>
                </div>
                <div class="min-w-0 flex-1 basis-44">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('ui.seasonal.admin.login_title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('ui.seasonal.admin.login_hint') }}</p>
                </div>
            </header>
            <div class="space-y-5 p-6">
                <div>
                    <label for="loginThemeSelect" class="mb-1 block text-xs font-medium text-gray-600">{{ __('ui.seasonal.admin.login_theme') }}</label>
                    <select name="login[theme]" id="loginThemeSelect"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 sm:w-72">
                        <option value="inherit" @selected($stLoginTheme === 'inherit')>{{ __('ui.seasonal.admin.login_inherit') }}</option>
                        <option value="none" @selected($stLoginTheme === 'none')>{{ __('ui.seasonal.admin.login_off') }}</option>
                        @foreach($stThemeKeys as $stLk)
                            <option value="{{ $stLk }}" @selected($stLoginTheme === $stLk)>{{ __($stCatalog[$stLk]['label_key']) }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset>
                    <legend class="mb-2 text-xs font-medium text-gray-600">{{ __('ui.seasonal.admin.login_scope') }}</legend>
                    <div class="space-y-2">
                        @foreach(['both', 'page', 'loading'] as $stScope)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="login[scope]" value="{{ $stScope }}" @checked($stLoginScope === $stScope) class="accent-gray-700">
                                <span>{{ __('ui.seasonal.admin.login_scope_' . $stScope) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-400">{{ __('ui.seasonal.admin.login_scope_note') }}</p>
                </fieldset>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pb-2">
            <a href="{{ route('admin.index') }}"
               class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                {{ __('ui.seasonal.admin.cancel') }}
            </a>
            <button type="submit"
                    class="rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-800">
                {{ __('ui.seasonal.admin.save') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const ST_TXT = {
    chooseFile: @json(__('ui.seasonal.toasts.choose_file')),
    uploadFailed: @json(__('ui.seasonal.toasts.upload_failed')),
    uploadNetwork: @json(__('ui.seasonal.toasts.upload_network')),
    incomplete: @json(__('ui.seasonal.toasts.schedule_incomplete')),
    order: @json(__('ui.seasonal.toasts.schedule_order')),
    pick: @json(__('ui.seasonal.schedule.pick_theme')),
    remove: @json(__('ui.seasonal.schedule.remove')),
    theme: @json(__('ui.seasonal.schedule.theme')),
    start: @json(__('ui.seasonal.schedule.start')),
    end: @json(__('ui.seasonal.schedule.end')),
    moves: @json(__('ui.seasonal.schedule.moves')),
};
const ST_LABELS = @json($stLabels);
const ST_SUGGEST = @json($stSuggest);
const ST_ROWS = @json($stRows);

function escAttr(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function updateRowHint(row) {
    const theme = row.querySelector('[data-f="theme"]').value;
    const hint = row.querySelector('[data-hint]');
    // Tema tanpa jendela tetap (Ramadan, Lebaran, Imlek): tanggalnya berpindah tiap tahun.
    const moves = theme !== '' && Object.prototype.hasOwnProperty.call(ST_SUGGEST, theme) && ST_SUGGEST[theme] === null;
    hint.classList.toggle('hidden', !moves);
}

function refreshScheduleEmpty() {
    const n = document.querySelectorAll('#scheduleRows [data-row]').length;
    document.getElementById('scheduleEmpty').classList.toggle('hidden', n > 0);
}

function addScheduleRow(data) {
    const d = data || {};
    const wrap = document.getElementById('scheduleRows');
    const row = document.createElement('div');
    row.setAttribute('data-row', '');
    row.className = 'rounded-lg border border-gray-200 p-3';
    const opts = ['<option value="">' + escAttr(ST_TXT.pick) + '</option>']
        .concat(Object.keys(ST_LABELS).map(k =>
            '<option value="' + escAttr(k) + '"' + (d.theme === k ? ' selected' : '') + '>' + escAttr(ST_LABELS[k]) + '</option>'));
    row.innerHTML =
        '<div class="grid gap-2 sm:grid-cols-[1fr_9.5rem_9.5rem_auto] sm:items-end">' +
            '<label class="block text-xs font-medium text-gray-600">' + escAttr(ST_TXT.theme) +
                '<select data-f="theme" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">' + opts.join('') + '</select></label>' +
            '<label class="block text-xs font-medium text-gray-600">' + escAttr(ST_TXT.start) +
                '<input data-f="start" type="date" value="' + escAttr(d.start) + '" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"></label>' +
            '<label class="block text-xs font-medium text-gray-600">' + escAttr(ST_TXT.end) +
                '<input data-f="end" type="date" value="' + escAttr(d.end) + '" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"></label>' +
            '<button type="button" data-remove class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">' + escAttr(ST_TXT.remove) + '</button>' +
        '</div>' +
        '<p data-hint class="mt-2 hidden text-xs text-amber-700">' + escAttr(ST_TXT.moves) + '</p>';
    wrap.appendChild(row);

    const themeSel = row.querySelector('[data-f="theme"]');
    themeSel.addEventListener('change', function () {
        // Isi saran tanggal bila kedua tanggal masih kosong dan tema punya jendela tetap.
        const s = ST_SUGGEST[themeSel.value];
        const start = row.querySelector('[data-f="start"]');
        const end = row.querySelector('[data-f="end"]');
        if (s && !start.value && !end.value) { start.value = s.start; end.value = s.end; }
        updateRowHint(row);
    });
    row.querySelector('[data-remove]').addEventListener('click', function () {
        row.remove();
        refreshScheduleEmpty();
    });
    updateRowHint(row);
    refreshScheduleEmpty();
}

function toggleSeasonalMode() {
    const auto = document.querySelector('input[name="mode"]:checked')?.value === 'auto';
    document.getElementById('seasonalSchedulePanel').classList.toggle('opacity-60', !auto);
}

// Kirim SEMUA field bersama. Baris jadwal yang benar-benar kosong dibuang (server menolaknya),
// baris yang setengah terisi dihentikan dengan pesan, sisanya diberi indeks berurutan.
document.getElementById('seasonalAdminForm').addEventListener('submit', function (ev) {
    const rows = Array.from(document.querySelectorAll('#scheduleRows [data-row]'));
    const kept = [];
    for (const row of rows) {
        const f = k => row.querySelector('[data-f="' + k + '"]');
        const vals = { theme: f('theme').value, start: f('start').value, end: f('end').value };
        if (!vals.theme && !vals.start && !vals.end) continue;
        if (!vals.theme || !vals.start || !vals.end) {
            ev.preventDefault();
            showNotification(ST_TXT.incomplete, 'warning');
            return;
        }
        if (vals.end < vals.start) {
            ev.preventDefault();
            showNotification(ST_TXT.order, 'warning');
            return;
        }
        kept.push({ row, f });
    }
    rows.forEach(r => { r.querySelectorAll('[data-f]').forEach(el => el.removeAttribute('name')); });
    kept.forEach((item, i) => {
        ['theme', 'start', 'end'].forEach(k => item.f(k).setAttribute('name', 'schedule[' + i + '][' + k + ']'));
    });
});

async function uploadGlobalSound() {
    const input = document.getElementById('globalSoundUploadInput');
    const btn = document.getElementById('globalSoundUploadBtn');
    if (!input.files.length) { showNotification(ST_TXT.chooseFile, 'warning'); return; }

    const fd = new FormData();
    fd.append('file', input.files[0]);

    btn.disabled = true;
    try {
        const res = await fetch('{{ route('admin.seasonal-theme.upload-sound') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: fd,
        });
        if (res.redirected || res.ok) {
            // Upload sukses lalu redirect balik ke halaman ini (respons controller):
            // reload penuh supaya daftar lagu ikut ter-refresh dengan file baru.
            window.location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            showNotification(data.message || ST_TXT.uploadFailed, 'error');
            btn.disabled = false;
        }
    } catch (err) {
        showNotification(ST_TXT.uploadNetwork, 'error');
        btn.disabled = false;
    }
}
function previewGlobalSound() {
    const select = document.getElementById('globalSoundSelect');
    const player = document.getElementById('globalSoundPreview');
    if (!select.value) { player.pause(); return; }
    player.src = '/sounds/' + select.value;
    player.currentTime = 0;
    player.play().catch(() => {});
}
function selectGlobalSeasonalTheme(theme, el) {
    document.getElementById('globalSeasonalThemeInput').value = theme;
    document.querySelectorAll('#adminThemePicker .st-card').forEach(o => o.classList.remove('is-selected'));
    if (el) el.classList.add('is-selected');
}

ST_ROWS.forEach(r => addScheduleRow(r));
refreshScheduleEmpty();
toggleSeasonalMode();
</script>
@endpush
@endsection
