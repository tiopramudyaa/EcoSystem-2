<?php

namespace App\Http\Controllers\Legal;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Legal\SalesCompliance\SalesComplianceAttachment;
use App\Models\Legal\SalesCompliance\SalesComplianceRequest;
use App\Services\Legal\SalesComplianceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Legal → Sales Compliance Approval.
 *
 * Pintu halaman dijaga rute: `menu:legal.sales-compliance` (+ `menu.can` untuk membuat/mengubah/menghapus) dan
 * `menu.approver` untuk halaman dokumen dan keputusan. Di sini diputuskan hal PER DOKUMEN: siapa pemiliknya, apakah masih draft,
 * apakah orang ini boleh memutuskan. Aturan persetujuannya ada di {@see SalesComplianceService}.
 */
class SalesComplianceController extends LegalWorkflowController
{
    private const MODULE        = 'sales-compliance';
    private const SLUG          = 'legal.sales-compliance';
    private const SLUG_APPROVE  = 'legal.sales-compliance.approve';

    public function __construct(private SalesComplianceService $service)
    {
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $me     = $this->meId();
        $all    = $this->seesAll(self::MODULE, self::SLUG_APPROVE);

        $rows = SalesComplianceRequest::query()
            ->with('employee.basicData')
            ->when(!$all, fn ($q) => $q->where(fn ($w) => $w->where('employee_id', $me)->orWhere('created_by', $me)))
            ->when(isset(SalesComplianceRequest::STATUS_LABELS[$status]), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('request_no', 'like', "%{$search}%")->orWhere('project_name', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = SalesComplianceRequest::query()
            ->when(!$all, fn ($q) => $q->where(fn ($w) => $w->where('employee_id', $me)->orWhere('created_by', $me)))
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('legal.sales-compliance.index', [
            'rows' => $rows, 'counts' => $counts, 'status' => $status, 'search' => $search, 'seesAll' => $all,
            'mine' => $this->service->pendingIdsFor($me),
            'canCreate' => $this->me()?->hasMenuPermission(self::SLUG, 'can_create') ?? false,
        ]);
    }

    public function create()
    {
        return view('legal.sales-compliance.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $result = $this->service->saveDraft(null, $this->documentData($request, $data, null), $this->answersFrom($request), $this->meId());

        return redirect()->route('legal.sales-compliance.edit', $result['request'])
            ->with('success', 'Draft ' . $result['request']->request_no . ' saved. Add the price documents, then submit it for review.');
    }

    public function edit(SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);

        if (!$salesComplianceRequest->isEditable()) {
            return redirect()->route('legal.sales-compliance.show', $salesComplianceRequest)
                ->with('error', 'Only a draft can be edited.');
        }

        return view('legal.sales-compliance.form', $this->formData($salesComplianceRequest));
    }

    public function update(Request $request, SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);
        $data = $this->validated($request);

        $result = $this->service->saveDraft($salesComplianceRequest, $this->documentData($request, $data, $salesComplianceRequest), $this->answersFrom($request), $this->meId());
        if (!$result['ok']) {
            return back()->with('error', $result['reason']);
        }

        return redirect()->route('legal.sales-compliance.edit', $salesComplianceRequest)->with('success', 'Draft saved.');
    }

    /**
     * Halaman dokumen. Terbuka bagi pemilik, pemegang slug, dan orang yang ditunjuk — middleware `menu.approver` sudah menahan
     * penunjuk tanpa slug pada dokumen yang tidak ia ikuti; pemilik tanpa `.approve` hanya boleh membuka miliknya.
     */
    public function show(SalesComplianceRequest $salesComplianceRequest)
    {
        $req = $salesComplianceRequest;
        $me  = $this->meId();

        abort_unless(
            $this->seesAll(self::MODULE, self::SLUG_APPROVE) || (int) $req->employee_id === $me || (int) $req->created_by === $me
                || app(\App\Services\Approval\ApprovalEngine::class)->participates(self::MODULE, $req, $me),
            403
        );

        $req->load(['employee.basicData', 'approvals.actor.basicData', 'approvals.role', 'attachments', 'logs', 'customer.basicData']);

        $gate      = $this->service->canAct($req, $me, $this->holds(self::SLUG_APPROVE));
        $questions = $this->service->questions();
        $answers   = $req->answers()->pluck('answer', 'question_id');
        $owner     = (int) $req->employee_id === $me || (int) $req->created_by === $me;

        return view('legal.sales-compliance.show', [
            'req' => $req, 'rounds' => $req->approvals->groupBy('round'),
            'questions' => $questions->groupBy('section'), 'answers' => $answers,
            'canDecide' => $gate['allowed'], 'decideWhy' => $gate['reason'],
            'owner' => $owner, 'backRoute' => route('legal.sales-compliance.index'),
            'readiness' => $req->isEditable() ? $this->service->readiness($req) : [],
            'canEdit' => $owner && $this->me()?->hasMenuPermission(self::SLUG, 'can_edit'),
            'canDelete' => $owner && $this->me()?->hasMenuPermission(self::SLUG, 'can_delete'),
        ]);
    }

    public function submit(SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);

        $result = $this->service->submit($salesComplianceRequest, $this->meId());
        if (!$result['ok']) {
            $detail = $result['missing'] ? ' Missing: ' . implode('; ', $result['missing']) . '.' : '';

            return back()->with('error', $result['reason'] . $detail);
        }

        return redirect()->route('legal.sales-compliance.show', $salesComplianceRequest)
            ->with('success', 'Submitted. The compliance team has been notified.');
    }

