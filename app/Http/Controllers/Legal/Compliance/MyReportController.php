<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Http\Controllers\Controller;
use App\Models\WebReport;
use App\Services\WebReport\WebReportIntake;
use Illuminate\Http\Request;

/**
 * My Compliance → Report a Concern (URL /compliance/report, every signed-in user, no Menu Access slug): a staff member reports a
 * compliance concern without leaving My Compliance. The report lands in Compliance Management → Reports (code CL-…),
 * separate from the public Web Report form (WR-…, Control Center → Web Report Management).
 *
 * Two ways to send: with the person's name (linked to them, so they follow the status here) or anonymously (nothing links it to them;
 * the one-time tracking code is the only way to follow it, on the public status page). Limits: config/web-report.php.
 */
class MyReportController extends Controller
{
    public function index()
    {
        return view('legal.compliance.my.report', [
            'mine' => WebReport::compliance()->where('employee_id', (int) session('user.id'))->latest()->get(),
            'code' => session('compliance_report_code'),
        ]);
    }

    public function store(Request $request, WebReportIntake $intake)
    {
        $cfg = config('web-report');

        $data = $request->validate([
            'reported_name' => 'required|string|max:150',
            'chronology' => "required|string|min:{$cfg['chronology_min']}|max:{$cfg['chronology_max']}",
            'evidence' => "nullable|file|max:{$cfg['evidence_max_kb']}|mimes:{$cfg['evidence_types']}",
            'anonymous' => 'nullable|boolean',
        ], [
            'reported_name.required' => 'Say who or what the report is about.',
            'chronology.required' => 'Describe what happened.',
            'chronology.min' => "Please describe what happened in a little more detail (at least {$cfg['chronology_min']} characters).",
            'evidence.max' => 'The file is larger than ' . intdiv($cfg['evidence_max_kb'], 1024) . ' MB.',
            'evidence.mimes' => 'This file type is not accepted.',
        ]);

        $employeeId = null;
        if (!$request->boolean('anonymous')) {
            $employeeId = (int) session('user.id');
            $data['reporter_name'] = session('user.name');
            $data['reporter_contact'] = session('user.email');
        }

        $report = $intake->create($data, $request->file('evidence'), app()->getLocale(), $employeeId, WebReport::CHANNEL_COMPLIANCE);

        return redirect()->route('compliance.report')
            ->with('success', 'Your report has been received.')
            ->with('compliance_report_code', $employeeId ? null : $report->tracking_code);
    }
}
