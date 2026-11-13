<?php

namespace App\Support;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pengaturan "Login dengan Microsoft" (Settings > Security, khusus EC
 * Administrator). Disimpan di app_configs sebagai satu baris JSON.
 *
 * - Client secret DISIMPAN TERENKRIPSI (Crypt) dan tidak pernah dikembalikan ke
 *   browser; form hanya tahu "ada secret tersimpan atau tidak".
 * - Tenant ID, Client ID, dan secret yang dikosongkan jatuh balik ke app
 *   registration Graph yang sudah ada (services.microsoft_graph.*), jadi satu
 *   app registration cukup dan hanya ada satu tanggal kedaluwarsa secret.
 * - Default MATI. Tombol login Microsoft hanya muncul bila diaktifkan DAN
 *   kredensialnya lengkap.
 * - Dibaca dalam try/catch: masalah DB tidak boleh menjatuhkan halaman login.
 */
final class MicrosoftLoginSettings
{
    public const KEY = 'security.microsoft_login';

    /** Hanya GUID (format tenant/client ID Entra) yang diterima. */
    private const GUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /** @return array{enabled: bool, tenant_id: string, client_id: string, client_secret: string} */
    private static function stored(): array
    {
        try {
            $raw = AppConfig::getJson(self::KEY, []);
        } catch (Throwable $e) {
            Log::warning('Microsoft login setting unreadable, treating as disabled', [
                'error' => $e->getMessage(),
            ]);
            $raw = [];
        }

        $raw = is_array($raw) ? $raw : [];

        return [
            'enabled'       => (bool) ($raw['enabled'] ?? false),
            'tenant_id'     => trim((string) ($raw['tenant_id'] ?? '')),
            'client_id'     => trim((string) ($raw['client_id'] ?? '')),
            'client_secret' => (string) ($raw['client_secret'] ?? ''),
        ];
    }

    public static function enabled(): bool
    {
        return self::stored()['enabled'];
    }

    public static function tenantId(): string
    {
        $stored = self::stored()['tenant_id'];

        return $stored !== '' ? $stored : trim((string) config('services.microsoft_graph.tenant_id'));
    }

    public static function clientId(): string
    {
        $stored = self::stored()['client_id'];

        return $stored !== '' ? $stored : trim((string) config('services.microsoft_graph.client_id'));
    }

    public static function clientSecret(): string
    {
        $encrypted = self::stored()['client_secret'];

        if ($encrypted !== '') {
            try {
                return Crypt::decryptString($encrypted);
            } catch (Throwable $e) {
                // APP_KEY berganti: secret lama tak terbaca. Jangan diam-diam
                // memakai secret lain, anggap tidak ada supaya admin mengisi ulang.
                Log::warning('Stored Microsoft login client secret could not be decrypted', [
                    'error' => $e->getMessage(),
                ]);

                return '';
            }
        }

        return trim((string) config('services.microsoft_graph.client_secret'));
    }

    /** Ada secret yang tersimpan lewat halaman ini (bukan dari .env)? */
    public static function hasStoredSecret(): bool
    {
        return self::stored()['client_secret'] !== '';
    }

    /** Ada nilai yang dipakai dari .env karena kolomnya dikosongkan di halaman ini? */
    public static function usesEnvFallback(): bool
    {
        $stored = self::stored();

        return $stored['tenant_id'] === '' || $stored['client_id'] === '' || $stored['client_secret'] === '';
    }

    /** Tenant/client ID untuk mengisi form (tanpa fallback env). */
    public static function storedTenantId(): string
    {
        return self::stored()['tenant_id'];
    }

    public static function storedClientId(): string
    {
        return self::stored()['client_id'];
    }

    public static function credentialsComplete(): bool
    {
        return preg_match(self::GUID_PATTERN, self::tenantId()) === 1
            && preg_match(self::GUID_PATTERN, self::clientId()) === 1
            && self::clientSecret() !== '';
    }

    /** Dipakai halaman login dan Settings: tombol tampil hanya bila true. */
    public static function loginAvailable(): bool
    {
        return self::enabled() && self::credentialsComplete();
    }

    /** URI yang harus didaftarkan di Entra (Web redirect URI). */
    public static function redirectUri(): string
    {
        return route('microsoft.callback');
    }

    /** Hanya EC Administrator (salah satu role-nya) yang boleh mengubah pengaturan ini. */
    public static function canManage(): bool
    {
        $user    = session('user') ?? [];
        $roleIds = $user['role_ids'] ?? array_filter([$user['role']['id'] ?? null]);

        return in_array(\App\Enums\RoleId::EC_ADMINISTRATOR->value, array_map('intval', (array) $roleIds), true);
    }

    public static function isValidGuid(string $value): bool
    {
        return preg_match(self::GUID_PATTERN, $value) === 1;
    }

    /**
     * @param string|null $clientSecret null/'' = pertahankan secret yang sudah ada.
     * @return array{enabled: bool, secret_changed: bool}
     */
    public static function save(bool $enabled, string $tenantId, string $clientId, ?string $clientSecret): array
    {
        $current = self::stored();

        $secretChanged = $clientSecret !== null && trim($clientSecret) !== '';
        $secret = $secretChanged ? Crypt::encryptString(trim($clientSecret)) : $current['client_secret'];

        AppConfig::setJson(
            self::KEY,
            [
                'enabled'       => $enabled,
                'tenant_id'     => trim($tenantId),
                'client_id'     => trim($clientId),
                'client_secret' => $secret,
            ],
            'Login dengan Microsoft (Settings > Security). Client secret terenkripsi.'
        );

        return ['enabled' => $enabled, 'secret_changed' => $secretChanged];
    }
}
