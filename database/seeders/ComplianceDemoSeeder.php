<?php

namespace Database\Seeders;

use App\Models\Compliance\ComplianceAlert;
use App\Models\Compliance\ComplianceArticle;
use App\Models\Compliance\ComplianceCategory;
use App\Models\Compliance\ComplianceRule;
use App\Models\Compliance\ComplianceSession;
use App\Models\WebReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Example data for Legal → Compliance and My Compliance, and example reports sent by the ESS test user (ESS001, Siti Rahma —
 * login ess.female@ecosystem.local / Password123!). Run EssTestAccountsSeeder first; without it the reports are skipped.
 *
 *   php artisan db:seed --class="Database\Seeders\ComplianceDemoSeeder"
 *
 * Safe to run again: content is matched by name / title, reports by their fixed tracking code. Refuses to run in production.
 */
class ComplianceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('ComplianceDemoSeeder is demo data and does not run in production.');

            return;
        }

        $this->call(ComplianceDefaultsSeeder::class);   // the statuses and dropdown choices the example data uses

        $cat = $this->categories();
        $this->articles($cat);
        $this->rules($cat);
        $this->alerts();
        $this->sessions();
        $this->reports();
    }

    /** @return array<string, int> category name => id */
    private function categories(): array
    {
        $rows = [
            ['Ethics & Conduct', '⚖️', '#0F7B6C', 'How we behave with clients, colleagues and partners.'],
            ['Pricing & Discounts', '💰', '#B45309', 'Quotes, rate cards and discount approval.'],
            ['SAP & Data Handling', '🔐', '#1D4ED8', 'Access to client systems and the data in them.'],
            ['Conflict of Interest', '🤝', '#7C3AED', 'Outside work, gifts and personal relationships.'],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $icon, $color, $desc]) {
            $ids[$name] = ComplianceCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'color' => $color, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true]
            )->id;
        }

        return $ids;
    }

    private function articles(array $cat): void
    {
        $rows = [
            ['Ethics & Conduct', 'Code of conduct in one page', 'What is expected of every Eclectic consultant, every day.', true, 'conduct,ethics,integrity',
                '<h3>Our principles</h3><p>Act honestly, respect the people you work with and protect client information as if it were your own.</p><ul><li>Tell the truth in reports, timesheets and claims.</li><li>Treat colleagues and client staff with respect.</li><li>Raise a concern early — through your lead or through <strong>Report a Concern</strong>.</li></ul>'],
            ['Pricing & Discounts', 'Who may approve a discount', 'Discount limits and the approval chain.', true, 'pricing,discount,approval',
                '<h3>Approval limits</h3><p>A discount is never promised to a client before it is approved.</p><ol><li>Up to 5% — the engagement lead.</li><li>5–15% — the practice head.</li><li>Above 15% — the director, in writing.</li></ol>'],
            ['SAP & Data Handling', 'Working safely in a client SAP system', 'Accounts, transports and data copies.', false, 'sap,access,data',
                '<h3>Rules of access</h3><p>Use only the account you were given, for the task you were given.</p><ul><li>Never share your user ID or password.</li><li>Do not export client data outside the agreed environment.</li><li>Report a suspected leak the same day.</li></ul>'],
            ['Conflict of Interest', 'Gifts, meals and outside work', 'What you may accept and what you must declare.', true, 'gifts,coi,outside-work',
                '<h3>Gifts and meals</h3><p>A modest meal during a working visit is fine. Cash, vouchers or anything that could look like a reward for a decision is not.</p><h3>Outside work</h3><p>Declare any paid work for a client, competitor or supplier before you start.</p>'],
        ];
        foreach ($rows as [$category, $title, $summary, $featured, $tags, $html]) {
            ComplianceArticle::updateOrCreate(
                ['title' => $title],
                ['category_id' => $cat[$category], 'summary' => $summary, 'content' => $html, 'tags' => $tags, 'is_featured' => $featured, 'is_active' => true]
            );
        }
    }

    private function rules(array $cat): void
    {
        $rows = [
            ['do', 'Ethics & Conduct', 'Record your time and expenses truthfully', 'Timesheets and claims are signed off by you; they must match what you did.', 'info'],
            ['do', 'SAP & Data Handling', 'Use only your own account in a client system', 'Ask for access you need instead of borrowing someone else\'s.', 'info'],
            ['do', 'Conflict of Interest', 'Declare outside work before you start', 'Tell your lead and Legal about paid work for a client, competitor or supplier.', 'info'],
            ['dont', 'Pricing & Discounts', 'Promise a discount that is not approved', 'A verbal promise still binds the company in the client\'s eyes.', 'critical'],
            ['dont', 'SAP & Data Handling', 'Copy client data to a personal device or mailbox', 'Data stays inside the environment the client agreed to.', 'critical'],
            ['dont', 'Conflict of Interest', 'Accept cash, vouchers or lavish gifts', 'Politely decline and tell your lead.', 'warning'],
        ];
        foreach ($rows as $i => [$type, $category, $title, $desc, $severity]) {
            ComplianceRule::updateOrCreate(
                ['title' => $title],
                ['type' => $type, 'category_id' => $cat[$category], 'description' => $desc, 'severity' => $severity, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }

    private function alerts(): void
    {
        $rows = [
            ['🚫', 'Zero tolerance for bribery', 'Offering or accepting a bribe ends in dismissal and is reported to the authorities.', 'danger'],
            ['🔒', 'Client data never leaves the client environment', 'No exports, screenshots or forwarding to personal accounts.', 'warning'],
            ['📣', 'Speak up', 'If something looks wrong, report it. Reports are confidential and retaliation is not tolerated.', 'info'],
        ];
        foreach ($rows as $i => [$icon, $title, $desc, $tone]) {
            ComplianceAlert::updateOrCreate(['title' => $title], ['icon' => $icon, 'description' => $desc, 'color_type' => $tone, 'sort_order' => $i + 1, 'is_active' => true]);
        }
    }

    private function sessions(): void
    {
        $rows = [
            ['Code of conduct refresher', now()->subDays(45), 'completed', 'Legal team', 'Meeting room A'],
            ['SAP access & data handling', now()->subDays(14), 'completed', 'IT Security', 'Online'],
            ['Pricing & discount approval', now()->addDays(9), 'upcoming', 'Finance & Legal', 'Meeting room B'],
            ['Conflict of interest workshop', now()->addDays(30), 'upcoming', 'Legal team', 'Online'],
        ];
        foreach ($rows as [$title, $date, $status, $who, $where]) {
            ComplianceSession::updateOrCreate(
                ['title' => $title],
                ['description' => 'Short session with examples and Q&A.', 'session_date' => $date->toDateString(), 'start_time' => '10:00:00', 'end_time' => '10:45:00',
                    'duration_min' => 45, 'session_type' => 'morning', 'status' => $status, 'facilitator' => $who, 'location' => $where]
            );
        }
    }

    /** Compliance reports (CL-) of the ESS test user in several statuses (shown under My Compliance → Report a Concern), plus one anonymous one. */
    private function reports(): void
    {
        $siti = DB::table('employee')->where('eci', 'ESS001')->value('employee_id');
        if (!$siti) {
            $this->command?->warn('ESS001 not found — run EssTestAccountsSeeder first. Reports skipped.');

            return;
        }

        $rows = [
            ['CL-DEMO-0001', $siti, 'Vendor quotation for the Phase 2 rollout', 'new', null,
                'During the vendor review on Monday, the quotation for the Phase 2 rollout was changed after bids closed, and one vendor was told the competing price. I attached the two versions I received.'],
            ['CL-DEMO-0002', $siti, 'Gift offered by a client contact', 'in_review', 'Thank you. We are reviewing it with the engagement lead and will update you here.',
                'A client contact offered a voucher after the go-live and asked me not to mention it to my lead. I declined, but I want it on record.'],
            ['CL-DEMO-0003', $siti, 'Shared SAP login in the support team', 'closed', 'Closed: the shared account was removed and the team was briefed. Thank you for raising it.',
                'Several consultants on the support rotation were using one shared SAP user to save time on access requests.'],
            ['CL-DEMO-0004', null, 'Timesheet padding in a project team', 'new', null,
                'Sent anonymously: hours are regularly logged to the project for work done on other engagements.'],
            ['CL-DEMO-0005', $siti, 'Meal voucher from a vendor after the audit', 'investigating', 'We have opened a review with Procurement. We will update you here.',
                'A vendor sent a food voucher to the team after the audit closed. I returned mine and want to know whether others kept theirs.'],
        ];

        foreach ($rows as [$code, $employeeId, $about, $status, $note, $story]) {
            if (WebReport::where('tracking_code', $code)->exists()) {
                continue;
            }
            $sent = now()->subDays(match ($status) { 'closed' => 25, 'in_review' => 6, 'investigating' => 3, default => 1 });

            $report = new WebReport([
                'tracking_code' => $code, 'channel' => 'compliance', 'employee_id' => $employeeId,
                'reporter_name' => $employeeId ? 'Siti Rahma (ESS Female)' : null, 'reporter_contact' => $employeeId ? 'ess.female@ecosystem.local' : null,
                'reported_name' => $about, 'chronology' => $story, 'status' => $status, 'public_note' => $note, 'lang' => 'en',
                'due_at' => $sent->copy()->addDays(WebReport::followUpDays()), 'closed_at' => $status === 'closed' ? $sent->copy()->addDays(10) : null,
            ]);
            $report->created_at = $sent;
            $report->updated_at = $sent;
            $report->save();

            DB::table('web_report_events')->insert(['report_id' => $report->id, 'actor_id' => $employeeId, 'action' => 'submitted', 'created_at' => $sent]);
        }
    }
}
