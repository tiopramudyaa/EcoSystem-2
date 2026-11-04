<?php

namespace App\Http\Controllers\Legal;

use App\Models\Customer;
use App\Models\Legal\VendorOnboarding\VendorDocument;
use App\Models\Legal\VendorOnboarding\VendorDocumentField;
use App\Models\Legal\VendorOnboarding\VendorRegistration;
use App\Services\Approval\ApprovalEngine;
use App\Services\Legal\VendorOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Legal → Vendor Onboarding.
 *
 * Daftar vendor adalah daftar kerja bersama (back-office), jadi pemegang slug halaman melihat semuanya; yang dibatasi adalah
 * orang yang hanya DITUNJUK pada langkah — ia hanya membuka pendaftaran yang ia ikuti (middleware `menu.approver`).
 * Aturan persetujuan ada di {@see VendorOnboardingService}.
 */
class VendorOnboardingController extends LegalWorkflowController
{
    private const MODULE       = 'vendor-onboarding';
    private const SLUG         = 'legal.vendor-onboarding';
    private const SLUG_APPROVE = 'legal.vendor-onboarding.approve';
    /** Membuat Business Partner adalah tindakan master data: butuh izin yang sama dengan menambah Business Partner. */
    private const SLUG_MASTER_CREATE = 'master.customer.create';

