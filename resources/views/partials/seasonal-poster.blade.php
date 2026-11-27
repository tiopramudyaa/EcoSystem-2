{{--
    Gambar diam (poster) loading screen satu tema, dipakai sebagai thumbnail kartu tema.
    Thumbnail statis 160x90 di public/images/seasonal/posters/thumb/<key>.webp (poster penuh 640x360 hanya dipakai pratinjau). Tidak ada query DB
    dan tidak ada JavaScript; dimuat lazy. Bila berkas tidak ada, tidak mencetak apa pun
    sehingga ikon vektor di belakangnya tetap tampil. Induknya harus position:relative.

    Params:
      $theme  string  key tema katalog.
--}}
@php
    $spKey = $theme ?? '';
    $spFile = 'images/seasonal/posters/thumb/' . $spKey . '.webp';
@endphp
@if($spKey !== '' && preg_match('/^[a-z_]+$/', $spKey) && is_file(public_path($spFile)))
    <img src="{{ asset($spFile) }}?v={{ filemtime(public_path($spFile)) }}" width="160" height="90" loading="lazy" decoding="async"
         alt="{{ __('ui.seasonal.preview.poster_alt', ['theme' => __(\App\Support\SeasonalThemes::get($spKey)['label_key'] ?? 'ui.seasonal.themes.none')]) }}"
         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover" onerror="this.remove()">
@endif
