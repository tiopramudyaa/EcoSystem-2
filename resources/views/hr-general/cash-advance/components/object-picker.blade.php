{{--
    Konsep Object untuk SATU Cash Advance: pilih Office, Project, atau tiket Support.

    Variabel: $costCenters ['branch' => [{id,label}], 'project' => [{id,label}]] · $val (pembaca nilai lama) · $lookupBase
    Skripnya: public/js/object-picker.js (dimuat oleh form). Nama field datar:
    cost_center_type · charged_branch_id · charged_project_id · charged_ticket_id · fund_category.

    Sisa dana TIDAK ditampilkan ke pemohon (dana terhubung, bukan ditampilkan).
--}}
@php
    $objType = $val('cost_center_type');
    $base    = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 primary-border';
@endphp
<div id="caObjectPicker" data-object-picker data-lookup-base="{{ $lookupBase ?? '' }}" class="md:col-span-2 xl:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1">Object <span class="text-red-600">*</span></label>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <select name="cost_center_type" class="js-object-type {{ $base }}" required aria-label="Object type">
            <option value="">- choose an Object -</option>
            <option value="branch" @selected($objType === 'branch')>Office</option>
            <option value="project" @selected($objType === 'project')>Project</option>
            <option value="support" @selected($objType === 'support')>Support ticket</option>
        </select>

        <div>
            <div data-for="branch" hidden>
                <select name="charged_branch_id" data-searchable="true" data-search-placeholder="Search office…" class="js-object-target {{ $base }}">
                    <option value="">- choose an office -</option>
                    @foreach($costCenters['branch'] as $option)
                        <option value="{{ $option['id'] }}" @selected((int) $val('charged_branch_id') === $option['id'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div data-for="project" hidden>
                <select name="charged_project_id" data-searchable="true" data-search-placeholder="Search project…" class="js-object-target js-project {{ $base }}">
                    <option value="">- choose a project -</option>
                    @foreach($costCenters['project'] as $option)
                        <option value="{{ $option['id'] }}" @selected((int) $val('charged_project_id') === $option['id'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div data-for="support" hidden class="relative">
                <input type="text" class="js-ticket-input js-object-target {{ $base }}" autocomplete="off" maxlength="50"
                       role="combobox" aria-expanded="false" aria-autocomplete="list"
                       placeholder="Search ticket number or description" value="{{ old('ticket_label', $document->charged_to_label ?? '') }}" aria-label="Support ticket">
                <input type="hidden" name="charged_ticket_id" class="js-ticket-id" value="{{ $val('charged_ticket_id') }}">
                <input type="hidden" name="ticket_label" value="{{ old('ticket_label') }}" class="js-ticket-label">
                <div class="js-ticket-panel absolute z-50 left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg text-sm" role="listbox" hidden></div>
            </div>
        </div>
    </div>

    <div class="js-category-wrap mt-3" hidden>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fund category <span class="font-normal normal-case text-gray-400">(optional)</span></label>
        <select name="fund_category" class="js-category {{ $base }}" data-selected="{{ $val('fund_category') }}" aria-label="Fund category">
            <option value="">- none -</option>
        </select>
    </div>

    <p class="text-xs text-gray-400 mt-1">The Object decides who reviews this cash advance. Support tickets come from the Ticket menu.</p>
    @error('cost_center_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