    public function __construct(private VendorOnboardingService $service)
    {
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $rows = VendorRegistration::query()
            ->with('customer')
            ->when(isset(VendorRegistration::STATUS_LABELS[$status]), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference_no', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%")->orWhere('company_email', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('legal.vendor-onboarding.index', [
            'rows' => $rows, 'counts' => VendorRegistration::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'status' => $status, 'search' => $search, 'mine' => $this->service->pendingIdsFor($this->meId()),
            'canCreate' => $this->me()?->hasMenuPermission(self::SLUG, 'can_create') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type') === VendorRegistration::TYPE_PERSONAL ? VendorRegistration::TYPE_PERSONAL : VendorRegistration::TYPE_B2B;

        return view('legal.vendor-onboarding.form', $this->formData(null, $type));
    }

    public function store(Request $request)
    {
        $result = $this->service->saveProfile(null, $this->profile($request), $this->answersFrom($request), $this->meId());
        $registration = $result['registration'];
        $this->storeUploads($request, $registration);

        return redirect()->route('legal.vendor-onboarding.edit', $registration)
            ->with('success', 'Registration ' . $registration->reference_no . ' saved as a draft.');
    }

    public function edit(VendorRegistration $vendorRegistration)
    {
        if (!$vendorRegistration->isEditable()) {
            return redirect()->route('legal.vendor-onboarding.show', $vendorRegistration)
                ->with('error', 'This registration is already under review and can no longer be edited.');
        }

        return view('legal.vendor-onboarding.form', $this->formData($vendorRegistration, $vendorRegistration->vendor_type));
    }

    public function update(Request $request, VendorRegistration $vendorRegistration)
    {
        $result = $this->service->saveProfile($vendorRegistration, $this->profile($request, $vendorRegistration), $this->answersFrom($request));
        if (!$result['ok']) {
            return back()->with('error', $result['reason']);
        }
        $this->storeUploads($request, $vendorRegistration);

        return redirect()->route('legal.vendor-onboarding.edit', $vendorRegistration)->with('success', 'Registration saved.');
    }

    public function show(VendorRegistration $vendorRegistration)
    {
        $reg = $vendorRegistration;
        $me  = $this->meId();

        // Pemegang slug halaman membuka semuanya; penunjuk tanpa slug hanya yang ia ikuti.
        abort_unless(
            $this->holds(self::SLUG) || app(ApprovalEngine::class)->participates(self::MODULE, $reg, $me),
            403
        );

        $reg->load(['approvals.actor.basicData', 'approvals.role', 'documents.field', 'logs', 'customer.basicData']);

        $gate = $this->service->canAct($reg, $me, $this->holds(self::SLUG_APPROVE));
        $me_  = $this->me();

        return view('legal.vendor-onboarding.show', [
            'reg' => $reg, 'rounds' => $reg->approvals->groupBy('round'),
            'fields' => $this->service->documentFields($reg->vendor_type)->keyBy('id'),
            'questions' => $this->service->questions($reg->vendor_type), 'answers' => $reg->answers()->pluck('answer', 'question_id'),
            'canDecide' => $gate['allowed'], 'decideWhy' => $gate['reason'], 'backRoute' => route('legal.vendor-onboarding.index'),
            'readiness' => $reg->isEditable() ? $this->service->readiness($reg) : [],
            'canEdit' => $this->holds(self::SLUG) && $me_?->hasMenuPermission(self::SLUG, 'can_edit'),
            'canDelete' => $this->holds(self::SLUG) && $me_?->hasMenuPermission(self::SLUG, 'can_delete'),
            'canCreatePartner' => $this->holds(self::SLUG_MASTER_CREATE),
            'vendors' => $reg->status === VendorRegistration::STATUS_VERIFIED && !$reg->customer_id
                ? Customer::where('type', Customer::TYPE_VENDOR)->with('basicData:basic_data_id,customer_id,name_1')->get()
                    ->map(fn ($c) => ['id' => $c->customer_id, 'label' => $c->customer_code . ' — ' . $c->basicData?->name_1])->sortBy('label')->values()
                : collect(),
        ]);
    }

    public function submit(VendorRegistration $vendorRegistration)
    {
        $result = $this->service->submit($vendorRegistration, $this->meId());
        if (!$result['ok']) {
            $detail = $result['missing'] ? ' Missing: ' . implode('; ', $result['missing']) . '.' : '';

            return back()->with('error', $result['reason'] . $detail);
        }

        return redirect()->route('legal.vendor-onboarding.show', $vendorRegistration)->with('success', 'Submitted. It can now be sent to review.');
    }

    public function startReview(VendorRegistration $vendorRegistration)
    {
        $result = $this->service->startReview($vendorRegistration, $this->meId());

        return $result['ok']
            ? redirect()->route('legal.vendor-onboarding.show', $vendorRegistration)->with('success', 'Review started. The first reviewer has been notified.')
            : back()->with('error', $result['reason']);
    }

    public function deleteDocument(VendorDocument $document)
    {
        $registration = $document->registration;
        abort_unless($registration->isEditable(), 403);

        $this->service->removeDocument($document);

        return redirect()->route('legal.vendor-onboarding.edit', $registration)->with('success', 'Document removed.');
    }

    /** Dokumen identitas/pajak: hanya yang boleh membuka pendaftarannya. */
    public function downloadDocument(VendorDocument $document)
    {
        $registration = $document->registration;
        abort_unless(
            $this->holds(self::SLUG) || app(ApprovalEngine::class)->participates(self::MODULE, $registration, $this->meId()),
            403
        );

        return $this->streamPrivate(VendorOnboardingService::DISK, $document->path, $document->original_name, $document->mime);
    }

    public function approve(Request $request, VendorRegistration $vendorRegistration)
    {
        $request->validate(['notes' => 'nullable|string|max:2000']);

        $result = $this->service->approve($vendorRegistration, $this->meId(), $request->input('notes'), $this->holds(self::SLUG_APPROVE));
        if (!$result['allowed']) {
            return back()->with('error', $result['reason']);
        }

        return back()->with('success', $result['completed'] ? 'Verified. You can now create or link the Business Partner.' : 'Approved. The registration moved to the next step.');
    }

    public function reject(Request $request, VendorRegistration $vendorRegistration)
    {
        $request->validate(['notes' => 'required|string|max:2000'], ['notes.required' => 'A note is required when rejecting.']);

        $result = $this->service->reject($vendorRegistration, $this->meId(), (string) $request->input('notes'), $this->holds(self::SLUG_APPROVE));
        if (!$result['allowed']) {
            return back()->with('error', $result['reason']);
        }
        $vendorRegistration->update(['admin_note' => $request->input('notes')]);

        return back()->with('success', 'Rejected.');
    }

    public function reopen(VendorRegistration $vendorRegistration)
    {
        $result = $this->service->reopen($vendorRegistration, $this->meId());

        return $result['ok']
            ? redirect()->route('legal.vendor-onboarding.edit', $vendorRegistration)->with('success', 'Reopened as a draft. Correct it and submit again.')
            : back()->with('error', $result['reason']);
    }

    public function createPartner(VendorRegistration $vendorRegistration)
    {
        abort_unless($this->holds(self::SLUG_MASTER_CREATE), 403, 'Creating a Business Partner needs the Create permission on Master → Business Partner.');

        $result = $this->service->createBusinessPartner($vendorRegistration, $this->meId());
        if (!$result['ok']) {
            return back()->with('error', $result['reason']);
        }

        $message = 'Business Partner ' . $result['customer']->customer_code . ' was created and linked.';
        if ($result['notes']) {
            $message .= ' Note: ' . implode(' ', $result['notes']);
        }

        return redirect()->route('legal.vendor-onboarding.show', $vendorRegistration)->with('success', $message);
    }

    public function linkPartner(Request $request, VendorRegistration $vendorRegistration)
    {
        $data = $request->validate(['customer_id' => ['required', 'integer', 'exists:customer,customer_id']]);

        $result = $this->service->linkBusinessPartner($vendorRegistration, (int) $data['customer_id'], $this->meId());

        return $result['ok']
            ? redirect()->route('legal.vendor-onboarding.show', $vendorRegistration)->with('success', 'Linked to the Business Partner.')
            : back()->with('error', $result['reason']);
    }

    public function destroy(VendorRegistration $vendorRegistration)
    {
        $result = $this->service->deleteDraft($vendorRegistration);

        return $result['ok']
            ? redirect()->route('legal.vendor-onboarding.index')->with('success', 'Draft deleted.')
            : back()->with('error', $result['reason']);
    }

    // ── private ─────────────────────────────────────────────────────────

    /** @return array<string,mixed> kolom profil yang sudah divalidasi */
    private function profile(Request $request, ?VendorRegistration $existing = null): array
    {
        $data = $request->validate([
            'vendor_type'       => [$existing ? 'nullable' : 'required', Rule::in([VendorRegistration::TYPE_B2B, VendorRegistration::TYPE_PERSONAL])],
            'company_name'      => ['required', 'string', 'max:255'],
            'company_address'   => ['nullable', 'string', 'max:2000'],
            'company_phone'     => ['nullable', 'string', 'max:50'],
            'company_email'     => ['nullable', 'email', 'max:255'],
            'company_website'   => ['nullable', 'string', 'max:255'],
            'npwp'              => ['nullable', 'string', 'max:50'],
            'nib'               => ['nullable', 'string', 'max:50'],
            'business_type'     => ['nullable', 'string', 'max:255'],
            'product_list'      => ['nullable', 'string', 'max:3000'],
            'bank_name'         => ['nullable', 'string', 'max:100'],
            'bank_account_no'   => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'pic_name'          => ['nullable', 'string', 'max:255'],
            'pic_phone'         => ['nullable', 'string', 'max:50'],
            'pic_email'         => ['nullable', 'email', 'max:255'],
            'files.*.*'         => ['nullable', 'file', 'mimes:' . implode(',', VendorOnboardingService::DOCUMENT_EXTENSIONS), 'max:' . VendorOnboardingService::DOCUMENT_MAX_KB],
        ], [
            'files.*.*.mimes' => 'Only PDF, JPG, PNG or WEBP files are accepted.',
            'files.*.*.max'   => 'A file may not be larger than 50 MB.',
        ]);

        unset($data['files']);
        if ($existing) {
            unset($data['vendor_type']); // tipe tidak berubah setelah dibuat: jenis dokumen dan label bergantung padanya
        }

        return $data;
    }

    /** Dokumen datang sebagai `files[<field_id>][]`. */
    private function storeUploads(Request $request, VendorRegistration $registration): void
    {
        $allowed = $this->service->documentFields($registration->vendor_type)->keyBy('id');

        foreach ((array) $request->file('files', []) as $fieldId => $files) {
            $field = $allowed[(int) $fieldId] ?? null;
            if (!$field) {
                continue;
            }
            foreach ((array) $files as $file) {
                if ($this->validUpload($file)) {
                    $this->service->addDocument($registration, $field, $file, $this->meId());
                }
            }
        }
    }

    private function formData(?VendorRegistration $reg, string $type): array
    {
        return [
            'reg' => $reg, 'type' => $type,
            'fields' => $this->service->documentFields($type),
            'questions' => $this->service->questions($type),
            'answers' => $reg ? $reg->answers()->pluck('answer', 'question_id') : collect(),
            'documents' => $reg ? $reg->documents->groupBy('field_id') : collect(),
            'readiness' => $reg ? $this->service->readiness($reg) : [],
            'required' => $this->service->requiredProfileFields($type),
        ];
    }
}
