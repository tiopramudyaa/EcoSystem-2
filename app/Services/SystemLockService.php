<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * System lock: while on, only the protected administrator account
 * (LoginSecurityService::PROTECTED_ECI) may sign in or keep using the system.
 * Meant for deployments and maintenance windows, so nobody changes data
 * while it runs.
 *
 * Fails open: if the table is missing or unreadable (e.g. migration not yet
 * run), the system is treated as unlocked so a lock bug can never take the
 * whole app down.
 */
class SystemLockService
{
    public const DEFAULT_MESSAGE = 'The system is locked for maintenance. Please try again later.';

    public static function state(): array
    {
        try {
            $row = Schema::hasTable('system_lock')
                ? DB::table('system_lock')->where('id', 1)->first()
                : null;
        } catch (\Throwable $e) {
            Log::warning('SystemLockService: cannot read lock state', ['error' => $e->getMessage()]);
            $row = null;
        }

        return [
            'is_locked' => (bool) ($row->is_locked ?? false),
            'message'   => $row->message ?? null,
            'locked_by' => $row->locked_by ?? null,
            'locked_at' => $row->locked_at ?? null,
        ];
    }

    public static function isLocked(): bool
    {
        return self::state()['is_locked'];
    }

    public static function message(): string
    {
        $message = trim((string) (self::state()['message'] ?? ''));

        return $message !== '' ? $message : self::DEFAULT_MESSAGE;
    }

    /** True when this ECI may still use the system while it is locked. */
    public static function isExempt(?string $eci): bool
    {
        return $eci === LoginSecurityService::PROTECTED_ECI;
    }

    /** True when this ECI is turned away right now (locked and not exempt). */
    public static function blocks(?string $eci): bool
    {
        return self::isLocked() && !self::isExempt($eci);
    }

    public static function lock(?string $message, string $by): void
    {
        DB::table('system_lock')->updateOrInsert(['id' => 1], [
            'is_locked'  => true,
            'message'    => $message !== null && trim($message) !== '' ? mb_substr(trim($message), 0, 255) : null,
            'locked_by'  => mb_substr($by, 0, 150),
            'locked_at'  => now(),
            'updated_at' => now(),
        ]);
    }

    public static function unlock(): void
    {
        DB::table('system_lock')->updateOrInsert(['id' => 1], [
            'is_locked'  => false,
            'message'    => null,
            'locked_by'  => null,
            'locked_at'  => null,
            'updated_at' => now(),
        ]);
    }
}
