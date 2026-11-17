@extends('dashboard')
@section('title', 'Compliance Management')
@section('page-title', 'Compliance Management')
@section('page-subtitle', 'Report ' . $report->tracking_code)

@section('content')
@php $canEdit = $canDo(config('compliance.slugs.manage'), 'edit'); @endphp
<div class="w-full px-1 lg:px-2">
    @include('legal.compliance.management.components.tabs')
    @include('partials.accent-remap')
    @include('hr-general.recruitment.components.form-errors')

    <div class="space-y-5 min-w-0">
        @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif
        <a href="{{ route('compliance.manage.reports') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-arrow-left text-xs"></i> Back to Reports</a>

        @include('web-report.components.report-handling', ['updateUrl' => route('compliance.manage.report-update', $report->id), 'evidenceUrl' => route('compliance.manage.report-evidence', $report->id)])
    </div>
</div>
@endsection
