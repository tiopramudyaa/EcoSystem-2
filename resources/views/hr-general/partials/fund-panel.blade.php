{{--
    Keadaan dana untuk PENYETUJU (halaman detail sisi HR). Pemohon tidak melihatnya.

    Variabel: $fundRows (FundService::summaryForReimbursement / summaryForCashAdvance) · $docWord ('reimbursement' | 'cash advance')

    Kolom "After this document" menjawab pertanyaan penyetuju: bila saya menyetujui ini,
    berapa sisa dananya? Dana baru berkurang saat dokumen disetujui sepenuhnya.
--}}
@php
    $rows = $fundRows ?? [];
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $objectName = ['office' => 'Office', 'project' => 'Project', 'support' => 'Support'];
    $funds = array_values(array_filter($rows, fn ($r) => $r['kind'] === 'fund'));
    $nofunds = array_values(array_filter($rows, fn ($r) => $r['kind'] === 'nofund'));
    $anyOver = collect($funds)->contains(fn ($r) => $r['over']);
    $state = $rows[0]['state'] ?? 'waiting';
@endphp
@if($rows !== [])
<div class="bg-white rounded-xl p-6 shadow-sm" id="fundPanel">
    <div class="flex flex-wrap items-start justify-between gap-2 mb-3 pb-3 border-b border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Budget check</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                @if($state === 'approved')
                    The fund after this {{ $docWord }} was approved.
                @elseif($state === 'voided')
                    This {{ $docWord }} is void. Any fund it used went back.
                @else
                    What is left of the fund if you approve this {{ $docWord }}. The fund only goes down when the last approver approves.
                @endif
            </p>
        </div>
        @if($anyOver && $state === 'waiting')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Over the fund</span>
        @elseif($funds !== [] && $state === 'waiting')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Within the fund</span>
        @endif
    </div>

    @if($funds !== [])
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full text-sm min-w-[560px]">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-3 py-2.5">Fund</th>
                    <th class="px-3 py-2.5 text-right">Allocation</th>
                    <th class="px-3 py-2.5 text-right">Approved</th>
                    <th class="px-3 py-2.5 text-right">Waiting</th>
                    <th class="px-3 py-2.5 text-right">This document</th>
                    <th class="px-3 py-2.5 text-right">{{ $state === 'approved' ? 'Remaining' : 'After this document' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($funds as $r)
                <tr class="align-top">
                    <td class="px-3 py-2.5">
                        <span class="font-medium text-gray-900">{{ $r['label'] }}</span>
                        <span class="block text-xs text-gray-400">{{ $objectName[$r['object']] ?? ucfirst($r['object']) }}</span>
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-gray-700">{{ $rp($r['allocation']) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-gray-700">{{ $rp($r['used']) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-gray-500" title="Other documents still waiting. They do not reduce the fund yet.">{{ $rp($r['pending_others']) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums font-medium text-gray-900">{{ $rp($r['this_document']) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums font-bold {{ $r['over'] ? 'text-red-700' : 'text-gray-900' }}">
                        @if($r['remaining_after'] !== null){{ $rp($r['remaining_after']) }}@else - @endif
                        @if($r['over'] && $state === 'waiting')
                            <span class="block text-xs font-normal text-red-700">
                                {{ $r['policy'] === 'block' ? 'Blocked: the last approval will be refused.' : 'Over the fund. The requester gave a reason.' }}
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @foreach($nofunds as $r)
        <p class="text-xs text-gray-500 mt-3">
            <span class="font-semibold text-gray-700">{{ $r['label'] }}</span> ({{ $objectName[$r['object']] ?? $r['object'] }}) has no fund set, so its budget is not checked.
        </p>
    @endforeach

    <p class="text-[11px] text-gray-400 mt-3">
        Approved = documents already approved. Waiting = other documents still in review (information only).
        Funds are set in Reimbursement Settings › Objects and Funds.
    </p>
</div>
@endif
