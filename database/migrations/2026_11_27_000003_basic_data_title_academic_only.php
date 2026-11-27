<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Basic Data "Title" now holds academic titles only (Dr., Prof.). Mr., Mrs. and Ms. are forms of address that
 * repeat the Gender field, so they are switched off in the list and cleared from employees (gender keeps the information).
 */
return new class extends Migration {
    public function up(): void
    {
        $redundant = ['Mr.', 'Mrs.', 'Ms.'];

        if (Schema::hasTable('employee_field_options')) {
            DB::table('employee_field_options')
                ->where('section', 'basic-data')->where('field', 'title')->whereIn('value', $redundant)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }

        if (Schema::hasTable('employee_basic_data')) {
            DB::table('employee_basic_data')->whereIn('title', $redundant)->update(['title' => null]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_field_options')) {
            DB::table('employee_field_options')
                ->where('section', 'basic-data')->where('field', 'title')->whereIn('value', ['Mr.', 'Mrs.', 'Ms.'])
                ->update(['is_active' => true, 'updated_at' => now()]);
        }
    }
};
