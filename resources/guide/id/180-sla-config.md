---
key: sla-config
chapter: sla
order: 10
title: SLA Config
summary: Tentukan target respons dan penyelesaian sebuah delivery support menurut prioritas dan skala, serta jam kerja yang dihitung.
requires: [sla.config]
updated: 2026-11-02
---
`SLA` › `SLA Config` adalah tempat service level agreement ditetapkan. Sebuah policy menyatakan seberapa cepat ticket dengan satu prioritas dan skala harus mendapat respons pertama dan penyelesaian di bawah satu delivery support.

## Siapa yang menggunakan

Helpdesk dan support manager, serta role mana pun yang diberi menu `SLA` › `SLA Config` di Menu Access. Policy yang sama juga dapat diubah di bagian `SLA` pada sebuah delivery support (lihat `Delivery` › `Support`).

## Sebelum Anda mulai

- Policy milik sebuah delivery support. Buat delivery support-nya dulu.
- Ticket menerima policy dari delivery support yang terhubung dengannya, dengan prioritas dan skala yang sama. Ticket tanpa policy aktif yang cocok tidak punya SLA.
- Hanya policy aktif yang berlaku, dan hanya untuk ticket baru.

## Langkah demi langkah

![Halaman SLA Configuration dengan kartu ringkasan dan tabel policy](images/guide/sla-config.png)

*Tangkapan layar dengan data contoh.*

1. Buka `SLA` › `SLA Config`. Kartu menampilkan `Total Policies`, `Active`, `Global Defaults` (policy yang tidak terikat satu delivery support), dan `24/7 Policies`.
2. Di tabel, setiap policy menampilkan namanya, delivery support, prioritas (`1: Very High` sampai `4: Low`), skala, target respons dan penyelesaian dalam jam, mode, dan status. Pakai daftar `All Delivery Supports` dan ikon corong untuk menyaring.
3. Untuk menambah policy pilih `Add Policy`.

![Jendela Add SLA Policy dengan delivery support, prioritas, skala, respons, penyelesaian, dan jam kerja](images/guide/sla-config-add.png)

*Tangkapan layar dengan data contoh.*

4. Pilih `Delivery Support`, `Priority`, dan `Scale` (`Simple`, `Medium`, atau `Complex`). `Label preview` di atas menampilkan nama policy.
5. Isi waktu `Response` dalam jam atau menit dan waktu `Resolution (Hour)`.
6. Centang `24/7 Calendar Hours` untuk menghitung setiap jam. Biarkan mati untuk menghitung jam kerja saja, dan isi `Work Start` dan `Work End`, serta `Break Start` dan `Break End` bila ada istirahat.
7. Pilih `Save Policy`. Pakai `Edit` pada baris untuk mengubah policy, matikan `Policy Active` agar tidak diterapkan ke ticket baru, atau hapus setelah konfirmasi `Delete Policy?`.

## Apa yang terjadi selanjutnya

Ticket baru dari delivery support, prioritas, dan skala itu langsung mendapat targetnya. Ticket yang sedang berjalan tetap memakai policy yang dimilikinya. Hasilnya dibaca di `SLA` › `SLA Report`.

## Pertanyaan umum

**Sebuah ticket tidak menampilkan SLA.** Ticket belum terhubung ke delivery support, atau tidak ada policy aktif untuk prioritas dan skalanya.

**Jam SLA menghitung malam dan akhir pekan.** Policy diatur ke `24/7 Calendar Hours`. Ubah dan matikan opsi itu.

<!-- sources: routes/web.php, resources/views/admin/sla/config.blade.php, app/Models/SlaPolicy.php -->
