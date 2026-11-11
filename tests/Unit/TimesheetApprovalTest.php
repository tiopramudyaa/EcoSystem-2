<?php

namespace Tests\Unit;

use App\Models\Timesheet;
use App\Support\TimesheetApproval;
use Tests\TestCase;

/**
 * Izin approve / reject timesheet per tipe (Menu Access: timesheet.approve.*).
 * Test ini tidak menyentuh database.
 */
class TimesheetApprovalTest extends TestCase
{
    private function make(array $attrs): Timesheet
    {
        return (new Timesheet())->forceFill($attrs);
    }

    public function test_type_is_derived_from_the_same_columns_as_the_ui(): void
    {
        $this->assertSame('project', TimesheetApproval::typeOf($this->make(['delivery_projects_id' => 7])));
        $this->assertSame('non_project', TimesheetApproval::typeOf($this->make(['is_non_project' => true])));
        $this->assertSame('support', TimesheetApproval::typeOf($this->make(['ticket_id' => 55])));
        $this->assertSame('office', TimesheetApproval::typeOf($this->make([])));
        // a project entry wins even if the flag were set by mistake
        $this->assertSame('project', TimesheetApproval::typeOf($this->make(['delivery_projects_id' => 7, 'is_non_project' => true])));
    }

    public function test_every_type_has_its_own_permission_and_label(): void
    {
        $this->assertSame(['project', 'non_project', 'support', 'office'], array_keys(TimesheetApproval::PERMISSIONS));
        $this->assertSame(array_keys(TimesheetApproval::PERMISSIONS), array_keys(TimesheetApproval::LABELS));
        $this->assertCount(4, array_unique(TimesheetApproval::PERMISSIONS));
    }

    public function test_permissions_are_registered_by_the_seeder_and_the_migration(): void
    {
        $seeder    = file_get_contents(base_path('database/seeders/MenuSeeder.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_11_12_000002_add_timesheet_approve_permissions.php'));

        foreach (TimesheetApproval::PERMISSIONS as $slug) {
            $this->assertStringContainsString("'{$slug}'", $seeder, "{$slug} is missing from MenuSeeder");
            $this->assertStringContainsString("'{$slug}'", $migration, "{$slug} is missing from the migration");
        }
    }

    public function test_constrain_limits_the_query_to_the_given_types(): void
    {
        $sql = TimesheetApproval::constrain(Timesheet::query(), ['non_project'])->toSql();
        $this->assertStringContainsString('is_non_project', $sql);
        $this->assertStringNotContainsString('ticket_id', $sql);

        $sql = TimesheetApproval::constrain(Timesheet::query(), ['support'])->toSql();
        $this->assertStringContainsString('ticket_id', $sql);
        $this->assertStringNotContainsString('is_non_project', $sql);

        // office must exclude Non Project, which also has neither a project nor a ticket
        $sql = TimesheetApproval::constrain(Timesheet::query(), ['office'])->toSql();
        $this->assertStringContainsString('is_non_project', $sql);

        // no permission at all means no rows
        $this->assertStringContainsString('1 = 0', TimesheetApproval::constrain(Timesheet::query(), [])->toSql());
    }

    public function test_the_controller_no_longer_hardcodes_roles_for_approval(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/TimesheetController.php'));

        $this->assertStringNotContainsString('Only managers can approve', $source);
        $this->assertStringNotContainsString('Only managers can reject', $source);
        $this->assertStringContainsString('TimesheetApproval::canApprove', $source);
    }
}
