<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\ContractOption;
use App\Models\ContractTemplate;
use App\Models\Letterhead;
use App\Models\Letters\LetterSetting;
use App\Models\Position;
use App\Services\Letters\LetterService;
use App\Services\Contracts\ContractOptionService;
use App\Models\Employee;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\ContractTemplateTransfer;
use App\Support\Contracts\ContractHtmlSanitizer;
use App\Support\Contracts\ContractPlaceholders;
use App\Support\Contracts\ContractRules;
use App\Support\Contracts\PageSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * HR & General → Contract → Templates (slug general.contracts.templates): teks kontrak dengan {{placeholder}}.
 *
 * V lihat & pratinjau · C buat template · E ubah (juga bawaan sistem) · D hapus (bukan bawaan sistem).
 * Kop surat TIDAK diatur di sini: dipasang di Letter Templates → Settings sebagai jenis "Employment Contract".
 */
class ContractTemplateController extends Controller
{
    public function __construct(private ContractService $contracts, private ContractOptionService $options) {}

    public function index()
    {
        return view('legal.contracts.templates', [
            'templates'  => ContractTemplate::orderByDesc('is_system_default')->orderBy('contract_type')->orderBy('name')->get(),
            'types'      => ContractRules::TYPES,
            'letterhead' => Letterhead::forLetter(ContractService::LETTER_TYPE),
            // What the contract pages take from HR & General → Letter Templates → Settings.
            'linked'     => [
                'company' => LetterSetting::current()->company_name,
                'city'    => LetterSetting::current()->signing_city,
                'signers' => LetterService::signatoryOptions()->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $type = ContractRules::normalizeType((string) $request->query('contract_type')) ?? ContractRules::TYPE_PKWT;

        return $this->form(new ContractTemplate(['contract_type' => $type, 'status' => ContractTemplate::STATUS_ACTIVE, 'use_letterhead' => true, 'body_html' => '']));
    }

    public function edit(int $templateId)
    {
        return $this->form(ContractTemplate::findOrFail($templateId));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        try {
            $this->contracts->saveTemplate(null, $data, (int) session('user.id'));
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['template' => $e->getMessage()]);
        }

        return redirect()->route('general.contracts.templates.index')->with('success', "Template “{$data['name']}” created.");
    }

    public function update(Request $request, int $templateId)
    {
        $template = ContractTemplate::findOrFail($templateId);
        $data = $request->validate($this->rules());

        try {
            $this->contracts->saveTemplate($template, $data, (int) session('user.id'));
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['template' => $e->getMessage()]);
        }

        return redirect()->route('general.contracts.templates.index')->with('success', "Template “{$data['name']}” saved.");
    }

    public function destroy(int $templateId)
    {
        $template = ContractTemplate::findOrFail($templateId);

        try {
            $this->contracts->deleteTemplate($template);
        } catch (\DomainException $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }

        return redirect()->route('general.contracts.templates.index')->with('success', "Template “{$template->name}” deleted.");
    }

    // ── Ekspor / impor template (berkas JSON) ─────────────────────────────

    /**
     * Semua template (atau yang dipilih lewat ?ids[]=) dalam tiga bentuk (?format=):
     *  json (bawaan) — untuk cadangan/pindah lingkungan, bisa diimpor lagi;
     *  html — SATU template, bisa disunting di editor lalu diimpor lagi;
     *  pdf  — contoh kontrak dengan nilai contoh untuk dibaca/dicetak (banyak template = ZIP); TIDAK bisa diimpor.
     * Teks template saja, tanpa data karyawan.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'json'));
        if (!in_array($format, ['json', 'html', 'pdf'], true)) {
            return back()->withErrors(['template' => 'Unknown export format.']);
        }
        $ids = array_values(array_filter(array_map('intval', (array) $request->query('ids', []))));
        $templates = ContractTemplate::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderByDesc('is_system_default')->orderBy('contract_type')->orderBy('name')->get();
        $transfer = new ContractTemplateTransfer($this->contracts);
        $stamp = now()->format('Ymd-His');

        if ($format !== 'json' && $templates->isEmpty()) {
            return back()->withErrors(['template' => 'There is no template to export.']);
        }

        if ($format === 'html') {
            if ($templates->count() !== 1) {
                return back()->withErrors(['template' => 'The HTML export is for one template at a time. Use the HTML link in that template’s row, or choose JSON for several.']);
            }
            $t = $templates->first();
            $html = $transfer->exportHtml($t);

            return response()->streamDownload(function () use ($html) {
                echo $html;
            }, $this->safeName($t->name) . '.html', ['Content-Type' => 'text/html; charset=utf-8']);
        }

        if ($format === 'pdf') {
            if ($templates->count() === 1) {
                $t = $templates->first();

                return response()->streamDownload(function () use ($t) {
                    echo $this->renderPdf($t)->output();
                }, $this->safeName($t->name) . '.pdf', ['Content-Type' => 'application/pdf']);
            }
            if (!class_exists(\ZipArchive::class)) {
                return back()->withErrors(['template' => 'The server cannot make a ZIP file. Export the templates one by one, or choose JSON.']);
            }
            if ($templates->count() > 40) {
                return back()->withErrors(['template' => 'Too many templates for one PDF package (maximum 40). Export them one by one.']);
            }
            @set_time_limit(120);
            $tmp = tempnam(sys_get_temp_dir(), 'tplpdf');
            $zip = new \ZipArchive();
            if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
                return back()->withErrors(['template' => 'The ZIP file could not be created.']);
            }
            $used = [];
            foreach ($templates as $t) {
                $base = $this->safeName($t->name);
                $name = $base;
                for ($n = 2; isset($used[$name]); $n++) {
                    $name = $base . '-' . $n;
                }
                $used[$name] = true;
                $zip->addFromString($name . '.pdf', $this->renderPdf($t)->output());
            }
            $zip->close();

            return response()->download($tmp, 'contract-templates-pdf-' . $stamp . '.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
        }

        $bundle = $transfer->export($templates);

        return response()->streamDownload(function () use ($bundle) {
            echo json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        }, 'contract-templates-' . $stamp . '.json', ['Content-Type' => 'application/json; charset=utf-8']);
    }

    /** Nama berkas aman dari nama template. */
    private function safeName(string $name): string
    {
        return \Illuminate\Support\Str::slug($name) ?: 'template';
    }

    private function renderPdf(ContractTemplate $template)
    {
        $doc = [
            'title'      => $template->name,
            'number'     => 'SAMPLE',
            'html'       => ContractRules::render($template->body_html, ContractService::sampleValues($template->contract_type)),
            'letterhead' => $template->use_letterhead ? Letterhead::forLetter(ContractService::LETTER_TYPE) : null,
        ];
        $doc['page'] = PageSettings::resolve(ContractService::templatePage($template), $doc['letterhead'] !== null);

        return Pdf::loadView('legal.contracts.pdf', ['doc' => $doc])->setPaper(PageSettings::paper($doc));
    }

    /** Langkah 1: baca berkas dan jelaskan apa yang akan terjadi pada tiap template. Tidak menyimpan apa pun. */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $items = $this->readBundle($request);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $analysis = (new ContractTemplateTransfer($this->contracts))->analyze($items);

        return response()->json(['success' => true, 'count' => count($analysis), 'items' => $analysis, 'canEdit' => $this->canEditTemplates()]);
    }

