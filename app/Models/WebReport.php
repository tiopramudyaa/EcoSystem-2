<?php

namespace App\Models;

use App\Models\Compliance\ComplianceReportStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * A report sent through the public Web Report form (/web-report) or, with channel = compliance, from My Compliance. Everything that identifies a person or tells the story is stored
 * encrypted. NOT Auditable on purpose: the audit log would copy the record into a readable snapshot. Who did what is kept in
 * web_report_events (action + status only, never content).
 */
class WebReport extends Model
{
    protected $fillable = [
        'tracking_code', 'channel', 'employee_id', 'reporter_name', 'reporter_contact', 'reported_name', 'chronology', 'evidence_path', 'evidence_name',
        'status', 'internal_note', 'public_note', 'lang', 'due_at', 'closed_at',
    ];

    protected $casts = [
        'reporter_name' => 'encrypted', 'reporter_contact' => 'encrypted', 'reported_name' => 'encrypted',
        'chronology' => 'encrypted', 'internal_note' => 'encrypted',
        'due_at' => 'datetime', 'closed_at' => 'datetime',
    ];

    public const CHANNEL_WEB = 'web';                 // the public form, code WR-…, handled in Control Center → Web Report Management
    public const CHANNEL_COMPLIANCE = 'compliance';   // sent from My Compliance, code CL-…, handled in Compliance Management → Reports

    public function scopeWeb($q)
    {
        return $q->where('channel', self::CHANNEL_WEB);
    }

    public function scopeCompliance($q)
    {
        return $q->where('channel', self::CHANNEL_COMPLIANCE);
    }

    /**
     * Every status of a channel, key => label. The public form uses config/web-report.php; compliance reports use the statuses managed in
     * Compliance Management → Settings.
     *
     * @return array<string, string>
     */
    public static function statuses(string $channel = self::CHANNEL_WEB): array
    {
        return $channel === self::CHANNEL_COMPLIANCE
            ? ComplianceReportStatus::ordered()->map->label->all()
            : config('web-report.statuses');
    }

    /** What the handling dropdown offers: the active statuses, plus the one the report has now. */
    public static function choices(string $channel, ?string $current = null): array
    {
        if ($channel !== self::CHANNEL_COMPLIANCE) {
            return self::statuses($channel);
        }

        return ComplianceReportStatus::ordered()->filter(fn ($s) => $s->is_active || $s->key === $current)->map->label->all();
    }

    /** Keys of the statuses that close a report. */
    public static function finalStatuses(string $channel = self::CHANNEL_WEB): array
    {
        return $channel === self::CHANNEL_COMPLIANCE
            ? ComplianceReportStatus::ordered()->filter->is_final->keys()->all()
            : ['closed'];
    }

    /** Days within which the reporter should get a follow-up (the promise made on the public page). */
    public static function followUpDays(string $channel = self::CHANNEL_WEB): int
    {
        return $channel === self::CHANNEL_COMPLIANCE
            ? max(1, (int) AppConfig::get('compliance_follow_up_days', config('web-report.follow_up_days')))
            : (int) config('web-report.follow_up_days');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::finalStatuses($this->channel), true);
    }

    public function isOverdue(): bool
    {
        return !$this->isFinal() && $this->due_at && $this->due_at->isPast();
    }

    public function statusLabel(): string
    {
        return self::statuses($this->channel)[$this->status] ?? $this->status;
    }

    /** Badge classes of the status. */
    public function statusTone(): string
    {
        if ($this->channel === self::CHANNEL_COMPLIANCE) {
            return ComplianceReportStatus::ordered()->get($this->status)?->toneClasses() ?? ComplianceReportStatus::TONES['gray'];
        }

        return ['new' => 'bg-red-100 text-red-700', 'in_review' => 'bg-amber-100 text-amber-700', 'investigating' => 'bg-violet-100 text-violet-700', 'closed' => 'bg-gray-100 text-gray-500'][$this->status] ?? '';
    }
}
