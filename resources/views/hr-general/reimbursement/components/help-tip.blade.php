{{--
    Ikon bantuan (?) untuk satu bagian Reimbursement Settings. Isi dari config/reimbursement_help.php.

    Variabel: $key (wajib, kunci di config) · $form (opsional, id form tempat "Use recommended" mengisi input)

    Ditaruh DI LUAR elemen <h2>/<h3> supaya navigasi bagian Settings, yang membaca teks judul, tidak terpengaruh.
    Seluruh teks di-escape Blade. Popover dibangun oleh help-script dari isi tersembunyi di bawah ini.
--}}
@php
    $help   = (config('reimbursement_help.items') ?? [])[$key ?? ''] ?? null;
    $guided = config('reimbursement_help.mode') === 'guided';
    $topic  = $help['topic'] ?? config('reimbursement_help.topic');
    $uid    = 'help-' . \Illuminate\Support\Str::slug(str_replace('.', '-', $key ?? 'x'));
    $values = $guided ? ($help['recommended']['values'] ?? []) : [];
@endphp
@if($help)
    <span class="inline-flex align-middle print:hidden" data-help-wrap>
        <button type="button"
                class="inline-flex items-center justify-center w-6 h-6 rounded-full text-gray-400 hover:text-red-800 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-800 transition-colors"
                data-help-open="{{ $uid }}" aria-haspopup="dialog" aria-expanded="false" aria-controls="help-popover"
                aria-label="Help: {{ $help['title'] }}" title="Help">
            <i class="fa-regular fa-circle-question text-base" aria-hidden="true"></i>
        </button>

        <div id="{{ $uid }}" hidden data-help-body @if($values) data-help-form="{{ $form ?? '' }}" data-help-values='@json($values)' @endif>
            <p class="text-sm font-bold text-gray-900" data-help-title>{{ $help['title'] }}</p>
            <p class="mt-1.5 text-sm text-gray-700">{{ $help['what'] }}</p>

            @if(!empty($help['options']))
                <dl class="mt-3 space-y-1.5">
                    @foreach($help['options'] as [$label, $text])
                        <div class="text-sm">
                            <dt class="inline font-semibold text-gray-800">{{ $label }}:</dt>
                            <dd class="inline text-gray-600">{{ $text }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if($guided && !empty($help['recommended']['text']))
                <div class="mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-green-800">Recommended</p>
                    <p class="mt-0.5 text-sm text-green-900">{{ $help['recommended']['text'] }}</p>
                </div>
            @endif

            @if($guided && !empty($help['risk']))
                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-800">If chosen wrongly</p>
                    <p class="mt-0.5 text-sm text-amber-900">{{ $help['risk'] }}</p>
                </div>
            @endif

            @if(!$guided && !empty($help['risk']))
                <p class="mt-2 text-sm text-gray-600">{{ $help['risk'] }}</p>
            @endif

            @if($guided)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if($values)
                        <button type="button" data-help-apply class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg primary-gradient hover:opacity-90">Use recommended</button>
                    @endif
                    <a href="{{ route('user-guide.index') }}#topic-{{ $topic }}" target="_blank" rel="noopener"
                       class="px-3 py-1.5 text-xs font-semibold text-red-800 border border-red-200 rounded-lg hover:bg-red-50">Open the guide</a>
                </div>
                @if($values)
                    <p class="mt-2 text-xs text-gray-500">Use recommended only fills in the fields. Nothing is saved until you click the Save button.</p>
                @endif
            @endif
        </div>
    </span>
@endif
