<?php

namespace App\Http\Controllers\WebReport;

use App\Http\Controllers\Controller;
use App\Models\WebReport;
use App\Services\WebReport\WebReportIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Web Report, the public side (URL /web-report; /lapor redirects here): anyone can report an issue WITHOUT logging in, and the login
 * page links to it. Because it is open to everyone it is protected by: a throttle, a one-time arithmetic captcha, a honeypot field,
 * an extension + content allow-list on the evidence file (kept on the PRIVATE disk), and encrypted storage.
 *
 * Deliberately NOT stored: IP address, user agent, session id. The reporter gets a tracking code once and can check the status with it
 * (POST /web-report/status) without ever giving a name. The admin handles the reports in Control Center → Web Report Management
 * (ManagementController). Limits and wording are in config/web-report.php and lang/en|id/web-report.php.
 */
class PublicController extends Controller
{
    public function form(Request $request)
    {
        $this->locale($request);

        return view('web-report.form', ['captcha' => $this->newCaptcha(), 'code' => session('web_report_code')]);
    }

    public function submit(Request $request, WebReportIntake $intake)
    {
        $this->locale($request);
        $cfg = config('web-report');

        // The captcha is single-use: whatever happens next, a new one is issued.
        $expected = session()->pull('web_report_captcha');

        if ($request->filled('website')) {                        // honeypot: real people never see this field
            return redirect()->route('web-report.form');
        }

        $data = $request->validate([
            'reporter_name' => 'nullable|string|max:100',
            'reporter_contact' => 'nullable|string|max:150',
            'reported_name' => 'required|string|max:150',
            'chronology' => "required|string|min:{$cfg['chronology_min']}|max:{$cfg['chronology_max']}",
            'evidence' => "nullable|file|max:{$cfg['evidence_max_kb']}|mimes:{$cfg['evidence_types']}",
            'captcha' => ['required', fn ($a, $v, $fail) => ($expected !== null && (int) $v === (int) $expected) ?: $fail(__('web-report.msg_captcha'))],
        ], [
            'reported_name.required' => __('web-report.msg_required'), 'chronology.required' => __('web-report.msg_required'),
            'chronology.min' => __('web-report.msg_short'), 'evidence.max' => __('web-report.msg_filesize'), 'evidence.mimes' => __('web-report.msg_type'),
        ]);

        $report = $intake->create($data, $request->file('evidence'), App::getLocale());

        return redirect()->route('web-report.form')->with('web_report_code', $report->tracking_code);
    }

    /** Status lookup. POST so the code never lands in a URL, a browser history or a server log. */
    public function status(Request $request)
    {
        $this->locale($request);

        $report = null;
        if ($request->isMethod('post')) {
            $report = WebReport::where('tracking_code', strtoupper(trim((string) $request->input('tracking_code'))))->first();

            if (!$report) {
                return back()->withInput()->withErrors(['tracking_code' => __('web-report.msg_not_found')]);
            }
        }

        return view('web-report.status', ['report' => $report]);
    }

    /** ?lang=id switches the language of the public pages; the choice is kept in the session. */
    private function locale(Request $request): void
    {
        $languages = config('web-report.languages');

        if (in_array($request->query('lang'), $languages, true)) {
            session(['web_report_lang' => $request->query('lang')]);
        }

        App::setLocale(session('web_report_lang', config('web-report.default_language')));
    }

    /** "7 + 4" — one-time, kept in the session. */
    private function newCaptcha(): string
    {
        [$min, $max] = config('web-report.captcha_range');
        [$a, $b] = [random_int($min, $max), random_int($min, $max)];
        session(['web_report_captcha' => $a + $b]);

        return "{$a} + {$b}";
    }
}
