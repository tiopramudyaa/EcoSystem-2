{{-- Jawaban pertanyaan, hanya baca. Param: $questions (koleksi), $answers (question_id => jawaban). --}}
@php
    $fmt = function ($q, $value) {
        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }
        if ($q->type === 'checkbox') {
            $list = json_decode((string) $value, true);

            return is_array($list) ? implode(', ', $list) : (string) $value;
        }

        return (string) $value;
    };
@endphp
@foreach($questions as $q)
    @php $answer = $fmt($q, $answers[$q->id] ?? null); @endphp
    <div class="py-2.5 border-b border-gray-100 last:border-b-0 grid sm:grid-cols-[minmax(0,1fr)_16rem] gap-x-4 gap-y-1">
        <div>
            <p class="text-sm text-gray-800">{{ $q->text_en }}</p>
            @if($q->text_id)<p class="text-xs text-gray-400">{{ $q->text_id }}</p>@endif
        </div>
        <p class="text-sm {{ $answer === null ? 'text-gray-300' : 'font-medium text-gray-900' }} whitespace-pre-line">{{ $answer ?? '—' }}</p>
    </div>
@endforeach
