// Kereta luncur Santa untuk adegan Natal: badan melengkung dengan gulungan emas, seluncur melengkung, karung hadiah,
// dan Santa (mantel, sabuk, janggut, topi) sebagai satu geometri gabungan berwarna vertex. Menghadap +x.
// Tidak ada yang berjalan saat import.
import { mergeParts } from '../common.js';
import { limb, blob, tube } from './_n-reindeer.js';

/** Mengembalikan { sleigh, harness, hands:[[x,y,z]...], front:[x,y,z] } (geometri milik pemanggil). */
export function buildSleigh(THREE, { red = '#a31f22', gold = '#d6a63c' } = {}) {
  const P = [];
  const M = (x, y, z, rx = 0, ry = 0, rz = 0) => new THREE.Matrix4().compose(new THREE.Vector3(x, y, z), new THREE.Quaternion().setFromEuler(new THREE.Euler(rx, ry, rz)), new THREE.Vector3(1, 1, 1));
  const dark = '#2b1a14', white = '#f3efe8', skin = '#e6b293', furRed = '#c42a2a';

  // badan: profil samping melengkung (haluan melengkung, buritan menjulang), diekstrusi lalu diberi bevel
  const sp = new THREE.Shape();
  sp.moveTo(-1.7, 0.1); sp.lineTo(1.2, 0.1);
  sp.bezierCurveTo(1.65, 0.15, 1.95, 0.7, 1.78, 1.28);
  sp.bezierCurveTo(1.58, 1.05, 1.36, 0.86, 1.1, 0.82);
  sp.lineTo(-1.2, 0.82);
  sp.bezierCurveTo(-1.6, 0.92, -1.92, 1.25, -1.86, 1.6);
  sp.bezierCurveTo(-2.12, 1.0, -2.02, 0.4, -1.7, 0.1);
  const body = new THREE.ExtrudeGeometry(sp, { depth: 1.0, bevelEnabled: true, bevelThickness: 0.09, bevelSize: 0.09, bevelSegments: 3, curveSegments: 16 });
  body.translate(0, 0, -0.5);
  P.push({ geo: body, color: red });
  // bagian dalam lebih gelap (jok) dan bantalan sandaran
  P.push({ geo: new THREE.BoxGeometry(2.7, 0.14, 0.92), m: M(-0.2, 0.34, 0), color: '#6d1417' });
  P.push(blob(THREE, -1.45, 0.8, 0, 0.35, 0.45, 0.5, '#7d181c'));
  // ban emas di tepi atas dan gulungan haluan
  [-1, 1].forEach((s) => {
    const z = s * 0.585;
    P.push(tube(THREE, [[-1.9, 1.62, z], [-1.62, 1.0, z], [-1.2, 0.86, z], [1.1, 0.86, z], [1.42, 0.92, z], [1.72, 1.2, z], [1.84, 1.34, z]], 0.035, gold, 36, 5));
    P.push(tube(THREE, [[1.2, 0.18, z], [1.7, 0.3, z], [1.95, 0.7, z], [1.86, 1.0, z], [1.64, 1.02, z], [1.62, 0.84, z]], 0.03, gold, 22, 5)); // gulungan emas di haluan
    P.push(tube(THREE, [[-1.7, 0.2, z], [-1.95, 0.5, z], [-2.0, 0.9, z], [-1.9, 1.3, z]], 0.026, gold, 14, 4));
  });
  // seluncur melengkung dengan ujung menggulung ke atas, disangga kaki
  [-0.62, 0.62].forEach((z) => {
    P.push(tube(THREE, [[-1.95, -0.55, z], [-1.4, -0.7, z], [0.8, -0.7, z], [1.75, -0.58, z], [2.15, -0.2, z], [2.12, 0.18, z], [1.92, 0.3, z]], 0.05, gold, 40, 6));
    [-1.2, -0.1, 1.1].forEach((x) => P.push(...limb(THREE, [x, -0.66, z], [x + 0.05, 0.06, z * 0.82], 0.035, 0.03, gold, 5, false)));
  });
  P.push(...limb(THREE, [-1.2, -0.66, -0.62], [-1.2, -0.66, 0.62], 0.025, 0.025, gold, 5, false));
  P.push(...limb(THREE, [1.1, -0.66, -0.62], [1.1, -0.66, 0.62], 0.025, 0.025, gold, 5, false));

  // karung hadiah dan kado
  P.push(blob(THREE, -1.2, 1.15, 0, 0.78, 0.72, 0.62, '#8c5d36', 18));
  P.push(blob(THREE, -1.02, 1.62, 0.08, 0.34, 0.3, 0.3, '#8c5d36', 12)); // leher karung terikat
  P.push(blob(THREE, -0.72, 1.18, 0.12, 0.3, 0.4, 0.3, '#9a6a3f', 12));
  const gift = (x, y, z, w, h, d, c, rb, ry) => {
    P.push({ geo: new THREE.BoxGeometry(w, h, d), m: M(x, y, z, 0, ry, 0), color: c });
    P.push({ geo: new THREE.BoxGeometry(w * 1.04, h * 1.04, d * 0.16), m: M(x, y, z, 0, ry, 0), color: rb });
    P.push({ geo: new THREE.BoxGeometry(w * 0.16, h * 1.04, d * 1.04), m: M(x, y, z, 0, ry, 0), color: rb });
  };
  gift(-1.52, 1.0, 0.5, 0.38, 0.32, 0.32, '#1f6b3f', '#e3c15a', 0.4);
  gift(-0.7, 0.62, -0.28, 0.34, 0.3, 0.34, '#2c4f9b', '#e8e2d0', -0.3);
  gift(-1.9, 1.02, -0.18, 0.3, 0.28, 0.3, '#c9322e', '#e3c15a', 0.9);
  P.push(...limb(THREE, [-1.1, 1.5, 0], [-1.1, 1.62, 0], 0.18, 0.12, gold, 8, false)); // tali pengikat

  // Santa duduk, menghadap +x
  const sx = 0.45, sy = 0.4;
  P.push(blob(THREE, sx, sy + 0.55, 0, 0.38, 0.5, 0.34, furRed, 16)); // badan mantel
  P.push(blob(THREE, sx + 0.08, sy + 0.9, 0, 0.3, 0.2, 0.3, furRed, 12)); // bahu
  P.push({ geo: new THREE.TorusGeometry(0.34, 0.05, 8, 24), m: M(sx, sy + 0.4, 0, Math.PI / 2, 0, 0), color: dark }); // sabuk
  P.push({ geo: new THREE.BoxGeometry(0.13, 0.13, 0.05), m: M(sx + 0.36, sy + 0.4, 0), color: gold });
  P.push({ geo: new THREE.BoxGeometry(0.1, 0.62, 0.05), m: M(sx + 0.33, sy + 0.62, 0), color: white }); // kancing/lis putih
  P.push({ geo: new THREE.TorusGeometry(0.4, 0.06, 8, 26), m: M(sx, sy + 0.04, 0, Math.PI / 2, 0, 0), color: white }); // ujung mantel berbulu
  P.push(...limb(THREE, [sx + 0.1, sy + 1.02, 0], [sx + 0.12, sy + 1.14, 0], 0.11, 0.1, skin, 6, false));
  P.push(blob(THREE, sx + 0.14, sy + 1.3, 0, 0.21, 0.23, 0.2, skin, 14)); // kepala
  P.push(blob(THREE, sx + 0.34, sy + 1.29, 0, 0.035, 0.035, 0.035, '#e49a8a', 6)); // hidung
  P.push(blob(THREE, sx + 0.3, sy + 1.36, 0.08, 0.018, 0.018, 0.018, '#171212', 5));
  P.push(blob(THREE, sx + 0.3, sy + 1.36, -0.08, 0.018, 0.018, 0.018, '#171212', 5));
  P.push(blob(THREE, sx + 0.26, sy + 1.04, 0, 0.19, 0.2, 0.2, white, 12)); // janggut berlapis
  P.push(blob(THREE, sx + 0.3, sy + 0.9, 0, 0.14, 0.18, 0.14, white, 10));
  P.push(blob(THREE, sx + 0.2, sy + 1.16, 0.11, 0.1, 0.1, 0.1, white, 8));
  P.push(blob(THREE, sx + 0.2, sy + 1.16, -0.11, 0.1, 0.1, 0.1, white, 8));
  P.push(blob(THREE, sx + 0.32, sy + 1.22, 0, 0.09, 0.05, 0.16, white, 8)); // kumis
  P.push({ geo: new THREE.TorusGeometry(0.2, 0.06, 8, 20), m: M(sx + 0.12, sy + 1.45, 0, Math.PI / 2 - 0.12, 0, 0.1), color: white }); // bulu topi
  const hat = new THREE.ConeGeometry(0.2, 0.55, 14, 1, true); hat.translate(0, 0.275, 0);
  P.push({ geo: hat, m: M(sx + 0.08, sy + 1.45, 0, 0, 0, 0.75), color: furRed });
  P.push(blob(THREE, sx - 0.34, sy + 1.8, 0, 0.075, 0.075, 0.075, white, 8)); // pom-pom
  const hands = [];
  [-1, 1].forEach((s) => {
    const sh = [sx + 0.12, sy + 0.95, s * 0.33], el = [sx + 0.5, sy + 0.7, s * 0.4], hd = [sx + 0.85, sy + 0.78, s * 0.3];
    P.push(...limb(THREE, sh, el, 0.1, 0.085, furRed, 7)); P.push(...limb(THREE, el, hd, 0.085, 0.07, furRed, 7));
    P.push(blob(THREE, hd[0], hd[1], hd[2], 0.085, 0.08, 0.085, dark, 8));
    hands.push(hd);
  });
  const sleigh = mergeParts(THREE, P); P.forEach((p) => p.geo.dispose());
  return { sleigh, hands, front: [1.9, 0.5, 0] };
}

