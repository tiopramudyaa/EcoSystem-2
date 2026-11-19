{{--
    Konsep Object: pratinjau penyetuju dan, bila perlu, alasan melebihi dana.

    Diisi oleh item-rows-script (#objectPreview). Pratinjau hanya MEMBACA lewat
    ReimbursementObjectLookupController::preview, yang memakai pembentuk
    penyetuju dan pemeriksa dana yang sama dengan pengiriman, sehingga yang
    terlihat sama dengan yang terjadi saat dikirim. Pemohon tidak memilih
    penyetuju dan tidak melihat sisa dana.
--}}
<div class="md:col-span-2" id="objectPreview">
    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Reviewed by (assigned automatically)</label>
    <div class="px-3 py-3 border border-gray-200 rounded-lg bg-gray-50 min-h-[2.375rem]">
        <div class="flex flex-wrap gap-1.5 text-sm text-gray-500" data-preview-steps>
            <span class="text-gray-400">Choose an Object on each item to see who will review this document.</span>
        </div>
        <p class="text-xs mt-2 text-gray-500" data-preview-note></p>
    </div>
    <p class="text-xs text-gray-400 mt-1">The reviewers follow the Object of each item. You do not need to choose them.</p>

    <div id="overAllocationBox" class="mt-3" @if(!old('over_allocation_reason')) hidden @endif>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5" for="overAllocationReason">
            Reason for exceeding the fund <span class="text-red-600">*</span>
        </label>
        <textarea id="overAllocationReason" name="over_allocation_reason" rows="2" maxlength="500"
                  placeholder="Explain why this amount is needed (at least 10 characters)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-red-800">{{ old('over_allocation_reason') }}</textarea>
        <p class="text-xs text-amber-700 mt-1">The amount is above the fund set for an Object on this document. You can still submit with a reason, and reviewers will see it.</p>
        @error('over_allocation_reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
</div>
