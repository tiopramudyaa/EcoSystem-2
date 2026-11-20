<?php

namespace App\Support;

/**
 * Hiasan vektor navbar tema musiman, dua potong per tema: pojok kiri dan pojok kanan header.
 *
 * Tiap potong berukuran viewBox 190x40 dan menempel di tepi header. Garis tepi header berada di y=28:
 *   - y < 28 (di dalam header) hanya dipakai di pojok luar (x < 56 untuk kiri, x > 134 untuk kanan),
 *     tempat tombol menu dan avatar berada, sehingga judul dan subjudul halaman tidak tertutup.
 *   - y >= 28 (menjuntai di bawah header) dipakai untuk pendulum kecil di sepanjang tepi, jaraknya
 *     tidak beraturan dan maksimal 12 unit, bukan rumbai menerus.
 * Setiap tema punya motif pojok dan bandul yang berbeda. Gerak (kelas sn-*) diatur oleh CSS di
 * partial seasonal-navbar: ayun, kedip, mengambang, letup, kibar, napas. Tidak ada JavaScript.
 * Tanpa id elemen, supaya dua svg dalam satu halaman tidak bentrok.
 */
class SeasonalNavbarArt
{
    public const EDGE = 28;

    /**
     * l, r: hiasan utama dan bandul. fl, fr: lapisan ketiga yang jarang (viewBox 70x40) di dekat tiap pojok luar.
     *
     * @return array{l:string,r:string,fl:string,fr:string}|null
     */
    public static function pieces(string $key): ?array
    {
        $p = self::mainPieces($key);

        return $p === null ? null : $p + ['fl' => self::fx($key, false), 'fr' => self::fx($key, true)];
    }

    /** @return array{l:string,r:string}|null */
    private static function mainPieces(string $key): ?array
    {
        return match ($key) {
            'natal' => ['l' => self::natalLeft(), 'r' => self::natalRight()],
            'tahun_baru' => ['l' => self::nyLeft(), 'r' => self::nyRight()],
            'ramadan' => ['l' => self::ramadanLeft(), 'r' => self::ramadanRight()],
            'lebaran' => ['l' => self::eidLeft(), 'r' => self::eidRight()],
            'imlek' => ['l' => self::lunarLeft(), 'r' => self::lunarRight()],
            'hut_ri' => ['l' => self::flagLeft(), 'r' => self::flagRight()],
            default => null,
        };
    }

    // ---------------------------------------------------------------- helpers

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    /** Titik dan sudut singgung kurva bezier kubik. */
    private static function bez(array $p, float $t): array
    {
        $u = 1 - $t;
        $x = $u ** 3 * $p[0] + 3 * $u * $u * $t * $p[2] + 3 * $u * $t * $t * $p[4] + $t ** 3 * $p[6];
        $y = $u ** 3 * $p[1] + 3 * $u * $u * $t * $p[3] + 3 * $u * $t * $t * $p[5] + $t ** 3 * $p[7];
        $dx = 3 * $u * $u * ($p[2] - $p[0]) + 6 * $u * $t * ($p[4] - $p[2]) + 3 * $t * $t * ($p[6] - $p[4]);
        $dy = 3 * $u * $u * ($p[3] - $p[1]) + 6 * $u * $t * ($p[5] - $p[3]) + 3 * $t * $t * ($p[7] - $p[5]);

        return [$x, $y, atan2($dy, $dx)];
    }

    private static function curve(array $p, string $stroke, float $w): string
    {
        return '<path d="M' . self::n($p[0]) . ' ' . self::n($p[1]) . 'C' . self::n($p[2]) . ' ' . self::n($p[3]) . ' '
            . self::n($p[4]) . ' ' . self::n($p[5]) . ' ' . self::n($p[6]) . ' ' . self::n($p[7])
            . '" fill="none" stroke="' . $stroke . '" stroke-width="' . $w . '" stroke-linecap="round"/>';
    }

    /** Pendulum: konten digambar dengan benang dari tepi header (y=28) dan berayun dari titik gantung. */
    private static function sway(float $x, string $inner, float $dur, float $delay, float $amp = 3.5): string
    {
        return '<g class="sn-sway" style="transform-origin:' . self::n($x) . 'px 28px;--d:' . $dur . 's;--o:-' . $delay . 's;--a:' . $amp . 'deg">'
            . $inner . '</g>';
    }

    private static function thread(float $x, float $len, string $c = '#9a8a6a'): string
    {
        return '<path d="M' . self::n($x) . ' 28V' . self::n(28 + $len) . '" stroke="' . $c . '" stroke-width=".6"/>';
    }

    private static function star4(float $cx, float $cy, float $r, string $fill): string
    {
        $i = $r * .32;

        return '<path d="M' . self::n($cx) . ' ' . self::n($cy - $r) . 'L' . self::n($cx + $i) . ' ' . self::n($cy - $i) . 'L' . self::n($cx + $r) . ' ' . self::n($cy)
            . 'L' . self::n($cx + $i) . ' ' . self::n($cy + $i) . 'L' . self::n($cx) . ' ' . self::n($cy + $r) . 'L' . self::n($cx - $i) . ' ' . self::n($cy + $i)
            . 'L' . self::n($cx - $r) . ' ' . self::n($cy) . 'L' . self::n($cx - $i) . ' ' . self::n($cy - $i) . 'Z" fill="' . $fill . '"/>';
    }

