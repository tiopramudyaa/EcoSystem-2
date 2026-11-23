<?php

namespace Tests\Feature\LeavePermit;

use App\Http\Controllers\HR\LeavePermitController;
use App\Models\LeavePermitApplication;
use App\Models\LeavePermitLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Leave & Permit API: who may decide, who may read or change whose application, and that a decision is all-or-nothing.
 *
 * Two layers are pinned here:
 *  1. Route middleware: every route that decides an application or edits the master types is behind a menu permission.
 *  2. Controller rules for the routes that are open to every signed-in employee (self-service): a person without the HR
 *     permission only ever sees and edits their OWN applications.
 *
 * In-memory SQLite with only the tables this touches. No `employee` row exists for any actor, so the controller's
 * permission lookup (`menuCan`) answers "no" for everyone: these tests exercise the plain-employee path and the
 * decision step, which has no permission check of its own (the route middleware guards it, see the route tests).
 */
class LeavePermitApiTest extends TestCase
{
    private const ALICE = 11;
    private const BOB = 12;
    private const HR = 99;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded (run: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit).');
        }
        parent::setUp();

        Schema::create('employee', function (Blueprint $t) {
            $t->unsignedBigInteger('employee_id')->primary();
            $t->string('eci')->nullable();
            $t->boolean('is_active')->default(true);
        });
        Schema::create('leave_permit_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('name')->nullable();
            $t->string('category')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('leave_permit_applications', function (Blueprint $t) {
            $t->id();
            $t->string('application_no')->nullable();
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('leave_permit_type_id')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->decimal('total_days', 5, 1)->default(1);
            $t->text('reason')->nullable();
            $t->string('attachment_path')->nullable();
            $t->string('status')->default('pending');
            $t->text('revision_notes')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('leave_permit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('application_id');
            $t->string('action');
            $t->unsignedBigInteger('performed_by')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('type');
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->unsignedBigInteger('from_employee_id')->nullable();
            $t->string('from_name')->nullable();
            $t->text('preview')->nullable();
            $t->string('link')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });
    }

    private function signInAs(int $employeeId): void
    {
        session(['user' => ['id' => $employeeId]]);
    }

    private function application(int $employeeId, string $status = 'pending'): LeavePermitApplication
    {
        return LeavePermitApplication::create([
            'application_no' => 'LP/' . $employeeId . '/' . uniqid(), 'employee_id' => $employeeId, 'leave_permit_type_id' => 1,
            'start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'total_days' => 1, 'reason' => 'Family event', 'status' => $status,
        ]);
    }

    private function controller(): LeavePermitController
    {
        return app(LeavePermitController::class);
    }

    private function route(string $uri, string $method = 'POST'): ?\Illuminate\Routing\Route
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }

    // ── 1. Route middleware ────────────────────────────────────────────────

    public function test_master_type_writes_and_hr_reports_are_behind_a_menu_permission(): void
    {
        $routes = [
            ['POST', 'api/hr-general/leave-permit/master-types'],
            ['POST', 'api/hr-general/leave-permit/master-types/{id}/update'],
            ['POST', 'api/hr-general/leave-permit/master-types/{id}/toggle-active'],
            ['GET', 'api/hr-general/leave-permit/employees-list'],
            ['GET', 'api/hr-general/leave-permit/all-employees-quotas'],
            ['GET', 'api/hr-general/leave-permit/employee-quota-detail/{employee_id}'],
            ['GET', 'api/hr-general/leave-permit/reports'],
        ];

        foreach ($routes as [$method, $uri]) {
            $route = $this->route($uri, $method);
            $this->assertNotNull($route, "{$method} {$uri} is registered");
            $mw = implode(',', $route->gatherMiddleware());
            $this->assertMatchesRegularExpression('/\bmenu(\.can)?:hr_general\.leave_permit/', $mw, "{$method} {$uri} must carry a menu permission, got: {$mw}");
        }
    }

    public function test_approve_reject_and_revision_need_the_inbox_edit_permission(): void
    {
        foreach (['approve', 'reject', 'revision'] as $action) {
            $route = $this->route("api/hr-general/leave-permit/applications/{id}/{$action}");
            $this->assertNotNull($route, $action);
            $this->assertContains('menu.can:hr_general.leave_permit.tab-inbox,edit', $route->gatherMiddleware(), "{$action} must require Edit on the Inbox tab");
        }
    }

    // ── 2. A decision is all-or-nothing and happens once ───────────────────

    public function test_approving_a_pending_application_records_the_reviewer_and_one_log_row(): void
    {
        $app = $this->application(self::ALICE);
        $this->signInAs(self::HR);

        $response = $this->controller()->approveApplication(Request::create('/x', 'POST', ['notes' => 'OK']), $app->id);

        $this->assertTrue($response->getData()->success);
        $app->refresh();
        $this->assertSame('approved', $app->status);
        $this->assertSame(self::HR, (int) $app->reviewed_by);
        $this->assertSame(1, LeavePermitLog::where('application_id', $app->id)->where('action', 'approved')->count());
    }

    public function test_a_second_decision_is_refused_and_writes_nothing(): void
    {
        $app = $this->application(self::ALICE);
        $this->signInAs(self::HR);

        $this->controller()->approveApplication(Request::create('/x', 'POST'), $app->id);

        $second = $this->controller()->rejectApplication(Request::create('/x', 'POST', ['rejection_reason' => 'Too late']), $app->id);
        $third  = $this->controller()->approveApplication(Request::create('/x', 'POST'), $app->id);

        $this->assertSame(422, $second->getStatusCode());
        $this->assertSame(422, $third->getStatusCode());
        $this->assertSame('approved', $app->fresh()->status, 'the first decision stands');
        $this->assertSame(1, LeavePermitLog::where('application_id', $app->id)->count(), 'refused decisions leave no log row');
    }

    public function test_only_a_pending_application_can_be_decided(): void
    {
        $this->signInAs(self::HR);

        foreach (['approved', 'rejected', 'revision'] as $status) {
            $app = $this->application(self::ALICE, $status);
            $this->assertSame(422, $this->controller()->approveApplication(Request::create('/x', 'POST'), $app->id)->getStatusCode(), "approve from {$status}");
            $this->assertSame($status, $app->fresh()->status);
        }
    }

    public function test_reject_and_revision_require_a_written_reason(): void
    {
        $app = $this->application(self::ALICE);
        $this->signInAs(self::HR);

        try {
            $this->controller()->rejectApplication(Request::create('/x', 'POST', ['rejection_reason' => '']), $app->id);
            $this->fail('reject without a reason must fail validation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rejection_reason', $e->errors());
        }

        try {
            $this->controller()->requestRevision(Request::create('/x', 'POST', []), $app->id);
            $this->fail('revision without notes must fail validation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('revision_notes', $e->errors());
        }

        $this->assertSame('pending', $app->fresh()->status);
    }

    public function test_each_outcome_sets_the_matching_status_and_log_action(): void
    {
        $this->signInAs(self::HR);

        $a = $this->application(self::ALICE);
        $this->controller()->rejectApplication(Request::create('/x', 'POST', ['rejection_reason' => 'No cover']), $a->id);
        $this->assertSame('rejected', $a->fresh()->status);
        $this->assertSame('rejected', LeavePermitLog::where('application_id', $a->id)->value('action'));

        $b = $this->application(self::BOB);
        $this->controller()->requestRevision(Request::create('/x', 'POST', ['revision_notes' => 'Add the date']), $b->id);
        $this->assertSame('revision', $b->fresh()->status);
        $this->assertSame('revision_requested', LeavePermitLog::where('application_id', $b->id)->value('action'));
    }

    public function test_a_failure_while_writing_the_log_leaves_the_application_pending(): void
    {
        $app = $this->application(self::ALICE);
        $this->signInAs(self::HR);

        Schema::drop('leave_permit_logs'); // the log insert now fails inside the decision step

        try {
            $this->controller()->approveApplication(Request::create('/x', 'POST'), $app->id);
            $this->fail('the log insert should have thrown');
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertSame('pending', DB::table('leave_permit_applications')->where('id', $app->id)->value('status'), 'status change is rolled back with the log');
        $this->assertNull(DB::table('leave_permit_applications')->where('id', $app->id)->value('reviewed_by'));
    }

    // ── 3. Plain employee boundaries on the open routes ────────────────────

    public function test_an_employee_only_lists_their_own_applications_even_when_asking_for_someone_else(): void
    {
        $mine   = $this->application(self::ALICE);
        $theirs = $this->application(self::BOB);
        $this->signInAs(self::ALICE);

        $response = $this->controller()->getApplications(Request::create('/x', 'GET', ['employee_id' => self::BOB]));
        $ids = collect($response->getData()->data)->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids, 'Alice must not see the application of Bob by passing his employee_id');
    }

    public function test_an_employee_cannot_edit_someone_elses_application(): void
    {
        $bobs = $this->application(self::BOB);
        $this->signInAs(self::ALICE);

        $response = $this->controller()->updateApplication(Request::create('/x', 'POST', [
            'leave_permit_type_id' => 1, 'start_date' => '2026-12-02', 'end_date' => '2026-12-02', 'reason' => 'Hijack',
        ]), $bobs->id);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Family event', $bobs->fresh()->reason);
    }

    public function test_an_employee_cannot_read_another_employees_quota(): void
    {
        $this->signInAs(self::ALICE);

        $response = $this->controller()->getUserQuotas(Request::create('/x', 'GET', ['employee_id' => self::BOB]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_an_employee_cannot_edit_their_own_application_once_it_is_decided(): void
    {
        $app = $this->application(self::ALICE, 'approved');
        $this->signInAs(self::ALICE);

        $response = $this->controller()->updateApplication(Request::create('/x', 'POST', [
            'leave_permit_type_id' => 1, 'start_date' => '2026-12-02', 'end_date' => '2026-12-02', 'reason' => 'Changed my mind',
        ]), $app->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Family event', $app->fresh()->reason);
    }
}
