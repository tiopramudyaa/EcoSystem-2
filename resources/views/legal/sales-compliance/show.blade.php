@extends('dashboard')
@section('title', $req->request_no)
@section('page-title', 'Sales Compliance Approval')
@section('page-subtitle', $req->request_no . ' — ' . $req->project_name)

@section('content')
@php
    $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 2, '.', ',');
    $variance = $req->priceVariancePct();
    $current = $req->currentApproval();
@endphp

<div class="w-full px-1 lg:px-2 space-y-5">
    @include('legal.sales-compliance._tabs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $backRoute }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <div class="flex flex-wrap items-center gap-2">
            @include('legal.workflow._status', ['status' => $req->status, 'label' => $req->statusLabel()])
            @if($owner && $canEdit && $req->isEditable())
                <a href="{{ route('legal.sales-compliance.edit', $req) }}" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Edit draft</a>
            @endif
            @if($owner && $canEdit && $req->status === 'rejected')
                <form method="POST" action="{{ route('legal.sales-compliance.reopen', $req) }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">Reopen to correct</button>
                </form>
            @endif
            @if($owner && $canEdit && in_array($req->status, ['draft', 'in_review'], true))
                <form method="POST" action="{{ route('legal.sales-compliance.cancel', $req) }}" data-confirm="Cancel {{ $req->request_no }}? It leaves the review and cannot be reopened." data-confirm-title="Cancel request" data-confirm-ok="Cancel request" data-confirm-variant="danger">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-red-200 text-sm font-semibold text-red-600 hover:bg-red-50">Cancel request</button>
                </form>
            @endif
            @if($owner && $canDelete && $req->isEditable() && !$req->submitted_at && $req->round === 1)
                <form method="POST" action="{{ route('legal.sales-compliance.destroy', $req) }}" data-confirm="Delete draft {{ $req->request_no }} with its files? This cannot be undone." data-confirm-title="Delete draft" data-confirm-ok="Delete" data-confirm-variant="danger">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-red-200 text-sm font-semibold text-red-600 hover:bg-red-50">Delete draft</button>
                </form>
            @endif
        </div>
    </div>

    @if($req->isEditable() && count($readiness))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Still needed before it can be submitted:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($readiness as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    @if($canDecide && $current)
        @include('legal.workflow._decision', [
            'approveUrl' => route('legal.sales-compliance.approve', $req), 'rejectUrl' => route('legal.sales-compliance.reject', $req), 'stepName' => $current->step_name,
        ])
    @elseif($current && !$canDecide && $decideWhy)
        <p class="text-xs text-gray-400">{{ $decideWhy }}</p>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5 min-w-0">
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">The deal</h3></div>
                <dl class="p-5 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-xs text-gray-500">Sales person</dt><dd class="font-medium text-gray-900">{{ $req->employee?->fullNameLabel() ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Project</dt><dd class="font-medium text-gray-900">{{ $req->project_name }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Customer</dt><dd class="font-medium text-gray-900">{{ $req->customer_name }}@if($req->customer) <span class="text-xs text-gray-400">({{ $req->customer->customer_code }})</span>@endif</dd></div>
                    <div><dt class="text-xs text-gray-500">Customer PIC</dt><dd class="font-medium text-gray-900">{{ $req->pic_name }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Meeting</dt><dd class="font-medium text-gray-900">{{ $req->has_meeting ? 'Yes — ' . $req->meeting_location : 'No' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Hospitality</dt><dd class="font-medium text-gray-900 whitespace-pre-line">{{ $req->has_hospitality ? 'Yes — ' . $req->hospitality_detail : 'No' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Documents received</dt><dd class="font-medium text-gray-900">{{ $req->documents_received ? implode(', ', $req->documents_received) : '—' }}</dd></div>
                </dl>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Price</h3></div>
                <dl class="p-5 grid sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-xs text-gray-500">Final price</dt><dd class="font-semibold text-gray-900">{{ $money($req->final_price) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">MRP price</dt><dd class="font-semibold text-gray-900">{{ $money($req->mrp_price) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Difference</dt>
                        <dd class="font-semibold {{ $variance !== null && $variance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $variance === null ? '—' : ($variance > 0 ? '+' : '') . $variance . '% vs MRP' }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-xs text-gray-500">Justification</dt><dd class="text-gray-900 whitespace-pre-line">{{ $req->price_justification ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Payment method</dt><dd class="font-medium text-gray-900">{{ $req->payment_method ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Contract value</dt><dd class="font-medium text-gray-900">{{ $money($req->contract_value) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Contract date</dt><dd class="font-medium text-gray-900">{{ $req->contract_date?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Documents</h3></div>
                <div class="p-5 space-y-2">
                    @forelse($req->attachments as $a)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $a->original_name }}</p>
                                <p class="text-xs text-gray-400">{{ \App\Models\Legal\SalesCompliance\SalesComplianceAttachment::TYPES[$a->file_type] ?? $a->file_type }} · {{ number_format($a->size / 1024, 0) }} KB</p>
                            </div>
                            <a href="{{ route('legal.sales-compliance.attachments.download', $a) }}" target="_blank" class="text-xs font-semibold primary-text hover:underline shrink-0">Open</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No documents.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Compliance declarations</h3></div>
                <div class="p-5 space-y-5">
                    @foreach($questions as $section => $items)
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide primary-text mb-1">{{ $section ?: 'General' }}</p>
                            @include('legal.workflow._answers', ['questions' => $items, 'answers' => $answers])
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="space-y-5 min-w-0">
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Approval steps</h3></div>
                <div class="p-5">@include('legal.workflow._steps', ['rounds' => $rounds, 'currentRound' => $req->round])</div>
            </section>
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">History</h3></div>
                <div class="px-5 py-3">@include('legal.workflow._logs', ['logs' => $req->logs])</div>
            </section>
        </div>
    </div>
</div>
@include('legal.workflow._confirm')
@endsection
