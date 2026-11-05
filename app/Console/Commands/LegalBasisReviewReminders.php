<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\Legal\LegalBasisService;
use Illuminate\Console\Command;

/**
 * Reminds the OWNER of a Legal Basis rule, through the notification bell, when its yearly review is close or has passed:
 *
 *   · a Reviewed rule whose review expires within 30 days, or already expired;
 *   · a rule that has been waiting in "Needs review" for more than 30 days.
 *
 * Only the rule currently in force for each rule key is considered (older versions and superseded ones are history).
 * A rule without an owner is not reminded (the Register page still flags it). An owner is reminded at most once per rule per 7 days.
 * `--dry-run` only counts. Does nothing until the owner column exists (SQL / migration of 6 Nov 2026).
 */
class LegalBasisReviewReminders extends Command
{
    protected $signature = 'legal-basis:review-reminders {--dry-run : Count what would be sent, send nothing}';

    protected $description = 'Remind Legal Basis rule owners that a yearly review is due, overdue or waiting';

    public function handle(LegalBasisService $legal): int
    {
        if (!$legal->hasOwnerColumn()) {
            $this->warn('The owner column does not exist yet; nothing to remind.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $sent = 0;
        $rules = $legal->all()->filter(fn ($r) => $r->status !== 'superseded' && $r->effective_date <= now()->toDateString())
            ->groupBy('rule_key')->map->first();

        foreach ($rules as $r) {
            if (empty($r->owner_employee_id)) {
                continue;
            }
            $text = null;
            if ($r->status === 'active' && $r->review_days_left !== null && $r->review_days_left <= LegalBasisService::REVIEW_WARN_DAYS) {
                $text = $r->review_days_left < 0
                    ? "Legal Basis rule {$r->rule_key} ({$r->label}) is " . abs($r->review_days_left) . ' day(s) past its yearly review.'
                    : "Legal Basis rule {$r->rule_key} ({$r->label}) needs its yearly review within {$r->review_days_left} day(s).";
            } elseif ($r->status === 'needs_review' && !empty($r->updated_at) && \Illuminate\Support\Carbon::parse($r->updated_at)->lt(now()->subDays(30))) {
                $text = "Legal Basis rule {$r->rule_key} ({$r->label}) has been waiting for review since " . \Illuminate\Support\Carbon::parse($r->updated_at)->format('d M Y') . '.';
            }
            if ($text === null) {
                continue;
            }
            $sent += $this->remind((int) $r->owner_employee_id, $text, route('finance.legal-basis.index', ['module' => $r->module]) . '#reg-' . $r->module, $r->rule_key, $dry) ? 1 : 0;
        }

        $this->info(($dry ? '[DRY RUN] Would send ' : 'Sent ') . $sent . ' reminder(s).');

        return self::SUCCESS;
    }

    /** One reminder per owner and rule per 7 days (the rule key travels in the preview text, so two rules never block each other). */
    private function remind(int $employeeId, string $text, string $link, string $ruleKey, bool $dry): bool
    {
        $already = Notification::where('employee_id', $employeeId)->where('type', 'legal_basis_review')
            ->where('preview', 'like', '%' . $ruleKey . ' %')->where('created_at', '>=', now()->subDays(7))->exists();
        if ($already) {
            return false;
        }
        if (!$dry) {
            Notification::create(['employee_id' => $employeeId, 'type' => 'legal_basis_review', 'preview' => mb_substr($text, 0, 250), 'link' => $link]);
        }

        return true;
    }
}
