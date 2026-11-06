---
key: dl-project-registers
chapter: delivery
order: 12
title: Register project: Stakeholders, Issues, Risks, dan WRICEF
summary: Kelola register stakeholder, issue log, register risiko, dan WRICEF log sebuah project, serta lihat semua issue lintas project.
requires: [delivery-project.stakeholder.view, delivery-project.issue-log.view, delivery-project.risk.view, delivery-project.wricef.view]
updated: 2026-11-02
---
Empat register di halaman detail project mencatat apa yang bisa salah dan apa yang harus dibangun. Register adalah ingatan kerja project: jaga tetap mutakhir sebelum setiap rapat status.

## Siapa yang menggunakan

Project head, project administrator, dan tim project. Setiap register punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access.

## Sebelum Anda mulai

- Sebagian layar di register ini berbahasa Indonesia. Nama kolom dan tombol sama seperti yang dijelaskan di sini.
- ID dibuat otomatis (`SH-001`, `ISS-001`, `RSK-001`). Anda tidak mengetiknya.

## Langkah demi langkah

### Issue Log

![Project Issue Log dengan issue, prioritas, status, dan owner](images/guide/dlv-project-issues.png)

*Tangkapan layar dengan data contoh.*

1. Di halaman detail project pilih tab `Issues` lalu `Add Issue`.
2. Jelaskan issue dan isi module, tanggal teridentifikasi, prioritas (`H`, `M`, atau `L`), owner, estimasi tanggal selesai, dan apakah perlu eskalasi (`Y` atau `N`). Tambahkan dampak dan komentar pelacakan seiring berjalan.
3. Saat selesai, atur status dan tanggal tutup.
4. Untuk melihat issue semua project sekaligus, buka `Delivery` › `Project` dan tab `Issues`: setiap baris menampilkan customer, ID, deskripsi, prioritas, status, owner, status project, dan tanggal.

### Risk Register

![Project Risk Register dengan tipe, kategori, probabilitas, dampak, dan skor](images/guide/dlv-project-risks.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `Risks` lalu `Add Risk`.
2. Isi tipe risiko (`Threat` atau `Opportunity`), kategori, deskripsi, penyebab, dan dampak ke project. Beri probabilitas `P` dan dampak `I` dari 1 sampai 5.
3. `Score` adalah `P × I`. Skor 12 atau lebih adalah `High`, 5 sampai 11 adalah `Medium`, dan 4 atau kurang adalah `Low`. Pilih strategi respons, rencana mitigasi, owner, dan tanggal target.
4. `Risk Dashboard` merangkum register. Sebuah risiko dapat dikaitkan dari issue lewat project risk ID-nya.

### Stakeholder Register

1. Pilih tab `Stakeholders` lalu `Add Stakeholder`.
2. Isi nama, peran, organisasi, kategori, dan tingkat `Power` serta `Interest`. Kuadran power-interest dihitung untuk Anda.
3. Catat sikap saat ini dan yang diharapkan, ekspektasi, strategi engagement, seberapa sering dan bagaimana berkomunikasi, dan PIC internal.

### WRICEF Log

![WRICEF Log dengan SAP module, kategori, ID objek, dan status](images/guide/dlv-project-wricef.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `WRICEF` lalu `Add WRICEF` untuk setiap workflow, report, interface, conversion, enhancement, atau form.
2. Isi SAP module, kategori, nama, prioritas, requestor, effort dalam mandays, dan persetujuan. ID objek dibentuk dari module dan kategori, misalnya `MM` ditambah `Report` menjadi `MMR001`.
3. Lacak tiga tahap berurutan: `FSD`, `Development`, dan `Testing`, masing-masing dengan PIC, tanggal, status, dan catatan. Baris total menampilkan total mandays.

## Apa yang terjadi selanjutnya

Register langsung memperbarui project. Issue dan risiko yang terbuka adalah yang ditinjau project head dan customer di rapat status.

## Pertanyaan umum

**Saya tidak bisa mengetik ID.** ID dibuat otomatis berurutan.

**Level risiko tampak salah.** Level mengikuti skor. Ubah `P` atau `I` dan levelnya berubah.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/issues/index.blade.php -->
