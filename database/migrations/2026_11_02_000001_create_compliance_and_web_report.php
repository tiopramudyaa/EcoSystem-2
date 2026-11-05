<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compliance (Legal) and Web Report, ported from the old compliance.eclectic.co.id and lapor.eclectic.co.id sites. Schema and menu rows only —
 * the starting choices (report statuses, dropdown choices) are in Database\Seeders\ComplianceDefaultsSeeder, example data in ComplianceDemoSeeder.
 *
 * Compliance
 *   compliance_categories · _articles · _rules · _alerts · _sessions   what My Compliance shows (managed in Compliance Management → Content)
 *   compliance_report_statuses · compliance_options                    the choices an admin manages in Compliance Management → Settings
 * Web Report — ONE table for two channels: `web` (the public form, code WR-…, handled in Control Center) and `compliance` (sent from
 *   My Compliance, code CL-…, handled in Compliance Management → Reports). Name, contact, subject, story and internal note are stored
 *   encrypted (Eloquent `encrypted` cast), so they are TEXT columns; evidence goes to the private disk. `employee_id` links a report to
 *   the person who chose to give their name; an anonymous one stays unlinked.
 *
 * Menu rows (a standing MenuRegistrar rule: they start for EC Administrator only; Admin ticks them per role afterwards):
 *   legal.compliance            V C E D  Compliance Management (Dashboard · Reports · Content · Settings)
 *   control-center.web-reports  V E      Web Report Management (read the inbox, change status / notes; no Create, no Delete)
 *   ess.my_compliance           —        My Compliance, open to every signed-in user: a locked "Global ESS" row, no gate
 * On a clean install the menu calls are skipped (the parents do not exist yet); MenuRegistrySeeder adds the rows from menu_registry.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('slug', 120)->unique();
            $t->string('icon', 16)->nullable();
            $t->string('color', 9)->default('#0F7B6C');
            $t->string('description', 400)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('compliance_articles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('compliance_categories')->cascadeOnDelete();
            $t->string('title', 250);
            $t->string('summary', 500)->nullable();
            $t->longText('content');                       // sanitised HTML (App\Support\Compliance\HtmlSanitizer)
            $t->string('tags', 250)->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('views')->default(0);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->index(['is_active', 'is_featured'], 'idx_comp_art_active');
        });

        // The columns that hold a choice of a managed dropdown (compliance_options) store its key: up to 40 characters.
        Schema::create('compliance_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained('compliance_categories')->nullOnDelete();
            $t->string('type', 4);                         // do | dont
            $t->string('title', 250);
            $t->text('description')->nullable();
            $t->string('severity', 40)->default('info');  // option list rule_severity
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('compliance_alerts', function (Blueprint $t) {
            $t->id();
            $t->string('icon', 16)->nullable();
            $t->string('title', 200);
            $t->string('description', 500)->nullable();
            $t->string('color_type', 40)->default('info'); // option list alert_color
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('compliance_sessions', function (Blueprint $t) {
            $t->id();
            $t->string('title', 250);
            $t->text('description')->nullable();
            $t->date('session_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->unsignedSmallInteger('duration_min')->default(30);
            $t->string('session_type', 40)->default('morning');  // option list session_period
            $t->string('status', 40)->default('upcoming');       // option list session_status
            $t->string('facilitator', 150)->nullable();
            $t->string('location', 200)->nullable();
            $t->text('notes')->nullable();
            $t->string('meeting_url', 500)->nullable();
            $t->string('recording_url', 500)->nullable();
            $t->string('material_url', 500)->nullable();
            $t->timestamps();
            $t->index(['status', 'session_date'], 'idx_comp_sess');
        });

        Schema::create('compliance_report_statuses', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();          // stored on web_reports.status
            $t->string('label', 60);
            $t->string('tone', 10)->default('gray');  // red · amber · violet · blue · green · gray
            $t->boolean('is_final')->default(false);  // reaching it closes the report (closed_at, no more follow-up date)
            $t->boolean('is_active')->default(true);  // inactive: reports keep it, but it can no longer be picked
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('compliance_options', function (Blueprint $t) {
            $t->id();
            $t->string('list', 30);                   // rule_severity | alert_color | session_status | session_period
            $t->string('key', 40);                    // stored on the row that uses the choice
            $t->string('label', 60);
            $t->string('tone', 10)->default('gray');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);  // inactive: rows keep it, but it can no longer be picked
            $t->boolean('is_system')->default(false); // the pages depend on it: can be renamed and recoloured, not deleted
            $t->timestamps();
            $t->unique(['list', 'key']);
        });

        Schema::create('web_reports', function (Blueprint $t) {
            $t->id();
            $t->string('tracking_code', 16)->unique();     // shown once to the reporter; lets them check status without an identity
            $t->string('channel', 10)->default('web');     // web (public form, WR-…) | compliance (My Compliance, CL-…)
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->text('reporter_name')->nullable();         // encrypted
            $t->text('reporter_contact')->nullable();      // encrypted
            $t->text('reported_name');                     // encrypted
            $t->longText('chronology');                    // encrypted
            $t->string('evidence_path', 255)->nullable();  // private disk
            $t->string('evidence_name', 255)->nullable();
            $t->string('status', 40)->default('new');      // web: config web-report.statuses · compliance: compliance_report_statuses
            $t->text('internal_note')->nullable();         // encrypted, never shown to the reporter
            $t->text('public_note')->nullable();           // shown on the status page
            $t->string('lang', 2)->default('en');
            $t->timestamp('due_at')->nullable();           // submitted + the follow-up days
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at'], 'idx_wb_status');
            $t->index(['channel', 'status', 'created_at'], 'idx_wr_channel');
            $t->index('employee_id', 'idx_wr_employee');
        });

        Schema::create('web_report_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('web_reports')->cascadeOnDelete();
            $t->unsignedBigInteger('actor_id')->nullable();   // employee id; null = the system / the reporter
            $t->string('action', 30);                         // submitted | viewed | status | note | evidence_viewed | exported
            $t->string('detail', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        MenuRegistrar::register('legal', ['legal.compliance' => 'Compliance Management'], 10, 'page');
        MenuRegistrar::register('control-center', ['control-center.web-reports' => 'Web Report Management'], 9, 'page');
        if (MenuRegistrar::register('general', ['ess.my_compliance' => 'My Compliance'], 91, 'page')) {
            DB::table('menu')->where('slug', 'ess.my_compliance')->update(['parent_id' => null, 'order_seq' => 7, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        MenuRegistrar::remove(['legal.compliance', 'control-center.web-reports', 'ess.my_compliance']);

        foreach (['web_report_events', 'web_reports', 'compliance_options', 'compliance_report_statuses', 'compliance_sessions', 'compliance_alerts', 'compliance_rules', 'compliance_articles', 'compliance_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
