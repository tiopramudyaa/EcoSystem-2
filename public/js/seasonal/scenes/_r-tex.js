// Texture prosedural untuk masjid Ramadan (batu, pola geometris bintang delapan, kaca berkisi, kubah timah, pelepah palem).
// Tidak ada yang berjalan saat import. Semua fungsi mengembalikan CanvasTexture; pemanggil wajib dispose().
import { canvasTex } from '../common.js';

const rep = (THREE, t, aniso = 4) => { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.anisotropy = 1; return t; };

/** Susunan blok batu kapur: baris berselang-seling, nat tipis, bintik. Satu ubin = 4 x 4 blok. */
export function stoneTexture(THREE, rnd) {
  return rep(THREE, canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#c6c8d0'; g.fillRect(0, 0, w, h);
    const rows = 8, bh = h / rows;
    for (let r = 0; r < rows; r++) {
      const bw = w / 4, off = (r % 2) * bw / 2;
      for (let c = -1; c < 5; c++) {
        const x = c * bw + off, v = 0.9 + rnd() * 0.16;
        g.fillStyle = `rgba(${Math.round(208 * v)},${Math.round(208 * v)},${Math.round(212 * v)},1)`;
        g.fillRect(x + 1.5, r * bh + 1.5, bw - 3, bh - 3);
        const gr = g.createLinearGradient(0, r * bh, 0, (r + 1) * bh); gr.addColorStop(0, 'rgba(255,255,255,0.10)'); gr.addColorStop(1, 'rgba(60,50,40,0.10)');
        g.fillStyle = gr; g.fillRect(x + 1.5, r * bh + 1.5, bw - 3, bh - 3);
      }
    }
    g.strokeStyle = 'rgba(70,62,50,0.55)'; g.lineWidth = 2.2;
    for (let r = 0; r <= rows; r++) { g.beginPath(); g.moveTo(0, r * bh); g.lineTo(w, r * bh); g.stroke(); }
    for (let r = 0; r < rows; r++) { const bw = w / 4, off = (r % 2) * bw / 2; for (let c = -1; c < 5; c++) { g.beginPath(); g.moveTo(c * bw + off, r * bh); g.lineTo(c * bw + off, (r + 1) * bh); g.stroke(); } }
    for (let i = 0; i < 1800; i++) { g.fillStyle = rnd() < 0.5 ? 'rgba(255,250,235,0.06)' : 'rgba(40,34,28,0.07)'; g.fillRect(rnd() * w, rnd() * h, 1 + rnd() * 3, 1 + rnd() * 3); }
  }));
}

/** Pola geometris bintang delapan (dua persegi bersilang) emas di atas biru-hijau tua; ubin 2 x 2 bintang. */
export function starPatternTexture(THREE) {
  return rep(THREE, canvasTex(THREE, 256, 256, (g, w, h) => {
    g.fillStyle = '#0f2e4c'; g.fillRect(0, 0, w, h);
    const cell = w / 2, gold = '#e2bb55';
    for (let iy = 0; iy < 2; iy++) for (let ix = 0; ix < 2; ix++) {
      const cx = ix * cell + cell / 2, cy = iy * cell + cell / 2;
      g.save(); g.translate(cx, cy);
      const s = cell * 0.36;
      g.fillStyle = 'rgba(30,86,110,0.55)';
      for (const rot of [0, Math.PI / 4]) { g.save(); g.rotate(rot); g.fillRect(-s, -s, s * 2, s * 2); g.restore(); }
      g.strokeStyle = gold; g.lineWidth = 3.2;
      for (const rot of [0, Math.PI / 4]) { g.save(); g.rotate(rot); g.strokeRect(-s, -s, s * 2, s * 2); g.restore(); }
      g.lineWidth = 1.8; g.beginPath();
      for (let k = 0; k < 8; k++) { const a = k * Math.PI / 4 + Math.PI / 8; g.lineTo(Math.cos(a) * s * 0.58, Math.sin(a) * s * 0.58); }
      g.closePath(); g.stroke();
      g.fillStyle = gold; g.beginPath(); g.arc(0, 0, 4, 0, 6.283); g.fill();
      g.restore();
      // belah ketupat di sudut sel
      g.fillStyle = gold;
      for (const [ox, oy] of [[0, 0], [cell, 0], [0, cell], [cell, cell]]) { const x = ix * cell + ox, y = iy * cell + oy; g.beginPath(); g.moveTo(x, y - 9); g.lineTo(x + 6, y); g.lineTo(x, y + 9); g.lineTo(x - 6, y); g.closePath(); g.fill(); }
    }
  }));
}

