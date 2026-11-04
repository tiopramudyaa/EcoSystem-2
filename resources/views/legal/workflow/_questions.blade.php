{{-- Pertanyaan due diligence / compliance sebagai isian form. Param: $questions (koleksi), $answers (question_id => jawaban). --}}
@foreach($questions as $q)
    @php
        $raw = old('q.' . $q->id, $answers[$q->id] ?? null);
        $multi = $q->type === 'checkbox';
        $selected = $multi ? (is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: [])) : [];
        $options = $q->options ?? [];
    @endphp
    <div class="py-3 border-b border-gray-100 last:border-b-0">
        <p class="text-sm text-gray-800">{{ $q->text_en }} @if($q->is_required)<span class="text-red-500">*</span>@endif</p>
        @if($q->text_id)<p class="text-xs text-gray-400 mt-0.5">{{ $q->text_id }}</p>@endif

        <div class="mt-2">
            @if($q->type === 'radio')
                <div class="flex flex-wrap gap-x-5 gap-y-1.5">
                    @foreach($options as $opt)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="q[{{ $q->id }}]" value="{{ $opt }}" @checked((string) $raw === (string) $opt) class="primary-text"> {{ $opt }}
                        </label>
                    @endforeach
                </div>
            @elseif($multi)
                <div class="flex flex-wrap gap-x-5 gap-y-1.5">
                    @foreach($options as $opt)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="q[{{ $q->id }}][]" value="{{ $opt }}" @checked(in_array($opt, $selected, true)) class="rounded primary-text"> {{ $opt }}
                        </label>
                    @endforeach
                </div>
            @elseif($q->type === 'textarea')
                <textarea name="q[{{ $q->id }}]" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ $raw }}</textarea>
            @else
                <input type="text" name="q[{{ $q->id }}]" value="{{ $raw }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @endif
        </div>
    </div>
@endforeach
