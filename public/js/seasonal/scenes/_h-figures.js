// Figur upacara untuk adegan flag: pembuat geometri tubuh (loft elips + anggota badan meruncing) dan kerumunan berinstans.
// Tidak ada yang berjalan saat import. Orang menghadap +z lokal; tangan kanan = -x lokal. Satu draw call per jenis seragam.

const SKINS = ['#d9a577', '#c98f62', '#b87a4f', '#a86e45', '#8f5d3a', '#e0b088'];
export const SKIN_TONES = SKINS;

/** Seragam: warna dan perlengkapan per jenis. */
export const OUTFITS = {
  paskibra: { top: '#f3f1ea', legs: '#f3f1ea', shoe: '#17130f', glove: '#fbfaf6', sash: '#e70011', hat: 'cap', hatColor: '#f3f1ea', hatBand: '#e70011', epaulet: '#d8b24a', belt: '#e8e4d8' },
  paskibraF: { top: '#f3f1ea', legs: '#f3f1ea', shoe: '#17130f', glove: '#fbfaf6', sash: '#e70011', hat: 'beret', hatColor: '#f3f1ea', hair: '#15100d', bun: true, epaulet: '#d8b24a', belt: '#e8e4d8' },
  pramuka: { top: '#9a6a36', legs: '#6e4a26', shoe: '#1a130d', neck: '#e70011', neck2: '#f4f2ec', hat: 'beret', hatColor: '#5e3d1d', hair: '#1a1210', belt: '#2a1b0e' },
  pramukaF: { top: '#9a6a36', legs: '#6e4a26', skirt: true, shoe: '#1a130d', neck: '#e70011', neck2: '#f4f2ec', hat: 'hijab', hatColor: '#6e4a26', belt: '#2a1b0e' },
  tni: { top: '#5d6a3a', legs: '#4f5a30', shoe: '#14110c', hat: 'beret', hatColor: '#8d1217', hair: '#171210', belt: '#241d12', epaulet: '#d8b24a', medals: true },
  veteran: { top: '#7d7448', legs: '#5a5434', shoe: '#17120d', hat: 'peci', hatColor: '#2a2118', hair: '#d9d9d6', belt: '#2a2113', epaulet: '#caa24a', medals: true },
  sipil: { top: '#efece4', legs: '#26272c', shoe: '#15110d', hat: 'peci', hatColor: '#121010', hair: '#161111', belt: '#1c1a18' },
  sipilF: { top: '#efece4', legs: '#26272c', skirt: true, shoe: '#15110d', hat: 'hijab', hatColor: '#f1eee6', belt: '#1c1a18' },
  sdM: { top: '#f4f2ec', legs: '#e70011', shoe: '#1b1b1b', hat: 'hair', hair: '#140f0d', belt: '#f4f2ec', child: true },
  sdF: { top: '#f4f2ec', legs: '#e70011', skirt: true, shoe: '#1b1b1b', hat: 'hijab', hatColor: '#f4f2ec', belt: '#f4f2ec', child: true },
  hero: { top: '#ffffff', legs: '#ffffff', shoe: '#ffffff', hat: 'peci', hatColor: '#ffffff', hair: '#ffffff', belt: '#ffffff', hero: true },
};

function acc() { return { pos: [], nor: [], col: [], skin: [], idx: [] }; }

