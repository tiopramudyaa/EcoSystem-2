<?php

namespace Tests\Unit;

use App\Http\Controllers\CustomerMandaysFlowController;
use App\Models\CustomerMandays;
use App\Models\CustomerMandaysApproval;
use App\Models\CustomerMandaysApprovalStep;
use App\Models\Ticket;
use App\Services\CustomerMandaysApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Approval bertingkat Customer Mandays: PIC submit → Level 1 → Level 2 → pending_helpdesk.
 * Tabel dibuat minimal di SQLite; tabel approval dibuat oleh migrasi aslinya.
 */
class CustomerMandaysApprovalTest extends TestCase
{
    private const ADMIN_ROLE    = 1;
    private const HELPDESK_ROLE = 6;
    private const USER_ROLE     = 3;

    private const ADMIN    = 10; // EC Administrator  → Level 1
    private const HELPDESK = 20; // Helpdesk          → Level 2
    private const PIC      = 30; // pengusul
    private const OUTSIDER = 40;

    private CustomerMandaysApprovalService $service;

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

        $this->service = app(CustomerMandaysApprovalService::class);
    }

    // ── skenario ───────────────────────────────────────────────────────────

    public function test_migration_seeds_admin_then_helpdesk(): void
    {
        $steps = CustomerMandaysApprovalStep::ordered()->get();

        $this->assertCount(2, $steps);
        $this->assertSame(self::ADMIN_ROLE, $steps[0]->approver_role_id);
        $this->assertSame(self::HELPDESK_ROLE, $steps[1]->approver_role_id);
    }

    public function test_submit_snapshots_both_levels_and_waits_for_level_one(): void
    {
        [$ticket, $p] = $this->draft();

        $r = $this->service->submit($p, $ticket, self::PIC, 'PIC');

        $this->assertTrue($r['allowed'], $r['reason']);
        $p->refresh();
        $this->assertSame('pending_approval', $p->status);
        $this->assertSame(1, $p->current_step_order);
        $this->assertSame('pending_approval', $ticket->fresh()->mandays_proposal_status);
        $this->assertSame(['waiting', 'waiting'], $p->approvals()->pluck('status')->all());
        $this->assertTrue($this->notified(self::ADMIN, 'customer_mandays_pending_approval'));
        $this->assertFalse($this->notified(self::HELPDESK, 'customer_mandays_pending_approval'));
    }

    public function test_submit_is_refused_when_a_level_has_no_approver(): void
    {
        CustomerMandaysApprovalStep::where('order_seq', 2)->update(['approver_role_id' => null]);
        [$ticket, $p] = $this->draft();

        $r = $this->service->submit($p, $ticket, self::PIC, 'PIC');

        $this->assertFalse($r['allowed']);
        $this->assertStringContainsString('level 2', $r['reason']);
        $this->assertSame('draft', $p->fresh()->status);
        $this->assertSame(0, CustomerMandaysApproval::count());
    }

    public function test_two_levels_in_order_then_ready_to_send(): void
    {
        [$ticket, $p] = $this->submitted();

        // Helpdesk belum gilirannya.
        $this->assertFalse($this->service->canAct($p, self::HELPDESK)['allowed']);

        $r1 = $this->service->approve($p->fresh(), $ticket, self::ADMIN, 'Admin');
        $this->assertTrue($r1['allowed'], $r1['reason']);
        $this->assertFalse($r1['completed']);
        $this->assertSame(2, $p->fresh()->current_step_order);
        $this->assertTrue($this->notified(self::HELPDESK, 'customer_mandays_pending_approval'));

        $r2 = $this->service->approve($p->fresh(), $ticket, self::HELPDESK, 'Helpdesk', null, 'ok');
        $this->assertTrue($r2['completed']);

        $p->refresh();
        $this->assertSame('pending_helpdesk', $p->status);
        $this->assertNull($p->current_step_order);
        $this->assertSame('pending_helpdesk', $ticket->fresh()->mandays_proposal_status);
        $this->assertSame(['approved', 'approved'], $p->approvals()->pluck('status')->all());
        $this->assertSame(self::HELPDESK, (int) $p->approvals()->where('order_seq', 2)->value('acted_by'));
        // Pemegang izin review mendapat kabar siap dikirim.
        $this->assertTrue($this->notified(self::HELPDESK, 'customer_mandays_proposed'));
    }

    public function test_outsider_cannot_approve(): void
    {
        [, $p] = $this->submitted();

        $gate = $this->service->canAct($p, self::OUTSIDER);

        $this->assertFalse($gate['allowed']);
        $this->assertStringContainsString('not an approver', $gate['reason']);
    }

    public function test_approver_can_adjust_mandays_while_approving(): void
    {
        [$ticket, $p] = $this->submitted();

        $this->service->approve($p, $ticket, self::ADMIN, 'Admin', [
            ['activity' => 'Build', 'module' => 'FI', 'mandays' => 2],
            ['activity' => 'Build', 'module' => 'CO', 'mandays' => 1.5],
        ]);

        $this->assertEquals(3.5, (float) $p->fresh()->total_mandays);
        $this->assertSame(2, DB::table('customer_mandays_detail')->where('customer_mandays_id', $p->id)->count());
    }

    public function test_approve_refuses_zero_total(): void
    {
        [$ticket, $p] = $this->submitted();

        $r = $this->service->approve($p, $ticket, self::ADMIN, 'Admin', [['activity' => 'X', 'module' => 'FI', 'mandays' => 0]]);

        $this->assertFalse($r['allowed']);
        $this->assertSame(1, $p->fresh()->current_step_order);
    }

    public function test_reject_closes_version_skips_rest_and_tells_pic(): void
    {
        [$ticket, $p] = $this->submitted();

        $r = $this->service->reject($p, $ticket, self::ADMIN, 'Admin', 'Too many days');

        $this->assertTrue($r['allowed']);
        $p->refresh();
        $this->assertSame('rejected', $p->status);
        $this->assertNull($p->current_step_order);
        $this->assertSame('rejected', $ticket->fresh()->mandays_proposal_status);
        $this->assertSame(['rejected', 'skipped'], $p->approvals()->pluck('status')->all());
        $this->assertSame('Too many days', $p->approvals()->where('order_seq', 1)->value('notes'));
        $this->assertTrue($this->notified(self::PIC, 'customer_mandays_rejected'));

        // Sudah tertutup: tidak bisa diputuskan lagi.
        $this->assertFalse($this->service->canAct($p, self::HELPDESK)['allowed']);
    }

    public function test_changing_the_flow_does_not_touch_a_running_proposal(): void
    {
        [$ticket, $p] = $this->submitted();

        CustomerMandaysApprovalStep::where('order_seq', 1)->update([
            'approver_type'         => 'employee',
            'approver_role_id'      => null,
            'approver_employee_ids' => json_encode([self::OUTSIDER]),
        ]);

        $this->assertTrue($this->service->canAct($p->fresh(), self::ADMIN)['allowed']);
        $this->assertFalse($this->service->canAct($p->fresh(), self::OUTSIDER)['allowed']);
    }

    public function test_level_assigned_to_named_people_only_allows_them(): void
    {
        CustomerMandaysApprovalStep::where('order_seq', 1)->update([
            'approver_type'         => 'employee',
            'approver_employee_ids' => json_encode([self::OUTSIDER]),
        ]);
        [, $p] = $this->submitted();

        $this->assertTrue($this->service->canAct($p, self::OUTSIDER)['allowed']);
        $this->assertFalse($this->service->canAct($p, self::ADMIN)['allowed']);
    }

    public function test_self_approval_is_allowed(): void
    {
        DB::table('employee_role_assignment')->insert(['employee_id' => self::PIC, 'role_id' => self::ADMIN_ROLE]);
        [, $p] = $this->submitted();

        $this->assertTrue($this->service->canAct($p, self::PIC)['allowed']);
    }

    public function test_skip_remaining_marks_waiting_levels_skipped(): void
    {
        [, $p] = $this->submitted();

        $this->service->skipRemaining($p);

        $this->assertSame(['skipped', 'skipped'], $p->approvals()->pluck('status')->all());
    }

    public function test_flow_page_saves_swapped_order_and_names(): void
    {
        $steps = CustomerMandaysApprovalStep::ordered()->get();

        $response = $this->syncFlow([
            ['id' => $steps[1]->id, 'name' => 'Helpdesk First', 'role_id' => self::HELPDESK_ROLE],
            ['id' => $steps[0]->id, 'name' => 'Admin Second', 'role_id' => '', 'employee_ids' => [self::ADMIN]],
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $after = CustomerMandaysApprovalStep::ordered()->get();
        $this->assertSame('Helpdesk First', $after[0]->name);
        $this->assertSame('role', $after[0]->approver_type);
        $this->assertSame('Admin Second', $after[1]->name);
        $this->assertSame('employee', $after[1]->approver_type);
        $this->assertSame([self::ADMIN], $after[1]->approver_employee_ids);
    }

    public function test_flow_page_refuses_level_without_approver(): void
    {
        $steps = CustomerMandaysApprovalStep::ordered()->get();

        $this->syncFlow([
            ['id' => $steps[0]->id, 'name' => 'Admin Approval', 'role_id' => ''],
            ['id' => $steps[1]->id, 'name' => 'Helpdesk Review', 'role_id' => self::HELPDESK_ROLE],
        ]);

        $this->assertSame(self::ADMIN_ROLE, CustomerMandaysApprovalStep::where('order_seq', 1)->value('approver_role_id'));
        $this->assertStringContainsString('Level 1', (string) session('error'));
    }

    public function test_flow_page_refuses_wrong_level_count(): void
    {
        $steps = CustomerMandaysApprovalStep::ordered()->get();

        $this->syncFlow([['id' => $steps[0]->id, 'name' => 'Only', 'role_id' => self::ADMIN_ROLE]]);

        $this->assertStringContainsString('exactly 2', (string) session('error'));
    }

    // ── helper ─────────────────────────────────────────────────────────────

    private function syncFlow(array $rows)
    {
        $request = Request::create('/management/flow/customer-mandays', 'POST', ['steps' => $rows]);
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        return app(CustomerMandaysFlowController::class)->sync($request);
    }

    /** @return array{0: Ticket, 1: CustomerMandays} */
    private function draft(): array
    {
        $ticket = Ticket::create([
            'ticket_number'           => 'T-0001',
            'ticket_lead_id'          => self::PIC,
            'ticket_type'             => 'Change Request',
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
            'customer_mandays_id' => $p->id, 'activity' => 'Build', 'module' => 'FI', 'mandays' => 5,
        ]);

        return [$ticket, $p];
    }

    /** @return array{0: Ticket, 1: CustomerMandays} */
    private function submitted(): array
    {
        [$ticket, $p] = $this->draft();
        $r = $this->service->submit($p, $ticket, self::PIC, 'PIC');
        $this->assertTrue($r['allowed'], $r['reason']);

        return [$ticket->fresh(), $p->fresh()];
    }

    private function notified(int $employeeId, string $type): bool
    {
        return DB::table('notifications')->where('employee_id', $employeeId)->where('type', $type)->exists();
    }

    private function createBaseTables(): void
    {
        foreach (['notifications', 'role_menu', 'menu', 'customer_mandays_approvals', 'customer_mandays_approval_steps',
                  'customer_mandays_detail', 'customer_mandays', 'ticket', 'employee_role_assignment',
                  'employee_basic_data', 'employee_role', 'employee'] as $t) {
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
        Schema::create('ticket', function ($t) {
            $t->id('ticket_id');
            $t->string('ticket_number')->nullable();
            $t->unsignedBigInteger('ticket_lead_id')->nullable();
            $t->string('ticket_type')->nullable();
            $t->string('status')->nullable();
            $t->string('mandays_proposal_status')->default('none');
            $t->decimal('man_days', 8, 2)->nullable();
            $t->timestamp('last_message_at')->nullable();
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
        }
        DB::table('employee_role_assignment')->insert([
            ['employee_id' => self::ADMIN,    'role_id' => self::ADMIN_ROLE],
            ['employee_id' => self::HELPDESK, 'role_id' => self::HELPDESK_ROLE],
            ['employee_id' => self::PIC,      'role_id' => self::USER_ROLE],
            ['employee_id' => self::OUTSIDER, 'role_id' => self::USER_ROLE],
        ]);
        // Izin review (penerima notifikasi "siap dikirim") hanya untuk Helpdesk.
        DB::table('menu')->insert(['id' => 1, 'name' => 'Review Mandays Proposal', 'slug' => 'ticket.review-mandays', 'is_active' => true]);
        DB::table('role_menu')->insert(['role_id' => self::HELPDESK_ROLE, 'menu_id' => 1, 'can_view' => true]);
    }
}
