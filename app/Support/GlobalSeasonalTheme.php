<?php

namespace App\Support;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Setting tema musiman yang dikontrol admin, berlaku sebagai DEFAULT untuk
 * seluruh user (dashboard) dan halaman login (pre-auth, lewat AppConfig
 * karena belum ada session). Bukan dipaksakan - tiap user tetap bisa
 * menimpa untuk dirinya sendiri lewat preferensi pribadinya (lihat
 * SeasonalThemeResolver, yang menggabungkan nilai ini dengan preferensi
 * per-user).
 *
 * Ditulis oleh Control Center → Seasonal Theme (SeasonalThemeSettingsController).
 * Bila belum ada admin yang pernah menyimpan apa pun, active() jatuh ke 'none'
 * sehingga tidak ada perubahan visual yang muncul tiba-tiba untuk siapa pun.
 *
 * Dua mode aktivasi:
 *   - manual: tema yang dipilih admin ('theme') selalu aktif.
 *   - auto:   tema aktif mengikuti jadwal ('schedule', rentang tanggal penuh
 *             Y-m-d per tema); di luar semua rentang hasilnya 'none'. Bila dua
 *             rentang tumpang tindih, entri yang tertulis lebih dulu menang.
 *
 * Pola sama seperti App\Support\AiModelSettings: baca lewat AppConfig::getJson,
 * selalu disanitasi ulang saat dibaca (bukan hanya saat ditulis) supaya baris
 * app_configs yang diedit manual atau menyebut tema yang sudah dihapus tidak
 * bisa menjatuhkan dashboard/login siapa pun.
 */
final class GlobalSeasonalTheme
{
    public const KEY = 'seasonal_theme.global';

    /** Kunci memo di container, berlaku selama satu request. */
    private const MEMO = 'seasonal_theme.global.memo';

    /** File musik latar harus ada di public/sounds/ - validasi lihat sanitizeSound(). */
    private const SOUND_DIR = 'sounds';

    private const DEFAULTS = [
        'theme' => SeasonalThemes::NONE,
        // null = tidak ada musik latar. BUKAN preferensi per-user - ini murni
        // wewenang admin (lihat App\Http\Controllers\SeasonalThemeSettingsController),
        // dashboard.blade.php cuma boleh mute/unmute LOKAL demi aksesibilitas
        // (WCAG 1.4.2), tidak pernah menyimpan pilihan lagu.
        'sound' => null,
        // 0-100 (persen), disimpan bulat demi UI slider admin. BUKAN preferensi
        // per-user juga - berlaku sama untuk semua user, sama seperti 'sound'.
        // 50 = default kalau admin belum pernah mengatur, supaya begitu admin
        // nyalakan musik untuk pertama kali tidak langsung full volume.
        'volume' => 50,
        'mode' => self::MODE_MANUAL,
        // Daftar {theme, start, end}; start/end berformat Y-m-d, inklusif.
        'schedule' => [],
        // Tema khusus halaman login/auth dan loading screen setelah login.
        // Hanya admin yang bisa mengubah. Data lama tanpa 'login' = perilaku lama.
        'login' => ['theme' => self::LOGIN_INHERIT, 'scope' => self::SCOPE_BOTH],
    ];

    /** Tema login 'inherit' = ikut tema global aktif (default). */
    public const LOGIN_INHERIT = 'inherit';
    public const SCOPE_BOTH = 'both';
    public const SCOPE_PAGE = 'page';
    public const SCOPE_LOADING = 'loading';

    public const MODE_MANUAL = 'manual';
    public const MODE_AUTO = 'auto';

    private const MAX_SCHEDULE_ENTRIES = 20;

    /** Tema aktif yang dipilih admin, sudah divalidasi terhadap SeasonalThemes. */
    public static function active(?\DateTimeInterface $now = null): string
    {
        $all = self::all();

        if ($all['mode'] !== self::MODE_AUTO) {
            return $all['theme'];
        }

        $today = ($now ?? now())->format('Y-m-d');

        foreach ($all['schedule'] as $entry) {
            if ($today >= $entry['start'] && $today <= $entry['end']) {
                return $entry['theme'];
            }
        }

        return SeasonalThemes::NONE;
    }

    /**
     * URL musik latar yang dipasang admin, atau null kalau tidak ada.
     * Dipakai dashboard.blade.php (semua halaman setelah login) - BUKAN
     * halaman login (itu pakai #seasonalJingle terpisah, klip loading screen
     * singkat, konsepnya beda dari musik latar berkelanjutan ini).
     */
    public static function soundUrl(): ?string
    {
        $sound = self::all()['sound'];

        return $sound ? '/' . self::SOUND_DIR . '/' . $sound : null;
    }

    /** Volume musik latar sebagai pecahan 0.0-1.0, siap dipakai langsung `audio.volume = ...` di JS. */
    public static function soundVolume(): float
    {
        return self::all()['volume'] / 100;
    }

    /** @return array{theme: string, scope: string} */
    public static function login(): array
    {
        return self::all()['login'];
    }

