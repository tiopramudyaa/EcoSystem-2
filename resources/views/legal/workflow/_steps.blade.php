{{-- Jejak langkah persetujuan per putaran + riwayat status. Param: $rounds (round => langkah), $logs, $currentRound. --}}
<div class="space-y-5">
    @foreach($rounds->sortKeysDesc() as $round => $steps)
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Round {{ $round }} @if((int) $round === (int) $currentRound)<span class="normal-case font-normal text-gray-400">(current)</span>@endif
            </p>
            <ol class="space-y-2">
                @foreach($steps as $step)
                    @php
                        $tone = match ($step->status) {
                            'approved' => 'border-green-300 bg-green-50',
                            'rejected' => 'border-red-300 bg-red-50',
                            'waiting'  => 'border-amber-300 bg-amber-50',
                            default    => 'border-gray-200 bg-gray-50',
                        };
                        $actor = $step->actor?->fullNameLabel();
                    @endphp
                    <li class="rounded-lg border {{ $tone }} px-3 py-2.5">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <p class="text-sm font-semibold text-gray-800">{{ $step->order_seq }}. {{ $step->step_name }}</p>
                            <span class="text-[11px] font-semibold uppercase text-gray-500">{{ $step->status === 'waiting' ? 'Waiting' : ucfirst($step->status) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Approver: {{ $step->approverLabel() }}</p>
                        @if($step->acted_at)
                            <p class="text-xs text-gray-600 mt-1">{{ $actor ?? 'Unknown' }} · {{ $step->acted_at->format('d M Y H:i') }}</p>
                        @endif
                        @if($step->notes)<p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $step->notes }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endforeach
    @if($rounds->isEmpty())
        <p class="text-sm text-gray-400">No approval steps yet. They are created when the request is sent to review.</p>
    @endif
</div>
