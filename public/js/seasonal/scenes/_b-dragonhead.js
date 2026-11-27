// Kepala naga Tiongkok (lóng), mengikuti referensi relief dan lukisan: moncong pendek dan dalam dengan hidung besar menonjol berlubang lebar,
// bibir atas tebal melengkung di sudut mulut, alis sudut berat yang menyapu ke pangkal tanduk, mata kecil dan dalam, rahang bawah pendek berjanggut,
// tanduk tipis menyapu ke belakang, jambul api di ubun-ubun, sirip telinga berbentuk daun.
// Koordinat lokal: +x depan, +y atas, +z sisi yang menghadap kamera. Tidak ada yang berjalan saat import.
import { merger, tube, loft } from './_b-geom.js';

export const JAW_PIVOT = [0.0, -0.42, 0];
export const EYE = [0.9, 0.5, 0.56, 0.17];

const UPPER = [ // x, y bawah, y atas, setengah lebar, eksponen
  [-1.2, -0.45, 0.6, 0.5, 2.2], [-0.8, -0.55, 0.9, 0.66, 2.2], [-0.3, -0.5, 1.05, 0.74, 2.3], [0.2, -0.44, 1.08, 0.74, 2.3], [0.7, -0.36, 0.98, 0.7, 2.4],
  [1.1, -0.3, 0.86, 0.64, 2.5], [1.6, -0.26, 0.9, 0.62, 2.6], [2.1, -0.2, 1.02, 0.66, 2.7], [2.6, -0.12, 1.2, 0.7, 2.6], [2.95, -0.02, 1.22, 0.64, 2.4],
  [3.25, 0.12, 1.2, 0.52, 2.2], [3.5, 0.3, 1.15, 0.38, 2.1], [3.65, 0.5, 1.0, 0.22, 2.0], [3.72, 0.64, 0.86, 0.05, 2.0],
];
const LOWER = [
  [-0.2, -0.98, -0.48, 0.55, 2.2], [0.4, -0.94, -0.46, 0.5, 2.2], [1.0, -0.84, -0.42, 0.44, 2.3], [1.6, -0.72, -0.36, 0.38, 2.4], [2.2, -0.58, -0.28, 0.32, 2.5],
  [2.7, -0.44, -0.2, 0.24, 2.3], [3.0, -0.34, -0.16, 0.12, 2.0], [3.08, -0.3, -0.2, 0.02, 2.0],
];
const lerpRing = (T, x, k) => {
  for (let i = 0; i < T.length - 1; i++) if (x <= T[i + 1][0]) { const u = (x - T[i][0]) / (T[i + 1][0] - T[i][0]); return T[i][k] + (T[i + 1][k] - T[i][k]) * Math.max(0, u); }
  return T[T.length - 1][k];
};