/** Bangun geometri satu figur. detail: 0 (rendah) atau 1 (tinggi). */
export function buildFigure(THREE, kind, detail = 1) {
  const o = OUTFITS[kind];
  const B = acc();
  const C = (c) => new THREE.Color(c);
  const v = new THREE.Vector3(), n3 = new THREE.Matrix3(), M = new THREE.Matrix4(), Q = new THREE.Quaternion(), UP = new THREE.Vector3(0, 1, 0), D = new THREE.Vector3(), S1 = new THREE.Vector3(1, 1, 1), P3 = new THREE.Vector3();
  const skinC = C('#c98f62');
  const hi = detail > 0;
  const child = !!o.child;

  function put(geo, mat, color, isSkin) {
    n3.getNormalMatrix(mat);
    const p = geo.attributes.position, nr = geo.attributes.normal, base = B.pos.length / 3;
    for (let i = 0; i < p.count; i++) {
      v.fromBufferAttribute(p, i).applyMatrix4(mat); B.pos.push(v.x, v.y, v.z);
      v.fromBufferAttribute(nr, i).applyMatrix3(n3).normalize(); B.nor.push(v.x, v.y, v.z);
      B.col.push(color.r, color.g, color.b); B.skin.push(isSkin ? 1 : 0);
    }
    if (geo.index) for (let i = 0; i < geo.index.count; i++) B.idx.push(geo.index.getX(i) + base);
    else for (let i = 0; i < p.count; i++) B.idx.push(i + base);
    geo.dispose();
  }
  const ell = (x, y, z, rx, ry, rz, color, isSkin, ws = 8, hs = 6, rotX = 0) => {
    Q.setFromAxisAngle(D.set(1, 0, 0), rotX);
    put(new THREE.SphereGeometry(1, hi ? Math.max(6, ws - 2) : Math.max(5, ws - 3), hi ? Math.max(4, hs - 1) : Math.max(3, hs - 3)), M.compose(P3.set(x, y, z), Q, v.set(rx, ry, rz)), color, isSkin);
  };
  // tabung meruncing dari a ke b
  const limb = (a, b, r0, r1, color, isSkin, seg = 7) => {
    D.set(b[0] - a[0], b[1] - a[1], b[2] - a[2]); const len = D.length();
    Q.setFromUnitVectors(UP, D.normalize());
    const g = new THREE.CylinderGeometry(r1, r0, len, hi ? 6 : 5, 1, true);
    put(g, M.compose(P3.set((a[0] + b[0]) / 2, (a[1] + b[1]) / 2, (a[2] + b[2]) / 2), Q, S1), color, isSkin);
    if (hi && r1 > 0.04) ell(b[0], b[1], b[2], r1, r1, r1, color, isSkin, 6, 4);
  };
  // loft cincin elips: rings = [[y, rx, rz, cx, cz], ...]
  const loft = (rings, color, isSkin, seg = 12, capTop = true, capBot = true) => {
    const N = hi ? Math.min(12, seg) : 8, pos = [], idx = [];
    rings.forEach(([y, rx, rz, cx = 0, cz = 0]) => { for (let i = 0; i < N; i++) { const a = (i / N) * 6.2832; pos.push(cx + Math.cos(a) * rx, y, cz + Math.sin(a) * rz); } });
    for (let r = 0; r < rings.length - 1; r++) for (let i = 0; i < N; i++) {
      const a = r * N + i, b = r * N + (i + 1) % N, c = (r + 1) * N + i, d = (r + 1) * N + (i + 1) % N;
      idx.push(a, c, b, b, c, d);
    }
    const mid = (r) => { const [y, , , cx = 0, cz = 0] = rings[r]; pos.push(cx, y, cz); return pos.length / 3 - 1; };
    if (capTop) { const t = mid(rings.length - 1), base = (rings.length - 1) * N; for (let i = 0; i < N; i++) idx.push(t, base + (i + 1) % N, base + i); }
    if (capBot) { const t = mid(0); for (let i = 0; i < N; i++) idx.push(t, i, (i + 1) % N); }
    const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); g.setIndex(idx); g.computeVertexNormals();
    put(g, M.identity(), color, isSkin);
  };
  const box = (x, y, z, w, h, d, color, isSkin = false, rz = 0, ry = 0) => {
    Q.setFromEuler(new THREE.Euler(0, ry, rz));
    put(new THREE.BoxGeometry(w, h, d), M.compose(P3.set(x, y, z), Q, S1), color, isSkin);
  };

  const top = C(o.top), legs = C(o.legs), shoe = C(o.shoe), gloveC = o.glove ? C(o.glove) : null;
  const fem = /F$/.test(kind);
  const sx = fem ? 0.9 : 1;
  const hipY = 0.93;

  // kaki (celana) atau rok + betis
  if (o.skirt) {
    loft([[0.58, 0.215, 0.15], [0.7, 0.2, 0.14], [0.85, 0.175, 0.125], [hipY + 0.02, 0.16, 0.11]], legs, false, 14, false, false);
    [-1, 1].forEach((s) => limb([s * 0.07, 0.58, 0], [s * 0.075, 0.07, 0.01], 0.045, 0.034, C('#cfa27a'), true));
  } else {
    [-1, 1].forEach((s) => {
      limb([s * 0.085 * sx, hipY, 0], [s * 0.095 * sx, 0.5, 0.012], 0.098, 0.068, legs, false);
      limb([s * 0.095 * sx, 0.5, 0.012], [s * 0.098 * sx, 0.075, 0], 0.068, 0.054, legs, false);
    });
  }
  [-1, 1].forEach((s) => { box(s * 0.095 * sx, 0.028, 0.05, 0.085, 0.056, 0.22, shoe); });

  // badan: pinggul, pinggang, dada, bahu
  const W = sx;
  loft([[hipY - 0.04, 0.165 * W, 0.105], [1.08, 0.15 * W, 0.098], [1.2, 0.165 * W, 0.11], [1.33, 0.195 * W, 0.126], [1.43, 0.215 * W, 0.11], [1.49, 0.1 * W, 0.075]], top, false, 14);
  // ikat pinggang
  if (o.belt) loft([[1.0, 0.158 * W, 0.103], [1.06, 0.155 * W, 0.1]], C(o.belt), false, 14, false, false);
  // leher dan syal
  limb([0, 1.47, 0], [0, 1.57, 0.005], 0.052, 0.045, skinC, true, 8);
  if (o.neck) { loft([[1.44, 0.095, 0.085], [1.5, 0.075, 0.07]], C(o.neck), false, 10, false, false); box(0, 1.35, 0.1, 0.06, 0.16, 0.012, C(o.neck2), false, 0.0); box(0, 1.37, 0.108, 0.05, 0.1, 0.012, C(o.neck), false); }
  if (o.sash) { // selempang merah putih (kanan ke kiri) melintang dada
    const a = [-0.185 * W, 1.43, 0.065], b = [0.14 * W, 1.02, 0.1];
    limb(a, b, 0.021, 0.021, C(o.sash), false, 4);
    D.set(0, 0, 0); box(0.0, 1.22, 0.112, 0.012, 0.012, 0.01, C('#f4f2ec'));
  }
  if (o.epaulet) [-1, 1].forEach((s) => box(s * 0.185 * W, 1.455, 0, 0.09, 0.012, 0.075, C(o.epaulet), false, s * 0.12));
  if (o.medals) { [0, 1, 2].forEach((i) => box(0.075 * W + i * 0.028, 1.28 - (i % 2) * 0.012, 0.118, 0.022, 0.03, 0.01, C(i === 1 ? '#c9a24a' : '#e70011'))); }
  if (hi) { // kancing atau saku
    [0, 1, 2].forEach((i) => ell(0, 1.34 - i * 0.1, 0.117, 0.008, 0.008, 0.006, C('#c8b070'), false, 5, 3));
  }

  // lengan: kiri lurus di sisi, kanan hormat (siku terangkat, ujung jari di alis)
  const sleeve = top;
  const hs = child ? 1.14 : 1.0;
  const SH = 0.205 * W;
  const L = { s: [SH, 1.43, 0], e: [SH + 0.04, 1.16, 0.0], w: [SH + 0.04, 0.9, 0.04], h: [SH + 0.04, 0.8, 0.05] };
  ell(SH, 1.43, 0, 0.065, 0.062, 0.062, sleeve, false, 8, 6);
  limb(L.s, L.e, 0.062, 0.052, sleeve, false); limb(L.e, L.w, 0.052, 0.044, sleeve, false);
  ell(L.h[0], L.h[1], L.h[2], 0.04, 0.055, 0.028, gloveC || skinC, !gloveC, 7, 5);
  const R = { s: [-SH, 1.43, 0], e: [-SH - 0.255, 1.405, 0.07], w: [-0.19, 1.665, 0.12], h: [-0.115, 1.695, 0.135] };
  if (o.hero) { R.e = [-SH - 0.14, 1.62, 0.02]; R.w = [-SH - 0.12, 1.95, 0.0]; R.h = [-SH - 0.12, 2.08, 0.0]; }
  ell(-SH, 1.43, 0, 0.065, 0.062, 0.062, sleeve, false, 8, 6);
  limb(R.s, R.e, 0.062, 0.052, sleeve, false); limb(R.e, R.w, 0.052, 0.044, sleeve, false);
  // telapak tangan: pipih, sejajar lengan bawah
  D.set(R.h[0] - R.w[0], R.h[1] - R.w[1], R.h[2] - R.w[2]);
  limb(R.w, R.h, 0.034, 0.03, gloveC || skinC, !gloveC, 6);
  if (!o.hero) ell((R.w[0] + R.h[0]) / 2 - 0.004, (R.w[1] + R.h[1]) / 2 + 0.005, (R.w[2] + R.h[2]) / 2, 0.052, 0.012, 0.036, gloveC || skinC, !gloveC, 6, 3);

  // kepala
  const HY = 1.655, hr = child ? 1.18 : 1.07;
  ell(0, HY, 0.008, 0.083 * hr, 0.108 * hr, 0.097 * hr, skinC, true, 10, 8);
  ell(0, HY - 0.012, 0.085 * hr, 0.012, 0.022, 0.016, skinC.clone().multiplyScalar(0.86), true, 5, 4); // hidung
  ell(0, HY - 0.06, 0.07 * hr, 0.05 * hr, 0.04 * hr, 0.04 * hr, skinC, true, 6, 4); // dagu
  [-1, 1].forEach((s) => ell(s * 0.08 * hr, HY - 0.012, 0.004, 0.006, 0.02, 0.013, skinC, true, 5, 3)); // telinga
  const hairC = C(o.hair || '#150f0c'), hatC = o.hatColor ? C(o.hatColor) : hairC;
  const cap = (color, ry, rz, y = HY, zoff = -0.014) => put(new THREE.SphereGeometry(1, hi ? 10 : 6, hi ? 6 : 4, 0, 6.2832, 0, 1.3), M.compose(P3.set(0, y + 0.016, zoff), Q.setFromAxisAngle(D.set(1, 0, 0), -0.5), v.set(0.09 * hr, ry * hr, rz * hr)), color, false);
  if (o.hat === 'hair' || o.hat === 'peci' || o.hat === 'cap' || o.hat === 'beret') cap(hairC, 0.115, 0.108);
  if (o.hat === 'peci') loft([[HY + 0.085, 0.087 * hr, 0.095 * hr], [HY + 0.13, 0.082 * hr, 0.09 * hr], [HY + 0.14, 0.081 * hr, 0.089 * hr]], hatC, false, 12, true, false);
  if (o.hat === 'cap') {
    loft([[HY + 0.075, 0.092 * hr, 0.1 * hr], [HY + 0.125, 0.098 * hr, 0.105 * hr], [HY + 0.15, 0.092 * hr, 0.098 * hr]], hatC, false, 12, true, false);
    if (o.hatBand) loft([[HY + 0.076, 0.093 * hr, 0.101 * hr], [HY + 0.092, 0.095 * hr, 0.103 * hr]], C(o.hatBand), false, 12, false, false);
    box(0, HY + 0.082, 0.105 * hr, 0.13 * hr, 0.01, 0.055, hatC.clone().multiplyScalar(0.8));
  }
  if (o.hat === 'beret') ell(0.012, HY + 0.112, -0.002, 0.108 * hr, 0.036 * hr, 0.104 * hr, hatC, false, 10, 5, -0.14);
  if (o.hat === 'hijab') {
    ell(0, HY + 0.012, -0.01, 0.103 * hr, 0.124 * hr, 0.11 * hr, hatC, false, 10, 7);
    loft([[1.4, 0.2, 0.11, 0, -0.005], [1.5, 0.17, 0.12, 0, -0.01], [HY - 0.05, 0.12 * hr, 0.115 * hr, 0, -0.01]], hatC, false, 10, false, false);
    ell(0, HY + 0.004, 0.073 * hr, 0.064 * hr, 0.078 * hr, 0.04 * hr, skinC, true, 8, 6); // wajah di bukaan
  }
  if (o.bun) ell(0, HY + 0.02, -0.105, 0.042, 0.04, 0.04, hairC, false, 7, 5);
  if (hi && !o.hat) { /* tanpa tutup */ }

  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.Float32BufferAttribute(B.pos, 3));
  g.setAttribute('normal', new THREE.Float32BufferAttribute(B.nor, 3));
  g.setAttribute('color', new THREE.Float32BufferAttribute(B.col, 3));
  g.setAttribute('aSkin', new THREE.Float32BufferAttribute(B.skin, 1));
  g.setIndex(B.idx);
  return g;
}

