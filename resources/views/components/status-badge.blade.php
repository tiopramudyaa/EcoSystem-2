@props([
    'status' => '',
    'label' => null,
])
@php
    // One colour per meaning, shared by every approval-style document (leave, overtime, reimbursement, cash advance, KPI, letters...).
    // Unknown words fall back to grey, so a new status never renders as an unstyled blob.
    $key = strtolower(trim((string) $status));
    $tone = match (true) {
        in_array($key, ['approved', 'paid', 'active', 'published', 'completed', 'done', 'accepted', 'verified', 'signed', 'hr_approved', 'generated'], true) => 'bg-green-100 text-green-700 ring-green-200',
        in_array($key, ['pending', 'submitted', 'in_review', 'in review', 'in_progress', 'waiting', 'awaiting_payment', 'sent'], true) => 'bg-amber-100 text-amber-700 ring-amber-200',
        in_array($key, ['rejected', 'void', 'voided', 'cancelled', 'canceled', 'failed', 'hr_rejected', 'expired'], true) => 'bg-red-100 text-red-700 ring-red-200',
        in_array($key, ['revision', 'returned', 'needs_revision'], true) => 'bg-orange-100 text-orange-700 ring-orange-200',
        in_array($key, ['draft', 'inactive', 'skipped'], true) => 'bg-gray-100 text-gray-600 ring-gray-200',
        default => 'bg-gray-100 text-gray-600 ring-gray-200',
    };
    $text = $label ?? ucwords(str_replace('_', ' ', preg_replace('/^hr_/', '', $key)));
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', $tone]) }}>{{ $text }}</span>
