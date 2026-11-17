{{-- The extra input(s) a chosen period needs. Expects $f (filters) and $inputPlain (input classes). Shown / hidden by the script of the reports page (data-when).
     A day and a range use the app's calendar; a month is a month + a year dropdown; a year is a dropdown. No browser date / month / number boxes. --}}
@php
    $thisYear = now()->year;
    $monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    [$pickedYear, $pickedMonth] = preg_match('/^(\d{4})-(\d{2})$/', $f['month'], $m) ? [(int) $m[1], (int) $m[2]] : [$thisYear, (int) now()->format('n')];
    $years = range($thisYear + 1, $thisYear - 10);
@endphp
<div data-when="date" class="items-center gap-2" style="display: none;"><i class="fas fa-calendar-day text-gray-400 text-xs"></i><input type="text" name="date" value="{{ $f['date'] }}" data-date placeholder="Select day" aria-label="Day" class="{{ $inputPlain }} w-40"></div>
<div data-when="month" class="items-center gap-2" style="display: none;">
    <i class="fas fa-calendar text-gray-400 text-xs"></i>
    <div class="w-36"><select data-month-part="m" aria-label="Month">@foreach($monthNames as $n => $name)<option value="{{ sprintf('%02d', $n) }}" @selected($pickedMonth === $n)>{{ $name }}</option>@endforeach</select></div>
    <div class="w-24"><select data-month-part="y" aria-label="Year of the month">@foreach($years as $y)<option value="{{ $y }}" @selected($pickedYear === $y)>{{ $y }}</option>@endforeach</select></div>
    <input type="hidden" name="month" value="{{ $f['month'] ?: sprintf('%04d-%02d', $pickedYear, $pickedMonth) }}">
</div>
<div data-when="year" class="items-center gap-2" style="display: none;"><i class="fas fa-calendar-days text-gray-400 text-xs"></i>
    <div class="w-28"><select name="year" aria-label="Year">@foreach($years as $y)<option value="{{ $y }}" @selected(((int) ($f['year'] ?: $thisYear)) === $y)>{{ $y }}</option>@endforeach</select></div>
</div>
<div data-when="range" class="items-center gap-2" style="display: none;"><i class="fas fa-arrows-left-right text-gray-400 text-xs"></i><input type="text" name="from" value="{{ $f['from'] }}" data-date placeholder="From" aria-label="From" class="{{ $inputPlain }} w-36"><span class="text-xs text-gray-400">to</span><input type="text" name="to" value="{{ $f['to'] }}" data-date placeholder="To" aria-label="To" class="{{ $inputPlain }} w-36"></div>
