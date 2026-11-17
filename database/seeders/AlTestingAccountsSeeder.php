<?php

namespace Database\Seeders;

use App\Models\EmployeeBasicData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dua akun uji pribadi: "Al-Testing-Internal" (karyawan internal biasa) dan "Al-Testing-Eksternal" (konsultan External).
 *
 *   php artisan db:seed --class=AlTestingAccountsSeeder
 *   AL_TESTING_CLEAN=1 php artisan db:seed --class=AlTestingAccountsSeeder     # hapus HANYA kedua akun ini
 *
 * Masuk dengan username, e-mail, atau ECI (kolom di bawah) dan kata sandi PASSWORD. Aman dijalankan berulang (updateOrInsert) dan
 * hanya menyentuh baris kedua karyawan ini. Akun berkata sandi tetap tidak boleh ada di production, jadi seeder berhenti di sana.
 * Hak akses menu diatur seperti akun lain, lewat Control Center → Menu Access (role di bawah hanyalah titik awal).
 */
class AlTestingAccountsSeeder extends Seeder
{
    /** Kata sandi akun uji (sama dengan akun uji lain di proyek ini). */
    private const PASSWORD = 'password123';

    private const ACCOUNTS = [
        [
            'eci' => 'AL-TEST-INT', 'username' => 'Al-Testing-Internal', 'email' => 'al.testing.internal@ecosystem.local',
            'name' => 'Al-Testing-Internal', 'external' => false, 'position' => 'Staff', 'cell' => '081200000001',
            'roles' => ['User System Registered', 'EC User'],
        ],
        [
            'eci' => 'AL-TEST-EXT', 'username' => 'Al-Testing-Eksternal', 'email' => 'al.testing.eksternal@ecosystem.local',
            'name' => 'Al-Testing-Eksternal', 'external' => true, 'position' => 'Consultant', 'cell' => '081200000002',
            'roles' => ['User System Registered', 'EC User', 'External Consultant'],
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->say('Dihentikan: akun uji berkata sandi tetap tidak boleh dibuat di production.');

            return;
        }
        if (env('AL_TESTING_CLEAN')) {
            $this->clean();

            return;
        }

        foreach (self::ACCOUNTS as $a) {
            $this->account($a);
        }
        $this->say('Selesai. Masuk dengan username "Al-Testing-Internal" / "Al-Testing-Eksternal" (atau e-mail / ECI AL-TEST-INT / AL-TEST-EXT) dan kata sandi "' . self::PASSWORD . '".');
        $this->say('Bersihkan: AL_TESTING_CLEAN=1 php artisan db:seed --class=AlTestingAccountsSeeder');
    }

    private function account(array $a): void
    {
        $now = now();
        $employeeId = (int) (DB::table('employee')->where('eci', $a['eci'])->value('employee_id')
            ?: DB::table('employee')->insertGetId(['eci' => $a['eci'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]));
        DB::table('employee')->where('employee_id', $employeeId)->update(['is_active' => true, 'updated_at' => $now]);

        DB::table('employee_basic_data')->updateOrInsert(['employee_id' => $employeeId], [
            'title' => 'Mr.', 'first_name' => $a['name'], 'last_name' => '', 'nick_name' => $a['name'],
            'search_term_1' => mb_strtoupper($a['name']), 'search_term_2' => mb_strtoupper($a['eci']),
            'gender' => 'Male', 'marital_status' => 'Single', 'birth_date' => '1995-01-01', 'birth_place' => 'Jakarta',
            'position' => $a['position'], 'department' => $a['external'] ? 'RESOURCE, PROJECT MANAGEMENT & OPERATIONS' : 'HUMAN CAPITAL',
            'division' => $a['external'] ? 'OPERATIONS' : 'Human Capital',
            'employee_group' => $a['external'] ? 'EXTERNAL' : 'INTERNAL', 'home_base' => $a['external'] ? 'Others' : 'Jakarta',
            'employee_type' => EmployeeBasicData::deriveEmployeeType($a['external'] ? 'Others' : 'Jakarta'),
            'created_by' => 'AlTestingSeeder', 'created_on' => $now, 'block' => false, 'deletion_flag' => false,
        ]);

        // Data induk dasar (KTP, NPWP, alamat, bank) agar halaman profil dan kontrak bisa dicoba; satu baris per jenis.
        // Nomor harus unik per orang (indeks unik jenis+nomor): diturunkan dari digit terakhir nomor telepon uji.
        $n = substr($a['cell'], -1);
        foreach (['KTP' => '31710101019500' . str_pad($n, 2, '0', STR_PAD_LEFT), 'NPWP' => '91.234.567.' . $n . '-541.000'] as $type => $number) {
            DB::table('employee_identification')->updateOrInsert(['employee_id' => $employeeId, 'identification_type' => $type],
                ['identification_number' => $number, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('employee_address')->updateOrInsert(['employee_id' => $employeeId, 'address_type' => 'Home'], [
            'country' => 'ID', 'region' => 'DKI Jakarta', 'city' => 'Jakarta Selatan', 'street' => 'Jl. Uji Coba No. 1', 'postal_code' => '12000',
            'cell_phone' => $a['cell'], 'email_work' => $a['email'], 'email_personal' => $a['email'], 'is_primary' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('employee_bank')->updateOrInsert(['employee_id' => $employeeId], [
            'bank_name' => 'Bank Contoh', 'account_number' => '8001200000' . substr($a['cell'], -1), 'account_holder' => $a['name'], 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ($a['roles'] as $role) {
            $roleId = DB::table('employee_role')->where('name', $role)->value('id');
            if (!$roleId) {
                $this->say("Role \"{$role}\" tidak ada di database ini; dilewati.");
                continue;
            }
            DB::table('employee_role_assignment')->insertOrIgnore(['employee_id' => $employeeId, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('auth_users')->updateOrInsert(['employee_id' => $employeeId], [
            'customer_id' => null, 'username' => $a['username'], 'email' => $a['email'], 'password' => Hash::make(self::PASSWORD),
            'is_active' => true, 'is_already_cp' => true, 'email_verified_at' => $now, 'cp_token' => null, 'cp_token_expires_at' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** Hapus HANYA kedua karyawan uji ini beserta baris turunannya. */
    private function clean(): void
    {
        $ids = DB::table('employee')->whereIn('eci', array_column(self::ACCOUNTS, 'eci'))->pluck('employee_id')->all();
        if (!$ids) {
            $this->say('Tidak ada akun uji untuk dihapus.');

            return;
        }
        foreach (['auth_users', 'employee_role_assignment', 'employee_bank', 'employee_address', 'employee_identification', 'employee_basic_data', 'employee'] as $table) {
            DB::table($table)->whereIn('employee_id', $ids)->delete();
        }
        $this->say('Akun uji dihapus (' . count($ids) . ' karyawan).');
    }

    private function say(string $msg): void
    {
        $this->command?->info($msg);
    }
}
