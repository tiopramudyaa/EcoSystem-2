<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Map coordinates of the project's delivery location, used to cross-check the
 * GPS captured on project timesheets against where the project actually is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->decimal('location_latitude', 10, 7)->nullable()->after('location_street');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->dropColumn(['location_latitude', 'location_longitude']);
        });
    }
};
