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
            foreach (['l', 'r'] as $side) {
                $svg = '<svg xmlns="http://www.w3.org/2000/svg">' . $p[$side] . '</svg>';
                $this->assertNotFalse(simplexml_load_string($svg), "$key/$side bukan XML valid");
                $this->assertLessThan(14000, strlen($p[$side]), "$key/$side terlalu besar");
                $this->assertStringNotContainsString(' id=', $p[$side]);
                $this->assertStringNotContainsString('script', $p[$side]);
                $this->assertStringNotContainsString('NaN', $p[$side]);
            }
        }
    }

    public function test_tema_tak_dikenal_tidak_punya_hiasan(): void
    {
        $this->assertNull(SeasonalNavbarArt::pieces('paskah'));
    }
}
