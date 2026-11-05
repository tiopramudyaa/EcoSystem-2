<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

/**
 * Satu jadwal release bulanan sebuah cost item Plan Cost Delivery Support —
 * mirror DeliveryProjectCostReleaseSchedule.
 */
class DeliverySupportCostReleaseSchedule extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Delivery Support';

    protected $table = 'delivery_support_cost_release_schedules';

    protected $fillable = [
        'delivery_support_cost_id',
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
        return $this->belongsTo(DeliverySupportCost::class, 'delivery_support_cost_id');
    }
}
