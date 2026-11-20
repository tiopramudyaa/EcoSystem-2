<?php

namespace App\View\Composers;

use App\Support\GlobalSeasonalTheme;
use App\Support\SeasonalThemeResolver;
use App\Support\SeasonalThemes;
use Illuminate\View\View;
use Throwable;

/**
 * Membagikan $seasonalTheme ke dashboard dan halaman auth, jadi Blade tidak
 * menghitung tema efektif sendiri.
 *
 * Bentuk: ['key' => string, 'meta' => ?array, 'soundUrl' => ?string, 'soundVolume' => float]
 *   - key: user login (session 'user') -> SeasonalThemeResolver::effectiveFor(preferensi user);
 *          belum login -> GlobalSeasonalTheme::active().
 *   - meta: entri katalog SeasonalThemes::get(key), null bila key 'none'.
 *   - soundUrl: musik latar admin, null bila tema 'none' atau tidak ada musik.
 */
class SeasonalThemeComposer
{
    public function compose(View $view): void
    {
        $view->with('seasonalTheme', self::build());
    }

    public static function build(): array
    {
        $fallback = ['key' => SeasonalThemes::NONE, 'meta' => null, 'soundUrl' => null, 'soundVolume' => 0.5];

        try {
            $user = session('user');

            $key = (is_array($user) ? !empty($user['id']) : !empty($user))
                ? SeasonalThemeResolver::effectiveFor((string) session('user_preferences.seasonal_theme', 'default'))
                : SeasonalThemeResolver::forAuthPage();

            if (!SeasonalThemes::isValid($key) || $key === SeasonalThemes::NONE) {
                return array_merge($fallback, ['soundVolume' => GlobalSeasonalTheme::soundVolume()]);
            }

            return [
                'key' => $key,
                'meta' => SeasonalThemes::get($key),
                'soundUrl' => GlobalSeasonalTheme::soundUrl(),
                'soundVolume' => GlobalSeasonalTheme::soundVolume(),
            ];
        } catch (Throwable $e) {
            return $fallback;
        }
    }
}
