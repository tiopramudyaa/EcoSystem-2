@extends('dashboard')
@section('title', 'Contract Templates')
@section('page-title', 'Contract')
@section('page-subtitle', 'A contract text for each contract type and position type — for employees and for external consultants.')

@php
    $canCreate = $canDo('general.contracts.templates', 'create');
    // Export / import per format (Menu Access boxes general.contracts.templates.{export|import}-{pdf|html|json}).
    $xPdf = $canDo('general.contracts.templates.export-pdf'); $xHtml = $canDo('general.contracts.templates.export-html'); $xJson = $canDo('general.contracts.templates.export-json');
    $iHtml = $canDo('general.contracts.templates.import-html'); $iJson = $canDo('general.contracts.templates.import-json');
    $canEdit   = $canDo('general.contracts.templates', 'edit');
    $canDelete = $canDo('general.contracts.templates', 'delete');
    $typeBadge = ['PKWT' => 'tone-primary', 'PKWTT' => 'tone-primary-strong', 'INTERNSHIP' => 'bg-sky-100 text-sky-700', 'ADDENDUM' => 'bg-purple-100 text-purple-700', 'EXTERNAL' => 'bg-amber-100 text-amber-700'];
@endphp

@section('content')
<div class="w-full space-y-6 px-1 lg:px-2">
    @include('legal.contracts.components.tabs')
    @include('hr-general.recruitment.components.form-errors')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="text-xs bg-sky-50 border border-sky-200 text-sky-900 rounded-lg px-4 py-3 flex-1">
            <p class="font-semibold mb-1">How the template is selected</p>
            <p>When a contract is created, the system uses a template of the same contract type: first one written for the contract’s <strong>position type</strong> (Sales, Internship, Management Trainee …), then one that matches its Position, then a general template (the newest), then the system default. You can also pick another template by hand in the contract form. Contract types and position types are kept in each menu’s <strong>Settings</strong> tab.</p>
            <p class="mt-1">A system default is the baseline text: it can be edited but not deleted. Once a contract is <strong>Active</strong>, it keeps the template text it had — editing a template never changes running contracts.</p>
            <p class="mt-1">
                @if($letterhead)<i class="fas fa-circle-check text-green-600"></i> Letterhead in use: <strong>{{ $letterhead->name }}</strong>.
                @else <i class="fas fa-circle-info"></i> No letterhead yet for “Employment Contract”.@endif
                @if($canDo('general.letters.settings.letterheads'))<a href="{{ route('general.document-identity.index', ['section' => 'letterheads']) }}" class="underline font-semibold">Manage letterheads</a>@endif
            </p>
            <p class="mt-1 text-[11px] text-sky-800"><i class="fas fa-link"></i> Linked to <strong>Letter Templates → Settings</strong>: company <strong>{{ $linked['company'] ?: '—' }}</strong> and signing city <strong>{{ $linked['city'] ?: '—' }}</strong> fill the contract text; {{ $linked['signers'] }} signer{{ $linked['signers'] === 1 ? '' : 's' }} can be picked as the company signatory.
                @if($canDo('general.letters.settings.signers'))<a href="{{ route('general.document-identity.index', ['section' => 'signers']) }}" class="underline font-semibold">Signers</a>@endif</p>
        </div>
        <div class="flex flex-col gap-2 shrink-0 sm:items-end">
            @if($canCreate)
            <div class="flex gap-2">
                <a href="{{ route('general.contracts.templates.create', ['contract_type' => 'EXTERNAL']) }}" class="px-4 py-2 bg-white border border-indigo-300 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-50 whitespace-nowrap"><i class="fas fa-user-tie mr-1"></i> Add EXT Template</a>
                <a href="{{ route('general.contracts.templates.create') }}" class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 whitespace-nowrap"><i class="fas fa-plus mr-1"></i> Add Template</a>
            </div>
            @endif
            <div class="flex gap-2">
                {{-- One Menu Access box per format: a person only sees the choices they were given. --}}
                @if($xPdf || $xJson)
                <details class="relative" id="tplExportMenu">
                    <summary class="list-none cursor-pointer px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 whitespace-nowrap select-none"><i class="fas fa-file-export mr-1"></i> Export templates <i class="fas fa-chevron-down text-[9px] ml-1"></i></summary>
                    <div class="absolute right-0 z-20 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-1 text-xs">
                        @if($xPdf)<a href="{{ route('general.contracts.templates.export', ['format' => 'pdf']) }}" class="block px-3 py-2 rounded-md hover:bg-gray-50"><span class="font-semibold text-gray-800">All templates — PDF (ZIP)</span><span class="block text-[11px] text-gray-500">Sample contracts to read, print or send for review. Cannot be imported back.</span></a>@endif
                        @if($xJson)<a href="{{ route('general.contracts.templates.export', ['format' => 'json']) }}" class="block px-3 py-2 rounded-md hover:bg-gray-50"><span class="font-semibold text-gray-800">All templates — backup file</span><span class="block text-[11px] text-gray-500">To keep a copy or move the templates to another system. Not meant to be read.</span></a>@endif
                    </div>
                </details>
                @endif
                @if($canCreate && ($iHtml || $iJson))<button type="button" id="tplImportOpen" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 whitespace-nowrap" title="Add or update templates from a file"><i class="fas fa-file-import mr-1"></i> Import templates</button>@endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                    <tr><th class="px-4 py-3 w-10">No</th><th class="px-4 py-3">Template</th><th class="px-4 py-3">Contract Type</th><th class="px-4 py-3">Applies To</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($templates as $t)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-800">{{ $t->name }}</span>
                                @if($t->is_system_default)<span class="ml-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[9px] font-bold">System Default</span>@endif
                                @if($t->description)<span class="block text-[11px] text-gray-400 mt-0.5">{{ \Illuminate\Support\Str::limit($t->description, 90) }}</span>@endif
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $typeBadge[$t->contract_type] ?? 'bg-gray-100 text-gray-600' }}">{{ $types[$t->contract_type] ?? $t->contract_type }}</span></td>
                            <td class="px-4 py-3">{{ $t->appliesToLabel() }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $t->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($t->status) }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('general.contracts.templates.show', $t->id) }}" class="px-2.5 py-1.5 rounded-md border border-sky-300 text-sky-700 hover:bg-sky-50 text-[11px] font-semibold"><i class="far fa-eye mr-1"></i>View</a>
                                    @if($xPdf || $xHtml || $xJson)
                                    <span class="inline-flex rounded-md border border-gray-300 overflow-hidden text-[10px] font-bold text-gray-500 divide-x divide-gray-300" role="group" aria-label="Export this template">
                                        @if($xPdf)<a href="{{ route('general.contracts.templates.export', ['ids' => [$t->id], 'format' => 'pdf']) }}" class="px-2 py-1.5 hover:bg-gray-50" title="Export as PDF with sample values (to read or print; cannot be imported back)">PDF</a>@endif
                                        @if($xHtml)<a href="{{ route('general.contracts.templates.export', ['ids' => [$t->id], 'format' => 'html']) }}" class="px-2 py-1.5 hover:bg-gray-50" title="Export as an editable HTML file (edit it, then import it back)">HTML</a>@endif
                                        @if($xJson)<a href="{{ route('general.contracts.templates.export', ['ids' => [$t->id], 'format' => 'json']) }}" class="px-2 py-1.5 hover:bg-gray-50" title="Export as a backup file (can be imported back)">Backup</a>@endif
                                    </span>
                                    @endif
                                    @if($canEdit)<a href="{{ route('general.contracts.templates.edit', $t->id) }}" class="px-2.5 py-1.5 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 text-[11px] font-semibold"><i class="far fa-pen-to-square mr-1"></i>Edit</a>@endif
                                    @if($canDelete && !$t->is_system_default)
                                        <form method="POST" action="{{ route('general.contracts.templates.destroy', $t->id) }}" data-confirm="Delete template “{{ $t->name }}”? Contracts already Active keep the text they have." data-confirm-title="Delete Template" data-confirm-ok="Delete" data-unsaved-ignore>@csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-md border border-red-300 text-red-600 hover:bg-red-50 text-[11px] font-semibold">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No template yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($canCreate)
    @include('legal.contracts.components.template-import', ['importHtml' => $iHtml, 'importJson' => $iJson])
@endif
@endsection
