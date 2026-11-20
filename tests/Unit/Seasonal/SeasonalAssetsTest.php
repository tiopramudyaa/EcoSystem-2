<?php

namespace Tests\Unit\Seasonal;

use App\Http\Controllers\AuthController;
use App\Support\SeasonalThemes;
use App\Support\GlobalSeasonalTheme;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use ReflectionMethod;

/**
 * Kontrak aset tema: setiap entri katalog harus punya ikon, ornamen, dan adegan 3D
 * yang benar-benar ada di berkas, supaya tema baru tidak merender kosong.
 */
class SeasonalAssetsTest extends TestCase
{
    private function root(string $path = ''): string
    {
        return dirname(__DIR__, 3) . '/' . $path;
    }

    private function sprite(): string
    {
        return (string) file_get_contents($this->root('resources/views/partials/seasonal-icons.blade.php'));
    }

    public function test_setiap_tema_punya_ikon_dan_ornamen_di_sprite(): void
    {
        $sprite = $this->sprite();

        foreach (SeasonalThemes::keys() as $key) {
            if ($key === SeasonalThemes::NONE) {
                continue;
            }
            $meta = SeasonalThemes::get($key);
            $this->assertStringContainsString('id="st-icon-' . $key . '"', $sprite, "ikon $key");
            $this->assertNotEmpty($meta['ornaments'], $key);
            foreach ($meta['ornaments'] as $name) {
                $this->assertStringContainsString('id="st-orn-' . $name . '"', $sprite, "ornamen $name ($key)");
            }
        }
    }

    public function test_setiap_adegan_punya_modul_dan_terdaftar(): void
    {
        $stage = (string) file_get_contents($this->root('public/js/seasonal/stage.js'));

        foreach (SeasonalThemes::keys() as $key) {
            $scene = SeasonalThemes::get($key)['scene'];
            if ($key === SeasonalThemes::NONE) {
                $this->assertNull($scene);
                continue;
            }
            $this->assertFileExists($this->root("public/js/seasonal/scenes/$scene.js"), $key);
            $this->assertStringContainsString($scene, $stage, "stage.js harus mengenal $scene");
        }
    }

    public function test_ramadan_dan_lebaran_berbeda_jelas(): void
    {
        $r = SeasonalThemes::get(SeasonalThemes::RAMADAN);
        $l = SeasonalThemes::get(SeasonalThemes::LEBARAN);

        $this->assertNotSame($r['scene'], $l['scene']);
        $this->assertNotSame($r['decoration'], $l['decoration']);
        $this->assertNotSame($r['palette']['primary_rgb'], $l['palette']['primary_rgb']);
        $this->assertNotSame($r['ornaments'][0], $l['ornaments'][0]);
    }

