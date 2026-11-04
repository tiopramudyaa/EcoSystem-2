@extends('dashboard')
@section('title', $title . ' Settings')
@section('page-title', $title)
@section('page-subtitle', 'Who approves, in which order, and what is asked. Changes apply to requests submitted from now on.')

@section('content')
@php
    $tabsView = $flow === 'vendor' ? 'legal.vendor-onboarding._tabs' : 'legal.sales-compliance._tabs';
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';
    $label = 'block text-xs font-semibold text-gray-600 mb-1';
    $btn = 'px-3.5 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold';
    $r = fn ($name, ...$args) => route($routePrefix . '.' . $name, ...$args);
@endphp

<div class="w-full px-1 lg:px-2 space-y-6">
    @include($tabsView)

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── Langkah persetujuan ── --}}
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-800">Approval steps</h3>
            <p class="text-xs text-gray-500 mt-0.5">Steps run in order. Each step is decided by a position or by named people — they do not need any menu permission to open and decide the requests that reach them. Requests already in review keep the steps they started with.</p>
        </div>
        <div class="p-5 space-y-3">
            @if($steps->where('is_active', true)->isEmpty())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">No active step. Nothing can be sent to review until one is added.</div>
            @endif

            @foreach($steps as $step)
                <details class="rounded-lg border {{ $step->is_active ? 'border-gray-200' : 'border-gray-200 opacity-60' }}">
                    <summary class="cursor-pointer px-4 py-2.5 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-800">{{ $step->order_seq }}. {{ $step->name }}</span>
                        <span class="text-xs text-gray-500">{{ $step->approverLabel() }}@unless($step->is_active) · switched off @endunless</span>
                    </summary>
                    <div class="px-4 pb-4 pt-2 border-t border-gray-100">
                        @if($canEdit)
                            @include('legal.workflow._step-form', ['action' => $r('steps.update', $step->id), 'step' => $step, 'submit' => 'Save step'])
                        @endif
                        @if($canDelete)
                            <form method="POST" action="{{ $r('steps.destroy', $step->id) }}" class="mt-3" data-confirm="Delete the step “{{ $step->name }}”? Requests already in review keep their own copy." data-confirm-title="Delete step" data-confirm-ok="Delete" data-confirm-variant="danger">@csrf
                                <button class="text-xs font-semibold text-red-600 hover:underline">Delete this step</button>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach

            @if($canCreate)
                <details class="rounded-lg border border-dashed border-indigo-300">
                    <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold primary-text">Add a step</summary>
                    <div class="px-4 pb-4 pt-2 border-t border-gray-100">
                        @include('legal.workflow._step-form', ['action' => $r('steps.store'), 'step' => null, 'submit' => 'Add step'])
                    </div>
                </details>
            @endif
        </div>
    </section>

    {{-- ── Jenis dokumen (vendor) ── --}}
    @if($flow === 'vendor')
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">Document types</h3>
                <p class="text-xs text-gray-500 mt-0.5">What a vendor must provide. A type is switched off, not deleted, so files already uploaded stay readable.</p>
            </div>
            <div class="p-5 space-y-2">
                @foreach($fields as $f)
                    <details class="rounded-lg border border-gray-200 {{ $f->is_active ? '' : 'opacity-60' }}">
                        <summary class="cursor-pointer px-4 py-2.5 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm text-gray-800">{{ $f->label_en }} @if($f->is_required)<span class="text-red-500">*</span>@endif</span>
                            <span class="text-xs text-gray-500">{{ ['all' => 'All vendors', 'b2b' => 'Company', 'personal' => 'Individual'][$f->vendor_type] ?? $f->vendor_type }}@unless($f->is_active) · switched off @endunless</span>
                        </summary>
                        <div class="px-4 pb-4 pt-2 border-t border-gray-100 space-y-3">
                            @if($canEdit)
                                <form method="POST" action="{{ $r('fields.update', $f->id) }}" class="grid md:grid-cols-2 gap-3">@csrf
                                    <div><label class="{{ $label }}">Name (English)</label><input name="label_en" value="{{ $f->label_en }}" class="{{ $input }}" required></div>
                                    <div><label class="{{ $label }}">Name (Indonesian)</label><input name="label_id" value="{{ $f->label_id }}" class="{{ $input }}"></div>
                                    <div><label class="{{ $label }}">Applies to</label>
                                        <select name="vendor_type" class="{{ $input }}">@foreach(['all' => 'All vendors', 'b2b' => 'Company', 'personal' => 'Individual'] as $k => $n)<option value="{{ $k }}" @selected($f->vendor_type === $k)>{{ $n }}</option>@endforeach</select></div>
                                    <div><label class="{{ $label }}">Template link (optional)</label><input name="template_url" value="{{ $f->template_url }}" class="{{ $input }}" placeholder="https://…"></div>
                                    <div><label class="{{ $label }}">Order</label><input type="number" name="sort_order" value="{{ $f->sort_order }}" class="{{ $input }}"></div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-5"><input type="checkbox" name="is_required" value="1" @checked($f->is_required) class="rounded primary-text"> Required</label>
                                    <div class="md:col-span-2"><button class="{{ $btn }}">Save</button></div>
                                </form>
                                <form method="POST" action="{{ $r('fields.toggle', $f->id) }}">@csrf
                                    <button class="text-xs font-semibold {{ $f->is_active ? 'text-red-600' : 'text-green-700' }} hover:underline">{{ $f->is_active ? 'Switch off' : 'Switch on' }}</button>
                                </form>
                            @endif
                        </div>
                    </details>
                @endforeach

                @if($canCreate)
                    <details class="rounded-lg border border-dashed border-indigo-300">
                        <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold primary-text">Add a document type</summary>
                        <form method="POST" action="{{ $r('fields.store') }}" class="px-4 pb-4 pt-3 border-t border-gray-100 grid md:grid-cols-2 gap-3">@csrf
                            <div><label class="{{ $label }}">Name (English)</label><input name="label_en" class="{{ $input }}" required></div>
                            <div><label class="{{ $label }}">Name (Indonesian)</label><input name="label_id" class="{{ $input }}"></div>
                            <div><label class="{{ $label }}">Applies to</label>
                                <select name="vendor_type" class="{{ $input }}">@foreach(['all' => 'All vendors', 'b2b' => 'Company', 'personal' => 'Individual'] as $k => $n)<option value="{{ $k }}">{{ $n }}</option>@endforeach</select></div>
                            <div><label class="{{ $label }}">Template link (optional)</label><input name="template_url" class="{{ $input }}" placeholder="https://…"></div>
                            <div><label class="{{ $label }}">Order</label><input type="number" name="sort_order" value="500" class="{{ $input }}"></div>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-5"><input type="checkbox" name="is_required" value="1" class="rounded primary-text"> Required</label>
                            <div class="md:col-span-2"><button class="{{ $btn }}">Add document type</button></div>
                        </form>
                    </details>
                @endif
            </div>
        </section>
    @endif

    {{-- ── Pertanyaan ── --}}
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-800">Questions</h3>
            <p class="text-xs text-gray-500 mt-0.5">A question is switched off, not deleted, so answers already given stay readable.</p>
        </div>
        <div class="p-5 space-y-2">
            @foreach($questions as $q)
                <details class="rounded-lg border border-gray-200 {{ $q->is_active ? '' : 'opacity-60' }}">
                    <summary class="cursor-pointer px-4 py-2.5 flex flex-wrap items-start justify-between gap-2">
                        <span class="text-sm text-gray-800 max-w-3xl">{{ \Illuminate\Support\Str::limit($q->text_en, 150) }}</span>
                        <span class="text-xs text-gray-500 shrink-0">{{ $q->type }}@if($q->is_required) · required @endif @unless($q->is_active) · switched off @endunless</span>
                    </summary>
                    <div class="px-4 pb-4 pt-2 border-t border-gray-100 space-y-3">
                        @if($canEdit)
                            @include('legal.workflow._question-form', ['action' => $r('questions.update', $q->id), 'q' => $q, 'submit' => 'Save question'])
                            <form method="POST" action="{{ $r('questions.toggle', $q->id) }}">@csrf
                                <button class="text-xs font-semibold {{ $q->is_active ? 'text-red-600' : 'text-green-700' }} hover:underline">{{ $q->is_active ? 'Switch off' : 'Switch on' }}</button>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach

            @if($canCreate)
                <details class="rounded-lg border border-dashed border-indigo-300">
                    <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold primary-text">Add a question</summary>
                    <div class="px-4 pb-4 pt-3 border-t border-gray-100">
                        @include('legal.workflow._question-form', ['action' => $r('questions.store'), 'q' => null, 'submit' => 'Add question'])
                    </div>
                </details>
            @endif
        </div>
    </section>
</div>
@include('legal.workflow._confirm')
@endsection
