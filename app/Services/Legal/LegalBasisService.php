<?php

namespace App\Services\Legal;

use Illuminate\Support\Facades\DB;

/**
 * Register Dasar Hukum (`legal_basis_register`): membaca versi berlaku sebuah aturan, memeriksa status ulasan, dan menulis versi/ulasan.
 *
 * Aturan = `rule_key`; tiap versi punya `effective_date`. Versi berlaku = yang `effective_date`-nya terbaru ≤ tanggal acuan dan tidak
 * `superseded`. Status `active` = sudah diulas manusia; `needs_review` = belum (kalkulator yang bergantung padanya tidak boleh dinyalakan).
 */
class LegalBasisService
{
    public const MODULES = ['pph21' => 'PPh 21', 'bpjs' => 'BPJS', 'overtime' => 'Overtime', 'contract' => 'Contract (PKWT)', 'offboarding' => 'Offboarding'];
    public const TYPES = ['UU' => 'Law (UU)', 'PP' => 'Government Regulation (PP)', 'Perpres' => 'Presidential Regulation (Perpres)', 'PMK' => 'Finance Minister Regulation (PMK)', 'Kepmenaker' => 'Ministerial Decree (Kepmenaker)', 'Putusan' => 'Court decision (Putusan MK)', 'Surat' => 'Circular / letter'];
    public const STATUSES = ['active' => 'Reviewed', 'needs_review' => 'Needs review', 'superseded' => 'Superseded'];
    public const BADGES = ['active' => 'bg-green-100 text-green-700', 'needs_review' => 'bg-amber-100 text-amber-700', 'superseded' => 'bg-gray-200 text-gray-500'];
    public const CONFIDENCE = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    /** Ulasan berlaku 12 bulan; peringatan dimulai 30 hari sebelum jatuh tempo. */
    public const REVIEW_MONTHS = 12;
    public const REVIEW_WARN_DAYS = 30;

