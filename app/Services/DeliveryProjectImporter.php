<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DeliveryProject;
use App\Models\DeliveryProjectType;
use App\Models\EmployeeBasicData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import Delivery Project dari Excel / CSV (tombol "Import Excel" di daftar
 * Project). Satu baris = satu project, dengan aturan yang sama seperti form
 * Create Project (DeliveryProjectController::store):
 *
 *  - Kolom dikenali dari NAMA header (urutan bebas), mengikuti template
 *    (lihat DeliveryProjectImportController::template). Baris judul grup di atas
 *    header dan kolom "No" diabaikan.
 *  - Wajib: Customer/Client, Project Owner, Project Type, Contract Start Date,
 *    Contract End Date, IO/Number Order, Project Name, Description.
 *  - Gross Profit & % Gross Profit tidak dibaca: dihitung ulang dari Revenue -
 *    Plan Cost, sama seperti form.
 *
 * Duplikat (diberi info lalu dilewati, tidak dianggap error):
 *  - baris yang SAMA PERSIS dengan baris sebelumnya di file yang sama;
 *  - project dengan Customer + IO Number + Project Name yang sama sudah ada di
 *    sistem (termasuk yang baru saja diimpor dari baris sebelumnya di file).
 *
 * Baris lain yang tidak valid dilewati dengan pesan; baris valid tetap diimpor.
 */
class DeliveryProjectImporter
{
    public const MAX_ROWS = 1000;

    /** field => alias header (sudah dinormalisasi: huruf kecil, tanpa "*"). */
    public const COLUMNS = [
        'customer'              => ['customer/client', 'customer / client', 'customer', 'client', 'customer code', 'client/customer'],
        'project_owner'         => ['project owner', 'delivery owner'],
        'project_type'          => ['project type'],
        'high_level_risk'       => ['high level risk'],
        'contract_start_date'   => ['contract start date', 'start date'],
        'contract_end_date'     => ['contract end date', 'end date'],
        'io_number'             => ['io/number order', 'io / number order', 'io number', 'io/number', 'io'],
        'name'                  => ['project name'],
        'description'           => ['description'],
        'ae_type'               => ['account executive type', 'ae type'],
        'ae_name'               => ['account executive name', 'ae name', 'account executive'],
        'ae_phone'              => ['phone number', 'ae phone', 'phone'],
        'ae_email'              => ['email', 'ae email'],
        'revenue'               => ['revenue (rp)', 'revenue'],
        'plan_cost'             => ['plan cost (rp)', 'plan cost'],
        'delivery_method'       => ['delivery method'],
        'warranty_period'       => ['warranty period (weeks)', 'warranty period'],
        'total_mandays'         => ['total mandays'],
        'location_name'         => ['location name'],
        'location_type'         => ['type of address', 'location type'],
        'location_country'      => ['country'],
        'location_geographical' => ['geographical'],
        'location_region'       => ['region / province', 'region/province', 'region', 'province'],
        'location_city'         => ['city'],
        'location_street'       => ['street address', 'street'],
        'location_maps'         => ['paste from google maps (koordinat/link)', 'paste from google maps', 'google maps', 'coordinates'],
        'location_latitude'     => ['latitude'],
        'location_longitude'    => ['longitude'],
    ];

    public const REQUIRED = [
        'customer'            => 'Customer/Client',
        'project_owner'       => 'Project Owner',
        'project_type'        => 'Project Type',
        'contract_start_date' => 'Contract Start Date',
        'contract_end_date'   => 'Contract End Date',
        'io_number'           => 'IO/Number Order',
        'name'                => 'Project Name',
        'description'         => 'Description',
    ];

    public const RISKS          = ['Low', 'Moderate', 'High'];
    public const AE_TYPES       = ['Internal', 'External'];
    public const METHODS        = ['Onsite', 'Hybrid', 'WFH'];
    public const LOCATION_TYPES = ['Head Office', 'Plant'];

    // ── Lookup master (dimuat sekali per import) ────────────────────────────
    private array $customersByCode = [];
    private array $customersByName = [];
    private array $employeesByName = [];
    private array $projectTypes    = [];

