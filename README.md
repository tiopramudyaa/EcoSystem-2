# EcoSystem-2

Aplikasi internal sisi employee dan admin untuk operasional perusahaan: ticketing dan Delivery Support, delivery project, SLA, RPMO, Human Capital, Finance, reporting, serta Control Center untuk administrasi sistem. Portal sisi customer bernama **JARVIES** adalah aplikasi terpisah yang memakai database dan tabel `auth_users` yang sama.

## Daftar isi

1. [Tech stack](#tech-stack)
2. [Prasyarat](#prasyarat)
3. [Setup lokal](#setup-lokal)
4. [Menjalankan aplikasi](#menjalankan-aplikasi)
5. [Queue dan scheduler](#queue-dan-scheduler)
6. [Konfigurasi `.env`](#konfigurasi-env)
7. [Testing](#testing)
8. [User Guide](#user-guide)
9. [Deploy (Docker)](#deploy-docker)
10. [Struktur dan dokumentasi](#struktur-dan-dokumentasi)

## Tech stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.2 (platform Composer dikunci di 8.2.12) |
| Frontend | Blade, Vite 7, Tailwind CSS 4 |
| Auth dan keamanan | Laravel Sanctum (API), Google2FA (two-factor), Security Center |
| Dokumen | barryvdh/laravel-dompdf (PDF), maatwebsite/excel (Excel), bacon-qr-code |
| Integrasi | Microsoft Graph (email, OneDrive, Teams), Power Automate, IMAP, Web Push |
| AI | Anthropic SDK (Claude) dan OpenAI PHP client |
| Dokumentasi API | dedoc/scramble |
| Testing | PHPUnit 11 |

## Prasyarat

| Kebutuhan | Versi atau catatan |
|---|---|
| PHP | 8.2.x dengan ekstensi `pdo_mysql` atau `pdo_sqlite`, `mbstring`, `gd`, `zip`, `bcmath`. Di Windows, PHP bawaan XAMPP 8.2 sudah cukup |
| Composer | 2.x |
| Node.js dan npm | Untuk Vite |
| Database | MySQL untuk server (Dockerfile memasang `pdo_mysql`). `.env.example` memakai SQLite sebagai default lokal |
| Git | Untuk clone dan kontribusi |

## Setup lokal

```bash
git clone https://github.com/PUTRA11835/EcoSystem-2.git
cd EcoSystem-2

composer install
npm install

# Linux/macOS
cp .env.example .env
# Windows (cmd)
copy .env.example .env

php artisan key:generate
```

Atur koneksi database di `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

`DatabaseSeeder` menyiapkan instalasi bersih: role, menu dan permission matrix, hari libur, data wilayah, dan satu akun admin `ECI_ADMIN`. Password bawaannya ada di `database/seeders/EmployeeSeeder.php`. **Ganti password ini segera setelah login pertama**, dan jangan pernah memakainya di server.

Data employee dan customer asli tidak dimasukkan lewat seeder, melainkan lewat Import CSV di menu Backup & Export. Seeder data dummy (`CustomerSeeder`, `DeliveryProjectSeeder`, dan lainnya) sengaja dinonaktifkan di `DatabaseSeeder`. Aktifkan kembali bila butuh data contoh untuk development.

## Menjalankan aplikasi

```bash
composer dev
```

Perintah ini menjalankan empat proses sekaligus:

| Proses | Perintah |
|---|---|
| server | `php artisan serve` |
| queue | `php artisan queue:listen --tries=1` |
| logs | `php artisan pail --timeout=0` |
| vite | `npm run dev` |

Aplikasi terbuka di `http://localhost:8000`.

## Queue dan scheduler

### Queue

`QUEUE_CONNECTION=database`. Ada dua antrean:

| Antrean | Isi | Timeout worker di server |
|---|---|---|
| `default` | Job pendek: email, notifikasi, event SLA | 120 detik |
| `reports` | `GenerateWordReportJob` (Word Report Generator, 5 sampai 10 menit per job) | 900 detik |

> **Penting untuk lokal:** worker di `composer dev` hanya mendengarkan antrean `default`. Kalau sedang mengerjakan Word Report Generator, jalankan worker kedua di terminal terpisah. Tanpa itu, job laporan akan pending selamanya.
>
> ```bash
> php artisan queue:listen --queue=reports --timeout=900
> ```

`DB_QUEUE_RETRY_AFTER` harus lebih besar dari timeout worker terpanjang (900). Nilai yang disarankan adalah `1800`. Kalau lebih kecil, job laporan yang masih berjalan akan dianggap macet lalu diproses ulang.

### Scheduler

Semua jadwal didefinisikan di `routes/console.php`. Di server, cron eksternal harus memanggil `schedule:run` setiap menit:

```cron
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

Di lokal, jalankan scheduler hanya bila diperlukan:

```bash
php artisan schedule:work
```

| Jadwal | Command | Fungsi |
|---|---|---|
| Tiap menit | `email:process-inbox` | Membaca mailbox support, lalu membuat tiket atau menambah pesan ke tiket |
| Tiap menit | `tickets:open-reminders` | Reminder Teams untuk tiket yang masih open |
| Tiap menit | `teams:sync-chat-messages`, `teams:flush-outbox`, `teams:add-pending-guests` | Sinkronisasi group chat Teams dengan internal note tiket |
| Tiap 5 menit | `security:detect-anomalies` | Deteksi anomali keamanan |
| Tiap 15 menit | `schedule-monitor:check-*` | Memantau scheduler, queue backlog, dan kapasitas disk |
| Tiap jam | `tickets:deliverable-reminders` | Reminder dokumen deliverable yang belum lengkap |
| Tiap 6 jam | `schedule-monitor:check-integration-health` | Mengecek kredensial MS Graph, Anthropic, dan OpenAI |
| Harian | `activities:recompute-status`, `onedrive:audit-links --fix`, `ai:prune-conversations`, `notifications:project-reminders`, `notifications:timesheet-reminders`, `security:prune-logs` | Pemeliharaan dan reminder harian |

> **Peringatan:** `email:process-inbox` membaca mailbox yang diatur di variabel `MS_*`. Jangan mengisi kredensial mailbox produksi di lingkungan lokal atau staging tanpa pagar `POWER_AUTOMATE_ALLOWED_SUBMITTERS`. Tanpa pagar itu, email customer sungguhan bisa memicu tiket dan notifikasi Teams dari server yang datanya belum benar.

## Konfigurasi `.env`

`.env.example` sudah berisi penjelasan lengkap untuk tiap variabel. Kelompok yang paling penting:

| Kelompok | Variabel utama | Keterangan |
|---|---|---|
| Aplikasi | `APP_ENV`, `APP_DEBUG`, `APP_URL` | Di produksi `APP_URL` wajib berawalan `https://`. Tautan lampiran ke Teams juga dibangun dari `APP_URL` |
| Sesi | `SESSION_SECURE_COOKIE` | `true` hanya di produksi HTTPS. Di lokal `http`, nilai `true` membuat login gagal |
| Queue | `QUEUE_CONNECTION`, `DB_QUEUE_RETRY_AFTER` | Lihat bagian [Queue](#queue) |
| Microsoft Graph | `MS_TENANT_ID`, `MS_CLIENT_ID`, `MS_CLIENT_SECRET`, `MS_SENDER_EMAIL` | Email tiket, OneDrive, dan Teams |
| JARVIES | `JARVIES_URL`, `JARVIES_API_KEY`, `EXTERNAL_TICKET_API_KEY`, `CORS_ALLOWED_ORIGINS` | Integrasi dengan portal customer |
| AI | `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, `ANTHROPIC_*_SKILL_ID`, `AI_HISTORY_RETENTION_DAYS` | Pada instalasi baru, kedua API key wajib diisi. Model per asisten dipilih di Control Center, AI Settings |
| Power Automate | `POWER_AUTOMATE_*` | Integrasi Microsoft Teams. Flow yang URL-nya kosong otomatis dilewati |
| Teams sync | `TEAMS_SYNC_*` | Semua default mati, bisa dinyalakan per arah |

Skill ID untuk Word Report Generator (`ANTHROPIC_WORD_REPORT_SKILL_ID`) diisi dari output perintah berikut:

```bash
php artisan claude:upload-skill
```

Setiap kali `.env` diubah di server, jalankan:

```bash
php artisan optimize:clear
```

## Testing

```bash
composer test
# atau
php artisan test
```

Test berjalan di SQLite in-memory dengan queue `sync` dan mail `array` (lihat `phpunit.xml`), jadi tidak menyentuh database kerja.

## User Guide

Aplikasi punya User Guide dua bahasa di `resources/guide/en/` dan `resources/guide/id/`. **Setiap perubahan yang terlihat oleh pengguna wajib disertai pembaruan User Guide di commit yang sama.** Aturan lengkapnya (struktur topik, front matter, bagian Rules to know, dan aturan screenshot) ada di [`CLAUDE.md`](CLAUDE.md), `resources/guide/_IMAGES.md`, dan `docs/HRGA/USER-GUIDE-EDITOR.md`.

Sebelum menyatakan pekerjaan selesai, kedua perintah ini harus bersih:

```bash
php docs/HRGA/tools/guide-check.php
php artisan test tests/Unit/Guide
```

Admin juga bisa mengubah isi panduan lewat Control Center, User Guide Editor. Berkas di `resources/guide/` tetap menjadi sumber bawaan yang di-commit.

## Deploy (Docker)

`Dockerfile` membangun image `php:8.4-fpm` dengan ekstensi yang dibutuhkan, termasuk `pcntl`. Tanpa `pcntl`, worker tidak bisa menegakkan timeout job. Container menjalankan `supervisord` (`docker/supervisord.conf`) yang mengelola tiga proses:

| Program | Fungsi |
|---|---|
| `php-fpm` | Melayani request web |
| `queue-worker` | Antrean `default` |
| `queue-worker-reports` | Antrean `reports` |

Deploy dilakukan dengan rebuild dan restart container ini, tanpa service tambahan. Status worker bisa dicek dengan:

```bash
docker exec <container> supervisorctl status
```

Checklist `.env` produksi:

- `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` berawalan `https://` tanpa garis miring di akhir
- `SESSION_SECURE_COOKIE=true`
- `DB_QUEUE_RETRY_AFTER=1800`
- `POWER_AUTOMATE_ALLOWED_SUBMITTERS` **dikosongkan** di produksi dan **diisi** di dev atau staging
- Cron `schedule:run` setiap menit sudah aktif
- Jalankan `php artisan optimize:clear` setelah mengubah env

Batas upload diatur di Dockerfile: `upload_max_filesize=30M` dan `post_max_size=80M`, karena satu attachment tiket bisa sampai 25 MB.

## Struktur dan dokumentasi

| Lokasi | Isi |
|---|---|
| `routes/web.php`, `routes/api.php` | Route utama web dan API |
| `routes/delivery-support.php` | Route modul Delivery Support |
| `routes/lite.php` | Lite API (lihat `docs/lite-api.md`) |
| `routes/console.php` | Jadwal scheduler |
| `database/seeders/MenuSeeder.php` | Daftar menu dan permission matrix. Menu baru wajib ditambahkan di sini atau lewat migrasi menu |
| `config/user_guide.php` | Daftar bab User Guide |
| `resources/guide/` | Isi User Guide (en dan id) |
| `MULTI_ROLE_SYSTEM.md` | Referensi sistem multi-role: role, menu, CRUD per halaman, dan permission per tombol |
| `docs/power-automate-integration.md`, `docs/power-automate/` | Integrasi Power Automate dan Microsoft Teams |
| `docs/teams-chat-sync-design.md`, `docs/teams-sync-parity-design.md` | Rancangan sinkronisasi chat Teams |
| `docs/jarvies-*.md` | Integrasi dengan JARVIES |
| `docs/ai-assistant-sensitive-data-policy.md` | Kebijakan data sensitif untuk AI Assistant |
| `docs/planning/` | Roadmap dan rencana fitur |
| `docs/handoff/` | Catatan serah terima pekerjaan |

Dokumentasi API dibuat otomatis oleh Scramble dari route berawalan `/api`. Halamannya ada di path bawaan Scramble, dan aksesnya diatur oleh middleware di `config/scramble.php`.
