<?php

// Hanya grup tema musiman (diekstrak dari aldy_merge).
return [
    'seasonal' =>     array (
      'title' => 'Tema musiman',
      'subtitle' => 'Warna dan dekorasi perayaan di dashboard Anda',
      'follows_admin' => 'Default mengikuti pilihan administrator Anda, saat ini :theme.',
      'opt_out' => 'Pilih Off untuk mematikannya hanya bagi Anda.',
      'manage' => 'Kelola default global (Admin)',
      'default' => 'Default',
      'off' => 'Off',
      'preview' => 
      array (
        'play' => 'Play preview',
        'close' => 'Close preview',
        'hint' => 'Memutar pratinjau singkat layar loading untuk tema yang dipilih. Tidak ada unduhan tambahan sampai Anda menekan tombolnya.',
        'unavailable' => 'Pilih tema yang memiliki layar loading untuk melihat pratinjaunya.',
        'dialog_label' => 'Pratinjau layar loading: :theme',
        'poster_alt' => 'Gambar diam layar loading :theme',
        'still_note' => 'Menampilkan gambar diam karena animasi tidak dapat berjalan di perangkat ini atau dengan pengaturan Anda saat ini.',
      ),
      'themes' => 
      array (
        'none' => 'Off',
        'natal' => 'Natal',
        'tahun_baru' => 'Tahun Baru',
        'ramadan' => 'Ramadan',
        'lebaran' => 'Lebaran',
        'imlek' => 'Imlek',
        'hut_ri' => 'HUT RI',
      ),
      'loading' => 
      array (
        'natal' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Selamat Natal dari Eclectic Consulting',
        ),
        'tahun_baru' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Selamat Tahun Baru dari Eclectic Consulting',
        ),
        'ramadan' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Selamat menunaikan ibadah Ramadan',
        ),
        'lebaran' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Selamat Hari Raya Idulfitri, mohon maaf lahir dan batin',
        ),
        'imlek' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Selamat Tahun Baru Imlek dari Eclectic Consulting',
        ),
        'hut_ri' => 
        array (
          'title' => 'Menyiapkan ruang kerja EcoSystem Anda…',
          'subtitle' => 'Dirgahayu Republik Indonesia',
        ),
      ),
      'admin' => 
      array (
        'subtitle' => 'Atur tema perayaan untuk semua pengguna dan halaman login',
        'info' => 'Pengaturan ini menjadi tema default untuk sidebar, navbar semua pengguna, dan halaman login. Pengguna yang sudah memilih sendiri di Settings (Off atau tema tertentu) tidak berubah. Hanya pengguna dengan pilihan "Default" yang mengikuti pengaturan ini.',
        'active_today' => 'Aktif hari ini',
        'active_none' => 'Tanpa tema (Off)',
        'login_title' => 'Login and auth pages',
        'login_hint' => 'Tema untuk halaman login, lupa password, verifikasi dua langkah, dan ganti password, serta untuk loading screen yang tampil tepat setelah login. Hanya administrator yang dapat mengubahnya. Dashboard, sidebar, dan navbar tidak terpengaruh.',
        'login_theme' => 'Tema',
        'login_inherit' => 'Ikuti tema global',
        'login_off' => 'Off',
        'login_scope' => 'Berlaku untuk',
        'login_scope_both' => 'Halaman login dan loading screen',
        'login_scope_page' => 'Halaman login saja',
        'login_scope_loading' => 'Loading screen setelah login saja',
        'login_scope_note' => 'Bila dipilih tema selain "Ikuti tema global", tema ini menggantikan pilihan loading screen tiap pengguna untuk bagian yang dipilih di sini.',
        'theme_title' => 'Tema',
        'theme_hint' => 'Berlaku di sidebar, navbar, dan halaman login.',
        'theme_manual' => 'Tema yang dipakai pada mode Manual.',
        'mode_title' => 'Pengaktifan',
        'mode_hint' => 'Pilih apakah tema dinyalakan oleh Anda atau mengikuti kalender.',
        'music_title' => 'Musik latar',
        'music_hint' => 'Diputar di setiap halaman setelah login. Hanya administrator yang memilih lagu; pengguna hanya bisa membisukan atau mengaktifkan suara untuk dirinya sendiri.',
        'music_none' => 'Tidak ada (tanpa musik latar)',
        'preview' => 'Pratinjau',
        'volume' => 'Volume',
        'volume_hint' => 'Berlaku untuk semua pengguna. Mereka tidak bisa mengubahnya, hanya membisukan atau mengaktifkan suara.',
        'upload_title' => 'Unggah lagu baru',
        'upload' => 'Unggah',
        'upload_hint' => 'WAV, MP3, OGG, atau AAC, maksimal 3 MB. Berkas memakai folder yang sama dengan suara di Control Center › Notif Sounds. Setelah mengunggah, pilih lagunya di atas lalu simpan.',
        'cancel' => 'Batal',
        'save' => 'Simpan pengaturan',
        'saved' => 'Tema musiman disimpan. Pengguna dengan pilihan "Default" langsung melihatnya; pengguna yang memilih sendiri tidak terpengaruh.',
        'uploaded' => '":file" berhasil diunggah. Pilih di atas lalu simpan agar aktif.',
        'upload_type' => 'Hanya berkas WAV, MP3, OGG, dan AAC yang diizinkan.',
        'upload_size' => 'Ukuran berkas tidak boleh melebihi 3 MB.',
      ),
      'mode' => 
      array (
        'manual' => 'Manual',
        'manual_hint' => 'Tema yang Anda pilih di atas selalu aktif.',
        'auto' => 'Jadwal otomatis',
        'auto_hint' => 'Tema mengikuti periode di bawah. Di luar semua periode, tema Off.',
      ),
      'schedule' => 
      array (
        'title' => 'Jadwal',
        'hint' => 'Dipakai pada mode Jadwal otomatis. Bila dua periode tumpang tindih, yang tertulis lebih dulu berlaku.',
        'theme' => 'Tema',
        'pick_theme' => 'Pilih tema',
        'start' => 'Tanggal mulai',
        'end' => 'Tanggal selesai',
        'add' => 'Tambah periode',
        'remove' => 'Hapus',
        'empty' => 'Belum ada periode. Tambahkan satu untuk memakai jadwal otomatis.',
        'suggested' => 'Saran tanggal untuk :year sudah diisi. Sesuaikan bila perlu.',
        'moves' => 'Tanggal perayaan ini berpindah setiap tahun. Isi tanggal yang tepat.',
      ),
      'toasts' => 
      array (
        'choose_file' => 'Pilih berkas terlebih dahulu.',
        'upload_failed' => 'Unggahan gagal.',
        'upload_network' => 'Terjadi gangguan jaringan saat mengunggah.',
        'schedule_incomplete' => 'Setiap periode memerlukan tema, tanggal mulai, dan tanggal selesai.',
        'schedule_order' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
      ),
    ),
];
