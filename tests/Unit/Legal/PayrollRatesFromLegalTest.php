<?php

namespace Tests\Unit\Legal;

use App\Services\Legal\LegalBasisService;
use App\Services\Legal\PayrollRatesFromLegal as L;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tarif regulasi Payroll dibaca dari Legal Basis: bentuk keluarannya harus sama dengan yang dibaca mesin Payroll dari Payroll Settings
 * (persen sebagai 5.0, bukan 0.05), versi dipilih menurut tanggal berlaku, dan aturan yang belum lengkap dilaporkan (bukan diam-diam nol).
 */
class PayrollRatesFromLegalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite tidak aktif');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('legal_basis_register', function ($t) {
            $t->id(); $t->string('rule_key'); $t->string('module')->default('pph21'); $t->string('status')->default('needs_review');
            $t->date('effective_date'); $t->text('payload')->nullable(); $t->string('number')->default('1'); $t->integer('year')->default(2026); $t->string('regulation_type')->default('PP');
            $t->unsignedBigInteger('reviewed_by')->nullable(); $t->timestamp('reviewed_at')->nullable();
        });
    }

    private function rule(string $key, array|null $payload, string $date = '2020-01-01', string $status = 'needs_review'): void
    {
        DB::table('legal_basis_register')->insert(['rule_key' => $key, 'effective_date' => $date, 'status' => $status, 'payload' => $payload === null ? null : json_encode($payload)]);
    }

    private function svc(): L
    {
        return new L(new LegalBasisService());
    }

    public function test_pph21_values_are_returned_in_the_shape_payroll_reads_with_percent_as_percent(): void
    {
        $this->rule('pph21.progressive', ['brackets' => [['up_to' => 60000000, 'rate' => 0.05], ['up_to' => null, 'rate' => 0.35]]]);
        $this->rule('pph21.ptkp', ['codes' => [['code' => 'TK/0', 'description' => 'x', 'annual_amount' => 54000000], ['code' => 'K/1', 'description' => 'y', 'annual_amount' => 63000000]]]);
        $this->rule('pph21.ter', ['categories' => ['A' => [['gross_over' => 0, 'gross_upto' => 5400000, 'rate' => 0], ['gross_over' => 5400000, 'gross_upto' => null, 'rate' => 0.0025]]]]);
        $this->rule('pph21.occupational_cost', ['rate' => 0.05, 'max_monthly' => 500000]);

        $s = $this->svc();
        $this->assertSame([['upper_limit' => 60000000.0, 'rate' => 5.0], ['upper_limit' => null, 'rate' => 35.0]], $s->brackets(2026));
        $this->assertSame(['TK/0' => 54000000.0, 'K/1' => 63000000.0], $s->ptkp(2026));
        $this->assertSame([['gross_over' => 0.0, 'gross_upto' => 5400000.0, 'rate' => 0.0], ['gross_over' => 5400000.0, 'gross_upto' => null, 'rate' => 0.25]], $s->ter(2026)['A']);
        $this->assertSame(['occupational_cost_rate' => 5.0, 'occupational_cost_monthly_max' => 500000.0], $s->occupational(2026));
    }

    public function test_float_noise_is_removed_so_3_7_percent_is_exactly_3_7(): void
    {
        $this->rule('bpjs.jht', ['employer' => 0.037, 'employee' => 0.02]);

        $o = $this->svc()->bpjsOverlay('2026-06-30');
        $this->assertSame(3.7, $o['jht_employer_rate']);
        $this->assertSame(2.0, $o['jht_employee_rate']);
    }

    public function test_bpjs_overlay_maps_rates_and_ceilings_and_picks_the_version_in_force_on_the_date(): void
    {
        $this->rule('bpjs.kesehatan', ['employer' => 0.04, 'employee' => 0.01, 'wage_cap' => 12000000, 'min_base' => 0]);
        $this->rule('bpjs.jp', ['employer' => 0.02, 'employee' => 0.01, 'wage_cap' => 10547400], '2025-03-01');
        $this->rule('bpjs.jp', ['employer' => 0.02, 'employee' => 0.01, 'wage_cap' => 11086300], '2026-03-01');
        $this->rule('bpjs.jkk_jkm', ['jkm' => 0.003, 'jkk_min' => 0.001, 'jkk_max' => 0.016]);

        $s = $this->svc();
        $feb = $s->bpjsOverlay('2026-02-28');
        $mar = $s->bpjsOverlay('2026-03-01');
        $this->assertSame(10547400.0, $feb['jp_cap'], 'sebelum 1 Mar 2026: versi lama');
        $this->assertSame(11086300.0, $mar['jp_cap'], 'sejak 1 Mar 2026: versi baru');
        $this->assertSame(12000000.0, $mar['health_cap']);
        $this->assertSame(4.0, $mar['health_employer_rate']);
        $this->assertSame(0.3, $mar['jkm_rate']);
        $this->assertArrayNotHasKey('jkk_rate', $mar, 'kelas JKK adalah pilihan perusahaan: tetap dari Payroll Settings');
    }

    public function test_a_superseded_version_and_a_future_version_are_not_used(): void
    {
        $this->rule('bpjs.jht', ['employer' => 0.037, 'employee' => 0.02], '2015-07-01');
        $this->rule('bpjs.jht', ['employer' => 0.05, 'employee' => 0.05], '2020-01-01', 'superseded');
        $this->rule('bpjs.jht', ['employer' => 0.09, 'employee' => 0.09], '2999-01-01');

        $this->assertSame(3.7, $this->svc()->bpjsOverlay('2026-06-30')['jht_employer_rate']);
    }

    public function test_missing_reports_rules_without_values_instead_of_silently_using_zero(): void
    {
        $this->rule('pph21.progressive', ['brackets' => [['up_to' => null, 'rate' => 0.05]]]);
        $this->rule('pph21.ptkp', null);                                          // ada, tetapi kosong
        $this->rule('bpjs.kesehatan', ['employer' => 0.04, 'employee' => 0.01]);  // tanpa wage_cap

        $m = $this->svc()->missing();
        $this->assertSame('has no calculation values', $m['pph21.ptkp']);
        $this->assertSame('is missing “wage_cap”', $m['bpjs.kesehatan']);
        $this->assertSame('has no version in force', $m['pph21.ter']);
        $this->assertArrayNotHasKey('pph21.progressive', $m);
    }

    public function test_payroll_is_blocked_only_when_legal_is_the_source_and_something_is_missing(): void
    {
        $legal = new LegalBasisService();
        $this->assertSame([], $legal->payrollBlockers(), 'sumber bawaan = Payroll Settings: tidak ada penahan baru');
    }

    // ── Lembur ────────────────────────────────────────────────────────────

    private function overtimePayload(array $over = []): array
    {
        return $over + [
            'monthly_divisor' => 173,
            'workday' => [['hours' => 1, 'multiplier' => 1.5], ['hours' => null, 'multiplier' => 2]],
            'rest_day_5' => [['hours' => 8, 'multiplier' => 2], ['hours' => 1, 'multiplier' => 3], ['hours' => null, 'multiplier' => 4]],
            'rest_day_6' => [['hours' => 7, 'multiplier' => 2], ['hours' => 1, 'multiplier' => 3], ['hours' => null, 'multiplier' => 4]],
        ];
    }

    public function test_overtime_rules_equal_to_the_standard_give_the_same_pay_as_the_built_in_rules(): void
    {
        $this->rule('overtime.multipliers', $this->overtimePayload());
        $rules = $this->svc()->overtimeRules('2026-06-30');
        $this->assertNotNull($rules);
        $builtIn = new \App\Services\Overtime\OvertimeRateService();
        $fromLegal = new \App\Services\Overtime\OvertimeRateService($rules);

        foreach (['workday', 'weekend', 'public_holiday'] as $day) {
            foreach ([5, 6] as $week) {
                foreach ([30, 60, 75, 120, 420, 450, 480, 540, 570, 630, 840] as $minutes) {
                    $this->assertEquals($builtIn->calculate($minutes, $day, 57803.47, $week)['amount'], $fromLegal->calculate($minutes, $day, 57803.47, $week)['amount'], "{$day} {$week}-day {$minutes} min");
                }
            }
        }
        $this->assertSame([], $this->svc()->differencesOvertime(), 'isi register = aturan baku: tidak ada selisih');
    }

    public function test_changed_overtime_rules_change_the_pay_and_the_divisor_changes_the_hourly_wage(): void
    {
        $this->rule('overtime.multipliers', $this->overtimePayload(['monthly_divisor' => 160, 'workday' => [['hours' => 2, 'multiplier' => 1.75], ['hours' => null, 'multiplier' => 2.5]]]));
        $svc = new \App\Services\Overtime\OvertimeRateService($this->svc()->overtimeRules('2026-06-30'));

        $this->assertEquals(62500.0, $svc->hourlyRateFromMonthly(10000000), 'pembagi 160');
        $r = $svc->calculate(180, 'workday', 62500.0, 5);   // 2 jam x 1,75 + 1 jam x 2,5
        $this->assertEquals(2 * 1.75 + 2.5, $r['weighted_hours']);
        $this->assertCount(2, $this->svc()->differencesOvertime(), 'pembagi dan hari kerja berbeda dari aturan baku');
    }

    public function test_the_last_band_always_covers_all_remaining_hours_so_no_minute_is_lost(): void
    {
        $this->rule('overtime.multipliers', $this->overtimePayload(['workday' => [['hours' => 1, 'multiplier' => 1.5], ['hours' => 3, 'multiplier' => 2]]]));

        $m = $this->svc()->missingOvertime();
        $this->assertStringContainsString('last row', $m['overtime.multipliers']);
        $this->assertNull($this->svc()->overtimeRules('2026-06-30'), 'aturan tidak lengkap: tidak dipakai (bukan dipakai setengah)');
    }

    public function test_incomplete_or_invalid_overtime_rules_are_reported_with_a_reason(): void
    {
        $cases = [
            [null, 'has no version in force'],
            [['monthly_divisor' => 0] + $this->overtimePayload(), 'monthly_divisor'],
            [array_diff_key($this->overtimePayload(), ['rest_day_6' => 1]), 'rest_day_6'],
            [$this->overtimePayload(['workday' => [['hours' => null, 'multiplier' => 0]]]), 'multiplier above 0'],
            [$this->overtimePayload(['workday' => [['hours' => -1, 'multiplier' => 1.5], ['hours' => null, 'multiplier' => 2]]]), 'hours above 0'],
        ];
        foreach ($cases as [$payload, $needle]) {
            DB::table('legal_basis_register')->delete();
            if ($payload !== null) {
                $this->rule('overtime.multipliers', $payload);
            }
            $m = $this->svc()->missingOvertime();
            $this->assertArrayHasKey('overtime.multipliers', $m);
            $this->assertStringContainsString($needle, $m['overtime.multipliers']);
        }
    }

    public function test_the_two_switches_are_independent_and_default_to_the_old_behaviour(): void
    {
        $this->assertFalse(L::useLegal(L::AREA_RATES));
        $this->assertFalse(L::useLegal(L::AREA_OVERTIME));
        $this->assertSame('payroll_rates_source', L::configKey(L::AREA_RATES));
        $this->assertSame('payroll_overtime_source', L::configKey(L::AREA_OVERTIME));
    }
}
