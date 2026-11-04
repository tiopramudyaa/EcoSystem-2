@extends('dashboard')
@section('title', $reg->reference_no)
@section('page-title', 'Vendor Onboarding')
@section('page-subtitle', $reg->reference_no . ' — ' . $reg->company_name)

@section('content')
@php
    $personal = $reg->isPersonal();
    $current = $reg->currentApproval();
    $names = [
        'company_name' => $personal ? 'Full name' : 'Company name', 'company_email' => $personal ? 'Email' : 'Company email',
        'company_phone' => $personal ? 'Phone' : 'Company phone', 'company_website' => $personal ? 'Website / portfolio' : 'Company website',
        'npwp' => 'Tax ID (NPWP)', 'nib' => $personal ? 'National ID number (NIK)' : 'NIB',
        'business_type' => $personal ? 'Expertise' : 'Business type',
    ];
@endphp

<div class="w-full px-1 lg:px-2 space-y-5">
    @include('legal.vendor-onboarding._tabs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $backRoute }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <div class="flex flex-wrap items-center gap-2">
            @include('legal.workflow._status', ['status' => $reg->status, 'label' => $reg->statusLabel()])
            @if($canEdit && $reg->isEditable())
                <a href="{{ route('legal.vendor-onboarding.edit', $reg) }}" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
            @endif
            @if($canEdit && $reg->status === 'submitted')
                <form method="POST" action="{{ route('legal.vendor-onboarding.start-review', $reg) }}" data-confirm="Send {{ $reg->reference_no }} to review? The first reviewer is notified and the data can no longer be edited." data-confirm-title="Start review" data-confirm-ok="Start review">@csrf
                    <button class="px-3 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">Start review</button>
                </form>
            @endif
            @if($canEdit && $reg->status === 'rejected')
                <form method="POST" action="{{ route('legal.vendor-onboarding.reopen', $reg) }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">Reopen to correct</button>
                </form>
            @endif
            @if($canDelete && $reg->status === 'draft' && !$reg->submitted_at && $reg->round === 1)
                <form method="POST" action="{{ route('legal.vendor-onboarding.destroy', $reg) }}" data-confirm="Delete draft {{ $reg->reference_no }} with its documents? This cannot be undone." data-confirm-title="Delete draft" data-confirm-ok="Delete" data-confirm-variant="danger">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-red-200 text-sm font-semibold text-red-600 hover:bg-red-50">Delete draft</button>
                </form>
            @endif
        </div>
    </div>

    @if($reg->isEditable() && count($readiness))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Still needed before it can be submitted:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($readiness as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    @if($reg->status === 'rejected' && $reg->admin_note)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><span class="font-semibold">Rejection note:</span> {{ $reg->admin_note }}</div>
    @endif

    @if($canDecide && $current)
        @include('legal.workflow._decision', [
            'approveUrl' => route('legal.vendor-onboarding.approve', $reg), 'rejectUrl' => route('legal.vendor-onboarding.reject', $reg), 'stepName' => $current->step_name,
        ])
    @elseif($current && !$canDecide && $decideWhy)
        <p class="text-xs text-gray-400">{{ $decideWhy }}</p>
    @endif

    {{-- Setelah terverifikasi: jadikan Business Partner bertipe Vendor (atau tautkan ke yang sudah ada). --}}
    @if($reg->status === 'verified')
        <section class="rounded-xl border {{ $reg->customer_id ? 'border-green-200 bg-green-50' : 'border-indigo-200 bg-indigo-50' }} p-4">
            @if($reg->customer_id)
                <p class="text-sm text-green-800"><i class="fas fa-circle-check mr-1"></i> Linked to Business Partner
                    <span class="font-semibold">{{ $reg->customer?->customer_code }} — {{ $reg->customer?->basicData?->name_1 }}</span>.</p>
            @else
                <p class="text-sm font-semibold text-indigo-900">This vendor is verified but is not a Business Partner yet.</p>
                @if($canEdit)
                    <div class="mt-3 grid md:grid-cols-2 gap-4">
                        <form method="POST" action="{{ route('legal.vendor-onboarding.create-partner', $reg) }}" data-confirm="Create a Business Partner of type Vendor from this registration?" data-confirm-title="Create Business Partner" data-confirm-ok="Create">@csrf
                            <p class="text-xs text-indigo-800 mb-2">Creates the Business Partner with its identification (NPWP / {{ $personal ? 'NIK' : 'NIB' }}), bank account, address and contact. Documents stay here, in private storage.</p>
                            <button @disabled(!$canCreatePartner) class="px-4 py-2 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold disabled:opacity-40">Create Business Partner</button>
                            @unless($canCreatePartner)<p class="text-xs primary-text mt-1">You need Create permission on Master → Business Partner.</p>@endunless
                        </form>
                        <form method="POST" action="{{ route('legal.vendor-onboarding.link-partner', $reg) }}">@csrf
                            <p class="text-xs text-indigo-800 mb-2">Or link to a vendor that already exists as a Business Partner.</p>
                            <div class="flex gap-2">
                                <select name="customer_id" class="flex-1 rounded-lg border border-indigo-200 px-3 py-2 text-sm bg-white" required>
                                    <option value="">Choose a Vendor…</option>
                                    @foreach($vendors as $opt)<option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>@endforeach
                                </select>
                                <button class="px-4 py-2 rounded-lg border border-indigo-300 primary-text text-sm font-semibold hover:bg-white">Link</button>
                            </div>
                        </form>
                    </div>
                @endif
            @endif
        </section>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5 min-w-0">
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800">Profile</h3>
                    <span class="text-xs text-gray-400">{{ \App\Models\Legal\VendorOnboarding\VendorRegistration::TYPE_LABELS[$reg->vendor_type] ?? $reg->vendor_type }} · source: {{ $reg->source }}</span>
                </div>
                <dl class="p-5 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @foreach($names as $col => $name)
                        <div><dt class="text-xs text-gray-500">{{ $name }}</dt><dd class="font-medium text-gray-900 break-words">{{ $reg->{$col} ?: '—' }}</dd></div>
                    @endforeach
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Address</dt><dd class="font-medium text-gray-900 whitespace-pre-line">{{ $reg->company_address ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">{{ $personal ? 'Services offered' : 'Products / services' }}</dt><dd class="font-medium text-gray-900 whitespace-pre-line">{{ $reg->product_list ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Bank</dt><dd class="font-medium text-gray-900">{{ $reg->bank_name ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Account</dt><dd class="font-medium text-gray-900">{{ $reg->bank_account_no ?: '—' }} @if($reg->bank_account_name)<span class="text-gray-500">({{ $reg->bank_account_name }})</span>@endif</dd></div>
                    @unless($personal)
                        <div><dt class="text-xs text-gray-500">PIC</dt><dd class="font-medium text-gray-900">{{ $reg->pic_name ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">PIC contact</dt><dd class="font-medium text-gray-900">{{ $reg->pic_phone ?: '—' }} {{ $reg->pic_email ? '· ' . $reg->pic_email : '' }}</dd></div>
                    @endunless
                </dl>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Documents</h3></div>
                <div class="p-5 space-y-2">
                    @forelse($reg->documents as $d)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $d->original_name }}</p>
                                <p class="text-xs text-gray-400">{{ $d->field?->label_en ?? 'Document' }} · {{ number_format($d->size / 1024, 0) }} KB</p>
                            </div>
                            <a href="{{ route('legal.vendor-onboarding.documents.download', $d) }}" target="_blank" class="text-xs font-semibold primary-text hover:underline shrink-0">Open</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No documents uploaded.</p>
                    @endforelse
                </div>
            </section>

            @if($questions->count())
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Due diligence declarations</h3></div>
                    <div class="p-5">@include('legal.workflow._answers', ['questions' => $questions, 'answers' => $answers])</div>
                </section>
            @endif
        </div>

        <div class="space-y-5 min-w-0">
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">Approval steps</h3></div>
                <div class="p-5">@include('legal.workflow._steps', ['rounds' => $rounds, 'currentRound' => $reg->round])</div>
            </section>
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">History</h3></div>
                <div class="px-5 py-3">@include('legal.workflow._logs', ['logs' => $reg->logs])</div>
            </section>
        </div>
    </div>
</div>
@include('legal.workflow._confirm')
@endsection
