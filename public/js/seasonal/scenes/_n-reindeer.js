// Rusa terbang untuk adegan Natal: badan, kepala bertanduk, dan dua pasang kaki sebagai geometri gabungan
// berwarna vertex (3 draw call per rusa). Menghadap +x, sumbu kaki di bahu dan pinggul supaya bisa diayun.
// Tidak ada yang berjalan saat import.
import { mergeParts } from '../common.js';

const UP = [0, 1, 0];

/** Ruas silinder runcing dari a ke b, jari-jari r1 di a dan r2 di b. */
export function limb(THREE, a, b, r1, r2, color, radial = 6, caps = true) {
  const v = new THREE.Vector3(b[0] - a[0], b[1] - a[1], b[2] - a[2]), len = v.length();
  const q = new THREE.Quaternion().setFromUnitVectors(new THREE.Vector3(...UP), v.clone().normalize());
  const mid = new THREE.Vector3((a[0] + b[0]) / 2, (a[1] + b[1]) / 2, (a[2] + b[2]) / 2);
  const m = new THREE.Matrix4().compose(mid, q, new THREE.Vector3(1, 1, 1));
  const out = [{ geo: new THREE.CylinderGeometry(r2, r1, len, radial, 1, true), m, color }];
  if (caps) {
    out.push({ geo: new THREE.SphereGeometry(r1, radial + 2, 5), m: new THREE.Matrix4().makeTranslation(a[0], a[1], a[2]), color });
    out.push({ geo: new THREE.SphereGeometry(r2, radial + 2, 5), m: new THREE.Matrix4().makeTranslation(b[0], b[1], b[2]), color });
  }
  return out;
}

export function blob(THREE, x, y, z, sx, sy, sz, color, seg = 14) {
  const m = new THREE.Matrix4().compose(new THREE.Vector3(x, y, z), new THREE.Quaternion(), new THREE.Vector3(sx, sy, sz));
  return { geo: new THREE.SphereGeometry(1, seg, Math.max(6, seg - 4)), m, color };
}

export function tube(THREE, pts, r, color, seg = 8, radial = 5) {
  const c = new THREE.CatmullRomCurve3(pts.map((p) => new THREE.Vector3(...p)));
  return { geo: new THREE.TubeGeometry(c, seg, r, radial, false), color };
}

function finish(THREE, parts) {
  const g = mergeParts(THREE, parts);
  parts.forEach((p) => p.geo.dispose());
  return g;
}

