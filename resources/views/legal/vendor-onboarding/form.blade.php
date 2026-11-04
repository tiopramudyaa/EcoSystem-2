@extends('dashboard')
@section('title', $reg ? 'Edit ' . $reg->reference_no : 'New vendor registration')
@section('page-title', 'Vendor Onboarding')
@section('page-subtitle', $reg ? $reg->reference_no . ' — ' . $reg->statusLabel() : 'Register a vendor and attach the documents the review needs.')

@section('content')
@php
    $personal = $type === 'personal';
    $v = fn ($name) => old($name, $reg?->{$name});
    $label = 'block text-xs font-semibold text-gray-600 mb-1';
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';
    $star = fn ($col) => isset($required[$col]) ? '<span class="text-red-500">*</span>' : '';
    $action = $reg ? route('legal.vendor-onboarding.update', $reg) : route('legal.vendor-onboarding.store');
    $names = [
        'company_name' => $personal ? 'Full name' : 'Company name', 'company_address' => $personal ? 'Address' : 'Company address',
        'company_phone' => $personal ? 'Phone' : 'Company phone', 'company_email' => $personal ? 'Email' : 'Company email',
        'company_website' => $personal ? 'Website / portfolio' : 'Company website', 'npwp' => 'Tax ID (NPWP)',
        'nib' => $personal ? 'National ID number (NIK)' : 'Business Identification Number (NIB)',
        'business_type' => $personal ? 'Expertise' : 'Business type', 'product_list' => $personal ? 'Services offered' : 'Products / services',
    ];
@endphp

<div class="w-full px-1 lg:px-2 space-y-5">
    @include('legal.vendor-onboarding._tabs')

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($reg && count($readiness))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Still needed before it can be submitted:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($readiness as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @unless($reg)<input type="hidden" name="vendor_type" value="{{ $type }}">@endunless

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800">1. {{ $personal ? 'Individual' : 'Company' }} profile</h3>
                <span class="text-xs text-gray-400">{{ $personal ? 'Individual vendor' : 'Company vendor' }} · fields marked * are needed to submit</span>
            </div>
            <div class="p-5 grid md:grid-cols-2 gap-4">
                @foreach(['company_name', 'company_email', 'company_phone', 'company_website', 'npwp', 'nib', 'business_type'] as $col)
                    <div>
                        <label class="{{ $label }}">{{ $names[$col] }} {!! $star($col) !!}</label>
                        <input name="{{ $col }}" value="{{ $v($col) }}" maxlength="255" class="{{ $input }}" @if($col === 'company_name') required @endif>
                    </div>
                @endforeach
                <div class="md:col-span-2">
                    <label class="{{ $label }}">{{ $names['company_address'] }} {!! $star('company_address') !!}</label>
                    <textarea name="company_address" rows="2" maxlength="2000" class="{{ $input }}">{{ $v('company_address') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}">{{ $names['product_list'] }} {!! $star('product_list') !!}</label>
                    <textarea name="product_list" rows="2" maxlength="3000" class="{{ $input }}">{{ $v('product_list') }}</textarea>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">2. Bank account</h3></div>
            <div class="p-5 grid md:grid-cols-3 gap-4">
                @foreach(['bank_name' => 'Bank name', 'bank_account_no' => 'Account number', 'bank_account_name' => 'Account holder'] as $col => $name)
                    <div><label class="{{ $label }}">{{ $name }} {!! $star($col) !!}</label><input name="{{ $col }}" value="{{ $v($col) }}" maxlength="255" class="{{ $input }}"></div>
                @endforeach
            </div>
        </section>

        @unless($personal)
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">3. Person in charge (PIC)</h3></div>
                <div class="p-5 grid md:grid-cols-3 gap-4">
                    @foreach(['pic_name' => 'PIC name', 'pic_phone' => 'PIC phone', 'pic_email' => 'PIC email'] as $col => $name)
                        <div><label class="{{ $label }}">{{ $name }} {!! $star($col) !!}</label><input name="{{ $col }}" value="{{ $v($col) }}" maxlength="255" class="{{ $input }}"></div>
                    @endforeach
                </div>
            </section>
        @endunless

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">{{ $personal ? '3' : '4' }}. Documents</h3></div>
            <div class="p-5 space-y-4">
                <p class="text-xs text-gray-500">PDF, JPG, PNG or WEBP, up to 50 MB each. Documents are private: they are shown only to people who can open this registration. You can pick several files for one document type.</p>
                @foreach($fields as $f)
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ $f->label_en }} @if($f->is_required)<span class="text-red-500">*</span>@endif
                                @if($f->label_id && $f->label_id !== $f->label_en)<span class="text-xs text-gray-400 font-normal"> · {{ $f->label_id }}</span>@endif</p>
                            @if($f->template_url)<a href="{{ $f->template_url }}" target="_blank" rel="noopener" class="text-xs font-semibold primary-text hover:underline">Download template</a>@endif
                        </div>
                        @foreach(($documents[$f->id] ?? collect()) as $d)
                            <div class="flex items-center justify-between gap-3 mt-2 rounded bg-gray-50 px-3 py-1.5">
                                <span class="text-sm text-gray-700 truncate">{{ $d->original_name }}</span>
                                <span class="flex items-center gap-3 shrink-0">
                                    <a href="{{ route('legal.vendor-onboarding.documents.download', $d) }}" target="_blank" class="text-xs font-semibold primary-text hover:underline">Open</a>
                                    <button type="submit" form="del-doc-{{ $d->id }}" class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                </span>
                            </div>
                        @endforeach
                        <input type="file" name="files[{{ $f->id }}][]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-2 text-sm">
                    </div>
                @endforeach
            </div>
        </section>

        @if($questions->count())
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">{{ $personal ? '4' : '5' }}. Due diligence declarations</h3></div>
                <div class="p-5">@include('legal.workflow._questions', ['questions' => $questions, 'answers' => $answers])</div>
            </section>
        @endif

        <div class="flex items-center gap-3">
            <button class="px-5 py-2 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">Save</button>
            <a href="{{ $reg ? route('legal.vendor-onboarding.show', $reg) : route('legal.vendor-onboarding.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>

    {{-- Form hapus dokumen ditaruh DI LUAR form utama (form tidak boleh bersarang); tombolnya menunjuk lewat atribut form=. --}}
    @foreach($documents->flatten() as $d)
        <form id="del-doc-{{ $d->id }}" method="POST" action="{{ route('legal.vendor-onboarding.documents.destroy', $d) }}" data-confirm="Remove {{ $d->original_name }}?" data-confirm-title="Remove document" data-confirm-ok="Remove" data-confirm-variant="danger">@csrf</form>
    @endforeach

    @if($reg)
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">Save first if you changed anything above, then submit the registration.</p>
                <form method="POST" action="{{ route('legal.vendor-onboarding.submit', $reg) }}" data-confirm="Submit {{ $reg->reference_no }}? You can still send it to review afterwards." data-confirm-title="Submit registration" data-confirm-ok="Submit">@csrf
                    <button @disabled($reg->status !== 'draft') class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold disabled:opacity-40">Submit registration</button>
                </form>
            </div>
        </section>
    @endif
</div>
@include('legal.workflow._confirm')
@endsection
