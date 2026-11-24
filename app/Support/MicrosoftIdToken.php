<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Membaca dan memeriksa klaim ID token Microsoft Entra (OpenID Connect).
 *
 * Tanda tangan JWT TIDAK diverifikasi di sini, dengan sengaja: token ini
 * diterima langsung dari token endpoint Microsoft lewat TLS, dalam permintaan
 * yang diautentikasi client secret kita sendiri (OIDC Core 3.1.3.7 mengizinkan
 * ini). Token tidak pernah datang dari browser. Karena itu yang diperiksa
 * adalah klaimnya: penerbit, audience, tenant, kedaluwarsa, dan nonce.
 */
final class MicrosoftIdToken
{
    /**
     * @return array<string, mixed> klaim yang sudah lolos pemeriksaan
     * @throws InvalidArgumentException bila ada klaim yang tidak sah
     */
    public static function claims(string $jwt, string $tenantId, string $clientId, string $nonce, ?int $now = null): array
    {
        $now ??= time();

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Malformed ID token.');
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $claims = is_string($json) ? json_decode($json, true) : null;

        if (!is_array($claims)) {
            throw new InvalidArgumentException('Unreadable ID token.');
        }

        $tid = strtolower((string) ($claims['tid'] ?? ''));
        if ($tid === '' || $tid !== strtolower($tenantId)) {
            throw new InvalidArgumentException('ID token is from a different tenant.');
        }

        if (($claims['iss'] ?? '') !== 'https://login.microsoftonline.com/' . $tid . '/v2.0') {
            throw new InvalidArgumentException('ID token issuer mismatch.');
        }

        if (!hash_equals(strtolower($clientId), strtolower((string) ($claims['aud'] ?? '')))) {
            throw new InvalidArgumentException('ID token audience mismatch.');
        }

        if ((int) ($claims['exp'] ?? 0) < $now - 60) {
            throw new InvalidArgumentException('ID token expired.');
        }

        if ($nonce === '' || !hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw new InvalidArgumentException('ID token nonce mismatch.');
        }

        if (!MicrosoftLoginSettings::isValidGuid((string) ($claims['oid'] ?? ''))) {
            throw new InvalidArgumentException('ID token has no valid object id.');
        }

        return $claims;
    }
}
