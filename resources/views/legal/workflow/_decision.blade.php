{{-- Kotak keputusan approve / reject. Param: $approveUrl, $rejectUrl, $stepName. Hanya dirender bila orang ini boleh memutuskan. --}}
<div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
    <p class="text-sm font-semibold text-indigo-900">Your decision{{ !empty($stepName) ? ' — ' . $stepName : '' }}</p>
    <p class="text-xs primary-text mt-0.5">A note is optional when approving and required when rejecting.</p>
    <form method="POST" class="mt-3 space-y-3" id="decisionForm">
        @csrf
        <textarea name="notes" rows="3" maxlength="2000" placeholder="Note (required to reject)" class="w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">{{ old('notes') }}</textarea>
        <div class="flex gap-2">
            <button type="submit" formaction="{{ $approveUrl }}" class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Approve</button>
            <button type="submit" formaction="{{ $rejectUrl }}" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">Reject</button>
        </div>
    </form>
</div>
