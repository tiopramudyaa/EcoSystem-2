<?php

namespace App\Support;

/**
 * Pesan galat yang aman ditampilkan kepada pengguna akhir.
 *
 * Hanya pengecualian "bisnis" (DomainException, InvalidArgumentException, ValidationException) yang pesannya ditulis untuk manusia
 * dan boleh tampil apa adanya. Pengecualian lain (SQL, SMTP, kelas internal, jejak tumpukan) diganti pesan umum; detailnya cukup
 * di log (Log::error) yang sudah dipanggil pemanggil.
 */
final class UserMessage
{
    public const GENERIC = 'Something went wrong. Please try again; if it keeps happening, contact your administrator.';

    public static function of(\Throwable $e, string $fallback = self::GENERIC): string
    {
        $safe = $e instanceof \DomainException
            || $e instanceof \InvalidArgumentException
            || $e instanceof \Illuminate\Validation\ValidationException;

        return $safe && trim($e->getMessage()) !== '' ? $e->getMessage() : $fallback;
    }
}
