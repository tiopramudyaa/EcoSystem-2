<?php

namespace App\Models\ObjectFund;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan alur per Object. Tabel satu baris, dibaca lewat current().
 * `enabled` = false berarti Reimbursement berjalan persis seperti konsep lama.
 */
class ObjectFlowSetting extends Model
{
    protected $table = 'object_flow_settings';

    protected $fillable = [
        'enabled', 'cash_advance_enabled',
        'project_admin_enabled', 'project_manager_enabled', 'project_order',
        'project_fallback_role_id', 'project_fallback_employee_id',
        'support_lead_enabled', 'support_manager_enabled',
        'support_fallback_role_id', 'support_fallback_employee_id',
        'policy_project', 'policy_office', 'policy_support', 'access_project', 'access_support', 'updated_by',
    ];

    protected $casts = [
        'enabled'                 => 'boolean',
        'cash_advance_enabled'    => 'boolean',
        'project_admin_enabled'   => 'boolean',
        'project_manager_enabled' => 'boolean',
        'support_lead_enabled'    => 'boolean',
        'support_manager_enabled' => 'boolean',
    ];

    public const POLICY_WARN  = 'warn';
    public const POLICY_BLOCK = 'block';
    public const POLICIES     = [self::POLICY_WARN, self::POLICY_BLOCK];

    public const ORDER_ADMIN_FIRST   = 'admin_first';
    public const ORDER_MANAGER_FIRST = 'manager_first';

    private static ?self $cached = null;

    public static function current(): self
    {
        return self::$cached ??= (self::first() ?? self::create([]));
    }

    public static function forgetCache(): void
    {
        self::$cached = null;
    }

    /** Kebijakan dana untuk satu jenis Object. */
    public function policyFor(string $objectType): string
    {
        $value = match ($objectType) {
            FundPool::TYPE_PROJECT => $this->policy_project,
            FundPool::TYPE_OFFICE  => $this->policy_office,
            FundPool::TYPE_SUPPORT => $this->policy_support,
            default                => self::POLICY_WARN,
        };

        return in_array($value, self::POLICIES, true) ? $value : self::POLICY_WARN;
    }
}
