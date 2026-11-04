<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRole extends Model
{
    use HasFactory;

    protected $table = 'employee_role';

    protected $fillable = [
        'name',
        'description',
        'period_domain', // 'project' | 'support' | null — the timesheet period domain this role is held to (see PeriodService)
    ];

    public function getRoleIdAttribute()
    {
        return $this->id;
    }

    /** @return list<int> ids of the roles every new employee receives (config/roles.php → new_employee_roles, by name) */
    public static function newEmployeeRoleIds(): array
    {
        return static::query()
            ->whereIn('name', config('roles.new_employee_roles', []))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_role_assignment', 'role_id', 'employee_id', 'id', 'employee_id')
                    ->withTimestamps();
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu', 'role_id', 'menu_id')
            ->withPivot('can_view', 'can_create', 'can_edit', 'can_delete')
            ->withTimestamps();
    }
}
