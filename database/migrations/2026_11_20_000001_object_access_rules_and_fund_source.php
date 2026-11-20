<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tiga tambahan untuk konsep Object dan dana. Seluruhnya aditif dan punya down().
 *
 *  1. Aturan akses: siapa yang boleh membebankan biaya ke sebuah project atau tiket.
 *     - object_flow_settings.access_project / access_support: aturan bawaan (everyone | team | lead).
 *     - object_access_rules: pengecualian per project atau per tiket.
 *  2. Sumber dana kantong: `source` manual atau mengikuti rencana biaya project
 *     (delivery_project_costs, hanya DIBACA, tidak pernah ditulis).
 *  3. Keamanan data: satu entri dana per baris dokumen (unique), supaya persetujuan
 *     bersamaan tidak dapat menggandakan pengurangan dana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('object_flow_settings', function (Blueprint $table) {
            // team = lead dan anggota objeknya. lead = hanya lead. everyone = semua karyawan.
            $table->string('access_project', 10)->default('team');
            $table->string('access_support', 10)->default('team');
        });

        Schema::create('object_access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('object_type', 10);          // project | support
            $table->unsignedBigInteger('ref_id');       // delivery_projects.id atau ticket.ticket_id
            $table->string('ref_label', 200)->nullable();
            $table->string('mode', 10);                 // everyone | team | lead
            $table->string('notes', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['object_type', 'ref_id'], 'object_access_rule_unique');
        });

        Schema::table('object_fund_pools', function (Blueprint $table) {
            // manual | project_plan. project_plan hanya untuk Object bertipe project.
            $table->string('source', 15)->default('manual');
            // Baris rencana biaya (delivery_project_costs.id) yang diikuti; kosong = dicocokkan lewat nama kategori.
            $table->unsignedBigInteger('source_cost_id')->nullable();
        });

        // Entri CA tidak punya baris item: 0 menggantikan NULL supaya kunci unik berlaku.
        DB::table('object_fund_entries')->whereNull('item_id')->update(['item_id' => 0]);

        Schema::table('object_fund_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->default(0)->nullable(false)->change();
            $table->unique(['document_type', 'document_id', 'item_id'], 'object_fund_entry_doc_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('object_fund_entries', function (Blueprint $table) {
            $table->dropUnique('object_fund_entry_doc_item_unique');
        });

        Schema::table('object_fund_pools', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_cost_id']);
        });

        Schema::dropIfExists('object_access_rules');

        Schema::table('object_flow_settings', function (Blueprint $table) {
            $table->dropColumn(['access_project', 'access_support']);
        });
    }
};
