// Tekstur sisik naga yang dibuat di kanvas: peta warna (sisik merah tua bertepi emas, pelat perut krem) dan peta tonjolan.
// Sumbu x tekstur = keliling (0 di punggung, 0.5 di perut), sumbu y = sepanjang tubuh. Tidak ada yang berjalan saat import.
import { canvasTex } from '../common.js';

/** cols = jumlah sisik di sekeliling. Mengembalikan { map, bump }. */
export function skinTextures(THREE, cols) {
  const S = 16, size = cols * S, rows = cols;
  const bandHalf = 0.12; // setengah lebar pita perut (pecahan keliling)
  const inBand = (xn) => Math.abs(xn - 0.5) < bandHalf;
  const mk = (isBump) => canvasTex(THREE, size, size, (g, w, h) => {
    g.fillStyle = isBump ? '#101010' : '#3a050b'; g.fillRect(0, 0, w, h);
    for (let r = -1; r <= rows; r++) {
      for (let c = -1; c <= cols; c++) {
        const cx = (c + (r & 1) * 0.5) * S + S / 2, cy = r * S + S / 2, xn = ((cx % w) + w) % w / w;
        if (inBand(xn)) continue;
        const fl = Math.pow(Math.sin(Math.PI * 2 * xn), 2); // sisi badan lebih terang dan oranye
        const rad = 11.2;
        const gr = g.createRadialGradient(cx - 1.5, cy - 2.5, 1, cx, cy, rad);
        if (isBump) { gr.addColorStop(0, '#ffffff'); gr.addColorStop(0.6, '#a0a0a0'); gr.addColorStop(1, '#303030'); } else {
          const hi = `rgb(${Math.round(226 + 14 * fl)},${Math.round(52 + 36 * fl)},${Math.round(50 - 10 * fl)})`;
          const mid = `rgb(${Math.round(176 + 24 * fl)},${Math.round(20 + 22 * fl)},${Math.round(32 - 8 * fl)})`;
          gr.addColorStop(0, hi); gr.addColorStop(0.55, mid); gr.addColorStop(1, '#5a0810');
        }
        g.fillStyle = gr; g.beginPath(); g.arc(cx, cy, rad, 0, Math.PI * 2); g.fill();
        if (!isBump) { // tepi bebas (ke arah ekor = atas kanvas) berlapis emas tipis
          g.strokeStyle = 'rgba(255,196,84,0.8)'; g.lineWidth = 1.5; g.beginPath(); g.arc(cx, cy, rad - 0.8, Math.PI * 1.12, Math.PI * 1.88); g.stroke();
          g.strokeStyle = 'rgba(40,2,6,0.55)'; g.lineWidth = 1; g.beginPath(); g.arc(cx, cy, rad, 0, Math.PI * 2); g.stroke();
        }
      }
    }
    // pelat perut: pita melintang krem
    const x0 = (0.5 - bandHalf) * w, bw = bandHalf * 2 * w;
    for (let r = -1; r <= rows; r++) {
      const y = r * S;
      const gr = g.createLinearGradient(0, y, 0, y + S);
      if (isBump) { gr.addColorStop(0, '#d8d8d8'); gr.addColorStop(0.85, '#808080'); gr.addColorStop(1, '#101010'); } else { gr.addColorStop(0, '#f6e1a8'); gr.addColorStop(0.7, '#dcb468'); gr.addColorStop(1, '#8a5a22'); }
      g.fillStyle = gr; g.fillRect(x0, y, bw, S - 1);
      if (!isBump) { g.fillStyle = 'rgba(120,60,10,0.35)'; g.fillRect(x0, y + S - 2, bw, 1); g.fillStyle = 'rgba(255,255,255,0.35)'; g.fillRect(x0, y + 1, bw, 1); }
    }
    // tepi pita perut halus
    if (!isBump) { g.fillStyle = 'rgba(255,200,90,0.5)'; g.fillRect(x0 - 1, 0, 2, h); g.fillRect(x0 + bw - 1, 0, 2, h); }
  }, !isBump);
  const map = mk(false), bump = mk(true);
  for (const t of [map, bump]) { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.anisotropy = 4; }
  return { map, bump };
}
