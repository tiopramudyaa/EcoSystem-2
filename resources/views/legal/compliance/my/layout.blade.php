@extends('dashboard')
@section('title', 'My Compliance')
@section('page-title', 'My Compliance')
@section('page-subtitle', 'Ethics, pricing, SAP and reporting rules — and where to report a violation.')

{{-- Shared frame of the My Compliance pages (index · knowledge · article · rules · training · report · search): tabs and search bar. Each page fills "my-content". --}}
@section('content')
@once
@push('styles')
<style>
    /* Everything here follows the Accent colour (Settings → Appearance); green / red / amber keep their meaning. */
    .cp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; box-shadow: 0 1px 2px rgba(0, 0, 0, .04); }
    .cp-link:hover { border-color: rgba(var(--primary-rgb), .45); box-shadow: 0 2px 8px rgba(var(--primary-rgb), .10); }
    .cp-link { transition: border-color .15s, box-shadow .15s; }
    .cp-tint { background: rgba(var(--primary-rgb), .08); color: rgb(var(--primary-rgb)); }
    .cp-tint-strong { background: rgba(var(--primary-rgb), .14); color: rgb(var(--primary-dark-rgb)); }
    .cp-accent { color: rgb(var(--primary-rgb)); }
    .cp-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
    .cp-section-title { font-size: .875rem; font-weight: 600; color: #1f2937; }
</style>
@endpush
@endonce
@php $canManage = $canDo(config('compliance.slugs.manage')); @endphp

<div class="w-full px-1 lg:px-2 space-y-6">
    @include('legal.compliance.my.components.tabs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('compliance.search') }}" class="flex gap-2 flex-1 min-w-[16rem] max-w-xl">
            <input type="search" name="q" value="{{ $q ?? '' }}" minlength="{{ config('compliance.portal.search_min_chars') }}" placeholder="Search articles and rules…" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white">
            <button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90" aria-label="Search"><i class="fas fa-magnifying-glass"></i></button>
        </form>
        @if($canManage)
            <a href="{{ route('compliance.manage.index') }}" class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"><i class="fas fa-pen mr-1"></i> Manage content</a>
        @endif
    </div>

    @yield('my-content')
</div>
@endsection
