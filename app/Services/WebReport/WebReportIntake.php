<?php

namespace App\Services\WebReport;

use App\Models\WebReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stores a new Web Report — shared by the public form (no login) and My Compliance (signed-in staff), so both are stored and
 * logged the same way. `channel` decides where a report is handled: the public form (WR-…) in Control Center → Web Report Management,
 * My Compliance (CL-…) in Compliance Management → Reports.
 */
class WebReportIntake
{
    /** @param array{reporter_name?: ?string, reporter_contact?: ?string, reported_name: string, chronology: string} $data */
    public function create(array $data, ?UploadedFile $file, string $lang, ?int $employeeId = null, string $channel = WebReport::CHANNEL_WEB): WebReport
    {
        $cfg = config('web-report');

        $report = WebReport::create([
            'tracking_code' => $this->newCode($channel),
            'channel' => $channel,
            'employee_id' => $employeeId,
            'reporter_name' => $data['reporter_name'] ?? null,
            'reporter_contact' => $data['reporter_contact'] ?? null,
            'reported_name' => $data['reported_name'],
            'chronology' => $data['chronology'],
            'evidence_path' => $file?->store($cfg['directory'], $cfg['disk']),
            'evidence_name' => $file ? Str::limit(basename($file->getClientOriginalName()), 200, '') : null,
            'lang' => $lang,
            'due_at' => now()->addDays(WebReport::followUpDays($channel)),
        ]);

        DB::table('web_report_events')->insert(['report_id' => $report->id, 'actor_id' => $employeeId, 'action' => 'submitted', 'created_at' => now()]);

        return $report;
    }

    /** WR-XXXX-XXXX (public form) or CL-XXXX-XXXX (My Compliance), from the configured alphabet; unique. */
    public function newCode(string $channel = WebReport::CHANNEL_WEB): string
    {
        $cfg = config('web-report');
        $prefix = $channel === WebReport::CHANNEL_COMPLIANCE ? config('compliance.report.code_prefix') : $cfg['code_prefix'];
        $alphabet = $cfg['code_alphabet'];
        $last = strlen($alphabet) - 1;

        do {
            $body = collect(range(1, 8))->map(fn () => $alphabet[random_int(0, $last)])->implode('');
            $code = $prefix . '-' . substr($body, 0, 4) . '-' . substr($body, 4);
        } while (WebReport::where('tracking_code', $code)->exists());

        return $code;
    }
}