    private static function twinkle(float $x, float $y, float $r, string $fill, float $dur, float $delay): string
    {
        return '<g class="sn-twinkle" style="transform-origin:' . self::n($x) . 'px ' . self::n($y) . 'px;--d:' . $dur . 's;--o:-' . $delay . 's">'
            . self::star4($x, $y, $r, $fill) . '</g>';
    }

    private static function bauble(float $x, float $len, float $r, string $fill, string $stroke): string
    {
        $cy = 28 + $len + $r * .55;

        return self::thread($x, $len)
            . '<rect x="' . self::n($x - 1.1) . '" y="' . self::n(28 + $len - 1.2) . '" width="2.2" height="1.6" rx=".4" fill="#a97b1f"/>'
            . '<circle cx="' . self::n($x) . '" cy="' . self::n($cy) . '" r="' . $r . '" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width=".5"/>'
            . '<path d="M' . self::n($x - $r * .6) . ' ' . self::n($cy - $r * .15) . 'Q' . self::n($x - $r * .5) . ' ' . self::n($cy - $r * .65) . ' ' . self::n($x - $r * .1) . ' ' . self::n($cy - $r * .7)
            . '" stroke="#fff" stroke-opacity=".7" stroke-width=".7" fill="none" stroke-linecap="round"/>';
    }

    private static function svg(string $inner): string
    {
        return $inner;
    }

    // ---------------------------------------------------------------- Natal

    private static function natalLeft(): string
    {
        $b = [0, 35, 18, 35, 34, 25, 52, 9];
        $o = self::curve($b, '#5a3a1f', 2.2);
        for ($t = .1; $t <= 1.001; $t += .1) {
            [$x, $y, $a] = self::bez($b, $t);
            foreach ([-1, 1] as $s) {
                $a2 = $a + $s * 0.95;
                $o .= '<path d="M' . self::n($x) . ' ' . self::n($y) . 'l' . self::n(cos($a2) * 8) . ' ' . self::n(sin($a2) * 8)
                    . '" stroke="' . ($s > 0 ? '#1f6b3a' : '#2f8a4c') . '" stroke-width="1.7" stroke-linecap="round"/>';
            }
        }
        foreach ([.42, .62, .86] as $t) {
            [$x, $y] = self::bez($b, $t);
            $o .= '<circle cx="' . self::n($x + 1) . '" cy="' . self::n($y + 2.2) . '" r="1.7" fill="#c8102e"/>';
        }
        // pita merah di pangkal ranting
        $o .= '<path d="M7 31C4 27 1 28 2 31C3 34 6 33 7 31ZM7 31C10 27 13 28 12 31C11 34 8 33 7 31Z" fill="#c8102e" stroke="#8e0b1f" stroke-width=".4"/><circle cx="7" cy="31" r="1.4" fill="#d9162f"/>';
        $o .= self::sway(80, self::bauble(80, 8, 3.1, '#c8102e', '#8e0b1f'), 4.2, .5, 4);
        $o .= self::sway(112, self::thread(112, 6) . '<g transform="translate(112 34)">' . self::star4(0, 0, 3.6, '#f2c14e') . '</g>', 3.4, 1.9, 6);
        $o .= '<g class="sn-twinkle" style="transform-origin:140px 33px;--d:3s;--o:-.8s"><path d="M140 28V39" stroke="#cfe6f5" stroke-width="1.2" stroke-linecap="round"/><path d="M138.6 28H141.4L140 39Z" fill="#e7f3fb" stroke="#9cc4dc" stroke-width=".4"/></g>';
        $o .= self::sway(164, self::bauble(164, 6, 2.5, '#f2c14e', '#c99a2a'), 5, 2.6, 5);

        return self::svg($o);
    }

