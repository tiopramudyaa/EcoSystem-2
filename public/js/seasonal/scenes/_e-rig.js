// Figur manusia berkerangka untuk adegan Lebaran: semua orang digabung menjadi SATU SkinnedMesh (1 draw call),
// proporsi dewasa nyata (kepala sekitar 1/7 tinggi), pakaian lapis dengan warna vertex (baju koko, kebaya, hijab, peci, sarung, batik),
// dan animasi tanpa alokasi: napas, geser beban, anggukan, dan lengan lewat IK dua tulang ke target dunia (salaman, pelukan, nampan).
// Tidak ada yang berjalan saat import. Orang menghadap +z; kiri orang = +x.
import { canvasTex } from '../common.js';

const BN = ['root', 'spine', 'chest', 'neck', 'head', 'uaL', 'faL', 'haL', 'uaR', 'faR', 'haR', 'thL', 'shL', 'thR', 'shR'];
export const BONE = {}; BN.forEach((n, i) => { BONE[n] = i; });
const NB = BN.length;
// posisi lokal tulang (terhadap induk), satuan meter pada orang dewasa
const REST = {
  root: [0, 0.92, 0], spine: [0, 0.04, 0], chest: [0, 0.22, 0], neck: [0, 0.21, 0], head: [0, 0.07, 0],
  uaL: [0.19, 0.15, 0], faL: [0, -0.29, 0], haL: [0, -0.26, 0], uaR: [-0.19, 0.15, 0], faR: [0, -0.29, 0], haR: [0, -0.26, 0],
  thL: [0.09, -0.04, 0], shL: [0, -0.42, 0], thR: [-0.09, -0.04, 0], shR: [0, -0.42, 0],
};
const PARENT = { spine: 'root', chest: 'spine', neck: 'chest', head: 'neck', uaL: 'chest', faL: 'uaL', haL: 'faL', uaR: 'chest', faR: 'uaR', haR: 'faR', thL: 'root', shL: 'thL', thR: 'root', shR: 'thR' };
const HY = 1.565; // pusat kepala (rest)
const L1 = 0.29, L2 = 0.26;

/** Atlas bahan 512x256: kiri = kain polos (tenun halus), kanan = batik kawung (nada abu, dikali warna vertex). */
export function clothAtlas(THREE, rnd) {
  const t = canvasTex(THREE, 512, 256, (g, w, h) => {
    g.fillStyle = '#f2f2f2'; g.fillRect(0, 0, 256, h);
    for (let i = 0; i < 2600; i++) { const v = 205 + Math.floor(rnd() * 50); g.fillStyle = `rgb(${v},${v},${v})`; g.fillRect(rnd() * 256, rnd() * h, 1, 1 + rnd() * 2); }
    g.fillStyle = '#6a6a6a'; g.fillRect(256, 0, 256, h);
    const cell = 32;
    g.fillStyle = '#f0f0f0';
    for (let y = 0; y < h / cell + 1; y++) for (let x = 0; x < 256 / cell + 1; x++) {
      const cx = 256 + x * cell + (y % 2 ? cell / 2 : 0), cy = y * cell;
      for (let k = 0; k < 4; k++) { g.save(); g.translate(cx, cy); g.rotate(k * Math.PI / 2); g.beginPath(); g.ellipse(0, -cell * 0.25, cell * 0.11, cell * 0.22, 0, 0, 6.283); g.fill(); g.restore(); }
      g.fillStyle = '#9a9a9a'; g.beginPath(); g.arc(cx, cy, 2.2, 0, 6.283); g.fill(); g.fillStyle = '#f0f0f0';
    }
    g.fillStyle = '#d8d8d8'; g.fillRect(256, 0, 256, 6); g.fillRect(256, h - 6, 256, 6); // pinggir kain
  });
  t.wrapS = t.wrapT = THREE.ClampToEdgeWrapping; t.anisotropy = 4;
  return t;
}

