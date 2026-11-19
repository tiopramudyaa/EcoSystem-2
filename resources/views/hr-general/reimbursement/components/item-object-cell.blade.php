{{--
    Sel "Object" satu baris biaya (konsep Object): Office / Project / Support.

    Variabel:
      $key       kunci baris ('__KEY__' di cetakan)
      $row       data baris (boleh kosong)
      $branches  [{id, label}] · $projects [{id, label}]

    Hanya kontrol sesuai jenis yang dikirim: yang lain dinonaktifkan oleh
    item-rows-script. Sisa dana tidak ditampilkan ke pemohon (dana terhubung,
    bukan ditampilkan).
--}}
@php
    $type = $row['cost_center_type'] ?? 'branch';
    $base = 'w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-red-800';
@endphp
<div class="js-object-cell space-y-1.5">
    <select name="items[{{ $key }}][cost_center_type]" class="js-object-type {{ $base }}" aria-label="Object type">
        <option value="branch" @selected($type === 'branch')>Office</option>
        <option value="project" @selected($type === 'project')>Project</option>
        <option value="support" @selected($type === 'support')>Support</option>
    </select>

    <div data-for="branch">
        <select name="items[{{ $key }}][branch_id]" data-searchable="true" data-search-placeholder="Search office…" class="js-object-target {{ $base }}">
            <option value="">- choose an office -</option>
            @foreach($branches as $branch)
                <option value="{{ $branch['id'] }}" @selected((string) ($row['branch_id'] ?? '') === (string) $branch['id'])>{{ $branch['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div data-for="project" hidden>
        <select name="items[{{ $key }}][delivery_project_id]" data-searchable="true" data-search-placeholder="Search project…" class="js-object-target js-project {{ $base }}">
            <option value="">- choose a project -</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}" @selected((string) ($row['delivery_project_id'] ?? '') === (string) $project['id'])>{{ $project['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div data-for="support" hidden>
        <input type="text" class="js-ticket-input js-object-target {{ $base }}" list="tickets-{{ $key }}" autocomplete="off" maxlength="50"
               placeholder="Type the ticket number" value="{{ $row['ticket_label'] ?? '' }}" aria-label="Support ticket number">
        <datalist id="tickets-{{ $key }}"></datalist>
        <input type="hidden" name="items[{{ $key }}][ticket_id]" class="js-ticket-id" value="{{ $row['ticket_id'] ?? '' }}">
    </div>

    {{-- Muncul hanya bila project atau tiket ini punya kategori dana. Pembungkusnya yang disembunyikan:
         skrip select-enhance membungkus <select> dengan tombol sendiri, jadi atribut hidden pada select tidak cukup. --}}
    <div class="js-category-wrap" hidden>
        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Fund category <span class="font-normal normal-case text-gray-400">(optional)</span></label>
        <select name="items[{{ $key }}][fund_category]" class="js-category {{ $base }}" data-selected="{{ $row['fund_category'] ?? '' }}" aria-label="Fund category">
            <option value="">- none -</option>
        </select>
    </div>
</div>
