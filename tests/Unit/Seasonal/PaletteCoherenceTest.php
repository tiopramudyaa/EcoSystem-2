<?php

namespace Tests\Unit\Seasonal;

use App\Support\SeasonalThemes;
use PHPUnit\Framework\TestCase;

/**
 * Satu identitas warna per tema: login, loading screen, dan dashboard (sidebar, tombol, tautan)
 * berasal dari satu keluarga warna. Tema bukan 'none' saja.
 */
class PaletteCoherenceTest extends TestCase
{
    private const THEMES = ['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'];

    private static function rgb(string $v): array
    {
        if ($v[0] === '#') {
            return [hexdec(substr($v, 1, 2)), hexdec(substr($v, 3, 2)), hexdec(substr($v, 5, 2))];
        }

        return array_map('intval', preg_split('/\s+/', trim($v)));
    }

    /** @return array{0: float, 1: float} hue (derajat) dan chroma (0..1) */
    private static function hueChroma(array $c): array
    {
        [$r, $g, $b] = array_map(fn ($x) => $x / 255, $c);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $d = $max - $min;
        if ($d == 0) {
            return [0.0, 0.0];
        }
        $h = match ($max) {
            $r => fmod(($g - $b) / $d, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };
        $h *= 60;

        return [$h < 0 ? $h + 360 : $h, $d];
    }

    private static function lum(array $c): float
    {
        $c = array_map(fn ($v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function sameFamily(array $a, array $b, string $msg): void
    {
        [$ha, $ca] = self::hueChroma($a);
        [$hb, $cb] = self::hueChroma($b);
        if (max($ca, $cb) < 0.12) {
            // Hampir netral (arang atau hitam): bandingkan terang gelapnya.
            self::assertLessThan(0.05, abs(self::lum($a) - self::lum($b)), $msg . ' (luminans)');

            return;
        }
        $diff = abs($ha - $hb);
        $diff = min($diff, 360 - $diff);
        self::assertLessThanOrEqual(25, $diff, $msg . " (hue $ha vs $hb)");
    }

    public function test_primary_sepadan_dengan_gradien_login(): void
    {
        foreach (self::THEMES as $key) {
            $p = SeasonalThemes::get($key)['palette'];
            self::sameFamily(self::rgb($p['primary_rgb']), self::rgb($p['login_to']), "$key primary vs login_to");
            self::sameFamily(self::rgb($p['primary_dark_rgb']), self::rgb($p['login_mid']), "$key primary_dark vs login_mid");
            self::sameFamily(self::rgb($p['button']), self::rgb($p['primary_rgb']), "$key tombol vs primary");
            self::sameFamily(self::rgb($p['loading_bottom']), self::rgb($p['login_mid']), "$key loading vs login_mid");
        }
    }

    public function test_teks_putih_di_atas_primary_lolos_aa_dan_beda_dari_warna_bahaya(): void
    {
        $danger = [220, 38, 38];
        foreach (self::THEMES as $key) {
            $p = SeasonalThemes::get($key)['palette'];
            foreach (['primary_rgb', 'primary_dark_rgb'] as $name) {
                $l = self::lum(self::rgb($p[$name]));
                $this->assertGreaterThanOrEqual(4.5, 1.05 / ($l + 0.05), "$key $name");
            }
            $pr = self::rgb($p['primary_rgb']);
            $dist = sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $pr, $danger)));
            $this->assertGreaterThanOrEqual(50, $dist, "$key primary terlalu dekat dengan merah bahaya");
        }
    }

    public function test_aksen_sama_di_semua_permukaan(): void
    {
        $root = dirname(__DIR__, 3);
        $vars = file_get_contents($root . '/resources/views/partials/seasonal-theme-vars.blade.php');
        $loading = file_get_contents($root . '/resources/views/partials/seasonal-loading.blade.php');
        // Dashboard dan login memakai --st-accent dari palet; loading mengisinya dari palet yang sama.
        $this->assertStringContainsString("--st-accent: {{ \$stVarsPalette['accent'] }}", $vars);
        $this->assertStringContainsString("setProperty('--st-accent', p.accent)", $loading);
        foreach (self::THEMES as $key) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', SeasonalThemes::get($key)['palette']['accent']);
        }
    }

    public function test_identitas_dominan_tiap_tema(): void
    {
        $hue = fn (string $k) => self::hueChroma(self::rgb(SeasonalThemes::get($k)['palette']['primary_rgb']))[0];
        $this->assertEqualsWithDelta(150, $hue('natal'), 20);
        $this->assertEqualsWithDelta(222, $hue('ramadan'), 20);
        $this->assertEqualsWithDelta(125, $hue('lebaran'), 25);
        $this->assertEqualsWithDelta(15, $hue('imlek'), 15);
        $this->assertEqualsWithDelta(350, $hue('hut_ri'), 12);
        // Tahun baru: arang hampir hitam.
        $this->assertLessThan(0.05, self::lum(self::rgb(SeasonalThemes::get('tahun_baru')['palette']['primary_dark_rgb'])));
        $this->assertLessThan(0.05, self::hueChroma(self::rgb(SeasonalThemes::get('tahun_baru')['palette']['primary_rgb']))[1]);
    }
}
