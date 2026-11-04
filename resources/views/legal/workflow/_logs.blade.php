{{-- Riwayat perpindahan status. Param: $logs. --}}
@if($logs->isEmpty())
    <p class="text-sm text-gray-400">No history yet.</p>
@else
    <ul class="divide-y divide-gray-100">
        @foreach($logs as $log)
            <li class="py-2 text-sm">
                <span class="text-gray-800 font-medium">{{ ucfirst(str_replace('_', ' ', $log->to_status)) }}</span>
                <span class="text-gray-400 text-xs"> · {{ $log->created_at?->format('d M Y H:i') }} · {{ $log->actor_name ?? 'System' }}</span>
                @if($log->note)<p class="text-xs text-gray-600 mt-0.5 whitespace-pre-line">{{ $log->note }}</p>@endif
            </li>
        @endforeach
    </ul>
@endif
