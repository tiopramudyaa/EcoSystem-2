<?php

namespace Tests\Unit\Seasonal;

use App\Http\Controllers\SettingsController;
use App\Support\SeasonalThemes;
use PHPUnit\Framework\TestCase;

class SeasonalThemesTest extends TestCase
{
    public function test_keys_memuat_semua_tema_termasuk_none(): void
    {
        $this->assertSame(
            ['none', 'natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'],
            SeasonalThemes::keys()
        );
    }

    public function test_user_choices_menambah_default_di_depan(): void
    {
        $choices = SeasonalThemes::userChoices();
        $this->assertSame('default', $choices[0]);
        $this->assertSame(SeasonalThemes::keys(), array_slice($choices, 1));
    }

    public function test_setiap_tema_bukan_none_punya_label_dan_palet_lengkap(): void
    {
        foreach (SeasonalThemes::keys() as $key) {
            $meta = SeasonalThemes::get($key);
            $this->assertNotNull($meta, $key);
            $this->assertNotEmpty($meta['label_key'], $key);

            if ($key === SeasonalThemes::NONE) {
                $this->assertNull($meta['palette']);
                continue;
            }

            foreach (['primary_rgb', 'primary_dark_rgb', 'accent', 'login_from', 'login_mid', 'login_to', 'button', 'loading_bottom'] as $name) {
                $this->assertArrayHasKey($name, $meta['palette'], "$key.$name");
                $this->assertNotSame('', $meta['palette'][$name], "$key.$name");
            }
        }
    }

    public function test_get_dan_is_valid_untuk_key_tak_dikenal(): void
    {
        $this->assertNull(SeasonalThemes::get('paskah'));
        $this->assertFalse(SeasonalThemes::isValid('paskah'));
        $this->assertTrue(SeasonalThemes::isValid('natal'));
    }

    public function test_whitelist_settings_controller_memuat_semua_key_katalog(): void
    {
        $allowed = SettingsController::allowedValues()['seasonal_theme'];
        $this->assertSame(SeasonalThemes::userChoices(), $allowed);
        $this->assertContains('default', $allowed);
        $this->assertContains('hut_ri', $allowed);
        $this->assertSame('default', SettingsController::DEFAULT_PREFERENCES['seasonal_theme']);
        // Field lain tetap ada.
        $this->assertSame(['en', 'id'], SettingsController::allowedValues()['language']);
    }
}
