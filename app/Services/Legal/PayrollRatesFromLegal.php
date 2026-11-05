<?php

namespace App\Services\Legal;

use App\Models\AppConfig;

/**
 * Tarif regulasi untuk Payroll, dibaca dari Legal Basis (satu sumber) dalam BENTUK yang sama dengan yang dibaca mesin Payroll dari
 * tabel Payroll Settings (persen sebagai 5.00, bukan 0.05), sehingga rumus hitung tidak berubah sama sekali.
 *
 * Saklar `payroll_rates_source` (app_configs): `settings` (bawaan: tarif dari Payroll Settings, perilaku lama) atau `legal`.
 * Hanya yang BERSIFAT REGULASI dibaca dari sini: tarif progresif, biaya jabatan, PTKP, TER, tarif dan batas upah BPJS, JKM.
 * Pilihan perusahaan tetap di Payroll Settings: tahun mana yang aktif, kelas JKK, saklar BPJS/PPh 21, jadwal kerja, pembulatan.
 *
 * Versi aturan dipilih menurut tanggal berlaku (LegalBasisService::current). PPh 21 per tahun pajak memakai versi yang berlaku pada 31 Desember
 * tahun itu (satu paket per tahun, seperti tabel Payroll Settings); BPJS memakai versi pada tanggal akhir periode gaji.
 */
final class PayrollRatesFromLegal
{
    public const CONFIG_KEY = 'payroll_rates_source';
    public const SOURCE_SETTINGS = 'settings';
    public const SOURCE_LEGAL = 'legal';

    /** Kunci aturan → kunci wajib di payload agar Payroll bisa membacanya. */
    public const REQUIRED = [
        'pph21.progressive'       => ['brackets'],
        'pph21.occupational_cost' => ['rate', 'max_monthly'],
        'pph21.ptkp'              => ['codes'],
        'pph21.ter'               => ['categories'],
        'bpjs.kesehatan'          => ['employer', 'employee', 'wage_cap'],
        'bpjs.jht'                => ['employer', 'employee'],
        'bpjs.jp'                 => ['employer', 'employee', 'wage_cap'],
        'bpjs.jkk_jkm'            => ['jkm'],
    ];

    public function __construct(private LegalBasisService $legal)
    {
    }

    /** Area yang punya saklar sendiri: tarif PPh 21 + BPJS, dan aturan lembur (perusahaan bisa menyalakan salah satunya saja). */
    public const AREA_RATES = 'rates';
    public const AREA_OVERTIME = 'overtime';
    public const OVERTIME_KEY = 'payroll_overtime_source';

    public static function configKey(string $area): string
    {
        return $area === self::AREA_OVERTIME ? self::OVERTIME_KEY : self::CONFIG_KEY;
    }

