{{--
    One status pill for every approval-style document, so the same state looks the same in every module.

    @include('partials.status-badge', ['status' => 'pending'])                     // label defaults from the status
    @include('partials.status-badge', ['status' => 'overdue', 'label' => '5 days'])  // custom text, status colour

    Known statuses: draft, pending, overdue, approved, rejected, cancelled, paid. Anything else renders as a neutral pill.
--}}
@php
    $key = strtolower(str_replace([' ', '_'], '-', (string) ($status ?? '')));
    $map = [
        'draft'     => ['bg-gray-100 text-gray-600',    'Draft',     'fa-pen'],
        'pending'   => ['bg-amber-100 text-amber-800',  'Pending',   'fa-hourglass-half'],
        'overdue'   => ['bg-red-100 text-red-700',      'Overdue',   'fa-exclamation-circle'],
        'approved'  => ['bg-green-100 text-green-700',  'Approved',  'fa-check-circle'],
        'rejected'  => ['bg-red-100 text-red-700',      'Rejected',  'fa-times-circle'],
        'cancelled' => ['bg-gray-100 text-gray-500',    'Cancelled', 'fa-ban'],
        'paid'      => ['bg-blue-100 text-blue-700',    'Paid',      'fa-money-bill-wave'],
    ];
    [$tone, $default, $icon] = $map[$key] ?? ['bg-gray-100 text-gray-600', ucfirst(str_replace('-', ' ', $key)) ?: '—', 'fa-circle'];
@endphp
{{-- Icon + text, never colour alone, so the state is readable without colour vision. --}}
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $tone }}">
    <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label ?? $default }}
</span>
