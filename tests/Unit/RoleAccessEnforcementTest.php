<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Izin fungsi di Control Center > Role Access harus benar-benar dipaksakan backend,
 * bukan hanya menyembunyikan tombol. Test ini menjaga agar tiap slug tetap diperiksa di
 * controller yang menjalankan aksinya, dan agar aksi itu tidak kembali memakai daftar role.
 */
class RoleAccessEnforcementTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function enforcedSlugs(): array
    {
        return [
            'take ticket'          => ['TicketController', 'ticket.take'],
            'manage members'       => ['TicketController', 'ui.ticket.manage-members'],
            'create delivery sup.' => ['TicketController', 'ticket.assign-delivery-support'],
            'staging approve'      => ['StagingTicketController', 'staging.approve'],
            'staging reject'       => ['StagingTicketController', 'staging.reject'],
            'create event'         => ['CalendarController', 'calendar.events.create'],
            'create timesheet'     => ['TimesheetController', 'timesheet.create'],
            'view credential'      => ['CustomerCredentialController', 'ticket.view-credential'],
            'view credential (cs)' => ['CustomerCredentialController', 'customer.section.credential.view'],
            'propose customer md'  => ['MandaysController', 'ticket.propose-mandays-customer'],
            'propose resolution'   => ['MandaysController', 'ticket.propose-mandays-resolution'],
            'save draft'           => ['MandaysController', 'ticket.review-mandays.save-draft'],
        ];
    }

    /**
     * @dataProvider enforcedSlugs
     */
    public function test_controller_checks_the_slug(string $controller, string $slug): void
    {
        $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));

        $this->assertStringContainsString(
            "'{$slug}'",
            $source,
            "{$controller} no longer checks Role Access slug {$slug}"
        );
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function gatedRoutes(): array
    {
        return [
            'dropdown config writes' => ['routes/api.php', 'menu:management.employee.dropdown-settings.manage'],
            'project type writes'    => ['routes/api.php', 'menu:management.delivery.project'],
            'support type writes'    => ['routes/api.php', 'menu:management.delivery.support'],
            'support data endpoints' => ['routes/delivery-support.php', 'menu:delivery-support.activities.view'],
            'support assign ticket'  => ['routes/delivery-support.php', 'menu:ticket.assign-delivery-support'],
        ];
    }

    /**
     * @dataProvider gatedRoutes
     */
    public function test_route_carries_menu_middleware(string $file, string $middleware): void
    {
        $this->assertStringContainsString($middleware, file_get_contents(base_path($file)));
    }

    public function test_sla_policies_follow_the_delivery_support_slugs_too(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/SlaController.php'));

        $this->assertStringContainsString("'delivery-support.sla.manage'", $source);
        $this->assertStringContainsString("'delivery-support.sla.view'", $source);
    }

    public function test_take_ticket_and_member_management_do_not_use_fixed_role_lists(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/TicketController.php'));

        $this->assertStringNotContainsString('Only employees can take tickets', $source);
        $this->assertStringNotContainsString('Only Admin, Helpdesk, or the assigned PIC can add members', $source);
        $this->assertStringNotContainsString('Only Admin, Helpdesk, and RPMO can create delivery supports', $source);
    }

    public function test_mandays_notifications_follow_role_access_not_role_ids(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/MandaysController.php'));

        $this->assertStringNotContainsString('RoleId::DELIVERY_HELPDESK->value]', $source);
        $this->assertStringContainsString("['ticket.review-mandays']", $source);
    }
}
