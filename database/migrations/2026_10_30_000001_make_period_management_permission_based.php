<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Period Management used to decide who may do what by hard-coded role IDs (App\Enums\RoleId). It now follows what the owner sets in
 * Management → Roles, so any role can be given any part of the period lifecycle:
 *
 *   rpmo.periods                          the page itself (already existed)
 *   rpmo.periods.manage-global            create / edit dates / delete a period, open / close it globally, force-close a domain
 *   rpmo.periods.manage-domain.project    open / close the Project domain, decide Level-1 late-access requests for it
 *   rpmo.periods.manage-domain.support    the same for the Support domain
 *   rpmo.periods.approve-final            decide Level-2 late-access requests
 *
 * Which TIMESHEET DOMAIN a role is held to (and so is blocked once its domain closes) is now a setting on the role itself:
 * employee_role.period_domain = 'project' | 'support' | NULL (not period-restricted), editable in Management → Roles.
 *
 * Grants are made by role NAME so nobody loses what they could do before; they are the roles the old enum meant. Note the old enum's IDs
 * (e.g. 15 = "Delivery Project User") did not match this database (15 = "Modul Lead", the Project User is 9): the backfill below follows
 * the NAMES, so "Modul Lead" is no longer period-restricted and "Delivery Project User" now is.
 */
return new class extends Migration
{
    private const SLUGS = [
        'rpmo.periods.manage-global'         => 'Period Management · Create, edit & open/close globally',
        'rpmo.periods.manage-domain.project' => 'Period Management · Open/close Project domain & Level-1 approval',
        'rpmo.periods.manage-domain.support' => 'Period Management · Open/close Support domain & Level-1 approval',
        'rpmo.periods.approve-final'         => 'Period Management · Level-2 (final) late-access approval',
    ];

    private const DOMAIN_ROLES = [
        'project' => ['Delivery Project User', 'Delivery Project Head'],
        'support' => ['Delivery Support User', 'Delivery Support Head'],
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('employee_role', 'period_domain')) {
            Schema::table('employee_role', function (Blueprint $t) {
                $t->string('period_domain', 20)->nullable()->after('description');
            });
        }

        foreach (self::DOMAIN_ROLES as $domain => $names) {
            DB::table('employee_role')->whereIn('name', $names)->update(['period_domain' => $domain]);
        }

        if (!MenuRegistrar::register('rpmo.periods', self::SLUGS, 10)) {
            return;
        }

        MenuRegistrar::grantToAdminAndRoles(['rpmo.periods.manage-global'], ['Delivery RPMO Head']);
        MenuRegistrar::grantToAdminAndRoles(['rpmo.periods.approve-final'], ['Delivery RPMO Head']);
        MenuRegistrar::grantToAdminAndRoles(['rpmo.periods.manage-domain.project'], ['Delivery Project Head']);
        MenuRegistrar::grantToAdminAndRoles(['rpmo.periods.manage-domain.support'], ['Delivery Support Head']);
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys(self::SLUGS));

        if (Schema::hasColumn('employee_role', 'period_domain')) {
            Schema::table('employee_role', fn (Blueprint $t) => $t->dropColumn('period_domain'));
        }
    }
};
