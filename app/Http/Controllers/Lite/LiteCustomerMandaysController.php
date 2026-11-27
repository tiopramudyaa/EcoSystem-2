<?php

namespace App\Http\Controllers\Lite;

use App\Enums\RoleId;
use App\Http\Controllers\Controller;
use App\Models\CustomerMandays;
use App\Models\Employee;
use App\Models\Ticket;
use App\Services\CustomerMandaysApprovalService;
use App\Services\LiteCustomerMandaysPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Approval bertingkat Customer Mandays untuk Lite API (PWA).
 *
 * Semua keputusan (siapa boleh approve/reject, total > 0, alasan reject) ada di
 * CustomerMandaysApprovalService, sama dengan web. Controller ini hanya menambah
 * pemeriksaan versi (mandays_id + step_order) supaya approver tidak memutuskan
 * proposal yang sudah berubah sejak ia membukanya.
 */
class LiteCustomerMandaysController extends Controller
{
    public function __construct(
        private CustomerMandaysApprovalService $approvalService,
        private LiteCustomerMandaysPresenter $presenter,
    ) {
    }

    /**
     * Ticket yang proposalnya sedang menunggu giliran user ini.
     *
     * GET /api/lite/customer-mandays/approvals
     */
    public function approvals(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $page    = max((int) $request->query('page', 1), 1);
        $search  = trim((string) $request->query('search', ''));

        $proposals = $this->approvalService->pendingFor((int) $user['id']);

        $tickets = Ticket::with(['customer.basicData', 'ticketLead.basicData'])
            ->whereIn('ticket_id', $proposals->pluck('ticket_id'))
            ->whereNull('is_hidden')
            ->whereNotNull('ticket_lead_id')
            ->get()
            ->keyBy('ticket_id');

        $items = $proposals
            ->filter(fn (CustomerMandays $p) => $tickets->has($p->ticket_id))
            ->map(function (CustomerMandays $p) use ($tickets) {
                $t = $tickets[$p->ticket_id];
                return [
                    'ticket_id'     => $t->ticket_id,
                    'ticket_number' => $t->ticket_number,
                    'description'   => $t->description,
                    'ticket_type'   => $t->ticket_type,
                    'status'        => $t->status,
                    'customer_name' => $t->customer?->basicData?->name_1,
                    'pic'           => $t->ticketLead ? [
                        'employee_id'   => $t->ticketLead->employee_id,
                        'employee_name' => $t->ticketLead->basicData->nick_name
                            ?? $t->ticketLead->basicData->first_name ?? 'Unknown',
                    ] : null,
                    'mandays_id'    => $p->id,
                    'version'       => $p->version,
                    'total_mandays' => (float) $p->total_mandays,
                    'step_order'    => $p->current_step_order,
                    'waiting_step'  => $p->currentApproval()?->step_name,
                    'submitted_at'  => $p->submitted_to_customer_at?->toISOString(),
                ];
            })
            ->when($search !== '', fn ($c) => $c->filter(fn ($row) =>
                str_contains(mb_strtolower((string) $row['ticket_number']), mb_strtolower($search))
                || str_contains(mb_strtolower((string) $row['description']), mb_strtolower($search))
            ))
            ->values();

        $total = $items->count();

        return response()->json([
            'success' => true,
            'data'    => $items->forPage($page, $perPage)->values(),
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => max((int) ceil($total / $perPage), 1),
            ],
        ]);
    }

    /**
     * Detail proposal terbaru + jejak approval + apakah user boleh bertindak.
     *
     * GET /api/lite/tickets/{ticketId}/customer-mandays
     */
    public function show(Request $request, int $ticketId): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $ticket = $this->findTicket($ticketId, $user);
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        $employeeId = (int) $user['id'];
        $proposal   = $this->presenter->latestProposal($ticket);
        $gate       = $this->presenter->gate($proposal, $employeeId);

        if (!$this->presenter->canView(Employee::find($employeeId), $ticket, $gate['allowed'])) {
            return response()->json(['success' => false, 'message' => 'You do not have access to the customer mandays of this ticket.'], 403);
        }

        return response()->json([
            'success'        => true,
            'data'           => $proposal ? $this->presenter->detail($proposal) : null,
            'can_act'        => $gate['allowed'],
            'can_act_reason' => $gate['reason'],
        ]);
    }

    /**
     * Penyetuju level aktif menyetujui, boleh dengan angka MD yang diubah.
     *
     * POST /api/lite/tickets/{ticketId}/customer-mandays/approve
     */
    public function approve(Request $request, int $ticketId): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Aturan details sama dengan MandaysController::approveLevel (web).
        $validator = Validator::make($request->all(), [
            'mandays_id'         => 'required|integer',
            'step_order'         => 'required|integer',
            'notes'              => 'nullable|string|max:1000',
            'details'            => 'nullable|array|min:1',
            'details.*.activity' => 'nullable|string|max:255',
            'details.*.module'   => 'required_with:details|string|max:100',
            'details.*.mandays'  => 'required_with:details|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        [$ticket, $proposal, $error] = $this->resolveForDecision($request, $ticketId, $user);
        if ($error) {
            return $error;
        }

        $result = $this->approvalService->approve(
            $proposal,
            $ticket,
            (int) $user['id'],
            $user['name'] ?? 'Approver',
            $request->input('details'),
            $request->input('notes')
        );

        if (!$result['allowed']) {
            return response()->json(['success' => false, 'message' => $result['reason']], 422);
        }

        Log::info('LiteCustomerMandays: approved', ['ticket_id' => $ticketId, 'mandays_id' => $proposal->id, 'by' => $user['id']]);

        return response()->json([
            'success'   => true,
            'message'   => $result['final']
                ? 'Customer mandays approved.'
                : 'Approved. The proposal moves to the next approval level.',
            'completed' => $result['completed'],
            'data'      => $this->presenter->detail($this->presenter->latestProposal($ticket)),
        ]);
    }

    /**
     * Penyetuju level aktif menolak dengan alasan; proposal kembali ke PIC.
     *
     * POST /api/lite/tickets/{ticketId}/customer-mandays/reject
     */
    public function reject(Request $request, int $ticketId): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Aturan notes sama dengan MandaysController::rejectLevel (web).
        $validator = Validator::make($request->all(), [
            'mandays_id' => 'required|integer',
            'step_order' => 'required|integer',
            'notes'      => 'required|string|min:5|max:1000',
        ], [
            'notes.required' => 'Please explain why the proposal is rejected.',
            'notes.min'      => 'The reason must be at least 5 characters.',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        [$ticket, $proposal, $error] = $this->resolveForDecision($request, $ticketId, $user);
        if ($error) {
            return $error;
        }

        $result = $this->approvalService->reject(
            $proposal,
            $ticket,
            (int) $user['id'],
            $user['name'] ?? 'Approver',
            $request->input('notes')
        );

        if (!$result['allowed']) {
            return response()->json(['success' => false, 'message' => $result['reason']], 422);
        }

        Log::info('LiteCustomerMandays: rejected', ['ticket_id' => $ticketId, 'mandays_id' => $proposal->id, 'by' => $user['id']]);

        return response()->json([
            'success' => true,
            'message' => 'Proposal rejected and returned to the PIC.',
            'data'    => $this->presenter->detail($this->presenter->latestProposal($ticket)),
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Ticket + proposal terbaru, dan 409 bila proposal yang dikirim PWA bukan lagi
     * versi/level yang sedang menunggu.
     *
     * @return array{0: ?Ticket, 1: ?CustomerMandays, 2: ?JsonResponse}
     */
    private function resolveForDecision(Request $request, int $ticketId, array $user): array
    {
        $ticket = $this->findTicket($ticketId, $user);
        if (!$ticket) {
            return [null, null, response()->json(['success' => false, 'message' => 'Ticket not found.'], 404)];
        }

        $proposal = $this->presenter->latestProposal($ticket);

        $stale = !$proposal
            || (int) $proposal->id !== (int) $request->input('mandays_id')
            || $proposal->current_step_order === null
            || (int) $proposal->current_step_order !== (int) $request->input('step_order');

        if ($stale) {
            return [$ticket, $proposal, response()->json([
                'success' => false,
                'message' => 'This proposal has changed since you opened it. Reload it and review it again.',
                'data'    => $proposal ? $this->presenter->detail($proposal) : null,
            ], 409)];
        }

        return [$ticket, $proposal, null];
    }

    /** Ticket yang disembunyikan hanya terlihat untuk EC Administrator (sama dengan LiteTicketController::show). */
    private function findTicket(int $ticketId, array $user): ?Ticket
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return null;
        }
        if ($ticket->is_hidden && (int) ($user['role']['id'] ?? 0) !== RoleId::EC_ADMINISTRATOR->value) {
            return null;
        }

        return $ticket;
    }

    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    private function resolveUser(Request $request): ?array
    {
        return $request->session()->get('user')
            ?? $request->attributes->get('lite_user');
    }
}