    public function uploadAttachment(Request $request, SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);
        abort_unless($salesComplianceRequest->isEditable(), 403);

        $request->validate([
            'file_type' => ['required', Rule::in(array_keys(SalesComplianceAttachment::TYPES))],
            'file'      => ['required', 'file', 'mimes:' . implode(',', SalesComplianceService::ATTACHMENT_EXTENSIONS), 'max:' . SalesComplianceService::ATTACHMENT_MAX_KB],
        ], [
            'file.mimes' => 'Only PDF, JPG, PNG or WEBP files are accepted.',
            'file.max'   => 'The file may not be larger than 10 MB.',
        ]);

        // Satu dokumen harga dan satu MRP cukup; unggahan baru menggantikan yang lama supaya tidak ada versi ganda yang ambigu.
        if (in_array($request->input('file_type'), [SalesComplianceAttachment::TYPE_PRICE, SalesComplianceAttachment::TYPE_MRP], true)) {
            foreach ($salesComplianceRequest->attachments()->where('file_type', $request->input('file_type'))->get() as $old) {
                $this->service->removeAttachment($old);
            }
        }

        $this->service->addAttachment($salesComplianceRequest, $request->input('file_type'), $request->file('file'), $this->meId());

        return redirect()->route('legal.sales-compliance.edit', $salesComplianceRequest)->with('success', 'File uploaded.');
    }

    public function deleteAttachment(SalesComplianceAttachment $attachment)
    {
        $req = $attachment->request;
        $this->abortUnlessOwner($req);
        abort_unless($req->isEditable(), 403);

        $this->service->removeAttachment($attachment);

        return redirect()->route('legal.sales-compliance.edit', $req)->with('success', 'File removed.');
    }

    /** Berkas hanya untuk yang boleh melihat dokumennya: pemilik, pemegang slug lihat-semua, atau orang yang ikut di langkahnya. */
    public function downloadAttachment(SalesComplianceAttachment $attachment)
    {
        $req = $attachment->request;
        $me  = $this->meId();

        abort_unless(
            $this->seesAll(self::MODULE, self::SLUG_APPROVE) || (int) $req->employee_id === $me || (int) $req->created_by === $me
                || app(\App\Services\Approval\ApprovalEngine::class)->participates(self::MODULE, $req, $me),
            403
        );

        return $this->streamPrivate(SalesComplianceService::DISK, $attachment->path, $attachment->original_name, $attachment->mime);
    }

    public function approve(Request $request, SalesComplianceRequest $salesComplianceRequest)
    {
        $request->validate(['notes' => 'nullable|string|max:2000']);

        $result = $this->service->approve($salesComplianceRequest, $this->meId(), $request->input('notes'), $this->holds(self::SLUG_APPROVE));
        if (!$result['allowed']) {
            return back()->with('error', $result['reason']);
        }

        return back()->with('success', $result['completed'] ? 'Approved. The request is now fully approved.' : 'Approved. The request moved to the next step.');
    }

    public function reject(Request $request, SalesComplianceRequest $salesComplianceRequest)
    {
        $request->validate(['notes' => 'required|string|max:2000'], ['notes.required' => 'A note is required when rejecting.']);

        $result = $this->service->reject($salesComplianceRequest, $this->meId(), (string) $request->input('notes'), $this->holds(self::SLUG_APPROVE));
        if (!$result['allowed']) {
            return back()->with('error', $result['reason']);
        }

        return back()->with('success', 'Rejected. The sales person has been notified.');
    }

    public function reopen(SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);

        $result = $this->service->reopen($salesComplianceRequest, $this->meId());

        return $result['ok']
            ? redirect()->route('legal.sales-compliance.edit', $salesComplianceRequest)->with('success', 'Reopened as a draft. Correct it and submit again.')
            : back()->with('error', $result['reason']);
    }

    public function cancel(SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);

        $result = $this->service->cancel($salesComplianceRequest, $this->meId());

        return $result['ok']
            ? redirect()->route('legal.sales-compliance.show', $salesComplianceRequest)->with('success', 'The request was cancelled.')
            : back()->with('error', $result['reason']);
    }

    public function destroy(SalesComplianceRequest $salesComplianceRequest)
    {
        $this->abortUnlessOwner($salesComplianceRequest);

        $result = $this->service->deleteDraft($salesComplianceRequest);

        return $result['ok']
            ? redirect()->route('legal.sales-compliance.index')->with('success', 'Draft deleted.')
            : back()->with('error', $result['reason']);
    }

    // ── private ─────────────────────────────────────────────────────────

    /** Hanya pembuat deal (atau yang membuatkannya) yang mengubah dan mengajukan; pemutus tidak ikut menyunting. */
    private function abortUnlessOwner(SalesComplianceRequest $request): void
    {
        $me = $this->meId();
        abort_unless((int) $request->employee_id === $me || (int) $request->created_by === $me, 403, 'Only the sales person who owns this request can change it.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id'         => ['nullable', 'integer', 'exists:employee,employee_id'],
            'project_name'        => ['required', 'string', 'max:255'],
            'customer_name'       => ['required', 'string', 'max:255'],
            'pic_name'            => ['required', 'string', 'max:255'],
            'has_meeting'         => ['nullable', 'boolean'],
            'meeting_location'    => ['nullable', 'required_if:has_meeting,1', 'string', 'max:500'],
            'has_hospitality'     => ['nullable', 'boolean'],
            'hospitality_detail'  => ['nullable', 'required_if:has_hospitality,1', 'string', 'max:2000'],
            'documents_received'  => ['nullable', 'string', 'max:2000'],
            'documents_checked'   => ['nullable', 'array'],
            'documents_checked.*' => ['string', 'max:255'],
            'final_price'         => ['nullable', 'numeric', 'min:0', 'max:99999999999999999'],
            'mrp_price'           => ['nullable', 'numeric', 'min:0', 'max:99999999999999999'],
            'price_justification' => ['nullable', 'string', 'max:3000'],
            'payment_method'      => ['nullable', 'string', 'max:100'],
            'contract_value'      => ['nullable', 'numeric', 'min:0', 'max:99999999999999999'],
            'contract_date'       => ['nullable', 'date'],
        ], [
            'meeting_location.required_if'   => 'Enter the meeting location.',
            'hospitality_detail.required_if' => 'Describe the hospitality.',
        ]);
    }

    /** Ubah input form menjadi kolom dokumen. Hanya pemegang `.approve` yang boleh memilih sales lain (membuatkan atas nama). */
    private function documentData(Request $request, array $validated, ?SalesComplianceRequest $existing): array
    {
        $me = $this->meId();

        $employeeId = $existing?->employee_id ?? $me;
        if (!$existing && !empty($validated['employee_id']) && $this->holds(self::SLUG_APPROVE)) {
            $employeeId = (int) $validated['employee_id'];
        }

        $name = trim($validated['customer_name']);
        $customerId = Customer::where('type', Customer::TYPE_CUSTOMER)
            ->whereHas('basicData', fn ($q) => $q->where('name_1', $name))->value('customer_id');

        // Daftar baku dari portal lama (kotak centang) + isian lain satu per baris
        $docs = collect($validated['documents_checked'] ?? [])
            ->merge(preg_split('/\R/', (string) ($validated['documents_received'] ?? '')))
            ->map(fn ($line) => trim($line))->filter()->unique()->values()->all();

        return [
            'employee_id'         => $employeeId,
            'project_name'        => trim($validated['project_name']),
            'customer_id'         => $customerId,
            'customer_name'       => $name,
            'pic_name'            => trim($validated['pic_name']),
            'has_meeting'         => $request->boolean('has_meeting'),
            'meeting_location'    => $request->boolean('has_meeting') ? ($validated['meeting_location'] ?? null) : null,
            'has_hospitality'     => $request->boolean('has_hospitality'),
            'hospitality_detail'  => $request->boolean('has_hospitality') ? ($validated['hospitality_detail'] ?? null) : null,
            'documents_received'  => $docs ?: null,
            'final_price'         => $validated['final_price'] ?? null,
            'mrp_price'           => $validated['mrp_price'] ?? null,
            'price_justification' => $validated['price_justification'] ?? null,
            'payment_method'      => $validated['payment_method'] ?? null,
            'contract_value'      => $validated['contract_value'] ?? null,
            'contract_date'       => $validated['contract_date'] ?? null,
        ];
    }

    private function formData(?SalesComplianceRequest $req): array
    {
        $onBehalf = $this->holds(self::SLUG_APPROVE);

        return [
            'req'       => $req,
            'questions' => $this->service->questions()->groupBy('section'),
            'answers'   => $req ? $req->answers()->pluck('answer', 'question_id') : collect(),
            'customers' => Customer::where('type', Customer::TYPE_CUSTOMER)->with('basicData:basic_data_id,customer_id,name_1')
                ->get()->map(fn ($c) => $c->basicData?->name_1)->filter()->sort()->values(),
            'attachments' => $req ? $req->attachments : collect(),
            'readiness'   => $req ? $this->service->readiness($req) : [],
            'employees'   => $onBehalf && !$req
                ? Employee::with('basicData')->where('is_active', true)->get()
                    ->map(fn ($e) => ['id' => $e->employee_id, 'name' => $e->fullNameLabel()])->sortBy('name')->values()
                : collect(),
            'meId' => $this->meId(),
        ];
    }
}
