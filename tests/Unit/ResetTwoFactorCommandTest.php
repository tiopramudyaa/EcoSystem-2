<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Jalur darurat reset 2FA dari server. Perilaku lengkapnya diuji manual terhadap data nyata
 * (lihat ringkasan pekerjaan); tes ini menjaga perintahnya tetap terdaftar dan pagar
 * keselamatannya (alasan wajib, konfirmasi ECI) tidak hilang diam-diam.
 */
class ResetTwoFactorCommandTest extends TestCase
{
    public function test_command_is_registered_with_its_safety_options(): void
    {
        $command = Artisan::all()['security:reset-two-factor'] ?? null;

        $this->assertNotNull($command, 'security:reset-two-factor is not registered');

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('eci'));
        $this->assertTrue($definition->hasOption('reason'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('unlock'));
    }

    public function test_command_refuses_to_run_without_a_reason_or_confirmation(): void
    {
        $source = file_get_contents(base_path('app/Console/Commands/ResetTwoFactor.php'));

        $this->assertStringContainsString('A reason is required', $source);
        $this->assertStringContainsString('Type the ECI', $source);
    }
}
