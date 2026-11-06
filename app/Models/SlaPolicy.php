<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $table = 'sla_policies';

    protected $fillable = [
        'delivery_support_id',
        'priority',
        'scale',
        'response_hours',
        'resolution_hours',
        'is_24_hours',
        'work_start_time',
        'work_end_time',
        'break_start_time',
        'break_end_time',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'response_hours'   => 'decimal:2',
        'resolution_hours' => 'decimal:2',
        'is_24_hours'      => 'boolean',
        'is_active'        => 'boolean',
    ];

    // Very High priority always uses 24/7 calendar hours
    public function getIs24HoursAttribute($value): bool
    {
        return $this->attributes['priority'] === 'Very High' ? true : (bool) $value;
    }

    public function deliverySupport()
    {
        return $this->belongsTo(\App\Models\DeliverySupport::class, 'delivery_support_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by', 'employee_id');
    }

    public function ticketSlas()
    {
        return $this->hasMany(TicketSla::class, 'sla_policy_id');
    }

    /**
     * Cari policy yang cocok untuk delivery support + priority + scale tertentu.
     *
     * $policyIndex (opsional) = hasil activeIndex(), dipakai saat banyak tiket dicocokkan
     * sekaligus supaya tidak ada query maupun iterasi koleksi per tiket.
     */
    public static function findFor(?int $deliverySupportId, string $priority, string $scale, ?array $policyIndex = null): ?self
    {
        if ($policyIndex !== null) {
            return $policyIndex[self::indexKey($deliverySupportId, $priority, $scale)] ?? null;
        }

        return self::where('priority', $priority)
            ->where('scale', $scale)
            ->where('is_active', true)
            ->where('delivery_support_id', $deliverySupportId)
            ->first();
    }

    /**
     * Semua policy aktif sebagai peta kunci => policy (id terkecil menang bila kembar).
     * Teks priority/scale dibandingkan case-insensitive, sama seperti collation MySQL
     * pada query findFor().
     *
     * @return array<string, self>
     */
    public static function activeIndex(): array
    {
        $index = [];

        foreach (self::where('is_active', true)->orderBy('id')->get() as $policy) {
            $key = self::indexKey(
                $policy->delivery_support_id === null ? null : (int) $policy->delivery_support_id,
                (string) $policy->priority,
                (string) $policy->scale
            );
            $index[$key] ??= $policy;
        }

        return $index;
    }

    private static function indexKey(?int $deliverySupportId, string $priority, string $scale): string
    {
        return ($deliverySupportId ?? 'null') . '|' . mb_strtolower($priority) . '|' . mb_strtolower($scale);
    }
}
