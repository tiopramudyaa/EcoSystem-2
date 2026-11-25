<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Template pilihan kolom export. Lihat App\Support\ExportColumns.
 *
 * Personal (is_global = false): hanya terlihat dan bisa dipakai pemiliknya.
 * Global (is_global = true): terlihat dan bisa dipakai semua orang yang membuka
 * export itu, tapi hanya pemiliknya yang boleh menimpanya.
 */
class ExportTemplate extends Model
{
    protected $table = 'export_templates';

    protected $fillable = ['employee_id', 'export_type', 'name', 'is_global', 'columns'];

    protected $casts = [
        'columns'   => 'array',
        'is_global' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
