<?php

namespace App\Support;

/**
 * Menggabungkan preferensi tema musiman per-user dengan setting global admin
 * menjadi SATU key tema yang benar-benar dirender - titik tunggal yang
 * dipanggil dashboard.blade.php. Halaman login (belum ada user) memakai
 * GlobalSeasonalTheme::active() langsung. Titik tunggal ini ada supaya logika
 * "default ikut admin, tapi user boleh menimpa" tidak ditulis ulang.
 *
 * Aturan:
 *   - preferensi user 'default' → ikut GlobalSeasonalTheme::active().
 *   - preferensi user 'none'    → dimatikan user sendiri, menang atas admin.
 *   - preferensi user tema lain (mis. 'natal') → dipilih user sendiri,
 *     menang atas admin (termasuk saat admin belum/tidak mengaktifkan apa pun).
 *   - nilai tak dikenal apa pun (data lama/rusak) → 'none', tidak pernah
 *     merender tema yang tidak valid.
 */
final class SeasonalThemeResolver
{
    public static function effectiveFor(string $userPreference): string
    {
        $theme = $userPreference === 'default'
            ? GlobalSeasonalTheme::active()
            : $userPreference;

        return SeasonalThemes::isValid($theme) ? $theme : SeasonalThemes::NONE;
    }

    /**
     * Tema halaman auth anonim (login, forgot-password, 2FA, change-password,
     * check-email). Memakai tema login admin bila cakupannya mencakup halaman
     * dan bukan 'inherit'; selain itu tema global aktif seperti biasa.
     */
    public static function forAuthPage(): string
    {
        $login = GlobalSeasonalTheme::login();

        if ($login['theme'] !== GlobalSeasonalTheme::LOGIN_INHERIT && $login['scope'] !== GlobalSeasonalTheme::SCOPE_LOADING) {
            return SeasonalThemes::isValid($login['theme']) ? $login['theme'] : SeasonalThemes::NONE;
        }

        $theme = GlobalSeasonalTheme::active();

        return SeasonalThemes::isValid($theme) ? $theme : SeasonalThemes::NONE;
    }

    /**
     * Tema loading screen setelah login. Tema login admin (bila cakupannya
     * mencakup loading dan bukan 'inherit') menang atas preferensi user;
     * selain itu tema efektif user seperti biasa.
     */
    public static function forLoading(string $userPreference): string
    {
        $login = GlobalSeasonalTheme::login();

        if ($login['theme'] !== GlobalSeasonalTheme::LOGIN_INHERIT && $login['scope'] !== GlobalSeasonalTheme::SCOPE_PAGE) {
            return SeasonalThemes::isValid($login['theme']) ? $login['theme'] : SeasonalThemes::NONE;
        }

        return self::effectiveFor($userPreference);
    }
}
