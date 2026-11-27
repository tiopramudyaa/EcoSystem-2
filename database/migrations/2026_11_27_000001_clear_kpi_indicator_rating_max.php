<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each KPI template has one rating scale. A per-indicator rating_max (for example 3 stars on some rows of a
 * 5-star template) made the star count differ inside one form, so the column is cleared and no longer used.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('kpi_indicators', 'rating_max')) {
            DB::table('kpi_indicators')->whereNotNull('rating_max')->update(['rating_max' => null]);
        }
    }

    public function down(): void
    {
        // The old per-indicator values are not recoverable.
    }
};
