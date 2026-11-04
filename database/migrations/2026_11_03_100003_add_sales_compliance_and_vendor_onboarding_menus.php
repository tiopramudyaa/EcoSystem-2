<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * Menu Sales Compliance Approval dan Vendor Onboarding di bawah Business & Legal (slug induk `legal`).
 *
 * Seperti semua slug baru, kelimanya lahir aktif HANYA untuk EC Administrator (MenuRegistrar). Pemilik sistem membagikannya
 * lewat Control Center → Menu Access setelah tim compliance ditetapkan:
 *   - Sales             → `legal.sales-compliance` (View + Create + Edit: membuat dan mengubah draft)
 *   - Tim compliance    → tidak perlu slug untuk MENYETUJUI — cukup ditunjuk pada langkah di halaman Settings (ApprovalEngine);
 *                         `.approve` hanya untuk pemegang yang ingin ikut memutuskan tanpa ditunjuk.
 *   - Procurement/Legal → `legal.vendor-onboarding`
 * Settings (langkah persetujuan, pertanyaan, jenis dokumen) dipisah supaya bisa diberikan ke Legal tanpa hak lain.
 */
return new class extends Migration
{
    private const PAGES = [
        'legal.sales-compliance'            => 'Sales Compliance Approval',
        'legal.sales-compliance.settings'   => 'Sales Compliance Settings',
        'legal.vendor-onboarding'           => 'Vendor Onboarding',
        'legal.vendor-onboarding.settings'  => 'Vendor Onboarding Settings',
    ];

    private const FUNCTIONS = [
        'legal.sales-compliance.approve'  => 'Sales Compliance — Approve / Reject',
        'legal.vendor-onboarding.approve' => 'Vendor Onboarding — Approve / Reject',
    ];

    public function up(): void
    {
        MenuRegistrar::register('legal', self::PAGES, 210, 'page');
        MenuRegistrar::register('legal', self::FUNCTIONS, 230, 'function');

        // Halaman memakai kotak Create/Edit/Delete (`menu.can`), dan MenuRegistrar hanya memberi View. EC Administrator diberi
        // ketiganya supaya bisa langsung menguji; role lain tetap kosong sampai pemilik membaginya di Menu Access.
        MenuRegistrar::grantToAdminAndRoles(array_keys(self::PAGES), [], ['create', 'edit', 'delete']);
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_merge(array_keys(self::PAGES), array_keys(self::FUNCTIONS)));
    }
};
