{{--
    Remap warna UI brand ke palet musiman. Aktif HANYA bila <html data-season="..."> ada,
    jadi tanpa tema musiman tampilan persis seperti sebelumnya. CSS murni, tanpa JS, tanpa query.

    Latar: halaman memakai utilitas Tailwind merah tetap (bg-red-700/800, hover:bg-red-900, dst) untuk
    tombol dan aksen brand. Variabel --primary-* sudah ditimpa oleh partials/seasonal-theme-vars,
    jadi di sini utilitas-utilitas itu diarahkan ke variabel tersebut (comma form "R, G, B").

    TIDAK diubah (warna semantik bahaya/status): red-50..red-600 (alert, badge, error, tombol hapus
    bg-red-600), teks error di luar tautan/tombol, dan elemen bertanda:
      - atribut data-danger (tombol destruktif eksplisit: tolak, hapus, konfirmasi dialog "danger"),
      - onclick yang memuat "elete", "eject", "evoke" (confirmDelete..., rejectX, revokeX),
      - kelas .uppercase pada badge latar merah (badge "DELETED"),
      - elemen di dalam latar bg-red-50 / bg-red-100 (kotak peringatan).

    Param: $dark (bool) tema gelap pengguna: teks tautan memakai versi terang palet supaya terbaca.
--}}
@php
    $stRemapDark = (bool) ($dark ?? false);
@endphp
<style>
    /* Warna teks tautan/tombol brand: palet apa adanya di mode terang, versi terang di mode gelap. */
    html[data-season] { --st-link: rgb(var(--primary-rgb)); }
@if($stRemapDark)
    html[data-season] { --st-link: #e5e7eb; }
    @supports (color: color-mix(in srgb, red, blue)) {
        html[data-season] { --st-link: color-mix(in srgb, rgb(var(--primary-rgb)) 40%, #fff); }
    }
@endif

    /* ---- Latar: tombol dan chip brand ---- */
    html[data-season] .bg-red-700:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],.uppercase),
    html[data-season] .bg-red-800:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],.uppercase) {
        background-color: rgb(var(--primary-rgb));
    }
    html[data-season] .bg-red-900:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],.uppercase) {
        background-color: rgb(var(--primary-dark-rgb));
    }
    /* Hover: lebih gelap. Dasar merah terang (red-500/600) = tombol bahaya, dibiarkan. */
    html[data-season] .hover\:bg-red-700:hover:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-5"],[class*="bg-red-6"]),
    html[data-season] .hover\:bg-red-800:hover:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-5"],[class*="bg-red-6"]),
    html[data-season] .hover\:bg-red-900:hover:not([data-danger],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-5"],[class*="bg-red-6"]) {
        background-color: rgb(var(--primary-dark-rgb));
    }

    /* ---- Gradien ---- */
    html[data-season] .from-red-800,
    html[data-season] .from-red-700 {
        --tw-gradient-from: rgb(var(--primary-dark-rgb));
        --tw-gradient-to: rgba(var(--primary-dark-rgb), 0);
    }
    html[data-season] .from-red-900 {
        --tw-gradient-from: rgb(var(--primary-dark-rgb));
        --tw-gradient-to: rgba(var(--primary-dark-rgb), 0);
    }
    html[data-season] .to-red-700,
    html[data-season] .to-red-800 {
        --tw-gradient-to: rgb(var(--primary-rgb));
    }
    html[data-season] .via-red-800 {
        --tw-gradient-stops: var(--tw-gradient-from), rgb(var(--primary-rgb)), var(--tw-gradient-to);
    }

    /* ---- Teks: hanya tautan, tombol, tab, dan label (teks statis merah = pesan error, dibiarkan) ---- */
    html[data-season] :is(a, button, summary, label, [role="tab"], [role="button"], .border-b-2).text-red-700:not([data-danger],[class*="bg-red-50"],[class*="bg-red-100"],[class*="text-red-4"],[class*="text-red-5"],[class*="text-red-6"],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-50"] *,[class*="bg-red-100"] *),
    html[data-season] :is(a, button, summary, label, [role="tab"], [role="button"], .border-b-2).text-red-800:not([data-danger],[class*="bg-red-50"],[class*="bg-red-100"],[class*="text-red-4"],[class*="text-red-5"],[class*="text-red-6"],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-50"] *,[class*="bg-red-100"] *),
    html[data-season] :is(a, button, summary, label, [role="tab"], [role="button"]).text-red-900:not([data-danger],[class*="bg-red-50"],[class*="bg-red-100"],[class*="text-red-4"],[class*="text-red-5"],[class*="text-red-6"],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-50"] *,[class*="bg-red-100"] *),
    html[data-season] :is(a, button, summary, label, [role="tab"], [role="button"]).hover\:text-red-700:hover:not([data-danger],[class*="text-red-4"],[class*="text-red-5"],[class*="text-red-6"],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-50"] *,[class*="bg-red-100"] *),
    html[data-season] :is(a, button, summary, label, [role="tab"], [role="button"]).hover\:text-red-800:hover:not([data-danger],[class*="text-red-4"],[class*="text-red-5"],[class*="text-red-6"],[onclick*="elete"],[onclick*="eject"],[onclick*="evoke"],[class*="bg-red-50"] *,[class*="bg-red-100"] *) {
        color: var(--st-link){{ $stRemapDark ? ' !important' : '' }};
    }

    /* ---- Border ---- */
    html[data-season] .border-red-700:not([data-danger]),
    html[data-season] .border-red-800:not([data-danger]),
    html[data-season] .hover\:border-red-700:hover:not([data-danger],[class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]),
    html[data-season] .hover\:border-red-800:hover:not([data-danger],[class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]),
    html[data-season] .focus\:border-red-700:focus:not([class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]),
    html[data-season] .focus\:border-red-800:focus:not([class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]) {
        border-color: rgb(var(--primary-rgb));
    }

    /* ---- Focus ring dan accent-color (checkbox, radio, range) ---- */
    html[data-season] .ring-red-800,
    html[data-season] .focus\:ring-red-700:focus:not([class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]),
    html[data-season] .focus\:ring-red-800:focus:not([class*="border-red-3"],[class*="border-red-4"],[class*="border-red-5"]) {
        --tw-ring-color: rgb(var(--primary-rgb));
    }
    html[data-season] .accent-red-600,
    html[data-season] .accent-red-700,
    html[data-season] .accent-red-800 {
        accent-color: rgb(var(--primary-rgb));
    }
</style>
