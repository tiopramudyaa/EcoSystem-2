<?php

namespace Tests\Feature\Kpi;

use App\Http\Controllers\HR\KpiController;
use App\Models\KpiEvaluation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * HR approval of a KPI evaluation: approve only once the assessor has submitted, "send back" needs a reason and clears the
 * submitted mark so the form reopens, an approved evaluation cannot be sent back, and a self-assessment is never approved.
 * In-memory SQLite; with no template row the evaluation is the normal lead-review type.
 */
class KpiEvaluationApprovalTest extends TestCase
{
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
        });
        Schema::create('kpi_templates', function (Blueprint $t) {
            $t->id();
            $t->string('target_type')->default('supervisor');
            $t->timestamps();
        });
        Schema::create('kpi_evaluations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('template_id')->nullable();
            $t->string('period_month')->nullable();
            $t->unsignedBigInteger('supervisor_id')->nullable();
            $t->string('status')->default('draft');
            $t->boolean('is_anonymous')->default(false);
            $t->decimal('overall_score', 6, 2)->nullable();
            $t->text('general_notes')->nullable();
            $t->date('self_deadline')->nullable();
            $t->date('supervisor_deadline')->nullable();
            $t->timestamp('self_assessed_at')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamp('hr_approved_at')->nullable();
            $t->unsignedBigInteger('hr_approved_by')->nullable();
            $t->text('hr_notes')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('published_by')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        session(['user' => ['id' => self::HR]]);
    }

    private function evaluation(array $extra = []): KpiEvaluation
    {
        $id = DB::table('kpi_evaluations')->insertGetId($extra + [
            'employee_id' => 11, 'supervisor_id' => 12, 'period_month' => '2026-10', 'status' => 'reviewed',
            'reviewed_at' => now()->subDay(), 'overall_score' => 82.5, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return KpiEvaluation::find($id);
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/x', 'POST', $data, [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    private function controller(): KpiController
    {
        return app(KpiController::class);
    }

    public function test_approving_a_reviewed_evaluation_records_hr_and_the_time(): void
    {
        $evaluation = $this->evaluation();

        $response = $this->controller()->approveEvaluation($this->jsonRequest(['hr_notes' => 'Good']), $evaluation->id);

        $this->assertTrue($response->getData()->success);
        $evaluation->refresh();
        $this->assertSame('hr_approved', $evaluation->status);
        $this->assertSame(self::HR, (int) $evaluation->hr_approved_by);
        $this->assertNotNull($evaluation->hr_approved_at);
        $this->assertSame('Good', $evaluation->hr_notes);
    }

    public function test_an_evaluation_the_assessor_has_not_submitted_cannot_be_approved(): void
    {
        $evaluation = $this->evaluation(['status' => 'draft', 'reviewed_at' => null]);

        $response = $this->controller()->approveEvaluation($this->jsonRequest(), $evaluation->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('draft', $evaluation->fresh()->status);
        $this->assertNull($evaluation->fresh()->hr_approved_at);
    }

    public function test_sending_back_needs_a_reason_of_at_least_five_characters(): void
    {
        $evaluation = $this->evaluation();

        try {
            $this->controller()->rejectEvaluation($this->jsonRequest(['hr_notes' => 'no']), $evaluation->id);
            $this->fail('a too short reason must fail validation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('hr_notes', $e->errors());
        }

        $this->assertSame('reviewed', $evaluation->fresh()->status);
    }

    public function test_sending_back_reopens_the_form_and_hides_the_score(): void
    {
        $evaluation = $this->evaluation();

        $response = $this->controller()->rejectEvaluation($this->jsonRequest(['hr_notes' => 'Please add examples']), $evaluation->id);

        $this->assertTrue($response->getData()->success);
        $evaluation->refresh();
        $this->assertSame('hr_rejected', $evaluation->status);
        $this->assertNull($evaluation->reviewed_at, 'the submitted mark is cleared so the assessor can edit again');
        $this->assertNull($evaluation->overall_score, 'the score stays hidden until it is resubmitted and approved');
        $this->assertSame('Please add examples', $evaluation->hr_notes);
    }

    public function test_an_approved_evaluation_cannot_be_sent_back(): void
    {
        $evaluation = $this->evaluation(['status' => 'hr_approved', 'hr_approved_at' => now(), 'hr_approved_by' => self::HR]);

        $response = $this->controller()->rejectEvaluation($this->jsonRequest(['hr_notes' => 'Changed my mind']), $evaluation->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('hr_approved', $evaluation->fresh()->status);
    }

    public function test_a_self_assessment_is_never_approved(): void
    {
        $templateId = DB::table('kpi_templates')->insertGetId(['target_type' => 'self', 'created_at' => now(), 'updated_at' => now()]);
        $evaluation = $this->evaluation(['template_id' => $templateId, 'status' => 'self_assessed', 'self_assessed_at' => now(), 'reviewed_at' => null]);

        $response = $this->controller()->approveEvaluation($this->jsonRequest(), $evaluation->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('self_assessed', $evaluation->fresh()->status);
    }
}
