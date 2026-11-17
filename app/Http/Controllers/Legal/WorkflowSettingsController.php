<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\Legal\SalesCompliance\SalesComplianceApprovalStep;
use App\Models\Legal\SalesCompliance\SalesComplianceQuestion;
use App\Models\Legal\VendorOnboarding\VendorApprovalStep;
use App\Models\Legal\VendorOnboarding\VendorDocumentField;
use App\Models\Legal\VendorOnboarding\VendorQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings Sales Compliance Approval dan Vendor Onboarding: langkah persetujuan, pertanyaan, dan (vendor) jenis dokumen.
 *
 * Satu controller untuk dua modul karena bentuknya identik; modul mana ditentukan dari alamat rutenya (`legal/vendor-onboarding/...` atau `legal/sales-compliance/...`).
 * Mengubah langkah HANYA berlaku untuk dokumen yang diajukan sesudahnya: dokumen berjalan memegang salinan langkahnya sendiri.
 */
class WorkflowSettingsController extends Controller
{
    public function index(Request $request)
    {
        $c = $this->config($request);
        $stepModel = $c['step'];

        return view('legal.workflow-settings', [
            'flow' => $c['flow'], 'title' => $c['title'], 'routePrefix' => $c['routes'],
            'steps' => $stepModel::query()->where('module', $c['module'])->orderBy('order_seq')->get(),
            'roles' => EmployeeRole::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::with('basicData')->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->employee_id, 'name' => $e->fullNameLabel()])->sortBy('name')->values(),
            'questions' => ($c['question'])::query()->orderBy('sort_order')->orderBy('id')->get(),
            'fields' => $c['flow'] === 'vendor' ? VendorDocumentField::query()->orderBy('sort_order')->orderBy('id')->get() : collect(),
            'canCreate' => (bool) Employee::find((int) session('user.id'))?->hasMenuPermission($c['slug'], 'can_create'),
            'canEdit' => (bool) Employee::find((int) session('user.id'))?->hasMenuPermission($c['slug'], 'can_edit'),
            'canDelete' => (bool) Employee::find((int) session('user.id'))?->hasMenuPermission($c['slug'], 'can_delete'),
        ]);
    }

    // ── Langkah persetujuan ─────────────────────────────────────────────

    public function storeStep(Request $request)
    {
        $c = $this->config($request);
        $data = $this->stepData($request, $c, null);
        $stepModel = $c['step'];

        $stepModel::create($data + ['module' => $c['module']]);

        return back()->with('success', 'Approval step added. It applies to requests submitted from now on.');
    }

    public function updateStep(Request $request, int $step)
    {
        $c = $this->config($request);
        $stepModel = $c['step'];
        $row = $stepModel::where('module', $c['module'])->findOrFail($step);

        $row->update($this->stepData($request, $c, $row));

        return back()->with('success', 'Approval step updated. It applies to requests submitted from now on.');
    }

    public function destroyStep(Request $request, int $step)
    {
        $c = $this->config($request);
        $stepModel = $c['step'];
        $stepModel::where('module', $c['module'])->findOrFail($step)->delete();

        return back()->with('success', 'Approval step deleted. Requests already in review keep their own copy of the steps.');
    }

    // ── Pertanyaan ──────────────────────────────────────────────────────

    public function storeQuestion(Request $request)
    {
        $c = $this->config($request);
        ($c['question'])::create($this->questionData($request, $c));

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, int $question)
    {
        $c = $this->config($request);
        ($c['question'])::findOrFail($question)->update($this->questionData($request, $c));

        return back()->with('success', 'Question updated.');
    }

    /** Pertanyaan dinonaktifkan, bukan dihapus: jawaban lama tetap terbaca. */
    public function toggleQuestion(Request $request, int $question)
    {
        $c = $this->config($request);
        $row = ($c['question'])::findOrFail($question);
        $row->update(['is_active' => !$row->is_active]);

        return back()->with('success', $row->is_active ? 'Question switched on.' : 'Question switched off. Existing answers stay readable.');
    }

    // ── Jenis dokumen vendor ────────────────────────────────────────────

    public function storeField(Request $request)
    {
        VendorDocumentField::create($this->fieldData($request));

        return back()->with('success', 'Document type added.');
    }

    public function updateField(Request $request, int $field)
    {
        VendorDocumentField::findOrFail($field)->update($this->fieldData($request));

        return back()->with('success', 'Document type updated.');
    }

    public function toggleField(int $field)
    {
        $row = VendorDocumentField::findOrFail($field);
        $row->update(['is_active' => !$row->is_active]);

        return back()->with('success', $row->is_active ? 'Document type switched on.' : 'Document type switched off. Uploaded files stay.');
    }

    // ── private ─────────────────────────────────────────────────────────

    private function config(Request $request): array
    {
        return $request->is('legal/vendor-onboarding/*')
            ? [
                'flow' => 'vendor', 'title' => 'Vendor Onboarding', 'step' => VendorApprovalStep::class, 'module' => VendorApprovalStep::MODULE,
                'question' => VendorQuestion::class, 'slug' => 'legal.vendor-onboarding.settings', 'routes' => 'legal.vendor-onboarding.settings',
            ]
            : [
                'flow' => 'sales', 'title' => 'Sales Compliance Approval', 'step' => SalesComplianceApprovalStep::class, 'module' => SalesComplianceApprovalStep::MODULE,
                'question' => SalesComplianceQuestion::class, 'slug' => 'legal.sales-compliance.settings', 'routes' => 'legal.sales-compliance.settings',
            ];
    }

    private function stepData(Request $request, array $c, $existing): array
    {
        $stepModel = $c['step'];

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'approver_type'         => ['required', Rule::in(['role', 'employee'])],
            'approver_role_id'      => ['nullable', 'required_if:approver_type,role', 'integer', 'exists:employee_role,id'],
            'approver_employee_ids' => ['nullable', 'required_if:approver_type,employee', 'array', 'min:1'],
            'approver_employee_ids.*' => ['integer', 'exists:employee,employee_id'],
            'order_seq'             => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_active'             => ['nullable', 'boolean'],
        ], [
            'approver_role_id.required_if'      => 'Choose the position that approves this step.',
            'approver_employee_ids.required_if' => 'Choose at least one employee for this step.',
        ]);

        $order = $data['order_seq'] ?? ($stepModel::where('module', $c['module'])->max('order_seq') + 1);
        $clash = $stepModel::where('module', $c['module'])->where('order_seq', $order)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists();
        if ($clash) {
            abort(back()->withErrors(['order_seq' => "Another step already uses order {$order}. Pick a free number."])->withInput());
        }

        $isRole = $data['approver_type'] === 'role';

        return [
            'name' => trim($data['name']), 'order_seq' => $order, 'approver_type' => $data['approver_type'],
            'approver_role_id' => $isRole ? (int) $data['approver_role_id'] : null,
            'approver_employee_ids' => $isRole ? null : array_map('intval', $data['approver_employee_ids']),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function questionData(Request $request, array $c): array
    {
        $data = $request->validate([
            'text_en'    => ['required', 'string', 'max:2000'],
            'text_id'    => ['nullable', 'string', 'max:2000'],
            'type'       => ['required', Rule::in(['radio', 'checkbox', 'text', 'textarea'])],
            'options'    => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'section'    => ['nullable', 'string', 'max:255'],
            'vendor_type' => ['nullable', Rule::in(['all', 'b2b', 'personal'])],
        ]);

        $options = in_array($data['type'], ['radio', 'checkbox'], true)
            ? collect(preg_split('/\R/', (string) ($data['options'] ?? '')))->map(fn ($o) => trim($o))->filter()->values()->all()
            : [];

        if (in_array($data['type'], ['radio', 'checkbox'], true) && count($options) < 1) {
            abort(back()->withErrors(['options' => 'Give at least one choice, one per line.'])->withInput());
        }

        $row = [
            'text_en' => trim($data['text_en']), 'text_id' => $data['text_id'] ?? null, 'type' => $data['type'],
            'options' => $options, 'is_required' => $request->boolean('is_required'), 'sort_order' => $data['sort_order'] ?? 0,
        ];

        return $c['flow'] === 'vendor'
            ? $row + ['vendor_type' => $data['vendor_type'] ?? 'all']
            : $row + ['section' => trim((string) ($data['section'] ?? ''))];
    }

    private function fieldData(Request $request): array
    {
        $data = $request->validate([
            'label_en'     => ['required', 'string', 'max:255'],
            'label_id'     => ['nullable', 'string', 'max:255'],
            'vendor_type'  => ['required', Rule::in(['all', 'b2b', 'personal'])],
            'template_url' => ['nullable', 'url', 'max:500'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        return [
            'label_en' => trim($data['label_en']), 'label_id' => $data['label_id'] ?? null, 'vendor_type' => $data['vendor_type'],
            'template_url' => $data['template_url'] ?? null, 'sort_order' => $data['sort_order'] ?? 0,
            'is_required' => $request->boolean('is_required'),
        ];
    }
}
