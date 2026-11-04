{{-- One training session in a list; click opens the details modal. Expects $s (ComplianceSession). --}}
@php use App\Models\Compliance\ComplianceOption; @endphp
<div onclick="openSession({{ \Illuminate\Support\Js::from($s->modalData()) }})" class="cp-card cp-link flex items-stretch overflow-hidden cursor-pointer {{ $s->status === 'completed' ? 'opacity-80' : '' }}">
    <div class="w-20 cp-tint flex flex-col items-center justify-center py-4 shrink-0">
        <span class="text-2xl font-bold leading-none">{{ $s->session_date->format('d') }}</span><span class="text-[10px] font-semibold uppercase mt-0.5">{{ $s->session_date->format('M') }}</span>
        <span class="text-[10px] text-gray-400 mt-1 uppercase">{{ \Illuminate\Support\Str::limit(ComplianceOption::label('session_period', $s->session_type), 9, '') }}</span>
    </div>
    <div class="flex-1 min-w-0 p-4">
        <p class="text-sm font-semibold text-gray-900">{{ $s->title }}</p>
        @if($s->description)<p class="text-xs text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($s->description, 110) }}</p>@endif
        <p class="text-[11px] text-gray-400 mt-2">@if($s->start_time)<i class="far fa-clock"></i> {{ substr($s->start_time, 0, 5) }}@if($s->end_time)–{{ substr($s->end_time, 0, 5) }}@endif · @endif{{ $s->duration_min }} min @if($s->facilitator)· {{ $s->facilitator }}@endif</p>
    </div>
    <div class="p-4 flex flex-col items-end justify-center gap-1 shrink-0"><span class="px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase {{ ComplianceOption::badge('session_status', $s->status) }}">{{ ComplianceOption::label('session_status', $s->status) }}</span><span class="text-[11px] font-medium cp-accent">Details →</span></div>
</div>