    /** Langkah 2: simpan sesuai pilihan (create / update / copy / skip) per template. Berkas yang sama dikirim ulang dan diperiksa ulang. */
    public function importApply(Request $request): JsonResponse
    {
        try {
            $items = $this->readBundle($request);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $decisions = json_decode((string) $request->input('decisions', '{}'), true);
        if (!is_array($decisions)) {
            return response()->json(['success' => false, 'message' => 'The choices could not be read. Check the file again.'], 422);
        }

        $report = (new ContractTemplateTransfer($this->contracts))->apply($items, $decisions, (int) session('user.id'), $this->canEditTemplates());

        return response()->json(['success' => true, 'report' => $report]);
    }

    /** @return array<int,array<string,mixed>> @throws \DomainException */
    private function readBundle(Request $request): array
    {
        $request->validate(['file' => ['required', 'file', 'max:' . (int) (ContractTemplateTransfer::MAX_BYTES / 1024)]], [
            'file.max' => 'The file is larger than ' . (ContractTemplateTransfer::MAX_BYTES / 1024 / 1024) . ' MB.',
        ]);

        return (new ContractTemplateTransfer($this->contracts))->parse((string) file_get_contents($request->file('file')->getRealPath()));
    }

    private function canEditTemplates(): bool
    {
        return (bool) (Employee::find(session('user.id'))?->allPermissionMatrix()['general.contracts.templates']['edit'] ?? false);
    }

    /** Pratinjau dengan nilai contoh — tidak membaca data karyawan mana pun. */
    public function show(int $templateId)
    {
        $template = ContractTemplate::findOrFail($templateId);
        $values = ContractService::sampleValues($template->contract_type);

        return view('legal.contracts.template-preview', [
            'template'   => $template,
            'html'       => ContractRules::render($template->body_html, $values),
            'letterhead' => $template->use_letterhead ? Letterhead::forLetter(ContractService::LETTER_TYPE) : null,
            'pdfUrl'     => route('general.contracts.templates.pdf', $template->id),
        ]);
    }

    /** The sample contract as the real PDF (letterhead on every page) — what the preview page shows. */
    public function pdf(int $templateId)
    {
        return $this->renderPdf(ContractTemplate::findOrFail($templateId))->stream('template-' . $templateId . '.pdf');
    }

    /**
     * The text exactly as it is being edited (not yet saved) as the real PDF, with sample values and the letterhead of
     * "Employment Contract" — the editor's Preview. Reads nothing from the database about employees.
     */
    public function previewDraft(Request $request)
    {
        $data = $request->validate([
            'body_html'      => 'required|string|max:300000',
            'contract_type'  => ['required', Rule::in(array_keys(ContractRules::TYPES))],
            'use_letterhead' => 'nullable|boolean',
            ...$this->pageRules(),
        ]);
        $html = ContractHtmlSanitizer::clean($data['body_html']);
        $doc = [
            'title'      => 'Preview',
            'number'     => 'PREVIEW',
            'html'       => ContractRules::render($html, ContractService::sampleValues($data['contract_type'])),
            'letterhead' => ($data['use_letterhead'] ?? true) ? Letterhead::forLetter(ContractService::LETTER_TYPE) : null,
        ];
        $stored = ContractService::pageColumns($data);
        $doc['page'] = PageSettings::resolve(PageSettings::store($stored['page_size'], $stored['page_orientation'], ['top' => $stored['margin_top'], 'right' => $stored['margin_right'], 'bottom' => $stored['margin_bottom'], 'left' => $stored['margin_left']]), $doc['letterhead'] !== null);

        return Pdf::loadView('legal.contracts.pdf', ['doc' => $doc])->setPaper(PageSettings::paper($doc))->stream('preview.pdf');
    }

    private function form(ContractTemplate $template)
    {
        return view('legal.contracts.template-form', [
            'template'     => $template,
            'letterheadUri' => Letterhead::forLetter(ContractService::LETTER_TYPE)?->backgroundDataUri(),
            'pageSizes'    => PageSettings::sizeOptions(),
            'page'         => ['size' => $template->page_size ?: 'A4', 'orientation' => $template->page_orientation ?: 'portrait',
                'margins' => ['top' => $template->margin_top, 'right' => $template->margin_right, 'bottom' => $template->margin_bottom, 'left' => $template->margin_left]],
            'pageDefaults' => ['plain' => PageSettings::defaultMarginsCm(false), 'letterhead' => PageSettings::defaultMarginsCm(true)],
            // The types switched on in each menu's Settings (a template being edited keeps its own even if it was switched off).
            'types'        => $this->typeOptions($template->contract_type),
            'positionTypeSets' => [
                'internal' => $this->options->positionTypes(ContractOption::SCOPE_INTERNAL, $template->contract_type !== ContractRules::TYPE_EXTERNAL ? $template->position_type : null),
                'external' => $this->options->positionTypes(ContractOption::SCOPE_EXTERNAL, $template->contract_type === ContractRules::TYPE_EXTERNAL ? $template->position_type : null),
            ],
            'placeholders' => ['employee' => ContractPlaceholders::EMPLOYEE, 'external' => ContractPlaceholders::EXTERNAL],
            'positions'    => Position::options(),
            'signatories'  => LetterService::signatoryOptions(),
            'letterhead'   => Letterhead::forLetter(ContractService::LETTER_TYPE),
        ]);
    }

    private function rules(): array
    {
        return [
            'name'           => 'required|string|max:150',
            'contract_type'  => ['required', Rule::in(array_keys(ContractRules::TYPES))],
            'position'       => 'nullable|string|max:255',
            'position_type'  => 'nullable|string|max:100',
            'description'    => 'nullable|string|max:500',
            'body_html'      => 'required|string|max:300000',
            'use_letterhead' => 'nullable|boolean',
            'signatory_employee_id' => 'nullable|integer',
            'approver_employee_id'  => 'nullable|integer',
            'status'         => ['nullable', Rule::in(['active', 'inactive'])],
            ...$this->pageRules(),
        ];
    }

    /** Internal types switched on in Settings, the external type, and (when editing) the type the template already has. */
    private function typeOptions(?string $keep = null): array
    {
        $types = $this->options->options(ContractOption::SCOPE_INTERNAL, ContractOption::KIND_CONTRACT_TYPE, $keep);
        $types[ContractRules::TYPE_EXTERNAL] = ContractRules::TYPES[ContractRules::TYPE_EXTERNAL];

        return $types;
    }

    private function pageRules(): array
    {
        $m = 'nullable|numeric|min:' . PageSettings::MARGIN_MIN_CM . '|max:' . PageSettings::MARGIN_MAX_CM;

        return ['page_size' => ['nullable', Rule::in(array_keys(PageSettings::SIZES))], 'page_orientation' => ['nullable', Rule::in(['portrait', 'landscape'])],
            'margin_top' => $m, 'margin_right' => $m, 'margin_bottom' => $m, 'margin_left' => $m];
    }
}
