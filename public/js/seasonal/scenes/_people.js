// Pembangun figur manusia parametrik untuk adegan musiman (eid, flag). Tidak ada yang berjalan saat import.
// Setiap orang: bagian digabung per "bucket" dengan warna vertex (tubuh bawah, badan, kepala, dua lengan),
// satu material bersama. Mode rigid: seluruh orang digabung jadi 1 mesh (untuk kerumunan, 1 draw call).
// Animasi tanpa tulang: rotasi per bagian (napas, anggukan, ayun lengan). Orang menghadap +z; yaw lewat group.rotation.y.

const SKINS = ['#c98f62', '#b87a4f', '#d9a577', '#a86e45', '#e0b088'];

// Arah lengan (out, y, z), out dikali sisi (-1 = tangan kanan orang, +1 = kiri). A = lengan kanan, B = lengan kiri.
export const POSES = {
  stand: { A: [[0.07, -1, 0], [0.0, -1, 0.4]], B: [[0.07, -1, 0], [0.0, -1, 0.4]], tilt: 0 },
  salute: { A: [[0.86, -0.42, 0.25], [-0.68, 0.73, 0.05]], B: [[0.06, -1, 0], [0.0, -1, 0.3]], tilt: 0 },
  handshake: { A: [[0.12, -0.72, 0.62], [-0.22, -0.08, 1]], B: [[0.08, -1, 0.05], [-0.05, -1, 0.5]], tilt: 0.04, pump: 1 },
  hug: { A: [[0.35, -0.45, 0.8], [-0.6, 0.3, 0.75]], B: [[0.35, -0.45, 0.8], [-0.6, 0.3, 0.75]], tilt: 0.05 },
  bow: { A: [[0.12, -0.7, 0.5], [-0.12, -0.3, 1]], B: [[0.12, -0.7, 0.5], [-0.12, -0.3, 1]], tilt: 0.75 },
  carry: { A: [[0.16, -0.86, 0.28], [-0.14, 0.02, 1]], B: [[0.16, -0.86, 0.28], [-0.14, 0.02, 1]], tilt: 0.0, tray: 1 },
  wave: { A: [[0.62, -0.5, 0.3], [0.22, 0.95, 0.15]], B: [[0.07, -1, 0], [0.0, -1, 0.4]], tilt: 0, wave: 1 },
};

/** Material bersama: vertexColors + cahaya tepi (rim) dan isi diri (fill). rim: warna, fill: 0..1 kontribusi self-lit. */
export function peopleMaterial(THREE, { rim = '#ffb36b', power = 2.4, strength = 1, fill = 0.05, roughness = 0.85 } = {}) {
  const m = new THREE.MeshStandardMaterial({ vertexColors: true, roughness, metalness: 0 });
  const rc = new THREE.Color(rim).multiplyScalar(strength);
  m.onBeforeCompile = (sh) => {
    sh.uniforms.uRim = { value: rc };
    sh.fragmentShader = sh.fragmentShader
      .replace('#include <common>', '#include <common>\nuniform vec3 uRim;')
      .replace('#include <opaque_fragment>', `float rimF = pow(1.0 - saturate(dot(normalize(vNormal), normalize(vViewPosition))), ${power.toFixed(2)});
outgoingLight += uRim * rimF + diffuseColor.rgb * ${fill.toFixed(3)};
#include <opaque_fragment>`);
  };
  m.customProgramCacheKey = () => 'ppl' + power + '|' + fill;
  return m;
}

function bucket() { return { pos: [], nor: [], col: [], idx: [] }; }

function toGeometry(THREE, b) {
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.Float32BufferAttribute(b.pos, 3));
  g.setAttribute('normal', new THREE.Float32BufferAttribute(b.nor, 3));
  g.setAttribute('color', new THREE.Float32BufferAttribute(b.col, 3));
  g.setIndex(b.idx);
  return g;
}

/**
 * opts: pose, mirror, skin, top, bottom, hat('peci'|'hijab'|'hair'|'cap'|'beret'|'none'), hatColor, hair,
 * lower('pants'|'sarung'|'skirt'), sleeve('long'|'short'), sash, height, child, rigid, material, phase, tray (isi nampan)
 * Mengembalikan { group, update(dt), dispose() }.
 */
