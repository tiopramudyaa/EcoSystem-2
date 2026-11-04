{{-- Form pertanyaan (tambah / ubah). Param: $action, $q (null = baru), $submit, $flow. Sales memakai "section"; vendor memakai "applies to". --}}
@php
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';
    $label = 'block text-xs font-semibold text-gray-600 mb-1';
    $optionsText = old('options', $q && $q->options ? implode("\n", $q->options) : '');
@endphp
<form method="POST" action="{{ $action }}" class="grid md:grid-cols-2 gap-3">@csrf
    <div class="md:col-span-2"><label class="{{ $label }}">Question (English)</label><textarea name="text_en" rows="2" maxlength="2000" class="{{ $input }}" required>{{ old('text_en', $q?->text_en) }}</textarea></div>
    <div class="md:col-span-2"><label class="{{ $label }}">Question (Indonesian, optional)</label><textarea name="text_id" rows="2" maxlength="2000" class="{{ $input }}">{{ old('text_id', $q?->text_id) }}</textarea></div>
    <div>
        <label class="{{ $label }}">Answer type</label>
        <select name="type" class="{{ $input }}">
            @foreach(['radio' => 'Choose one', 'checkbox' => 'Choose several', 'text' => 'Short text', 'textarea' => 'Long text'] as $k => $n)
                <option value="{{ $k }}" @selected(old('type', $q?->type ?? 'radio') === $k)>{{ $n }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $label }}">Order</label>
        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $q?->sort_order ?? 500) }}" class="{{ $input }}">
    </div>
    <div class="md:col-span-2">
        <label class="{{ $label }}">Choices (one per line — for “Choose one / several”)</label>
        <textarea name="options" rows="3" class="{{ $input }}">{{ $optionsText }}</textarea>
    </div>
    @if($flow === 'vendor')
        <div>
            <label class="{{ $label }}">Applies to</label>
            <select name="vendor_type" class="{{ $input }}">
                @foreach(['all' => 'All vendors', 'b2b' => 'Company', 'personal' => 'Individual'] as $k => $n)
                    <option value="{{ $k }}" @selected(old('vendor_type', $q?->vendor_type ?? 'all') === $k)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div><label class="{{ $label }}">Section</label><input name="section" value="{{ old('section', $q?->section) }}" maxlength="255" class="{{ $input }}" placeholder="e.g. A. Opportunity Identification"></div>
    @endif
    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-5">
        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $q?->is_required ?? false)) class="rounded primary-text"> Required to submit
    </label>
    <div class="md:col-span-2"><button class="px-3.5 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">{{ $submit }}</button></div>
</form>
