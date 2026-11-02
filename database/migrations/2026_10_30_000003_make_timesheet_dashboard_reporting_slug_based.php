<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Timesheets, the dashboards, the support-timesheet report and the Lite ticket API used to decide by hard-coded role IDs
 * (App\Enums\RoleId). They now read these function slugs, ticked per role in Management → Roles → Menu Access.
 *
 * Each slug starts with the roles that could already do the thing, resolved by NAME (never by ID — the enum's IDs do not match this
 * database), so nobody gains or loses anything today. `admin` says whether EC Administrator is part of the starting grant: it is for
 * powers it already had, and not for views that were scoped to one head (the head sees "their" type of timesheet, the admin sees all).
 *
 * "EC User" is deliberately NOT granted the organisation-wide dashboard: in this database nearly every employee holds that baseline role, so the
 * grant would show every employee all tickets (with customer names). The old code only matched it as the *primary* role.
 *
 * Already existing and now used by the controllers: tickets.staging, reporting.md-recap, reporting.resolution-days,
 * rpmo.periods.manage-global (closing a period), ticket.external-unrestricted.
 */
return new class extends Migration
{
    private const ADMIN = 'EC Administrator';
    private const HOP = 'Delivery Project Head';
    private const HOS = 'Delivery Support Head';
    private const RPMO = 'Delivery RPMO Head';
    private const HELPDESK = 'Delivery Support Service Helpdesk';
    private const MANAGER = 'Delivery Support Manager';
    private const SU = 'Delivery Support User';
    private const PU = 'Delivery Project User';
    private const ECU = 'EC User';

    /** slug => [parent, label, roles, admin included] */
    private function slugs(): array
    {
        $ts = 'calendar.timesheets';
        $db = 'dashboard';

        return [
            // ── Timesheets ──────────────────────────────────────────────────────────────────────────
            'timesheet.approve'          => [$ts, 'Timesheet · Review, approve, reject & export', [self::HOP, self::HOS, self::RPMO], true],
            'timesheet.manage-others'    => [$ts, "Timesheet · Edit, delete & submit other people's entries", [self::HOP, self::HOS, self::RPMO], true],
            'timesheet.view-all'         => [$ts, 'Timesheet · See every entry', [], true],
            'timesheet.view-support'     => [$ts, 'Timesheet · See all support entries (and be notified of their submission)', [self::HOS], false],
            'timesheet.view-project'     => [$ts, 'Timesheet · See all project entries (and be notified of their submission)', [self::HOP], false],
            'timesheet.view-office'      => [$ts, 'Timesheet · See all office entries (and be notified of their submission)', [self::RPMO], false],
            'timesheet.view-record'      => [$ts, 'Timesheet · Open any single entry', [self::HOS, self::HOP, self::HELPDESK], true],
            'timesheet.bypass-period'    => [$ts, 'Timesheet · Submit outside the open period', [self::RPMO], true],
            'timesheet.type.support'     => [$ts, 'Timesheet · May enter support timesheets', [self::SU, self::HELPDESK], true],
            'timesheet.type.project'     => [$ts, 'Timesheet · May enter project timesheets', [self::PU, self::HELPDESK], true],
            'timesheet.type.office'      => [$ts, 'Timesheet · May enter office timesheets', [self::ECU, self::HELPDESK], true],
            // ── Dashboard ───────────────────────────────────────────────────────────────────────────
            'dashboard.tickets.full'     => [$db, 'Dashboard · Organisation-wide ticket dashboard', [self::HOS], true],
            'dashboard.tickets.helpdesk' => [$db, 'Dashboard · Helpdesk ticket dashboard', [self::HELPDESK], false],
            'dashboard.tickets.manager'  => [$db, 'Dashboard · Managed-deliveries ticket dashboard', [self::MANAGER], false],
            'dashboard.tickets.mine'     => [$db, 'Dashboard · My tickets dashboard (PIC / member)', [self::SU], false],
            'dashboard.staging-pending'  => [$db, 'Dashboard · Show staging tickets awaiting validation', [self::HELPDESK], true],
            'dashboard.timesheet-pending' => [$db, 'Dashboard · Show timesheets awaiting approval', [self::HOS], false],
            // ── Reporting ───────────────────────────────────────────────────────────────────────────
            'reporting.timesheet-support.view-all' => ['reporting', "Reporting · Support timesheet report: everyone's rows & export", [self::HOS], true],
            // ── Tickets (Lite API) ──────────────────────────────────────────────────────────────────
            'ticket.view-hidden'         => ['tickets.inbox', 'Tickets · Open hidden tickets', [], true],
            'ticket.delete-any-note'     => ['tickets.inbox', "Tickets · Delete other people's notes", [], true],
            'ticket.update-status'       => ['tickets.inbox', 'Tickets · Change status (Lite app)', [self::HOS, self::HELPDESK, self::RPMO, self::MANAGER], true],
            // Restriction, not a power: holders see only unassigned tickets in the Lite app.
            'ticket.lite-unassigned-only' => ['tickets.inbox', 'Tickets · Lite app shows only unassigned tickets', [self::SU], false],
        ];
    }

    public function up(): void
    {
        $adminRoleId = MenuRegistrar::adminRoleId();
        $seq = 300;

        foreach ($this->slugs() as $slug => [$parent, $label, $roles, $withAdmin]) {
            if (!MenuRegistrar::register($parent, [$slug => $label], $seq++)) {
                continue;
            }

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
