<?php

namespace App\Models\ObjectFund;

use Illuminate\Database\Eloquent\Model;

/** Pengecualian aturan akses untuk SATU project atau tiket (mis. tiket A terbuka untuk semua). */
class ObjectAccessRule extends Model
{
    protected $table = 'object_access_rules';

    protected $fillable = ['object_type', 'ref_id', 'ref_label', 'mode', 'notes', 'updated_by'];

    protected $casts = ['ref_id' => 'integer'];
}
