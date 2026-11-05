<?php

namespace App\Services\Contracts;

use App\Models\ContractOption;
use App\Models\ContractTemplate;
use App\Services\Letters\LetterService;
use App\Support\Contracts\ContractHtmlSanitizer;
use App\Support\Contracts\ContractPlaceholders;
use App\Support\Contracts\ContractRules;
use App\Support\Contracts\PageSettings;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ekspor / impor Template kontrak (HR & General → Contract → Templates) sebagai satu berkas JSON.
 *
 * Dipakai untuk memindahkan template antarlingkungan (mis. dari UAT ke produksi) dan memulihkannya sesudah database
 * dibersihkan. Berkas berisi TEKS template saja (dengan {{placeholder}}), tanpa data karyawan.
 *
 * Alur impor dibagi dua supaya aman: analyze() hanya MEMBACA dan menjelaskan apa yang akan terjadi per template;
 * apply() menyimpan lewat ContractService::saveTemplate, jalur yang sama dengan formulir biasa, jadi sanitasi teks,
 * pemeriksaan placeholder, dan aturan "bawaan sistem" tetap berlaku.
 */
final class ContractTemplateTransfer
{
    public const FORMAT = 'ecosystem.contract-templates';
    public const VERSION = 1;
    public const MAX_TEMPLATES = 60;
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const ACT_CREATE = 'create';
    public const ACT_UPDATE = 'update';
    public const ACT_COPY = 'copy';
    public const ACT_SKIP = 'skip';

    /** @param Closure|null $signerIds  () => int[] id karyawan yang boleh menandatangani; bawaan: Letter Templates → Settings → Signers */
    public function __construct(
        private ContractService $contracts,
        private ?Closure $signerIds = null,
    ) {
    }

    // ── Ekspor ────────────────────────────────────────────────────────────

    /** @param Collection<int,ContractTemplate> $templates */
    public function export(Collection $templates): array
    {
        $ids = $templates->flatMap(fn ($t) => [$t->signatory_employee_id, $t->approver_employee_id])->filter()->unique()->values()->all();
        $eci = $ids ? DB::table('employee')->whereIn('employee_id', $ids)->pluck('eci', 'employee_id')->all() : [];
        $ref = fn ($id) => $id ? ['eci' => $eci[$id] ?? null] : null;

        return [
            'format'      => self::FORMAT,
            'version'     => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'count'       => $templates->count(),
            'templates'   => $templates->map(fn (ContractTemplate $t) => [
                'name'              => $t->name,
                'contract_type'     => $t->contract_type,
                'position'          => $t->position,
                'position_type'     => $t->position_type,
                'description'       => $t->description,
                'status'            => $t->status,
                'is_system_default' => (bool) $t->is_system_default,
                'use_letterhead'    => (bool) $t->use_letterhead,
                'page'              => [
                    'size'        => $t->page_size,
                    'orientation' => $t->page_orientation,
                    'margins'     => ['top' => $t->margin_top, 'right' => $t->margin_right, 'bottom' => $t->margin_bottom, 'left' => $t->margin_left],
                ],
                'signatory'         => $ref($t->signatory_employee_id),
                'approver'          => $ref($t->approver_employee_id),
                'body_html'         => $t->body_html,
            ])->values()->all(),
        ];
    }

    /**
     * Satu template sebagai berkas HTML yang bisa dibuka di peramban dan disunting di editor teks/HTML, lalu diimpor lagi.
     * Rincian (nama, jenis, kertas, penandatangan) ada di blok JSON `ec-template`; teks kontrak di antara dua penanda.
     */
    public function exportHtml(ContractTemplate $template): string
    {
        $data = $this->export(collect([$template]));
        $meta = ['format' => self::FORMAT, 'version' => self::VERSION] + $data['templates'][0];
        unset($meta['body_html']);
        $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

        return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n<title>" . e($template->name) . "</title>\n"
            . "<!-- Contract template. Edit the text between the two ec-body markers. Keep the {{placeholders}}, the ec-template block and the markers. Import it again from Contract > Templates > Import templates. -->\n"
            . "<script type=\"application/json\" id=\"ec-template\">\n{$json}\n</script>\n</head>\n<body>\n<!-- ec-body-start -->\n"
            . $template->body_html . "\n<!-- ec-body-end -->\n</body>\n</html>\n";
    }

