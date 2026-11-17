<?php

namespace App\Models\Compliance;

use App\Models\WebReport;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Legal → Compliance → Settings: a step in the handling of a compliance report (New, In review, Investigating, Closed, …).
 * `new` is where every report starts and cannot be removed; a status flagged final closes the report.
 */
class ComplianceReportStatus extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    public const INITIAL = 'new';

    /** tone => badge classes */
    public const TONES = \App\Support\Compliance\Tones::BADGE;

    protected $fillable = ['key', 'label', 'tone', 'is_final', 'is_active', 'sort_order'];

    protected $casts = ['is_final' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget('compliance.report-statuses');
        static::saved($forget);
        static::deleted($forget);
    }

    /** Every status in order (cached): key => row. */
    public static function ordered(): \Illuminate\Support\Collection
    {
        return Cache::remember('compliance.report-statuses', 600, fn () => static::orderBy('sort_order')->orderBy('id')->get()->keyBy('key'));
    }

    public function isUsed(): bool
    {
        return WebReport::compliance()->where('status', $this->key)->exists();
    }

    public function isSystem(): bool
    {
        return $this->key === self::INITIAL;
    }

    public function toneClasses(): string
    {
        return self::TONES[$this->tone] ?? self::TONES['gray'];
    }
}
