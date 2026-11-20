// Pahat kepala naga untuk lunar.js, dioptimalkan untuk siluet PROFIL samping: moncong panjang melengkung ke atas dengan tonjolan hidung,
// alis berat, satu mata menonjol, rahang membuka tipis bertaring, tanduk rusa menyapu ke belakang dan atas, surai api, janggut.
// Koordinat lokal: +x depan, +y atas, +z sisi yang menghadap kamera. Tidak ada yang berjalan saat import.
import { merger } from './_b-geom.js';

export const EYE = [0.8, 0.44, 0.58, 0.2]; // x, y, z, radius mata
export const JAW_PIVOT = [0.1, -0.3, 0];

export function sculptHead(THREE) {
  const K = (h) => new THREE.Color(h);
  const red = K('#c01a22'), dark = K('#5a070d'), gold = K('#f4b83c'), iv = K('#f6eed6'), bone = K('#ecc66a'), flame = K('#ff7a1a'), blk = K('#14070a'), brown = K('#8a1a14');
  const hm = merger(THREE), jm = merger(THREE);
  const sph = (m, r, c, x, y, z, sx = 1, sy = 1, sz = 1) => m.add(new THREE.SphereGeometry(r, 14, 10), c, x, y, z, 0, 0, 0, sx, sy, sz);
  const cone = (m, r, h, c, x, y, z, rx, ry, rz, seg = 6) => m.add(new THREE.ConeGeometry(r, h, seg), c, x, y, z, rx, ry, rz);

  // tengkorak dan dahi
  sph(hm, 1, red, -0.15, 0.2, 0, 1.2, 0.85, 0.9);
  sph(hm, 0.7, red, 0.55, 0.38, 0, 1.0, 0.7, 0.85);
  sph(hm, 0.55, brown, -0.9, 0.05, 0, 1, 0.8, 0.8);
  // moncong panjang dengan ujung melengkung ke atas
  hm.limb([0.4, 0.25, 0], [2.15, 0.12, 0], 0.62, 0.4, red, 12);
  hm.limb([2.1, 0.12, 0], [3.0, 0.42, 0], 0.4, 0.3, red, 12);
  sph(hm, 0.3, red, 2.6, 0.62, 0, 1.15, 0.9, 1.1); // tonjolan hidung
  hm.limb([0.45, -0.2, 0], [2.9, -0.02, 0], 0.4, 0.26, dark, 10); // bibir atas
  hm.limb([0.2, 0.84, 0], [2.65, 0.82, 0], 0.07, 0.05, gold); // garis emas di puncak moncong (rim emas)
  hm.limb([0.7, 0.2, 0.5], [2.95, 0.3, 0.3], 0.05, 0.04, gold);
  sph(hm, 0.13, blk, 2.88, 0.36, 0.27, 1, 0.7, 0.8); cone(hm, 0.17, 0.45, gold, 2.8, 0.5, 0.36, 0, 0, -1.1);
  [-1, 1].forEach((s) => {
    // alis berat dan rongga mata
    hm.limb([-0.1, 0.8, s * 0.42], [1.35, 0.66, s * 0.54], 0.27, 0.1, dark, 8);
    hm.limb([0.0, 0.98, s * 0.4], [1.3, 0.8, s * 0.52], 0.06, 0.04, gold, 6);
    sph(hm, 0.32, K('#2a0508'), 0.78, 0.4, s * 0.56, 1, 0.8, 0.7);
    hm.add(new THREE.SphereGeometry(0.12, 8, 6), blk, EYE[0] + 0.05, EYE[1], s * (EYE[2] + 0.17), 0, 0, 0, 0.12, 1.3, 0.12);
    // taring atas dan deretan gigi
    cone(hm, 0.14, 0.85, iv, 2.45, -0.38, s * 0.3, Math.PI, 0, 0); cone(hm, 0.1, 0.6, iv, 1.5, -0.42, s * 0.5, Math.PI, 0, 0);
    for (let k = 0; k < 5; k++) cone(hm, 0.045, 0.24, iv, 0.9 + k * 0.32, -0.42, s * 0.58, Math.PI, 0, 0, 5);
    // sirip pipi emas menyapu ke belakang
    for (let k = 0; k < 4; k++) cone(hm, 0.15 - k * 0.025, 1.2 - k * 0.18, gold, -0.4 - k * 0.28, -0.1 - k * 0.2, s * (0.8 + k * 0.04), 0, s * 0.4, Math.PI / 2 + 0.3);
    // tanduk rusa: menyapu ke belakang dan atas, bercabang anggun (z kecil agar siluet bersih)
    const a = [[-0.15, 0.95, 0.32], [-0.85, 1.6, 0.4], [-1.75, 2.1, 0.46], [-2.8, 2.3, 0.5], [-3.9, 2.0, 0.5], [-4.7, 1.3, 0.5]].map((p) => [p[0], p[1], p[2] * s]);
    const rr = [0.34, 0.3, 0.24, 0.17, 0.1, 0.03];
    for (let k = 0; k < 5; k++) hm.limb(a[k], a[k + 1], rr[k], rr[k + 1], bone, 8);
    [[1, [-0.45, 2.45]], [2, [-1.3, 3.1]], [2, [-0.9, 2.6]], [3, [-2.4, 3.2]], [3, [-1.9, 2.8]], [4, [-3.7, 2.9]]].forEach(([i, p]) => hm.limb(a[i], [p[0], p[1], a[i][2]], 0.17, 0.03, bone, 6));
  });
  // surai api dari tengkuk
  for (let k = 0; k < 10; k++) cone(hm, 0.26 - k * 0.015, 1.8 - k * 0.07, k % 2 ? gold : flame, -0.5 - k * 0.3, 0.75 - k * 0.1, 0, 0, 0, Math.PI / 2 + 0.7);

  // rahang bawah (dipasang pada engsel animasi)
  jm.limb([0.1, -0.05, 0], [2.5, 0.05, 0], 0.48, 0.28, K('#e8ae3c'), 10);
  jm.limb([0.3, -0.35, 0], [2.3, -0.25, 0], 0.3, 0.18, K('#b8701c'), 8);
  jm.add(new THREE.BoxGeometry(1.4, 0.07, 0.34), K('#e0405a'), 1.2, 0.22, 0, 0, 0, 0.04);
  [-1, 1].forEach((s) => { cone(jm, 0.11, 0.7, iv, 2.25, 0.42, s * 0.24, 0, 0, 0); cone(jm, 0.07, 0.45, iv, 1.5, 0.34, s * 0.38, 0, 0, 0); for (let k = 0; k < 4; k++) cone(jm, 0.04, 0.2, iv, 0.9 + k * 0.3, 0.3, s * 0.42, 0, 0, 0, 5); });
  for (let k = 0; k < 3; k++) cone(jm, 0.1, 0.9 - k * 0.2, gold, 2.2 - k * 0.5, -0.55, 0, Math.PI, 0, 0.35); // janggut di dagu
  return { head: hm.build(), jaw: jm.build() };
}
