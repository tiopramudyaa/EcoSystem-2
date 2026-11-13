<?php

namespace Tests\Unit;

use App\Http\Middleware\DetectAttackPatterns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Aksi di menu Timesheet tidak boleh terblokir WAF.
 *
 * Ada dua lapis WAF: middleware DetectAttackPatterns (di aplikasi; ship dengan mode 'log'
 * tetapi bisa diganti 'block') dan WAF edge di production yang menolak method DELETE dan PUT
 * (lihat komentar di routes/api.php dan AiAssistantController). Test ini memaksa mode 'block'
 * dan menjalankan request yang dikirim halaman Timesheet, lalu memastikan route Timesheet hanya
 * memakai GET dan POST.
 */
class TimesheetWafCompatibilityTest extends TestCase
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    /** @return array<string, array{0:string,1:string,2:array,3:?string}> */
    public static function timesheetRequests(): array
    {
        $gps = ['gps_latitude' => -6.2441, 'gps_longitude' => 106.7993, 'gps_accuracy' => 18];
        $common = [
            'employee_id' => 1, 'date' => '2026-11-11', 'start_time' => '08:00', 'end_time' => '12:00',
            'description' => "Workshop & review (go-live) 10/11, 'quote' - user's request; ref #123",
            'notes' => 'Catatan: lihat dokumen http://sharepoint/x',
        ];
        $body = fn (array $b) => json_encode($b);

        return [
            'open page'              => ['GET', '/calendar/timesheets', [], null],
            'list'                   => ['GET', '/api/timesheets', ['start_date' => '2026-10-21', 'end_date' => '2026-11-20'], null],
            'approval list non project' => ['GET', '/api/timesheets/submitted-for-approval', ['status' => 'submitted', 'type_filter' => 'non_project'], null],
            'statistics'             => ['GET', '/api/timesheets/statistics', ['start_date' => '2026-10-21', 'end_date' => '2026-11-20'], null],
            'form context'           => ['GET', '/api/timesheets/project-form-context', ['date' => '2026-11-11', 'exclude_id' => 12], null],
            'my projects'            => ['GET', '/api/timesheets/my-projects', ['include_project_id' => 7], null],
            'my activities'          => ['GET', '/api/timesheets/my-activities/7', ['date' => '2026-11-11'], null],
            'remaining md'           => ['GET', '/api/timesheets/remaining-md', ['ticket_id' => 55], null],
            'valid periods'          => ['GET', '/api/timesheets/valid-periods', [], null],
            'create project'         => ['POST', '/api/timesheets', [], $body($common + $gps + ['delivery_projects_id' => 7, 'activity_id' => 3, 'presence' => 'onsite', 'location' => 'Gedung Pasaraya Blok M, Jl. Iskandarsyah II No. 2, Jakarta Selatan 12160'])],
            'create non project'     => ['POST', '/api/timesheets', [], $body($common + $gps + ['is_non_project' => true, 'presence' => 'remote', 'location' => 'Home'])],
            'create support'         => ['POST', '/api/timesheets', [], $body($common + ['ticket_id' => 55, 'md_consumed' => 0.5, 'presence' => 'onsite', 'activity_type' => 'support'])],
            'create office'          => ['POST', '/api/timesheets', [], $body($common + ['activity_type' => 'other', 'presence' => 'remote', 'location' => 'Home'])],
            'update'                 => ['POST', '/api/timesheets/12/update', [], $body($common + $gps + ['is_non_project' => true])],
            'delete (POST)'          => ['POST', '/api/timesheets/12/delete', [], '{}'],
            'submit'                 => ['POST', '/api/timesheets/12/submit', [], '{}'],
            'approve'                => ['POST', '/api/timesheets/12/approve', [], '{}'],
            'reject'                 => ['POST', '/api/timesheets/12/reject', [], $body(['rejection_reason' => 'Jam kerja tidak sesuai jadwal; mohon diperbaiki (lihat catatan).'])],
            'export'                 => ['POST', '/api/timesheets/export', [], $body(['ids' => [1, 2, 3], 'type_filter' => 'non_project'])],
            'late access request'    => ['POST', '/api/periods/exception-requests', [], $body(['period_id' => 5, 'reason' => 'Lupa mengisi karena sedang dinas luar kota'])],
        ];
    }

    /**
     * @dataProvider timesheetRequests
     */
    public function test_timesheet_request_passes_the_waf_in_block_mode(string $method, string $uri, array $query, ?string $body): void
    {
        config(['security_center.waf.mode' => 'block']);

        $request = Request::create($uri . ($query ? '?' . http_build_query($query) : ''), $method, [], [], [], [
            'HTTP_USER_AGENT' => self::UA,
            'CONTENT_TYPE'    => 'application/json',
            'HTTP_ACCEPT'     => 'application/json',
        ], $body);

        $response = (new DetectAttackPatterns())->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode(), "$method $uri was blocked by the WAF");
    }

    public function test_timesheet_routes_only_use_get_and_post(): void
    {
        $offenders = [];
        foreach (Route::getRoutes() as $route) {
            if (!str_contains($route->uri(), 'timesheets')) {
                continue;
            }
            foreach ($route->methods() as $method) {
                if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
                    $offenders[] = "$method {$route->uri()}";
                }
            }
        }

        $this->assertSame([], $offenders, 'The production edge WAF rejects PUT and DELETE: use POST variants.');
    }

    /** The late access decision (RPMO Periods page) has POST variants next to the PATCH routes. */
    public function test_late_access_decisions_have_post_routes(): void
    {
        foreach (['head-decide', 'rpmo-decide'] as $action) {
            $route = Route::getRoutes()->match(Request::create("/api/periods/exception-requests/1/{$action}", 'POST'));
            $this->assertContains('POST', $route->methods(), "POST {$action} route is missing");
        }

        $this->assertDoesNotMatchRegularExpression(
            "/fetch\(url,\s*\{\s*method:\s*'PATCH'/",
            file_get_contents(base_path('resources/views/rpmo/periods/index.blade.php')),
            'RPMO Periods sends PATCH for the late access decision'
        );
    }

    public function test_timesheet_page_scripts_do_not_send_blocked_verbs(): void
    {
        $sources = [
            base_path('public/js/calendar-timesheets.js'),
            base_path('resources/views/calendar/timesheets.blade.php'),
            base_path('resources/views/calendar/partials/timesheet-detail-panel.blade.php'),
        ];

        foreach ($sources as $file) {
            $this->assertDoesNotMatchRegularExpression(
                "/method:\s*['\"](PUT|PATCH|DELETE)['\"]/i",
                file_get_contents($file),
                basename($file) . ' sends a verb the edge WAF blocks'
            );
        }
    }
}
