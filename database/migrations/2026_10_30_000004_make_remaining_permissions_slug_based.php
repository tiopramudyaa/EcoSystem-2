<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * The last controllers and views that decided by hard-coded role IDs. Each slug starts with the roles that could already do the thing
 * (matched by NAME), so nobody gains or loses anything today.
 *
 * Existing slugs now used instead of a role check: control-center.activity-log / audit-log / sessions / failed-jobs / backup (the
 * Control Center APIs), ticket.manage, ticket.delete-any-note, ticket.review-mandays, ticket.approve-resolution-days,
 * ticket.eligible-ticket-member.
 */
return new class extends Migration
{
    /** slug => [parent, label, roles besides EC Administrator] */
    private function slugs(): array
    {
        return [
            'control-center.api-docs'         => ['control-center', 'Control Center · API documentation', []],
            'timesheet.import'                => ['calendar.timesheets', 'Timesheet · Import timesheets (heads)', ['Delivery Project Head', 'Delivery Support Head', 'Delivery RPMO Head']],
            'delivery-support.ticket-links'   => ['tickets.inbox', 'Delivery support · See all linked tickets & remove a ticket link', []],
            'email.mailbox'                   => ['tickets.inbox', 'Company mailbox · Read, process & reprocess mail', ['Delivery Support User', 'Delivery Support Service Helpdesk', 'Delivery RPMO Head']],
        ];
    }

    public function up(): void
    {
        $seq = 400;
        foreach ($this->slugs() as $slug => [$parent, $label, $roles]) {
            if (MenuRegistrar::register($parent, [$slug => $label], $seq++)) {
                MenuRegistrar::grantToAdminAndRoles([$slug], $roles);
            }
        }
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys($this->slugs()));
    }
};