export function sculptHead(THREE, low) {
  const K = (h) => new THREE.Color(h);
  const iv = K('#f6eed6'), gold = K('#f0b73c'), dark = K('#2a0407');
  const skin = merger(THREE), bone = merger(THREE), jawSkin = merger(THREE), jawBone = merger(THREE), eyes = merger(THREE);
  const rad = low ? 10 : 18, ss = (a, b, x) => { const t = Math.min(1, Math.max(0, (x - a) / (b - a))); return t * t * (3 - 2 * t); };
  const pal = K('#4a0a10'), mouthDark = K('#3a060a');
  const rings = (T) => T.map((r) => ({ x: r[0], y0: r[1], y1: r[2], w: r[3], n: r[4] }));
  skin.addM(loft(THREE, rings(UPPER), rad, (i, phi, c) => { const s = Math.sin(phi); c.setRGB(1, 1, 1).lerp(pal, ss(-0.4, -0.85, s)); }, 0.9, 1), null, new THREE.Matrix4());
  const jm = new THREE.Matrix4().makeTranslation(-JAW_PIVOT[0], -JAW_PIVOT[1], -JAW_PIVOT[2]);
  jawSkin.addM(loft(THREE, rings(LOWER), rad - 2, (i, phi, c) => { const s = Math.sin(phi); c.setRGB(1, 1, 1).lerp(mouthDark, ss(0.25, 0.75, s)); }, 0.9, 1), null, jm);

  const tb = (m, pts, r, o, color) => m.addM(tube(THREE, pts, r, Object.assign({ radial: low ? 5 : 7 }, o)), color || null, new THREE.Matrix4());
  const jt = (pts) => pts.map((p) => [p[0] - JAW_PIVOT[0], p[1] - JAW_PIVOT[1], p[2]]);
  [-1, 1].forEach((s) => {
    const z = (a) => a.map((p) => [p[0], p[1], p[2] * s]);
    // alis berat bersudut, menyapu ke belakang; garis emas di atasnya
    tb(skin, z([[1.7, 0.82, 0.4], [1.25, 1.0, 0.54], [0.8, 1.14, 0.66], [0.25, 1.22, 0.72], [-0.3, 1.16, 0.66]]), [0.07, 0.15, 0.19, 0.16, 0.09], { seg: 12, uvk: 0.9 });
    tb(bone, z([[1.65, 0.92, 0.4], [1.2, 1.08, 0.54], [0.75, 1.22, 0.66], [0.2, 1.3, 0.72], [-0.3, 1.22, 0.66]]), [0.025, 0.035, 0.04, 0.035, 0.02], { seg: 12, radial: 5 }, gold);
    // bibir atas tebal, melengkung naik di sudut mulut; garis emas di bawahnya
    tb(skin, z([[3.15, 0.04, 0.42], [2.5, -0.1, 0.62], [1.6, -0.2, 0.66], [0.7, -0.34, 0.74], [0.1, -0.38, 0.77], [-0.15, -0.15, 0.78]]), [0.07, 0.1, 0.11, 0.11, 0.1, 0.06], { seg: 16, radial: 7, uvk: 0.9 });
    tb(bone, z([[3.1, -0.04, 0.5], [2.5, -0.2, 0.7], [1.6, -0.3, 0.74], [0.7, -0.44, 0.82], [0.1, -0.5, 0.84], [-0.2, -0.22, 0.84]]), [0.03, 0.04, 0.04, 0.04, 0.04, 0.025], { seg: 16, radial: 5 }, gold);
    tb(skin, z([[3.3, 0.3, 0.3], [3.55, 0.45, 0.28], [3.7, 0.7, 0.2]]), [0.1, 0.08, 0.03], { seg: 8, radial: 6, uvk: 0.9 });
    // cuping hidung besar dan lubang hidung
    skin.add(new THREE.SphereGeometry(0.18, low ? 8 : 12, low ? 6 : 8), null, 2.9, 0.74, s * 0.5, 0, 0, 0.25, 1.4, 0.8, 0.85);
    bone.add(new THREE.SphereGeometry(0.075, 8, 6), K('#120304'), 3.08, 0.74, s * 0.62, 0, 0, 0, 1.2, 1.1, 0.6);
    // kelopak atas miring, mata kecil di dalam rongga
    skin.add(new THREE.SphereGeometry(EYE[3] * 1.3, low ? 8 : 12, 8, 0, Math.PI * 2, 0, 1.2), null, EYE[0], EYE[1], s * (EYE[2] - 0.03), 0, 0, -0.65, 1.05, 1, 1);
    eyes.add(new THREE.SphereGeometry(0.17, 10, 8), dark, EYE[0] - 0.03, EYE[1] - 0.01, s * (EYE[2] - 0.14), 0, 0, 0, 1, 0.88, 0.7);
    eyes.add(new THREE.SphereGeometry(EYE[3], 14, 10), K('#ffb61e').multiplyScalar(1.5), EYE[0], EYE[1], s * (EYE[2] + 0.02), 0, 0, 0, 1, 1, 0.92);
    eyes.add(new THREE.SphereGeometry(0.085, 8, 8), K('#0a0102'), EYE[0] + 0.08, EYE[1], s * (EYE[2] + 0.17), 0, 0, 0, 0.32, 1.45, 0.3);
    eyes.add(new THREE.SphereGeometry(0.03, 6, 6), K('#fffbe8').multiplyScalar(1.6), EYE[0] - 0.02, EYE[1] + 0.08, s * (EYE[2] + 0.19));
    // sirip pipi (duri emas) dan sirip telinga berbentuk daun
    for (let k = 0; k < 3; k++) {
      const L = 1.6 - k * 0.35, y0 = -0.05 - k * 0.2;
      tb(bone, z([[-0.45, y0, 0.66], [-0.45 - L * 0.5, y0 - 0.05, 0.78 + k * 0.05], [-0.45 - L, y0 - 0.3 - k * 0.1, 0.84 + k * 0.05]]), [0.1, 0.065, 0.012], { seg: 8, radial: 5, c0: '#c8801c', c1: '#ffd46a' });
    }
    tb(bone, z([[-0.5, 0.75, 0.75], [-0.95, 1.0, 0.9], [-1.4, 1.1, 1.05]]), [0.04, 0.13, 0.0], { seg: 8, radial: 6, flat: 0.35, c0: '#d8401c', c1: '#ffb040' });
    // tanduk rusa tipis, menyapu ke belakang dan sedikit ke atas, dua cabang
    const A = z([[-0.15, 0.9, 0.4], [-0.75, 1.45, 0.5], [-1.45, 1.95, 0.62], [-2.25, 2.25, 0.78], [-3.0, 2.3, 0.9], [-3.65, 2.1, 0.95]]).map((p) => p.map((v) => v * 0.88));
    tb(bone, A, [0.11, 0.09, 0.072, 0.052, 0.034, 0.01], { c0: '#5a3010', c1: '#f4dfae', seg: 20, radial: low ? 6 : 8 });
    const br = (root, pts, r) => tb(bone, [root].concat(pts.map((p) => [p[0] * 0.88, p[1] * 0.88, p[2] * s * 0.88])), r, { seg: 8, radial: 5, c0: '#a46a28', c1: '#f4dfae' });
    br(A[1], [[-0.95, 2.1, 0.58], [-0.9, 2.7, 0.6], [-0.65, 3.15, 0.62]], [0.07, 0.055, 0.035, 0.01]);
    br(A[2], [[-1.75, 2.6, 0.72], [-1.6, 3.15, 0.76], [-1.3, 3.5, 0.8]], [0.065, 0.05, 0.03, 0.01]);
    br(A[3], [[-2.8, 2.7, 0.9], [-3.1, 3.1, 0.95]], [0.055, 0.035, 0.01]);
    // taring atas dan deretan gigi
    bone.add(new THREE.ConeGeometry(0.1, 0.75, 6), iv, 2.2, -0.5, s * 0.34, Math.PI, 0, 0);
    bone.add(new THREE.ConeGeometry(0.06, 0.34, 6), iv, 1.2, -0.46, s * 0.5, Math.PI, 0, 0);
    for (let k = 0; k < 6; k++) {
      const x = 0.7 + k * 0.38, w = lerpRing(UPPER, x, 3), y0 = lerpRing(UPPER, x, 1);
      bone.add(new THREE.ConeGeometry(0.04, 0.17, 5), iv, x, y0 - 0.02, s * w * 0.6, Math.PI, 0, 0);
    }
    jawBone.addM(new THREE.ConeGeometry(0.07, 0.4, 6), iv, new THREE.Matrix4().makeTranslation(2.3 - JAW_PIVOT[0], -0.22 - JAW_PIVOT[1] + 0.1, s * 0.22));
    for (let k = 0; k < 4; k++) jawBone.addM(new THREE.ConeGeometry(0.035, 0.14, 5), iv, new THREE.Matrix4().makeTranslation(1.0 + k * 0.42 - JAW_PIVOT[0], -0.4 - JAW_PIVOT[1] + 0.07, s * 0.34));
    tb(jawBone, jt(z([[3.0, -0.2, 0.06], [2.5, -0.26, 0.24], [1.6, -0.36, 0.42], [0.6, -0.44, 0.52], [0.0, -0.48, 0.54]])), [0.04, 0.045, 0.045, 0.045, 0.04], { seg: 12, radial: 5 }, gold);
  });
  // dahi berbenjol dan garis puncak emas berduri
  skin.add(new THREE.SphereGeometry(0.38, low ? 8 : 12, 8), null, 0.45, 1.06, 0, 0, 0, 0, 1.0, 0.62, 0.95);
  tb(bone, [[3.1, 1.18, 0], [2.6, 1.24, 0], [2.1, 1.05, 0], [1.5, 0.92, 0], [1.0, 0.9, 0], [0.4, 1.12, 0], [-0.4, 1.1, 0], [-1.2, 0.76, 0]], 0.04, { seg: 18, radial: 5 }, gold);
  for (let k = 0; k < 4; k++) { const x = 1.6 - k * 0.55, y = 0.9 + 0.1 * k; bone.add(new THREE.ConeGeometry(0.07, 0.32, 5), gold, x, y + 0.12, 0, 0, 0, 0.55); }
  // jambul api di ubun-ubun menyapu ke belakang
  for (let k = 0; k < 7; k++) {
    const sd = (k - 3) * 0.16, x0 = -0.3 - Math.abs(k - 3) * 0.1, L = 1.5 - Math.abs(k - 3) * 0.18;
    tb(bone, [[x0, 1.0, sd], [x0 - L * 0.5, 1.25 - Math.abs(sd) * 0.3, sd * 1.5], [x0 - L, 1.15 - Math.abs(sd) * 0.6, sd * 2.2]], [0.1, 0.07, 0.0], { seg: 8, radial: 5, c0: '#ff7a1c', c1: '#ffd25a' });
  }
  // lidah api bercabang
  tb(jawBone, jt([[0.5, -0.4, 0], [1.4, -0.34, 0], [2.2, -0.3, 0], [2.8, -0.2, 0.0], [3.3, -0.05, 0.05]]), [0.07, 0.07, 0.06, 0.05, 0.03], { seg: 14, radial: 6, flat: 2.0, c0: '#d8260f', c1: '#ff9a2a' });
  [-1, 1].forEach((s) => tb(jawBone, jt([[3.25, -0.06, 0], [3.5, 0, s * 0.1], [3.75, 0.05, s * 0.22]]), [0.045, 0.03, 0.01], { seg: 6, radial: 4, c0: '#ff7a1c', c1: '#ffd060' }));

  return { skin: skin.build(), bone: bone.build(), jawSkin: jawSkin.build(), jawBone: jawBone.build(), eyes: eyes.build() };
}