/** Tali kekang: garis emas dari kereta ke tiap pasang rusa, tali merah dari tangan Santa. pairs: [[x, y, z(+)], ...] */
export function buildHarness(THREE, { hands, front, pairs, gold = '#d6a63c', red = '#a31f22' }) {
  const P = [];
  let prev = front;
  pairs.forEach(([x, y, z]) => {
    [-1, 1].forEach((s) => {
      const a = [prev[0], prev[1], s * Math.min(Math.abs(prev[2]) || 0.4, z)], b = [x - 0.55, y + 0.05, s * z];
      P.push(tube(THREE, [a, [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2 - 0.05, (a[2] + b[2]) / 2], b], 0.014, gold, 6, 4));
    });
    prev = [x - 0.55, y + 0.05, 0.45];
  });
  const lead = pairs[pairs.length - 1];
  hands.forEach((h, i) => {
    const s = i ? 1 : -1, b = [lead[0] + 0.55, lead[1] + 0.4, s * lead[2]];
    P.push(tube(THREE, [h, [(h[0] + b[0]) / 2, h[1] + 0.35, (h[2] + b[2]) / 2], b], 0.012, red, 12, 4));
  });
  const g = mergeParts(THREE, P); P.forEach((p) => p.geo.dispose());
  return g;
}
