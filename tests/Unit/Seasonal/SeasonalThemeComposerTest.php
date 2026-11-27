<?php

namespace Tests\Unit\Seasonal;

use App\View\Composers\SeasonalThemeComposer;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeasonalThemeComposerTest extends TestCase
{
    public function test_tanpa_tabel_app_configs_jatuh_ke_none_tanpa_exception(): void
    {
        // Lingkungan uji tidak punya database: pembacaan AppConfig melempar dan harus ditelan.
        Cache::flush();

        $theme = SeasonalThemeComposer::build();

        $this->assertSame('none', $theme['key']);
        $this->assertNull($theme['meta']);
        $this->assertNull($theme['soundUrl']);
        $this->assertArrayHasKey('soundVolume', $theme);
    }

    public function test_user_login_dengan_preferensi_eksplisit_memakai_tema_itu(): void
    {
        session(['user' => ['id' => 1], 'user_preferences' => ['seasonal_theme' => 'imlek']]);

        $theme = SeasonalThemeComposer::build();

        $this->assertSame('imlek', $theme['key']);
        $this->assertSame('ui.seasonal.themes.imlek', $theme['meta']['label_key']);
    }

    public function test_preferensi_none_user_menghasilkan_meta_null(): void
    {
        session(['user' => ['id' => 1], 'user_preferences' => ['seasonal_theme' => 'none']]);

        $theme = SeasonalThemeComposer::build();

        $this->assertSame('none', $theme['key']);
        $this->assertNull($theme['meta']);
        $this->assertNull($theme['soundUrl']);
    }
}