    // ── Impor: baca ───────────────────────────────────────────────────────

    /**
     * @return array<int,array<string,mixed>> template mentah dari berkas (.json hasil ekspor, atau satu berkas .html hasil ekspor HTML)
     * @throws \DomainException
     */
    public function parse(string $raw): array
    {
        if (strlen($raw) > self::MAX_BYTES) {
            throw new \DomainException('The file is larger than ' . (self::MAX_BYTES / 1024 / 1024) . ' MB.');
        }
        $head = ltrim($raw, "\xEF\xBB\xBF \t\r\n");
        if (str_starts_with($head, '%PDF-')) {
            throw new \DomainException('A PDF is for reading and printing only; it cannot be imported back. Use the .json file (or the .html file for a single template) from Export.');
        }
        if (str_starts_with($head, '<')) {
            return [$this->parseHtml($head)];
        }
        $data = json_decode($head, true, 64);
        if (!is_array($data) || ($data['format'] ?? null) !== self::FORMAT) {
            throw new \DomainException('This is not a contract template file. Use a file made by "Export templates" on this page.');
        }
        if ((int) ($data['version'] ?? 0) > self::VERSION) {
            throw new \DomainException('This file was made by a newer version of the system. Update the system first, or export it again.');
        }
        $items = $data['templates'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new \DomainException('The file contains no templates.');
        }
        if (count($items) > self::MAX_TEMPLATES) {
            throw new \DomainException('The file contains more than ' . self::MAX_TEMPLATES . ' templates.');
        }

        return array_values(array_filter($items, 'is_array'));
    }

    /**
     * Apa yang akan terjadi pada tiap template. TIDAK menulis apa pun.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array<string,mixed>>
     */
    public function analyze(array $items): array
    {
        $out = [];
        foreach ($items as $i => $raw) {
            $n = $this->normalize($raw);
            $match = $n['d'] && $n['type'] ? $this->findMatch($n['type'], $n['d']['name'], $n['d']['position_type'] ?? null) : null;
            $errors = $n['errors'];
            $warnings = $n['warnings'];
            $identical = $match && !$errors && $this->sameAsStored($match, $n['d']);

            $options = [self::ACT_SKIP];
            if (!$errors) {
                $options = $match ? [self::ACT_UPDATE, self::ACT_COPY, self::ACT_SKIP] : [self::ACT_CREATE, self::ACT_SKIP];
            }
            if ($identical) {
                $warnings[] = 'Same as the template already here: nothing to update.';
            }
            $restore = $n['restoreSystem'] && !$match && !$errors
                && !ContractTemplate::where('contract_type', $n['type'])->where('is_system_default', true)->exists();
            if ($restore) {
                $warnings[] = 'Will be restored as the system default of its type (none exists yet).';
            }

            $out[] = [
                'index'      => $i,
                'name'       => (string) ($n['d']['name'] ?? ($raw['name'] ?? '(no name)')),
                'type'       => $n['type'],
                'type_label' => $n['type'] ? (ContractRules::TYPES[$n['type']] ?? $n['type']) : (string) ($raw['contract_type'] ?? ''),
                'applies_to' => $this->appliesTo($n['type'], $n['d']['position_type'] ?? null, $n['d']['position'] ?? null),
                'status'     => $n['d']['status'] ?? null,
                'system'     => (bool) ($raw['is_system_default'] ?? false),
                'chars'      => mb_strlen(strip_tags((string) ($n['d']['body_html'] ?? ''))),
                'match'      => $match ? ['id' => $match->id, 'name' => $match->name, 'system' => (bool) $match->is_system_default] : null,
                'identical'  => $identical,
                'errors'     => $errors,
                'warnings'   => array_values(array_unique($warnings)),
                'options'    => $options,
                'default'    => $errors ? self::ACT_SKIP : ($match ? ($identical ? self::ACT_SKIP : self::ACT_UPDATE) : self::ACT_CREATE),
            ];
        }

        return $out;
    }

