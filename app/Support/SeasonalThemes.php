<?php

namespace App\Support;

/**
 * Katalog tema musiman: sumber kebenaran TUNGGAL untuk tema apa saja yang
 * tersedia, termasuk palet warna, dekorasi, teks loading screen, dan saran
 * jadwal. Murni tema yang bisa dirender (none, natal, ...); konsep "ikut
 * admin" ('default') ditangani App\Support\SeasonalThemeResolver.
 *
 * Dua jalur konfigurasi independen, keduanya di-resolve ke satu key katalog:
 *
 *   - Per-user: SettingsController (auth_users.preferences.seasonal_theme).
 *   - Global admin: App\Support\GlobalSeasonalTheme (app_configs), berlaku
 *     sebagai default semua user dan halaman login, bisa manual atau
 *     terjadwal. User boleh menimpa pilihan admin di preferensinya.
 *
 * Menambah tema baru cukup menambah satu entri CATALOG. Daftar yang diterima
 * SettingsController dan validasi admin dibangun dari keys(), jadi tidak ada
 * literal ganda yang perlu disinkronkan.
 *
 * Field entri:
 *   - label_key    kunci terjemahan nama tema (lang/*\/ui.php).
 *   - icon         kelas Font Awesome untuk kartu pilihan.
 *   - accent       warna chip pratinjau.
 *   - palette      nilai CSS variable --st-* (lihat partial seasonal-theme-vars).
 *                  null untuk 'none' (tidak ada override warna).
 *   - decoration   tag efek animasi yang dibaca partial seasonal-theme:
 *                  snow, confetti, stars, sparkle, lantern, flag, atau null.
 *   - ornaments    nama ornamen vektor (SVG symbol st-orn-NAMA) untuk dekorasi
 *                  header dan sidebar. Ikon utama tema: symbol st-icon-KEY.
 *   - scene        jenis adegan 3D loading screen (public/js/seasonal/scenes).
 *   - loading      kunci terjemahan judul dan subjudul loading screen.
 *   - window       saran jadwal [mm-dd awal, mm-dd akhir]; null bila tanggalnya
 *                  berpindah tiap tahun (Ramadan, Lebaran, Imlek) sehingga
 *                  admin yang mengisi tanggal tepatnya.
 */
final class SeasonalThemes
{
    public const NONE = 'none';
    public const NATAL = 'natal';
    public const TAHUN_BARU = 'tahun_baru';
    public const RAMADAN = 'ramadan';
    public const LEBARAN = 'lebaran';
    public const IMLEK = 'imlek';
    public const HUT_RI = 'hut_ri';

