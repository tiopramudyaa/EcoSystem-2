<?php

namespace App\Models\Compliance;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Legal → Compliance: a knowledge-base article. `content` is HTML that was sanitised on save (App\Support\Compliance\HtmlSanitizer). */
class ComplianceArticle extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    protected $fillable = ['category_id', 'title', 'summary', 'content', 'tags', 'is_featured', 'is_active', 'views', 'updated_by'];

    protected $casts = ['is_featured' => 'boolean', 'is_active' => 'boolean', 'views' => 'integer'];

    public function category()
    {
        return $this->belongsTo(ComplianceCategory::class, 'category_id');
    }
}