    public function test_tidak_ada_emoji_di_katalog(): void
    {
        $src = (string) file_get_contents($this->root('app/Support/SeasonalThemes.php'));
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $src));
    }

    public function test_respons_login_membawa_tema_efektif_dari_preferences(): void
    {
        // Resolver kini membaca setting global (termasuk tema login); semai cache "belum ada setting".
        Cache::put('app_config_' . GlobalSeasonalTheme::KEY, ['found' => false, 'value' => null], 300);
        $method = new ReflectionMethod(AuthController::class, 'effectiveSeasonalTheme');
        $method->setAccessible(true);

        $user = fn ($prefs) => (object) ['preferences' => $prefs];

        $this->assertSame('natal', $method->invoke(null, $user('{"seasonal_theme":"natal"}')));
        $this->assertSame('none', $method->invoke(null, $user('{"seasonal_theme":"none"}')));
        $this->assertSame('none', $method->invoke(null, $user('{"seasonal_theme":"tidak-ada"}')));
        $this->assertSame('imlek', $method->invoke(null, $user(['seasonal_theme' => 'imlek'])));
    }

    public function test_setiap_tema_beranimasi_punya_poster_webp_ringan(): void
    {
        foreach (SeasonalThemes::keys() as $key) {
            if ($key === SeasonalThemes::NONE || empty(SeasonalThemes::get($key)['scene'])) {
                continue;
            }
            $file = $this->root("public/images/seasonal/posters/$key.webp");
            $this->assertFileExists($file, "poster $key");
            $this->assertLessThanOrEqual(40 * 1024, filesize($file), "poster $key maks 40 KB");
            $this->assertGreaterThan(2 * 1024, filesize($file), "poster $key tidak kosong");
            $head = (string) file_get_contents($file, false, null, 0, 12);
            $this->assertSame('RIFF', substr($head, 0, 4), "poster $key RIFF");
            $this->assertSame('WEBP', substr($head, 8, 4), "poster $key WEBP");
        }
    }

    public function test_setiap_poster_punya_thumbnail_kecil_untuk_kartu_tema(): void
    {
        foreach (SeasonalThemes::keys() as $key) {
            if ($key === SeasonalThemes::NONE || empty(SeasonalThemes::get($key)['scene'])) {
                continue;
            }
            $file = $this->root("public/images/seasonal/posters/thumb/$key.webp");
            $this->assertFileExists($file, "thumbnail $key");
            $this->assertLessThanOrEqual(8 * 1024, filesize($file), "thumbnail $key maks 8 KB");
            $this->assertSame('WEBP', substr((string) file_get_contents($file, false, null, 8, 4), 0, 4));
        }
    }
    public function test_partial_pratinjau_hanya_import_dinamis_dan_tidak_memuat_three_saat_load(): void
    {
        $src = (string) file_get_contents($this->root('resources/views/partials/seasonal-preview.blade.php'));

        // Tidak ada import statis, tag script src, atau preload modul 3D.
        $this->assertSame(0, preg_match('/^\s*import\s[^(]/m', $src), 'tanpa import statis');
        $this->assertSame(0, preg_match('/<script[^>]+src=/i', $src), 'tanpa script src');
        $this->assertSame(0, preg_match('/modulepreload|rel="preload"/i', $src), 'tanpa preload');
        $this->assertStringNotContainsString('three.module', $src);
        // import() dinamis hanya ada di dalam open() (setelah klik), bukan di tingkat atas IIFE.
        $this->assertSame(1, substr_count($src, 'import(STAGE)'));
        $open = strpos($src, 'function open()');
        $this->assertNotFalse($open);
        $this->assertGreaterThan($open, strpos($src, 'import(STAGE)'));
        // Kontrol wajib: dialog, Esc, dispose, tab tersembunyi, fallback hemat data.
        foreach (['role="dialog"', 'aria-modal="true"', "'Escape'", 'handle.stop()', 'visibilitychange', 'saveData', 'prefers-reduced-motion', 'MAX_MS = 6000'] as $needle) {
            $this->assertStringContainsString($needle, $src, $needle);
        }
        // Tanpa query DB atau panggilan jaringan selain aset statis.
        $this->assertSame(0, preg_match('/fetch\(|XMLHttpRequest|DB::|->get\(\)/', $src));
    }

    public function test_pratinjau_dipasang_di_settings_dan_halaman_admin(): void
    {
        foreach (['settings/index', 'admin/seasonal-theme-settings'] as $view) {
            $src = (string) file_get_contents($this->root("resources/views/$view.blade.php"));
            $this->assertStringContainsString("@include('partials.seasonal-preview'", $src, $view);
            $this->assertStringContainsString("@include('partials.seasonal-poster'", $src, $view);
        }
    }

    public function test_kunci_bahasa_pratinjau_en_dan_id_identik(): void
    {
        $en = require $this->root('lang/en/ui.php');
        $id = require $this->root('lang/id/ui.php');
        $this->assertSame(array_keys($en['seasonal']['preview']), array_keys($id['seasonal']['preview']));
        foreach ($en['seasonal']['preview'] as $k => $v) {
            $this->assertDoesNotMatchRegularExpression('/[\x{2013}\x{2014}]/u', $v . $id['seasonal']['preview'][$k], $k);
        }
    }
}
