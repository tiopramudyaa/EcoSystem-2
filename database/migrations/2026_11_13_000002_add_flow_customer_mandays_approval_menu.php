<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu "Flow" (group, baru) → "Customer Mandays Approval" (page) di bawah Management.
 *
 * Grup "Flow" disiapkan sebagai wadah semua pengaturan alur approval; Customer
 * Mandays adalah flow pertamanya. Grup dibuat manual karena MenuRegistrar::register()
 * hanya bisa membuat menu di bawah parent yang SUDAH ada (pola sama dengan
 * 2026_09_07_000002_add_ticket_document_type_menu).
 *
 * order_seq 9: children `management` yang sudah ada memakai 1-8.
 *
 * Grant admin-only sesuai aturan baku MenuRegistrar — role lain menyusul lewat
 * Control Center → Menu Access.
 */
return new class extends Migration
{
    private const GROUP_SLUG = 'management.flow';
    private const CHILD_SLUG = 'management.flow.customer-mandays';

    public function up(): void
    {
        if (!DB::table('menu')->where('slug', self::GROUP_SLUG)->exists()) {
            $managementId = DB::table('menu')->where('slug', 'management')->value('id');
            if (!$managementId) {
                return;
            }

            $now = now();
            DB::table('menu')->insert([
                'parent_id'  => $managementId,
                'name'       => 'Flow',
                'slug'       => self::GROUP_SLUG,
                'type'       => 'group',
                'route_name' => null,
                'icon'       => null,
                'order_seq'  => 9,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        MenuRegistrar::grantToAdminOnly([self::GROUP_SLUG]);

        MenuRegistrar::register(self::GROUP_SLUG, [
            self::CHILD_SLUG => 'Customer Mandays Approval',
        ], 1, 'page');
    }

    public function down(): void
    {
        MenuRegistrar::remove([self::CHILD_SLUG, self::GROUP_SLUG]);
    }
};
