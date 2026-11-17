@extends('dashboard')
@section('title', 'Web report')
@section('page-title', 'Web Report Management')
@section('page-subtitle', 'Web report ' . $report->tracking_code)

@section('content')
@php
    $canEdit = $canDo(config('web-report.slug'), 'edit');
@endphp
<div class="w-full px-1 lg:px-2 space-y-5">
    @include('partials.accent-remap')
    @include('hr-general.recruitment.components.form-errors')

    @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif

    <a href="{{ route('admin.web-reports.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-arrow-left text-xs"></i> Back to Web Report Management</a>

    @include('web-report.components.report-handling', ['updateUrl' => route('admin.web-reports.update', $report->id), 'evidenceUrl' => route('admin.web-reports.evidence', $report->id)])
</div>
@endsection
