---
key: dl-project-planning-cost
chapter: delivery
order: 13
title: Planning project dan Plan Cost
summary: Rencanakan fase dan aktivitas project, pantau progresnya, dan kelola budget, release, serta biaya aktual.
requires: [delivery-project.planning.view, delivery-project.plan-cost.view]
updated: 2026-11-02
---
Bagian `Planning` dan `Plan Cost` sebuah project menjawab dua pertanyaan: kapan pekerjaan selesai, dan berapa biayanya. Keduanya mengalir ke progres dan profit yang tampil di tempat lain.

## Siapa yang menggunakan

Project head, project administrator, dan project manager. Setiap bagian punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access.

## Sebelum Anda mulai

- Planning menentukan `Category`, `Phase`, dan `Go Live Estimated` project. Sebelum planning ada, kolom itu kosong.
- Project yang ditutup tidak bisa direncanakan atau diberi biaya. Buka kembali dulu.

## Langkah demi langkah

### Planning

1. Di halaman detail project pilih tab `Planning`. Bagian `Project Planning` menampilkan fase, grup, tahap, dan aktivitas dengan bobot, deskripsi, objek, mulai, selesai, hari, status, dan progres.
2. Beralih antara `Table View` dan `Gantt Chart`. Pakai `Expand All`, `Collapse All`, dan `Refresh` untuk membaca pohonnya.
3. Untuk menyusun atau mengubah rencana, pilih `Manage Full Planning`. Buat fase dulu, lalu grup, tahap, dan aktivitas di bawahnya, dan beri tiap item bobot, tanggal, dan status.
4. Aktivitas yang ditandai `Go-Live` menentukan `Go Live Estimated` project. Progres project dihitung dari bobot.
5. Untuk membandingkan semua project, buka `Delivery` › `Project` dan tab `Planning`. `Project Planning List` menampilkan tiap project dengan progres, status, budget, dan tanggal dibuat, dan dapat disaring menurut status.

![Project Planning List dengan progres, status, dan budget setiap project](images/guide/dlv-project-planning-list.png)

*Tangkapan layar dengan data contoh.*

### Plan Cost

![Bagian Plan Cost dengan total budget, release, aktual, dan tabel biaya](images/guide/dlv-project-plan-cost.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `Plan Cost`. Lima kartu menampilkan `TOTAL BUDGET`, `TOTAL RELEASE`, `TOTAL ACTUAL`, `AVAIL. BUDGET`, dan `AVAIL. RELEASE`.
2. Pilih `Add Cost Item` untuk menambah baris biaya dengan kode, nama, budget, dan tipe biaya (indirect atau direct). Pakai `Add sub-item` di bawah sebuah baris untuk merincinya, dengan jumlah, periode, dan dokumen bila ada.
3. Baca tabelnya: `Avail. Budget` adalah budget dikurangi release, dan `Avail. Release` adalah release dikurangi aktual.
4. Pilih `Export Excel` untuk mengunduh rekapnya.

## Apa yang terjadi selanjutnya

Progres, go-live, dan angka budget diperbarui di project dan di daftar. Plan cost juga mengalir ke gross profit di `Delivery Information`.

## Pertanyaan umum

**Fase dan kategori kosong.** Planning belum dibuat.

**Progres tidak bergerak.** Progres mengikuti status dan bobot aktivitas. Periksa bahwa aktivitas punya bobot.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/project-planning/index.blade.php -->
