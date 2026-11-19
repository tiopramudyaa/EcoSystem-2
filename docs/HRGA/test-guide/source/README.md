# Sumber panduan pengujian

Panduan `Panduan-Pengujian-Objects-and-Funds` (DOCX dan PDF) dibuat dari satu sumber isi.

- `content.mjs`: seluruh isi (bab, skenario uji, tabel). Ubah di sini lalu bangun ulang kedua berkas.
- `diagrams.mjs`: tujuh diagram (SVG) yang dirender ke PNG.
- `render.mjs`, `build-pdf.mjs`, `build-docx.mjs`: perender.

Cara membangun ulang (Node 20+, Google Chrome terpasang):

```
npm install docx puppeteer-core
node render.mjs        # diagram -> out/*.png
node build-pdf.mjs     # -> out/*.pdf
node build-docx.mjs    # -> out/*.docx
```

Jalur Chrome pada `render.mjs` dan `build-pdf.mjs` perlu disesuaikan bila berbeda.