    /** Apakah $area membaca dari Legal Basis. Bawaan (belum diatur / tabel belum ada) = sumber lama. */
    public static function useLegal(string $area = self::AREA_RATES): bool
    {
        try {
            return AppConfig::get(self::configKey($area), self::SOURCE_SETTINGS) === self::SOURCE_LEGAL;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function setSource(string $source, string $area = self::AREA_RATES): void
    {
        AppConfig::set(self::configKey($area), $source === self::SOURCE_LEGAL ? self::SOURCE_LEGAL : self::SOURCE_SETTINGS,
            $area === self::AREA_OVERTIME
                ? 'Where Payroll reads overtime multipliers: settings (built-in standard) or legal (Legal Basis overtime.multipliers)'
                : 'Where Payroll reads PPh 21 / BPJS regulation rates: settings (Payroll Settings) or legal (Legal Basis)');
    }

    // ── Lembur (pengali per jam, pembagi upah sejam) ──────────────────────

    /** Aturan lembur baku PP 35/2021, sama dengan OvertimeRateService tanpa parameter: dipakai pembanding. */
    public const OVERTIME_STANDARD = [
        'divisor' => 173.0,
        'workday' => [['minutes' => 60, 'multiplier' => 1.5], ['minutes' => null, 'multiplier' => 2.0]],
        'rest5'   => [['minutes' => 480, 'multiplier' => 2.0], ['minutes' => 60, 'multiplier' => 3.0], ['minutes' => null, 'multiplier' => 4.0]],
        'rest6'   => [['minutes' => 420, 'multiplier' => 2.0], ['minutes' => 60, 'multiplier' => 3.0], ['minutes' => null, 'multiplier' => 4.0]],
    ];

    /** Kunci payload `overtime.multipliers` → nama daftar band di OvertimeRateService. */
    private const OVERTIME_BANDS = ['workday' => 'workday', 'rest_day_5' => 'rest5', 'rest_day_6' => 'rest6'];

    /**
     * Aturan lembur dari Legal Basis pada $date, dalam bentuk yang dibaca OvertimeRateService; NULL bila payload belum lengkap
     * (pemanggil harus menolak, lihat missingOvertime()). Band terakhir SELALU "semua jam tersisa", supaya tak ada menit lembur yang hilang.
     *
     * @return array{divisor:float,workday:array,rest5:array,rest6:array}|null
     */
    public function overtimeRules(string $date): ?array
    {
        if ($this->missingOvertime($date)) {
            return null;
        }
        $p = $this->legal->payload('overtime.multipliers', $date);
        $out = ['divisor' => (float) $p['monthly_divisor']];
        foreach (self::OVERTIME_BANDS as $key => $name) {
            $rows = array_values((array) $p[$key]);
            foreach ($rows as $i => $r) {
                $rows[$i] = ['label' => 'Band ' . ($i + 1), 'minutes' => ($r['hours'] ?? null) === null ? null : (int) round((float) $r['hours'] * 60), 'multiplier' => (float) $r['multiplier']];
            }
            $rows[count($rows) - 1]['minutes'] = null;
            $out[$name] = $rows;
        }

        return $out;
    }

    /**
     * Mengapa aturan lembur belum bisa dibaca Payroll (kosong = siap). Memeriksa pembagi, tiga daftar band (hari kerja, libur 5 hari,
     * libur 6 hari), pengali > 0, dan band terakhir "semua jam tersisa".
     *
     * @return array<string,string> rule_key → alasan
     */
    public function missingOvertime(?string $date = null): array
    {
        $key = 'overtime.multipliers';
        $p = $this->legal->payload($key, $date);
        if ($p === null) {
            return [$key => $this->legal->current($key, $date) ? 'has no calculation values' : 'has no version in force'];
        }
        if (!is_numeric($p['monthly_divisor'] ?? null) || (float) $p['monthly_divisor'] <= 0) {
            return [$key => 'is missing a valid “monthly_divisor”'];
        }
        foreach (array_keys(self::OVERTIME_BANDS) as $band) {
            $rows = $p[$band] ?? null;
            if (!is_array($rows) || $rows === []) {
                return [$key => "is missing “{$band}”"];
            }
            foreach (array_values($rows) as $i => $r) {
                if (!is_array($r) || !is_numeric($r['multiplier'] ?? null) || (float) $r['multiplier'] <= 0) {
                    return [$key => "“{$band}” row " . ($i + 1) . ' needs a pay multiplier above 0'];
                }
                if (($r['hours'] ?? null) !== null && (!is_numeric($r['hours']) || (float) $r['hours'] <= 0)) {
                    return [$key => "“{$band}” row " . ($i + 1) . ' needs hours above 0 (or empty for all remaining hours)'];
                }
            }
            if (($rows[array_key_last($rows)]['hours'] ?? null) !== null) {
                return [$key => "“{$band}”: the last row must be “all remaining hours” (leave its hours empty)"];
            }
        }

        return [];
    }

    /** Selisih aturan lembur Legal Basis terhadap aturan baku yang dipakai bila saklar lembur mati. @return string[] */
    public function differencesOvertime(): array
    {
        $r = $this->overtimeRules(now()->toDateString());
        if ($r === null) {
            return [];
        }
        $out = [];
        $fmt = fn (array $bands) => implode(', ', array_map(fn ($b) => ($b['minutes'] === null ? 'rest' : rtrim(rtrim(number_format($b['minutes'] / 60, 2, '.', ''), '0'), '.') . ' h') . ' × ' . rtrim(rtrim(number_format($b['multiplier'], 2, '.', ''), '0'), '.'), $bands));
        if (abs($r['divisor'] - self::OVERTIME_STANDARD['divisor']) > 1e-9) {
            $out[] = 'Overtime monthly divisor: built-in ' . self::OVERTIME_STANDARD['divisor'] . ', Legal Basis ' . $r['divisor'];
        }
        foreach (['workday' => 'Workday', 'rest5' => 'Rest day / holiday, 5-day week', 'rest6' => 'Rest day / holiday, 6-day week'] as $k => $label) {
            $norm = fn (array $bands) => array_map(fn ($b) => [$b['minutes'], (float) $b['multiplier']], $bands);
            if ($norm($r[$k]) != $norm(self::OVERTIME_STANDARD[$k])) {
                $out[] = "{$label}: built-in " . $fmt(self::OVERTIME_STANDARD[$k]) . ', Legal Basis ' . $fmt($r[$k]);
            }
        }

        return $out;
    }

    /**
     * Aturan yang belum bisa dibaca Payroll (tidak ada versi berlaku, atau payload kurang kunci wajib), dengan alasannya.
     *
     * @return array<string,string> rule_key → alasan
     */
    public function missing(?string $date = null): array
    {
        $out = [];
        foreach (self::REQUIRED as $key => $need) {
            $p = $this->legal->payload($key, $date);
            if ($p === null) {
                $out[$key] = $this->legal->current($key, $date) ? 'has no calculation values' : 'has no version in force';
                continue;
            }
            foreach ($need as $k) {
                if (!array_key_exists($k, $p) || $p[$k] === null || $p[$k] === [] || $p[$k] === '') {
                    $out[$key] = "is missing “{$k}”";
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Selisih antara angka di Payroll Settings dan di Legal Basis (tahun pajak terbaru; BPJS pada tanggal hari ini), dalam kalimat
     * yang bisa dibaca manusia. Kosong = kedua sumber sama, jadi beralih sumber tidak mengubah hasil hitung.
     *
     * @return string[]
     */
    public function differences(): array
    {
        $out = [];
        $repo = app(\App\Services\Payroll\Pph21RateRepository::class);
        $rp = fn (float $v) => 'Rp ' . number_format($v, 0, ',', '.');
        $pc = fn (float $v) => rtrim(rtrim(number_format($v, 4, ',', ''), '0'), ',') . '%';
        $cmp = function (string $label, $a, $b, callable $fmt) use (&$out) {
            if ($a === null || $b === null || abs((float) $a - (float) $b) > 1e-9) {
                $out[] = "{$label}: Payroll Settings " . ($a === null ? '—' : $fmt((float) $a)) . ", Legal Basis " . ($b === null ? '—' : $fmt((float) $b));
            }
        };

        $years = $repo->years();
        if ($years) {
            $y = $years[0];
            try {
                $s = $repo->bundle($y, false);
                $l = $repo->bundle($y, true);
            } catch (\Throwable) {
                $s = $l = null;
            }
            if ($s && $l) {
                foreach (array_unique(array_merge(array_keys($s['ptkp']), array_keys($l['ptkp']))) as $code) {
                    $cmp("PTKP {$code} ({$y})", $s['ptkp'][$code] ?? null, $l['ptkp'][$code] ?? null, $rp);
                }
                foreach (range(0, max(count($s['brackets']), count($l['brackets'])) - 1) as $i) {
                    $cmp('Progressive bracket ' . ($i + 1) . " rate ({$y})", $s['brackets'][$i]['rate'] ?? null, $l['brackets'][$i]['rate'] ?? null, $pc);
                    $cmp('Progressive bracket ' . ($i + 1) . " limit ({$y})", $s['brackets'][$i]['upper_limit'] ?? 0, $l['brackets'][$i]['upper_limit'] ?? 0, $rp);
                }
                $cmp("Occupational cost rate ({$y})", $s['settings']['occupational_cost_rate'], $l['settings']['occupational_cost_rate'], $pc);
                $cmp("Occupational cost monthly maximum ({$y})", $s['settings']['occupational_cost_monthly_max'], $l['settings']['occupational_cost_monthly_max'], $rp);
                $n = 0;
                foreach (array_unique(array_merge(array_keys($s['ter']), array_keys($l['ter']))) as $cat) {
                    $a = $s['ter'][$cat] ?? []; $b = $l['ter'][$cat] ?? [];
                    if (count($a) !== count($b)) {
                        $out[] = "TER category {$cat} ({$y}): Payroll Settings has " . count($a) . " rows, Legal Basis " . count($b);
                        continue;
                    }
                    foreach ($a as $i => $row) {
                        if ($n < 5 && (abs($row['rate'] - $b[$i]['rate']) > 1e-9 || abs($row['gross_over'] - $b[$i]['gross_over']) > 1e-9)) {
                            $cmp("TER category {$cat} row " . ($i + 1) . " ({$y})", $row['rate'], $b[$i]['rate'], $pc);
                            $n++;
                        }
                    }
                }
            }
        }

        try {
            $svc = app(\App\Services\Payroll\BpjsSettingsService::class);
            $date = now()->toDateString();
            $base = \App\Support\Payroll\BpjsSettingRules::effectiveOn($svc->all(), $date);
            foreach ($this->bpjsOverlay($date) as $col => $val) {
                $cmp('BPJS ' . str_replace('_', ' ', $col), $base[$col] ?? null, $val, str_ends_with($col, '_rate') ? $pc : $rp);
            }
        } catch (\Throwable) {
            // belum ada versi BPJS di Payroll Settings: tidak ada pembanding
        }

        return $out;
    }

    // ── PPh 21 (per tahun pajak) ──────────────────────────────────────────

    /** @return array<string,float> kode PTKP → nilai setahun */
    public function ptkp(int $year): array
    {
        $out = [];
        foreach ((array) ($this->p('pph21.ptkp', $year)['codes'] ?? []) as $r) {
            $out[(string) $r['code']] = (float) $r['annual_amount'];
        }

        return $out;
    }

    /** @return array<int,array{upper_limit:?float,rate:float}> */
    public function brackets(int $year): array
    {
        return array_map(fn ($r) => ['upper_limit' => ($r['up_to'] ?? null) === null ? null : (float) $r['up_to'], 'rate' => $this->pct($r['rate'] ?? 0)],
            array_values((array) ($this->p('pph21.progressive', $year)['brackets'] ?? [])));
    }

    /** @return array<string,array<int,array{gross_over:float,gross_upto:?float,rate:float}>> */
    public function ter(int $year): array
    {
        $out = [];
        foreach ((array) ($this->p('pph21.ter', $year)['categories'] ?? []) as $cat => $rows) {
            foreach (array_values((array) $rows) as $r) {
                $out[(string) $cat][] = [
                    'gross_over' => (float) ($r['gross_over'] ?? 0),
                    'gross_upto' => ($r['gross_upto'] ?? null) === null ? null : (float) $r['gross_upto'],
                    'rate'       => $this->pct($r['rate'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /** @return array{occupational_cost_rate:float,occupational_cost_monthly_max:float} */
    public function occupational(int $year): array
    {
        $p = $this->p('pph21.occupational_cost', $year) ?? [];

        return ['occupational_cost_rate' => $this->pct($p['rate'] ?? 0), 'occupational_cost_monthly_max' => (float) ($p['max_monthly'] ?? 0)];
    }

    // ── BPJS (per tanggal) ────────────────────────────────────────────────

    /**
     * Kolom versi BPJS yang diambil dari Legal Basis pada $date (hanya yang ada di payload; sisanya tetap dari Payroll Settings,
     * mis. kelas JKK dan saklar). Persen sebagai 4.00.
     *
     * @return array<string,float>
     */
    public function bpjsOverlay(string $date): array
    {
        $o = [];
        $map = [
            'bpjs.kesehatan' => ['employer' => 'health_employer_rate', 'employee' => 'health_employee_rate', 'min_base' => 'health_min_base', 'wage_cap' => 'health_cap'],
            'bpjs.jht'       => ['employer' => 'jht_employer_rate', 'employee' => 'jht_employee_rate'],
            'bpjs.jp'        => ['employer' => 'jp_employer_rate', 'employee' => 'jp_employee_rate', 'wage_cap' => 'jp_cap'],
            'bpjs.jkk_jkm'   => ['jkm' => 'jkm_rate'],
        ];
        foreach ($map as $rule => $fields) {
            $p = $this->legal->payload($rule, $date) ?? [];
            foreach ($fields as $src => $col) {
                if (array_key_exists($src, $p) && $p[$src] !== null) {
                    $o[$col] = str_ends_with($col, '_rate') ? $this->pct($p[$src]) : (float) $p[$src];
                }
            }
        }

        return $o;
    }

    // ── bagian dalam ──────────────────────────────────────────────────────

    /** Payload versi berlaku untuk tahun pajak (31 Desember tahun itu). */
    private function p(string $key, int $year): ?array
    {
        return $this->legal->payload($key, sprintf('%04d-12-31', $year));
    }

    /** Pecahan (0.05) → persen (5.0), dibulatkan supaya 0.037 menjadi persis 3.7 (bukan 3.7000000000000006). */
    private function pct(mixed $fraction): float
    {
        return round((float) $fraction * 100, 6);
    }
}
