<?php

namespace Tests\Unit;

use App\Models\CustomerMandays;
use App\Models\Ticket;
use App\Services\CustomerMandaysApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Lite API (PWA) untuk approval bertingkat Customer Mandays:
 * daftar menunggu giliran, detail, approve, reject, dan pemeriksaan versi (409).
 * Tabel dibuat minimal di SQLite; tabel approval dibuat oleh migrasi aslinya.
 */
class LiteCustomerMandaysApiTest extends TestCase
{
    private const ADMIN_ROLE    = 1;
    private const HELPDESK_ROLE = 6;
    private const USER_ROLE     = 3;

    private const ADMIN    = 10; // Level 1
    private const HELPDESK = 20; // Level 2, juga pemegang Review Mandays Proposal
    private const PIC      = 30;
    private const OUTSIDER = 40;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available.');
        }
        // Tes ini drop & create tabel: jangan pernah jalan di database sungguhan.
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Runs only on the SQLite test database.');
        }

        $this->createBaseTables();
        (require base_path('database/migrations/2026_11_13_000001_create_customer_mandays_approval_tables.php'))->up();
    }

    // ── daftar & detail ────────────────────────────────────────────────────

    public function test_approvals_list_shows_only_proposals_waiting_for_me(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->getJson('/api/lite/customer-mandays/approvals')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.ticket_id', $ticket->ticket_id)
            ->assertJsonPath('data.0.mandays_id', $p->id)
            ->assertJsonPath('data.0.step_order', 1)
            ->assertJsonPath('data.0.waiting_step', 'Admin Approval');

        // Level 2 belum gilirannya.
        $this->as(self::HELPDESK)->getJson('/api/lite/customer-mandays/approvals')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_approvals_list_skips_hidden_tickets(): void
    {
        [$ticket] = $this->submitted();
        DB::table('ticket')->where('ticket_id', $ticket->ticket_id)->update(['is_hidden' => 1]);

        $this->as(self::ADMIN)->getJson('/api/lite/customer-mandays/approvals')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_detail_for_current_approver_has_grid_trail_and_can_act(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->getJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays")
            ->assertOk()
            ->assertJsonPath('can_act', true)
            ->assertJsonPath('data.mandays_id', $p->id)
            ->assertJsonPath('data.step_order', 1)
            ->assertJsonPath('data.modules', ['CO', 'FI'])
            ->assertJsonPath('data.rows.0.activity', 'Build')
            ->assertJsonPath('data.rows.0.total', 5)
            ->assertJsonPath('data.column_totals.FI', 3)
            ->assertJsonPath('data.approval_trail.0.status', 'waiting')
            ->assertJsonPath('data.approval_trail.1.step_name', 'Helpdesk Review');
    }

    public function test_reviewer_sees_detail_without_can_act(): void
    {
        [$ticket] = $this->submitted();

        $this->as(self::HELPDESK)->getJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays")
            ->assertOk()
            ->assertJsonPath('can_act', false);
    }

    public function test_outsider_cannot_see_detail(): void
    {
        [$ticket] = $this->submitted();

        $this->as(self::OUTSIDER)->getJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays")
            ->assertForbidden();
    }

    public function test_detail_is_hidden_when_ticket_has_no_pic(): void
    {
        [$ticket] = $this->submitted();
        DB::table('ticket')->where('ticket_id', $ticket->ticket_id)->update(['ticket_lead_id' => null]);

        $this->as(self::HELPDESK)->getJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays")
            ->assertForbidden();
    }

    // ── approve ────────────────────────────────────────────────────────────

    public function test_level_one_approve_moves_to_level_two(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id, 'step_order' => 1, 'notes' => 'ok',
        ])
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('data.step_order', 2);

        $this->assertSame(2, $p->fresh()->current_step_order);
        $this->assertSame('ok', DB::table('customer_mandays_approvals')->where('customer_mandays_id', $p->id)->where('order_seq', 1)->value('notes'));
    }

    public function test_last_level_approve_completes(): void
    {
        [$ticket, $p] = $this->submitted();
        app(CustomerMandaysApprovalService::class)->approve($p, $ticket, self::ADMIN, 'Admin');

        $this->as(self::HELPDESK)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id, 'step_order' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('data.status', 'pending_helpdesk');
    }

    public function test_approve_with_adjusted_mandays(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id,
            'step_order' => 1,
            'details'    => [
                ['activity' => 'Build', 'module' => 'FI', 'mandays' => 1],
                ['activity' => 'Test',  'module' => 'FI', 'mandays' => 0.5],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.total_mandays', 1.5);
    }

    public function test_approve_refuses_zero_total(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id, 'step_order' => 1,
            'details'    => [['activity' => 'Build', 'module' => 'FI', 'mandays' => 0]],
        ])->assertStatus(422);

        $this->assertSame(1, $p->fresh()->current_step_order);
    }

    public function test_non_approver_gets_422_with_reason(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::HELPDESK)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id, 'step_order' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You are not an approver for "Admin Approval".');
    }

    public function test_stale_step_order_gets_409(): void
    {
        [$ticket, $p] = $this->submitted();
        app(CustomerMandaysApprovalService::class)->approve($p, $ticket, self::ADMIN, 'Admin');

        // PWA masih memegang layar Level 1.
        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id, 'step_order' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('data.step_order', 2);
    }

    public function test_wrong_mandays_id_gets_409(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [
            'mandays_id' => $p->id + 99, 'step_order' => 1,
        ])->assertStatus(409);

        $this->assertSame(1, $p->fresh()->current_step_order);
    }

    public function test_approve_requires_version_fields(): void
    {
        [$ticket] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/approve", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mandays_id', 'step_order']);
    }

    // ── reject ─────────────────────────────────────────────────────────────

    public function test_reject_requires_reason_of_five_characters(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/reject", [
            'mandays_id' => $p->id, 'step_order' => 1, 'notes' => 'no',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The reason must be at least 5 characters.');
    }

    public function test_reject_closes_version(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->as(self::ADMIN)->postJson("/api/lite/tickets/{$ticket->ticket_id}/customer-mandays/reject", [
            'mandays_id' => $p->id, 'step_order' => 1, 'notes' => 'Too many days',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame('rejected', $ticket->fresh()->mandays_proposal_status);
    }

    // ── ticket detail & notifikasi ─────────────────────────────────────────

    public function test_ticket_summary_block_follows_visibility(): void
    {
        [$ticket, $p] = $this->submitted();
        $presenter = app(\App\Services\LiteCustomerMandaysPresenter::class);

        $summary = $presenter->summary($ticket, self::ADMIN);
        $this->assertSame($p->id, $summary['mandays_id']);
        $this->assertSame('pending_approval', $summary['status']);
        $this->assertSame('Admin Approval', $summary['waiting_step']);
        $this->assertTrue($summary['can_act']);

        $this->assertFalse($presenter->summary($ticket, self::HELPDESK)['can_act']);
        $this->assertNull($presenter->summary($ticket, self::OUTSIDER));
    }

    public function test_approval_notifications_carry_ticket_id(): void
    {
        [$ticket] = $this->submitted();

        $this->assertSame(
            $ticket->ticket_id,
            (int) DB::table('notifications')->where('employee_id', self::ADMIN)
                ->where('type', 'customer_mandays_pending_approval')->value('ticket_id')
        );
    }

    // ── helper ─────────────────────────────────────────────────────────────

    private function as(int $employeeId): static
    {
        $roleId = (int) DB::table('employee_role_assignment')->where('employee_id', $employeeId)->value('role_id');

        return $this->withSession([
            'auth_token' => 'test',
            'user'       => ['id' => $employeeId, 'name' => "Employee {$employeeId}", 'role' => ['id' => $roleId]],
        ]);
    }

    /** @return array{0: Ticket, 1: CustomerMandays} */
    private function submitted(): array
    {
        $ticket = Ticket::create([
            'ticket_number'           => 'T-0001',
            'description'             => 'Need a new report',
            'ticket_lead_id'          => self::PIC,
            'ticket_type'             => 'Change Request',
            'status'                  => 'inprocess',
            'mandays_proposal_status' => 'pic_draft',
        ]);

        $p = CustomerMandays::create([
            'ticket_id'            => $ticket->ticket_id,
            'version'              => 1,
            'description'          => 'Proposal',
            'proposed_by_agent_id' => self::PIC,
            'proposed_at'          => now(),
            'status'               => 'draft',
            'total_mandays'        => 5,
        ]);
        DB::table('customer_mandays_detail')->insert([
            ['customer_mandays_id' => $p->id, 'activity' => 'Build', 'module' => 'FI', 'mandays' => 3],
            ['customer_mandays_id' => $p->id, 'activity' => 'Build', 'module' => 'CO', 'mandays' => 2],
        ]);

        $r = app(CustomerMandaysApprovalService::class)->submit($p, $ticket, self::PIC, 'PIC');
        $this->assertTrue($r['allowed'], $r['reason']);

        return [$ticket->fresh(), $p->fresh()];
    }

    private function createBaseTables(): void
    {
        foreach (['notifications', 'role_menu', 'menu', 'customer_mandays_approvals', 'customer_mandays_approval_steps',
                  'customer_mandays_detail', 'customer_mandays', 'ticket', 'customer_basic_data', 'customer',
                  'employee_role_assignment', 'employee_basic_data', 'employee_role', 'employee'] as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('employee', function ($t) {
            $t->unsignedBigInteger('employee_id')->primary();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('employee_basic_data', function ($t) {
            $t->id('basic_data_id');
            $t->unsignedBigInteger('employee_id');
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('nick_name')->nullable();
            $t->timestamps();
        });
        Schema::create('employee_role', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('description')->nullable();
            $t->timestamps();
        });
        Schema::create('employee_role_assignment', function ($t) {
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('role_id');
            $t->timestamps();
        });
        Schema::create('menu', function ($t) {
            $t->id();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('name');
            $t->string('slug');
            $t->string('type')->nullable();
            $t->string('route_name')->nullable();
            $t->string('icon')->nullable();
            $t->integer('order_seq')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('role_menu', function ($t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('menu_id');
            $t->boolean('can_view')->default(false);
            $t->boolean('can_create')->default(false);
            $t->boolean('can_edit')->default(false);
            $t->boolean('can_delete')->default(false);
            $t->timestamps();
        });
        Schema::create('customer', function ($t) {
            $t->id('customer_id');
            $t->string('customer_code')->nullable();
            $t->timestamps();
        });
        Schema::create('customer_basic_data', function ($t) {
            $t->id('basic_data_id');
            $t->unsignedBigInteger('customer_id');
            $t->string('name_1')->nullable();
            $t->timestamps();
        });
        Schema::create('ticket', function ($t) {
            $t->id('ticket_id');
            $t->string('ticket_number')->nullable();
            $t->text('description')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('ticket_lead_id')->nullable();
            $t->string('ticket_type')->nullable();
            $t->string('status')->nullable();
            $t->string('mandays_proposal_status')->default('none');
            $t->decimal('man_days', 8, 2)->nullable();
            $t->boolean('is_hidden')->nullable();
            $t->timestamp('last_message_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('customer_mandays', function ($t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->integer('version')->default(1);
            $t->string('description')->nullable();
            $t->text('proposal_notes')->nullable();
            $t->unsignedBigInteger('proposed_by_agent_id')->nullable();
            $t->timestamp('proposed_at')->nullable();
            $t->timestamp('submitted_to_customer_at')->nullable();
            $t->timestamp('sent_to_chat_at')->nullable();
            $t->string('status')->default('draft');
            $t->text('customer_notes')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('canceled_by_id')->nullable();
            $t->timestamp('canceled_at')->nullable();
            $t->decimal('total_mandays', 8, 2)->default(0);
            $t->timestamp('customer_response_at')->nullable();
            $t->timestamps();
        });
        Schema::create('customer_mandays_detail', function ($t) {
            $t->id();
            $t->unsignedBigInteger('customer_mandays_id');
            $t->string('activity')->nullable();
            $t->string('module');
            $t->decimal('mandays', 8, 2);
            $t->timestamps();
        });
        Schema::create('notifications', function ($t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->string('type');
            $t->unsignedBigInteger('from_employee_id')->nullable();
            $t->string('from_name')->nullable();
            $t->text('preview')->nullable();
            $t->string('link')->nullable();
            $t->boolean('is_read')->default(false);
            $t->softDeletes();
            $t->timestamps();
        });

        $now = now();
        DB::table('employee_role')->insert([
            ['id' => self::ADMIN_ROLE,    'name' => 'EC Administrator',                  'created_at' => $now, 'updated_at' => $now],
            ['id' => self::USER_ROLE,     'name' => 'EC User',                           'created_at' => $now, 'updated_at' => $now],
            ['id' => self::HELPDESK_ROLE, 'name' => 'Delivery Support Service Helpdesk', 'created_at' => $now, 'updated_at' => $now],
        ]);
        foreach ([self::ADMIN, self::HELPDESK, self::PIC, self::OUTSIDER] as $id) {
            DB::table('employee')->insert(['employee_id' => $id, 'is_active' => true]);
            DB::table('employee_basic_data')->insert(['employee_id' => $id, 'first_name' => "Emp{$id}"]);
        }
        DB::table('employee_role_assignment')->insert([
            ['employee_id' => self::ADMIN,    'role_id' => self::ADMIN_ROLE],
            ['employee_id' => self::HELPDESK, 'role_id' => self::HELPDESK_ROLE],
            ['employee_id' => self::PIC,      'role_id' => self::USER_ROLE],
            ['employee_id' => self::OUTSIDER, 'role_id' => self::USER_ROLE],
        ]);
        // Hanya Helpdesk yang memegang Review Mandays Proposal.
        DB::table('menu')->insert(['id' => 1, 'name' => 'Review Mandays Proposal', 'slug' => 'ticket.review-mandays', 'is_active' => true]);
        DB::table('role_menu')->insert(['role_id' => self::HELPDESK_ROLE, 'menu_id' => 1, 'can_view' => true]);
    }
}