    // ── Impor: simpan ─────────────────────────────────────────────────────

    /**
     * @param  array<int,array<string,mixed>>  $items
     * @param  array<int|string,string>        $decisions  indeks → create|update|copy|skip (tanpa keputusan = skip)
     * @return array{created:int,updated:int,skipped:int,failed:int,rows:array<int,array<string,mixed>>}
     */
    public function apply(array $items, array $decisions, int $actorId, bool $canEdit): array
    {
        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'rows' => []];
        $row = function (int $i, string $name, string $result, string $message = '') use (&$report) {
            $report[$result]++;
            $report['rows'][] = ['index' => $i, 'name' => $name, 'result' => $result, 'message' => $message];
        };

        foreach ($items as $i => $raw) {
            $act = (string) ($decisions[$i] ?? $decisions[(string) $i] ?? self::ACT_SKIP);
            $n = $this->normalize($raw);
            $name = (string) ($n['d']['name'] ?? ($raw['name'] ?? '(no name)'));

            if ($act === self::ACT_SKIP || !in_array($act, [self::ACT_CREATE, self::ACT_UPDATE, self::ACT_COPY], true)) {
                $row($i, $name, 'skipped', 'Skipped.');
                continue;
            }
            if ($n['errors']) {
                $row($i, $name, 'failed', implode(' ', $n['errors']));
                continue;
            }

            $d = $n['d'];
            $match = $this->findMatch($n['type'], $d['name'], $d['position_type'] ?? null);

            try {
                if ($act === self::ACT_UPDATE) {
                    if (!$match) {
                        throw new \DomainException('There is no template of this name and type to update.');
                    }
                    if (!$canEdit) {
                        throw new \DomainException('Updating needs the Edit permission on Templates.');
                    }
                    $this->contracts->saveTemplate($match, $d, $actorId);
                    $row($i, $name, 'updated', 'Updated.');
                    continue;
                }

                if ($act === self::ACT_CREATE && $match) {
                    throw new \DomainException('A template with this name and type exists now. Choose Update, Copy or Skip.');
                }
                if ($act === self::ACT_COPY) {
                    $d['name'] = mb_substr($d['name'], 0, 150 - 22) . ' (imported ' . now()->format('Y-m-d') . ')';
                }
                $restore = $act === self::ACT_CREATE && $n['restoreSystem']
                    && !ContractTemplate::where('contract_type', $n['type'])->where('is_system_default', true)->exists();
                $created = $this->contracts->saveTemplate(null, $d, $actorId);
                if ($restore) {
                    $created->update(['is_system_default' => true, 'status' => ContractTemplate::STATUS_ACTIVE]);
                }
                $row($i, $name, 'created', $restore ? 'Created and restored as the system default.' : 'Created.');
            } catch (\DomainException $e) {
                $row($i, $name, 'failed', $e->getMessage());
            }
        }

