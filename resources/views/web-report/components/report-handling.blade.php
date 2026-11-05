{{-- One report in full: what was reported (left, 3 parts) and how it is handled (right, 1 part): status, the message the reporter sees, the internal
     note and the access history. Expects: $report, $events, $canEdit, $updateUrl, $evidenceUrl.
     Shared by Web Report Management (Control Center) and Compliance Management → Reports, so both handle a report the same way. --}}
@php
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 bg-white disabled:bg-gray-50 disabled:text-gray-500';
    $label = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
    $actions = ['submitted' => 'Report received', 'viewed' => 'Opened', 'status' => 'Status changed', 'note' => 'Notes updated', 'evidence_viewed' => 'Evidence downloaded', 'exported' => 'Included in an export'];
    $actionIcons = ['submitted' => 'inbox', 'viewed' => 'eye', 'status' => 'right-left', 'note' => 'pen', 'evidence_viewed' => 'paperclip', 'exported' => 'file-export'];
    $isCompliance = $report->channel === \App\Models\WebReport::CHANNEL_COMPLIANCE;
@endphp
<div class="grid lg:grid-cols-4 gap-6 items-start">

    {{-- What was reported — 3 of 4 columns --}}
    <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-w-0">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="{{ $label }} mb-0.5">Tracking code</p>
                <p class="font-mono text-base font-bold text-gray-800 tracking-wide select-all">{{ $report->tracking_code }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($report->isOverdue())<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-600"><i class="fas fa-triangle-exclamation mr-1"></i>Past follow-up date</span>@endif
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span>
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-6">
            <div class="grid sm:grid-cols-3 gap-5 text-sm">
                <div>
                    <p class="{{ $label }}">Reporter</p>
                    @if($report->reporter_name)
                        <p class="font-semibold text-gray-800"><i class="fas fa-user text-gray-300 mr-1.5"></i>{{ $report->reporter_name }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $isCompliance ? 'Employee · sent from My Compliance' : 'Gave a name on the public form' }}</p>
                    @else
                        <p class="font-semibold text-gray-500 italic"><i class="fas fa-user-secret text-gray-300 mr-1.5"></i>Anonymous</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">The reporter did not give a name</p>
                    @endif
                </div>
                <div>
                    <p class="{{ $label }}">Contact for follow-up</p>
                    <p class="text-gray-800 break-words">{{ $report->reporter_contact ?: '—' }}</p>
                </div>
                <div>
                    <p class="{{ $label }}">Received</p>
                    <p class="text-gray-800">{{ $report->created_at->format('d M Y, H:i') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Language: {{ strtoupper($report->lang) }}</p>
                </div>
            </div>

            <div class="text-sm border-t border-gray-100 pt-5">
                <p class="{{ $label }}">Person or subject reported</p>
                <p class="text-base font-semibold text-gray-900">{{ $report->reported_name }}</p>
            </div>

            <div class="text-sm">
                <p class="{{ $label }}">Chronology</p>
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3.5 whitespace-pre-line text-gray-700 leading-relaxed">{{ $report->chronology }}</div>
            </div>

            <div class="text-sm">
                <p class="{{ $label }}">Evidence</p>
                @if($report->evidence_path)
                    <a href="{{ $evidenceUrl }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"><i class="fas fa-paperclip text-gray-400"></i> {{ $report->evidence_name ?: 'Download' }} <i class="fas fa-download text-gray-300 text-[10px]"></i></a>
                    <p class="text-[11px] text-gray-400 mt-1.5"><i class="fas fa-shield-halved mr-1"></i>Scan the file before opening it{{ $isCompliance ? '' : ' — it came from an unknown person' }}. Every download is logged.</p>
                @else
                    <p class="text-gray-400">No file was attached.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- How it is handled — 1 of 4 columns --}}
    <div class="lg:col-span-1 space-y-5 min-w-0 lg:sticky lg:top-24">
        <form method="POST" action="{{ $updateUrl }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4" data-unsaved-ignore>@csrf
            <h3 class="text-sm font-bold text-gray-800"><i class="fas fa-clipboard-check text-gray-400 mr-1.5"></i>Handling</h3>
            <div>
                <label class="{{ $label }}" for="wStatus">Status</label>
                <select id="wStatus" name="status" class="{{ $input }}" @disabled(!$canEdit)>@foreach(\App\Models\WebReport::choices($report->channel, $report->status) as $k => $text)<option value="{{ $k }}" @selected($report->status === $k)>{{ $text }}</option>@endforeach</select>
                <p class="text-[11px] mt-1.5 {{ $report->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                    @if($report->isFinal())Closed{{ $report->closed_at ? ' on ' . $report->closed_at->format('d M Y') : '' }}
                    @else<i class="far fa-clock mr-0.5"></i> Follow-up due {{ $report->due_at?->format('d M Y') ?? '—' }}@endif
                </p>
            </div>
            <div>
                <label class="{{ $label }}" for="wPublic">Message the reporter will see</label>
                <textarea id="wPublic" name="public_note" rows="4" maxlength="2000" class="{{ $input }}" @disabled(!$canEdit)>{{ old('public_note', $report->public_note) }}</textarea>
                <p class="text-[10px] text-gray-400 mt-1">Shown with the tracking code on the public status page. Never put names or evidence here.</p>
            </div>
            <div>
                <label class="{{ $label }}" for="wInternal">Internal note</label>
                <textarea id="wInternal" name="internal_note" rows="5" maxlength="10000" class="{{ $input }}" @disabled(!$canEdit)>{{ old('internal_note', $report->internal_note) }}</textarea>
                <p class="text-[10px] text-gray-400 mt-1">Only people who handle reports can read this.</p>
            </div>
            @if($canEdit)
                <button class="w-full px-4 py-2.5 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90"><i class="fas fa-floppy-disk mr-1"></i> Save</button>
            @else
                <p class="text-[11px] text-gray-500 bg-gray-50 rounded-lg px-3 py-2"><i class="fas fa-lock mr-1"></i> You can read this report but not change it. Ask an administrator to tick Edit for your role in Menu Access.</p>
            @endif
        </form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-3"><i class="fas fa-clock-rotate-left text-gray-400 mr-1.5"></i>Access history</h3>
            <ul class="space-y-3 text-xs text-gray-600 max-h-96 overflow-y-auto pr-1">
                @foreach($events as $e)
                    <li class="flex gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-[9px] shrink-0 mt-0.5"><i class="fas fa-{{ $actionIcons[$e->action] ?? 'circle' }}"></i></span>
                        <span class="min-w-0">
                            <span class="block font-medium text-gray-700">{{ $actions[$e->action] ?? $e->action }}@if($e->detail) <span class="font-normal text-gray-400">({{ $e->detail }})</span>@endif</span>
                            <span class="block text-[11px] text-gray-400">{{ trim($e->actor) ?: 'Reporter' }} · {{ \Illuminate\Support\Carbon::parse($e->created_at)->format('d M Y, H:i') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
