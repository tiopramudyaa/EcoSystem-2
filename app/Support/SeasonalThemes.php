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
            'accent' => '#1b6a40',
            'palette' => [
                'primary_rgb' => '20 69 47',
                'primary_dark_rgb' => '11 42 30',
                'accent' => '#d4af37',
                'login_from' => '#0b2a1e',
                'login_mid' => '#14452f',
                'login_to' => '#1f6b43',
                'button' => '#1b6a40',
                'loading_bottom' => '#14452f',
                'dark' => ['accent' => '#e0bc4a', 'login_to' => '#2a7f52'],
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
            'icon' => 'fa-clock',
            'accent' => '#2b2b36',
            'palette' => [
                'primary_rgb' => '34 34 44',
                'primary_dark_rgb' => '10 10 15',
                'accent' => '#f3dc8e',
                'login_from' => '#07070b',
                'login_mid' => '#16161f',
                'login_to' => '#2b2b36',
                'button' => '#2b2b36',
                'loading_bottom' => '#2b2b36',
            ],
            'decoration' => 'confetti',
            'ornaments' => ['firework', 'spark', 'star', 'clock'],
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
                'login_mid' => '#0f2046',
                'login_to' => '#1e3a8a',
                'button' => '#1e3a8a',
                'loading_bottom' => '#1e3a8a',
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
                'login_mid' => '#0e3d14',
                'login_to' => '#2e7d32',
                'button' => '#2e7d32',
                'loading_bottom' => '#2e7d32',
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
            'accent' => '#a83416',
            'palette' => [
                'primary_rgb' => '168 52 22',
                'primary_dark_rgb' => '122 31 16',
                'accent' => '#f5c542',
                'login_from' => '#3a0a05',
                'login_mid' => '#7a1f10',
                'login_to' => '#a83416',
                'button' => '#a83416',
                'loading_bottom' => '#7a1f10',
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
            'accent' => '#a80c24',
            'palette' => [
                'primary_rgb' => '168 12 36',
                'primary_dark_rgb' => '125 10 30',
                'accent' => '#ffffff',
                'login_from' => '#3d0610',
                'login_mid' => '#7d0a1e',
                'login_to' => '#a80c24',
                'button' => '#a80c24',
                'loading_bottom' => '#7d0a1e',
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