    /** Apakah kolom pemilik sudah ada di database ini (SQL/migrasi 6 Nov 2026); tanpa itu fitur pemilik diam, yang lain tetap jalan. */
    public function hasOwnerColumn(): bool
    {
        static $has = null;
        try {
            return $has ??= \Illuminate\Support\Facades\Schema::hasColumn('legal_basis_register', 'owner_employee_id');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Karyawan aktif untuk pilihan pemilik. @return array<int,string> employee_id → nama */
    public function ownerOptions(): array
    {
        return DB::table('employee as e')->join('employee_basic_data as b', 'b.employee_id', '=', 'e.employee_id')
            ->where('e.is_active', 1)->orderBy('b.first_name')->orderBy('b.last_name')
            ->get(['e.employee_id', 'b.first_name', 'b.last_name', 'e.eci'])
            ->mapWithKeys(fn ($r) => [(int) $r->employee_id => trim($r->first_name . ' ' . $r->last_name) . ' (' . $r->eci . ')'])->all();
    }

    /** Dua payload dianggap sama bila isinya sama (urutan kunci dan spasi tidak penting). */
    private function samePayload(?string $a, ?string $b): bool
    {
        $da = ($a === null || trim($a) === '') ? null : json_decode($a, true);
        $db = ($b === null || trim($b) === '') ? null : json_decode($b, true);
        $canon = function ($v) use (&$canon) {
            if (is_array($v)) {
                $isList = array_is_list($v);
                $v = array_map($canon, $v);
                if (!$isList) {
                    ksort($v);
                }
            }

            return $v;
        };

        return json_encode($canon($da)) === json_encode($canon($db));
    }

    /** Aturan yang wajib sudah diulas sebelum kalkulator Offboarding boleh dinyalakan. */
    public const OFFBOARDING_REQUIRED = [
        'offboarding.pkwt_compensation', 'offboarding.severance_table', 'offboarding.reason_multipliers', 'offboarding.severance_tax', 'offboarding.leave_divisor',
    ];

    /** Aturan yang wajib sudah diulas per modul bila saklar "Enforce" modul itu menyala. */
    public const REQUIRED_BY_MODULE = [
        'pph21' => ['pph21.ter', 'pph21.progressive', 'pph21.ptkp', 'pph21.occupational_cost', 'pph21.non_npwp'],
        'bpjs' => ['bpjs.kesehatan', 'bpjs.jht', 'bpjs.jp', 'bpjs.jkk_jkm', 'bpjs.jkp'],
        'overtime' => ['overtime.multipliers'],
        'contract' => ['pkwt.max_term'],
        'offboarding' => self::OFFBOARDING_REQUIRED,
    ];

    /** Penjelasan saklar untuk halaman Legal Basis. */
    public const SWITCH_HELP = [
        'show_panel' => "Show the \"Legal basis\" panel on the module's Settings page",
        'warn_banner' => "Show a warning on the module's working pages while a rule is not reviewed",
        'enforce' => 'Enforce: the module refuses to run until its rules are marked Reviewed',
    ];

    /** Saklar global "tampilkan informasi Legal Basis" (panel, peringatan, label DRAFT di seluruh aplikasi). Bawaan: tampil. */
    public function displayEnabled(): bool
    {
        try {
            return \App\Models\AppConfig::get('legal_basis_display', '1') !== '0';
        } catch (\Throwable) {
            return true;
        }
    }

    public function setDisplay(bool $on): void
    {
        \App\Models\AppConfig::set('legal_basis_display', $on ? '1' : '0', 'Show Legal Basis panels, warnings and DRAFT labels across the app');
    }

    /** Saklar penerapan satu modul (bawaan bila baris/tabel belum ada: panel & peringatan menyala, enforce mati). */
    public function switches(string $module): object
    {
        $sw = $this->rawSwitches($module);
        if (!$this->displayEnabled()) {
            // Tampilan dimatikan global: panel & peringatan disembunyikan. "Enforce" tetap berlaku sebagaimana diset (itu penjaga, bukan tampilan).
            $sw->show_panel = 0;
            $sw->warn_banner = 0;
        }

        return $sw;
    }

    /** Saklar apa adanya dari database (dipakai halaman pengaturan). */
    public function rawSwitches(string $module): object
    {
        try {
            $row = DB::table('legal_basis_settings')->where('module', $module)->first();
        } catch (\Throwable) {
            $row = null;
        }

        return $row ?: (object) ['module' => $module, 'show_panel' => 1, 'warn_banner' => 1, 'enforce' => 0];
    }

    /** @return \Illuminate\Support\Collection<string,object> */
    public function allSwitches()
    {
        return collect(array_keys(self::MODULES))->mapWithKeys(fn ($m) => [$m => $this->rawSwitches($m)]);
    }

    /**
     * Aturan wajib yang MENAHAN fitur modul: kosong bila "Enforce" mati, selain itu daftar rule_key yang belum Reviewed.
     * @return string[]
     */
    public function blockers(string $module): array
    {
        if (!(bool) $this->switches($module)->enforce) {
            return [];
        }

        return $this->notReviewed(self::REQUIRED_BY_MODULE[$module] ?? []);
    }

    /** Penahan fitur Payroll: gabungan blockers pph21 + bpjs + overtime (kosong selama saklar Enforce ketiganya mati). @return string[] */
    public function payrollBlockers(): array
    {
        $blockers = array_merge($this->blockers('pph21'), $this->blockers('bpjs'), $this->blockers('overtime'));
        // Sumber tarif = Legal Basis: aturan yang belum punya nilai yang bisa dibaca Payroll ikut menahan (kalau tidak, hasil hitung bisa nol).
        if (PayrollRatesFromLegal::useLegal()) {
            foreach ((new PayrollRatesFromLegal($this))->missing() as $key => $why) {
                $blockers[] = "{$key} ({$why})";
            }
        }

        // Saklar lembur sendiri: bila lembur dibaca dari Legal Basis, aturan pengalinya harus lengkap.
        if (PayrollRatesFromLegal::useLegal(PayrollRatesFromLegal::AREA_OVERTIME)) {
            foreach ((new PayrollRatesFromLegal($this))->missingOvertime() as $key => $why) {
                $blockers[] = "{$key} ({$why})";
            }
        }

        return array_values(array_unique($blockers));
    }

    /** Aturan wajib yang belum Reviewed (untuk spanduk peringatan), terlepas dari saklar enforce. @return string[] */
    public function pending(string $module): array
    {
        return $this->notReviewed(self::REQUIRED_BY_MODULE[$module] ?? []);
    }

    /** Simpan saklar per modul. @param array<string,array{show_panel?:mixed,warn_banner?:mixed,enforce?:mixed}> $rows */
    public function saveSwitches(array $rows, int $actorId): void
    {
        foreach (array_keys(self::MODULES) as $module) {
            $in = $rows[$module] ?? [];
            DB::table('legal_basis_settings')->updateOrInsert(['module' => $module], [
                'show_panel' => !empty($in['show_panel']) ? 1 : 0, 'warn_banner' => !empty($in['warn_banner']) ? 1 : 0,
                'enforce' => !empty($in['enforce']) ? 1 : 0, 'updated_by' => $actorId, 'updated_at' => now(),
            ]);
        }
    }

    /** Versi berlaku suatu aturan pada tanggal acuan (bawaan hari ini). */
    public function current(string $key, ?string $date = null): ?object
    {
        $row = DB::table('legal_basis_register')->where('rule_key', $key)->where('status', '!=', 'superseded')
            ->where('effective_date', '<=', $date ?: now()->toDateString())->orderByDesc('effective_date')->first();

        return $row ? $this->decode($row) : null;
    }

    /** @return array<string,mixed>|null isi payload versi berlaku */
    public function payload(string $key, ?string $date = null): ?array
    {
        return $this->current($key, $date)?->payload_data;
    }

    /** Kunci dari $keys yang BELUM berstatus Reviewed (atau tak ada). @param  string[]  $keys  @return string[] */
    public function notReviewed(array $keys): array
    {
        return array_values(array_filter($keys, fn ($k) => ($this->current($k)?->status) !== 'active'));
    }

    /** @return \Illuminate\Support\Collection<int,object> semua versi (terbaru dulu), dengan nama pengulas */
    public function all(?string $module = null)
    {
        $owners = $this->hasOwnerColumn();
        $rows = DB::table('legal_basis_register as l')->leftJoin('employee_basic_data as b', 'b.employee_id', '=', 'l.reviewed_by')
            ->when($owners, fn ($q) => $q->leftJoin('employee_basic_data as o', 'o.employee_id', '=', 'l.owner_employee_id'))
            ->when($module, fn ($q) => $q->where('l.module', $module))
            ->orderBy('l.module')->orderBy('l.rule_key')->orderByDesc('l.effective_date')
            ->get(array_merge(['l.*', DB::raw("CONCAT_WS(' ', b.first_name, b.last_name) as reviewer")], $owners ? [DB::raw("CONCAT_WS(' ', o.first_name, o.last_name) as owner_name")] : []));

        return $rows->map(fn ($r) => $this->decode($r));
    }

    /** Everything the Legal page needs to show the register: rows per module, counters, switches and the not-reviewed rules. */
    public function registerPage(?string $module = ''): array
    {
        $module = (string) $module;
        $rows = $this->all(isset(self::MODULES[$module]) ? $module : null);
        $stale = $rows->filter(fn ($r) => $r->status === 'active' && $r->reviewed_at && strtotime($r->reviewed_at) < strtotime('-12 months'))->count();

        return [
            'rows' => $rows->groupBy('module'), 'module' => isset(self::MODULES[$module]) ? $module : '', 'modules' => self::MODULES, 'types' => self::TYPES,
            'statuses' => self::STATUSES, 'badges' => self::BADGES, 'confidence' => self::CONFIDENCE,
            'switches' => $this->allSwitches(), 'pending' => collect(array_keys(self::MODULES))->mapWithKeys(fn ($m) => [$m => $this->pending($m)]),
            'counts' => ['total' => $rows->count(), 'needs_review' => $rows->where('status', 'needs_review')->count(), 'stale' => $stale, 'reviewed' => $rows->where('status', 'active')->count(),
                'due_soon' => $rows->filter(fn ($r) => $r->status === 'active' && $r->review_days_left !== null && $r->review_days_left >= 0 && $r->review_days_left <= self::REVIEW_WARN_DAYS)->count()],
            'hasOwners' => $this->hasOwnerColumn(), 'ownerOptions' => $this->hasOwnerColumn() ? $this->ownerOptions() : [],
        ];
    }

    /** Ringkasan untuk panel di halaman Settings: jumlah belum diulas, tertua ditinjau. @return array{total:int,needs_review:int,stale:int,rows:\Illuminate\Support\Collection} */
    public function summary(string $module)
    {
        $rows = $this->all($module)->filter(fn ($r) => $r->status !== 'superseded')->groupBy('rule_key')->map->first()->values();
        $stale = $rows->filter(fn ($r) => $r->status === 'active' && $r->reviewed_at && strtotime($r->reviewed_at) < strtotime('-12 months'))->count();

        return ['total' => $rows->count(), 'needs_review' => $rows->where('status', 'needs_review')->count(), 'stale' => $stale, 'rows' => $rows];
    }

    // ── Tulis ─────────────────────────────────────────────────────────────

    /**
     * Versi baru (atau koreksi versi yang sama tanggal efektifnya). Perubahan isi/ketentuan SELALU menurunkan status ke `needs_review`:
     * ulasan berlaku untuk isi yang diulas, bukan untuk isi yang sudah diubah.
     *
     * @param  array<string,mixed>  $d
     * @throws \DomainException
     */
    public function save(?int $id, array $d, int $actorId): int
    {
        $payload = trim((string) ($d['payload'] ?? ''));
        if ($payload !== '') {
            json_decode($payload);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \DomainException('The calculation values could not be read. Reload the page and try again.');
            }
        }
        $row = [
            'rule_key' => trim((string) $d['rule_key']), 'module' => $d['module'], 'regulation_type' => $d['regulation_type'],
            'number' => mb_substr(trim((string) $d['number']), 0, 40), 'year' => (int) $d['year'], 'title' => mb_substr(trim((string) $d['title']), 0, 400),
            'article' => ($d['article'] ?? '') !== '' ? mb_substr((string) $d['article'], 0, 400) : null,
            'effective_date' => $d['effective_date'], 'last_amended_date' => ($d['last_amended_date'] ?? '') !== '' ? $d['last_amended_date'] : null,
            'amended_by' => ($d['amended_by'] ?? '') !== '' ? mb_substr((string) $d['amended_by'], 0, 400) : null,
            'source_url' => ($d['source_url'] ?? '') !== '' ? mb_substr((string) $d['source_url'], 0, 500) : null,
            'confidence' => $d['confidence'] ?? 'medium', 'payload' => $payload === '' ? null : $payload,
            'notes' => ($d['notes'] ?? '') !== '' ? (string) $d['notes'] : null,
            'status' => 'needs_review', 'reviewed_by' => null, 'reviewed_at' => null, 'updated_by' => $actorId, 'updated_at' => now(),
        ];
        if ($row['last_amended_date'] && $row['last_amended_date'] < '1900-01-01') {
            throw new \DomainException('Enter a valid last-amended date.');
        }

        // Pemilik (penanggung jawab). Hanya bila kolomnya sudah ada di database ini.
        $ownerGiven = array_key_exists('owner_employee_id', $d) && $this->hasOwnerColumn();
        $owner = $ownerGiven && ($d['owner_employee_id'] ?? '') !== '' ? (int) $d['owner_employee_id'] : null;
        if ($ownerGiven) {
            if ($owner !== null && !DB::table('employee')->where('employee_id', $owner)->exists()) {
                throw new \DomainException('The chosen owner was not found.');
            }
            $row['owner_employee_id'] = $owner;
        }

        if ($id) {
            $old = DB::table('legal_basis_register')->where('id', $id)->first();
            if (!$old) {
                throw new \DomainException('Entry not found.');
            }
            // Mengubah HANYA pemilik bukan perubahan isi: status Reviewed dan pengulasnya dipertahankan.
            $content = array_diff_key($row, array_flip(['status', 'reviewed_by', 'reviewed_at', 'updated_by', 'updated_at', 'owner_employee_id', 'payload']));
            $same = $this->samePayload($old->payload, $row['payload']);
            foreach ($content as $k => $v) {
                if ((string) ($old->$k ?? '') !== (string) ($v ?? '')) {
                    $same = false;
                    break;
                }
            }
            if ($same) {
                unset($row['status'], $row['reviewed_by'], $row['reviewed_at']);
            }
            $clash = DB::table('legal_basis_register')->where('rule_key', $row['rule_key'])->where('effective_date', $row['effective_date'])->where('id', '!=', $id)->exists();
            if ($clash) {
                throw new \DomainException('A version of this rule with that effective date already exists.');
            }
            DB::table('legal_basis_register')->where('id', $id)->update($row);

            return $id;
        }
        if (DB::table('legal_basis_register')->where('rule_key', $row['rule_key'])->where('effective_date', $row['effective_date'])->exists()) {
            throw new \DomainException('A version of this rule with that effective date already exists. Edit it, or use another effective date.');
        }

        return (int) DB::table('legal_basis_register')->insertGetId($row + ['created_at' => now()]);
    }

    /** Tandai Reviewed (status active), dicatat siapa dan kapan. @throws \DomainException */
    public function markReviewed(int $id, int $actorId): void
    {
        $row = DB::table('legal_basis_register')->where('id', $id)->first();
        if (!$row) {
            throw new \DomainException('Entry not found.');
        }
        if ($row->status === 'superseded') {
            throw new \DomainException('A superseded version cannot be reviewed.');
        }
        DB::table('legal_basis_register')->where('id', $id)->update(['status' => 'active', 'reviewed_by' => $actorId, 'reviewed_at' => now(), 'updated_at' => now()]);
    }

    /**
     * "Accept review" untuk SEMUA aturan modul yang masih Needs review (versi berlaku, bukan superseded). Tetap tindakan MANUSIA:
     * dipanggil dari tombol yang dikonfirmasi, mencatat pengulas dan waktu per baris.
     *
     * @return int jumlah aturan yang ditandai
     */
    public function markModuleReviewed(string $module, int $actorId): int
    {
        if (!isset(self::MODULES[$module])) {
            throw new \DomainException('Unknown module.');
        }

        return DB::table('legal_basis_register')->where('module', $module)->where('status', 'needs_review')
            ->update(['status' => 'active', 'reviewed_by' => $actorId, 'reviewed_at' => now(), 'updated_at' => now()]);
    }

    public function markNeedsReview(int $id): void
    {
        DB::table('legal_basis_register')->where('id', $id)->update(['status' => 'needs_review', 'updated_at' => now()]);
    }

    /** Kembalikan versi yang ditandai Superseded menjadi "Needs review" (dipakai lagi setelah diulas ulang). @throws \DomainException */
    public function restore(int $id): void
    {
        $row = DB::table('legal_basis_register')->where('id', $id)->first();
        if (!$row) {
            throw new \DomainException('Entry not found.');
        }
        if ($row->status !== 'superseded') {
            throw new \DomainException('Only a superseded version can be restored.');
        }
        DB::table('legal_basis_register')->where('id', $id)->update(['status' => 'needs_review', 'reviewed_by' => null, 'reviewed_at' => null, 'updated_at' => now()]);
    }

    public function supersede(int $id): void
    {
        DB::table('legal_basis_register')->where('id', $id)->update(['status' => 'superseded', 'updated_at' => now()]);
    }

    /** Menambah `payload_data` (larik) dan `label` (mis. "PP 35/2021") pada baris register. */
    public function decorate(object $row): object
    {
        return $this->decode($row);
    }

    private function decode(object $row): object
    {
        $row->payload_data = $row->payload ? json_decode($row->payload, true) : null;
        $row->label = trim($row->regulation_type . ' ' . $row->number . '/' . $row->year);
        // Ulasan berlaku REVIEW_MONTHS bulan: sisa hari sampai jatuh tempo (negatif = terlambat); hanya untuk yang sudah Reviewed.
        $row->review_due_at = null;
        $row->review_days_left = null;
        if (($row->status ?? null) === 'active' && !empty($row->reviewed_at)) {
            $due = \Illuminate\Support\Carbon::parse($row->reviewed_at)->startOfDay()->addMonths(self::REVIEW_MONTHS);
            $row->review_due_at = $due->toDateString();
            $row->review_days_left = (int) now()->startOfDay()->diffInDays($due, false);
        }

        return $row;
    }
}
