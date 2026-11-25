<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin mencatat timesheet sendiri, satu per tipe: Project, Non Project, Support, Office.
 *
 * Sebelumnya tab tipe di halaman Timesheets ditentukan peta role yang ditulis di kode, dengan
 * cadangan "semua tipe" untuk role yang tidak ada di peta (mis. External Consultant melihat
 * semua tab). Sekarang diatur di Control Center > Menu Access, di bawah menu Timesheets.
 *
 * Grant awal meniru peta lama supaya tidak ada yang kehilangan akses mendadak (keputusan
 * pemilik sistem, bukan aturan baku "hanya Admin"). Role yang tadinya hanya memakai cadangan
 * tidak diberi apa pun: akses mereka harus diberikan lewat Menu Access.
 *   Project, Non Project : Delivery Project Head, Delivery Project User (role id 15), Helpdesk
 *   Support              : Delivery Support User, Delivery Support Head, Helpdesk
 *   Office               : EC User, Delivery RPMO Head, Helpdesk
 * EC Administrator selalu ikut.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'timesheet.log.project'     => 'Log Project Timesheet',
        'timesheet.log.non-project' => 'Log Non Project Timesheet',
        'timesheet.log.support'     => 'Log Support Timesheet',
        'timesheet.log.office'      => 'Log Office Timesheet',
    ];

    /** slug => id role (sama dengan peta lama di CalendarController); nama diresolve dari id. */
    private const GRANTS = [
        'timesheet.log.project'     => [4, 15, 6],
        'timesheet.log.non-project' => [4, 15, 6],
        'timesheet.log.support'     => [2, 5, 6],
        'timesheet.log.office'      => [3, 7, 6],
    ];

    public function up(): void
    {
        // order_seq 1-6 sudah dipakai menu Timesheets lain
        if (!MenuRegistrar::register('calendar.timesheets', self::PERMISSIONS, 7)) {
            return;
        }

        foreach (self::GRANTS as $slug => $roleIds) {
            $names = DB::table('employee_role')->whereIn('id', $roleIds)->pluck('name')->all();
            MenuRegistrar::grantToAdminAndRoles([$slug], $names);
        }
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys(self::PERMISSIONS));
    }
};