    /**
     * @return array{imported:int, duplicates:int, errors:int, rows:array<int, array{row:int, status:string, message:string, project_id?:int}>}
     */
    public function import(string $path, string $extension): array
    {
        $rows = self::readRows($path, $extension);
        if ($rows === null) {
            throw new \InvalidArgumentException('The file cannot be read. Use the .xlsx template or a .csv file.');
        }

        [$headerIndex, $colIdx] = self::locateHeader($rows);
        if ($headerIndex === null) {
            throw new \InvalidArgumentException('Header row not found. The file must contain the template headers, at least "Project Name" and "Customer/Client".');
        }

        $missing = array_values(array_diff_key(self::REQUIRED, $colIdx));
        if ($missing) {
            throw new \InvalidArgumentException('Required column(s) not found: ' . implode(', ', $missing) . '.');
        }

        $dataRows = array_slice($rows, $headerIndex + 1, null, true);
        $dataRows = array_filter($dataRows, fn ($r) => !self::isBlankRow($r, $colIdx));
        if (count($dataRows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('Too many rows: at most ' . self::MAX_ROWS . ' projects per import.');
        }

        $this->loadLookups();

        $result     = ['imported' => 0, 'duplicates' => 0, 'errors' => 0, 'rows' => []];
        $seenRows   = []; // sidik jari baris => nomor baris pertama
        $seenKeys   = []; // customer|io|name => nomor baris yang diimpor

        foreach ($dataRows as $index => $raw) {
            $rowNum = $index + 1; // nomor baris seperti di Excel
            $values = self::extract($raw, $colIdx);

            // 1. Sama persis dengan baris lain di file.
            $fingerprint = self::fingerprint($values);
            if (isset($seenRows[$fingerprint])) {
                $result['duplicates']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'duplicate',
                    'message' => "Exactly the same as row {$seenRows[$fingerprint]} in this file - skipped."];
                continue;
            }
            $seenRows[$fingerprint] = $rowNum;

            // 2. Validasi & normalisasi.
            [$data, $errors] = $this->validateRow($values);
            if ($errors) {
                $result['errors']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'error', 'message' => implode(' ', $errors) . ' Skipped.'];
                continue;
            }

            // 3. Sudah ada (di sistem atau diimpor dari baris sebelumnya).
            $key = $data['client_id'] . '|' . mb_strtolower($data['io_number']) . '|' . mb_strtolower($data['name']);
            if (isset($seenKeys[$key])) {
                $result['duplicates']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'duplicate',
                    'message' => "Same Customer, IO Number and Project Name as row {$seenKeys[$key]} in this file - skipped."];
                continue;
            }
            if ($existing = $this->findExisting($data)) {
                $result['duplicates']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'duplicate', 'project_id' => $existing,
                    'message' => "Project \"{$data['name']}\" with IO Number {$data['io_number']} already exists for this customer - skipped."];
                continue;
            }

            // 4. IO Number hanya boleh dipakai ulang dalam company yang sama.
            if ($this->ioUsedByOtherCompany($data['io_number'], $data['client_id'])) {
                $result['errors']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'error',
                    'message' => "IO Number {$data['io_number']} is already used by another company. An IO Number may only be reused within the same company. Skipped."];
                continue;
            }

            try {
                $project = DB::transaction(fn () => DeliveryProject::create($data + [
                    'category'      => 'Open',
                    'phase'         => null,
                    // Belum ada baseline planning → SPI belum terdefinisi → On Track.
                    'status'        => 'On Track',
                    'created_by_id' => null,
                ]));
                $seenKeys[$key] = $rowNum;
                $result['imported']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'imported', 'project_id' => $project->id,
                    'message' => "Imported \"{$project->name}\"."];
            } catch (\Throwable $e) {
                Log::error('DeliveryProjectImporter: row failed', ['row' => $rowNum, 'error' => $e->getMessage()]);
                $result['errors']++;
                $result['rows'][] = ['row' => $rowNum, 'status' => 'error', 'message' => 'Server error while saving this row. Skipped.'];
            }
        }

        return $result;
    }

    // ── Baca file ───────────────────────────────────────────────────────────

    /**
     * Semua baris sebagai array nilai MENTAH (bukan teks terformat), supaya
     * angka besar seperti nomor telepon tidak berubah jadi "6.28212E+12" dan
     * tanggal tetap berupa nomor seri Excel yang bisa dikonversi.
     */
    public static function readRows(string $path, string $extension): ?array
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['xlsx', 'xls', 'ods'], true)) {
            try {
                $reader = IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $sheet = $reader->load($path)->getSheet(0);
                return $sheet->toArray(null, true, false, false);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $handle = @fopen($path, 'r');
        if (!$handle) {
            return null;
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $first = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }
        $delimiter = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows ?: null;
    }

    public static function normalizeHeader($value): string
    {
        $h = mb_strtolower(trim((string) $value));
        $h = str_replace('*', '', $h);
        return trim(preg_replace('/\s+/', ' ', $h));
    }

    /**
     * Baris header = baris pertama (dari 10 teratas) yang memuat "Project Name"
     * dan "Customer/Client". Mengembalikan [index baris, field => index kolom].
     */
    public static function locateHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 10, true) as $i => $row) {
            $map = [];
            foreach ($row as $col => $cell) {
                $map[self::normalizeHeader($cell)] ??= $col;
            }

            $colIdx = [];
            foreach (self::COLUMNS as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if (isset($map[$alias])) {
                        $colIdx[$field] = $map[$alias];
                        break;
                    }
                }
            }

            if (isset($colIdx['name'], $colIdx['customer'])) {
                return [$i, $colIdx];
            }
        }

        return [null, []];
    }

    private static function isBlankRow(array $row, array $colIdx): bool
    {
        foreach ($colIdx as $col) {
            if (trim(self::cellText($row[$col] ?? null)) !== '') {
                return false;
            }
        }
        return true;
    }

    /** field => nilai mentah (string terpangkas, atau angka apa adanya). */
    public static function extract(array $row, array $colIdx): array
    {
        $values = [];
        foreach (array_keys(self::COLUMNS) as $field) {
            $raw = isset($colIdx[$field]) ? ($row[$colIdx[$field]] ?? null) : null;
            if (is_string($raw)) {
                $raw = trim($raw);
                if (!mb_check_encoding($raw, 'UTF-8')) {
                    $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
                }
                // Galat rumus Excel (#N/A, #REF!, ...) dianggap kosong.
                if ($raw === '' || preg_match('/^#[A-Z\/0]+[!?]?$/', $raw)) {
                    $raw = null;
                }
            }
            $values[$field] = $raw;
        }
        return $values;
    }

    /** Sidik jari baris untuk cek "sama persis" (huruf besar/kecil & spasi diabaikan). */
    public static function fingerprint(array $values): string
    {
        return sha1(json_encode(array_map(
            fn ($v) => mb_strtolower(preg_replace('/\s+/', ' ', trim(self::cellText($v)))),
            $values
        )));
    }

    private static function cellText($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return sprintf('%.0f', $value);
        }
        return (string) $value;
    }

    // ── Konversi nilai ──────────────────────────────────────────────────────

    /** Tanggal dari nomor seri Excel atau teks (Y-m-d, d/m/Y, d-M-y, ...). */
    public static function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $n = (float) $value;
            // Nomor seri Excel masuk akal: 1 Jan 1950 .. 31 Des 2199.
            if ($n > 18264 && $n < 109574) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($n))->toDateString();
            }
            return null;
        }

        $text    = trim((string) $value);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j-M-y', 'j-M-Y', 'd-M-y', 'd-M-Y', 'j M Y', 'd M Y', 'j F Y', 'd F Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $text);
                if ($date && $date->format($format) === $text) {
                    return $date->toDateString();
                }
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }
        // Format lain yang masih jelas (mis. "1-Jan-2026" dengan bulan panjang).
        foreach (['j-M-y', 'j-M-Y', 'j F Y', 'j M Y'] as $format) {
            try {
                return Carbon::createFromFormat('!' . $format, $text)->toDateString();
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    /** Angka dari sel numerik atau teks ("1,067,000,000", "Rp 1.067.000.000", "12,5"). */
    public static function parseNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = preg_replace('/[^\d.,\-]/', '', (string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }
        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $text)) {
            $text = str_replace(',', '', $text);           // 1,067,000,000.50
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $text)) {
            $text = str_replace(['.', ','], ['', '.'], $text); // 1.067.000.000,50
        } else {
            $text = str_replace(',', '.', $text);           // 12,5
        }
        return is_numeric($text) ? (float) $text : null;
    }

    /** Nomor telepon: sel angka besar dikembalikan utuh, bukan notasi ilmiah. */
    public static function parsePhone($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return sprintf('%.0f', $value);
        }
        return trim((string) $value);
    }

    /**
     * Koordinat dari kolom "Paste from Google Maps": "-6.30,106.84", link
     * ".../@-6.30,106.84,17z" atau "?q=-6.30,106.84".
     *
     * @return array{0: float, 1: float}|null
     */
    public static function parseCoordinates($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (preg_match('/(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)/', (string) $value, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return [$lat, $lng];
            }
        }
        return null;
    }

    /** Pilihan tetap, cocok tanpa peduli huruf besar/kecil. */
    private static function pick($value, array $options, array $aliases = []): ?string
    {
        $text = mb_strtolower(trim(self::cellText($value)));
        if (isset($aliases[$text])) {
            return $aliases[$text];
        }
        foreach ($options as $option) {
            if (mb_strtolower($option) === $text) {
                return $option;
            }
        }
        return null;
    }

    // ── Validasi baris ──────────────────────────────────────────────────────

    private function loadLookups(): void
    {
        foreach (Customer::with('basicData')->customers()->get() as $c) {
            if ($c->customer_code) {
                $this->customersByCode[mb_strtolower(trim($c->customer_code))] = $c->customer_id;
            }
            $name = self::key($c->basicData->name_1 ?? '');
            if ($name !== '') {
                $this->customersByName[$name][] = [
                    'id'     => $c->customer_id,
                    'code'   => $c->customer_code,
                    'parent' => $c->parent_customer_id,
                ];
            }
        }

        foreach (EmployeeBasicData::all() as $b) {
            $name = self::key($b->full_name);
            if ($name !== '') {
                $this->employeesByName[$name] ??= $b->full_name;
            }
        }

        $this->projectTypes = DeliveryProjectType::active()->orderBy('order_seq')->orderBy('name')->pluck('name')->all();
    }

    /** "Nama (Induk)" → "Nama". */
    public static function stripParentMarker(string $name): string
    {
        return trim(preg_replace('/\s*\(\s*induk\s*\)\s*$/i', '', $name));
    }

    /**
     * Dari beberapa customer bernama sama: id customer induk bila tepat satu
     * yang tidak punya induk DAN semua sisanya adalah cabang langsungnya.
     * Selain itu null (tetap ambigu, harus pakai kode customer).
     *
     * @param array<int, array{id:int, code:?string, parent:?int}> $matches
     */
    public static function parentOf(array $matches): ?int
    {
        $roots = array_values(array_filter($matches, fn ($m) => empty($m['parent'])));
        if (count($roots) !== 1) {
            return null;
        }
        $rootId = $roots[0]['id'];
        foreach ($matches as $m) {
            if ($m['id'] !== $rootId && (int) $m['parent'] !== (int) $rootId) {
                return null;
            }
        }
        return $rootId;
    }

    private static function key(?string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $text)));
    }

    /** @return array{0: array, 1: string[]} [data project, daftar error] */
    private function validateRow(array $v): array
    {
        $errors = [];
        $text   = fn ($x) => $x === null ? null : trim(self::cellText($x));

        foreach (self::REQUIRED as $field => $label) {
            if ($text($v[$field]) === null || $text($v[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }
        if ($errors) {
            return [[], $errors];
        }

        $data = [];

        // Customer: kode customer, atau nama customer. Nama yang dipakai induk
        // dan cabangnya sekaligus → induknya (tulisan "(Induk)" boleh ditambahkan).
        $customer = $text($v['customer']);
        $clientId = $this->customersByCode[mb_strtolower($customer)] ?? null;
        if (!$clientId) {
            $matches  = $this->customersByName[self::key(self::stripParentMarker($customer))] ?? [];
            $clientId = count($matches) === 1 ? $matches[0]['id'] : self::parentOf($matches);
            if (!$clientId && $matches) {
                $codes    = implode(', ', array_filter(array_column($matches, 'code')));
                $errors[] = "Customer \"{$customer}\" matches more than one Business Partner ({$codes}). Fill the customer code instead.";
            } elseif (!$clientId) {
                $errors[] = "Customer \"{$customer}\" was not found as a Customer in Business Partner.";
            }
        }
        $data['client_id'] = $clientId;

        $owner = $this->employeesByName[self::key($text($v['project_owner']))] ?? null;
        if (!$owner) {
            $errors[] = "Project Owner \"{$text($v['project_owner'])}\" was not found in Master Employee.";
        }
        $data['project_owner'] = $owner;

        $type = self::pick($v['project_type'], $this->projectTypes);
        if (!$type) {
            $errors[] = "Project Type \"{$text($v['project_type'])}\" is not an active Project Type (" . implode(', ', $this->projectTypes) . ').';
        }
        $data['project_type'] = $type;

        $data['high_level_risk'] = null;
        if ($text($v['high_level_risk'])) {
            $data['high_level_risk'] = self::pick($v['high_level_risk'], self::RISKS);
            if (!$data['high_level_risk']) {
                $errors[] = 'High Level Risk must be Low, Moderate or High.';
            }
        }

        $start = self::parseDate($v['contract_start_date']);
        $end   = self::parseDate($v['contract_end_date']);
        if (!$start) {
            $errors[] = "Contract Start Date \"{$text($v['contract_start_date'])}\" is not a valid date.";
        }
        if (!$end) {
            $errors[] = "Contract End Date \"{$text($v['contract_end_date'])}\" is not a valid date.";
        }
        if ($start && $end && $end < $start) {
            $errors[] = 'Contract End Date must be on or after Contract Start Date.';
        }
        $data['contract_start_date'] = $start;
        $data['contract_end_date']   = $end;

        $data['io_number'] = $text($v['io_number']);
        $data['name']      = $text($v['name']);
        if (mb_strlen($data['io_number']) > 255) {
            $errors[] = 'IO/Number Order may not be longer than 255 characters.';
        }
        if (mb_strlen($data['name']) > 255) {
            $errors[] = 'Project Name may not be longer than 255 characters.';
        }
        $data['description'] = $text($v['description']);

        // Account Executive (opsional). Internal = karyawan; External = teks bebas.
        $aeType = $text($v['ae_type']);
        $aeName = $text($v['ae_name']);
        $data['ae_type'] = null;
        $data['ae_name'] = null;
        if ($aeType) {
            $data['ae_type'] = self::pick($aeType, self::AE_TYPES);
            if (!$data['ae_type']) {
                $errors[] = 'Account Executive Type must be Internal or External.';
            }
        } elseif ($aeName) {
            $errors[] = 'Account Executive Type is required when Account Executive Name is filled.';
        }
        if ($aeName && $data['ae_type'] === 'Internal') {
            $data['ae_name'] = $this->employeesByName[self::key($aeName)] ?? null;
            if (!$data['ae_name']) {
                $errors[] = "Account Executive \"{$aeName}\" was not found in Master Employee (required for type Internal).";
            }
        } elseif ($aeName) {
            $data['ae_name'] = $aeName;
        }
        $data['ae_phone'] = self::parsePhone($v['ae_phone']);
        $data['ae_email'] = $text($v['ae_email']);
        if ($data['ae_email'] && !filter_var($data['ae_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email \"{$data['ae_email']}\" is not a valid email address.";
        }

        // Delivery Information: Gross Profit dihitung ulang seperti di form.
        foreach (['revenue' => 'Revenue', 'plan_cost' => 'Plan Cost'] as $field => $label) {
            $data[$field] = null;
            if ($text($v[$field]) !== null) {
                $data[$field] = self::parseNumber($v[$field]);
                if ($data[$field] === null || $data[$field] < 0) {
                    $errors[] = "{$label} must be a number of 0 or more.";
                }
            }
        }
        $data['gross_profit']            = null;
        $data['gross_profit_percentage'] = null;
        if ($data['revenue'] !== null) {
            $gp = $data['revenue'] - (float) ($data['plan_cost'] ?? 0);
            $data['gross_profit']            = $gp;
            $data['gross_profit_percentage'] = $data['revenue'] > 0 ? round($gp / $data['revenue'] * 100, 2) : 0;
        }

        $data['delivery_method'] = null;
        if ($text($v['delivery_method'])) {
            $data['delivery_method'] = self::pick($v['delivery_method'], self::METHODS, ['on-site' => 'Onsite', 'on site' => 'Onsite', 'remote' => 'WFH']);
            if (!$data['delivery_method']) {
                $errors[] = 'Delivery Method must be Onsite, Hybrid or WFH.';
            }
        }
        foreach (['warranty_period' => 'Warranty Period', 'total_mandays' => 'Total Mandays'] as $field => $label) {
            $data[$field] = null;
            if ($text($v[$field]) !== null) {
                $n = self::parseNumber($v[$field]);
                if ($n === null || $n < 0 || floor($n) != $n) {
                    $errors[] = "{$label} must be a whole number of 0 or more.";
                } else {
                    $data[$field] = (int) $n;
                }
            }
        }

        // Location Information.
        foreach (['location_name', 'location_country', 'location_geographical', 'location_region', 'location_city', 'location_street'] as $field) {
            $data[$field] = $text($v[$field]);
        }
        if ($data['location_name'] && mb_strlen($data['location_name']) > 255) {
            $errors[] = 'Location Name may not be longer than 255 characters.';
        }
        $data['location_type'] = null;
        if ($text($v['location_type'])) {
            $data['location_type'] = self::pick($v['location_type'], self::LOCATION_TYPES);
            if (!$data['location_type']) {
                $errors[] = 'Type of Address must be Head Office or Plant.';
            }
        }

        // Latitude/Longitude diutamakan; bila kosong diambil dari kolom Google Maps.
        $lat = $text($v['location_latitude']) !== null ? self::parseNumber($v['location_latitude']) : null;
        $lng = $text($v['location_longitude']) !== null ? self::parseNumber($v['location_longitude']) : null;
        if ($lat === null && $lng === null && $text($v['location_maps'])) {
            $coords = self::parseCoordinates($v['location_maps']);
            if ($coords) {
                [$lat, $lng] = $coords;
            } else {
                $errors[] = 'No coordinates could be read from the Google Maps column.';
            }
        }
        if (($lat === null) !== ($lng === null)) {
            $errors[] = 'Latitude and Longitude must be filled together.';
        } elseif ($lat !== null && ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)) {
            $errors[] = 'Latitude must be between -90 and 90 and Longitude between -180 and 180.';
        }
        $data['location_latitude']  = $lat;
        $data['location_longitude'] = $lng;

        return [$data, $errors];
    }

    private function findExisting(array $data): ?int
    {
        return DeliveryProject::where('client_id', $data['client_id'])
            ->whereRaw('LOWER(TRIM(io_number)) = ?', [mb_strtolower($data['io_number'])])
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($data['name'])])
            ->value('id');
    }

    /** Sama dengan DeliveryProjectController::sameCompanyIoRule. */
    private function ioUsedByOtherCompany(string $io, $clientId): bool
    {
        return DeliveryProject::where('io_number', $io)
            ->where(fn ($q) => $q->where('client_id', '!=', $clientId)->orWhereNull('client_id'))
            ->exists();
    }
}
