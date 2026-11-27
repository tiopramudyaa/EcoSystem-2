<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen (CR Form, SoW, WO, ...) yang dikirim ke customer bersama proposal
 * Customer Mandays. Dokumen dipilih dari Deliverables ticket saat Send to
 * Customer; doc type, nama file, dan link disalin (snapshot) supaya riwayat
 * versi tetap utuh walau deliverable-nya kemudian dihapus.
 *
 * Juga menambahkan doc type SoW dan WO ke master Document Type (CR Form sudah ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_mandays_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_mandays_id')->constrained('customer_mandays')->cascadeOnDelete();
            $table->unsignedBigInteger('ticket_deliverable_id')->nullable();
            $table->string('doc_type', 50);
            $table->string('file_name', 255)->nullable();
            $table->text('file_url')->nullable();
            $table->timestamps();

            $table->index('ticket_deliverable_id');
        });

        $nextSeq = (int) (DB::table('deliverable_document_types')->max('order_seq') ?? 0);
        foreach (['SoW' => 'Statement of Work', 'WO' => 'Work Order'] as $name => $description) {
            if (DB::table('deliverable_document_types')->where('name', $name)->exists()) {
                continue;
            }
            DB::table('deliverable_document_types')->insert([
                'name'        => $name,
                'description' => $description,
                'is_active'   => true,
                'order_seq'   => ++$nextSeq,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_mandays_documents');
        DB::table('deliverable_document_types')->whereIn('name', ['SoW', 'WO'])->delete();
    }
};