/** Kisi jendela (arabesque sederhana) berlatar kuning-oranye hangat; UV 0..1 mengikuti kotak jendela. */
export function windowGlassTexture(THREE) {
  return canvasTex(THREE, 128, 256, (g, w, h) => {
    const gr = g.createLinearGradient(0, h, 0, 0); gr.addColorStop(0, '#ff9a3a'); gr.addColorStop(0.55, '#ffcf7a'); gr.addColorStop(1, '#ffe7a8');
    g.fillStyle = gr; g.fillRect(0, 0, w, h);
    g.strokeStyle = '#4a2a0a'; g.lineWidth = 3;
    for (let k = -h; k < w + h; k += 26) { g.beginPath(); g.moveTo(k, 0); g.lineTo(k + h * 0.6, h); g.stroke(); g.beginPath(); g.moveTo(k + h * 0.6, 0); g.lineTo(k, h); g.stroke(); }
    g.lineWidth = 5; g.beginPath(); g.moveTo(w / 2, 0); g.lineTo(w / 2, h); g.stroke(); g.beginPath(); g.moveTo(0, h * 0.62); g.lineTo(w, h * 0.62); g.stroke();
    g.strokeStyle = '#7a4a14'; g.lineWidth = 6; g.strokeRect(1, 1, w - 2, h - 2);
    const vg = g.createRadialGradient(w / 2, h * 0.55, 6, w / 2, h * 0.55, h * 0.7); vg.addColorStop(0, 'rgba(255,255,255,0.28)'); vg.addColorStop(1, 'rgba(120,40,0,0.3)');
    g.fillStyle = vg; g.fillRect(0, 0, w, h);
  });
}

/** Kubah berlapis timah: iga vertikal (24 per putaran), lapisan melintang, pita emas di dasar. u = putaran, v = dasar ke puncak. */
export function domeTexture(THREE, rnd) {
  return rep(THREE, canvasTex(THREE, 512, 256, (g, w, h) => {
    g.fillStyle = '#6f84a6'; g.fillRect(0, 0, w, h);
    const ribs = 24;
    for (let i = 0; i < ribs; i++) {
      const x = (i / ribs) * w, v = 0.9 + rnd() * 0.2;
      g.fillStyle = `rgba(${Math.round(120 * v)},${Math.round(142 * v)},${Math.round(176 * v)},0.55)`; g.fillRect(x + 1, 0, w / ribs - 2, h);
      g.fillStyle = 'rgba(235,205,120,0.85)'; g.fillRect(x - 1.2, 0, 2.4, h);
    }
    g.strokeStyle = 'rgba(30,40,60,0.25)'; g.lineWidth = 1.4;
    for (let y = 0; y < h; y += 14) { g.beginPath(); g.moveTo(0, y); g.lineTo(w, y); g.stroke(); }
    g.fillStyle = 'rgba(226,187,85,0.9)'; g.fillRect(0, h - 10, w, 10); // dasar (v kecil ada di bawah kanvas = y besar)
    g.fillStyle = 'rgba(40,30,10,0.35)'; g.fillRect(0, h - 12, w, 2);
  }));
}

/** Pelepah palem: tulang daun dan anak daun runcing (segitiga), alfa untuk alphaTest. Kolom kiri 0..0.15 = padat (batang). */
export function palmTexture(THREE, rnd) {
  return canvasTex(THREE, 512, 256, (g, w, h) => {
    g.fillStyle = '#ffffff'; g.fillRect(0, 0, 80, h);
    g.translate(120, h / 2);
    g.fillStyle = '#ffffff';
    g.fillRect(0, -3, 380, 6);
    const n = 30;
    for (let i = 0; i < n; i++) {
      const x = 6 + i * (370 / n), L = (96 * Math.sin(Math.PI * Math.min(1, (i + 3) / (n + 2))) + 10) * (0.85 + rnd() * 0.2), bw = 13;
      for (const s of [-1, 1]) {
        g.beginPath(); g.moveTo(x - bw / 2, 0); g.quadraticCurveTo(x + 8, s * L * 0.5, x + 30 + rnd() * 8, s * L); g.lineTo(x + bw / 2 + 3, 0); g.closePath(); g.fill();
      }
    }
  }, true);
}
