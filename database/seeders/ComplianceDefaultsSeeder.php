<?php

namespace Database\Seeders;

use App\Models\Compliance\ComplianceOption;
use App\Models\Compliance\ComplianceReportStatus;
use Illuminate\Database\Seeder;

/**
 * The starting choices of Compliance Management → Settings: the statuses a compliance report moves through and the dropdown choices of
 * the Content tab. Reference data the module needs to work (not demo data), so it runs on a clean install. Safe to run again: a choice that
 * exists is left as the admin set it; only missing ones are added.
 *
 *   php artisan db:seed --class="Database\Seeders\ComplianceDefaultsSeeder"
 */
class ComplianceDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        // key => [label, tone, closes the report]. `new` is where every report starts and cannot be deleted.
        $statuses = [
            'new' => ['New', 'red', false],
            'in_review' => ['In review', 'amber', false],
            'investigating' => ['Investigating', 'violet', false],
            'closed' => ['Closed', 'gray', true],
        ];
        $order = 1;
        foreach ($statuses as $key => [$label, $tone, $final]) {
            ComplianceReportStatus::firstOrCreate(['key' => $key], ['label' => $label, 'tone' => $tone, 'is_final' => $final, 'is_active' => true, 'sort_order' => $order++]);
        }

        // list => [key => [label, tone, built in]]. Built-in choices are the ones the pages depend on: renamed and recoloured, never deleted.
        $options = [
            'rule_severity' => ['info' => ['Info', 'gray', true], 'warning' => ['Warning', 'amber', true], 'critical' => ['Critical', 'red', true]],
            'alert_color' => ['danger' => ['Red (danger)', 'red', true], 'warning' => ['Amber (warning)', 'amber', true], 'info' => ['Gray (info)', 'gray', true]],
            'session_status' => ['upcoming' => ['Upcoming', 'violet', true], 'completed' => ['Completed', 'green', true], 'cancelled' => ['Cancelled', 'gray', false]],
            'session_period' => ['morning' => ['Morning', 'amber', true], 'afternoon' => ['Afternoon', 'blue', true]],
        ];
        foreach ($options as $list => $choices) {
            $order = 1;
            foreach ($choices as $key => [$label, $tone, $builtIn]) {
                ComplianceOption::firstOrCreate(['list' => $list, 'key' => $key], ['label' => $label, 'tone' => $tone, 'sort_order' => $order++, 'is_active' => true, 'is_system' => $builtIn]);
            }
        }
    }
}
