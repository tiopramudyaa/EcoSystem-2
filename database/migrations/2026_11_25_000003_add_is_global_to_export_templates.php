<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * export_templates.is_global: template Personal (false, hanya pemiliknya) atau
 * Global (true, bisa dipakai siapa pun yang boleh membuka export itu). Template
 * lama menjadi Personal. Pemilik (employee_id) tetap satu-satunya yang boleh
 * mengubahnya; EC Administrator boleh menghapus template Global siapa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('export_templates', 'is_global')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->boolean('is_global')->default(false)->after('name');
                $table->index(['export_type', 'is_global'], 'export_templates_type_global_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('export_templates', 'is_global')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->dropIndex('export_templates_type_global_index');
                $table->dropColumn('is_global');
            });
        }
    }
};
