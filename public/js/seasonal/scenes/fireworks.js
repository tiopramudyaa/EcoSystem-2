// Tahun Baru: perayaan di tepi sungai kota. Siluet gedung detail, menara landmark, jembatan dengan jejak lampu mobil,
// air dengan pantulan cermin, kembang api berjenis-jenis (peony, willow, ring, crossette, palm), asap berpendar,
// kilatan cahaya ke gedung dan air, siluet penonton melambai. Semua geometri prosedural.
import { createCrowd, peopleMaterial } from './_people.js';
import { pal, rng, glowTexture, canvasTex, paintedSky, starField, cloudTexture, mergeParts, particlePool, easeInOut, smooth, lerp } from '../common.js';

const WATER = -2.2;

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(2026);
  const tex = glowTexture(THREE);
  const smokeTex = cloudTexture(THREE, rnd);
  const S = duration / 2800;
  const gold = P.accent.clone().multiplyScalar(1.7), silver = new THREE.Color('#dbe6ff').multiplyScalar(1.6);
  const red = new THREE.Color('#ff4636').multiplyScalar(1.5), blue = new THREE.Color('#4f8dff').multiplyScalar(1.7);
  camera.fov = 60; camera.updateProjectionMatrix();

  scene.fog = new THREE.FogExp2(new THREE.Color('#16141b'), 0.0042);
  scene.add(paintedSky(THREE, rnd, { stops: [[0, new THREE.Color('#3a3036').lerp(P.accent, 0.08)], [0.14, new THREE.Color('#1b1a24')], [0.5, new THREE.Color('#0a0a12')], [1, new THREE.Color('#040409')]], span: 2.6 }));
  const sf = starField(THREE, rnd, 420, { tex, span: 2.6, minEl: 0.12, size: 1.5, color: '#e8eeff' });
  scene.add(sf.group);

  scene.add(new THREE.HemisphereLight(new THREE.Color('#5a6a98'), new THREE.Color('#1a1418'), 0.3));
  const rim = new THREE.DirectionalLight(new THREE.Color('#d8b878'), 0.45); rim.position.set(-10, 25, -60); scene.add(rim);
  const flashes = [0, 1, 2].map(() => { const l = new THREE.PointLight('#ffffff', 0, 260, 1.3); scene.add(l); return l; });
  let fi = 0;

  // ---- tekstur jendela ----
  const winTex = canvasTex(THREE, 128, 256, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    const cs = ['#ffd48a', '#ffe6b0', '#fff3d6', '#bfe0ff', '#ffc070'];
    for (let y = 0; y < 16; y++) for (let x = 0; x < 8; x++) {
      if (rnd() < 0.27) { g.fillStyle = cs[(rnd() * cs.length) | 0]; g.globalAlpha = 0.55 + rnd() * 0.45; g.fillRect(x * 16 + 3, y * 16 + 4, 10, 8); }
    }
    g.globalAlpha = 1;
  });
  winTex.wrapS = winTex.wrapT = THREE.RepeatWrapping;

  const mirror = new THREE.Group(); mirror.scale.y = -1; mirror.position.y = 2 * WATER; scene.add(mirror);
  const mirrorMat = (c) => new THREE.MeshBasicMaterial({ color: c, map: winTex });
  const flat = new THREE.MeshBasicMaterial({ color: '#06070b' });
  const own = []; // geometri yang kita buat sendiri

  // ---- kota: dua baris gedung dengan tinggi bervariasi ----
  const beacons = [];
  function city(r, { z0, zj, hMul, xMin, xMax }) {
    const parts = [], col = new THREE.Color();
    for (let x = xMin; x < xMax;) {
      const w = 4 + r() * 7, d = 4 + r() * 6, prof = 0.3 + 0.7 * Math.exp(-Math.pow((x + 5) / 75, 2));
      const h = hMul * (9 + 55 * prof * (0.3 + 0.9 * r() * Math.sqrt(r()))), z = z0 - r() * zj;
      col.setHSL(0.62, 0.22, 0.045 + 0.04 * r());
      const su = Math.max(1, Math.round((w + d) / 14.4)), sv = Math.max(1, Math.round(h / 14));
      const box = (bw, bh, bd, y, s) => parts.push({ geo: new THREE.BoxGeometry(bw, bh, bd), m: new THREE.Matrix4().makeTranslation(x, WATER + y + bh / 2, z), color: col.clone().multiplyScalar(0.9 + r() * 0.2), uvScale: s });
      box(w, h, d, 0, [su, sv]);
      let top = h;
      if (r() < 0.5) { const h2 = h * (0.15 + 0.15 * r()); box(w * 0.66, h2, d * 0.66, h, [Math.max(1, su - 1), 1]); top += h2; }
      if (r() < 0.2) { const h3 = 3 + r() * 5; box(w * 0.36, h3, d * 0.36, top, [1, 1]); top += h3; }
      if (r() < 0.12) { const hp = 6 + r() * 6, g = new THREE.CylinderGeometry(0.1, w * 0.55, hp, 4); g.rotateY(Math.PI / 4); parts.push({ geo: g, m: new THREE.Matrix4().makeTranslation(x, WATER + top + hp / 2, z), color: col, uvScale: [0, 0] }); top += hp; }
      else if (r() < 0.3) { const ha = 5 + r() * 6; parts.push({ geo: new THREE.BoxGeometry(0.25, ha, 0.25), m: new THREE.Matrix4().makeTranslation(x + (r() - 0.5) * w * 0.3, WATER + top + ha / 2, z), color: '#222', uvScale: [0, 0] }); top += ha; }
      if (top > 34 || r() < 0.25) beacons.push(x, WATER + top + 0.6, z);
      x += w * (0.7 + 0.35 * r());
    }
    const geo = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose());
    const n = geo.attributes.normal, uv = geo.attributes.uv; // atap/dasar tidak bermotif jendela
    for (let i = 0; i < n.count; i++) if (Math.abs(n.getY(i)) > 0.5) uv.setXY(i, 0, 0);
    own.push(geo);
    return geo;
  }
  const cityMat = (e) => new THREE.MeshStandardMaterial({ vertexColors: true, emissiveMap: winTex, emissive: new THREE.Color('#ffffff'), emissiveIntensity: e, roughness: 0.85, metalness: 0.1 });
  const gF = city(rng(31), { z0: -88, zj: 10, hMul: 0.8, xMin: -175, xMax: 175 });
  const gB = city(rng(77), { z0: -122, zj: 10, hMul: 1.3, xMin: -190, xMax: 190 });
  scene.add(new THREE.Mesh(gF, cityMat(0.7)), new THREE.Mesh(gB, cityMat(0.45)));
  mirror.add(new THREE.Mesh(gF, mirrorMat('#c4c4d2')));

  // ---- menara landmark: tripod, poros, dua bola bercahaya, antena ----
  const twr = [], tm = (x, y, z) => new THREE.Matrix4().makeTranslation(x, y, z);
  const shaft = new THREE.CylinderGeometry(0.5, 1.3, 64, 10);
  twr.push({ geo: shaft, m: tm(0, 32, 0), color: '#5d5666' });
  for (let i = 0; i < 3; i++) {
    const a = i * 2.094 + 0.4, leg = new THREE.CylinderGeometry(0.4, 0.8, 38, 6);
    const m = new THREE.Matrix4().makeTranslation(Math.cos(a) * 4.8, 19, Math.sin(a) * 4.8).multiply(new THREE.Matrix4().makeRotationAxis(new THREE.Vector3(-Math.sin(a), 0, Math.cos(a)), -0.23));
    twr.push({ geo: leg, m, color: '#5d5666' });
  }
  twr.push({ geo: new THREE.CylinderGeometry(0.08, 0.4, 24, 6), m: tm(0, 76, 0), color: '#555' });
  const tg = mergeParts(THREE, twr); twr.forEach((p) => p.geo.dispose()); own.push(tg);
  const TX = 34, TZ = -102;
  const tower = new THREE.Group(); tower.position.set(TX, WATER, TZ); scene.add(tower);
  tower.add(new THREE.Mesh(tg, new THREE.MeshStandardMaterial({ vertexColors: true, emissive: new THREE.Color('#4a4256'), emissiveIntensity: 0.7, roughness: 0.5, metalness: 0.6 })));
  const orb = new THREE.MeshStandardMaterial({ color: '#2b2230', emissive: P.accent.clone().lerp(new THREE.Color('#ffb36a'), 0.3), emissiveIntensity: 0.75, roughness: 0.35, metalness: 0.5 });
  const ringM = new THREE.MeshBasicMaterial({ color: P.accent.clone().multiplyScalar(1.4) });
  const orbGeo = new THREE.SphereGeometry(1, 32, 20), ringG = new THREE.TorusGeometry(1, 0.045, 6, 48);
  own.push(orbGeo, ringG);
  [[38, 6.6], [58, 4.2], [16, 2.4]].forEach(([y, r]) => {
    const o = new THREE.Mesh(orbGeo, orb); o.scale.setScalar(r); o.position.y = y; tower.add(o);
    [0, 0.5, -0.5].forEach((k) => { const rg = new THREE.Mesh(ringG, ringM); rg.rotation.x = Math.PI / 2; rg.scale.setScalar(r * Math.sqrt(1 - k * k) * 1.01); rg.position.y = y + k * r; tower.add(rg); });
  });
  const beacon = new THREE.Mesh(new THREE.SphereGeometry(0.5, 8, 6), new THREE.MeshBasicMaterial({ color: '#ff3b30' })); beacon.position.y = 89; tower.add(beacon);
  const tMir = new THREE.Group(); tMir.position.set(TX, WATER, TZ);
  tMir.add(new THREE.Mesh(tg, flat), ...[[38, 6.6], [58, 4.2], [16, 2.4]].map(([y, r]) => { const o = new THREE.Mesh(orbGeo, new THREE.MeshBasicMaterial({ color: P.accent.clone().multiplyScalar(0.55) })); o.scale.setScalar(r); o.position.y = y; return o; }));
  mirror.add(tMir);

  // ---- jembatan: dek, tiang, kabel, lampu tepi ----
  const BZ = -62, DY = 2.6;
  const bd = [], bg = [];
  bd.push({ geo: new THREE.BoxGeometry(360, 0.7, 6.4), m: tm(0, DY, BZ), color: '#15161e' });
  for (let x = -153; x <= 153; x += 34) bd.push({ geo: new THREE.BoxGeometry(2.2, DY - WATER, 3.2), m: tm(x, (DY + WATER) / 2, BZ), color: '#12131a' });
  [-45, 55].forEach((px) => bd.push({ geo: new THREE.BoxGeometry(1.7, 34, 1.7), m: tm(px, DY + 17, BZ), color: '#1b1c26' }));
  [-1, 1].forEach((s) => bg.push({ geo: new THREE.BoxGeometry(360, 0.14, 0.14), m: tm(0, DY + 0.7, BZ + s * 3), color: P.accent.clone().multiplyScalar(1.3) }));
  const bdG = mergeParts(THREE, bd), bgG = mergeParts(THREE, bg); bd.forEach((p) => p.geo.dispose()); bg.forEach((p) => p.geo.dispose()); own.push(bdG, bgG);
  const bridgeDark = new THREE.Mesh(bdG, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.7, metalness: 0.3 }));
  const bridgeGlow = new THREE.Mesh(bgG, new THREE.MeshBasicMaterial({ vertexColors: true }));
  scene.add(bridgeDark, bridgeGlow);
  mirror.add(new THREE.Mesh(bdG, flat), new THREE.Mesh(bgG, new THREE.MeshBasicMaterial({ color: P.accent.clone().multiplyScalar(0.5) })));
  const cab = [];
  [-45, 55].forEach((px) => [-1, 1].forEach((s) => { for (let i = 0; i < 5; i++) cab.push(px, DY + 33 - i * 3.4, BZ, px + s * (8 + i * 10), DY + 0.4, BZ); }));
  const cabG = new THREE.BufferGeometry(); cabG.setAttribute('position', new THREE.Float32BufferAttribute(cab, 3)); own.push(cabG);
  scene.add(new THREE.LineSegments(cabG, new THREE.LineBasicMaterial({ color: P.accent.clone().multiplyScalar(0.7), transparent: true, opacity: 0.3 })));

  // mobil: kepala lampu putih dan lampu belakang merah, ekor memudar
  const NCAR = 26, TR = 5, cp = new Float32Array(NCAR * TR * 3), cc = new Float32Array(NCAR * TR * 3);
  const cars = [];
  for (let i = 0; i < NCAR; i++) {
    const dir = i % 2 ? 1 : -1, c = dir > 0 ? new THREE.Color('#ffeccb') : new THREE.Color('#ff3a2a');
    cars.push({ x: (rnd() - 0.5) * 340, v: dir * (16 + rnd() * 14), z: BZ + dir * 1.6 + (rnd() - 0.5) * 0.6, dir });
    for (let k = 0; k < TR; k++) { const f = Math.pow(1 - k / TR, 2) * (dir > 0 ? 1.3 : 1.0); cc[(i * TR + k) * 3] = c.r * f; cc[(i * TR + k) * 3 + 1] = c.g * f; cc[(i * TR + k) * 3 + 2] = c.b * f; }
  }
  const carG = new THREE.BufferGeometry(); carG.setAttribute('position', new THREE.BufferAttribute(cp, 3)); carG.setAttribute('color', new THREE.BufferAttribute(cc, 3)); own.push(carG);
  const carMat = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 4.2, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending });
  const carPts = new THREE.Points(carG, carMat); carPts.frustumCulled = false; scene.add(carPts);
  const carRef = new THREE.Points(carG, carMat.clone()); carRef.material.opacity = 0.35; carRef.scale.y = -1; carRef.position.y = 2 * WATER; carRef.frustumCulled = false; scene.add(carRef);

  // lampu merah puncak gedung berkedip + lampu lengkung jembatan (titik cahaya)
  const nb = beacons.length / 3, bc = new Float32Array(nb * 3), bph = new Float32Array(nb);
  const bcol = new THREE.Color('#ff3b30');
  for (let i = 0; i < nb; i++) bph[i] = rnd() * 6.28;
  const beaG = new THREE.BufferGeometry(); beaG.setAttribute('position', new THREE.Float32BufferAttribute(beacons, 3)); beaG.setAttribute('color', new THREE.BufferAttribute(bc, 3)); own.push(beaG);
  const beaPts = new THREE.Points(beaG, new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 6, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  beaPts.frustumCulled = false; scene.add(beaPts);

  // ---- air: pantulan cermin di bawahnya + permukaan semitransparan beriak ----
  const nTex = canvasTex(THREE, 256, 256, (g, w, h) => {
    const im = g.createImageData(w, h), H = (x, y) => Math.sin(x * 0.19 + Math.sin(y * 0.07) * 2) * 0.5 + Math.sin(y * 0.31 + x * 0.05) * 0.35 + Math.sin((x + y) * 0.11) * 0.3;
    for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) {
      const dx = H(x + 1, y) - H(x - 1, y), dy = H(x, y + 1) - H(x, y - 1), i = (y * w + x) * 4;
      im.data[i] = 128 + dx * 40; im.data[i + 1] = 128 + dy * 40; im.data[i + 2] = 255; im.data[i + 3] = 255;
    }
    g.putImageData(im, 0, 0);
  }, false);
  nTex.wrapS = nTex.wrapT = THREE.RepeatWrapping; nTex.repeat.set(70, 18);
  const water = new THREE.Mesh(new THREE.PlaneGeometry(520, 190), new THREE.MeshStandardMaterial({ color: '#070910', roughness: 0.1, metalness: 0.7, normalMap: nTex, normalScale: new THREE.Vector2(0.5, 0.5), transparent: true, opacity: 0.8 }));
  water.rotation.x = -Math.PI / 2; water.position.set(0, WATER, -42); scene.add(water);

  // ---- tepi sungai: promenade, pagar, lampu jalan ----
  const quay = new THREE.Mesh(new THREE.BoxGeometry(160, 2.2, 60), new THREE.MeshStandardMaterial({ color: '#13131a', roughness: 0.6, metalness: 0.2, transparent: true }));
  quay.renderOrder = 10;
  quay.position.set(0, -1.1, 42); scene.add(quay);
  const lampG = new THREE.CylinderGeometry(0.07, 0.09, 3.4, 6), bulbG = new THREE.SphereGeometry(0.2, 10, 8), railG = new THREE.BoxGeometry(160, 0.1, 0.1);
  own.push(lampG, bulbG, railG);
  const dark = new THREE.MeshStandardMaterial({ color: '#0a0a10', roughness: 0.6, metalness: 0.5 }), bulbM = new THREE.MeshBasicMaterial({ color: new THREE.Color('#ffd9a0').multiplyScalar(1.4) });
  const rail = new THREE.Mesh(railG, dark); rail.position.set(0, 1.05, 12.5); scene.add(rail);
  const lg = [];
  for (let i = 0; i < 7; i++) {
    const x = -36 + i * 12, p = new THREE.Mesh(lampG, dark); p.position.set(x, 1.7, 12.5); scene.add(p);
    const b = new THREE.Mesh(bulbG, bulbM); b.position.set(x, 3.5, 12.5); scene.add(b); lg.push(x, 3.5, 12.5);
  }
  const lgG = new THREE.BufferGeometry(); lgG.setAttribute('position', new THREE.Float32BufferAttribute(lg, 3)); own.push(lgG);
  const lgP = new THREE.Points(lgG, new THREE.PointsMaterial({ map: tex, color: '#ffc27a', size: 28, transparent: true, opacity: 0.5, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  lgP.frustumCulled = false; scene.add(lgP);

  // ---- penonton: siluet berlatar cahaya (rim hangat), pose melambai/berdiri, beberapa anak ----
  const crowdMat = peopleMaterial(THREE, { rim: '#ffbe78', power: 3.4, strength: 0.75, fill: 0.012 });
  crowdMat.transparent = true;
  const cl = ['#0b0c12', '#10121a', '#0e0f16', '#14151d'], specs = [], PEO = 54;
  for (let i = 0; i < PEO; i++) {
    const row = i % 3, kid = rnd() < 0.12, pose = rnd() < 0.5 ? 'wave' : 'stand';
    specs.push({ x: -27 + ((i / 3) | 0) * 3.05 + (rnd() - 0.5) * 1.2, z: 14.2 + row * 1.9 + rnd() * 0.6, yaw: (rnd() - 0.5) * 0.5, rigid: true, pose, mirror: rnd() < 0.5,
      height: kid ? 0.62 : 0.92 + rnd() * 0.16, child: kid, top: cl[(rnd() * 4) | 0], bottom: '#07080c', skin: '#2a1d16', hair: '#050506', hat: rnd() < 0.3 ? 'cap' : rnd() < 0.3 ? 'hijab' : 'hair', hatColor: '#0a0a0e' });
  }
  const crowd = createCrowd(THREE, specs, crowdMat);
  crowd.group.traverse((o) => { o.renderOrder = 11; });
  scene.add(crowd.group);

  // ---- kembang api: percikan, jejak, pelacak, asap ----
  const sparks = particlePool(THREE, 1000, { tex, size: 4.6, gravity: -9, drag: 0.9 });
  const trails = particlePool(THREE, 800, { tex, size: 2.6, gravity: -2, drag: 0.8 });
  scene.add(sparks.points, trails.points);
  const mkRef = (pool) => { const r = new THREE.Points(pool.points.geometry, pool.points.material.clone()); r.material.opacity = 0.3; r.scale.y = -1; r.position.y = 2 * WATER; r.frustumCulled = false; scene.add(r); return r; };
  const refS = mkRef(sparks), refT = mkRef(trails);
  const smokes = [];
  for (let i = 0; i < 8; i++) {
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: smokeTex, color: '#444', transparent: true, opacity: 0, depthWrite: false }));
    sp.visible = false; scene.add(sp); smokes.push({ sp, age: 99, col: new THREE.Color(), kick: 0 });
  }
  let si = 0;
  const TRC = [];
  for (let i = 0; i < 90; i++) TRC.push({ on: false, x: 0, y: 0, z: 0, vx: 0, vy: 0, vz: 0, age: 0, life: 0, drag: 1, g: -5, acc: 0, step: 0.05, tl: 0.7, split: -1, col: gold, col2: gold });
  const tmpC = new THREE.Color();
  function tracer(x, y, z, vx, vy, vz, o) {
    const t = TRC.find((q) => !q.on); if (!t) return;
    Object.assign(t, { on: true, x, y, z, vx, vy, vz, age: 0, acc: 0, split: -1, col2: o.col }, o);
  }
  const sph = (s, out) => { const u = rnd() * 2 - 1, th = rnd() * 6.283, r = Math.sqrt(1 - u * u); return [Math.cos(th) * r * s, u * s, Math.sin(th) * r * s]; };
  const SHOT = {
    peony(b) { sparks.burst(b.x, b.y, b.z, b.c1, 170, 44, 2.1, rnd); sparks.burst(b.x, b.y, b.z, b.c2, 50, 22, 1.6, rnd); },
    ring(b) {
      for (let i = 0; i < 84; i++) {
        const a = (i / 84) * 6.283, s = 35 * (0.95 + rnd() * 0.1), y0 = Math.sin(a) * s, tilt = 0.55;
        sparks.emit(b.x, b.y, b.z, Math.cos(a) * s, y0 * Math.cos(tilt), y0 * Math.sin(tilt), b.c1, 1.7 + rnd() * 0.3);
      }
      sparks.burst(b.x, b.y, b.z, b.c2, 36, 12, 1.4, rnd);
    },
    willow(b) { for (let i = 0; i < 26; i++) { const v = sph(31 + rnd() * 9); tracer(b.x, b.y, b.z, v[0], v[1], v[2], { life: 2.0, drag: 1.0, g: -5.2, step: 0.045, tl: 0.8, col: b.c1 }); } sparks.burst(b.x, b.y, b.z, b.c1, 40, 10, 1.3, rnd); },
    cross(b) { for (let i = 0; i < 13; i++) { const v = sph(28); tracer(b.x, b.y, b.z, v[0], v[1], v[2], { life: 1.5, drag: 0.8, g: -4, step: 0.06, tl: 0.45, split: 0.62, col: b.c1, col2: b.c2 }); } },
    palm(b) { for (let i = 0; i < 8; i++) { const a = i / 8 * 6.283; tracer(b.x, b.y, b.z, Math.cos(a) * 22, 30 + rnd() * 6, Math.sin(a) * 22, { life: 2.0, drag: 0.55, g: -9, step: 0.04, tl: 0.8, col: b.c1 }); } sparks.burst(b.x, b.y, b.z, b.c2, 50, 11, 1.3, rnd); },
  };
  const SEQ = [
    [0.2, 'peony', -38, 62, -104, gold, silver], [0.55, 'willow', 14, 76, -112, gold, gold], [0.85, 'ring', -8, 64, -96, blue, silver],
    [1.1, 'peony', 42, 58, -100, red, gold], [1.35, 'cross', -52, 58, -105, silver, red], [1.6, 'palm', 4, 70, -108, gold, red],
    [1.85, 'peony', 26, 68, -115, blue, silver], [2.0, 'willow', -24, 74, -110, gold, gold], [2.12, 'ring', 32, 64, -102, gold, silver],
    [2.22, 'peony', -46, 64, -100, red, gold], [2.3, 'palm', 48, 70, -108, gold, silver], [2.38, 'peony', 0, 82, -118, silver, gold], [2.44, 'peony', -16, 56, -98, blue, silver],
  ].map(([t, kind, x, y, z, c1, c2], i) => ({ t: t * S, kind, x, y, z, c1, c2, fired: false, rise: (i > 6 ? 0.3 : 0.5) * S, acc: 0 }));

  function fire(b) {
    b.fired = true; SHOT[b.kind](b);
    const l = flashes[fi++ % 3]; l.color.copy(b.c1).lerp(b.c2, 0.3); l.position.set(b.x * 0.7, b.y * 0.6, b.z + 45); l.intensity = 420;
    const sm = smokes[si++ % 8]; sm.age = 0; sm.col.copy(b.c1); sm.kick = 1; sm.sp.visible = true; sm.sp.position.set(b.x, b.y - 2, b.z - 4);
  }

  const tmpv = [0, 0, 0];
  return {
    update(t, dt) {
      const T = t * duration / 1000;
      for (const b of SEQ) {
        if (b.fired) continue;
        const L = b.t - b.rise;
        if (T >= L && T < b.t && dt > 0) {
          const k = (T - L) / b.rise, e = 1 - Math.pow(1 - k, 2), px = lerp(b.x * 0.55, b.x, e), pz = lerp(-58, b.z, e), py = lerp(WATER + 1, b.y, e);
          b.acc += dt; while (b.acc > 0.025) { b.acc -= 0.025; trails.emit(px, py, pz, (rnd() - 0.5) * 2, -3 - rnd() * 3, (rnd() - 0.5) * 2, gold, 0.5); }
        }
        if (T >= b.t) fire(b);
      }
      // pelacak: gerak, jejak, pecah (crossette)
      if (dt > 0) for (const q of TRC) {
        if (!q.on) continue;
        const dk = Math.exp(-q.drag * dt);
        q.vx *= dk; q.vy = q.vy * dk + q.g * dt; q.vz *= dk; q.x += q.vx * dt; q.y += q.vy * dt; q.z += q.vz * dt; q.age += dt; q.acc += dt;
        while (q.acc >= q.step) { q.acc -= q.step; trails.emit(q.x, q.y, q.z, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, q.col, q.tl); }
        if (q.split > 0 && q.age >= q.split) {
          q.split = -1;
          for (let i = 0; i < 4; i++) { const a = i * 1.571 + 0.7; sparks.emit(q.x, q.y, q.z, q.vx * 0.3 + Math.cos(a) * 12, q.vy * 0.3 + Math.sin(a) * 12, q.vz * 0.3 + (i % 2 ? 5 : -5), q.col2, 1.1); }
          q.on = false;
        } else if (q.age > q.life) q.on = false;
      }
      sparks.update(dt); trails.update(dt);
      flashes.forEach((l) => { l.intensity *= Math.exp(-3.4 * dt); });
      smokes.forEach((m) => {
        if (!m.sp.visible) return;
        m.age += dt; const a = m.age, k = Math.min(1, a / 2.4);
        m.kick *= Math.exp(-2.4 * dt);
        m.sp.scale.set(16 + 70 * k, 11 + 48 * k, 1);
        m.sp.material.opacity = Math.min(1, a / 0.5) * 0.3 * (1 - 0.4 * k);
        m.sp.material.color.copy(m.col).multiplyScalar(0.1 + 0.5 * m.kick).addScalar(0.035);
      });
      // mobil
      for (let i = 0; i < NCAR; i++) {
        const c = cars[i]; c.x += c.v * dt; if (c.x > 175) c.x -= 350; if (c.x < -175) c.x += 350;
        for (let k = 0; k < TR; k++) { const j = (i * TR + k) * 3; cp[j] = c.x - c.dir * k * 1.1; cp[j + 1] = DY + 0.85; cp[j + 2] = c.z; }
      }
      carG.attributes.position.needsUpdate = true;
      for (let i = 0; i < nb; i++) { const k = Math.max(0, Math.sin(T * 3.1 + bph[i] * 2)); const v = 0.15 + k * k * k * 1.2; bc[i * 3] = bcol.r * v; bc[i * 3 + 1] = bcol.g * v; bc[i * 3 + 2] = bcol.b * v; }
      beaG.attributes.color.needsUpdate = true;
      beacon.visible = Math.sin(T * 3) > -0.2;
      crowd.update(dt);
      nTex.offset.set(T * 0.01, T * 0.015);
      sf.update(T);
      // kamera setinggi mata di tepi sungai, maju pelan
      const k = easeInOut(t);
      camera.position.set(Math.sin(t * 1.4) * 2.5, 3.2 + 0.6 * k, 30 - 4 * k);
      camera.lookAt(Math.sin(t * 1.4) * -1 + 4, lerp(26, 29, smooth(t)), -85);
    },
    dispose() {
      [tex, smokeTex, winTex, nTex].forEach((x) => x.dispose());
      own.forEach((g) => g.dispose());
      crowd.dispose(); crowdMat.dispose();
      [carMat, mirrorMat, flat].forEach((m) => m.dispose && m.dispose());
    },
  };
}
