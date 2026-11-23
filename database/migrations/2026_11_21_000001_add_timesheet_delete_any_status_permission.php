<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * Izin menghapus timesheet di status apa pun (Submitted, Approved, Rejected), bukan hanya Draft.
 *
 * Tanpa izin ini, timesheet yang sudah Approved tidak bisa dihapus siapa pun lewat aplikasi.
 * Pemegang izin melihat aksi Delete di menu klik kanan dan di panel Timesheet Detail menu
 * Timesheets. Penghapusan berupa soft delete dan tercatat di Audit Log atas nama pelakunya.
 *
 * Sesuai aturan MenuRegistrar: lahir aktif hanya untuk EC Administrator. Role lain
 * diberikan lewat Control Center > Menu Access bila memang diputuskan.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'timesheet.delete.any-status' => 'Delete Timesheet in Any Status',
    ];

    public function up(): void
    {
        // order_seq 1 sampai 5 sudah dipakai timesheet.create dan timesheet.approve.*
        MenuRegistrar::register('calendar.timesheets', self::PERMISSIONS, 6);
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys(self::PERMISSIONS));
    }
};