/** Material figur: warna vertex, cahaya tepi, ayun napas lewat shader. uni = { uT: {value} } dipakai bersama. */
export function figureMaterial(THREE, uni, { rim = '#ffc690', power = 2.2, strength = 1.1, fill = 0.06 } = {}) {
  const m = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.88, metalness: 0 });
  const rc = new THREE.Color(rim).multiplyScalar(strength);
  m.onBeforeCompile = (sh) => {
    sh.uniforms.uT = uni.uT; sh.uniforms.uRim = { value: rc };
    sh.vertexShader = sh.vertexShader
      .replace('#include <common>', '#include <common>\nattribute float aPh;\nattribute float aTone;\nattribute float aSkin;\nuniform float uT;')
      .replace('#include <color_vertex>', `#include <color_vertex>
vColor.rgb *= mix(vec3(1.0), vec3(aTone, pow(aTone, 1.3), pow(aTone, 1.7)), aSkin);`)
      .replace('#include <begin_vertex>', `#include <begin_vertex>
{
  float hh = transformed.y;
  transformed.x += hh * 0.011 * sin(uT * 0.8 + aPh);
  transformed.z += hh * 0.006 * sin(uT * 0.6 + aPh * 1.7);
  transformed.y *= 1.0 + 0.0035 * sin(uT * 1.9 + aPh);
  float arm = smoothstep(-0.2, -0.38, transformed.x) * step(1.3, hh);
  transformed.y += arm * 0.007 * sin(uT * 1.3 + aPh * 2.0);
  transformed.z += step(1.55, hh) * 0.012 * sin(uT * 0.45 + aPh * 3.1);
}`);
    sh.fragmentShader = sh.fragmentShader
      .replace('#include <common>', '#include <common>\nuniform vec3 uRim;')
      .replace('#include <opaque_fragment>', `float rimF = pow(1.0 - saturate(dot(normalize(vNormal), normalize(vViewPosition))), ${power.toFixed(2)});
outgoingLight += uRim * rimF + diffuseColor.rgb * ${fill.toFixed(3)};
#include <opaque_fragment>`);
  };
  m.customProgramCacheKey = () => 'hfig' + power + '|' + fill;
  return m;
}

