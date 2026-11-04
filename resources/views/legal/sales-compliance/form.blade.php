@extends('dashboard')
@section('title', $req ? 'Edit ' . $req->request_no : 'New sales compliance request')
@section('page-title', 'Sales Compliance Approval')
@section('page-subtitle', $req ? 'Draft ' . $req->request_no . ' — round ' . $req->round : 'Describe the deal, state the price against the MRP and answer the declarations.')

@section('content')
@php
    $field = fn ($name, $default = null) => old($name, $req?->{$name} ?? $default);
    $label = 'block text-xs font-semibold text-gray-600 mb-1';
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';
    $action = $req ? route('legal.sales-compliance.update', $req) : route('legal.sales-compliance.store');
    // Daftar baku dari portal lama; isian lain tampil di kotak "Other"
    $docOptions = ['Surat Penawaran / RFQ', 'Kerangka Acuan Kerja (KAK / TOR)', 'HPS (Harga Perkiraan Sendiri)', 'Dokumen Spesifikasi Teknis', 'Surat Undangan Lelang / Tender', 'MOU / Draft Kontrak'];
    $savedDocs = old('documents_checked') !== null ? (array) old('documents_checked') : ($req && $req->documents_received ? $req->documents_received : []);
    $docsText = old('documents_received', implode("\n", array_diff($savedDocs, $docOptions)));
@endphp

