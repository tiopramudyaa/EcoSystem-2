<?php

namespace Tests\Unit\Seasonal;

use App\Support\GlobalSeasonalTheme;
use App\Support\SeasonalThemeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSeasonalThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Lingkungan uji tanpa driver sqlite: AppConfig::get() membaca lewat
        // Cache (array), jadi nilai "tersimpan" disemai langsung ke cache dan
        // GlobalSeasonalTheme::all() tetap menyanitasi ulang saat dibaca.
        $this->storeRaw([]);
    }

    private function storeRaw(array $stored): void
    {
        Cache::put('app_config_' . GlobalSeasonalTheme::KEY, [
            'found' => $stored !== [],
            'value' => $stored === [] ? null : json_encode($stored),
        ], 300);
    }

    private function save(array $over = []): void
    {
        $this->storeRaw($over + [
            'theme' => 'none', 'sound' => null, 'volume' => 50, 'mode' => 'manual', 'schedule' => [],
        ]);
    }

    private function on(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    public function test_default_saat_belum_ada_data(): void
    {
        $this->assertSame(
            ['theme' => 'none', 'sound' => null, 'volume' => 50, 'mode' => 'manual', 'schedule' => []],
            GlobalSeasonalTheme::all()
        );
        $this->assertSame('none', GlobalSeasonalTheme::active());
    }

    public function test_mode_rusak_jatuh_ke_manual_dan_tema_rusak_ke_none(): void
    {
        $this->save(['theme' => 'paskah', 'mode' => 'bogus']);
        $all = GlobalSeasonalTheme::all();
        $this->assertSame('manual', $all['mode']);
        $this->assertSame('none', $all['theme']);
    }

    public function test_sanitasi_jadwal_membuang_entri_rusak(): void
    {
        $this->save(['mode' => 'auto', 'schedule' => [
            ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26'],   // valid
            ['theme' => 'natal', 'start' => '2026-13-01', 'end' => '2026-12-26'],   // bulan rusak
            ['theme' => 'natal', 'start' => '2026-02-30', 'end' => '2026-03-01'],   // tidak ada di kalender
            ['theme' => 'natal', 'start' => '01/12/2026', 'end' => '26/12/2026'],   // format salah
            ['theme' => 'natal', 'start' => '2026-12-10', 'end' => '2026-12-01'],   // akhir < awal
            ['theme' => 'none', 'start' => '2026-01-01', 'end' => '2026-01-02'],    // none dibuang
            ['theme' => 'paskah', 'start' => '2026-01-01', 'end' => '2026-01-02'],  // tak dikenal
            'bukan array',
        ]]);

        $this->assertSame(
            [['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26']],
            GlobalSeasonalTheme::all()['schedule']
        );
    }

    public function test_jadwal_dibatasi_20_entri(): void
    {
        $entries = [];
        for ($i = 1; $i <= 25; $i++) {
            $entries[] = ['theme' => 'natal', 'start' => '2026-01-01', 'end' => '2026-01-02'];
        }
        $this->save(['schedule' => $entries]);
        $this->assertCount(20, GlobalSeasonalTheme::all()['schedule']);
    }

    public function test_mode_auto_memilih_entri_berdasarkan_tanggal_inklusif(): void
    {
        $this->save(['mode' => 'auto', 'schedule' => [
            ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26'],
            ['theme' => 'tahun_baru', 'start' => '2026-12-27', 'end' => '2027-01-03'],
        ]]);

        $this->assertSame('natal', GlobalSeasonalTheme::active($this->on('2026-12-01')));
        $this->assertSame('natal', GlobalSeasonalTheme::active($this->on('2026-12-26')));
        $this->assertSame('tahun_baru', GlobalSeasonalTheme::active($this->on('2026-12-27')));
        $this->assertSame('tahun_baru', GlobalSeasonalTheme::active($this->on('2027-01-03')));
    }

    public function test_mode_auto_di_luar_rentang_memberi_none(): void
    {
        $this->save(['theme' => 'natal', 'mode' => 'auto', 'schedule' => [
            ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26'],
        ]]);

        $this->assertSame('none', GlobalSeasonalTheme::active($this->on('2026-11-30')));
        $this->assertSame('none', GlobalSeasonalTheme::active($this->on('2027-01-01')));
    }

    public function test_mode_manual_mengabaikan_jadwal(): void
    {
        $this->save(['theme' => 'imlek', 'mode' => 'manual', 'schedule' => [
            ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26'],
        ]]);

        $this->assertSame('imlek', GlobalSeasonalTheme::active($this->on('2026-12-10')));
        $this->assertSame('imlek', GlobalSeasonalTheme::active($this->on('2030-01-01')));
    }

    public function test_rentang_tumpang_tindih_entri_pertama_menang(): void
    {
        $this->save(['mode' => 'auto', 'schedule' => [
            ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-31'],
            ['theme' => 'tahun_baru', 'start' => '2026-12-20', 'end' => '2027-01-03'],
        ]]);
        $this->assertSame('natal', GlobalSeasonalTheme::active($this->on('2026-12-25')));
    }

    public function test_volume_dijepit_dan_sound_tak_ada_dibuang(): void
    {
        $this->save(['volume' => 250, 'sound' => '../../etc/passwd.mp3']);
        $all = GlobalSeasonalTheme::all();
        $this->assertSame(100, $all['volume']);
        $this->assertNull($all['sound']);
        $this->assertNull(GlobalSeasonalTheme::soundUrl());
    }

    public function test_resolver_default_ikut_global_none_menimpa_key_valid_menimpa_dan_invalid_jadi_none(): void
    {
        $this->save(['theme' => 'natal']);

        $this->assertSame('natal', SeasonalThemeResolver::effectiveFor('default'));
        $this->assertSame('none', SeasonalThemeResolver::effectiveFor('none'));
        $this->assertSame('imlek', SeasonalThemeResolver::effectiveFor('imlek'));
        $this->assertSame('none', SeasonalThemeResolver::effectiveFor('paskah'));
        $this->assertSame('none', SeasonalThemeResolver::effectiveFor(''));
    }
}