export function createPerson(THREE, opts = {}) {
  const o = Object.assign({ pose: 'stand', skin: SKINS[0], top: '#e8dcc0', bottom: '#222', hat: 'none', hatColor: '#111', hair: '#1a1210', lower: 'pants', sleeve: 'long', height: 1, phase: 0, lively: 1, look: 0 }, opts);
  const pose = POSES[o.pose] || POSES.stand;
  const C = (c) => new THREE.Color(c);
  const skin = C(o.skin), top = C(o.top), bot = C(o.bottom);
  const child = !!o.child;
  const ph = o.phase;

  const U = new THREE.Matrix4().makeTranslation(0, 0.95, 0).multiply(new THREE.Matrix4().makeRotationX(pose.tilt));
  const T = (x, y, z) => new THREE.Matrix4().makeTranslation(x, y, z);
  const SH = 0.19;
  const B = { lower: new THREE.Matrix4(), torso: U, head: U.clone().multiply(T(0, 0.6, 0)), armR: U.clone().multiply(T(-SH, 0.5, 0)), armL: U.clone().multiply(T(SH, 0.5, 0)) };
  const buckets = o.rigid ? { all: bucket() } : { lower: bucket(), torso: bucket(), head: bucket(), armR: bucket(), armL: bucket() };

  const v = new THREE.Vector3(), n3 = new THREE.Matrix3(), M = new THREE.Matrix4(), tmp = [];
  function add(name, geo, color, local) {
    const b = o.rigid ? buckets.all : buckets[name];
    M.copy(o.rigid ? B[name] : new THREE.Matrix4()).multiply(local);
    n3.getNormalMatrix(M);
    const p = geo.attributes.position, nr = geo.attributes.normal, base = b.pos.length / 3;
    for (let i = 0; i < p.count; i++) {
      v.fromBufferAttribute(p, i).applyMatrix4(M); b.pos.push(v.x, v.y, v.z);
      v.fromBufferAttribute(nr, i).applyMatrix3(n3).normalize(); b.nor.push(v.x, v.y, v.z);
      b.col.push(color.r, color.g, color.b);
    }
    if (geo.index) for (let i = 0; i < geo.index.count; i++) b.idx.push(geo.index.getX(i) + base);
    else for (let i = 0; i < p.count; i++) b.idx.push(i + base);
    geo.dispose();
  }
  const Q = new THREE.Quaternion(), UP = new THREE.Vector3(0, 1, 0), S = new THREE.Vector3(), P3 = new THREE.Vector3(), D = new THREE.Vector3();
  const rg = o.rigid ? 0.65 : 1;
  const ell = (name, x, y, z, rx, ry, rz, color, ws = 10, hs = 7) => add(name, new THREE.SphereGeometry(1, Math.max(5, Math.round(ws * rg)), Math.max(4, Math.round(hs * rg))), color, new THREE.Matrix4().compose(P3.set(x, y, z), Q.identity(), S.set(rx, ry, rz)));
  const cyl = (name, x, y, z, rt, rb, h, color, sx = 1, sz = 1, seg = 10) => add(name, new THREE.CylinderGeometry(rt, rb, h, Math.max(6, Math.round(seg * rg)), 1), color, new THREE.Matrix4().compose(P3.set(x, y, z), Q.identity(), S.set(sx, 1, sz)));
  const box = (name, x, y, z, w, h, d, color, rz = 0) => add(name, new THREE.BoxGeometry(w, h, d), color, new THREE.Matrix4().compose(P3.set(x, y, z), Q.setFromAxisAngle(D.set(0, 0, 1), rz), S.set(1, 1, 1)));
  // kapsul dari titik a ke b (koordinat bucket)
  const limb = (name, ax, ay, az, bx, by, bz, r, color) => {
    D.set(bx - ax, by - ay, bz - az); const len = D.length();
    Q.setFromUnitVectors(UP, D.normalize());
    add(name, new THREE.CapsuleGeometry(r, Math.max(0.001, len - 2 * r), 2, o.rigid ? 5 : 6), color, new THREE.Matrix4().compose(P3.set((ax + bx) / 2, (ay + by) / 2, (az + bz) / 2), Q, S.set(1, 1, 1)));
  };

  // bawah: kaki / rok / sarung
  const lc = o.lower === 'pants' ? bot : skin;
  if (o.lower === 'pants') {
    [-1, 1].forEach((s) => limb('lower', s * 0.075, 0.93, 0, s * 0.085, 0.07, 0.02, 0.068, bot));
  } else {
    [-1, 1].forEach((s) => limb('lower', s * 0.07, 0.3, 0, s * 0.075, 0.07, 0.02, 0.045, skin));
    const hemY = o.lower === 'sarung' ? 0.2 : 0.1;
    cyl('lower', 0, (0.95 + hemY) / 2, 0, 0.165, 0.225, 0.95 - hemY, bot, 1, 0.8, 12);
    cyl('lower', 0, hemY + 0.03, 0, 0.226, 0.23, 0.06, C(o.accent || '#caa24a'), 1, 0.8, 12);
  }
  void lc;
  [-1, 1].forEach((s) => box('lower', s * 0.08, 0.03, 0.05, 0.1, 0.06, 0.22, C('#1b1411')));

  // badan
  const waist = o.lower === 'pants' ? bot : bot;
  cyl('torso', 0, 0.28, 0, 0.17, 0.15, 0.58, top, 1, 0.62, 12);
  ell('torso', 0, 0.54, 0, 0.215, 0.075, 0.1, top, 12, 6);
  cyl('torso', 0, 0.02, 0, 0.16, 0.16, 0.07, o.lower === 'pants' ? waist : C(o.accent || '#caa24a'), 1, 0.66, 12);
  cyl('torso', 0, 0.62, 0, 0.042, 0.05, 0.1, skin, 1, 1, 8);
  if (o.sash) { box('torso', 0, 0.3, 0.098, 0.065, 0.72, 0.02, C(o.sash), 0.62); box('torso', 0, 0.5, 0.1, 0.4, 0.035, 0.015, C('#f4f4f4')); }
  if (o.collar) ell('torso', 0, 0.55, 0.05, 0.1, 0.04, 0.07, C(o.collar), 8, 5);
  if (pose.tray) {
    cyl('torso', 0, 0.2, 0.38, 0.23, 0.23, 0.025, C('#c9a24a'), 1, 1, 14);
    const food = o.tray || ['#2e7d32', '#d4e157', '#8a4b1f'];
    food.forEach((c, i) => { const a = i * 2.1; ell('torso', Math.cos(a) * 0.11, 0.255, 0.38 + Math.sin(a) * 0.09, 0.07, 0.065, 0.07, C(c), 8, 6); });
  }

  // kepala
  const hs = child ? 1.25 : 1.12;
  ell('head', 0, 0.14 * hs, 0, 0.095 * hs, 0.115 * hs, 0.105 * hs, skin, 12, 9);
  ell('head', 0, 0.115 * hs, 0.1 * hs, 0.014, 0.02, 0.016, skin.clone().multiplyScalar(0.88), 6, 4);
  if (!o.rigid) { // mata putih berpupil dan senyum kecil; muka berhijab lebih maju
    const fz = (o.hat === 'hijab' ? 0.122 : 0.1) * hs;
    [-1, 1].forEach((s) => { ell('head', s * 0.037 * hs, 0.152 * hs, fz - 0.006, 0.017, 0.013, 0.008, C('#f4efe6'), 6, 4); ell('head', s * 0.037 * hs, 0.152 * hs, fz - 0.001, 0.009, 0.01, 0.006, C('#1a0f0a'), 5, 4); ell('head', s * 0.035 * hs, 0.17 * hs, fz - 0.007, 0.02, 0.005, 0.006, C('#2a1810'), 5, 3); });
    ell('head', 0, 0.072 * hs, fz - 0.004, 0.03, 0.009, 0.01, C('#8a2a24'), 6, 4);
    [-1, 1].forEach((s) => ell('head', s * 0.036 * hs, 0.078 * hs, fz - 0.006, 0.008, 0.008, 0.006, skin.clone().multiplyScalar(0.8), 4, 3));
  }
  const hairC = C(o.hair), hatC = C(o.hatColor);
  const capShell = (rx, ry, rz, y, color, rot) => add('head', new THREE.SphereGeometry(1, 12, 7, 0, Math.PI * 2, 0, Math.PI * 0.56), color, new THREE.Matrix4().compose(P3.set(0, y * hs, -0.008), Q.setFromAxisAngle(D.set(1, 0, 0), rot), S.set(rx * hs, ry * hs, rz * hs)));
  if (o.hat === 'hair' || o.hat === 'peci' || o.hat === 'cap') capShell(0.1, 0.125, 0.112, 0.145, hairC, -0.32);
  if (o.hat === 'peci') cyl('head', 0, 0.245 * hs, -0.004, 0.092 * hs, 0.103 * hs, 0.085 * hs, hatC, 1, 1.05, 12);
  if (o.hat === 'cap') { cyl('head', 0, 0.245 * hs, 0, 0.1 * hs, 0.108 * hs, 0.07 * hs, hatC, 1, 1.05, 12); box('head', 0, 0.225 * hs, 0.115 * hs, 0.17 * hs, 0.012, 0.08, hatC.clone().multiplyScalar(0.8)); }
  if (o.hat === 'beret') ell('head', 0.02, 0.245 * hs, -0.005, 0.125 * hs, 0.05 * hs, 0.12 * hs, hatC, 10, 6);
  if (o.hat === 'hijab') {
    ell('head', 0, 0.14 * hs, -0.012, 0.118 * hs, 0.14 * hs, 0.118 * hs, hatC, 12, 9);
    ell('head', 0, -0.01 * hs, -0.02, 0.17 * hs, 0.105 * hs, 0.11 * hs, hatC, 12, 7);
    ell('head', 0, 0.13 * hs, 0.078 * hs, 0.074 * hs, 0.09 * hs, 0.046 * hs, skin, 10, 7);
    ell('head', 0, 0.115 * hs, 0.128 * hs, 0.014, 0.02, 0.016, skin.clone().multiplyScalar(0.88), 6, 4);
  }
  if (o.hat === 'hair' && o.bun) ell('head', 0, 0.2 * hs, -0.12 * hs, 0.05, 0.05, 0.05, hairC, 8, 6);

  // lengan: arah dari preset, dibake bersama siku
  const sleeveC = o.sleeve === 'long' ? top : top;
  const armDefs = [['armR', -1, pose.A], ['armL', 1, pose.B]];
  if (o.mirror) { armDefs[0][2] = pose.B; armDefs[1][2] = pose.A; }
  const sc = child ? 0.95 : 1;
  armDefs.forEach(([name, s, [u, f]]) => {
    const ux = s * 1, ud = new THREE.Vector3(u[0] * ux * -1 * -1, u[1], u[2]).normalize(); // out dikali sisi
    ud.x = u[0] * (s === -1 ? -1 : 1); // sisi kanan (-1) berarti out menuju -x
    const fd = new THREE.Vector3(f[0] * (s === -1 ? -1 : 1), f[1], f[2]).normalize();
    const LU = 0.29 * sc, LF = 0.17 * sc, LH = 0.1 * sc;
    const e = ud.clone().multiplyScalar(LU), w = e.clone().addScaledVector(fd, LF), h = w.clone().addScaledVector(fd, LH);
    ell(name, 0, 0, 0, 0.07, 0.07, 0.07, sleeveC, 8, 6);
    limb(name, 0, 0, 0, e.x, e.y, e.z, 0.062, sleeveC);
    limb(name, e.x, e.y, e.z, w.x, w.y, w.z, 0.054, o.sleeve === 'long' ? sleeveC : skin);
    limb(name, w.x, w.y, w.z, h.x, h.y, h.z, 0.045, skin);
    ell(name, h.x + fd.x * 0.02, h.y + fd.y * 0.02, h.z + fd.z * 0.02, 0.046, 0.05, 0.04, skin, 7, 5);
  });
  void tmp;

  const mat = o.material;
  const group = new THREE.Group();
  const h = o.height * (child ? 0.62 : 1);
  group.scale.setScalar(h);
  const geos = [];
  const meshes = {};
  let upper = null, head = null, armR = null, armL = null;
  if (o.rigid) {
    const g = toGeometry(THREE, buckets.all); geos.push(g);
    group.add(new THREE.Mesh(g, mat));
  } else {
    const mk = (name, parent) => { const g = toGeometry(THREE, buckets[name]); geos.push(g); const m = new THREE.Mesh(g, mat); parent.add(m); meshes[name] = m; return m; };
    mk('lower', group);
    upper = new THREE.Group(); upper.position.set(0, 0.95, 0); upper.rotation.x = pose.tilt; group.add(upper);
    mk('torso', upper);
    head = new THREE.Group(); head.position.set(0, 0.6, 0); upper.add(head); mk('head', head);
    armR = new THREE.Group(); armR.position.set(-SH, 0.5, 0); upper.add(armR); mk('armR', armR);
    armL = new THREE.Group(); armL.position.set(SH, 0.5, 0); upper.add(armL); mk('armL', armL);
  }
  let t = ph * 3.7;
  const base = h;
  return {
    group, pose: o.pose,
    update(dt) {
      t += dt;
      if (o.rigid) { group.scale.y = base * (1 + 0.007 * Math.sin(t * 2.1 + ph)); return; }
      const L = o.lively, st = pose.tilt > 0.3 || pose.pump;
      upper.scale.y = 1 + 0.011 * Math.sin(t * 2.2 + ph);
      upper.position.x = 0.025 * L * Math.sin(t * 0.9 + ph); upper.rotation.z = 0.03 * L * Math.sin(t * 0.9 + ph + 1); // pindah beban
      upper.position.y = 0.95 + (st ? 0.014 * Math.sin(t * 5.0 + ph) : 0.006 * L * Math.sin(t * 2.2 + ph));
      upper.rotation.x = pose.tilt + 0.012 * Math.sin(t * 1.4 + ph) + (pose.tilt > 0.3 ? 0.04 * Math.sin(t * 1.1 + ph) : 0);
      head.rotation.x = 0.05 * Math.sin(t * 1.3 + ph * 1.7) + (pose.tilt > 0.3 ? -0.5 * pose.tilt : 0);
      head.rotation.y = o.look + 0.2 * L * Math.sin(t * 0.7 + ph) + 0.09 * Math.sin(t * 0.37 + ph * 2);
      const a = 0.025 * Math.sin(t * 1.7 + ph), pump = pose.pump ? 0.07 * Math.sin(t * 5.2 + ph) : 0;
      armR.rotation.x = a + pump + (o.pose === 'stand' ? 0.14 * L * Math.sin(t * 1.9 + ph * 3) : 0); armL.rotation.x = -a * 0.8;
      if (o.pose !== 'salute' && o.pose !== 'hug') armL.rotation.z = -0.05 * L * Math.sin(t * 1.3 + ph * 2);
      if (pose.wave) armR.rotation.z = 0.14 * Math.sin(t * 6 + ph);
      if (pose.tray) { armR.rotation.x = armL.rotation.x = 0.02 * Math.sin(t * 2 + ph); }
    },
    dispose() { geos.forEach((g) => g.dispose()); },
  };
}

/** Bangun kerumunan. specs: array opts + { x, y, z, yaw }. Mengembalikan { group, people, update(dt), dispose() }. */
export function createCrowd(THREE, specs, material) {
  const group = new THREE.Group(), people = [];
  specs.forEach((s, i) => {
    const p = createPerson(THREE, Object.assign({ phase: i * 1.7, material }, s));
    p.group.position.set(s.x || 0, s.y || 0, s.z || 0);
    p.group.rotation.y = s.yaw || 0;
    group.add(p.group); people.push(p);
  });
  return { group, people, update(dt) { for (let i = 0; i < people.length; i++) people[i].update(dt); }, dispose() { people.forEach((p) => p.dispose()); } };
}
