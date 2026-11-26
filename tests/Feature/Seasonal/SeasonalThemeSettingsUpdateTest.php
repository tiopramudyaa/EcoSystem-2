<?php

namespace Tests\Feature\Seasonal;

use App\Http\Controllers\SeasonalThemeSettingsController;
use App\Support\GlobalSeasonalTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Memanggil controller langsung (tanpa middleware auth/menu) supaya aturan
 * validasi update() teruji tanpa fixture login dan database penuh. Hanya jalur
 * penolakan yang diuji di sini; jalur sukses menulis AuditLog (tabel di luar
 * sqlite memori), sehingga penyimpanan lima field diuji lewat
 * GlobalSeasonalThemeTest.
 */
class SeasonalThemeSettingsUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Tanpa driver sqlite di lingkungan uji: semai cache "belum ada setting"
        // agar GlobalSeasonalTheme::all() tidak menyentuh database.
        Cache::put('app_config_' . GlobalSeasonalTheme::KEY, ['found' => false, 'value' => null], 300);
    }

    private function valid(array $over = []): array
    {
        return $over + [
            'theme' => 'natal', 'mode' => 'auto', 'sound' => null, 'volume' => 60,
            'schedule' => [['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-26']],
        ];
    }

    private function assertRejected(array $payload, string $errorKey): void
    {
        try {
            app(SeasonalThemeSettingsController::class)->update(Request::create('/x', 'POST', $payload));
            $this->fail('Validasi seharusnya menolak payload.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($errorKey, $e->errors());
        }

        // Tidak ada yang tersimpan.
        $this->assertSame('none', GlobalSeasonalTheme::all()['theme']);
        $this->assertSame([], GlobalSeasonalTheme::all()['schedule']);
    }

    public function test_tema_tak_dikenal_ditolak(): void
    {
        $this->assertRejected($this->valid(['theme' => 'paskah']), 'theme');
    }

    public function test_mode_tak_valid_ditolak(): void
    {
        $this->assertRejected($this->valid(['mode' => 'bogus']), 'mode');
    }

    public function test_jadwal_dengan_tema_none_atau_tak_dikenal_ditolak(): void
    {
        $row = ['start' => '2026-12-01', 'end' => '2026-12-02'];
        $this->assertRejected($this->valid(['schedule' => [['theme' => 'none'] + $row]]), 'schedule.0.theme');
        $this->assertRejected($this->valid(['schedule' => [['theme' => 'paskah'] + $row]]), 'schedule.0.theme');
    }

    public function test_tanggal_salah_format_atau_akhir_sebelum_awal_ditolak(): void
    {
        $this->assertRejected($this->valid(['schedule' => [['theme' => 'natal', 'start' => '01-12-2026', 'end' => '2026-12-02']]]), 'schedule.0.start');
        $this->assertRejected($this->valid(['schedule' => [['theme' => 'natal', 'start' => '2026-12-10', 'end' => '2026-12-02']]]), 'schedule.0.end');
    }

    public function test_jadwal_lebih_dari_20_ditolak(): void
    {
        $rows = array_fill(0, 21, ['theme' => 'natal', 'start' => '2026-12-01', 'end' => '2026-12-02']);
        $this->assertRejected($this->valid(['schedule' => $rows]), 'schedule');
    }

    public function test_tema_login_tak_dikenal_ditolak(): void
    {
        $this->assertRejected($this->valid(['login' => ['theme' => 'paskah', 'scope' => 'both']]), 'login.theme');
    }

    public function test_cakupan_login_tak_valid_ditolak(): void
    {
        $this->assertRejected($this->valid(['login' => ['theme' => 'inherit', 'scope' => 'dashboard']]), 'login.scope');
    }

    public function test_route_pengaturan_hanya_lewat_middleware_menu_admin(): void
    {
        foreach (['admin.seasonal-theme', 'admin.seasonal-theme.update'] as $name) {
            $middleware = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('menu:control-center.seasonal-theme', $middleware);
        }
    }
}
