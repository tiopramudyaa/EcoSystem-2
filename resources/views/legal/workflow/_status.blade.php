{{-- Lencana status dokumen Sales Compliance / Vendor Onboarding. Param: $status (kunci), $label (teks). --}}
@php
    $tone = match ($status) {
        'approved', 'verified' => 'bg-green-50 text-green-700 border-green-200',
        'rejected'             => 'bg-red-50 text-red-700 border-red-200',
        'in_review'            => 'bg-amber-50 text-amber-700 border-amber-200',
        'submitted'            => 'bg-blue-50 text-blue-700 border-blue-200',
        default                => 'bg-gray-100 text-gray-600 border-gray-200',
    };
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-semibold {{ $tone }}">{{ $label }}</span>
