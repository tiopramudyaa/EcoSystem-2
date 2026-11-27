<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Folder dokumen per Delivery Project (section Project Documents).
 *
 * - Folder standar (is_default = true): 01. Discover, 02. Prepare, 03. Explore,
 *   04. Realize, 05. Deploy, 06. Run, 00. Other. Tidak bisa di-rename/hapus.
 * - Folder tambahan user: nomor berurutan mulai 11 (11, 12, 13, ...).
 * - onedrive_folder_id: subfolder di folder OneDrive project, dibuat saat
 *   folder OneDrive project dibuat atau paling lambat saat upload pertama.
 *
 * documents.folder_id   : folder tempat dokumen berada (dokumen lama → 00. Other).
 * documents.onedrive_item_id : item ID file di OneDrive, dipakai untuk memindahkan
 *   file saat dokumen dipindah folder. Dokumen lama tidak punya (null).
 */
return new class extends Migration
{
    private const DEFAULTS = [
        1 => 'Discover',
        2 => 'Prepare',
        3 => 'Explore',
        4 => 'Realize',
        5 => 'Deploy',
        6 => 'Run',
        0 => 'Other',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('delivery_project_document_folders')) {
            Schema::create('delivery_project_document_folders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_projects_id');
                $table->unsignedSmallInteger('number');
                $table->string('name', 100);
                $table->boolean('is_default')->default(false);
                $table->string('onedrive_folder_id')->nullable();
                $table->timestamps();

                $table->foreign('delivery_projects_id')
                      ->references('id')->on('delivery_projects')
                      ->cascadeOnDelete();
                $table->unique(['delivery_projects_id', 'number'], 'dp_doc_folders_project_number_unique');
            });
        }

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'folder_id')) {
                $table->unsignedBigInteger('folder_id')->nullable()->after('delivery_projects_id');
                $table->foreign('folder_id', 'documents_folder_id_foreign')
                      ->references('id')->on('delivery_project_document_folders')
                      ->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'onedrive_item_id')) {
                $table->string('onedrive_item_id')->nullable()->after('link_document');
            }
        });

        // Folder standar untuk semua project yang sudah ada + dokumen lama
        // dimasukkan ke "00. Other".
        $now = now();
        foreach (DB::table('delivery_projects')->pluck('id') as $projectId) {
            $existing = DB::table('delivery_project_document_folders')
                ->where('delivery_projects_id', $projectId)
                ->pluck('number')->all();

            $rows = [];
            foreach (self::DEFAULTS as $number => $name) {
                if (!in_array($number, $existing)) {
                    $rows[] = [
                        'delivery_projects_id' => $projectId,
                        'number'               => $number,
                        'name'                 => $name,
                        'is_default'           => true,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
            }
            if ($rows) {
                DB::table('delivery_project_document_folders')->insert($rows);
            }

            $otherId = DB::table('delivery_project_document_folders')
                ->where('delivery_projects_id', $projectId)
                ->where('number', 0)
                ->value('id');

            DB::table('documents')
                ->where('delivery_projects_id', $projectId)
                ->whereNull('folder_id')
                ->update(['folder_id' => $otherId]);
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'folder_id')) {
                $table->dropForeign('documents_folder_id_foreign');
                $table->dropColumn('folder_id');
            }
            if (Schema::hasColumn('documents', 'onedrive_item_id')) {
                $table->dropColumn('onedrive_item_id');
            }
        });

        Schema::dropIfExists('delivery_project_document_folders');
    }
};
