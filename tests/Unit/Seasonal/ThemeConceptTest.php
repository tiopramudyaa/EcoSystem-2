<?php

namespace Tests\Unit\Seasonal;

use App\Support\GlobalSeasonalTheme;
use App\Support\SeasonalThemeResolver;
use App\Support\SeasonalThemes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bukti konsep tema yang disepakati:
 *   1. Admin bisa mengatur tema global (Control Center) dan tema dirinya sendiri (Settings).
 *   2. User lain bisa memilih Default, tema sendiri, atau Off.
 *   3. User yang memilih Default mengikuti aturan global admin (termasuk jadwal).
 * Resolver tidak menerima role sama sekali, jadi admin dan user biasa diperlakukan sama di sisi tampilan;
 * perbedaannya hanya izin menu untuk MENGUBAH global.
 */
class ThemeConceptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function global(array $over): void
    {
        GlobalSeasonalTheme::flushMemo();
        Cache::put('app_config_' . GlobalSeasonalTheme::KEY, [
            'found' => true,
            'value' => json_encode($over + ['theme' => 'none', 'sound' => null, 'volume' => 50, 'mode' => 'manual', 'schedule' => []]),
        ], 300);
    }

    /** @return array<string,array{0:string,1:string,2:string}> */
    public static function matrix(): array
    {
        $rows = [];
        foreach (['none', 'natal', 'imlek'] as $global) {
            foreach (['default', 'none', 'ramadan'] as $pref) {
                // aturan: default ikut global, none mematikan, tema lain dipilih sendiri (menang atas global)
                $expect = $pref === 'default' ? $global : $pref;
                $rows["global=$global user=$pref"] = [$global, $pref, $expect];
            }
        }

        return $rows;
    }

    #[DataProvider('matrix')]
    public function test_matriks_global_x_preferensi_user(string $global, string $pref, string $expect): void
    {
        $this->global(['theme' => $global]);
        $this->assertSame($expect, SeasonalThemeResolver::effectiveFor($pref));
    }

    public function test_default_mengikuti_jadwal_otomatis_admin_per_tanggal(): void
    {
        $this->global(['mode' => 'auto', 'schedule' => [['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26']]]);

        $this->assertSame('natal', GlobalSeasonalTheme::active(CarbonImmutable::parse('2026-12-10')));
        $this->assertSame('none', GlobalSeasonalTheme::active(CarbonImmutable::parse('2026-11-30')));
        // pilihan sendiri tidak tergantung jadwal
        $this->assertSame('imlek', SeasonalThemeResolver::effectiveFor('imlek'));
    }

    public function test_pilihan_user_yang_boleh_adalah_default_off_dan_semua_tema(): void
    {
        $choices = SeasonalThemes::userChoices();
        $this->assertContains('default', $choices);
        $this->assertContains('none', $choices);
        foreach (['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'] as $k) {
            $this->assertContains($k, $choices);
        }
        $this->assertNotContains('paskah', $choices);
    }

    public function test_resolver_tidak_punya_masukan_role(): void
    {
        $m = new \ReflectionMethod(SeasonalThemeResolver::class, 'effectiveFor');
        $this->assertCount(1, $m->getParameters(), 'hanya preferensi user, tidak ada role');
    }

    public function test_hanya_pemegang_izin_menu_yang_mengubah_global(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3) . '/routes/web.php');
        foreach (["'/seasonal-theme', [", "'/seasonal-theme/upload-sound'"] as $needle) {
            $this->assertStringContainsString($needle, $routes);
        }
        $this->assertSame(3, substr_count($routes, "menu:control-center.seasonal-theme"), 'GET, POST, upload-sound');
    }
}
