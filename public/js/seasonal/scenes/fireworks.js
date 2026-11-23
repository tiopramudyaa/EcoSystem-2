// Tahun Baru: perayaan tengah malam di tepi sungai kota. Siluet gedung dengan jendela menyala dan pantulan di air,
// jembatan dengan jejak lampu mobil, kembang api bertahap (peony, krisan, willow, palem, cincin, hati, crossette)
// yang digerakkan GPU (goresan berekor, kilau, asap yang disinari), kilatan ke gedung, penonton siluet dengan
// tangan terangkat, confetti emas, dan bola hitung mundur di puncak menara. Semua geometri prosedural.
import { createCrowd } from './_f-crowd.js';
import { createSparks } from './_f-sparks.js';
import { createShots } from './_f-shots.js';
import { createConfetti } from './_f-confetti.js';
import { pal, rng, glowTexture, canvasTex, paintedSky, starField, mergeParts, easeInOut, smooth, lerp, clamp, seg } from '../common.js';

const WATER = -2.2;

export function create(THREE, ctx) {
  const { scene, camera, renderer, palette, duration } = ctx;
  const low = ctx.quality === 'low';
  const P = pal(THREE, palette);
  const rnd = rng(2026);
  const tex = glowTexture(THREE);
  const S = duration / 2800;
  const gold = P.accent.clone().multiplyScalar(1.8), champ = new THREE.Color('#f6e2b8').multiplyScalar(1.6), white = new THREE.Color('#fff6e4').multiplyScalar(1.7);
  const rose = new THREE.Color('#ff9f80').multiplyScalar(1.5), ice = new THREE.Color('#c4d8ff').multiplyScalar(1.6), amber = new THREE.Color('#ffb347').multiplyScalar(1.6);
  camera.fov = 60; camera.updateProjectionMatrix();

  scene.fog = new THREE.FogExp2(new THREE.Color('#15131a'), 0.0042);
  scene.add(paintedSky(THREE, rnd, { stops: [[0, new THREE.Color('#3a3036').lerp(P.accent, 0.08)], [0.14, new THREE.Color('#1b1a24')], [0.5, new THREE.Color('#0a0a12')], [1, new THREE.Color('#040409')]], span: 2.6 }));
  const sf = starField(THREE, rnd, low ? 240 : 420, { tex, span: 2.6, minEl: 0.12, size: 1.5, color: '#e8eeff' });
  scene.add(sf.group);

  scene.add(new THREE.HemisphereLight(new THREE.Color('#4a5a8c'), new THREE.Color('#1a1418'), 0.26));
  const rim = new THREE.DirectionalLight(new THREE.Color('#d8b878'), 0.22); rim.position.set(-10, 25, -60); scene.add(rim);
  const flashes = [0, 1].map(() => { const l = new THREE.PointLight('#ffffff', 0, 260, 1.3); scene.add(l); return l; });
  let fi = 0;
  const own = []; // geometri yang kita buat sendiri
  const ownMat = [];
  const tm = (x, y, z) => new THREE.Matrix4().makeTranslation(x, y, z);

  // ---- tekstur jendela: jarang, hangat, per lantai ----
  const winTex = canvasTex(THREE, 128, 256, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    const cs = ['#ffd48a', '#ffe6b0', '#fff0cf', '#ffc070', '#cfe4ff'];
    for (let y = 0; y < 16; y++) {
      const floorOn = rnd() < 0.55;
      for (let x = 0; x < 8; x++) {
        if (rnd() < (floorOn ? 0.26 : 0.04)) { g.fillStyle = cs[(rnd() * cs.length) | 0]; g.globalAlpha = 0.5 + rnd() * 0.5; g.fillRect(x * 16 + 4, y * 16 + 5, 8, 7); }
      }
    }
    g.globalAlpha = 1;
  });
  winTex.wrapS = winTex.wrapT = THREE.RepeatWrapping;

  const mirror = new THREE.Group(); mirror.scale.y = -1; mirror.position.y = 2 * WATER; scene.add(mirror);
  const mirrorMat = (c) => new THREE.MeshBasicMaterial({ color: c, map: winTex });
  const flat = new THREE.MeshBasicMaterial({ color: '#06070b' });

  // ---- kota: dua baris gedung, jendela berbeda tiap gedung ----
  const beacons = [];
  function city(r, { z0, zj, hMul, xMin, xMax }) {
    const parts = [], col = new THREE.Color();
    for (let x = xMin; x < xMax;) {
      const w = 4 + r() * 7, d = 4 + r() * 6, prof = 0.3 + 0.7 * Math.exp(-Math.pow((x + 5) / 75, 2));
      const h = hMul * (9 + 55 * prof * (0.3 + 0.9 * r() * Math.sqrt(r()))), z = z0 - r() * zj;
      col.setHSL(0.62, 0.22, 0.04 + 0.04 * r());
      const su = Math.max(1, Math.round((w + d) / 14.4)), sv = Math.max(1, Math.round(h / 14));
      const ou = Math.floor(r() * 8) / 8, ov = Math.floor(r() * 16) / 16;
      const box = (bw, bh, bd, y, s) => {
        const g = new THREE.BoxGeometry(bw, bh, bd), uv = g.attributes.uv;
        for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i) + ou, uv.getY(i) + ov);
        parts.push({ geo: g, m: tm(x, WATER + y + bh / 2, z), color: col.clone().multiplyScalar(0.9 + r() * 0.2), uvScale: s });
      };
      box(w, h, d, 0, [su, sv]);
      let top = h;
      if (r() < 0.5) { const h2 = h * (0.15 + 0.15 * r()); box(w * 0.66, h2, d * 0.66, h, [Math.max(1, su - 1), 1]); top += h2; }
      if (r() < 0.2) { const h3 = 3 + r() * 5; box(w * 0.36, h3, d * 0.36, top, [1, 1]); top += h3; }
      if (r() < 0.12) { const hp = 6 + r() * 6, g = new THREE.CylinderGeometry(0.1, w * 0.55, hp, 4); g.rotateY(Math.PI / 4); parts.push({ geo: g, m: tm(x, WATER + top + hp / 2, z), color: col, uvScale: [0, 0] }); top += hp; }
      else if (r() < 0.3) { const ha = 5 + r() * 6; parts.push({ geo: new THREE.BoxGeometry(0.25, ha, 0.25), m: tm(x + (r() - 0.5) * w * 0.3, WATER + top + ha / 2, z), color: '#222', uvScale: [0, 0] }); top += ha; }
      if (top > 34 || r() < 0.2) beacons.push(x, WATER + top + 0.6, z);
      x += w * (0.7 + 0.35 * r());
    }
    const geo = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose());
    const n = geo.attributes.normal, uv = geo.attributes.uv; // atap/dasar tidak bermotif jendela
    for (let i = 0; i < n.count; i++) if (Math.abs(n.getY(i)) > 0.5) uv.setXY(i, 0, 0);
    own.push(geo);
    return geo;
  }
  const cityMat = (e) => { const m = new THREE.MeshStandardMaterial({ vertexColors: true, emissiveMap: winTex, emissive: new THREE.Color('#ffffff'), emissiveIntensity: e, roughness: 0.85, metalness: 0.1 }); ownMat.push(m); return m; };
  const gF = city(rng(31), { z0: -88, zj: 10, hMul: 0.8, xMin: -175, xMax: 175 });
  const gB = city(rng(77), { z0: -122, zj: 10, hMul: 1.3, xMin: -190, xMax: 190 });
  scene.add(new THREE.Mesh(gF, cityMat(0.85)), new THREE.Mesh(gB, cityMat(0.5)));
  mirror.add(new THREE.Mesh(gF, mirrorMat('#b8b8c6')));

  // ---- menara landmark: poros ramping, dek pengamat bercincin emas, antena; bola hitung mundur meluncur turun ----
  const twr = [];
  twr.push({ geo: new THREE.CylinderGeometry(0.45, 1.5, 70, 10), m: tm(0, 35, 0), color: '#4f4960' });
  for (let i = 0; i < 3; i++) {
    const a = i * 2.094 + 0.4, leg = new THREE.CylinderGeometry(0.3, 0.7, 42, 6);
    const m = tm(Math.cos(a) * 4.4, 21, Math.sin(a) * 4.4).multiply(new THREE.Matrix4().makeRotationAxis(new THREE.Vector3(-Math.sin(a), 0, Math.cos(a)), -0.19));
    twr.push({ geo: leg, m, color: '#4f4960' });
  }
  const deck = new THREE.SphereGeometry(1, 28, 14); deck.scale(4.8, 1.5, 4.8);
  twr.push({ geo: deck, m: tm(0, 52, 0), color: '#3b3547' });
  twr.push({ geo: new THREE.CylinderGeometry(0.1, 0.4, 36, 6), m: tm(0, 88, 0), color: '#555' });
  const tg = mergeParts(THREE, twr); twr.forEach((p) => p.geo.dispose()); own.push(tg);
  const TX = 34, TZ = -102;
  const tower = new THREE.Group(); tower.position.set(TX, WATER, TZ); scene.add(tower);
  const towerMat = new THREE.MeshStandardMaterial({ vertexColors: true, emissive: new THREE.Color('#4a4256'), emissiveIntensity: 0.7, roughness: 0.5, metalness: 0.6 }); ownMat.push(towerMat);
  tower.add(new THREE.Mesh(tg, towerMat));
  const ringG = new THREE.TorusGeometry(1, 0.06, 6, 40), ringM = new THREE.MeshBasicMaterial({ color: gold.clone().multiplyScalar(0.8) }); own.push(ringG); ownMat.push(ringM);
  [[52.2, 4.95], [51.0, 4.6]].forEach(([y, r]) => { const rg = new THREE.Mesh(ringG, ringM); rg.rotation.x = Math.PI / 2; rg.scale.setScalar(r); rg.position.y = y; tower.add(rg); });
  const ballG = new THREE.IcosahedronGeometry(1.5, 2), ballM = new THREE.MeshBasicMaterial({ color: new THREE.Color('#ffe9b0').multiplyScalar(1.5) }); own.push(ballG); ownMat.push(ballM);
  const ball = new THREE.Mesh(ballG, ballM); tower.add(ball);
  const ballGlow = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, color: '#ffd27a', transparent: true, opacity: 0.8, depthWrite: false, blending: THREE.AdditiveBlending, fog: false })); ballGlow.scale.setScalar(16); tower.add(ballGlow); ownMat.push(ballGlow.material);
  const beacon = new THREE.Mesh(new THREE.SphereGeometry(0.5, 8, 6), new THREE.MeshBasicMaterial({ color: '#ff3b30' })); beacon.position.y = 107; tower.add(beacon); own.push(beacon.geometry); ownMat.push(beacon.material);
  const tMir = new THREE.Group(); tMir.position.set(TX, WATER, TZ); tMir.add(new THREE.Mesh(tg, flat)); mirror.add(tMir);

  // ---- jembatan: dek, tiang, kabel, lampu tepi ----
  const BZ = -62, DY = 2.6;
  const bd = [], bg = [];
  bd.push({ geo: new THREE.BoxGeometry(360, 0.7, 6.4), m: tm(0, DY, BZ), color: '#15161e' });
  for (let x = -153; x <= 153; x += 34) bd.push({ geo: new THREE.BoxGeometry(2.2, DY - WATER, 3.2), m: tm(x, (DY + WATER) / 2, BZ), color: '#12131a' });
  [-45, 55].forEach((px) => bd.push({ geo: new THREE.BoxGeometry(1.7, 34, 1.7), m: tm(px, DY + 17, BZ), color: '#1b1c26' }));
  [-1, 1].forEach((s) => bg.push({ geo: new THREE.BoxGeometry(360, 0.14, 0.14), m: tm(0, DY + 0.7, BZ + s * 3), color: P.accent.clone().multiplyScalar(1.1) }));
  const bdG = mergeParts(THREE, bd), bgG = mergeParts(THREE, bg); bd.forEach((p) => p.geo.dispose()); bg.forEach((p) => p.geo.dispose()); own.push(bdG, bgG);
  const bridgeDark = new THREE.Mesh(bdG, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.7, metalness: 0.3 })); ownMat.push(bridgeDark.material);
  const bridgeGlow = new THREE.Mesh(bgG, new THREE.MeshBasicMaterial({ vertexColors: true })); ownMat.push(bridgeGlow.material);
  scene.add(bridgeDark, bridgeGlow);
  const bgMir = new THREE.MeshBasicMaterial({ color: P.accent.clone().multiplyScalar(0.45) }); ownMat.push(bgMir);
  mirror.add(new THREE.Mesh(bdG, flat), new THREE.Mesh(bgG, bgMir));
  const cab = [];
  [-45, 55].forEach((px) => [-1, 1].forEach((s) => { for (let i = 0; i < 5; i++) cab.push(px, DY + 33 - i * 3.4, BZ, px + s * (8 + i * 10), DY + 0.4, BZ); }));
  const cabG = new THREE.BufferGeometry(); cabG.setAttribute('position', new THREE.Float32BufferAttribute(cab, 3)); own.push(cabG);
  const cabM = new THREE.LineBasicMaterial({ color: P.accent.clone().multiplyScalar(0.6), transparent: true, opacity: 0.26 }); ownMat.push(cabM);
  scene.add(new THREE.LineSegments(cabG, cabM));

  // mobil: kepala lampu putih dan lampu belakang merah, ekor memudar
  const NCAR = low ? 14 : 26, TR = 5, cp = new Float32Array(NCAR * TR * 3), cc = new Float32Array(NCAR * TR * 3);
  const cars = [];
  for (let i = 0; i < NCAR; i++) {
    const dir = i % 2 ? 1 : -1, c = dir > 0 ? new THREE.Color('#ffeccb') : new THREE.Color('#ff3a2a');
    cars.push({ x: (rnd() - 0.5) * 340, v: dir * (16 + rnd() * 14), z: BZ + dir * 1.6 + (rnd() - 0.5) * 0.6, dir });
    for (let k = 0; k < TR; k++) { const f = Math.pow(1 - k / TR, 2) * (dir > 0 ? 1.3 : 1.0); cc[(i * TR + k) * 3] = c.r * f; cc[(i * TR + k) * 3 + 1] = c.g * f; cc[(i * TR + k) * 3 + 2] = c.b * f; }
  }
  const carG = new THREE.BufferGeometry(); carG.setAttribute('position', new THREE.BufferAttribute(cp, 3)); carG.setAttribute('color', new THREE.BufferAttribute(cc, 3)); own.push(carG);
  const carMat = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 4.2, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending }); ownMat.push(carMat);
  const carPts = new THREE.Points(carG, carMat); carPts.frustumCulled = false; scene.add(carPts);
  const carRefM = carMat.clone(); carRefM.opacity = 0.35; ownMat.push(carRefM);
  const carRef = new THREE.Points(carG, carRefM); carRef.scale.y = -1; carRef.position.y = 2 * WATER; carRef.frustumCulled = false; scene.add(carRef);

  // lampu merah puncak gedung berkedip
  const nb = beacons.length / 3, bc = new Float32Array(nb * 3), bph = new Float32Array(nb);
  const bcol = new THREE.Color('#ff3b30');
  for (let i = 0; i < nb; i++) bph[i] = rnd() * 6.28;
  const beaG = new THREE.BufferGeometry(); beaG.setAttribute('position', new THREE.Float32BufferAttribute(beacons, 3)); beaG.setAttribute('color', new THREE.BufferAttribute(bc, 3)); own.push(beaG);
  const beaM = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 5, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); ownMat.push(beaM);
  const beaPts = new THREE.Points(beaG, beaM); beaPts.frustumCulled = false; scene.add(beaPts);

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
  const waterM = new THREE.MeshStandardMaterial({ color: '#070910', roughness: 0.1, metalness: 0.7, normalMap: nTex, normalScale: new THREE.Vector2(0.5, 0.5), transparent: true, opacity: 0.8 }); ownMat.push(waterM);
  const water = new THREE.Mesh(new THREE.PlaneGeometry(520, 190), waterM); own.push(water.geometry);
  water.rotation.x = -Math.PI / 2; water.position.set(0, WATER, -42); scene.add(water);

  // ---- tepi sungai: promenade, pagar, lampu jalan (digabung) ----
  const quayM = new THREE.MeshStandardMaterial({ color: '#0b0b11', roughness: 0.8, metalness: 0.1, transparent: true }); ownMat.push(quayM);
  const quay = new THREE.Mesh(new THREE.BoxGeometry(160, 2.2, 60), quayM); own.push(quay.geometry);
  quay.renderOrder = 10; quay.position.set(0, -1.1, 42); scene.add(quay);
  const lampParts = [], bulbParts = [], lg = [];
  lampParts.push({ geo: new THREE.BoxGeometry(160, 0.1, 0.1), m: tm(0, 1.05, 12.5), color: '#0a0a10' });
  for (let i = 0; i < 7; i++) {
    const x = -36 + i * 12;
    lampParts.push({ geo: new THREE.CylinderGeometry(0.07, 0.09, 3.4, 6), m: tm(x, 1.7, 12.5), color: '#0a0a10' });
    bulbParts.push({ geo: new THREE.SphereGeometry(0.2, 10, 8), m: tm(x, 3.5, 12.5), color: '#ffe4b8' }); lg.push(x, 3.5, 12.5);
  }
  const lampG = mergeParts(THREE, lampParts), bulbG = mergeParts(THREE, bulbParts); lampParts.forEach((p) => p.geo.dispose()); bulbParts.forEach((p) => p.geo.dispose()); own.push(lampG, bulbG);
  const lampM = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.6, metalness: 0.5 }), bulbM = new THREE.MeshBasicMaterial({ vertexColors: true }); ownMat.push(lampM, bulbM);
  scene.add(new THREE.Mesh(lampG, lampM), new THREE.Mesh(bulbG, bulbM));
  const lgG = new THREE.BufferGeometry(); lgG.setAttribute('position', new THREE.Float32BufferAttribute(lg, 3)); own.push(lgG);
  const lgM = new THREE.PointsMaterial({ map: tex, color: '#ffc27a', size: 26, transparent: true, opacity: 0.45, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); ownMat.push(lgM);
  const lgP = new THREE.Points(lgG, lgM); lgP.frustumCulled = false; scene.add(lgP);

  // ---- penonton: siluet berpose, tangan terangkat, cahaya tepi mengikuti kilatan ----
  const specs = [], PEO = low ? 34 : 58, pp = ['up', 'up', 'wave', 'phone', 'stand', 'wave'];
  for (let i = 0; i < PEO; i++) {
    const row = i % 3, kid = rnd() < 0.1, s = (kid ? 0.6 : 0.88 + rnd() * 0.14);
    specs.push({ x: -29 + ((i / 3) | 0) * (58 / (PEO / 3)) + (rnd() - 0.5) * 1.0 + row * 0.6, z: 13.4 + row * 1.5 + rnd() * 0.5, yaw: (rnd() - 0.5) * 0.5, s, pose: pp[(rnd() * pp.length) | 0], ph: rnd() * 6.28, hop: kid ? 1.4 : 0.6 + rnd() * 0.8 });
  }
  const crowd = createCrowd(THREE, specs, { y: 0.02, rim: '#ffd9a0' });
  crowd.group.traverse((o) => { o.renderOrder = 11; });
  scene.add(crowd.group);

  // ---- kembang api GPU ----
  const sparks = createSparks(THREE, { capacity: low ? 2400 : 4300, water: WATER });
  scene.add(sparks.mesh, sparks.mirror);
  const shots = createShots(THREE, sparks, rnd, low ? 0.55 : 1);
  const confetti = createConfetti(THREE, rnd, { count: low ? 110 : 240, colors: ['#f3dc8e', '#fff3d6', '#e9c46a', '#d8d8e8', '#ffb59a'].map((c) => new THREE.Color(c).multiplyScalar(0.95)) });
  scene.add(confetti.mesh);

  const SEQ = [
    [0.2, 'peony', -38, 62, -104, gold, white], [0.55, 'willow', 14, 76, -112, gold, gold], [0.85, 'ring', -8, 64, -96, champ, white],
    [1.1, 'chrys', 42, 58, -100, rose, gold], [1.35, 'cross', -52, 58, -105, white, amber], [1.6, 'palm', 4, 70, -108, gold, rose],
    [1.85, 'peony', 26, 68, -115, ice, white], [2.0, 'willow', -24, 74, -110, champ, gold], [2.12, 'ring', 32, 64, -102, gold, white],
    [2.22, 'chrys', -46, 64, -100, rose, champ], [2.3, 'palm', 48, 70, -108, gold, white], [2.38, 'peony', 0, 82, -118, white, gold], [2.46, 'ring', -16, 58, -98, rose, champ],
  ].map(([t, kind, x, y, z, c1, c2], i) => ({ t: (t * 0.9 + 0.38) * S, kind, x, y, z, c1, c2, heart: i === 12, fired: false, rise: (i > 6 ? 0.34 : 0.5) * S, launched: false }));

  const v2 = new THREE.Vector2();
  return {
    update(t, dt) {
      const T = t * duration / 1000;
      for (const b of SEQ) {
        if (!b.launched && T >= b.t - b.rise) { b.launched = true; shots.launch(b); sparks.flush(); }
        if (!b.fired && T >= b.t) {
          b.fired = true;
          const l = flashes[fi++ % 2]; l.color.copy(b.c1).lerp(b.c2, 0.3); l.position.set(b.x * 0.7, b.y * 0.6, b.z + 45); l.intensity = 420;
        }
      }
      sparks.setTime(T);
      if (renderer && renderer.getDrawingBufferSize) { renderer.getDrawingBufferSize(v2); sparks.setSize(v2.x, v2.y); sparks.mesh.material.uniforms.uPx.value = Math.max(0.6, v2.y / 720); }
      flashes.forEach((l) => { l.intensity *= Math.exp(-3.4 * dt); });
      // mobil
      for (let i = 0; i < NCAR; i++) {
        const c = cars[i]; c.x += c.v * dt; if (c.x > 175) c.x -= 350; if (c.x < -175) c.x += 350;
        for (let k = 0; k < TR; k++) { const j = (i * TR + k) * 3; cp[j] = c.x - c.dir * k * 1.1; cp[j + 1] = DY + 0.85; cp[j + 2] = c.z; }
      }
      carG.attributes.position.needsUpdate = true;
      for (let i = 0; i < nb; i++) { const k = Math.max(0, Math.sin(T * 3.1 + bph[i] * 2)); const v = 0.15 + k * k * k * 1.2; bc[i * 3] = bcol.r * v; bc[i * 3 + 1] = bcol.g * v; bc[i * 3 + 2] = bcol.b * v; }
      beaG.attributes.color.needsUpdate = true;
      beacon.visible = Math.sin(T * 3) > -0.2;
      // bola hitung mundur turun sepanjang antena, berdenyut
      const bk = smooth(seg(t, 0.05, 0.9));
      ball.position.y = lerp(104, 74, bk); ballGlow.position.y = ball.position.y;
      const pulse = 1 + Math.sin(T * 7) * 0.06 + (t > 0.9 ? (1 - seg(t, 0.9, 1)) * 0.6 : 0);
      ball.scale.setScalar(pulse); ballGlow.scale.setScalar(15 * pulse);
      const fl = (flashes[0].intensity + flashes[1].intensity) / 420;
      crowd.update(T, clamp(fl, 0, 1));
      confetti.set(T, smooth(seg(t, 0.3, 0.95)));
      nTex.offset.set(T * 0.01, T * 0.015);
      sf.update(T);
      // kamera setinggi mata di tepi sungai, maju pelan
      const k = easeInOut(t);
      camera.position.set(Math.sin(t * 1.4) * 2.5, 3.2 + 0.6 * k, 30 - 4 * k);
      camera.lookAt(Math.sin(t * 1.4) * -1 + 4, lerp(26, 29, smooth(t)), -85);
    },
    dispose() {
      [tex, winTex, nTex].forEach((x) => x.dispose());
      own.forEach((g) => g.dispose());
      ownMat.forEach((m) => m.dispose());
      flat.dispose(); crowd.dispose(); sparks.dispose(); confetti.dispose();

    },
  };
}
