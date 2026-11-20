<?php

namespace Tests\Unit\Seasonal;

use App\Support\GlobalSeasonalTheme;
use App\Support\SeasonalThemeResolver;
use App\View\Composers\SeasonalThemeComposer;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginSeasonalThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->store([]);
    }

    private function store(array $stored): void
    {
        GlobalSeasonalTheme::flushMemo();
        Cache::put('app_config_' . GlobalSeasonalTheme::KEY, [
            'found' => $stored !== [],
            'value' => $stored === [] ? null : json_encode($stored),
        ], 300);
    }

    private function withLogin(mixed $login, string $global = 'natal'): void
    {
        $this->store(['theme' => $global, 'mode' => 'manual', 'login' => $login]);
    }

    public function test_data_lama_tanpa_login_berperilaku_seperti_dulu(): void
    {
        $this->store(['theme' => 'natal', 'mode' => 'manual']);

        $this->assertSame(['theme' => 'inherit', 'scope' => 'both'], GlobalSeasonalTheme::login());
        $this->assertSame('natal', SeasonalThemeResolver::forAuthPage());
        $this->assertSame('natal', SeasonalThemeResolver::forLoading('default'));
        $this->assertSame('none', SeasonalThemeResolver::forLoading('none'));
        $this->assertSame('imlek', SeasonalThemeResolver::forLoading('imlek'));
    }

    public function test_sanitasi_login_menolak_nilai_rusak(): void
    {
        $this->withLogin(['theme' => 'paskah', 'scope' => 'bogus']);
        $this->assertSame(['theme' => 'inherit', 'scope' => 'both'], GlobalSeasonalTheme::login());

        $this->withLogin('bukan-array');
        $this->assertSame(['theme' => 'inherit', 'scope' => 'both'], GlobalSeasonalTheme::login());

        $this->withLogin(['theme' => ['x'], 'scope' => 'page']);
        $this->assertSame(['theme' => 'inherit', 'scope' => 'page'], GlobalSeasonalTheme::login());

        $this->withLogin(['theme' => 'none', 'scope' => 'loading']);
        $this->assertSame(['theme' => 'none', 'scope' => 'loading'], GlobalSeasonalTheme::login());

        $this->withLogin(['theme' => 'imlek']);
        $this->assertSame(['theme' => 'imlek', 'scope' => 'both'], GlobalSeasonalTheme::login());
    }

    public function test_scope_both_memakai_tema_login_di_halaman_dan_loading(): void
    {
        $this->withLogin(['theme' => 'imlek', 'scope' => 'both']);

        $this->assertSame('imlek', SeasonalThemeResolver::forAuthPage());
        $this->assertSame('imlek', SeasonalThemeResolver::forLoading('default'));
        $this->assertSame('imlek', SeasonalThemeResolver::forLoading('none'));
    }

    public function test_scope_page_hanya_halaman_login(): void
    {
        $this->withLogin(['theme' => 'imlek', 'scope' => 'page']);

        $this->assertSame('imlek', SeasonalThemeResolver::forAuthPage());
        $this->assertSame('natal', SeasonalThemeResolver::forLoading('default'));
        $this->assertSame('none', SeasonalThemeResolver::forLoading('none'));
    }

    public function test_scope_loading_hanya_loading_screen(): void
    {
        $this->withLogin(['theme' => 'imlek', 'scope' => 'loading']);

        $this->assertSame('natal', SeasonalThemeResolver::forAuthPage());
        $this->assertSame('imlek', SeasonalThemeResolver::forLoading('default'));
        $this->assertSame('imlek', SeasonalThemeResolver::forLoading('natal'));
    }

    public function test_login_none_mematikan_tema_pada_cakupannya(): void
    {
        $this->withLogin(['theme' => 'none', 'scope' => 'both']);

        $this->assertSame('none', SeasonalThemeResolver::forAuthPage());
        $this->assertSame('none', SeasonalThemeResolver::forLoading('default'));
    }

    public function test_inherit_dengan_scope_apa_pun_mengikuti_perilaku_lama(): void
    {
        foreach (['both', 'page', 'loading'] as $scope) {
            $this->withLogin(['theme' => 'inherit', 'scope' => $scope]);

            $this->assertSame('natal', SeasonalThemeResolver::forAuthPage());
            $this->assertSame('natal', SeasonalThemeResolver::forLoading('default'));
            $this->assertSame('imlek', SeasonalThemeResolver::forLoading('imlek'));
        }
    }

    public function test_composer_anonim_memakai_tema_login_dan_dashboard_tidak_terpengaruh(): void
    {
        $this->withLogin(['theme' => 'imlek', 'scope' => 'both']);

        // Anonim: tema login.
        $this->assertSame('imlek', SeasonalThemeComposer::build()['key']);

        // Pengguna masuk: preferensi user atau global, tidak pernah tema login.
        session(['user' => ['id' => 1], 'user_preferences.seasonal_theme' => 'default']);
        $this->assertSame('natal', SeasonalThemeComposer::build()['key']);
    }
}
