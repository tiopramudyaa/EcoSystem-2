<?php

namespace App\Console\Commands;

use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Data dummy untuk menguji konsep Object dan dana (Reimbursement, Cash Advance,
 * Cash Advance Report). Semua baris berawalan DEMO supaya mudah dikenali dan
 * dihapus bersih.
 *
 *   php artisan demo:object-funds seed                 buat karyawan + login, office, project, tiket
 *   php artisan demo:object-funds seed --configure     ... dan isi pengaturan Objects and Funds
 *   php artisan demo:object-funds remove               hapus data dummy (menolak bila sudah ada dokumen)
 *   php artisan demo:object-funds remove --with-documents --reset-settings
 *
 * Tidak mengubah Approval Workflow maupun aturan Reimbursement/Cash Advance yang
 * sudah ada. Pengaturan Objects and Funds hanya diisi dengan --configure.
 */
class DemoObjectFundsCommand extends Command
{
    protected $signature = 'demo:object-funds {action : seed atau remove} {--configure : isi pengaturan Objects and Funds dengan nilai contoh} {--with-documents : saat remove, ikut hapus dokumen milik karyawan dummy} {--reset-settings : saat remove, matikan saklar Objects and Funds}';

    protected $description = 'Buat atau hapus data dummy untuk menguji Objects and Funds (Reimbursement, Cash Advance, Cash Advance Report)';

    public const PASSWORD = 'Demo#2026';
    private const DOMAIN  = 'example.test';
    private const ROLE    = 'DEMO Tester';

