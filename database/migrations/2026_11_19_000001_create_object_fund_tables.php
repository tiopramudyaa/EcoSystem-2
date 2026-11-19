<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konsep Object (Project / Office / Support) dan dana untuk Reimbursement
 * (dan kelak Cash Advance). Seluruhnya PENAMBAHAN: tidak ada tabel atau kolom
 * lama yang diubah, dan setiap tabel punya down(). Titik kembali konsep lama:
 * tag git `checkpoint/reimbursement-before-object-fund`.
 *
 * Rancangan: docs/HRGA/design/charged-object-and-auto-signatories.md
 *
 *  - object_fund_pools    : kantong dana (alokasi) per Object dan kategori.
 *  - object_fund_entries  : pencatatan pemakaian dana. Sisa = alokasi dikurangi
 *                           entri `committed` (dana berkurang saat disetujui).
 *  - object_flow_settings : satu baris, pengaturan alur per Object.
 *  - object_office_settings: pengaturan per cabang (dana aktif, nominal, penanggung jawab).
 *
 * Data biaya project (`delivery_project_costs`) TIDAK disentuh: nilai
 * `actual_amount`-nya dihitung ulang otomatis dari cost item sehingga tidak
 * aman ditulis fitur lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('object_fund_pools', function (Blueprint $table) {
            $table->id();

            // office | project | support
            $table->string('object_type', 10);
            // id cabang (branches.id), project (delivery_projects.id) atau tiket (ticket.ticket_id)
            $table->unsignedBigInteger('ref_id');
            // Label yang dibekukan saat dibuat, supaya laporan tetap terbaca bila nama berubah.
            $table->string('ref_label', 200)->nullable();
            // Kategori bebas pendek, mis. Transport atau Akomodasi. Kosong = satu kantong umum.
            $table->string('category', 60)->default('');
            // Hanya untuk Office: periode bulanan 'YYYY-MM'. Kosong untuk Project dan Support.
            $table->string('period', 7)->default('');

            $table->decimal('amount', 20, 2)->default(0);
            $table->boolean('is_active')->default(true);

            // proposed -> approved. Hanya yang approved dipakai untuk pemeriksaan sisa.
            $table->string('status', 10)->default('approved');
            $table->unsignedBigInteger('proposed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            // `category` dan `period` berbawaan '' (bukan NULL) supaya unik ini
            // benar-benar menolak duplikat di MySQL maupun SQLite.
            $table->unique(['object_type', 'ref_id', 'category', 'period'], 'object_fund_pool_unique');
            $table->index(['object_type', 'ref_id'], 'object_fund_pool_ref_idx');
        });

        Schema::create('object_fund_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pool_id');

            // Dokumen yang memakai dana: modul dan id-nya, dan barisnya.
            $table->string('document_type', 30)->default('reimbursement');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('item_id')->nullable();

            $table->decimal('amount', 20, 2);
            // committed = mengurangi sisa. voided = dikembalikan (dokumen dibatalkan Finance).
            $table->string('status', 10)->default('committed');
            // Tanda bahwa entri ini melewati sisa saat disetujui (kebijakan Peringatan).
            $table->boolean('over_allocation')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('pool_id', 'object_fund_entry_pool_fk')
                ->references('id')->on('object_fund_pools')->cascadeOnDelete();
            $table->index(['pool_id', 'status'], 'object_fund_entry_pool_status_idx');
            $table->index(['document_type', 'document_id'], 'object_fund_entry_doc_idx');
        });

        Schema::create('object_flow_settings', function (Blueprint $table) {
            $table->id();

            // Saklar utama konsep Object. false = perilaku Reimbursement lama persis.
            $table->boolean('enabled')->default(false);

            // Project: urutan dan keikutsertaan langkah.
            $table->boolean('project_admin_enabled')->default(true);
            $table->boolean('project_manager_enabled')->default(true);
            $table->string('project_order', 15)->default('admin_first'); // admin_first | manager_first
            $table->unsignedBigInteger('project_fallback_role_id')->nullable();
            $table->unsignedBigInteger('project_fallback_employee_id')->nullable();

            // Support.
            $table->boolean('support_lead_enabled')->default(true);
            $table->boolean('support_manager_enabled')->default(false);
            $table->unsignedBigInteger('support_fallback_role_id')->nullable();
            $table->unsignedBigInteger('support_fallback_employee_id')->nullable();

            // Kebijakan bila nominal melebihi sisa: warn | block.
            // Bawaan peringatan untuk semua (keputusan D3); diatur per Object.
            $table->string('policy_project', 5)->default('warn');
            $table->string('policy_office', 5)->default('warn');
            $table->string('policy_support', 5)->default('warn');

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('object_office_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->unique();

            $table->boolean('fund_active')->default(false);
            $table->decimal('monthly_amount', 20, 2)->default(0);

            // Penanggung jawab: role lalu orang.
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->unsignedBigInteger('approver_employee_id')->nullable();

            $table->timestamps();
        });

        // Satu baris pengaturan, selalu ada.
        \Illuminate\Support\Facades\DB::table('object_flow_settings')->insert([
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('object_office_settings');
        Schema::dropIfExists('object_flow_settings');
        Schema::dropIfExists('object_fund_entries');
        Schema::dropIfExists('object_fund_pools');
    }
};
