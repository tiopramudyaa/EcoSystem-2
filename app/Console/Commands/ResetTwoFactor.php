<?php

namespace App\Console\Commands;

use App\Services\TwoFactorAuthService;
use App\Services\TwoFactorResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Jalur darurat: mematikan 2FA satu karyawan dari server, tanpa login ke aplikasi.
 *
 * Untuk saat tombol "Reset 2FA" tidak bisa dipakai, terutama ketika SATU-SATUNYA EC
 * Administrator kehilangan ponsel dan kode pemulihannya (tidak ada admin lain yang bisa
 * mereset). Batas kepercayaannya adalah akses ke server: siapa pun yang bisa menjalankan
 * perintah ini memang sudah bisa mengubah database.
 *
 * Tetap meninggalkan jejak: Audit Log ("server console" + alasan) dan karyawannya diberi
 * tahu lewat notifikasi aplikasi dan email. Alasan wajib diisi.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'security:reset-two-factor
        {eci : Employee ID (ECI) karyawan yang 2FA-nya direset}
        {--reason= : Alasan reset (wajib, masuk Audit Log)}
        {--unlock : Sekaligus buka kunci akun bila terkunci karena kode 2FA salah berulang}
        {--force : Lewati konfirmasi mengetik ulang ECI (untuk skrip)}';

    protected $description = 'Emergency: turn off two-factor authentication for one employee from the server.';

    public function handle(): int
    {
        $eci = trim((string) $this->argument('eci'));

        $employee = DB::table('employee')->where('eci', $eci)->first(['employee_id', 'eci']);
        $target   = $employee
            ? DB::table('auth_users')->where('employee_id', $employee->employee_id)->first()
            : null;

        if (!$target) {
            $this->error("No employee account found for ECI \"{$eci}\".");
            return self::FAILURE;
        }

        if (!TwoFactorAuthService::isEnabled($target)) {
            $this->warn("Two-factor authentication is already off for {$eci}. Nothing to reset.");
            if ($this->option('unlock') && !empty($target->locked_until)) {
                $this->unlock($target);
                $this->info('The account lock was cleared.');
            }
            return self::SUCCESS;
        }

        $reason = trim((string) $this->option('reason'));
        if ($reason === '' && $this->input->isInteractive()) {
            $reason = trim((string) $this->ask('Reason for this reset (recorded in the Audit Log)'));
        }
        if ($reason === '') {
            $this->error('A reason is required. Use --reason="...".');
            return self::FAILURE;
        }

        $this->line("Employee : {$eci} ({$target->email})");
        $this->line("Reason   : {$reason}");

        if (!$this->option('force')) {
            if (!$this->input->isInteractive()) {
                $this->error('Not an interactive shell. Add --force to confirm.');
                return self::FAILURE;
            }
            $typed = trim((string) $this->ask("Type the ECI \"{$eci}\" again to confirm"));
            if ($typed !== $eci) {
                $this->error('The ECI did not match. Nothing was changed.');
                return self::FAILURE;
            }
        }

        $result = TwoFactorResetService::reset($target, 'server console on ' . gethostname(), $reason);

        if ($this->option('unlock')) {
            $this->unlock($target);
        }

        Log::warning('security:reset-two-factor executed from the server', [
            'eci'    => $eci,
            'reason' => $reason,
            'unlock' => (bool) $this->option('unlock'),
        ]);

        $this->info("Two-factor authentication was turned off for {$result['target_name']} ({$eci}).");
        $this->line($result['email_sent']
            ? 'The employee was notified by email and in the app.'
            : 'The employee was notified in the app, but the email could not be sent.');
        $this->line('They now sign in with their password only, and set up two-factor authentication again.');

        return self::SUCCESS;
    }

    private function unlock(object $target): void
    {
        DB::table('auth_users')->where('id', $target->id)->update(['locked_until' => null, 'updated_at' => now()]);
        TwoFactorAuthService::clearFailedChallenge($target->id);
    }
}