        return $report;
    }

    // ── Bagian dalam ──────────────────────────────────────────────────────

    /** @return array<string,mixed> @throws \DomainException */
    private function parseHtml(string $html): array
    {
        if (!preg_match('~<script[^>]*\sid=["\']ec-template["\'][^>]*>(.*?)</script>~is', $html, $m)) {
            throw new \DomainException('This HTML file has no template details (name, contract type, paper). Use a file made by Export > HTML and edit it in a text or HTML editor; Word removes these details.');
        }
        $meta = json_decode(trim($m[1]), true, 16);
        if (!is_array($meta) || ($meta['format'] ?? null) !== self::FORMAT) {
            throw new \DomainException('This is not a contract template file. Use a file made by Export on this page.');
        }
        if ((int) ($meta['version'] ?? 0) > self::VERSION) {
            throw new \DomainException('This file was made by a newer version of the system. Update the system first, or export it again.');
        }
        if (preg_match('~<!--\s*ec-body-start\s*-->(.*?)<!--\s*ec-body-end\s*-->~s', $html, $b)) {
            $body = $b[1];
        } elseif (preg_match('~<body[^>]*>(.*)</body>~is', $html, $b)) {
            $body = $b[1];
        } else {
            throw new \DomainException('The contract text was not found in the HTML file (keep the ec-body markers).');
        }
        unset($meta['format'], $meta['version']);
        $meta['body_html'] = trim($body);

        return $meta;
    }

    /**
     * Template mentah → masukan untuk ContractService::saveTemplate + catatan.
     *
     * @return array{d:?array<string,mixed>,type:?string,errors:string[],warnings:string[],restoreSystem:bool}
     */
    private function normalize(array $t): array
    {
        $errors = [];
        $warnings = [];
        $text = fn ($v) => is_string($v) ? trim($v) : '';

        $name = $text($t['name'] ?? '');
        if ($name === '') {
            $errors[] = 'The template has no name.';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'The name is longer than 150 characters.';
        }

        $type = ContractRules::normalizeType($text($t['contract_type'] ?? ''));
        if ($type === null) {
            $errors[] = 'Unknown contract type “' . $text($t['contract_type'] ?? '') . '”.';
        } elseif ($type !== ContractRules::TYPE_EXTERNAL && !array_key_exists($type, $this->enabledInternalTypes())) {
            $warnings[] = 'The contract type ' . (ContractRules::TYPES[$type] ?? $type) . ' is switched off in Contract → Settings. The template is saved, but its type is not offered until it is switched on.';
        }

        $rawBody = is_string($t['body_html'] ?? null) ? $t['body_html'] : '';
        $body = '';
        if (mb_strlen($rawBody) > 300000) {
            $errors[] = 'The contract text is too long.';
        } else {
            $body = ContractHtmlSanitizer::clean($rawBody);
            if (trim(strip_tags($body)) === '') {
                $errors[] = 'The contract text is empty.';
            } elseif ($type !== null && ($unknown = ContractRules::unknownPlaceholders($body, ContractPlaceholders::keysFor($type)))) {
                $errors[] = 'Unknown placeholder' . (count($unknown) > 1 ? 's' : '') . ': {{' . implode('}}, {{', $unknown) . '}}.';
            }
        }

        $description = $text($t['description'] ?? '');
        if (mb_strlen($description) > 500) {
            $errors[] = 'The description is longer than 500 characters.';
        }
        $position = $type === ContractRules::TYPE_EXTERNAL ? '' : $text($t['position'] ?? '');
        $positionType = $text($t['position_type'] ?? '');
        if (mb_strlen($position) > 255 || mb_strlen($positionType) > 100) {
            $errors[] = 'The position or position type is too long.';
        }
        if ($positionType !== '' && $type !== null && ($known = $this->positionTypes($type)) !== null && !in_array($positionType, $known, true)) {
            $warnings[] = "Position type “{$positionType}” is not in Contract → Settings yet. The template is saved; add the position type there so contracts can use it.";
        }

        $status = $text($t['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
            $warnings[] = 'Unknown status; the template is set to Active.';
        }

        $page = is_array($t['page'] ?? null) ? $t['page'] : [];
        $margins = is_array($page['margins'] ?? null) ? $page['margins'] : [];
        $size = $page['size'] ?? null;
        if (is_string($size) && $size !== '' && !isset(PageSettings::SIZES[strtoupper($size)])) {
            $warnings[] = "Paper size “{$size}” is not available; A4 is used.";
        }

        $signatory = $this->resolveSigner($t['signatory'] ?? null, 'Default signatory', $warnings);
        $approver = $this->resolveSigner($t['approver'] ?? null, 'Approver', $warnings);

        $d = [
            'name'                  => $name,
            'contract_type'         => $type ?? '',
            'position'              => $position !== '' ? $position : null,
            'position_type'         => $positionType !== '' ? $positionType : null,
            'description'           => $description !== '' ? $description : null,
            'body_html'             => $body,
            'use_letterhead'        => (bool) ($t['use_letterhead'] ?? true),
            'signatory_employee_id' => $signatory,
            'approver_employee_id'  => $approver,
            'page_size'             => is_string($size) ? $size : null,
            'page_orientation'      => is_string($page['orientation'] ?? null) ? $page['orientation'] : null,
            'margin_top'            => $margins['top'] ?? null,
            'margin_right'          => $margins['right'] ?? null,
            'margin_bottom'         => $margins['bottom'] ?? null,
            'margin_left'           => $margins['left'] ?? null,
            'status'                => $status,
        ];

        return [
            'd'             => $d,
            'type'          => $type,
            'errors'        => $errors,
            'warnings'      => $warnings,
            'restoreSystem' => !empty($t['is_system_default']) && $type !== null,
        ];
    }

    /** @param string[] $warnings */
    private function resolveSigner($ref, string $label, array &$warnings): ?int
    {
        $eci = is_array($ref) ? trim((string) ($ref['eci'] ?? '')) : '';
        if ($eci === '') {
            return null;
        }
        $id = DB::table('employee')->where('eci', $eci)->value('employee_id');
        if (!$id) {
            $warnings[] = "{$label} {$eci} does not exist here; left empty.";

            return null;
        }
        if (!in_array((int) $id, $this->allowedSigners(), true)) {
            $warnings[] = "{$label} {$eci} is not a signer here (Letter Templates → Settings → Signers); left empty.";

            return null;
        }

        return (int) $id;
    }

    /** @return int[] */
    private function allowedSigners(): array
    {
        if ($this->signerIds) {
            return array_map('intval', ($this->signerIds)());
        }

        return LetterService::signatoryOptions()->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    private function findMatch(string $type, string $name, ?string $positionType): ?ContractTemplate
    {
        $rows = ContractTemplate::where('contract_type', $type)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])->orderBy('id')->get();

        return $rows->firstWhere('position_type', $positionType) ?? $rows->first();
    }

    /** Apakah isi dan pengaturan template yang ada sama persis dengan yang diimpor. */
    private function sameAsStored(ContractTemplate $t, array $d): bool
    {
        $stored = PageSettings::store($t->page_size, $t->page_orientation, ['top' => $t->margin_top, 'right' => $t->margin_right, 'bottom' => $t->margin_bottom, 'left' => $t->margin_left]);
        $incoming = ContractService::pageColumns($d);
        $incPage = PageSettings::store($incoming['page_size'], $incoming['page_orientation'], ['top' => $incoming['margin_top'], 'right' => $incoming['margin_right'], 'bottom' => $incoming['margin_bottom'], 'left' => $incoming['margin_left']]);

        // Teks tersimpan bisa berasal dari migrasi/penyuntingan lama yang belum melewati sanitizer: bandingkan bentuk yang sudah dibersihkan.
        return ContractHtmlSanitizer::clean($t->body_html) === $d['body_html']
            && (string) $t->description === (string) ($d['description'] ?? '')
            && (bool) $t->use_letterhead === (bool) $d['use_letterhead']
            && (string) $t->position === (string) ($d['position'] ?? '')
            && (string) $t->position_type === (string) ($d['position_type'] ?? '')
            && ($t->is_system_default || $t->status === $d['status'])
            && (int) $t->signatory_employee_id === (int) ($d['signatory_employee_id'] ?? 0)
            && (int) $t->approver_employee_id === (int) ($d['approver_employee_id'] ?? 0)
            && $stored === $incPage;
    }

    private function appliesTo(?string $type, ?string $positionType, ?string $position): string
    {
        return $positionType ?: ($position ?: ($type === ContractRules::TYPE_EXTERNAL ? 'External consultants' : 'All positions'));
    }

    /** @return array<string,string> */
    private function enabledInternalTypes(): array
    {
        try {
            return app(ContractOptionService::class)->options(ContractOption::SCOPE_INTERNAL, ContractOption::KIND_CONTRACT_TYPE);
        } catch (\Throwable) {
            return array_combine(ContractRules::INTERNAL_TYPES, ContractRules::INTERNAL_TYPES);   // tanpa data pengaturan: jangan memberi peringatan palsu
        }
    }

    /** @return string[]|null null bila daftar pengaturan tidak terbaca (tidak memberi peringatan palsu) */
    private function positionTypes(string $type): ?array
    {
        try {
            $scope = $type === ContractRules::TYPE_EXTERNAL ? ContractOption::SCOPE_EXTERNAL : ContractOption::SCOPE_INTERNAL;

            return app(ContractOptionService::class)->positionTypes($scope);
        } catch (\Throwable) {
            return null;
        }
    }
}
