<?php

namespace Tests\Unit\Seasonal;

use App\Support\SeasonalThemes;
use PHPUnit\Framework\TestCase;

/**
 * Ilustrasi panel login dan palet baru: berkas statis, batas ukuran, tanpa referensi luar,
 * kontras tombol, dan perbedaan tiga tema merah dari warna bahaya aplikasi.
 */
class LoginArtTest extends TestCase
{
    private const THEMES = ['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'];

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function lum(string $hex): float
    {
        $c = array_map(
            fn ($i) => hexdec(substr($hex, $i, 2)) / 255,
            [1, 3, 5]
        );
        $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function dist(string $a, string $b): float
    {
        $d = 0;
        foreach ([1, 3, 5] as $i) {
            $d += (hexdec(substr($a, $i, 2)) - hexdec(substr($b, $i, 2))) ** 2;
        }

        return sqrt($d);
    }

    private static function contrast(string $a, string $b): float
    {
        [$x, $y] = [self::lum($a), self::lum($b)];

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    public function test_setiap_tema_punya_svg_statis_yang_aman_dan_ringan(): void
    {
        foreach (self::THEMES as $key) {
            $file = self::root() . "/public/images/seasonal/login/$key.svg";
            $this->assertFileExists($file, $key);
            $svg = file_get_contents($file);

            $this->assertLessThanOrEqual(45000, strlen($svg), "$key terlalu besar");
            $this->assertStringContainsString('viewBox="0 0 1000 800"', $svg, $key);
            $this->assertStringNotContainsString('<script', $svg, $key);
            $this->assertStringNotContainsString('<image', $svg, $key);
            $this->assertStringNotContainsString('data:', $svg, $key);
            $this->assertStringNotContainsString('<foreignObject', $svg, $key);
            // Hanya referensi internal (#id); tidak ada URL luar.
            $this->assertDoesNotMatchRegularExpression('#(href|src)="(?!\#)#', $svg, $key);
            $this->assertDoesNotMatchRegularExpression('#url\((?!\#)#', $svg, $key);
            $this->assertStringNotContainsString("\u{2014}", $svg, $key);
            $this->assertStringNotContainsString("\u{2013}", $svg, $key);
        }
    }

    public function test_partial_hanya_memuat_gambar_tema_aktif_dengan_gerakan_terbatas(): void
    {
        $partial = file_get_contents(self::root() . '/resources/views/partials/login-art.blade.php');

        // Gambar lewat CSS background untuk tema aktif saja.
        $this->assertStringContainsString('asset(\'images/seasonal/login/\' . $laKey . \'.svg\')', $partial);
        $this->assertStringContainsString('aspect-ratio: 5 / 4', $partial);
        $this->assertStringContainsString('prefers-reduced-motion: no-preference', $partial);

        // Paling banyak 12 elemen animasi per tema.
        preg_match_all("/'(natal|tahun_baru|ramadan|lebaran|imlek|hut_ri)' => \[(.*?)\n        \],/s", $partial, $m, PREG_SET_ORDER);
        $this->assertCount(6, $m);
        foreach ($m as $set) {
            $this->assertLessThanOrEqual(12, substr_count($set[2], "['"), $set[1]);
        }

        // Animasi hanya transform dan opacity.
        preg_match_all('/@keyframes dpa-[a-z]+ \{(.*?)\n        \}/s', $partial, $k);
        $this->assertNotEmpty($k[1]);
        foreach ($k[1] as $body) {
            $this->assertDoesNotMatchRegularExpression('/\b(top|left|right|bottom|width|height|margin)\s*:/', $body);
        }
    }

    public function test_login_tidak_menyertakan_ilustrasi_tanpa_tema_dan_memakai_variabel_palet(): void
    {
        $login = file_get_contents(self::root() . '/resources/views/auth/login.blade.php');

        $this->assertStringContainsString("@if(\$loginSeasonalTheme !== 'none')", $login);
        $this->assertStringContainsString("@include('partials.login-art'", $login);
        $this->assertStringContainsString('var(--st-login-mid', $login);
        $this->assertStringContainsString('--st-button', $login);
        // Form login dan id penting tetap ada.
        foreach (['id="submitBtn"', 'class="lp-btn"', 'id="deco-panel"', 'id="login-panel"'] as $needle) {
            $this->assertStringContainsString($needle, $login);
        }
    }

    public function test_palet_punya_kunci_baru_dan_tombol_lolos_kontras_aa(): void
    {
        foreach (self::THEMES as $key) {
            $p = SeasonalThemes::get($key)['palette'];
            foreach (['login_mid', 'button', 'loading_bottom'] as $name) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $p[$name], "$key.$name");
            }
            $text = $p['button_text'] ?? '#ffffff';
            $this->assertGreaterThanOrEqual(4.5, self::contrast($p['button'], $text), "$key tombol");
            // Tautan dan label di atas putih memakai link (atau tombol bila tidak ada).
            $link = $p['link'] ?? $p['button'];
            $this->assertGreaterThanOrEqual(4.5, self::contrast($link, '#ffffff'), "$key tautan");
        }
    }

    public function test_tahun_baru_memakai_tombol_arang_dan_tanpa_citra_minuman(): void
    {
        $p = SeasonalThemes::get('tahun_baru')['palette'];
        $this->assertSame('#2b2b36', $p['button']);
        $this->assertSame('fa-clock', SeasonalThemes::get('tahun_baru')['icon']);
        $this->assertNotContains('glass', SeasonalThemes::get('tahun_baru')['ornaments']);
        $svg = file_get_contents(self::root() . '/public/images/seasonal/login/tahun_baru.svg');
        $this->assertStringNotContainsString('flute', strtolower($svg));
    }

    public function test_loading_bawah_lebaran_dan_ramadan_memakai_login_to(): void
    {
        foreach (['lebaran', 'ramadan'] as $key) {
            $p = SeasonalThemes::get($key)['palette'];
            $this->assertSame($p['login_to'], $p['loading_bottom'], $key);
        }
        foreach (['natal', 'imlek', 'hut_ri'] as $key) {
            $p = SeasonalThemes::get($key)['palette'];
            $this->assertSame($p['login_mid'], $p['loading_bottom'], $key);
        }
    }

    public function test_tiga_tema_merah_berbeda_satu_sama_lain_dan_dari_warna_bahaya(): void
    {
        $danger = '#dc2626';
        $buttons = [];
        foreach (['natal', 'imlek', 'hut_ri'] as $key) {
            $buttons[$key] = SeasonalThemes::get($key)['palette']['button'];
            $this->assertNotSame($danger, $buttons[$key], $key);
            // Lebih gelap dari merah bahaya supaya tidak terbaca sebagai tombol hapus.
            $this->assertLessThan(self::lum($danger), self::lum($buttons[$key]), $key);
            $this->assertGreaterThanOrEqual(50, self::dist($buttons[$key], $danger), "$key jarak warna ke bahaya");
        }
        $this->assertCount(3, array_unique($buttons));
        $this->assertSame('#0b2a1e', SeasonalThemes::get('natal')['palette']['login_from']);
        $this->assertSame('#3a0a05', SeasonalThemes::get('imlek')['palette']['login_from']);
        $this->assertSame('#3d0610', SeasonalThemes::get('hut_ri')['palette']['login_from']);
    }
}
