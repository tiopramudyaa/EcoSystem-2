# Gerbang F: audit penjaga (Tugas A, B, C)

Rentang diff: 7d917c20..HEAD (6289e49e). Auditor read-only. Perubahan server (app, routes, database) pada rentang ini: KOSONG (git diff --stat -- app routes database tidak menghasilkan baris).

## Tugas A: keamanan perubahan

| # | Butir | Hasil | Bukti |
|---|---|---|---|
| A1 | CSS mask url('{{ asset(...) }}') | PASS | resources/views/partials/seasonal-loading.blade.php:125-126. Satu-satunya masukan adalah asset('images/eclectic_logo_nobg.png'), literal konstan, dicetak dengan {{ }} (ter-escape HTML). Tidak ada variabel pengguna. Di seasonal-preview.blade.php tidak ada mask, logo hanya <img src="{{ asset(...) }}"> (baris 71). Catatan: asset() bergantung APP_URL/host permintaan, bukan input pengguna; tanda kutip di host tidak mungkin lolos karena {{ }} meng-escape. |
| A2 | Teks lang baru | PASS | lang/en/ui.php:35-64 dan lang/id/ui.php:35-64: hanya string polos tanpa HTML. Dicetak lewat {{ __('ui.seasonal.loading.preparing') }} (seasonal-loading.blade.php:67). Judul/doa tema masuk lewat @json (seasonal-loading.blade.php:171) lalu textContent (baris 208-209), dan lewat Js::from (seasonal-preview.blade.php:117) lalu textContent (baris 201-202). Pemeriksaan em dash (U+2014) dan en dash (U+2013) pada seluruh baris tambahan non-docs diff: 0 temuan. Karakter ellipsis (U+2026) dipakai, bukan dash. |
| A3 | public/js/seasonal/stage.js | PASS | warmTextures baris 62-81 (hanya traverse adegan lokal, initTexture, setTimeout 50 ms plus rAF), release baris 16-21 (WeakMap, try/catch), reuseRenderer baris 88/119-125. Tidak ada eval, new Function, innerHTML, document.write, fetch, XHR. Import dinamis hanya THREE_URL konstan (baris 8,46) dan './scenes/' + name dengan name dibatasi allowlist SCENES (baris 49) sehingga bukan sumber tak tepercaya. |
| A4 | JS pratinjau | PASS | Data lewat Js::from (seasonal-preview.blade.php:117-124). Tidak ada innerHTML/insertAdjacent di berkas; teks diisi dengan textContent (baris 145, 201, 202, 212, 216, 218). poster.src dari URL asset server. |
| A5 | data-danger di confirm-modal | PASS | resources/views/partials/confirm-modal.blade.php:91: setAttribute('data-danger', '') hanya bila variant === 'danger' (nilai kosong konstan), selain itu removeAttribute. Tidak berasal dari input pengguna. (innerHTML pada baris 87 untuk ICONS adalah kode lama dari tabel konstan, bukan bagian diff ini.) |
| A6 | JS sidebar ringkas | PASS | dashboard.blade.php:108 membaca localStorage dan hanya menerima '1' (=== '1') dalam try/catch. setSidebarRail baris 2094-2113: tidak ada innerHTML; tooltip lewat el.setAttribute('title', tx.textContent.trim()) baris 2109; tulis localStorage hanya '1'/'0' dalam try/catch (baris 2097). Pendengar klik baris ~2125 memakai e.target.closest, tanpa data eksternal. Catatan non-keamanan: baris 2038 (btn.innerHTML tetap, kode lama pin). |

Risiko kecil A: (a) @json (bukan Js::from) di seasonal-loading.blade.php:168-176 dipertahankan dari port sumber; aman untuk nilai saat ini karena dikodekan json_encode default (tag script tidak lolos, slash di-escape). (b) data-danger bergantung pada selector CSS tema yang tidak diaudit di sini (sudah QA di step6-ui-function).
