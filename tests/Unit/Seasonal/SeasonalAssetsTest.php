<?php

namespace Tests\Unit\Seasonal;

use App\Http\Controllers\AuthController;
use App\Support\SeasonalThemes;
use PHPUnit\Framework\TestCase;
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
        $method = new ReflectionMethod(AuthController::class, 'effectiveSeasonalTheme');
        $method->setAccessible(true);

        $user = fn ($prefs) => (object) ['preferences' => $prefs];

        $this->assertSame('natal', $method->invoke(null, $user('{"seasonal_theme":"natal"}')));
        $this->assertSame('none', $method->invoke(null, $user('{"seasonal_theme":"none"}')));
        $this->assertSame('none', $method->invoke(null, $user('{"seasonal_theme":"tidak-ada"}')));
        $this->assertSame('imlek', $method->invoke(null, $user(['seasonal_theme' => 'imlek'])));
    }
}