    private static function natalRight(): string
    {
        $b = [190, 35, 172, 35, 156, 25, 138, 9];
        $o = self::curve($b, '#5a3a1f', 1.8);
        for ($t = .12; $t <= .95; $t += .17) {
            [$x, $y, $a] = self::bez($b, $t);
            foreach ([-1, 1] as $s) {
                $a2 = $a + $s * 1.15;
                $dx = cos($a2) * 7;
                $dy = sin($a2) * 7;
                $o .= '<path d="M' . self::n($x) . ' ' . self::n($y) . 'q' . self::n($dx * .5 - $dy * .25) . ' ' . self::n($dy * .5 + $dx * .25) . ' ' . self::n($dx) . ' ' . self::n($dy)
                    . 'q' . self::n(-$dy * .3) . ' ' . self::n($dx * .3) . ' ' . self::n(-$dx * .45) . ' ' . self::n(-$dy * .45) . 'Z" fill="#1f6b3a" stroke="#14502a" stroke-width=".4"/>';
            }
        }
        foreach ([[170, 31], [173.4, 29.6], [171.4, 27.4]] as [$x, $y]) {
            $o .= '<circle cx="' . $x . '" cy="' . $y . '" r="1.8" fill="#c8102e" stroke="#8e0b1f" stroke-width=".3"/>';
        }
        $bell = static fn (float $x, float $len, float $s): string => self::thread($x, $len)
            . '<path d="M' . self::n($x - 3 * $s) . ' ' . self::n(28 + $len + 5 * $s) . 'Q' . self::n($x - 3 * $s) . ' ' . self::n(28 + $len) . ' ' . self::n($x) . ' ' . self::n(28 + $len) . 'Q' . self::n($x + 3 * $s) . ' ' . self::n(28 + $len) . ' ' . self::n($x + 3 * $s) . ' ' . self::n(28 + $len + 5 * $s)
            . 'Z" fill="#f2c14e" stroke="#c99a2a" stroke-width=".5"/><circle cx="' . self::n($x) . '" cy="' . self::n(28 + $len + 5.8 * $s) . '" r="' . self::n(.9 * $s) . '" fill="#a97b1f"/>';
        $o .= self::sway(150, $bell(150, 3, 1.15), 3.6, .3, 7);
        $o .= self::sway(124, $bell(124, 2, .85), 4.4, 1.7, 6);
        // tongkat permen
        $o .= self::sway(92, '<path d="M92 28V35Q92 38.6 89.4 38.6Q87.2 38.6 87.2 36.4" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/><path d="M92 29.5V31M92 33V34.6M90.6 38L89 38.6M88 37.4L87.2 36.4" stroke="#c8102e" stroke-width="2.2" stroke-linecap="round" stroke-dasharray="1.2 1.6"/>', 4.8, 2.2, 4);
        $o .= self::twinkle(66, 34, 2.6, '#fff', 2.8, .4);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- Tahun Baru

    private static function nyLeft(): string
    {
        $o = self::curve([0, 36, 20, 36, 26, 13, 44, 13], '#e0b13a', 1.8);
        $o .= '<path d="M44 13C54 13 54 23 46 22C42 21.4 43 17 46.4 17.6" fill="none" stroke="#e0b13a" stroke-width="1.8" stroke-linecap="round"/>';
        $o .= self::curve([0, 38, 14, 38, 20, 24, 34, 24], '#cfd4de', 1.4);
        $o .= '<path d="M34 24C42 24 41 31 36 30.4" fill="none" stroke="#cfd4de" stroke-width="1.4" stroke-linecap="round"/>';
        $o .= self::twinkle(14, 11, 3.6, '#f6d36a', 2.6, .2) . self::twinkle(50, 28, 2.4, '#fff', 3.2, 1.4) . self::twinkle(24, 25, 1.9, '#f6d36a', 3.6, 2.2);
        $o .= self::twinkle(84, 33, 3, '#f6d36a', 3.4, .9);
        // pita konfeti bergelombang
        $o .= self::sway(118, '<path d="M118 28C121 30.5 115 32.5 118 35C121 37.5 115 39 118 40" fill="none" stroke="#e0405a" stroke-width="1.3" stroke-linecap="round"/>', 3.2, 1.1, 7);
        $o .= '<g class="sn-twinkle" style="transform-origin:150px 32px;--d:2.4s;--o:-1.6s"><circle cx="150" cy="32" r="1.4" fill="#f6d36a"/><circle cx="157" cy="35" r="1" fill="#cfd4de"/><circle cx="143" cy="35.5" r="1" fill="#e0405a"/></g>';

        return self::svg($o);
    }

    private static function burst(float $cx, float $cy, float $r, string $c1, string $c2, float $dur, float $delay): string
    {
        $o = '<g class="sn-pop" style="transform-origin:' . self::n($cx) . 'px ' . self::n($cy) . 'px;--d:' . $dur . 's;--o:-' . $delay . 's">';
        for ($i = 0; $i < 12; $i++) {
            $a = $i * M_PI / 6;
            $x1 = $cx + cos($a) * $r * .35;
            $y1 = $cy + sin($a) * $r * .35;
            $x2 = $cx + cos($a) * $r;
            $y2 = $cy + sin($a) * $r;
            $o .= '<path d="M' . self::n($x1) . ' ' . self::n($y1) . 'L' . self::n($x2) . ' ' . self::n($y2) . '" stroke="' . ($i % 2 ? $c1 : $c2) . '" stroke-width="1.1" stroke-linecap="round"/>'
                . '<circle cx="' . self::n($cx + cos($a) * $r * 1.12) . '" cy="' . self::n($cy + sin($a) * $r * 1.12) . '" r=".9" fill="' . ($i % 2 ? $c2 : $c1) . '"/>';
        }

        return $o . '</g>';
    }

    private static function nyRight(): string
    {
        $o = self::burst(166, 14, 11, '#f6d36a', '#fff', 4.2, 0);
        $o .= self::burst(142, 24, 6.5, '#e0405a', '#f6d36a', 3.6, 1.7);
        $o .= self::burst(184, 28, 5, '#cfd4de', '#f6d36a', 3.9, 2.6);
        $o .= self::twinkle(70, 33, 2.6, '#fff', 3, .6);
        $o .= self::sway(104, self::thread(104, 5) . '<g transform="translate(104 35)">' . self::star4(0, 0, 3.4, '#f6d36a') . '</g>', 4, 1.3, 6);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- Ramadan

    private static function lantern(float $x, float $len, float $s, string $fill, string $trim): string
    {
        $t = 28 + $len;
        $w = 3.4 * $s;
        $h = 8 * $s;

        return self::thread($x, $len, $trim)
            . '<path d="M' . self::n($x - $w * .55) . ' ' . self::n($t) . 'H' . self::n($x + $w * .55) . 'L' . self::n($x + $w * .75) . ' ' . self::n($t + 1.6 * $s) . 'L' . self::n($x - $w * .75) . ' ' . self::n($t + 1.6 * $s) . 'Z" fill="' . $trim . '"/>'
            . '<path d="M' . self::n($x - $w * .75) . ' ' . self::n($t + 1.6 * $s) . 'H' . self::n($x + $w * .75) . 'Q' . self::n($x + $w * 1.2) . ' ' . self::n($t + $h * .55) . ' ' . self::n($x + $w * .75) . ' ' . self::n($t + $h) . 'H' . self::n($x - $w * .75) . 'Q' . self::n($x - $w * 1.2) . ' ' . self::n($t + $h * .55) . ' ' . self::n($x - $w * .75) . ' ' . self::n($t + 1.6 * $s) . 'Z" fill="' . $fill . '" stroke="' . $trim . '" stroke-width=".5"/>'
            . '<path d="M' . self::n($x) . ' ' . self::n($t + 1.8 * $s) . 'V' . self::n($t + $h) . 'M' . self::n($x - $w * .5) . ' ' . self::n($t + 2 * $s) . 'Q' . self::n($x - $w * .9) . ' ' . self::n($t + $h * .55) . ' ' . self::n($x - $w * .5) . ' ' . self::n($t + $h - .4) . 'M' . self::n($x + $w * .5) . ' ' . self::n($t + 2 * $s) . 'Q' . self::n($x + $w * .9) . ' ' . self::n($t + $h * .55) . ' ' . self::n($x + $w * .5) . ' ' . self::n($t + $h - .4) . '" stroke="' . $trim . '" stroke-width=".5" fill="none" opacity=".8"/>'
            . '<rect x="' . self::n($x - $w * .8) . '" y="' . self::n($t + $h) . '" width="' . self::n($w * 1.6) . '" height="1.3" rx=".5" fill="' . $trim . '"/>';
    }

    private static function ramadanLeft(): string
    {
        $o = '<g class="sn-breathe" style="transform-origin:22px 15px;--d:5s"><circle cx="22" cy="15" r="13" fill="#f5c542" opacity=".14"/></g>';
        $o .= '<path d="M27 5.6A10.4 10.4 0 1 0 27 24.4A8.2 8.2 0 1 1 27 5.6Z" fill="#f5c542" stroke="#d9a21c" stroke-width=".5"/>';
        $o .= self::twinkle(38, 10, 3.2, '#fff4c2', 3.2, .5) . self::twinkle(46, 22, 2, '#f5c542', 2.8, 1.9) . self::twinkle(8, 6, 1.8, '#f5c542', 3.6, 2.4);
        $o .= self::sway(88, self::lantern(88, 2, 1, '#e8b93a', '#a97b1f'), 5.2, .8, 3.5);
        $o .= self::sway(122, self::thread(122, 5, '#c9a02a') . '<g transform="translate(122 34)">' . self::star4(0, 0, 3.8, '#f5c542') . '</g>', 3.8, 2.1, 6);
        $o .= self::sway(152, self::lantern(152, 1, .78, '#dcaa34', '#8f6a14'), 4.4, 1.4, 4.5);

        return self::svg($o);
    }

    private static function ramadanRight(): string
    {
        // siluet kubah dan menara di pojok kanan, di atas garis tepi
        $o = '<path d="M150 28V22Q150 14 160 14Q170 14 170 22V28Z" fill="#d9a21c" opacity=".9"/>'
            . '<path d="M160 14V10M158.6 10H161.4" stroke="#d9a21c" stroke-width="1.1" stroke-linecap="round"/>'
            . '<path d="M178 28V10L180 7L182 10V28Z" fill="#c8921a" opacity=".92"/><path d="M176.6 17H183.4" stroke="#f5c542" stroke-width=".9"/>'
            . '<path d="M180 7V3.6" stroke="#f5c542" stroke-width=".8"/><circle cx="180" cy="2.6" r="1.3" fill="#f5c542"/>'
            . '<path d="M154 28V24.6Q154 22 156 22Q158 22 158 24.6V28ZM162 28V24.6Q162 22 164 22Q166 22 166 24.6V28Z" fill="#6b4a0c" opacity=".55"/>';
        $o .= self::twinkle(140, 14, 2.4, '#fff4c2', 2.9, .3);
        $o .= self::sway(98, self::thread(98, 4, '#c9a02a') . '<g transform="translate(98 33)">' . self::star4(0, 0, 3, '#f5c542') . '</g>', 4.6, .6, 5);
        $o .= self::sway(70, self::thread(70, 9, '#c9a02a') . '<g transform="translate(70 38.6)">' . self::star4(0, 0, 3.4, '#f5c542') . '</g>', 3.4, 1.8, 6.5);
        $o .= self::sway(124, self::thread(124, 2, '#c9a02a') . '<path d="M124 30A4.4 4.4 0 1 0 124 38.4A3.4 3.4 0 1 1 124 30Z" fill="#f5c542"/>', 5.4, 2.6, 4);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- Lebaran

    private static function ketupat(float $x, float $len, float $s): string
    {
        $t = 28 + $len;
        $w = 3.6 * $s;
        $h = 4.4 * $s;
        $cy = $t + $h;

        return self::thread($x, $len, '#6b8e23')
            . '<path d="M' . self::n($x) . ' ' . self::n($t) . 'L' . self::n($x + $w) . ' ' . self::n($cy) . 'L' . self::n($x) . ' ' . self::n($cy + $h) . 'L' . self::n($x - $w) . ' ' . self::n($cy) . 'Z" fill="#9ccc3c" stroke="#4d7a16" stroke-width=".6"/>'
            . '<path d="M' . self::n($x - $w * .5) . ' ' . self::n($t + $h * .5) . 'L' . self::n($x + $w * .5) . ' ' . self::n($cy + $h * .5) . 'M' . self::n($x + $w * .5) . ' ' . self::n($t + $h * .5) . 'L' . self::n($x - $w * .5) . ' ' . self::n($cy + $h * .5) . 'M' . self::n($x - $w) . ' ' . self::n($cy) . 'H' . self::n($x + $w) . 'M' . self::n($x) . ' ' . self::n($t) . 'V' . self::n($cy + $h) . '" stroke="#4d7a16" stroke-width=".5" fill="none"/>'
            . '<path d="M' . self::n($x - .8) . ' ' . self::n($cy + $h) . 'L' . self::n($x - 1.6) . ' ' . self::n($cy + $h + 1.4) . 'M' . self::n($x + .8) . ' ' . self::n($cy + $h) . 'L' . self::n($x + 1.6) . ' ' . self::n($cy + $h + 1.4) . '" stroke="#d4e157" stroke-width=".7" stroke-linecap="round"/>';
    }

    private static function leafFan(float $bx, float $dir): string
    {
        // tiga helai janur melengkung dari pojok; dir +1 ke kanan, -1 ke kiri
        $o = '';
        foreach ([[0, 36, 22, 34, 34, 22, 40, 6, '#7fb02e'], [0, 38, 16, 38, 34, 32, 50, 22, '#aed581'], [2, 34, 8, 26, 14, 16, 12, 2, '#5f8f20']] as [$a, $b, $c, $d, $e, $f, $g, $h, $col]) {
            $x = static fn (float $v): float => $dir > 0 ? $bx + $v : $bx - $v;
            $o .= '<path d="M' . self::n($x($a)) . ' ' . self::n($b) . 'C' . self::n($x($c)) . ' ' . self::n($d) . ' ' . self::n($x($e)) . ' ' . self::n($f) . ' ' . self::n($x($g)) . ' ' . self::n($h)
                . 'C' . self::n($x($e + 4)) . ' ' . self::n($f + 8) . ' ' . self::n($x($c + 4)) . ' ' . self::n($d + 5) . ' ' . self::n($x($a)) . ' ' . self::n($b) . 'Z" fill="' . $col . '" stroke="#3f6a10" stroke-width=".5"/>'
                . '<path d="M' . self::n($x($a)) . ' ' . self::n($b) . 'C' . self::n($x($c)) . ' ' . self::n($d) . ' ' . self::n($x($e)) . ' ' . self::n($f) . ' ' . self::n($x($g)) . ' ' . self::n($h) . '" fill="none" stroke="#e6f2b8" stroke-width=".5" opacity=".8"/>';
        }

        return $o;
    }

    private static function eidLeft(): string
    {
        $o = self::leafFan(0, 1);
        $o .= self::twinkle(46, 8, 2.6, '#d4e157', 3.2, .6);
        $o .= self::sway(82, self::ketupat(82, 2, 1), 4.6, .6, 4.5);
        $o .= self::sway(114, self::thread(114, 4, '#9aa63a') . '<g transform="translate(114 33)">' . self::star4(0, 0, 3, '#d4e157') . '</g>', 3.6, 1.9, 6);
        $o .= self::sway(144, self::ketupat(144, 1, .72), 5.4, 2.4, 5);

        return self::svg($o);
    }

    private static function eidRight(): string
    {
        $o = self::leafFan(190, -1);
        $o .= '<path d="M137 10A5.6 5.6 0 1 0 137 21A4.4 4.4 0 1 1 137 10Z" fill="#d4e157" opacity=".92"/>' . self::twinkle(145, 8, 2.2, '#fff', 3, 1.2);
        $o .= self::sway(104, self::ketupat(104, 3, .85), 4.2, 1.5, 5);
        $o .= self::twinkle(70, 33, 2.8, '#d4e157', 2.8, .5);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- Imlek

    private static function plumBranch(array $b, array $ts): string
    {
        $o = self::curve($b, '#5b3a22', 2);
        foreach ($ts as $i => $t) {
            [$x, $y] = self::bez($b, $t);
            $y -= 2.6;
            $r = 3.2 - ($i % 2) * .5;
            $o .= '<g class="sn-twinkle" style="transform-origin:' . self::n($x) . 'px ' . self::n($y) . 'px;--d:' . (3.4 + $i * .7) . 's;--o:-' . ($i * 1.1) . 's">';
            for ($k = 0; $k < 5; $k++) {
                $a = $k * 2 * M_PI / 5 - M_PI / 2;
                $o .= '<circle cx="' . self::n($x + cos($a) * $r * .75) . '" cy="' . self::n($y + sin($a) * $r * .75) . '" r="' . self::n($r * .62) . '" fill="' . ($i % 2 ? '#f9d3dc' : '#f4a6b8') . '" stroke="#d96a86" stroke-width=".3"/>';
            }
            $o .= '<circle cx="' . self::n($x) . '" cy="' . self::n($y) . '" r="1" fill="#f6d36a"/></g>';
        }

        return $o;
    }

    private static function coin(float $x, float $len): string
    {
        $cy = 28 + $len + 3;

        return self::thread($x, $len, '#c99a2a') . '<circle cx="' . self::n($x) . '" cy="' . self::n($cy) . '" r="3.2" fill="#f2c14e" stroke="#b98a1e" stroke-width=".5"/><rect x="' . self::n($x - 1) . '" y="' . self::n($cy - 1) . '" width="2" height="2" fill="#a07014"/>';
    }

    private static function lunarLeft(): string
    {
        // lampion besar menggantung dari atas pojok, ayunan lambat
        $lan = '<path d="M16 0V4" stroke="#caa02a" stroke-width=".8"/><rect x="12.4" y="3.6" width="7.2" height="2.4" rx=".8" fill="#e8b93a" stroke="#b98a1e" stroke-width=".4"/>'
            . '<ellipse cx="16" cy="14.6" rx="8.4" ry="8" fill="#d4202a" stroke="#8f1018" stroke-width=".6"/>'
            . '<path d="M11.4 7Q8 14.6 11.4 22M20.6 7Q24 14.6 20.6 22M16 6.6V22.6" stroke="#9b111e" stroke-width=".7" fill="none"/>'
            . '<path d="M9.6 9.6Q8.6 12.4 9.2 14.6" stroke="#f26a6f" stroke-width="1" fill="none" stroke-linecap="round"/>'
            . '<rect x="12.4" y="22" width="7.2" height="2.2" rx=".8" fill="#e8b93a" stroke="#b98a1e" stroke-width=".4"/>'
            . '<path d="M14 24.2V31M16 24.2V32.4M18 24.2V31" stroke="#e8b93a" stroke-width="1.1" stroke-linecap="round"/><circle cx="16" cy="24.4" r="1.1" fill="#f6d36a"/>';
        $o = '<g class="sn-sway" style="transform-origin:16px 0;--d:5.6s;--o:-1s;--a:3deg">' . $lan . '</g>';
        $o .= self::plumBranch([22, 37, 34, 37, 46, 30, 56, 20], [.35, .62, .9]);
        $o .= self::sway(88, self::coin(88, 6), 4.4, .9, 5);
        $o .= self::sway(120, self::thread(120, 4, '#c99a2a') . '<circle cx="120" cy="33" r="1.4" fill="#d4202a"/><path d="M118.4 34L117.6 39M120 34.4V39.6M121.6 34L122.4 39" stroke="#d4202a" stroke-width=".9" stroke-linecap="round"/>', 3.8, 2.2, 6.5);
        $o .= self::sway(150, self::coin(150, 3), 5.2, 1.4, 4);

        return self::svg($o);
    }

    private static function lunarRight(): string
    {
        $o = self::plumBranch([190, 37, 174, 37, 156, 29, 138, 14], [.3, .55, .8, .98]);
        $o .= '<path d="M184 12L185.4 15.6L189 16L186.2 18.4L187 22L184 20L181 22L181.8 18.4L179 16L182.6 15.6Z" fill="#f2c14e" opacity=".9"/>';
        $o .= self::sway(100, self::thread(100, 3, '#caa02a') . '<ellipse cx="100" cy="34.4" rx="3.4" ry="3.2" fill="#d4202a" stroke="#8f1018" stroke-width=".4"/><path d="M99 38V40M101 38V40" stroke="#e8b93a" stroke-width=".8"/>', 4.8, .4, 6);
        $o .= self::sway(72, self::coin(72, 5), 3.9, 1.7, 5.5);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- HUT RI

    private static function flagPole(float $x0, float $y0, float $x1, float $y1, float $w, float $h, int $dir, float $dur, float $delay): string
    {
        $o = '<path d="M' . self::n($x0) . ' ' . self::n($y0) . 'L' . self::n($x1) . ' ' . self::n($y1) . '" stroke="#b8a37a" stroke-width="1.2" stroke-linecap="round"/><circle cx="' . self::n($x1) . '" cy="' . self::n($y1 - 1) . '" r="1.5" fill="#f2c14e"/>';
        $fx = $dir > 0 ? $x1 + .6 : $x1 - .6 - $w;
        $fy = $y1 + 1.4;
        $o .= '<g class="sn-wave" style="transform-origin:' . self::n($x1) . 'px ' . self::n($fy) . 'px;--d:' . $dur . 's;--o:-' . $delay . 's">'
            . '<path d="M' . self::n($fx) . ' ' . self::n($fy) . 'H' . self::n($fx + $w) . 'V' . self::n($fy + $h / 2) . 'H' . self::n($fx) . 'Z" fill="#d6202c"/>'
            . '<path d="M' . self::n($fx) . ' ' . self::n($fy + $h / 2) . 'H' . self::n($fx + $w) . 'V' . self::n($fy + $h) . 'H' . self::n($fx) . 'Z" fill="#fff" stroke="#d9d9d9" stroke-width=".3"/></g>';

        return $o;
    }

    private static function pennants(float $x0, float $x1, int $count): string
    {
        $o = '';
        $path = 'M' . self::n($x0) . ' 28Q' . self::n(($x0 + $x1) / 2) . ' 33.6 ' . self::n($x1) . ' 28';
        $o .= '<path d="' . $path . '" fill="none" stroke="#a89878" stroke-width=".7"/>';
        for ($i = 0; $i < $count; $i++) {
            $t = ($i + .5) / $count;
            $x = (1 - $t) ** 2 * $x0 + 2 * (1 - $t) * $t * (($x0 + $x1) / 2) + $t * $t * $x1;
            $y = (1 - $t) ** 2 * 28 + 2 * (1 - $t) * $t * 33.6 + $t * $t * 28;
            $o .= self::sway($x, '<path d="M' . self::n($x - 3.2) . ' ' . self::n($y) . 'L' . self::n($x + 3.2) . ' ' . self::n($y) . 'L' . self::n($x) . ' ' . self::n($y + 7.4) . 'Z" fill="' . ($i % 2 ? '#fff' : '#d6202c') . '" stroke="' . ($i % 2 ? '#d0d0d0' : '#a31019') . '" stroke-width=".4"/>', 3.4 + $i * .5, $i * 1.3, 5);
        }

        return $o;
    }

    private static function flagLeft(): string
    {
        $o = self::flagPole(8, 39, 30, 8, 17, 10, 1, 3.2, .4);
        $o .= self::flagPole(14, 39, 8, 5, 14, 8.4, 1, 3.8, 1.6);
        $o .= self::pennants(70, 124, 4);
        $o .= self::sway(152, self::thread(152, 5, '#c9a02a') . '<g transform="translate(152 33.6)">' . self::star4(0, 0, 3.2, '#f2c14e') . '</g>', 4.2, 1.1, 6);

        return self::svg($o);
    }

    private static function flagRight(): string
    {
        $o = self::flagPole(184, 39, 170, 12, 13, 8, -1, 3.6, 1);
        $o .= self::flagPole(178, 39, 184, 4, 12, 7.6, -1, 3, 2.2);
        $o .= self::pennants(84, 122, 3);
        $o .= self::twinkle(60, 33, 2.4, '#f2c14e', 3.4, .8);

        return self::svg($o);
    }

    // ---------------------------------------------------------------- Lapisan ketiga (fx) dan tepi

    /** Gerak untuk satu elemen fx: kelas gerak, titik pusat, durasi, dan jeda negatif masing-masing. */
    private static function mv(string $cls, float $x, float $y, float $dur, float $delay, string $inner): string
    {
        return '<g class="' . $cls . '" style="transform-origin:' . self::n($x) . 'px ' . self::n($y) . 'px;--d:' . self::n($dur) . 's;--o:-' . self::n($delay) . 's">' . $inner . '</g>';
    }

    private static function flake(float $x, float $y, float $r, string $c): string
    {
        $d = '';
        for ($k = 0; $k < 3; $k++) {
            $a = $k * M_PI / 3;
            $d .= 'M' . self::n($x - cos($a) * $r) . ' ' . self::n($y - sin($a) * $r) . 'L' . self::n($x + cos($a) * $r) . ' ' . self::n($y + sin($a) * $r);
        }

        return '<path d="' . $d . '" stroke="' . $c . '" stroke-width=".7" stroke-linecap="round" fill="none"/><circle cx="' . self::n($x) . '" cy="' . self::n($y) . '" r=".7" fill="#fff"/>';
    }

    private static function crystal(float $x, float $y, float $h, string $fill, string $stroke): string
    {
        return '<path d="M' . self::n($x) . ' ' . self::n($y - $h) . 'L' . self::n($x + $h * .38) . ' ' . self::n($y) . 'L' . self::n($x) . ' ' . self::n($y + $h) . 'L' . self::n($x - $h * .38) . ' ' . self::n($y) . 'Z" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width=".4"/>';
    }

    private static function star5(float $cx, float $cy, float $r, string $fill, string $stroke = 'none'): string
    {
        $d = '';
        for ($i = 0; $i < 10; $i++) {
            $a = $i * M_PI / 5 - M_PI / 2;
            $rr = $i % 2 ? $r * .42 : $r;
            $d .= ($i ? 'L' : 'M') . self::n($cx + cos($a) * $rr) . ' ' . self::n($cy + sin($a) * $rr);
        }

        return '<path d="' . $d . 'Z" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width=".4"/>';
    }

    private static function petal(float $x, float $y, float $rot, string $fill): string
    {
        return '<path transform="translate(' . self::n($x) . ' ' . self::n($y) . ') rotate(' . self::n($rot) . ')" d="M0-2.6C2 -2.6 2.6 .2 0 2.6C-2.6 .2 -2 -2.6 0-2.6Z" fill="' . $fill . '" stroke="#d96a86" stroke-width=".3"/>';
    }

    /** Lapisan ketiga: elemen kecil dan jarang di pojok luar, gerak sendiri-sendiri. Tanpa id. */
    private static function fx(string $key, bool $right): string
    {
        $o = '';
        switch ($key) {
            case 'natal':
                $f = $right
                    ? [[60, 9, 3, 5.2, 0], [42, 22, 2.2, 6.6, 2.4]]
                    : [[10, 7, 3.4, 5.8, 0], [31, 19, 2.4, 6.4, 2.1]];
                foreach ($f as [$x, $y, $r, $d, $dl]) {
                    $o .= self::mv('sn-drift', $x, $y, $d, $dl, self::flake($x, $y, $r, '#8fb9d6'));
                }
                $c = $right ? [[58, 33, 4.2, 3.4, .5], [28, 12, 3.4, 4.1, 1.6]] : [[48, 10, 4.6, 3.1, .7], [20, 33, 4, 3.8, 1.5]];
                foreach ($c as [$x, $y, $h, $d, $dl]) {
                    $o .= self::mv('sn-twinkle', $x, $y, $d, $dl, self::crystal($x, $y, $h, '#e7f3fb', '#9cc4dc'));
                }
                break;
            case 'tahun_baru':
                // jejak kilau menurun diagonal dari pojok luar
                for ($i = 0; $i < 5; $i++) {
                    $x = $right ? 64 - $i * 10.5 : 6 + $i * 11;
                    $y = 4 + $i * 4.8;
                    $o .= self::mv('sn-twinkle', $x, $y, 2.6 + $i * .35, .3 * $i + ($right ? .5 : 0), self::star4($x, $y, 3 - $i * .45, $i % 2 ? '#fff' : '#f6d36a'));
                }
                break;
            case 'ramadan':
                $s = $right ? [[62, 6, 2.6], [46, 12, 1.7], [56, 23, 2.1], [34, 5, 1.4]] : [[8, 10, 2.6], [27, 5, 1.8], [44, 17, 2.2], [14, 25, 1.5]];
                foreach ($s as $i => [$x, $y, $r]) {
                    $o .= self::mv('sn-twinkle', $x, $y, 2.7 + $i * .45, $i * 1.1, self::star4($x, $y, $r, $i % 2 ? '#fff4c2' : '#f5c542'));
                }
                break;
            case 'lebaran':
                $s = $right ? [[60, 8, 2.8, '#d4e157'], [42, 4, 1.7, '#fff'], [52, 21, 2.2, '#9ccc3c']] : [[10, 9, 2.8, '#d4e157'], [32, 5, 1.7, '#fff'], [50, 20, 2.2, '#9ccc3c']];
                foreach ($s as $i => [$x, $y, $r, $c]) {
                    $o .= self::mv('sn-twinkle', $x, $y, 3 + $i * .5, $i * 1.3, self::star4($x, $y, $r, $c));
                }
                $dx = $right ? 28 : 40;
                $o .= self::mv('sn-twinkle', $dx, 12, 2.4, .9, '<circle cx="' . $dx . '" cy="12" r="1" fill="#d4e157"/>');
                break;
            case 'imlek':
                $p = $right ? [[56, 5, 20], [38, 14, -35], [18, 8, 60]] : [[14, 4, -20], [34, 12, 40], [52, 6, -60]];
                foreach ($p as $i => [$x, $y, $rot]) {
                    $o .= self::mv('sn-fall', $x, $y, 5.2 + $i * 1.1, $i * 1.9, self::petal($x, $y, $rot, $i % 2 ? '#f9d3dc' : '#f4a6b8'));
                }
                break;
            case 'hut_ri':
                $s = $right ? [[62, 7, 3, '#d6202c'], [46, 13, 2.2, '#fff'], [56, 23, 2.4, '#f2c14e']] : [[9, 9, 3, '#d6202c'], [28, 5, 2.2, '#fff'], [47, 18, 2.4, '#f2c14e']];
                foreach ($s as $i => [$x, $y, $r, $c]) {
                    $o .= self::mv('sn-twinkle', $x, $y, 3 + $i * .5, $i * 1.2, self::star5($x, $y, $r, $c, $c === '#fff' ? '#d6202c' : 'none'));
                }
                break;
            default:
                return '';
        }

        return $o;
    }

    /**
     * Ubin SVG (data URI) rumbai es halus untuk natal: kristal tidak beraturan setinggi maksimal 6 unit,
     * dipakai CSS sebagai background berulang di bawah tepi header. Deterministik, tanpa acak sungguhan.
     */
    public static function frostTile(): string
    {
        $w = 480;
        $d = '';
        $x = 3.0;
        $i = 0;
        while ($x < $w - 2) {
            $h = 1.6 + fmod(abs(sin($i * 12.9898 + 1.7)) * 43758.5453, 1) * 4.2;
            $half = .5 + fmod(abs(sin($i * 78.233 + .3)) * 9631.7, 1) * .9;
            $d .= 'M' . self::n($x - $half) . ' 0L' . self::n($x) . ' ' . self::n($h) . 'L' . self::n($x + $half) . ' 0Z';
            if ($h > 4) {
                $d .= 'M' . self::n($x) . ' ' . self::n($h * .5) . 'l1.3 -.7M' . self::n($x) . ' ' . self::n($h * .5) . 'l-1.3 -.7';
            }
            $x += 5 + fmod(abs(sin($i * 39.346 + 2.1)) * 24634.6, 1) * 15;
            $i++;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' 6" width="' . $w . '" height="6"><path d="M0 .3H' . $w . '" stroke="#cfe6f5" stroke-width=".6"/><path d="' . $d . '" fill="#cfe6f5" stroke="#a9cfe6" stroke-width=".3"/></svg>';

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }

    /** Ubin bintang kecil di sepanjang garis tepi ramadan (data URI). */
    public static function edgeStarsTile(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 6" width="96" height="6">'
            . self::star4(10, 3, 2.6, '#fff4c2') . self::star4(44, 3, 1.8, '#f5c542') . self::star4(76, 3, 2.3, '#fff4c2') . '</svg>';

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }
}