export function createPeople(THREE, specs, { material, low = false }) {
  const C = (c) => new THREE.Color(c);
  const pos = [], col = [], uvs = [], si = [], sw = [], idx = [];
  const pv = new THREE.Vector3(), pm = new THREE.Matrix4();
  let base = 0, boneBase = 0;
  const WHITE_UV = [0.12, 0.5];

  function vert(x, y, z, c, w, u, v) {
    pv.set(x, y, z).applyMatrix4(pm); pos.push(pv.x, pv.y, pv.z); col.push(c.r, c.g, c.b); uvs.push(u, v);
    let tot = 0; const n = Math.min(4, w.length); for (let i = 0; i < n; i++) tot += w[i][1];
    for (let i = 0; i < 4; i++) { if (i < n) { si.push(boneBase + w[i][0]); sw.push(w[i][1] / tot); } else { si.push(boneBase); sw.push(0); } }
    return pos.length / 3 - 1;
  }
  // rings: [{c:[x,y,z], u:[x,y,z], v:[x,y,z], col, w, uvr}], loft bawah ke atas; u x v = -sumbu
  function loft(rings, N, skip) {
    if (low) N = Math.max(6, Math.round(N * 0.7));
    const start = pos.length / 3;
    for (const r of rings) {
      for (let k = 0; k <= N; k++) {
        const a = k / N * Math.PI * 2, ca = Math.cos(a), sa = Math.sin(a);
        const x = r.c[0] + r.u[0] * ca + r.v[0] * sa, y = r.c[1] + r.u[1] * ca + r.v[1] * sa, z = r.c[2] + r.u[2] * ca + r.v[2] * sa;
        const uu = r.uvr ? r.uvr[0] + (r.uvr[1] - r.uvr[0]) * (k / N) : WHITE_UV[0], vv = r.uvr ? r.uvr[2] : WHITE_UV[1];
        vert(x, y, z, r.col, r.w, uu, vv);
      }
    }
    const S = N + 1;
    for (let i = 0; i < rings.length - 1; i++) for (let k = 0; k < N; k++) {
      if (skip && skip(i, k, N)) continue;
      const wrap = !rings[0].uvr, k1 = wrap && k + 1 === N ? 0 : k + 1;
      const a = start + i * S + k, b = start + i * S + k1, c = a + S, d = start + (i + 1) * S + k1;
      idx.push(a, c, b, b, c, d);
    }
  }
  const ring = (cx, cy, cz, rx, rz, color, w, uvr) => ({ c: [cx, cy, cz], u: [rx, 0, 0], v: [0, 0, rz], col: color, w, uvr });
  // elipsoid: kutub ke kutub; rot opsional = Euler [x,y,z] pada pusatnya
  const rm = new THREE.Matrix4(), re = new THREE.Euler(), rv = new THREE.Vector3();
  function ellip(cx, cy, cz, rx, ry, rz, color, w, ns = 8, nr = 5, rot) {
    const rings = [];
    if (rot) rm.makeRotationFromEuler(re.set(rot[0], rot[1], rot[2]));
    for (let i = 0; i <= nr; i++) {
      const th = (1 - i / nr) * Math.PI, s = Math.sin(th), c = Math.cos(th);
      let r;
      if (rot) {
        const o = rv.set(0, ry * c, 0).applyMatrix4(rm);
        const ux = new THREE.Vector3(rx * s, 0, 0).applyMatrix4(rm), vz = new THREE.Vector3(0, 0, rz * s).applyMatrix4(rm);
        r = { c: [cx + o.x, cy + o.y, cz + o.z], u: [ux.x, ux.y, ux.z], v: [vz.x, vz.y, vz.z], col: color, w };
      } else r = ring(cx, cy + ry * c, cz, rx * s, rz * s, color, w);
      rings.push(r);
    }
    loft(rings, ns);
  }
  const ax = new THREE.Vector3(), au = new THREE.Vector3(), av = new THREE.Vector3(), UPV = new THREE.Vector3(0, 1, 0), SIDEV = new THREE.Vector3(1, 0, 0);
  // tabung dari a ke b: segmen (r0 di a, r1 di b), bobot fungsi u 0..1, n cincin
  function tube(a, b, r0, r1, color, wfn, n = 3, ns = 8, caps = true, rzScale = 1) {
    ax.set(b[0] - a[0], b[1] - a[1], b[2] - a[2]); const len = ax.length(); ax.divideScalar(len);
    au.crossVectors(Math.abs(ax.y) > 0.9 ? SIDEV : UPV, ax).normalize(); // tegak lurus
    av.crossVectors(au, ax).normalize(); // u x v = -sumbu? au x (au x ax) = -ax
    const rings = [];
    const mk = (t, r) => ({ c: [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t, a[2] + (b[2] - a[2]) * t], u: [au.x * r, au.y * r, au.z * r], v: [av.x * r * rzScale, av.y * r * rzScale, av.z * r * rzScale], col: color, w: wfn(t) });
    // tutup awal
    if (caps) rings.push(mk(0, 0.001));
    for (let i = 0; i <= n; i++) { const t = i / n; rings.push(mk(t, r0 + (r1 - r0) * t)); }
    if (caps) rings.push(mk(1, 0.001));
    // pastikan u x v = -sumbu; periksa dan balik bila perlu
    const cr = new THREE.Vector3().crossVectors(au, av);
    if (cr.dot(ax) > 0) rings.reverse();
    loft(rings, ns);
  }
  const W1 = (b) => [[b, 1]];
  const wTorso = (y) => {
    if (y >= 1.2) return [[BONE.chest, 1]];
    if (y >= 1.06) { const t = (y - 1.06) / 0.14; return [[BONE.chest, t], [BONE.spine, 1 - t]]; }
    if (y >= 0.92) { const t = (y - 0.92) / 0.14; return [[BONE.spine, t], [BONE.root, 1 - t]]; }
    return W1(BONE.root);
  };

  // ===== kerangka dan geometri tiap orang =====
  const people = [];
  const rootGroup = new THREE.Group();
  const allBones = [];
  const atlas = clothAtlas(THREE, (() => { let s = 7; return () => ((s = (s * 16807) % 2147483647) / 2147483647); })());

  specs.forEach((sp, pi) => {
    const o = Object.assign({ x: 0, z: 0, yaw: 0, height: 1, build: 'man', skin: '#b98258', top: '#e8dcc0', topStyle: 'koko', lower: 'pants', bottom: '#2a2a2a', hat: 'none', hatColor: '#f1e8cc', hair: '#1a1210', accent: '#d4aa4a', phase: pi * 1.9, motif: false, headScale: 1, shoes: '#1c1612', collar: null }, sp);
    const child = o.build === 'child', woman = o.build === 'woman', elder = !!o.elder;
    const s = o.height * (child ? 0.64 : 1);
    pm.makeTranslation(o.x, 0, o.z).multiply(new THREE.Matrix4().makeRotationY(o.yaw)).multiply(new THREE.Matrix4().makeScale(s, s, s));
    boneBase = pi * NB;
    base = pos.length / 3;
    const skin = C(o.skin), skinD = skin.clone().multiplyScalar(0.78), top = C(o.top), bot = C(o.bottom), hair = C(o.hair), acc = C(o.accent), hat = C(o.hatColor);
    const collar = C(o.collar || o.top);

    // --- badan (loft) ---
    const rg = [];
    const T = (y, rx, rz, cz, c, uvr) => rg.push(ring(0, y, cz, rx, rz, c, wTorso(y), uvr));
    const fem = woman || child;
    const hemY = o.topStyle === 'kebaya' ? 0.80 : o.topStyle === 'koko' ? 0.79 : 0.84;
    const topDark = top.clone().multiplyScalar(0.82);
    T(hemY, fem ? 0.186 : 0.186, fem ? 0.122 : 0.122, 0, o.topStyle === 'kebaya' ? acc : topDark);
    T(hemY + 0.025, fem ? 0.184 : 0.184, fem ? 0.12 : 0.12, 0, top);
    T(0.95, fem ? 0.158 : 0.162, fem ? 0.108 : 0.108, 0, top);
    T(1.06, fem ? 0.128 : 0.152, fem ? 0.096 : 0.104, 0, top);
    T(1.18, fem ? 0.158 : 0.172, fem ? 0.116 : 0.118, 0.006, top);
    T(1.28, fem ? 0.174 : 0.188, fem ? 0.128 : 0.124, 0.014, top);
    T(1.355, fem ? 0.178 : 0.2, fem ? 0.108 : 0.108, 0.004, top);
    T(1.395, fem ? 0.1 : 0.115, 0.075, 0, top);
    T(1.415, 0.06, 0.058, 0, collar); T(1.43, 0.057, 0.056, 0, o.topStyle === 'koko' ? collar : skin);
    // tutup bawah dan atas
    rg.unshift(ring(0, hemY, 0, 0.001, 0.001, topDark, wTorso(hemY)));
    loft(rg, 12);
    // leher
    tube([0, 1.41, 0], [0, 1.5, 0.005], 0.052, 0.047, skin, (t) => [[BONE.neck, 1 - t * 0.5], [BONE.head, t * 0.5]], 2, 8, false);
    // kancing/pita pada baju
    if (o.topStyle === 'koko') for (let i = 0; i < 4; i++) { const y = 1.33 - i * 0.1; ellip(0, y, (fem ? 0.14 : 0.13) + 0.004, 0.011, 0.011, 0.007, C('#d9b64d'), wTorso(y), 6, 4); }
    if (o.topStyle === 'kebaya') ellip(0.045, 1.26, 0.146, 0.022, 0.012, 0.008, acc, W1(BONE.chest), 8, 4);
    // --- bawah: kaki, sarung / kain ---
    const legTop = (side) => [side * 0.09, 0.88, 0];
    for (const side of [1, -1]) {
      const th = side > 0 ? BONE.thL : BONE.thR, sh = side > 0 ? BONE.shL : BONE.shR;
      const showLeg = o.lower === 'pants' || o.lower === 'sarong';
      const lc = o.lower === 'pants' ? bot : skin;
      if (showLeg) {
        tube(legTop(side), [side * 0.09, 0.46, 0.005], 0.07, 0.06, lc, () => W1(th), 3, 8, false);
        tube([side * 0.09, 0.46, 0.005], [side * 0.09, 0.08, 0], 0.062, o.lower === 'pants' ? 0.05 : 0.042, lc, (t) => (t < 0.18 ? [[th, 1 - t / 0.18 * 0.6], [sh, t / 0.18 * 0.6 + 0.4 * (t / 0.18)]] : W1(sh)), 3, 8, false);
      }
      // sepatu / sandal
      ellip(side * 0.09, 0.04, 0.045, 0.045, 0.036, 0.105, C(o.shoes), W1(sh), 8, 5);
    }
    if (o.lower === 'sarong' || o.lower === 'batik' || o.lower === 'skirt') {
      const hem = o.lower === 'sarong' ? 0.34 : 0.07, wide = o.lower === 'batik' ? 0.82 : 1;
      const sr = [];
      const uvr = (v) => (o.motif ? [0.5 + 0.004, 0.996, v] : null);
      const S = (y, rx, rz, c, v) => sr.push(ring(0, y, 0.004, rx, rz, c, y > 0.78 ? W1(BONE.root) : W1(BONE.root), uvr(v)));
      const motifV = (y) => 0.12 + 0.8 * (y - hem) / (1.02 - hem);
      const bc = bot, trim = acc;
      sr.push(ring(0, hem - 0.001, 0.004, 0.001, 0.001, trim, W1(BONE.root)));
      S(hem, 0.2 * wide + 0.02 * (1 - wide), 0.155 * wide + 0.02 * (1 - wide), trim, motifV(hem));
      S(hem + 0.05, 0.2 * wide + 0.018, 0.152 * wide + 0.018, bc, motifV(hem + 0.05));
      S((hem + 0.9) / 2 + 0.05, 0.205 * wide + 0.018, 0.156 * wide + 0.018, bc, motifV((hem + 0.9) / 2 + 0.05));
      S(0.86, 0.192, 0.134, bc, motifV(0.86));
      S(0.99, 0.166, 0.114, bc, motifV(0.99));
      S(1.04, 0.16, 0.108, trim, 0.96);
      loft(sr, 14);
    }
    // --- kepala ---
    const hc = (y) => HY + y;
    const HR = [[-0.118, 0.008, 0.008, 0.055], [-0.108, 0.034, 0.044, 0.05], [-0.085, 0.062, 0.076, 0.034], [-0.05, 0.076, 0.088, 0.025], [-0.01, 0.085, 0.097, 0.014], [0.03, 0.088, 0.1, 0.008], [0.07, 0.087, 0.1, 0.004], [0.1, 0.076, 0.09, 0], [0.122, 0.052, 0.066, -0.004], [0.136, 0.022, 0.032, -0.005], [0.141, 0.002, 0.003, -0.005]];
    const hrings = HR.map(([y, rx, rz, cz]) => ring(0, hc(y), cz, rx, rz, y < -0.04 ? skin.clone().lerp(skinD, 0.3) : skin, W1(BONE.head)));
    loft(hrings, 12);
    const HW = W1(BONE.head);
    // mata, alis, hidung, mulut, telinga
    for (const sd of [-1, 1]) {
      ellip(sd * 0.037, hc(0.028), 0.1, 0.0155, 0.0105, 0.007, C('#f5efe6'), HW, 7, 4);
      ellip(sd * 0.037, hc(0.027), 0.105, 0.0085, 0.0095, 0.0055, C('#1a0f0a'), HW, 6, 4);
      ellip(sd * 0.038, hc(0.056), 0.103, 0.024, 0.0045, 0.006, hair, HW, 6, 3, [0, 0, sd * 0.1]);
      ellip(sd * 0.0885, hc(-0.005), -0.004, 0.011, 0.027, 0.02, skin.clone().lerp(skinD, 0.4), HW, 6, 4);
      ellip(sd * 0.058, hc(-0.05), 0.088, 0.022, 0.026, 0.01, skin.clone().multiplyScalar(woman || child ? 1.06 : 1), HW, 6, 4); // pipi
    }
    ellip(0, hc(-0.012), 0.109, 0.014, 0.02, 0.022, skin.clone().lerp(skinD, 0.35), HW, 8, 5);
    ellip(0, hc(-0.059), 0.092, 0.022, 0.006, 0.008, C('#8a3a30'), HW, 8, 3);
    for (const sd of [-1, 1]) ellip(sd * 0.024, hc(-0.052), 0.091, 0.008, 0.007, 0.007, C('#8a3a30'), HW, 5, 3);
    // rambut / penutup kepala
    const capRows = (rowsFrom, mult, col2, extraBack) => {
      const rr = [];
      HR.forEach(([y, rx, rz, cz]) => { if (y >= rowsFrom) rr.push(ring(0, hc(y), cz - 0.004, rx * mult, rz * mult, col2, HW)); });
      loft(rr, 14);
    };
    if (o.hat === 'hijab') {
      const sk = (i, k, N) => { // buka bidang wajah di depan
        return false; };
      void sk;
      // cangkang kerudung: dari dahi sampai dagu, lalu jatuh ke bahu
      const hj = [];
      const hm = 1.15, drape = [];
      HR.forEach(([y, rx, rz, cz], i) => { if (y >= -0.085) hj.push({ y, rx: rx * hm + 0.004, rz: rz * hm + 0.006, cz: cz - 0.008 }); });
      hj.reverse(); // dari atas ke bawah, nanti dibalik agar loft naik
      const rings2 = [];
      // jatuh: dari y=-0.12 rel kepala ke dada
      const dr = [[-0.12, 0.112, 0.118, -0.01], [-0.16, 0.135, 0.125, -0.012], [-0.24, 0.17, 0.13, -0.006], [-0.34, 0.2, 0.15, 0.0]];
      const wDr = [[[BONE.head, 0.8], [BONE.neck, 0.2]], [[BONE.neck, 0.7], [BONE.chest, 0.3]], [[BONE.chest, 0.9], [BONE.neck, 0.1]], W1(BONE.chest)];
      dr.slice().reverse().forEach(([y, rx, rz, cz], i) => rings2.push(ring(0, hc(y), cz, rx, rz, hat.clone().multiplyScalar(0.94), wDr[wDr.length - 1 - i])));
      hj.reverse().forEach((h, i) => rings2.push(ring(0, hc(h.y), h.cz, h.rx, h.rz, hat, W1(BONE.head))));
      rings2.push(ring(0, hc(0.142), -0.012, 0.002, 0.003, hat, HW));
      const nDr = dr.length, NS = 14;
      const faceRows = (i) => i >= nDr && i <= nDr + 3; // baris wajah
      // sudut phi: 0 = +x, pi/2 = +z (depan)
      loft(rings2, NS, (i, k, N2) => { if (!faceRows(i)) return false; const a = (k + 0.5) / N2 * Math.PI * 2; return Math.abs(a - Math.PI / 2) < 0.78; });
      // bingkai wajah: pita tipis pada tepi bukaan (alis hijab)
    } else if (o.hat === 'peci' || o.hat === 'none' || o.hat === 'hair') {
      capRows(0.062, 1.045, hair, false);
      ellip(0, hc(0.02), -0.052, 0.09, 0.085, 0.06, hair, HW, 10, 6); // rambut belakang pendek
    }
    if (o.hat === 'peci') {
      const pc = hat.clone();
      const pr = [ring(0, hc(0.058), -0.004, 0.093, 0.107, pc, HW), ring(0, hc(0.12), -0.004, 0.096, 0.108, pc, HW), ring(0, hc(0.166), -0.006, 0.095, 0.105, pc, HW), ring(0, hc(0.168), -0.006, 0.001, 0.001, pc.clone().multiplyScalar(1.4), HW)];
      pr.unshift(ring(0, hc(0.056), -0.004, 0.001, 0.001, pc, HW));
      loft(pr, 16);
      // pita keemasan tipis di peci
      loft([ring(0, hc(0.062), -0.004, 0.0945, 0.108, C('#2a2218'), HW), ring(0, hc(0.072), -0.004, 0.095, 0.1085, C('#2a2218'), HW)], 16);
    }
    if (o.hat === 'girlhair') { capRows(0.05, 1.05, hair, true); ellip(0, hc(-0.03), -0.06, 0.088, 0.13, 0.06, hair, HW, 10, 6); }

    // --- lengan ---
    for (const side of [1, -1]) {
      const ua = side > 0 ? BONE.uaL : BONE.uaR, fa = side > 0 ? BONE.faL : BONE.faR, ha = side > 0 ? BONE.haL : BONE.haR;
      const sx = side * 0.19, sy = 1.33;
      const ex = [sx, sy - L1, 0], wr = [sx, sy - L1 - L2, 0];
      const sc = child ? 1 : 1, sl = C(o.topStyle === 'koko' ? o.top : o.top);
      const cuff = acc.clone().lerp(sl, 0.4);
      // bahu bulat + lengan atas
      ellip(sx, sy + 0.005, 0, 0.066, 0.066, 0.062, sl, [[BONE.chest, 0.55], [ua, 0.45]], 8, 5);
      tube([sx, sy, 0], ex, 0.058 * sc, 0.046 * sc, sl, (t) => (t < 0.22 ? [[ua, 0.5 + t / 0.22 * 0.5], [BONE.chest, 0.5 - t / 0.22 * 0.5]] : t > 0.85 ? [[ua, 0.6], [fa, 0.4]] : W1(ua)), 2, 8, false);
      tube(ex, wr, 0.046 * sc, 0.038 * sc, sl, (t) => (t < 0.18 ? [[fa, 0.5 + t / 0.18 * 0.5], [ua, 0.5 - t / 0.18 * 0.5]] : W1(fa)), 2, 8, false);
      tube([sx, wr[1] + 0.025, 0], wr, 0.04 * sc, 0.041 * sc, cuff, () => W1(fa), 1, 8, false);
      // tangan: telapak, jari, ibu jari
      ellip(sx, wr[1] - 0.052, 0.004, 0.034, 0.05, 0.016, skin, W1(ha), 8, 5);
      ellip(sx + side * 0.002, wr[1] - 0.112, 0.006, 0.027, 0.04, 0.0125, skin, W1(ha), 8, 5);
      ellip(sx - side * 0.034, wr[1] - 0.05, 0.015, 0.0125, 0.03, 0.012, skin, W1(ha), 6, 4, [0, 0, side * 0.25]);
    }
    // baki di tangan (opsional), diikat ke tulang spine
    if (o.tray) {
      const tr = o.tray;
      ellip(0, 1.0, 0.4, 0.27, 0.014, 0.27, C('#caa24a'), W1(BONE.spine), 16, 3);
      ellip(0, 1.012, 0.4, 0.24, 0.006, 0.24, C('#efe2b8'), W1(BONE.spine), 16, 3);
      tr.forEach((c, i) => { const a = i / tr.length * 6.283 + 0.4; ellip(Math.cos(a) * 0.12, 1.06, 0.4 + Math.sin(a) * 0.12, 0.055, 0.05, 0.055, C(c), W1(BONE.spine), 8, 5); });
    }

    // --- bone ---
    const bones = {};
    BN.forEach((n, i) => {
      const b = new THREE.Bone(); b.name = n; bones[n] = b; allBones.push(b);
    });
    BN.forEach((n) => { const p = PARENT[n]; if (p) bones[p].add(bones[n]); b2(bones[n], REST[n]); });
    function b2(b, r) { b.position.set(r[0], r[1], r[2]); }
    const root = bones.root;
    root.position.set(o.x, REST.root[1] * s, o.z); root.rotation.y = o.yaw; root.scale.setScalar(s);
    bones.head.scale.setScalar(o.headScale * (child ? 1.17 : 1));
    rootGroup.add(root);
    const P = {
      spec: o, bones, root, s, bx: o.x, bz: o.z, yaw: o.yaw, phase: o.phase,
      tilt: 0, twist: 0, hYaw: 0, hPitch: 0, hRoll: 0, nod: 0, sway: 1, lean: 0,
      arm: {
        1: { on: false, tw: new THREE.Vector3(), tl: new THREE.Vector3(0.24, -0.09, 0.10), pole: new THREE.Vector3(0.5, -1, -0.15), hand: new THREE.Euler(0, 0, 0) },
        '-1': { on: false, tw: new THREE.Vector3(), tl: new THREE.Vector3(-0.24, -0.09, 0.10), pole: new THREE.Vector3(-0.5, -1, -0.15), hand: new THREE.Euler(0, 0, 0) },
      },
      vStart: base, vCount: pos.length / 3 - base,
    };
    people.push(P);
  });

  // ===== geometri akhir =====
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
  g.setAttribute('color', new THREE.Float32BufferAttribute(col, 3));
  g.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));
  g.setAttribute('skinIndex', new THREE.Uint16BufferAttribute(si, 4));
  g.setAttribute('skinWeight', new THREE.Float32BufferAttribute(sw, 4));
  g.setIndex(new THREE.BufferAttribute(pos.length / 3 > 65535 ? new Uint32Array(idx) : new Uint16Array(idx), 1));
  g.computeVertexNormals();
  material.map = atlas; material.needsUpdate = true;
  const mesh = new THREE.SkinnedMesh(g, material);
  mesh.frustumCulled = false;
  rootGroup.add(mesh);
  rootGroup.updateMatrixWorld(true);
  const skeleton = new THREE.Skeleton(allBones);
  mesh.bind(skeleton, new THREE.Matrix4());

  // ===== animasi =====
  const DOWN = new THREE.Vector3(0, -1, 0);
  const vS = new THREE.Vector3(), vT = new THREE.Vector3(), vD = new THREE.Vector3(), vP = new THREE.Vector3(), vE = new THREE.Vector3(), vW = new THREE.Vector3(), vA = new THREE.Vector3(), vB = new THREE.Vector3();
  const q1 = new THREE.Quaternion(), q2 = new THREE.Quaternion(), q3 = new THREE.Quaternion(), qr = new THREE.Quaternion();
  function ik(P, side) {
    const A = P.arm[side], bs = P.bones, ua = bs[side > 0 ? 'uaL' : 'uaR'], fa = bs[side > 0 ? 'faL' : 'faR'], ha = bs[side > 0 ? 'haL' : 'haR'];
    ua.getWorldPosition(vS);
    if (A.on) vT.copy(A.tw); else { vT.copy(A.tl); P.root.localToWorld(vT); }
    const l1 = L1 * P.s, l2 = L2 * P.s;
    vD.subVectors(vT, vS); let d = vD.length(); const maxR = (l1 + l2) * 0.998; if (d > maxR) d = maxR; if (d < 0.08 * P.s) d = 0.08 * P.s;
    vD.normalize();
    // kutub siku: arah lokal orang -> dunia
    P.root.getWorldQuaternion(qr);
    vP.copy(A.pole).applyQuaternion(qr);
    vP.addScaledVector(vD, -vP.dot(vD)); if (vP.lengthSq() < 1e-6) vP.set(0, 0, -1); vP.normalize();
    const a = (l1 * l1 - l2 * l2 + d * d) / (2 * d), h = Math.sqrt(Math.max(0, l1 * l1 - a * a));
    vE.copy(vS).addScaledVector(vD, a).addScaledVector(vP, h);
    vW.copy(vS).addScaledVector(vD, d);
    vA.subVectors(vE, vS).normalize(); vB.subVectors(vW, vE).normalize();
    q1.setFromUnitVectors(DOWN, vA);
    ua.parent.getWorldQuaternion(q2).invert(); ua.quaternion.copy(q2.multiply(q1));
    ua.getWorldQuaternion(q3).invert();
    q1.setFromUnitVectors(DOWN, vB); fa.quaternion.copy(q3.multiply(q1));
    ha.rotation.copy(A.hand);
  }
  const E = new THREE.Euler();
  function update(T) {
    for (let i = 0; i < people.length; i++) {
      const P = people[i], b = P.bones, ph = P.phase, sw2 = P.sway;
      b.root.position.x = P.bx + 0.012 * sw2 * Math.sin(T * 0.9 + ph) * Math.cos(P.yaw) ;
      b.root.position.z = P.bz - 0.012 * sw2 * Math.sin(T * 0.9 + ph) * Math.sin(P.yaw);
      b.root.rotation.set(0.01 * sw2 * Math.sin(T * 0.7 + ph), P.yaw + 0.03 * sw2 * Math.sin(T * 0.55 + ph * 1.3), 0.018 * sw2 * Math.sin(T * 0.9 + ph + 1) + P.lean);
      b.spine.rotation.set(P.tilt * 0.55 + 0.01 * Math.sin(T * 2.2 + ph), 0, 0);
      b.chest.rotation.set(P.tilt * 0.45 + 0.012 * Math.sin(T * 2.2 + ph + 0.4), P.twist + 0.025 * sw2 * Math.sin(T * 0.8 + ph), 0);
      b.neck.rotation.set(0.04 * Math.sin(T * 1.1 + ph * 1.7) + P.hPitch * 0.4, P.hYaw * 0.4, P.hRoll * 0.4);
      b.head.rotation.set(0.03 * Math.sin(T * 1.3 + ph) + P.hPitch * 0.6 + P.nod * Math.sin(T * 3.4 + ph), P.hYaw * 0.6 + 0.07 * sw2 * Math.sin(T * 0.6 + ph), P.hRoll * 0.6 + 0.03 * Math.sin(T * 0.9 + ph));
      // kaki: pindah beban kecil
      b.thL.rotation.x = 0.012 * Math.sin(T * 0.9 + ph); b.thR.rotation.x = -0.012 * Math.sin(T * 0.9 + ph);
      void E;
    }
    rootGroup.updateMatrixWorld(true);
    for (let i = 0; i < people.length; i++) {
      const P = people[i];
      // sedikit ayun pada lengan istirahat
      ik(P, 1); ik(P, -1);
    }
  }
  return {
    group: rootGroup, mesh, people, update,
    dispose() { g.dispose(); atlas.dispose(); skeleton.dispose && skeleton.dispose(); },
  };
}
