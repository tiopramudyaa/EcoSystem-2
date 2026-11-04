<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor Onboarding — sisi internal dari portal vendor.eclectic.co.id.
 *
 * Satu baris `vendor_registrations` = satu pendaftaran vendor yang ditinjau sebelum menjadi Business Partner bertipe Vendor
 * (`customer.type = 'Vendor'`). Setelah terverifikasi, `customer_id` menautkan ke Business Partner yang dibuat.
 *
 * Seluruhnya tabel BARU. Nama kolom profil sengaja sama dengan portal lama (company_name, npwp, nib, …) supaya impor data lama
 * satu-satu. Seperti portal lama, vendor perorangan memakai kolom yang sama: nama lengkap → company_name, NIK → nib,
 * keahlian → business_type, daftar jasa → product_list; `vendor_type` menentukan label yang tampil.
 *
 *  - `source` membedakan asal baris: internal (dibuat staf), portal (kelak, portal publik), legacy (impor portal lama).
 *    `legacy_vendor_id` menjaga impor idempoten — baris yang sama tidak masuk dua kali.
 *  - Portal publik BELUM dibangun (menunggu keputusan pemilik), jadi belum ada kolom login vendor di sini; ditambah lewat
 *    migrasi tersendiri saat portalnya dikerjakan.
 *  - Jenis dokumen (`vendor_document_fields`) dan pertanyaan due diligence (`vendor_questions`) adalah konfigurasi, bukan kode.
 *    Isi awalnya perkiraan dari berkas terjemahan portal lama; ekspor DB lama bisa menimpa (lihat `vendors:import-legacy`).
 *  - Langkah persetujuan memakai bentuk yang sama dengan Purchase Request dan Sales Compliance Approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_registrations')) {
            Schema::create('vendor_registrations', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no', 30)->unique();
                $table->string('vendor_type', 10)->default('b2b'); // b2b|personal
                $table->string('source', 12)->default('internal'); // internal|portal|legacy
                $table->unsignedBigInteger('legacy_vendor_id')->nullable()->unique();

                $table->string('company_name', 255);
                $table->text('company_address')->nullable();
                $table->string('company_phone', 50)->nullable();
                $table->string('company_email', 255)->nullable();
                $table->string('company_website', 255)->nullable();
                $table->string('npwp', 50)->nullable();
                $table->string('nib', 50)->nullable();
                $table->string('business_type', 255)->nullable();
                $table->text('product_list')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_no', 50)->nullable();
                $table->string('bank_account_name', 255)->nullable();
                $table->string('pic_name', 255)->nullable();
                $table->string('pic_phone', 50)->nullable();
                $table->string('pic_email', 255)->nullable();

                $table->string('status', 20)->default('draft'); // draft|submitted|in_review|verified|rejected
                $table->unsignedSmallInteger('round')->default(1);
                $table->unsignedTinyInteger('current_step_order')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable(); // Business Partner hasil tautan
                $table->timestamps();

                $table->index('status');
                $table->index('customer_id');
                $table->foreign('customer_id')->references('customer_id')->on('customer')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('vendor_document_fields')) {
            Schema::create('vendor_document_fields', function (Blueprint $table) {
                $table->id();
                $table->string('label_en', 255);
                $table->string('label_id', 255)->nullable();
                $table->string('description_en', 500)->nullable();
                $table->string('description_id', 500)->nullable();
                $table->string('vendor_type', 10)->default('all'); // all|b2b|personal
                $table->boolean('is_required')->default(false);
                $table->string('template_url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vendor_documents')) {
            Schema::create('vendor_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_id');
                $table->unsignedBigInteger('field_id');
                $table->string('original_name', 255);
                $table->string('path', 255);                 // disk privat `local`, nama acak
                $table->unsignedInteger('size')->default(0);
                $table->string('mime', 100)->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();

                $table->index(['registration_id', 'field_id'], 'vendor_docs_reg_field_idx');
                $table->foreign('registration_id')->references('id')->on('vendor_registrations')->cascadeOnDelete();
                $table->foreign('field_id')->references('id')->on('vendor_document_fields');
            });
        }

        if (!Schema::hasTable('vendor_questions')) {
            Schema::create('vendor_questions', function (Blueprint $table) {
                $table->id();
                $table->text('text_en');
                $table->text('text_id')->nullable();
                $table->string('type', 20)->default('radio'); // text|radio|checkbox|textarea
                $table->json('options')->nullable();
                $table->string('vendor_type', 10)->default('all');
                $table->boolean('is_required')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vendor_answers')) {
            Schema::create('vendor_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_id');
                $table->unsignedBigInteger('question_id');
                $table->text('answer')->nullable();
                $table->timestamps();

                $table->unique(['registration_id', 'question_id'], 'vendor_answers_unique');
                $table->foreign('registration_id')->references('id')->on('vendor_registrations')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('vendor_status_logs')) {
            Schema::create('vendor_status_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_id');
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name', 255)->nullable();
                $table->timestamps();

                $table->index('registration_id');
                $table->foreign('registration_id')->references('id')->on('vendor_registrations')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('vendor_approval_steps')) {
            Schema::create('vendor_approval_steps', function (Blueprint $table) {
                $table->id();
                $table->string('module', 30)->default('vendor_onboarding');
                $table->unsignedTinyInteger('order_seq')->default(1);
                $table->string('name', 100);
                $table->string('approver_type', 20)->default('role'); // role|employee
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->json('approver_employee_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('approver_role_id')->references('id')->on('employee_role')->nullOnDelete();
                $table->index(['module', 'order_seq'], 'vendor_steps_module_order_idx');
            });
        }

        if (!Schema::hasTable('vendor_approvals')) {
            Schema::create('vendor_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_id');
                $table->unsignedSmallInteger('round')->default(1);
                $table->unsignedTinyInteger('order_seq');
                $table->string('step_name', 100);
                $table->string('approver_type', 20)->default('role');
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->json('approver_employee_ids')->nullable();
                $table->boolean('chosen_by_requester')->default(false); // dibaca ApprovalEngine; selalu false di modul ini
                $table->string('status', 20)->default('waiting');      // waiting|approved|rejected|skipped
                $table->unsignedBigInteger('acted_by')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['registration_id', 'round', 'order_seq'], 'vendor_approvals_unique');
                $table->foreign('registration_id')->references('id')->on('vendor_registrations')->cascadeOnDelete();
            });
        }

        $this->seedDocumentFields();
        $this->seedQuestions();
        $this->seedDefaultStep();
    }

    public function down(): void
    {
        foreach ([
            'vendor_approvals', 'vendor_approval_steps', 'vendor_status_logs', 'vendor_answers', 'vendor_questions',
            'vendor_documents', 'vendor_document_fields', 'vendor_registrations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function seedDocumentFields(): void
    {
        if (DB::table('vendor_document_fields')->exists()) {
            return;
        }

        // [label EN, label ID, tipe, wajib, urutan]
        $rows = [
            ['Business Identification Number (NIB)', 'NIB', 'b2b', true, 10],
            ['Company Tax ID (NPWP)', 'NPWP Perusahaan', 'b2b', true, 20],
            ['Deed of Incorporation', 'Akta Pendirian', 'b2b', true, 30],
            ['Latest Company Deed of Amendment', 'Akta Perubahan Terakhir Perusahaan', 'b2b', false, 40],
            ['Company Management Structure', 'Susunan Pengurus Perusahaan', 'b2b', false, 50],
            ['Trading Business License (SIUP)', 'SIUP', 'b2b', false, 60],
            ['Business Domicile Certificate (SKDU)', 'Surat Keterangan Domisili Usaha', 'b2b', false, 70],
            ['National Identity Card of Directors / Commissioners (KTP)', 'KTP Direksi / Komisaris', 'b2b', false, 80],
            ['Company Profile', 'Company Profile', 'b2b', false, 90],
            ['Anti-Bribery and Anti-Corruption Declaration', 'Surat Pernyataan Anti Suap dan Anti Korupsi', 'all', true, 100],
            ['National Identity Card (KTP)', 'Kartu Tanda Penduduk (KTP)', 'personal', true, 110],
            ['Tax ID (NPWP)', 'NPWP', 'personal', true, 120],
            ['Curriculum Vitae (CV)', 'Curriculum Vitae (CV)', 'personal', true, 130],
            ['Family Card (KK)', 'Kartu Keluarga (KK)', 'personal', false, 140],
            ['Diploma', 'Ijazah', 'personal', false, 150],
            ['Other Legal Documents', 'Dokumen Legal Lainnya', 'all', false, 900],
        ];

        $now = now();
        foreach ($rows as [$en, $id, $type, $required, $order]) {
            DB::table('vendor_document_fields')->insert([
                'label_en' => $en, 'label_id' => $id, 'vendor_type' => $type, 'is_required' => $required,
                'sort_order' => $order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /** Teks diambil dari berkas terjemahan portal lama; jenis jawaban (Yes/No, teks) adalah perkiraan. */
    private function seedQuestions(): void
    {
        if (DB::table('vendor_questions')->exists()) {
            return;
        }

        $file = database_path('data/vendor_onboarding_questions.php');
        if (!is_file($file)) {
            return;
        }

        $now = now();
        foreach (require $file as $row) {
            DB::table('vendor_questions')->insert([
                'text_en' => $row['text_en'], 'text_id' => $row['text_id'], 'type' => $row['type'],
                'options' => json_encode($row['options']), 'vendor_type' => 'all', 'is_required' => false,
                'sort_order' => $row['sort_order'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function seedDefaultStep(): void
    {
        if (DB::table('vendor_approval_steps')->exists()) {
            return;
        }

        $roleId = DB::table('employee_role')->where('name', 'HO Legal Head')->value('id')
            ?? DB::table('employee_role')->where('name', 'EC Administrator')->value('id');

        DB::table('vendor_approval_steps')->insert([
            'module' => 'vendor_onboarding', 'order_seq' => 1, 'name' => 'Vendor Verification',
            'approver_type' => 'role', 'approver_role_id' => $roleId, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