/** Mengembalikan { body, legsF, legsB, shoulder:[x,y,z], hip:[x,y,z], nose:[x,y,z] } (geometri milik pemanggil). */
export function buildReindeer(THREE, { redNose = false } = {}) {
  const fur = '#7a4a2a', furDark = '#5e3a22', belly = '#c9a77f', white = '#f1e8d8', horn = '#bda072', hoof = '#1d1511';
  const P = [];
  P.push(blob(THREE, 0, 0, 0, 0.8, 0.34, 0.3, fur));
  P.push(blob(THREE, 0.46, 0.03, 0, 0.36, 0.37, 0.29, fur));
  P.push(blob(THREE, -0.5, 0.02, 0, 0.37, 0.34, 0.29, furDark));
  P.push(blob(THREE, 0.05, -0.13, 0, 0.72, 0.2, 0.27, belly));
  P.push(blob(THREE, 0.74, -0.04, 0, 0.17, 0.2, 0.17, white, 10));
  P.push(...limb(THREE, [0.62, 0.12, 0], [1.0, 0.55, 0], 0.17, 0.1, fur, 8));
  P.push(...limb(THREE, [0.7, 0.04, 0], [1.02, 0.4, 0], 0.1, 0.07, white, 6, false)); // dada/leher bawah terang
  P.push(blob(THREE, 1.08, 0.6, 0, 0.17, 0.13, 0.11, fur, 12));
  P.push(...limb(THREE, [1.1, 0.58, 0], [1.37, 0.46, 0], 0.085, 0.055, furDark, 7));
  const noseCol = redNose ? '#ff3a2a' : '#1d1511';
  P.push(blob(THREE, 1.4, 0.45, 0, 0.06, 0.055, 0.06, noseCol, 8));
  [-1, 1].forEach((s) => {
    P.push(blob(THREE, 1.2, 0.6, s * 0.085, 0.024, 0.024, 0.024, '#0a0807', 5));
    P.push(...limb(THREE, [1.02, 0.68, s * 0.08], [0.95, 0.84, s * 0.2], 0.05, 0.012, furDark, 5, false));
    // tanduk: batang utama melengkung ke belakang, dengan cabang
    const b0 = [1.02, 0.7, s * 0.07], b1 = [0.97, 0.92, s * 0.12], b2 = [0.86, 1.14, s * 0.15], b3 = [0.78, 1.36, s * 0.13];
    P.push(tube(THREE, [b0, b1, b2, b3], 0.022, horn, 10));
    P.push(tube(THREE, [b1, [1.08, 1.03, s * 0.13], [1.16, 1.12, s * 0.12]], 0.017, horn, 5, 4));
    P.push(tube(THREE, [b2, [0.97, 1.28, s * 0.17], [1.02, 1.42, s * 0.17]], 0.016, horn, 5, 4));
    P.push(tube(THREE, [b2, [0.78, 1.3, s * 0.2], [0.7, 1.46, s * 0.22]], 0.014, horn, 5, 4));
    P.push(tube(THREE, [b3, [0.72, 1.5, s * 0.12], [0.68, 1.6, s * 0.1]], 0.014, horn, 4, 4));
  });
  P.push(blob(THREE, -0.86, 0.12, 0, 0.07, 0.07, 0.07, white, 8));
  // tali dada emas
  const ringG = new THREE.TorusGeometry(0.3, 0.014, 4, 18); ringG.rotateY(Math.PI / 2);
  P.push({ geo: ringG, m: new THREE.Matrix4().compose(new THREE.Vector3(0.3, 0, 0), new THREE.Quaternion(), new THREE.Vector3(1, 1.15, 1.05)), color: '#c79a3a' });
  const collar = new THREE.TorusGeometry(0.15, 0.03, 5, 14); collar.rotateY(Math.PI / 2);
  P.push({ geo: collar, m: new THREE.Matrix4().compose(new THREE.Vector3(0.78, 0.28, 0), new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(0, 0, 1), 0.85), new THREE.Vector3(1, 1, 1)), color: '#c79a3a' });
  const body = finish(THREE, P);

  const legs = (k, pts, rr) => {
    const L = [];
    [-0.12, 0.12].forEach((z, i) => {
      const sh = i ? 1 : 0.88; // kaki sisi jauh sedikit berbeda agar tidak serempak
      const q = pts.map(([x, y]) => [x * sh, y, z]);
      for (let j = 0; j < q.length - 1; j++) L.push(...limb(THREE, q[j], q[j + 1], rr[j], rr[j + 1], j < 2 ? fur : furDark, 6));
      const e = q[q.length - 1];
      L.push(blob(THREE, e[0] + 0.015 * k, e[1] - 0.025, e[2], 0.045, 0.06, 0.04, hoof, 6));
    });
    return finish(THREE, L);
  };
  const legsF = legs(1, [[0, 0], [0.3, -0.3], [0.62, -0.42], [0.74, -0.62]], [0.1, 0.065, 0.045, 0.036]);
  const legsB = legs(-1, [[0, 0], [-0.26, -0.34], [-0.72, -0.32], [-0.8, -0.5]], [0.14, 0.075, 0.045, 0.036]);
  return { body, legsF, legsB, shoulder: [0.45, -0.14, 0], hip: [-0.5, -0.12, 0], nose: [1.4, 0.45, 0] };
}
