<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * Izin "Reset Two-Factor Authentication" di Master > Employee.
 *
 * Dipakai administrator untuk mematikan 2FA karyawan yang kehilangan ponsel DAN kode pemulihannya.
 * Sesuai aturan baku MenuRegistrar, izin baru lahir aktif HANYA untuk EC Administrator dan mati untuk
 * semua role lain; pemilik sistem yang memutuskan siapa lagi yang boleh lewat Control Center > Menu Access.
 */
return new class extends Migration
{
    public function up(): void
    {
        MenuRegistrar::register('master.employee', [
            'master.employee.reset-two-factor' => 'Reset Two-Factor Authentication',
        ], 3);
    }

    public function down(): void
    {
        MenuRegistrar::remove(['master.employee.reset-two-factor']);
    }
};
