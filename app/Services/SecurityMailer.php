<?php

namespace App\Services;

use App\Http\Controllers\PasswordSetupController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Email pemberitahuan keamanan ke karyawan, lewat mailbox pengirim Microsoft Graph yang sama
 * dengan email setup/reset password. Kegagalan kirim TIDAK boleh menggagalkan aksi yang
 * memicunya: dicatat di log, dan pemanggil diberi tahu lewat nilai kembali.
 */
class SecurityMailer
{
    /** @return bool true bila Graph menerima email */
    public static function send(?string $to, string $subject, string $htmlBody): bool
    {
        $to = trim((string) $to);

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning('SecurityMailer: tidak ada alamat email yang valid, email dilewati');
            return false;
        }

        try {
            $sender   = config('services.microsoft_graph.sender_email');
            $response = Http::withToken(PasswordSetupController::getGraphToken())
                ->timeout(15)
                ->post(
                    rtrim(config('services.microsoft_graph.base_url', 'https://graph.microsoft.com/v1.0'), '/') . "/users/{$sender}/sendMail",
                    [
                        'message' => [
                            'subject'      => $subject,
                            'body'         => ['contentType' => 'HTML', 'content' => $htmlBody],
                            'toRecipients' => [['emailAddress' => ['address' => $to]]],
                        ],
                        'saveToSentItems' => true,
                    ]
                );

            if (!$response->successful()) {
                Log::error('SecurityMailer: Graph menolak email', ['status' => $response->status()]);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('SecurityMailer: gagal kirim email', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
