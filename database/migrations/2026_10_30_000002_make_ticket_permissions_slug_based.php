<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * TicketController used to decide by hard-coded role IDs (App\Enums\RoleId). It now reads these function slugs, ticked per role in
 * Management → Roles → Menu Access (Tickets). Each slug starts with the roles that could already do the thing, resolved by NAME, so
 * nobody gains or loses anything today; the owner can change any of them afterwards.
 *
 * Also used by the controller, already existing: ticket.all-tickets, tickets.inbox, ticket.take, ticket.assign-delivery-support,
 * ui.ticket.edit-additional-info, ui.ticket.btn-create.
 */
return new class extends Migration
{
    private const ADMIN = 'EC Administrator';
    private const HEAD = 'Delivery Support Head';
    private const HELPDESK = 'Delivery Support Service Helpdesk';
    private const RPMO = 'Delivery RPMO Head';
    private const MANAGER = 'Delivery Support Manager';
    private const SUPPORT_USER = 'Delivery Support User';

    /** slug => [label, extra roles besides EC Administrator, include EC Administrator] */
    private function slugs(): array
    {
        return [
            'ticket.manage'                 => ['Tickets · Manage (edit fields, change status, add members)', [self::HEAD, self::HELPDESK, self::RPMO], true],
            'ticket.create-direct'          => ['Tickets · Create directly (skip staging)', [], true],
            'ticket.external-unrestricted'  => ['Tickets · External employees: see & handle all tickets', [], true],
            'ticket.edit-man-days'          => ['Tickets · Edit man-days', [], true],
            'ticket.confirm-assignment'     => ['Tickets · Confirm assignment (pending confirmations)', [], true],
            'ticket.process-member-changes' => ['Tickets · Review & decide member change requests', [self::HEAD], true],
            'ticket.request-member-change'  => ['Tickets · Request member change / removal', [self::SUPPORT_USER, self::HELPDESK, self::RPMO], false],
            'ticket.remove-member'          => ['Tickets · Remove a member directly', [self::HEAD, self::MANAGER, self::HELPDESK, self::RPMO], true],
            'ticket.export'                 => ['Tickets · Export to Excel', [self::HEAD, self::HELPDESK], true],
            // Restriction, not a power: holders need a DSM qualification before opening or taking tickets. Admin is deliberately NOT in it.
            'ticket.qualification-required' => ['Tickets · Requires DSM qualification', [self::SUPPORT_USER], false],
        ];
    }

    public function up(): void
    {
        $labels = array_map(fn ($d) => $d[0], $this->slugs());

        if (!MenuRegistrar::register('tickets.inbox', $labels, 200)) {
            return;
        }

        $adminRoleId = MenuRegistrar::adminRoleId();

        foreach ($this->slugs() as $slug => [, $roles, $withAdmin]) {
            MenuRegistrar::grantToAdminAndRoles([$slug], $roles);

            if (!$withAdmin) {
                $menuId = DB::table('menu')->where('slug', $slug)->value('id');
                DB::table('role_menu')->where('menu_id', $menuId)->where('role_id', $adminRoleId)->delete();
                MenuRegistrar::flushPermCache(collect([$adminRoleId]));
            }
        }
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys($this->slugs()));
    }
};
