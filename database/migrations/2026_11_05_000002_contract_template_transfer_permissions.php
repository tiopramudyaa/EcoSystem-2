<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;

/**
 * Izin terpisah per format untuk ekspor / impor Template kontrak (Contract › Templates), supaya pengguna non-teknis tidak dibanjiri
 * pilihan yang tidak mereka perlukan. Lahir aktif HANYA untuk EC Administrator (aturan baku MenuRegistrar); pemilik sistem
 * membagikannya lewat Control Center → Menu Access.
 *
 *   export-pdf    contoh kontrak untuk dibaca/dicetak (tak bisa diimpor)
 *   export-html   satu template sebagai berkas HTML yang bisa disunting
 *   export-json   berkas cadangan semua template (memindahkan template antarlingkungan)
 *   import-html   mengimpor berkas HTML (satu template)
 *   import-json   mengimpor berkas cadangan (.json; satu atau banyak template)
 * Impor PDF tidak ada: PDF tidak menyimpan struktur teks dan {{placeholder}}, jadi tidak bisa dibaca kembali dengan aman.
 * Impor tetap butuh kotak Create pada menu Templates (dan Edit untuk memperbarui template yang sudah ada).
 */
return new class extends Migration
{
    public const SLUGS = [
        'general.contracts.templates.export-pdf'  => 'Template export — PDF',
        'general.contracts.templates.export-html' => 'Template export — HTML (editable)',
        'general.contracts.templates.export-json' => 'Template export — backup file (.json)',
        'general.contracts.templates.import-html' => 'Template import — HTML file',
        'general.contracts.templates.import-json' => 'Template import — backup file (.json)',
    ];

    public function up(): void
    {
        MenuRegistrar::register('general.contracts', self::SLUGS, 13, 'function');
    }

    public function down(): void
    {
        MenuRegistrar::remove(array_keys(self::SLUGS));
    }
};
