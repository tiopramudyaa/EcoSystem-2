<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Support\TwoFactorEnforcementSettings;
use Illuminate\Support\Facades\DB;

/**
 * Mematikan 2FA satu akun karyawan beserta semua jejaknya. Dipakai bersama oleh:
 *   - EmployeeController::resetTwoFactor (tombol "Reset 2FA", admin lain + kode 2FA baru),
 *   - perintah artisan `security:reset-two-factor` (jalur darurat dari server).
 * Pemanggil bertanggung jawab atas SIAPA yang boleh; layanan ini hanya melakukan reset,
 * mencatatnya di Audit Log, dan memberi tahu karyawannya (notifikasi aplikasi + email).
 */
class TwoFactorResetService
{
    /**
     * @param  object  $target       baris auth_users milik karyawan
     * @param  string  $byLabel      siapa yang mereset, mis. "Nama Admin" atau "server console"
     * @param  string  $auditNote    tambahan untuk Audit Log, mis. alasan dari jalur darurat
     * @return array{target_name: string, email_sent: bool}
     */
    public static function reset(object $target, string $byLabel, string $auditNote = ''): array
    {
        $employeeId = (int) $target->employee_id;

        $basic      = DB::table('employee_basic_data')->where('employee_id', $employeeId)->first(['first_name', 'last_name']);
        $targetName = trim(($basic->first_name ?? '') . ' ' . ($basic->last_name ?? '')) ?: ('Employee #' . $employeeId);

        DB::table('auth_users')->where('id', $target->id)->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_last_used_at'   => null,
            'updated_at'                => now(),
        ]);

        TwoFactorAuthService::clearTrust($target->id);
        TwoFactorAuthService::clearFailedChallenge($target->id);

        AuditLog::recordAction(
            module: 'Security',
            auditableType: 'AuthUser',
            auditableId: $target->id,
            event: 'updated',
            recordLabel: $targetName,
            description: "reset two-factor authentication of {$targetName} (by {$byLabel})" . ($auditNote !== '' ? ": {$auditNote}" : ''),
            old: ['two_factor_enabled' => true],
            new: ['two_factor_enabled' => false],
        );

        // Pemberitahuan ke karyawan: bell + email. Gagal kirim tidak membatalkan reset.
        $roleIds        = DB::table('employee_role_assignment')->where('employee_id', $employeeId)->pluck('role_id')->map(fn ($r) => (int) $r)->all();
        $mustSetUpAgain = !empty(array_intersect($roleIds, TwoFactorEnforcementSettings::roleIds()));

        Notification::create([
            'employee_id' => $employeeId,
            'type'        => 'security_alert',
            'from_name'   => 'Security',
            'preview'     => 'Your two-factor authentication was reset by an administrator. If you did not ask for this, contact your administrator.',
            'link'        => '/settings?tab=security',
            'is_read'     => false,
        ]);

        $appName  = e(config('app.name', 'ECoSystem'));
        $safeName = e($targetName);
        $safeBy   = e($byLabel);
        $when     = e(now()->format('d M Y H:i'));
        $again    = $mustSetUpAgain
            ? '<p>Your role requires two-factor authentication, so you will be asked to set it up again after you sign in.</p>'
            : '<p>You can turn it on again in <strong>Settings &gt; Security</strong>.</p>';

        $emailSent = SecurityMailer::send(
            $target->email,
            "Your {$appName} two-factor authentication was reset",
            <<<HTML
<p>Hello {$safeName},</p>
<p>Two-factor authentication on your <strong>{$appName}</strong> account was reset by <strong>{$safeBy}</strong> on {$when}.</p>
<p>From now on you sign in with your password only, until you set it up again.</p>
{$again}
<p><strong>If you did not ask for this, contact your administrator at once.</strong></p>
<br>
<p>Regards,<br><strong>The {$appName} Team</strong></p>
HTML
        );

        return ['target_name' => $targetName, 'email_sent' => $emailSent];
    }
}
