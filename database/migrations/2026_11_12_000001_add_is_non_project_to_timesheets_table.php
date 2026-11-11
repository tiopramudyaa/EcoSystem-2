<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Non Project" timesheets: same inputs as a Project timesheet (date = today, GPS,
 * presence, location, activity detail) but not tied to any project or activity.
 * They have no project and no ticket, so they cannot be told apart from Office
 * entries without an explicit flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->boolean('is_non_project')->default(false)->after('delivery_projects_id');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('is_non_project');
        });
    }
};