<div class="w-full px-1 lg:px-2 space-y-5">
    @include('legal.sales-compliance._tabs')

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($req && count($readiness))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Still needed before you can submit:</p>
            <ul class="list-disc ml-5 mt-1">@foreach($readiness as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">1. The deal</h3></div>
            <div class="p-5 grid md:grid-cols-2 gap-4">
                @if(!$req && $employees->count())
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Sales person</label>
                        {{-- Combobox mode ketat: harus karyawan dari daftar; ID tersimpan di input tersembunyi --}}
                        <input type="hidden" name="employee_id" id="salesPersonId" value="{{ (int) old('employee_id', $meId) }}">
                        <input type="text" id="salesPersonSearch" data-suggest-source="#salesPersonData" data-suggest-strict="#salesPersonId" data-suggest-label="person" data-suggest-plural="people" placeholder="Search a sales person" class="{{ $input }}">
                        <script type="application/json" id="salesPersonData">@json($employees->map(fn ($e) => ['value' => $e['id'], 'label' => $e['name']])->values())</script>
                        <p class="text-xs text-gray-400 mt-1">You may start a request on behalf of a sales person. That person cannot be the one who decides it.</p>
                    </div>
                @endif
                <div>
                    <label class="{{ $label }}">Project name <span class="text-red-500">*</span></label>
                    <input name="project_name" value="{{ $field('project_name') }}" maxlength="255" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">Customer <span class="text-red-500">*</span></label>
                    <input name="customer_name" value="{{ $field('customer_name') }}" maxlength="255" data-suggest-source="#customerData" data-suggest-label="customer" placeholder="Search a customer or type a new name" class="{{ $input }}" required>
                    <script type="application/json" id="customerData">@json(collect($customers)->values())</script>
                    <p class="text-xs text-gray-400 mt-1">Pick a Business Partner or type a new name.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Customer PIC <span class="text-red-500">*</span></label>
                    <input name="pic_name" value="{{ $field('pic_name') }}" maxlength="255" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">Documents received from the client</label>
                    <div class="grid sm:grid-cols-2 gap-1.5 mb-2">
                        @foreach($docOptions as $opt)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="documents_checked[]" value="{{ $opt }}" @checked(in_array($opt, $savedDocs, true))> {{ $opt }}
                            </label>
                        @endforeach
                    </div>
                    <textarea name="documents_received" rows="2" class="{{ $input }}" placeholder="Other documents, one per line">{{ $docsText }}</textarea>
                </div>

                <div class="space-y-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="has_meeting" value="0">
                        <input type="checkbox" name="has_meeting" value="1" data-reveal="#meetingBox" @checked($field('has_meeting')) class="rounded primary-text"> There was a meeting with the client
                    </label>
                    <div id="meetingBox" class="{{ $field('has_meeting') ? '' : 'hidden' }}">
                        <input name="meeting_location" value="{{ $field('meeting_location') }}" maxlength="500" placeholder="Meeting location" class="{{ $input }}">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="has_hospitality" value="0">
                        <input type="checkbox" name="has_hospitality" value="1" data-reveal="#hospitalityBox" @checked($field('has_hospitality')) class="rounded primary-text"> Hospitality was given (meal, gift, travel, …)
                    </label>
                    <div id="hospitalityBox" class="{{ $field('has_hospitality') ? '' : 'hidden' }}">
                        <textarea name="hospitality_detail" rows="2" maxlength="2000" placeholder="Describe what, to whom and the value" class="{{ $input }}">{{ $field('hospitality_detail') }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">2. Price</h3></div>
            <div class="p-5 grid md:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}">Final (agreed) price <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="final_price" id="finalPrice" value="{{ $field('final_price') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">MRP price <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="mrp_price" id="mrpPrice" value="{{ $field('mrp_price') }}" class="{{ $input }}">
                </div>
                <div class="flex items-end"><p id="priceDiff" class="text-sm text-gray-500"></p></div>
                <div class="md:col-span-3">
                    <label class="{{ $label }}">Price justification <span class="text-gray-400 font-normal">(required when the final price differs from the MRP)</span></label>
                    <textarea name="price_justification" rows="3" maxlength="3000" class="{{ $input }}">{{ $field('price_justification') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Payment method</label>
                    <input name="payment_method" value="{{ $field('payment_method') }}" maxlength="100" class="{{ $input }}" placeholder="e.g. Transfer, 30 days">
                </div>
                <div>
                    <label class="{{ $label }}">Contract value</label>
                    <input type="number" step="0.01" min="0" name="contract_value" value="{{ $field('contract_value') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Contract date</label>
                    <input type="date" name="contract_date" value="{{ old('contract_date', $req?->contract_date?->format('Y-m-d')) }}" class="{{ $input }}">
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">3. Compliance declarations</h3></div>
            <div class="p-5 space-y-5">
                @foreach($questions as $section => $items)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide primary-text mb-1">{{ $section ?: 'General' }}</p>
                        @include('legal.workflow._questions', ['questions' => $items, 'answers' => $answers])
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button class="px-5 py-2 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">Save draft</button>
            <a href="{{ $req ? route('legal.sales-compliance.show', $req) : route('legal.sales-compliance.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>

    {{-- Lampiran: butuh dokumen yang sudah tersimpan, jadi baru muncul setelah draft pertama disimpan. Form terpisah (multipart). --}}
    @if($req)
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-800">4. Documents</h3></div>
            <div class="p-5 space-y-4">
                <p class="text-xs text-gray-500">The agreed-price document and the MRP document are required. PDF, JPG, PNG or WEBP, up to 10 MB each. A new upload of the price or MRP document replaces the old one.</p>
                @forelse($attachments as $a)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800 truncate">{{ $a->original_name }}</p>
                            <p class="text-xs text-gray-400">{{ \App\Models\Legal\SalesCompliance\SalesComplianceAttachment::TYPES[$a->file_type] ?? $a->file_type }} · {{ number_format($a->size / 1024, 0) }} KB</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('legal.sales-compliance.attachments.download', $a) }}" target="_blank" class="text-xs font-semibold primary-text hover:underline">Open</a>
                            <form method="POST" action="{{ route('legal.sales-compliance.attachments.destroy', $a) }}" data-confirm="Remove {{ $a->original_name }}?" data-confirm-title="Remove file" data-confirm-ok="Remove" data-confirm-variant="danger">@csrf
                                <button class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No documents uploaded yet.</p>
                @endforelse

                <form method="POST" action="{{ route('legal.sales-compliance.attachments.store', $req) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 pt-2 border-t border-gray-100">
                    @csrf
                    <div>
                        <label class="{{ $label }}">Type</label>
                        <select name="file_type" class="{{ $input }}">
                            @foreach(\App\Models\Legal\SalesCompliance\SalesComplianceAttachment::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">File</label>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="text-sm">
                    </div>
                    <button class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Upload</button>
                </form>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">Save the draft first if you changed anything above, then submit it to the compliance team.</p>
                <form method="POST" action="{{ route('legal.sales-compliance.submit', $req) }}" data-confirm="Submit {{ $req->request_no }} for compliance review? You cannot edit it while it is in review." data-confirm-title="Submit for review" data-confirm-ok="Submit">@csrf
                    <button class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Submit for review</button>
                </form>
            </div>
        </section>
    @endif
</div>

@include('legal.workflow._confirm')
<script src="{{ asset('js/suggest-combobox.js') }}"></script>
<script>
(function () {
    document.querySelectorAll('[data-reveal]').forEach(function (box) {
        box.addEventListener('change', function () {
            document.querySelector(box.dataset.reveal).classList.toggle('hidden', !box.checked);
        });
    });
    var f = document.getElementById('finalPrice'), m = document.getElementById('mrpPrice'), out = document.getElementById('priceDiff');
    if (!f || !m || !out) return;
    function update() {
        var a = parseFloat(f.value), b = parseFloat(m.value);
        if (isNaN(a) || isNaN(b) || b === 0) { out.textContent = ''; return; }
        var pct = (a - b) / b * 100;
        out.textContent = (pct === 0 ? 'Equal to the MRP' : (pct > 0 ? '+' : '') + pct.toFixed(2) + '% vs MRP');
        out.className = 'text-sm ' + (pct < 0 ? 'text-red-600 font-semibold' : 'text-gray-500');
    }
    f.addEventListener('input', update); m.addEventListener('input', update); update();
})();
</script>
@endsection