    /** @return array{theme: string, sound: ?string, volume: int, mode: string, schedule: array<int, array{theme: string, start: string, end: string}>, login: array{theme: string, scope: string}} */
    public static function all(): array
    {
        // Memo per request: satu halaman dashboard memanggil ini 4 sampai 5 kali
        // (composer, musik latar, resolver) dan CACHE_STORE bisa berupa database,
        // jadi tanpa memo tiap panggilan = satu SELECT. Disimpan di container
        // (bukan properti static) supaya hilang di akhir request (FPM, Octane) dan
        // per test. Daemon queue:work atau schedule:work memakai satu container
        // seumur proses, jadi job yang membaca ini sebaiknya memanggil
        // flushMemo() lebih dulu (saat ini tidak ada job yang membacanya).
        // save() selalu membuangnya.
        $app = app();
        if ($app->bound(self::MEMO)) {
            return $app->make(self::MEMO);
        }

        $stored = [];

        try {
            $stored = AppConfig::getJson(self::KEY, []);
        } catch (Throwable $e) {
            // Tabel app_configs belum ada / DB bermasalah - jangan jatuhkan
            // dashboard/login siapa pun, cukup jatuh ke default 'none'.
            Log::warning('Global seasonal theme unreadable, falling back to default', [
                'error' => $e->getMessage(),
            ]);
        }

        $clean = self::sanitize(is_array($stored) ? $stored : []);
        $app->instance(self::MEMO, $clean);

        return $clean;
    }

    /** Buang memo request ini (dipakai save() dan test). */
    public static function flushMemo(): void
    {
        app()->forgetInstance(self::MEMO);
    }

    /**
     * @param array<string, mixed> $input
     *
     * PENTING: $input harus selalu membawa SEMUA field ('theme', 'sound',
     * 'volume', 'mode', 'schedule', 'login') bersamaan, bukan cuma yang berubah -
     * sanitize() menjatuhkan field yang hilang ke default, jadi
     * save(['theme'=>'natal']) SAJA akan diam-diam MENGHAPUS musik latar,
     * reset volume, dan mengosongkan jadwal yang sudah diset sebelumnya. Form
     * admin (admin/seasonal-theme-settings.blade.php) selalu submit semuanya.
     */
    public static function save(array $input): void
    {
        $clean = self::sanitize($input);

        self::flushMemo();

        AppConfig::setJson(self::KEY, $clean, 'Tema musiman + musik latar default untuk semua user + halaman login (Control Center → Seasonal Theme)');

        // Dibuang lagi setelah tulis: bila ada pembacaan di antara dua langkah di atas,
        // memo tidak boleh menyimpan nilai lama.
        self::flushMemo();
    }

    /** @param array<string, mixed> $input */
    private static function sanitize(array $input): array
    {
        $theme = (string) ($input['theme'] ?? self::DEFAULTS['theme']);

        return [
            'theme' => SeasonalThemes::isValid($theme) ? $theme : self::DEFAULTS['theme'],
            'sound' => self::sanitizeSound($input['sound'] ?? null),
            'volume' => self::sanitizeVolume($input['volume'] ?? null),
            'mode' => ($input['mode'] ?? null) === self::MODE_AUTO ? self::MODE_AUTO : self::MODE_MANUAL,
            'schedule' => self::sanitizeSchedule($input['schedule'] ?? null),
            'login' => self::sanitizeLogin($input['login'] ?? null),
        ];
    }

    /** @return array{theme: string, scope: string} */
    private static function sanitizeLogin(mixed $login): array
    {
        $login = is_array($login) ? $login : [];
        $theme = $login['theme'] ?? null;
        $scope = $login['scope'] ?? null;

        return [
            'theme' => is_string($theme) && ($theme === self::LOGIN_INHERIT || SeasonalThemes::isValid($theme))
                ? $theme
                : self::LOGIN_INHERIT,
            'scope' => in_array($scope, [self::SCOPE_PAGE, self::SCOPE_LOADING], true) ? $scope : self::SCOPE_BOTH,
        ];
    }

    /**
     * Buang entri rusak: tema tak dikenal atau 'none', tanggal bukan Y-m-d
     * valid, atau akhir sebelum awal. Urutan input dipertahankan karena
     * menentukan pemenang saat rentang tumpang tindih.
     *
     * @return array<int, array{theme: string, start: string, end: string}>
     */
    private static function sanitizeSchedule(mixed $schedule): array
    {
        if (!is_array($schedule)) {
            return [];
        }

        $clean = [];

        foreach ($schedule as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $theme = (string) ($entry['theme'] ?? '');
            $start = self::validDate($entry['start'] ?? null);
            $end = self::validDate($entry['end'] ?? null);

            if ($theme === SeasonalThemes::NONE || !SeasonalThemes::isValid($theme)
                || $start === null || $end === null || $end < $start) {
                continue;
            }

            $clean[] = ['theme' => $theme, 'start' => $start, 'end' => $end];

            if (count($clean) >= self::MAX_SCHEDULE_ENTRIES) {
                break;
            }
        }

        return $clean;
    }

    private static function validDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    /** Dijepit ke 0-100, jatuh ke default (50) kalau bukan angka valid. */
    private static function sanitizeVolume(mixed $volume): int
    {
        if (!is_numeric($volume)) {
            return self::DEFAULTS['volume'];
        }

        return (int) max(0, min(100, round((float) $volume)));
    }

    /**
     * Whitelist KETAT: cuma nama file polos (basename, bukan path) berekstensi
     * audio yang BENAR-BENAR ADA di public/sounds/ - admin memilih dari daftar
     * yang sudah ada (lihat SeasonalThemeSettingsController::index()), bukan
     * mengetik bebas, tapi tetap disanitasi ulang di sini (bukan cuma saat
     * simpan) karena baris app_configs bisa diedit manual/menyebut file yang
     * sudah dihapus dari disk.
     */
    private static function sanitizeSound(mixed $sound): ?string
    {
        if (!is_string($sound) || $sound === '') {
            return null;
        }

        $filename = basename($sound);

        if (!preg_match('/^[\w.\-]+\.(mp3|wav|ogg|aac)$/i', $filename)) {
            return null;
        }

        return file_exists(public_path(self::SOUND_DIR . '/' . $filename)) ? $filename : null;
    }
}
