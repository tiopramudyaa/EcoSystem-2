<?php

namespace App\Models\Compliance;

use App\Support\Compliance\Tones;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Legal → Compliance → Settings: one choice of a dropdown of the Content tab. `list` says which dropdown (see LISTS); the choices of a list
 * are managed by the admin. A choice stored on a row is its `key`.
 */
class ComplianceOption extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    /** list => [model, column that stores the choice] — what decides whether a choice is in use. */
    public const LISTS = [
        'rule_severity' => [ComplianceRule::class, 'severity'],
        'alert_color' => [ComplianceAlert::class, 'color_type'],
        'session_status' => [ComplianceSession::class, 'status'],
        'session_period' => [ComplianceSession::class, 'session_type'],
    ];

    protected $fillable = ['list', 'key', 'label', 'tone', 'sort_order', 'is_active', 'is_system'];

    protected $casts = ['is_active' => 'boolean', 'is_system' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget('compliance.options');
        static::saved($forget);
        static::deleted($forget);
    }

    /** @return Collection<string, ComplianceOption> the choices of a list in order, by key (cached) */
    public static function list(string $list): Collection
    {
        $all = Cache::remember('compliance.options', 600, fn () => static::orderBy('sort_order')->orderBy('id')->get()->groupBy('list'));

        return ($all[$list] ?? collect())->keyBy('key');
    }

    /** key => label of the active choices, plus $current if it was switched off since the row was saved. */
    public static function choices(string $list, ?string $current = null): array
    {
        return static::list($list)->filter(fn ($o) => $o->is_active || $o->key === $current)->map->label->all();
    }

    public static function label(string $list, ?string $key): string
    {
        return static::list($list)->get($key)?->label ?? (string) $key;
    }

    public static function tone(string $list, ?string $key): string
    {
        return static::list($list)->get($key)?->tone ?? 'gray';
    }

    public static function badge(string $list, ?string $key): string
    {
        return Tones::badge(static::tone($list, $key));
    }

    public function isUsed(): bool
    {
        [$model, $column] = self::LISTS[$this->list];

        return $model::where($column, $this->key)->exists();
    }
}
