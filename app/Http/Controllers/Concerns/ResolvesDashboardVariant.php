<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;

/**
 * Which ticket dashboard a person gets, and which extra cards it shows, follows the `dashboard.*` function slugs ticked for their
 * role(s) in Management → Roles → Menu Access — no role ID is compared. Shared by the web dashboard and the Lite API.
 */
trait ResolvesDashboardVariant
{
    private ?Employee $dashboardEmployee = null;

    private function dashboardCan(?array $user, string $slug): bool
    {
        if (($user['type'] ?? 'employee') !== 'employee' || empty($user['id'])) {
            return false;
        }

        $this->dashboardEmployee ??= Employee::find((int) $user['id']);

        return $this->dashboardEmployee?->hasPermission($slug) ?? false;
    }

    /** @return 'full'|'helpdesk'|'manager'|'mine'|null the first dashboard the person holds, most specific first (so a baseline grant never hides a role's own) */
    private function dashboardVariant(?array $user): ?string
    {
        foreach (['helpdesk', 'manager', 'mine', 'full'] as $variant) {
            if ($this->dashboardCan($user, "dashboard.tickets.{$variant}")) {
                return $variant;
            }
        }

        return null;
    }
}
