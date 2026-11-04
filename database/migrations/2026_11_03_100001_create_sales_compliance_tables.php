<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sales Compliance Approval — pengganti portal sales.eclectic.co.id.
 *
 * Satu baris `sales_compliance_requests` = satu deal yang harus lolos tinjauan compliance sebelum ditandatangani.
 * Seluruhnya tabel BARU; tidak ada tabel lama yang diubah.
 *
 *  - Pengaju adalah karyawan (`employee_id`), bukan akun terpisah seperti `sales_users` di portal lama: login, peran, dan
 *    atasan sudah dikelola EcoSystem.
 *  - Harga disimpan sebagai angka (`decimal`), bukan teks seperti di portal lama, supaya selisih dengan MRP bisa dihitung.
 *  - Alur persetujuan memakai bentuk yang sama dengan Purchase Request: `*_approval_steps` (konfigurasi) dan `*_approvals`
 *    (salinan langkah pada dokumen). Kolom `round` ada karena dokumen yang ditolak boleh diajukan ulang: putaran baru membuat
 *    salinan langkah baru, jejak putaran lama tetap utuh.
 *  - 19 pertanyaan bagian A–G disalin dari portal lama (teks asli Indonesia di `text_id`, terjemahan di `text_en`) dan bisa
 *    diubah kelak; pertanyaan yang tidak dipakai dinonaktifkan, tidak dihapus, agar jawaban lama tetap terbaca.
 *  - Langkah awal tunggal "Compliance Review" dipegang HO Legal Head (cadangan: EC Administrator). Tim compliance sebenarnya
 *    belum ditetapkan; pemilik sistem mengaturnya di halaman Settings tanpa perubahan kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_compliance_requests')) {
            Schema::create('sales_compliance_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_no', 30)->unique();
                $table->unsignedBigInteger('employee_id');           // sales yang mengajukan
                $table->unsignedBigInteger('created_by')->nullable(); // terisi bila admin membuatkan

                $table->string('project_name', 255);
                $table->unsignedBigInteger('customer_id')->nullable(); // Business Partner bertipe Customer, bila dipilih
                $table->string('customer_name', 255);                  // dibekukan sebagai teks: nama bisa berubah kelak
                $table->string('pic_name', 255);
                $table->boolean('has_meeting')->default(false);
                $table->string('meeting_location', 500)->nullable();
                $table->boolean('has_hospitality')->default(false);
                $table->text('hospitality_detail')->nullable();
                $table->json('documents_received')->nullable();

                $table->decimal('final_price', 20, 2)->nullable();
                $table->decimal('mrp_price', 20, 2)->nullable();
                $table->text('price_justification')->nullable();
                $table->string('payment_method', 100)->nullable();
                $table->decimal('contract_value', 20, 2)->nullable();
                $table->date('contract_date')->nullable();

                $table->string('status', 20)->default('draft'); // draft|in_review|approved|rejected|cancelled
                $table->unsignedSmallInteger('round')->default(1);
                $table->unsignedTinyInteger('current_step_order')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'employee_id'], 'sca_requests_status_emp_idx');
                $table->index('customer_id');
            });
        }

        if (!Schema::hasTable('sales_compliance_questions')) {
            Schema::create('sales_compliance_questions', function (Blueprint $table) {
                $table->id();
                $table->string('section', 255)->default('');
                $table->text('text_en');
                $table->text('text_id')->nullable();
                $table->string('type', 20)->default('radio'); // text|radio|checkbox|textarea
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order'], 'sca_questions_active_idx');
            });
        }

        if (!Schema::hasTable('sales_compliance_answers')) {
            Schema::create('sales_compliance_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id');
                $table->unsignedBigInteger('question_id');
                $table->text('answer')->nullable();
                $table->timestamps();

                $table->unique(['request_id', 'question_id'], 'sca_answers_unique');
                $table->foreign('request_id')->references('id')->on('sales_compliance_requests')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('sales_compliance_attachments')) {
            Schema::create('sales_compliance_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id');
                $table->string('file_type', 30);            // price|mrp|other
                $table->string('original_name', 255);
                $table->string('path', 255);                // disk privat `local`, nama acak
                $table->unsignedInteger('size')->default(0);
                $table->string('mime', 100)->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();

                $table->index('request_id');
                $table->foreign('request_id')->references('id')->on('sales_compliance_requests')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('sales_compliance_approval_steps')) {
            Schema::create('sales_compliance_approval_steps', function (Blueprint $table) {
                $table->id();
                $table->string('module', 30)->default('sales_compliance');
                $table->unsignedTinyInteger('order_seq')->default(1);
                $table->string('name', 100);
                $table->string('approver_type', 20)->default('role'); // role|employee
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->json('approver_employee_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('approver_role_id')->references('id')->on('employee_role')->nullOnDelete();
                $table->index(['module', 'order_seq'], 'sca_steps_module_order_idx');
            });
        }

        if (!Schema::hasTable('sales_compliance_approvals')) {
            Schema::create('sales_compliance_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id');
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

                $table->unique(['request_id', 'round', 'order_seq'], 'sca_approvals_unique');
                $table->foreign('request_id')->references('id')->on('sales_compliance_requests')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('sales_compliance_logs')) {
            Schema::create('sales_compliance_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id');
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name', 255)->nullable();
                $table->timestamps();

                $table->index('request_id');
                $table->foreign('request_id')->references('id')->on('sales_compliance_requests')->cascadeOnDelete();
            });
        }

        $this->seedQuestions();
        $this->seedDefaultStep();
    }

    public function down(): void
    {
        foreach ([
            'sales_compliance_logs', 'sales_compliance_approvals', 'sales_compliance_approval_steps',
            'sales_compliance_attachments', 'sales_compliance_answers', 'sales_compliance_questions',
            'sales_compliance_requests',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    /** Hanya bila tabelnya masih kosong — menjalankan ulang tidak menggandakan pertanyaan. */
    private function seedQuestions(): void
    {
        if (DB::table('sales_compliance_questions')->exists()) {
            return;
        }

        $yn = ['Yes', 'No'];
        $ny = ['No', 'Yes'];
        $rows = [
            ['A. Opportunity Identification', 'Does this opportunity come from an official and transparent process?', 'Apakah opportunity ini berasal dari proses resmi dan transparan?', 'radio', $yn, 1, 10],
            ['A. Opportunity Identification', 'Is there any indication that the tender or specification is steered toward a particular vendor?', 'Apakah ada indikasi bahwa tender/specification diarahkan ke vendor tertentu?', 'radio', ['None', 'There is an indication'], 1, 20],
            ['A. Opportunity Identification', 'If so, please explain.', 'Jika ada, jelaskan.', 'textarea', [], 0, 30],
            ['A. Opportunity Identification', 'Did Eclectic help prepare the TOR, RFP, technical specification, or requirements used in this tender?', 'Apakah Eclectic ikut menyusun TOR, RFP, spesifikasi teknis, atau requirement yang akan digunakan dalam tender ini?', 'radio', $ny, 1, 40],
            ['B. Conflict of Interest', 'Is there any family or personal relationship between the sales team, Eclectic management, a partner or subcontractor, and an official or client contact on this project?', 'Apakah ada hubungan keluarga/pribadi antara team sales, management Eclectic, partner, atau subcontractor dengan pejabat/client terkait project?', 'radio', $ny, 1, 50],
            ['B. Conflict of Interest', 'Is any former client or government employee involved in this deal?', 'Apakah ada mantan pegawai client/pemerintah yang terlibat dalam deal ini?', 'radio', $ny, 1, 60],
            ['B. Conflict of Interest', 'If so, describe their position and role.', 'Jika ya, jelaskan posisi dan perannya.', 'textarea', [], 0, 70],
            ['C. Third Party / Partner Involvement', 'Is any third party, consultant, or broker helping to obtain this project?', 'Apakah ada pihak ketiga/consultant/broker yang membantu memperoleh project ini?', 'radio', $ny, 1, 80],
            ['C. Third Party / Partner Involvement', 'Is there any success fee, marketing fee, referral fee, or commission related to this project?', 'Apakah ada success fee, marketing fee, referral fee, atau komisi terkait project ini?', 'radio', $ny, 1, 90],
            ['C. Third Party / Partner Involvement', 'If so, state to whom, how much, and on what contractual basis.', 'Jika ya, jelaskan kepada siapa, berapa besarannya, dan dasar kontraknya apa.', 'textarea', [], 0, 100],
            ['C. Third Party / Partner Involvement', 'Have all partners and subcontractors passed compliance due diligence?', 'Apakah seluruh partner/subcontractor telah melalui due diligence compliance?', 'radio', ['Yes', 'Not yet'], 1, 110],
            ['D. Government Interaction', 'Has the client requested any gift, sponsorship, entertainment, travel, cash incentive, or other benefit?', 'Apakah ada permintaan hadiah, sponsorship, entertainment, perjalanan, cash incentive, atau fasilitas lain dari pihak client?', 'radio', $ny, 1, 120],
            ['D. Government Interaction', 'Has there been any informal communication about the tender winner, competitor pricing, evaluation scores, or non-public information?', 'Apakah pernah ada komunikasi informal terkait pemenang tender, pricing competitor, nilai evaluasi, atau akses informasi non-public?', 'radio', $ny, 1, 130],
            ['E. Commercial Integrity', 'Is the pricing in line with internal and principal policy?', 'Apakah pricing yang diberikan telah sesuai kebijakan internal dan principal?', 'radio', $yn, 1, 140],
            ['E. Commercial Integrity', 'Is there any side agreement, verbal commitment, or arrangement outside the official contract?', 'Apakah ada side agreement, verbal commitment, atau arrangement di luar kontrak resmi?', 'radio', $ny, 1, 150],
            ['E. Commercial Integrity', 'Has anyone requested a fictitious invoice, split billing, an abnormal mark-up, or payment to a third party\'s account?', 'Apakah ada permintaan invoice fiktif, split billing, mark-up abnormal, atau payment ke rekening pihak lain?', 'radio', $ny, 1, 160],
            ['F. Data & Security', 'Does the project involve government data, state secrets, critical infrastructure, or restricted information?', 'Apakah project melibatkan data pemerintah, data rahasia negara, critical infrastructure, atau restricted information?', 'radio', $ny, 1, 170],
            ['F. Data & Security', 'Will external AI, public cloud, or any tool not approved by the client be used to process project data?', 'Apakah ada penggunaan external AI/public cloud/tool yang belum disetujui client untuk memproses data project?', 'radio', $ny, 1, 180],
            ['G. Sales Declaration', 'I declare that all information above is true and that I am not aware of any compliance violation, bribery, conflict of interest, or illegal activity related to this opportunity.', 'Saya menyatakan bahwa seluruh informasi di atas benar dan saya tidak mengetahui adanya pelanggaran compliance, bribery, conflict of interest, ataupun aktivitas ilegal terkait opportunity ini.', 'radio', ['I agree'], 1, 190],
        ];

        $now = now();
        foreach ($rows as [$section, $en, $id, $type, $options, $required, $order]) {
            DB::table('sales_compliance_questions')->insert([
                'section' => $section, 'text_en' => $en, 'text_id' => $id, 'type' => $type,
                'options' => json_encode($options), 'is_required' => (bool) $required, 'sort_order' => $order,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function seedDefaultStep(): void
    {
        if (DB::table('sales_compliance_approval_steps')->exists()) {
            return;
        }

        // Dicari lewat NAMA: id role bisa berbeda antara lokal dan produksi.
        $roleId = DB::table('employee_role')->where('name', 'HO Legal Head')->value('id')
            ?? DB::table('employee_role')->where('name', 'EC Administrator')->value('id');

        DB::table('sales_compliance_approval_steps')->insert([
            'module' => 'sales_compliance', 'order_seq' => 1, 'name' => 'Compliance Review',
            'approver_type' => 'role', 'approver_role_id' => $roleId, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
