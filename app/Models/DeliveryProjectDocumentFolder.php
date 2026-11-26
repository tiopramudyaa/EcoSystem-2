<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Folder dokumen sebuah Delivery Project (section Project Documents).
 *
 * Folder standar (is_default) dibuat otomatis untuk setiap project dan tidak
 * bisa di-rename atau dihapus. Folder tambahan user diberi nomor berurutan mulai
 * FIRST_CUSTOM_NUMBER (11, 12, 13, ...). Nama tampilan = "NN. Nama".
 */
class DeliveryProjectDocumentFolder extends Model
{
    protected $table = 'delivery_project_document_folders';

    protected $fillable = [
        'delivery_projects_id',
        'number',
        'name',
        'is_default',
        'onedrive_folder_id',
    ];

    protected $casts = [
        'number'     => 'integer',
        'is_default' => 'boolean',
    ];

    protected $appends = ['display_name'];

    /** Folder standar: nomor => nama, dalam urutan tampil. */
    public const DEFAULTS = [
        1 => 'Discover',
        2 => 'Prepare',
        3 => 'Explore',
        4 => 'Realize',
        5 => 'Deploy',
        6 => 'Run',
        0 => 'Other',
    ];

    public const DISCOVER_NUMBER     = 1;
    public const OTHER_NUMBER        = 0;
    public const FIRST_CUSTOM_NUMBER = 11;

    public function project()
    {
        return $this->belongsTo(DeliveryProject::class, 'delivery_projects_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return self::formatDisplayName($this->number, $this->name);
    }

    public static function formatDisplayName(int $number, string $name): string
    {
        return sprintf('%02d. %s', $number, $name);
    }

    /**
     * Urutan tampil: 01..06, 00. Other, lalu folder tambahan (11, 12, ...).
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN number = 0 THEN 1 ELSE 0 END')
            ->orderBy('number');
    }

    /**
     * Pastikan folder standar ada untuk project ini, dan dokumen yang belum punya
     * folder dimasukkan ke "00. Other". Idempotent dan murah (satu query baca
     * bila semuanya sudah lengkap). Ditulis lewat query builder: ini sinkronisasi
     * sistem, bukan aktivitas user, jadi Last Update project tidak bergeser.
     */
    public static function ensureDefaults(DeliveryProject $project): void
    {
        $existing = DB::table('delivery_project_document_folders')
            ->where('delivery_projects_id', $project->id)
            ->pluck('id', 'number');

        $now  = now();
        $rows = [];
        foreach (self::DEFAULTS as $number => $name) {
            if (!$existing->has($number)) {
                $rows[] = [
                    'delivery_projects_id' => $project->id,
                    'number'               => $number,
                    'name'                 => $name,
                    'is_default'           => true,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('delivery_project_document_folders')->insertOrIgnore($rows);
            $existing = DB::table('delivery_project_document_folders')
                ->where('delivery_projects_id', $project->id)
                ->pluck('id', 'number');
        }

        DB::table('documents')
            ->where('delivery_projects_id', $project->id)
            ->whereNull('folder_id')
            ->update(['folder_id' => $existing[self::OTHER_NUMBER]]);
    }

    /** Folder standar bernomor tertentu milik project (setelah ensureDefaults). */
    public static function defaultFolder(DeliveryProject $project, int $number): self
    {
        self::ensureDefaults($project);

        return self::where('delivery_projects_id', $project->id)
            ->where('number', $number)
            ->firstOrFail();
    }

    /**
     * Nomor folder tambahan berikutnya: satu di atas nomor tambahan tertinggi,
     * minimal 11. Celah di tengah (mis. 12 dihapus saat 13 masih ada) tidak diisi
     * ulang; nomor tertinggi yang dihapus terpakai lagi oleh folder berikutnya.
     */
    public static function nextCustomNumber(int $projectId): int
    {
        $max = (int) self::where('delivery_projects_id', $projectId)
            ->where('is_default', false)
            ->max('number');

        return max(self::FIRST_CUSTOM_NUMBER, $max + 1);
    }
}