    private const CATALOG = [
        self::NONE => [
            'label_key' => 'ui.seasonal.themes.none',
            'icon' => 'fa-ban',
            'accent' => '#9ca3af',
            'palette' => null,
            'decoration' => null,
            'ornaments' => [],
            'scene' => null,
            'loading' => null,
            'window' => null,
        ],
        self::NATAL => [
            'label_key' => 'ui.seasonal.themes.natal',
            'icon' => 'fa-tree',
            'accent' => '#b91c1c',
            'palette' => [
                'primary_rgb' => '153 27 27',
                'primary_dark_rgb' => '127 29 29',
                'accent' => '#f2c14e',
                'login_from' => '#1a0000',
                'login_to' => '#b91c1c',
            ],
            'decoration' => 'snow',
            'ornaments' => ['snowflake', 'bauble', 'star', 'bell'],
            'scene' => 'sleigh',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.natal.title',
                'subtitle_key' => 'ui.seasonal.loading.natal.subtitle',
            ],
            'window' => ['12-01', '12-26'],
        ],
        self::TAHUN_BARU => [
            'label_key' => 'ui.seasonal.themes.tahun_baru',
            'icon' => 'fa-champagne-glasses',
            'accent' => '#a16207',
            'palette' => [
                'primary_rgb' => '133 100 22',
                'primary_dark_rgb' => '84 62 10',
                'accent' => '#f3dc8e',
                'login_from' => '#07070b',
                'login_to' => '#2b2b36',
            ],
            'decoration' => 'confetti',
            'ornaments' => ['firework', 'spark', 'star', 'glass'],
            'scene' => 'fireworks',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.tahun_baru.title',
                'subtitle_key' => 'ui.seasonal.loading.tahun_baru.subtitle',
            ],
            'window' => ['12-27', '01-03'],
        ],
        self::RAMADAN => [
            'label_key' => 'ui.seasonal.themes.ramadan',
            'icon' => 'fa-moon',
            'accent' => '#1e407c',
            'palette' => [
                'primary_rgb' => '30 64 124',
                'primary_dark_rgb' => '15 32 70',
                'accent' => '#f5c542',
                'login_from' => '#050a1c',
                'login_to' => '#1e3a8a',
            ],
            'decoration' => 'stars',
            'ornaments' => ['crescent', 'star', 'fanous', 'star'],
            'scene' => 'crescent',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.ramadan.title',
                'subtitle_key' => 'ui.seasonal.loading.ramadan.subtitle',
            ],
            'window' => null,
        ],
        self::LEBARAN => [
            'label_key' => 'ui.seasonal.themes.lebaran',
            'icon' => 'fa-mosque',
            'accent' => '#1b5e20',
            'palette' => [
                'primary_rgb' => '27 94 32',
                'primary_dark_rgb' => '14 61 20',
                'accent' => '#d4e157',
                'login_from' => '#06240d',
                'login_to' => '#2e7d32',
            ],
            'decoration' => 'sparkle',
            'ornaments' => ['ketupat', 'mosque', 'star8', 'ketupat'],
            'scene' => 'eid',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.lebaran.title',
                'subtitle_key' => 'ui.seasonal.loading.lebaran.subtitle',
            ],
            'window' => null,
        ],
        self::IMLEK => [
            'label_key' => 'ui.seasonal.themes.imlek',
            'icon' => 'fa-dragon',
            'accent' => '#b91c1c',
            'palette' => [
                'primary_rgb' => '185 28 28',
                'primary_dark_rgb' => '127 29 29',
                'accent' => '#facc15',
                'login_from' => '#450a0a',
                'login_to' => '#b91c1c',
            ],
            'decoration' => 'lantern',
            'ornaments' => ['lantern', 'coin', 'fu', 'cloud'],
            'scene' => 'lunar',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.imlek.title',
                'subtitle_key' => 'ui.seasonal.loading.imlek.subtitle',
            ],
            'window' => null,
        ],
        self::HUT_RI => [
            'label_key' => 'ui.seasonal.themes.hut_ri',
            'icon' => 'fa-flag',
            'accent' => '#dc2626',
            'palette' => [
                'primary_rgb' => '185 28 28',
                'primary_dark_rgb' => '153 27 27',
                'accent' => '#f8fafc',
                'login_from' => '#450a0a',
                'login_to' => '#dc2626',
            ],
            'decoration' => 'flag',
            'ornaments' => ['flag', 'bamboo', 'star', 'ribbon'],
            'scene' => 'flag',
            'loading' => [
                'title_key' => 'ui.seasonal.loading.hut_ri.title',
                'subtitle_key' => 'ui.seasonal.loading.hut_ri.subtitle',
            ],
            'window' => ['08-10', '08-20'],
        ],
    ];

    /** Semua key tema yang dikenal, termasuk 'none'. */
    public static function keys(): array
    {
        return array_keys(self::CATALOG);
    }

    /** Key tema yang bisa dipilih user: 'default' (ikut admin) + semua key katalog. */
    public static function userChoices(): array
    {
        return array_merge(['default'], self::keys());
    }

    /** Katalog lengkap, dipakai untuk render picker (Settings dan Control Center). */
    public static function catalog(): array
    {
        return self::CATALOG;
    }

    /** Metadata satu tema, atau null bila key tidak dikenal. */
    public static function get(string $key): ?array
    {
        return self::CATALOG[$key] ?? null;
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::CATALOG);
    }
}
