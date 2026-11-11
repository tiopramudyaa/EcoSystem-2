<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

/**
 * Satu baris jadwal Release bulanan milik sebuah cost item Plan Cost.
 * period ("YYYY-MM") selalu diturunkan dari schedule_date oleh controller.
 */
class DeliveryProjectCostReleaseSchedule extends Model
{
    use HasFactory, Auditable, Concerns\TouchesProjectActivity;

    protected static ?string $auditModule = 'Delivery Project';

    protected $table = 'delivery_project_cost_release_schedules';

    protected $fillable = [
        'delivery_project_cost_id',
        'schedule_date',
        'period',
        'amount',
        'notes',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'amount'        => 'float',
    ];

    public function costItem()
    {
        return $this->belongsTo(DeliveryProjectCost::class, 'delivery_project_cost_id');
    }

    /** Jadwal tidak menyimpan FK project — ambil lewat cost induknya. */
    protected function projectIdForActivity()
    {
        return DeliveryProjectCost::whereKey($this->delivery_project_cost_id)->value('delivery_projects_id');
    }
}