/**
 * Kerumunan berinstans. people: [{ kind, x, z, yaw, s, ph, tone, bright, detail }].
 * Mengembalikan { group, meshes, update(), dispose(), triangles }.
 */
export function createCrowd(THREE, people, material) {
  const group = new THREE.Group(), meshes = [], geos = [];
  const byKey = new Map();
  people.forEach((p) => { const k = p.kind + '|' + (p.detail || 0); if (!byKey.has(k)) byKey.set(k, []); byKey.get(k).push(p); });
  let triangles = 0;
  const dm = new THREE.Object3D(), col = new THREE.Color();
  byKey.forEach((list, key) => {
    const [kind, d] = key.split('|');
    const geo = buildFigure(THREE, kind, +d);
    const n = list.length;
    geo.setAttribute('aPh', new THREE.InstancedBufferAttribute(new Float32Array(n), 1));
    geo.setAttribute('aTone', new THREE.InstancedBufferAttribute(new Float32Array(n), 1));
    const im = new THREE.InstancedMesh(geo, material, n);
    list.forEach((p, i) => {
      dm.position.set(p.x, p.y || 0, p.z); dm.rotation.set(0, p.yaw, 0); dm.scale.setScalar(p.s || 1); dm.updateMatrix();
      im.setMatrixAt(i, dm.matrix);
      im.setColorAt(i, col.setScalar(p.bright || 1));
      geo.attributes.aPh.setX(i, p.ph || 0); geo.attributes.aTone.setX(i, p.tone || 1);
    });
    im.instanceMatrix.needsUpdate = true; if (im.instanceColor) im.instanceColor.needsUpdate = true;
    im.frustumCulled = false;
    group.add(im); meshes.push(im); geos.push(geo);
    triangles += (geo.index.count / 3) * n;
  });
  return { group, meshes, triangles, dispose() { meshes.forEach((m) => m.dispose()); geos.forEach((g) => g.dispose()); } };
}
