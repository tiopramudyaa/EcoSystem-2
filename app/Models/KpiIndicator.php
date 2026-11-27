<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiIndicator extends Model
{
    protected $table = 'kpi_indicators';

    protected $fillable = [
        'template_id',
        'name',
        'answer_type',
        'rating_max',
        'description',
        'measurement_unit',
        'target_value',
        'weight',
        'order_seq',
    ];

    protected $casts = [
        'target_value' => 'float',
        'weight'       => 'float',
        'rating_max'   => 'integer',
        'order_seq'    => 'integer',
    ];

    /**
     * A paragraph indicator collects free text only - no rating, no weight.
     */
    public function isParagraph(): bool
    {
        return ($this->answer_type ?? 'rating') === 'paragraph';
    }

    /**
     * Scale cap for this indicator. Always the template's scale: one scale per template, so the stars can never differ between indicators (a stored per-indicator rating_max is ignored).
     */
    public function effectiveMax(): int
    {
        // Lazy-loads the template when it isn't eager-loaded, so the star
        // count always follows the template's scoring scale (5, 7, ...) -
        // never a hardcoded 5.
        return (int) ($this->template?->scaleMax() ?: 5);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function template()
    {
        return $this->belongsTo(KpiTemplate::class, 'template_id');
    }

    public function evaluationDetails()
    {
        return $this->hasMany(KpiEvaluationDetail::class, 'indicator_id');
    }
}
