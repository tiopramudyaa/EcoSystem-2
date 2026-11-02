<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\Employee;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Header quick search (Ctrl+K): employees, tickets and delivery projects in one call.
 *
 * Each group is returned only when the signed-in employee holds the menu that lists that data, so the palette never
 * reveals a record the person could not open from the sidebar.
 */
class GlobalSearchController extends Controller
{
    private const LIMIT = 6;

    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $employee = Employee::find(session('user.id'));

        if ($employee === null || mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';
        $groups = [];

        if ($employee->canAccessMenu('master.employee')) {
            $items = Employee::query()
                ->with('basicData:basic_data_id,employee_id,nick_name,first_name,last_name')
                ->where(fn ($q) => $q->where('eci', 'like', $like)
                    ->orWhereHas('basicData', fn ($b) => $b->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('nick_name', 'like', $like)))
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (Employee $e) => [
                    'title' => $e->fullNameLabel(),
                    'hint'  => $e->eci,
                    'url'   => route('master.employee.detail', $e->employee_id),
                ]);
            $groups[] = ['label' => 'Employees', 'icon' => 'fa-user', 'items' => $items];
        }

        if ($employee->canAccessMenu('tickets.inbox')) {
            $items = Ticket::query()
                ->where(fn ($q) => $q->where('ticket_number', 'like', $like)->orWhere('description', 'like', $like))
                ->latest('ticket_id')
                ->limit(self::LIMIT)
                ->get(['ticket_id', 'ticket_number', 'description', 'status'])
                ->map(fn (Ticket $t) => [
                    'title' => (string) $t->ticket_number,
                    'hint'  => trim(\Illuminate\Support\Str::limit(strip_tags((string) $t->description), 70) . ' · ' . $t->status, ' ·'),
                    'url'   => route('ticket.show', $t->ticket_id),
                ]);
            $groups[] = ['label' => 'Tickets', 'icon' => 'fa-ticket-alt', 'items' => $items];
        }

        if ($employee->canAccessMenu('delivery.project')) {
            $items = DeliveryProject::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('io_number', 'like', $like))
                ->latest('id')
                ->limit(self::LIMIT)
                ->get(['id', 'name', 'io_number'])
                ->map(fn (DeliveryProject $p) => [
                    'title' => $p->name,
                    'hint'  => $p->io_number,
                    'url'   => route('projects.show', $p->id),
                ]);
            $groups[] = ['label' => 'Projects', 'icon' => 'fa-project-diagram', 'items' => $items];
        }

        return response()->json(['groups' => array_values(array_filter($groups, fn ($g) => $g['items']->isNotEmpty()))]);
    }
}