    /** username => [eci, nama depan, nama belakang, nick, jabatan, role tambahan, akses penuh admin] */
    private const PERSONAS = [
        'demo.requester'      => ['DEMO001', 'Dina', 'Pemohon',        'Dina Demo',   'Consultant',             [], false],
        'demo.projectadmin'   => ['DEMO002', 'Sari', 'Admin Project',  'Sari Demo',   'Project Administrator',  ['Delivery Project Administrator'], false],
        'demo.projectmanager' => ['DEMO003', 'Rudi', 'Manajer Project', 'Rudi Demo',  'Project Manager',        ['Delivery Project Manager'], false],
        'demo.ticketlead'     => ['DEMO004', 'Gita', 'Ticket Lead',    'Gita Demo',   'Support Lead',           [], false],
        'demo.officehead'     => ['DEMO005', 'Dewi', 'Kepala Office',  'Dewi Demo',   'Office Head',            [], false],
        'demo.finance'        => ['DEMO006', 'Fajar', 'Finance',       'Fajar Demo',  'Finance Officer',        ['EC Administrator'], true],
    ];

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'seed'   => $this->seed(),
            'remove' => $this->remove(),
            default  => $this->fail('Action must be "seed" or "remove".'),
        };
    }

    // ── seed ────────────────────────────────────────────────────────────────

    private function seed(): int
    {
        if (DB::table('employee')->where('eci', 'like', 'DEMO%')->exists()) {
            $this->error('Demo data already exists. Run "php artisan demo:object-funds remove" first if you want to recreate it.');

            return self::FAILURE;
        }

        $roleId = fn (string $name) => DB::table('employee_role')->where('name', $name)->value('id');
        $registered = $roleId('User System Registered');

        if (!$registered) {
            $this->error('The role "User System Registered" does not exist, so demo users could not log in. Aborting.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($roleId, $registered) {
            // Peran uji dengan akses ke menu Cash Advance milik karyawan.
            $testerRole = DB::table('employee_role')->where('name', self::ROLE)->value('id')
                ?: DB::table('employee_role')->insertGetId(['name' => self::ROLE, 'description' => 'Demo data for Objects and Funds testing', 'created_at' => now(), 'updated_at' => now()]);

            foreach (['general.my-cash-advance', 'general.my-cash-advance-report', 'general.my-reimbursement'] as $slug) {
                $menuId = DB::table('menu')->where('slug', $slug)->value('id');
                if ($menuId) {
                    DB::table('role_menu')->updateOrInsert(['role_id' => $testerRole, 'menu_id' => $menuId], ['can_view' => 1, 'can_create' => 1, 'can_edit' => 0, 'can_delete' => 0, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            // Tanda tangan contoh: persetujuan Reimbursement mensyaratkannya.
            Storage::disk('local')->put('demo-signatures/signature.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

            foreach (self::PERSONAS as $username => [$eci, $first, $last, $nick, $position, $extraRoles]) {
                DB::table('employee')->insert(['eci' => $eci, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
                $employeeId = (int) DB::table('employee')->where('eci', $eci)->value('employee_id');

                DB::table('employee_basic_data')->insert([
                    'employee_id' => $employeeId, 'first_name' => $first, 'last_name' => $last,
                    'nick_name' => $nick, 'position' => $position,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                foreach (array_merge(['User System Registered', self::ROLE], $extraRoles) as $roleName) {
                    $id = $roleId($roleName);
                    if ($id) {
                        DB::table('employee_role_assignment')->updateOrInsert(['employee_id' => $employeeId, 'role_id' => $id], ['created_at' => now(), 'updated_at' => now()]);
                    } else {
                        $this->warn("Role \"{$roleName}\" not found; skipped for {$username}.");
                    }
                }

                DB::table('auth_users')->insert([
                    'employee_id' => $employeeId, 'username' => $username, 'email' => $username . '@' . self::DOMAIN,
                    'password' => Hash::make(self::PASSWORD), 'is_active' => 1, 'is_already_cp' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                if ($username !== 'demo.requester' && Schema::hasTable('employee_hr_profile')) {
                    DB::table('employee_hr_profile')->updateOrInsert(['employee_id' => $employeeId], ['signature_path' => 'demo-signatures/signature.png']);
                }
            }

            $this->seedMasterData();
        });

        Cache::flush();

        $this->info('Demo data created.');
        $this->table(['Username', 'Name', 'Role in the test'], collect(self::PERSONAS)->map(fn ($p, $u) => [$u, "{$p[1]} {$p[2]}", $p[4]])->values()->all());
        $this->line('Password for every demo user: ' . self::PASSWORD);

        if ($this->option('configure')) {
            $this->applyExampleSettings();
        } else {
            $this->line('Objects and Funds settings were NOT changed. Fill them in Reimbursement > Settings > Objects and Funds, or run again with --configure after "remove".');
        }

        return self::SUCCESS;
    }

    private function seedMasterData(): void
    {
        $emp = fn (string $eci) => (int) DB::table('employee')->where('eci', $eci)->value('employee_id');

        foreach ([['DEMO-JKT', 'Demo Jakarta'], ['DEMO-BDG', 'Demo Bandung'], ['DEMO-SBY', 'Demo Surabaya']] as [$code, $name]) {
            DB::table('branches')->insert(['code' => $code, 'name' => $name, 'latitude' => -6.2, 'longitude' => 106.8, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        DB::table('customer')->insert(['email' => 'demo.client@' . self::DOMAIN, 'created_at' => now(), 'updated_at' => now()]);
        $clientId = (int) DB::table('customer')->where('email', 'demo.client@' . self::DOMAIN)->value('customer_id');

        DB::table('delivery_projects')->insert([
            ['name' => 'Demo ERP Alpha',   'io_number' => 'DEMO-001', 'client_id' => $clientId, 'project_admin_id' => $emp('DEMO002'), 'project_manager_id' => $emp('DEMO003'), 'is_closed' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Demo Mobile Beta (no admin)', 'io_number' => 'DEMO-002', 'client_id' => $clientId, 'project_admin_id' => null, 'project_manager_id' => $emp('DEMO003'), 'is_closed' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Demo Closed Project', 'io_number' => 'DEMO-003', 'client_id' => $clientId, 'project_admin_id' => $emp('DEMO002'), 'project_manager_id' => $emp('DEMO003'), 'is_closed' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('ticket')->insert([
            ['ticket_number' => 'DEMO-T-0001', 'description' => 'Demo: server migration support', 'ticket_lead_id' => $emp('DEMO004'), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['ticket_number' => 'DEMO-T-0002', 'description' => 'Demo: ticket without a lead', 'ticket_lead_id' => null, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /** Nilai contoh untuk Objects and Funds. Hanya dengan --configure. */
    private function applyExampleSettings(): void
    {
        $emp = fn (string $eci) => (int) DB::table('employee')->where('eci', $eci)->value('employee_id');
        $branch = fn (string $code) => (int) DB::table('branches')->where('code', $code)->value('id');
        $project = fn (string $io) => (int) DB::table('delivery_projects')->where('io_number', $io)->value('id');
        $ticket = fn (string $no) => (int) DB::table('ticket')->where('ticket_number', $no)->value('ticket_id');

        ObjectFlowSetting::current()->update([
            'enabled' => true, 'cash_advance_enabled' => true,
            'project_admin_enabled' => true, 'project_manager_enabled' => true, 'project_order' => 'admin_first',
            'project_fallback_employee_id' => $emp('DEMO006'), 'support_fallback_employee_id' => $emp('DEMO006'),
            'support_lead_enabled' => true, 'support_manager_enabled' => false,
            'policy_project' => 'warn', 'policy_office' => 'warn', 'policy_support' => 'warn',
        ]);
        ObjectFlowSetting::forgetCache();

        OfficeSetting::updateOrCreate(['branch_id' => $branch('DEMO-JKT')], ['fund_active' => true, 'monthly_amount' => 5000000, 'approver_employee_id' => $emp('DEMO005')]);
        OfficeSetting::updateOrCreate(['branch_id' => $branch('DEMO-BDG')], ['fund_active' => false, 'monthly_amount' => 0, 'approver_employee_id' => $emp('DEMO005')]);
        // DEMO-SBY sengaja tanpa penanggung jawab: dipakai untuk menguji pesan penolakan.

        foreach ([
            ['project', $project('DEMO-001'), 'Demo ERP Alpha', 'Transport', 10000000],
            ['project', $project('DEMO-001'), 'Demo ERP Alpha', 'Accommodation', 15000000],
            ['support', $ticket('DEMO-T-0001'), 'DEMO-T-0001', 'Accommodation', 3000000],
            ['support', $ticket('DEMO-T-0001'), 'DEMO-T-0001', 'Transport', 1500000],
        ] as [$type, $ref, $label, $category, $amount]) {
            FundPool::create(['object_type' => $type, 'ref_id' => $ref, 'ref_label' => $label, 'category' => $category, 'period' => '', 'amount' => $amount, 'status' => FundPool::STATUS_APPROVED, 'approved_at' => now()]);
        }

        $this->info('Objects and Funds settings filled with the example values.');
    }

    // ── remove ──────────────────────────────────────────────────────────────

    private function remove(): int
    {
        $ids = DB::table('employee')->where('eci', 'like', 'DEMO%')->pluck('employee_id')->all();

        $docs = $this->documentCounts($ids);

        if (array_sum($docs) > 0 && !$this->option('with-documents')) {
            $this->error('Demo employees already have documents: ' . json_encode($docs) . '. Run again with --with-documents to delete them too.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($ids) {
            if ($this->option('with-documents')) {
                $this->deleteDocuments($ids);
            }

            $pools = DB::table('object_fund_pools')->where(function ($q) {
                $q->where(fn ($w) => $w->where('object_type', 'project')->whereIn('ref_id', DB::table('delivery_projects')->where('io_number', 'like', 'DEMO-%')->pluck('id')))
                  ->orWhere(fn ($w) => $w->where('object_type', 'support')->whereIn('ref_id', DB::table('ticket')->where('ticket_number', 'like', 'DEMO-T-%')->pluck('ticket_id')))
                  ->orWhere(fn ($w) => $w->where('object_type', 'office')->whereIn('ref_id', DB::table('branches')->where('code', 'like', 'DEMO-%')->pluck('id')));
            })->pluck('id');

            DB::table('object_fund_entries')->whereIn('pool_id', $pools)->delete();
            DB::table('object_fund_pools')->whereIn('id', $pools)->delete();
            DB::table('object_office_settings')->whereIn('branch_id', DB::table('branches')->where('code', 'like', 'DEMO-%')->pluck('id'))->delete();

            DB::table('ticket')->where('ticket_number', 'like', 'DEMO-T-%')->delete();
            DB::table('delivery_projects')->where('io_number', 'like', 'DEMO-%')->delete();
            DB::table('branches')->where('code', 'like', 'DEMO-%')->delete();
            DB::table('customer')->where('email', 'like', 'demo.client@%')->delete();

            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->whereIn('employee_id', $ids)->delete();
            }
            if (Schema::hasTable('employee_hr_profile')) {
                DB::table('employee_hr_profile')->whereIn('employee_id', $ids)->delete();
            }

            DB::table('auth_users')->whereIn('employee_id', $ids)->delete();
            DB::table('employee_role_assignment')->whereIn('employee_id', $ids)->delete();
            DB::table('employee_basic_data')->whereIn('employee_id', $ids)->delete();
            DB::table('employee')->whereIn('employee_id', $ids)->delete();

            $testerRole = DB::table('employee_role')->where('name', self::ROLE)->value('id');
            if ($testerRole) {
                DB::table('role_menu')->where('role_id', $testerRole)->delete();
                DB::table('employee_role')->where('id', $testerRole)->delete();
            }

            if ($this->option('reset-settings')) {
                ObjectFlowSetting::current()->update(['enabled' => false, 'cash_advance_enabled' => false, 'project_fallback_employee_id' => null, 'support_fallback_employee_id' => null]);
                ObjectFlowSetting::forgetCache();
            }
        });

        Storage::disk('local')->delete('demo-signatures/signature.png');
        Cache::flush();

        $this->info('Demo data removed.');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function documentCounts(array $ids): array
    {
        return [
            'reimbursements' => DB::table('reimbursement_requests')->whereIn('employee_id', $ids)->count(),
            'cash_advances'  => DB::table('cash_advances')->whereIn('employee_id', $ids)->count(),
            'cash_advance_reports' => DB::table('cash_advance_reports')->whereIn('employee_id', $ids)->count(),
        ];
    }

    private function deleteDocuments(array $ids): void
    {
        $rb = DB::table('reimbursement_requests')->whereIn('employee_id', $ids)->pluck('id');
        $ca = DB::table('cash_advances')->whereIn('employee_id', $ids)->pluck('id');
        $car = DB::table('cash_advance_reports')->whereIn('employee_id', $ids)->orWhereIn('cash_advance_id', $ca)->pluck('id');

        DB::table('object_fund_entries')->where('document_type', 'reimbursement')->whereIn('document_id', $rb)->delete();
        DB::table('object_fund_entries')->where('document_type', 'cash_advance')->whereIn('document_id', $ca)->delete();

        DB::table('reimbursement_items')->whereIn('reimbursement_request_id', $rb)->delete();
        DB::table('reimbursement_request_approvals')->whereIn('reimbursement_request_id', $rb)->delete();
        DB::table('reimbursement_requests')->whereIn('id', $rb)->delete();

        DB::table('cash_advance_report_items')->whereIn('cash_advance_report_id', $car)->delete();
        DB::table('cash_advance_report_approvals')->whereIn('cash_advance_report_id', $car)->delete();
        DB::table('cash_advance_reports')->whereIn('id', $car)->delete();

        DB::table('cash_advance_approvals')->whereIn('cash_advance_id', $ca)->delete();
        DB::table('cash_advances')->whereIn('id', $ca)->delete();
    }
}
