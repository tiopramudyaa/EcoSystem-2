<?php

namespace Tests\Unit\Seasonal;

use App\Support\SeasonalNavbarArt;
use App\Support\SeasonalThemes;
use PHPUnit\Framework\TestCase;

class SeasonalNavbarArtTest extends TestCase
{
    public function test_setiap_tema_aktif_punya_dua_potong_svg_valid_dan_ringan(): void
    {
        foreach (SeasonalThemes::keys() as $key) {
            if ($key === 'none') {
                $this->assertNull(SeasonalNavbarArt::pieces($key));
                continue;
            }
            $p = SeasonalNavbarArt::pieces($key);
            $this->assertNotNull($p, $key);
            foreach (['l', 'r', 'fl', 'fr'] as $side) {
                $svg = '<svg xmlns="http://www.w3.org/2000/svg">' . $p[$side] . '</svg>';
                $this->assertNotFalse(simplexml_load_string($svg), "$key/$side bukan XML valid");
                $this->assertLessThan(14000, strlen($p[$side]), "$key/$side terlalu besar");
                $this->assertStringNotContainsString(' id=', $p[$side]);
                $this->assertStringNotContainsString('script', $p[$side]);
                $this->assertStringNotContainsString('NaN', $p[$side]);
                $this->assertLessThanOrEqual(8, substr_count($p[$side], 'class="sn-'), "$key/$side melebihi 8 elemen bergerak");
                $this->assertStringNotContainsString('filter', $p[$side]);
            }
        }
    }

    public function test_tiap_tema_punya_lapisan_ketiga_yang_jarang_di_pojok_luar(): void
    {
        foreach (SeasonalThemes::keys() as $key) {
            if ($key === 'none') {
                continue;
            }
            $p = SeasonalNavbarArt::pieces($key);
            foreach (['fl', 'fr'] as $side) {
                $this->assertNotSame('', $p[$side], "$key/$side kosong");
                $this->assertLessThanOrEqual(8, substr_count($p[$side], 'class="sn-'));
                $this->assertGreaterThanOrEqual(2, substr_count($p[$side], 'class="sn-'));
            }
        }
    }

    public function test_ubin_es_natal_dan_bintang_tepi_valid_dan_kecil(): void
    {
        foreach ([SeasonalNavbarArt::frostTile(), SeasonalNavbarArt::edgeStarsTile()] as $uri) {
            $this->assertStringStartsWith('data:image/svg+xml,', $uri);
            $this->assertLessThan(14000, strlen($uri));
            $svg = rawurldecode(substr($uri, strlen('data:image/svg+xml,')));
            $this->assertNotFalse(simplexml_load_string($svg));
            $this->assertStringNotContainsString('NaN', $svg);
            $this->assertStringNotContainsString(' id=', $svg);
        }
        // kristal es tidak lebih tinggi dari 6 unit
        $svg = rawurldecode(substr(SeasonalNavbarArt::frostTile(), strlen('data:image/svg+xml,')));
        $this->assertStringContainsString('viewBox="0 0 480 6"', $svg);
        preg_match_all('/L[\d.]+ ([\d.]+)Z/', $svg, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertLessThanOrEqual(6.0, max(array_map('floatval', $m[1])));
    }

    public function test_aksen_dashboard_dibatasi_ke_tema_aktif_dan_tanpa_efek_berat(): void
    {
        $src = file_get_contents(dirname(__DIR__, 3) . '/resources/views/partials/seasonal-accents.blade.php');
        $this->assertNotFalse($src);
        $css = preg_replace('/\{\{--.*?--\}\}/s', '', $src);
        $this->assertStringNotContainsString('backdrop-filter', $css);
        $this->assertStringNotContainsString('filter:', $css);
        $this->assertStringNotContainsString('box-shadow', $css);
        $this->assertStringNotContainsString('@keyframes', $css);
        $this->assertStringNotContainsString("\u{2014}", $src);
        $this->assertStringNotContainsString("\u{2013}", $src);
        $this->assertStringContainsString('html[data-season=', $css);
        foreach (['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'] as $k) {
            $this->assertStringContainsString("@case('$k')", $css);
        }
    }

    public function test_tema_tak_dikenal_tidak_punya_hiasan(): void
    {
        $this->assertNull(SeasonalNavbarArt::pieces('paskah'));
    }
}
