<?php

namespace App\Models\Compliance;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Legal → Compliance: a topic that groups knowledge-base articles and rules (business ethics, pricing, SAP, reporting …). */
class ComplianceCategory extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    protected $fillable = ['name', 'slug', 'icon', 'color', 'description', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function articles()
    {
        return $this->hasMany(ComplianceArticle::class, 'category_id');
    }
}
