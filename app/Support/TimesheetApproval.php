<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Izin approve / reject timesheet, satu izin per tipe, diatur di
 * Control Center > Menu Access (role_menu), bukan hardcode role.
 *
 * Satu izin per tipe sudah mencakup approve DAN reject.
 */
class TimesheetApproval
{
    /** tipe => slug izin (function di bawah menu Timesheets) */
    public const PERMISSIONS = [
        'project'     => 'timesheet.approve.project',
        'non_project' => 'timesheet.approve.non-project',
        'support'     => 'timesheet.approve.support',
        'office'      => 'timesheet.approve.office',
    ];

    public const LABELS = [
        'project'     => 'Project',
        'non_project' => 'Non Project',
        'support'     => 'Support',
        'office'      => 'Office',
    ];

    /** project | non_project | support | office, diturunkan dari kolom yang sama dengan UI. */
    public static function typeOf(Timesheet $timesheet): string
    {
        if ($timesheet->delivery_projects_id) {
            return 'project';
        }
        if ($timesheet->is_non_project) {
            return 'non_project';
        }
        if ($timesheet->ticket_id) {
            return 'support';
        }

        return 'office';
    }

    /** Karyawan yang sedang login (dari session), atau null. */
    public static function currentEmployee(): ?Employee
    {
        $id = session('user')['id'] ?? null;

        return $id ? Employee::find($id) : null;
    }

    /**
     * Tipe yang boleh di-approve / reject karyawan ini, urut seperti tab di halaman.
     *
     * @return string[]
     */
    public static function approvableTypes(?Employee $employee = null): array
    {
        $employee ??= self::currentEmployee();
        if (!$employee) {
            return [];
        }

        return array_values(array_filter(
            array_keys(self::PERMISSIONS),
            fn (string $type) => $employee->canAccessMenu(self::PERMISSIONS[$type])
        ));
    }

    public static function canApprove(string $type, ?Employee $employee = null): bool
    {
        return in_array($type, self::approvableTypes($employee), true);
    }

    /**
     * Batasi query Timesheet ke baris bertipe salah satu dari $types
     * (tanpa tipe sama sekali = tidak ada baris).
     *
     * @param  string[]  $types
     */
    public static function constrain(Builder $query, array $types): Builder
    {
        if (empty($types)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($types) {
            foreach ($types as $type) {
                $q->orWhere(function (Builder $inner) use ($type) {
                    match ($type) {
                        'project'     => $inner->whereNotNull('delivery_projects_id'),
                        'non_project' => $inner->where('is_non_project', true),
                        'support'     => $inner->whereNull('delivery_projects_id')->whereNotNull('ticket_id'),
                        'office'      => $inner->whereNull('delivery_projects_id')->whereNull('ticket_id')->where('is_non_project', false),
                    };
                });
            }
        });
    }

    /**
     * Karyawan aktif yang diberi izin approve tipe ini lewat rolenya, untuk notifikasi
     * "timesheet menunggu persetujuan". EC Administrator tidak ikut dinotifikasi: role itu
     * memegang semua izin, jadi tanpa pengecualian ini setiap submit membunyikan semua admin.
     * Admin yang juga memegang role approver lain tetap dinotifikasi lewat role itu.
     *
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    public static function approversFor(string $type)
    {
        $slug = self::PERMISSIONS[$type] ?? null;
        if (!$slug) {
            return collect();
        }

        $adminRoleId = MenuRegistrar::adminRoleId();

        $roleIds = DB::table('role_menu')
            ->join('menu', 'menu.id', '=', 'role_menu.menu_id')
            ->where('menu.slug', $slug)
            ->where('menu.is_active', true)
            ->where('role_menu.can_view', true)
            ->where('role_menu.role_id', '!=', $adminRoleId)
            ->pluck('role_menu.role_id')
            ->all();

        if (empty($roleIds)) {
            return collect();
        }

        return Employee::withAnyRole($roleIds)->where('is_active', true)->get();
    }
}
