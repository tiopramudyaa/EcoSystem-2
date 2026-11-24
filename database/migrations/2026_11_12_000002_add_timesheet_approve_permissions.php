<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * Izin approve / reject timesheet, satu per tipe: Project, Non Project, Support, Office.
 *
 * Sebelumnya siapa boleh approve ditulis di kode (EC Administrator, RPMO Head, Head of Project,
 * Head of Support untuk semua tipe). Sekarang diatur di Control Center > Menu Access, di bawah
 * menu Timesheets. Satu izin per tipe mencakup approve DAN reject.
 *
 * Grant awal meniru pembagian yang berjalan hari ini, supaya approval tidak berhenti saat
 * migrasi ini dijalankan (keputusan pemilik sistem, bukan aturan baku "hanya Admin"):
 *   Project, Non Project : Delivery Project Head
 *   Support              : Delivery Support Head
 *   Office               : Delivery RPMO Head
 * EC Administrator selalu ikut. Selanjutnya diubah lewat Menu Access.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'timesheet.approve.project'     => 'Approve or Reject Project Timesheet',
        'timesheet.approve.non-project' => 'Approve or Reject Non Project Timesheet',
        'timesheet.approve.support'     => 'Approve or Reject Support Timesheet',
        'timesheet.approve.office'      => 'Approve or Reject Office Timesheet',
    ];

    public function up(): void
    {
        // order_seq 1 sudah dipakai timesheet.create
        if (!MenuRegistrar::register('calendar.timesheets', self::PERMISSIONS, 2)) {
            return;
        }

        MenuRegistrar::grantToAdminAndRoles(
            ['timesheet.approve.project', 'timesheet.approve.non-project'],
            ['Delivery Project Head']
        );
        MenuRegistrar::grantToAdminAndRoles(['timesheet.approve.support'], ['Delivery Support Head']);
        MenuRegistrar::grantToAdminAndRoles(['timesheet.approve.office'], ['Delivery RPMO Head']);
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys(self::PERMISSIONS));
    }
};
