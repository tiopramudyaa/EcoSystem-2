// Lebaran: serambi rumah kayu saat senja, keluarga besar bersalaman (cium tangan), berpelukan, nenek menyambut cucu; hidangan opor dan ketupat
// dengan pelita, untaian ketupat anyaman dan janur, masjid dan kembang api jauh di langit senja berhilal.
// Figur berkerangka (satu SkinnedMesh) dari _e-rig.js, panggung dari _e-set.js, masjid dari _r-mosque.js.
import { pal, rng, glowTexture, canvasTex, paintedSky, starField, cloudTexture, moonTexture, phasedMoon, skyDir, particlePool, easeInOut, smooth, seg, lerp } from '../common.js';
import { peopleMaterial } from './_people.js';
import { createPeople } from './_e-rig.js';
import { buildHouse, buildKetupat, pavingTexture } from './_e-set.js';
import { buildMosque } from './_r-mosque.js';
import { buildPalmGeometry } from './_r-palms.js';
import { palmTexture } from './_r-tex.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const low = ctx.quality === 'low';
  const P = pal(THREE, palette);
  const rnd = rng(1446);
  const tex = glowTexture(THREE);
  const K = (h) => new THREE.Color(h);
  const gold = K('#e8b63c'), janur = P.accent.clone(), dkGreen = P.primary.clone();
  const mid = palette.login_mid ? K(palette.login_mid) : P.from.clone().lerp(P.to, 0.5);
  const own = [];
  const cloudTex = cloudTexture(THREE, rnd);

  scene.fog = new THREE.FogExp2(K('#244f38').lerp(P.to, 0.15), 0.012);
  // ---- langit senja: emas di cakrawala, hijau tua di atas ----
  const sky = paintedSky(THREE, rnd, {
    stops: [[0, K('#b8b868')], [0.04, K('#78883f')], [0.12, K('#28664c')], [0.26, P.to.clone().lerp(K('#14604f'), 0.55).multiplyScalar(0.8)], [0.5, mid.clone().lerp(K('#0d4a3c'), 0.4)], [1, P.from.clone().lerp(mid, 0.2)]],
    span: 2.6, upTop: 1.1, below: 0.3,
    paint: (g, m) => {
      const x = m.x(0.28), y = m.y(0.01), r = 300;
      const rg = g.createRadialGradient(x, y, 0, x, y, r); rg.addColorStop(0, 'rgba(236,228,150,0.5)'); rg.addColorStop(0.3, 'rgba(190,180,90,0.22)'); rg.addColorStop(0.65, 'rgba(110,190,120,0.14)'); rg.addColorStop(1, 'rgba(60,150,110,0)');
      g.save(); g.scale(1, 0.5); g.translate(0, y * (1 / 0.5 - 1)); g.fillStyle = rg; g.fillRect(x - r, y - r, r * 2, r * 2); g.restore();
    },
  });
  scene.add(sky);
  const sf = starField(THREE, rnd, low ? 120 : 260, { tex, minEl: 0.42, size: 1.5, color: '#f2f6d0', span: 2.6, upTop: 1.1 });
  scene.add(sf.group);
  const clouds = [];
  for (let i = 0; i < 6; i++) {
    const az = -0.9 + rnd() * 1.8, el = 0.06 + rnd() * 0.45, r = 400 + rnd() * 20, w = r * (0.3 + rnd() * 0.3), nr = Math.max(0, 1 - Math.hypot(az - 0.28, el - 0.03) / 0.7);
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: K('#2c6a54').lerp(K('#d6cc78'), nr), transparent: true, opacity: 0.3 + nr * 0.25, depthWrite: false, fog: false }));
    sp.scale.set(w, w * 0.26, 1); skyDir(THREE, az, el, r, sp.position); scene.add(sp); clouds.push({ sp, az, el, r, v: (rnd() - 0.4) * 0.008 });
  }
  const sunP = skyDir(THREE, 0.28, 0.02, 430);
  const mkSpr = (c, s, o, p) => { const m = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, color: c, transparent: true, opacity: o, depthWrite: false, blending: THREE.AdditiveBlending, fog: false })); m.scale.set(s, s, 1); m.position.copy(p); scene.add(m); return m; };
  mkSpr('#e4dc88', 170, 0.55, sunP); const sunWide = mkSpr('#9cc468', 520, 0.3, sunP);
  // hilal Syawal tipis di langit atas
  const moonTex = moonTexture(THREE, rnd, { base: '#efe3bd' });
  const moon = phasedMoon(THREE, moonTex, { radius: 12, sunDir: new THREE.Vector3(0.35, -0.5, -1.2), tint: '#ffe6a8', earth: 0.012, gain: 2.8 });
  moon.position.copy(skyDir(THREE, -0.42, 0.52, 440)); scene.add(moon);
  mkSpr('#ffd58a', 70, 0.35, moon.position.clone().multiplyScalar(1.01));

  // ---- cahaya ----
  scene.add(new THREE.HemisphereLight(K('#c4e6b4'), K('#2f4a2a'), 0.62));
  const key = new THREE.DirectionalLight(K('#f6f0c6'), 1.7); key.position.set(4, 5, 9); scene.add(key);
  const rim = new THREE.DirectionalLight(K('#9ed88a'), 1.4); rim.position.set(9, 3, -14); scene.add(rim);
  if (!low) { const fill = new THREE.DirectionalLight(K('#8ccbb0'), 0.4); fill.position.set(-8, 2, 6); scene.add(fill); }
  const lampL = new THREE.PointLight(K('#ffb050'), 7, 8, 1.8); scene.add(lampL);
  const porchL = new THREE.PointLight(K('#ffbf66'), 8, 9, 1.8); porchL.position.set(-3, 2.6, -1.2); scene.add(porchL);

  // ---- tanah: paving halaman, rumput jauh ----
  const payT = pavingTexture(THREE, rnd); payT.repeat.set(16, 11); own.push(payT);
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(30, 21), new THREE.MeshStandardMaterial({ map: payT, roughness: 0.9, color: '#9cb88a' }));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, 0, 4.5); scene.add(ground); own.push(ground.geometry, ground.material);
  const grass = new THREE.Mesh(new THREE.PlaneGeometry(500, 400), new THREE.MeshStandardMaterial({ color: dkGreen.clone().lerp(K('#7a7a30'), 0.3).multiplyScalar(0.5), roughness: 1 }));
  grass.rotation.x = -Math.PI / 2; grass.position.set(0, -0.03, -120); scene.add(grass); own.push(grass.geometry, grass.material);

  // ---- rumah, masjid jauh, palem ----
  const house = buildHouse(THREE, rnd, { low }); house.meshes.forEach((m) => scene.add(m));
  const mq = buildMosque(THREE, rnd, { detail: 'far', gold: '#e6c36a', mirror: false, dome: '#7fd09a', stone: '#b4d4a4' });
  mq.group.scale.setScalar(0.42); mq.group.position.set(13, 0, -48); mq.group.rotation.y = -0.25; scene.add(mq.group);
  const palmTex = palmTexture(THREE, rnd); own.push(palmTex);
  const palmG = buildPalmGeometry(THREE, rnd, [{ x: 7.4, y: 0, z: -7, h: 8.5, lean: 0.1, rot: 0.5 }, { x: 10.5, y: 0, z: -11, h: 11, lean: -0.08, rot: 2.2 }, { x: 13.5, y: 0, z: -6, h: 7, lean: 0.12, rot: 4 }, { x: 5.2, y: 0, z: -14, h: 12, lean: 0.06, rot: 1.1 }], { fronds: low ? 9 : 12 }); own.push(palmG);
  const palmMat = new THREE.MeshStandardMaterial({ map: palmTex, vertexColors: true, alphaTest: 0.4, side: THREE.DoubleSide, roughness: 1 }); own.push(palmMat);
  const palms = new THREE.Mesh(palmG, palmMat); palms.frustumCulled = false; scene.add(palms);
  lampL.position.set(house.lamp[0], house.lamp[1] + 0.1, house.lamp[2] + 0.2);

  // ---- ketupat anyaman dan janur ----
  const KS = [];
  for (let i = 0, x = -7.2; x < 5.2; i++, x += 0.78 + (i % 3) * 0.12) KS.push({ x, y: 3.06, z: -2.02, L: 0.3 + ((i * 7) % 5) * 0.16, s: 0.95 + ((i * 3) % 4) * 0.06, ph: i * 0.9, tint: i % 3 === 1 ? '#e6f6a0' : i % 3 === 2 ? '#bfe07a' : '#ffffff' });
  const kt = buildKetupat(THREE, KS, { low }); kt.meshes.forEach((m) => scene.add(m));
  const ribM = new THREE.MeshStandardMaterial({ color: K('#a6c94c'), side: THREE.DoubleSide, roughness: 0.6, emissive: K('#4a6a14'), emissiveIntensity: 0.2 }); own.push(ribM);
  const ribs = [];
  for (let i = 0; i < 7; i++) {
    const g = new THREE.PlaneGeometry(0.17, 1.5 + (i % 3) * 0.35, 1, 10); own.push(g);
    const m = new THREE.Mesh(g, ribM); m.position.set(-6.4 + i * 1.65, 3.05, -1.96); scene.add(m);
    ribs.push({ g, base: new Float32Array(g.attributes.position.array), ph: i * 1.3, h: 1.5 + (i % 3) * 0.35 });
  }
  // untaian lampu kecil di balok
  const NB = 40, bp = new Float32Array(NB * 3), bc = new Float32Array(NB * 3), bph = new Float32Array(NB);
  for (let i = 0; i < NB; i++) { const x = -8.5 + i * (13.4 / (NB - 1)); bp[i * 3] = x; bp[i * 3 + 1] = 3.0 - 0.1 * Math.sin(i / (NB - 1) * Math.PI * 6) ** 2; bp[i * 3 + 2] = -1.9; bph[i] = rnd() * 6.28; }
  const bg = new THREE.BufferGeometry(); bg.setAttribute('position', new THREE.BufferAttribute(bp, 3)); bg.setAttribute('color', new THREE.BufferAttribute(bc, 3)); own.push(bg);
  const bm = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 0.3, sizeAttenuation: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(bm);
  const bulbs = new THREE.Points(bg, bm); bulbs.frustumCulled = false; scene.add(bulbs);
  const warmC = K('#ffc060');
  // kilau hangat pelita dan jendela
  const gl = [house.lamp[0], house.lamp[1], house.lamp[2]];
  const gGeo = new THREE.BufferGeometry(); gGeo.setAttribute('position', new THREE.Float32BufferAttribute(gl.concat([-3.3, 1.8, -5.2, -9.6, 1.8, -5.2, -6.6, 2.0, -5.2, -0.6, 2.0, -5.2, 2.6, 2.0, -5.2]), 3)); own.push(gGeo);
  const gMat = new THREE.PointsMaterial({ map: tex, color: K('#ffb347'), size: 2.8, sizeAttenuation: true, transparent: true, opacity: 0.4, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(gMat);
  const glowP = new THREE.Points(gGeo, gMat); glowP.frustumCulled = false; scene.add(glowP);
  // genangan cahaya hangat di tanah (aditif, instanced)
  const poolG = new THREE.PlaneGeometry(1, 1); poolG.rotateX(-Math.PI / 2); own.push(poolG);
  const poolM = new THREE.MeshBasicMaterial({ map: tex, color: K('#e8b050'), transparent: true, opacity: 0.26, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(poolM);
  const pools = [[house.table[0] + 0.3, house.table[1] + 0.2, 4.6, 3.4], [-3.3, -0.9, 4.2, 2.4], [-9, -0.9, 4, 2.4], [0.8, -0.9, 4, 2.4]];
  const poolI = new THREE.InstancedMesh(poolG, poolM, pools.length);
  const dm = new THREE.Object3D();
  pools.forEach(([x, z, w, d], i) => { dm.position.set(x, 0.36 < 0 ? 0 : 0.02, z); dm.rotation.set(0, 0, 0); dm.scale.set(w, 1, d); dm.updateMatrix(); poolI.setMatrixAt(i, dm.matrix); });
  poolI.frustumCulled = false; scene.add(poolI);

  // ---- keluarga ----
  const mat = peopleMaterial(THREE, { rim: '#ffb860', power: 2.4, strength: 0.32, fill: 0.3, roughness: 0.8 });
  const KOKO = '#efe4c4', GRN = '#2d7a3a', BAT = '#8a5428';
  const sk = ['#b98258', '#a8734a', '#c99468', '#9c6a44', '#d1a27a'];
  const toward = (x) => (x < 0 ? 1 : -1);
  const specs = [
    { x: -2.3, z: 0.15, yaw: 1.12, build: 'man', height: 1.0, elder: true, skin: sk[1], top: KOKO, topStyle: 'koko', lower: 'sarong', bottom: '#7a4a28', motif: true, hat: 'peci', hatColor: '#15110e', hair: '#8c8a86', accent: '#d9b64d' },
    { x: -1.45, z: 0.3, yaw: -1.08, build: 'man', height: 0.98, skin: sk[0], top: GRN, topStyle: 'koko', lower: 'pants', bottom: '#2c2824', hat: 'peci', hatColor: '#15110e', collar: '#e6dcb8' },
    { x: -0.19, z: 0.4, yaw: 1.3, build: 'woman', height: 0.94, skin: sk[2], top: '#2e7a3a', topStyle: 'kebaya', lower: 'batik', bottom: BAT, motif: true, hat: 'hijab', hatColor: '#efe5c4', accent: '#e0b84a' },
    { x: 0.19, z: 0.42, yaw: -1.3, build: 'woman', height: 0.92, skin: sk[3], top: '#f1e8cf', topStyle: 'kebaya', lower: 'batik', bottom: '#7a4422', motif: true, hat: 'hijab', hatColor: '#b9d04c', accent: '#c9a04a' },
    { x: 1.8, z: 0.25, yaw: -1.12, build: 'woman', height: 0.9, elder: true, skin: sk[1], top: '#ebe7dc', topStyle: 'kebaya', lower: 'batik', bottom: '#6a4a2e', motif: true, hat: 'hijab', hatColor: '#e9e3d2', accent: '#cfc9b0' },
    { x: 1.0, z: 0.4, yaw: 1.1, build: 'child', height: 1.12, skin: sk[4], top: '#cfe36a', topStyle: 'kebaya', lower: 'skirt', bottom: '#2f7d3a', hat: 'hijab', hatColor: '#f1e8cf', accent: '#e0b84a' },
    { x: -1.05, z: -1.45, yaw: 0.2, build: 'man', height: 1.03, skin: sk[3], top: '#1f6130', topStyle: 'koko', lower: 'sarong', bottom: '#5a1f1f', hat: 'peci', hatColor: '#15110e', accent: '#d9b64d' },
    { x: 0.55, z: -0.7, yaw: -0.2, build: 'child', height: 1.04, skin: sk[0], top: '#eae4c4', topStyle: 'koko', lower: 'pants', bottom: '#2a2724', hat: 'peci', hatColor: '#15110e' },
    { x: 2.85, z: -2.45, yaw: -0.12, build: 'woman', height: 0.95, skin: sk[2], top: '#1e6a2e', topStyle: 'kebaya', lower: 'batik', bottom: '#7a4a22', motif: true, hat: 'hijab', hatColor: '#d9ab3a', accent: '#e0b84a', tray: ['#cfe05a', '#a9c64c', '#e0a84a', '#f1e3c0'] },
  ];
  const rig = createPeople(THREE, specs, { material: mat, low });
  scene.add(rig.group);
  const [elder, young, w1, w2, nenek, girl, dad, boy, mom] = rig.people;
  void toward;
  // pose dasar
  young.tilt = 0.62; young.hPitch = 0.5; young.hYaw = -0.15; young.sway = 0.6;
  elder.tilt = 0.03; elder.hYaw = 0.2; elder.hPitch = 0.12; elder.nod = 0.03;
  w1.tilt = 0.13; w1.hYaw = -0.62; w1.hRoll = -0.14; w2.tilt = 0.13; w2.hYaw = 0.62; w2.hRoll = 0.14; w1.sway = w2.sway = 0.5;
  nenek.tilt = 0.14; nenek.hYaw = -0.1; nenek.hPitch = 0.3; nenek.nod = 0.025; nenek.lean = 0.02;
  girl.tilt = 0.5; girl.hPitch = 0.45; girl.hYaw = 0.1;
  dad.hYaw = -0.2; dad.hRoll = 0.05; boy.hYaw = 0.3; boy.hPitch = -0.05; mom.hYaw = -0.1; mom.sway = 0.4; mom.tilt = -0.03;
  [elder, young, w1, w2, nenek, girl].forEach((p) => { p.arm[1].pole.set(0.7, -1, -0.1); p.arm[-1].pole.set(-0.7, -1, -0.1); });
  const C1 = new THREE.Vector3(), C2 = new THREE.Vector3(), tv = new THREE.Vector3(), tv2 = new THREE.Vector3();
  const back = (P, side, lx, ly) => { const A = P.arm[side]; A.on = true; return A.tw; };
  void back;
  function poses(T) {
    // salaman bapak - anak muda: bapak mengulurkan tangan kanan, anak muda menyambut dengan kedua tangan (cium tangan)
    elder.arm[-1].tl.set(-0.15, 0.1 + 0.012 * Math.sin(T * 3), 0.43); elder.arm[-1].pole.set(-0.6, -1, 0);
    elder.arm[1].tl.set(0.2, 0.0, 0.2);
    tv.copy(elder.arm[-1].tl); elder.root.localToWorld(tv); C1.copy(tv);
    young.arm[-1].on = young.arm[1].on = true;
    young.arm[-1].tw.copy(C1).add(tv2.set(0, 0.015 * Math.sin(T * 3), 0));
    young.arm[1].tw.copy(C1).add(tv2.set(0, 0.035, 0.03));
    young.arm[-1].pole.set(-0.8, -0.6, -0.2); young.arm[1].pole.set(0.8, -0.6, -0.2);
    // pelukan: tangan masing-masing ke punggung lawan
    for (const [a, b] of [[w1, w2], [w2, w1]]) {
      const ch = b.bones.chest;
      a.arm[1].on = a.arm[-1].on = true;
      a.arm[1].tw.set(0.08, 0.06, -0.15); ch.localToWorld(a.arm[1].tw);
      a.arm[-1].tw.set(-0.1, -0.06, -0.15); ch.localToWorld(a.arm[-1].tw);
      a.arm[1].pole.set(0.6, -0.3, 0.8); a.arm[-1].pole.set(-0.6, -0.3, 0.8);
    }
    // nenek menyambut cucu: tangan kanan terulur, kiri mengusap kepala
    nenek.arm[-1].tl.set(-0.15, 0.06, 0.4 + 0.01 * Math.sin(T * 2.6)); nenek.arm[-1].pole.set(-0.6, -1, 0);
    tv.copy(nenek.arm[-1].tl); nenek.root.localToWorld(tv); C2.copy(tv);
    girl.arm[-1].on = girl.arm[1].on = true;
    girl.arm[-1].tw.copy(C2); girl.arm[1].tw.copy(C2).add(tv2.set(0, 0.03, 0.02));
    girl.arm[-1].pole.set(-0.8, -0.6, -0.2); girl.arm[1].pole.set(0.8, -0.6, -0.2);
    nenek.arm[1].on = true; tv2.set(0.02, 0.16, 0.0); girl.bones.head.localToWorld(tv2); nenek.arm[1].tw.copy(tv2).add(tv.set(0, 0.012 * Math.sin(T * 2.4), 0)); nenek.arm[1].pole.set(0.7, -0.4, -0.3);
    // ayah melambai, anak dan ibu menyajikan
    dad.arm[-1].tl.set(-0.3, 0.66 + 0.035 * Math.sin(T * 6), 0.18); dad.arm[-1].pole.set(-0.7, -1, 0.2);
    dad.arm[-1].hand.set(0, 0, 0.2 * Math.sin(T * 6));
    boy.arm[1].tl.set(0.06, 0.02, 0.24); boy.arm[-1].tl.set(-0.06, 0.02, 0.24); boy.arm[1].pole.set(0.8, -0.8, -0.1); boy.arm[-1].pole.set(-0.8, -0.8, -0.1);
    mom.arm[1].tl.set(0.23, 0.07, 0.33); mom.arm[-1].tl.set(-0.23, 0.07, 0.33); mom.arm[1].pole.set(0.9, -0.5, -0.3); mom.arm[-1].pole.set(-0.9, -0.5, -0.3);
  }

  // bayangan kontak di bawah tiap orang (satu draw call)
  const shT = canvasTex(THREE, 64, 64, (g, w, h) => { const r = g.createRadialGradient(32, 32, 2, 32, 32, 32); r.addColorStop(0, 'rgba(0,0,0,0.75)'); r.addColorStop(0.6, 'rgba(0,0,0,0.3)'); r.addColorStop(1, 'rgba(0,0,0,0)'); g.fillStyle = r; g.fillRect(0, 0, w, h); });
  const shG = new THREE.PlaneGeometry(1, 1); shG.rotateX(-Math.PI / 2);
  const shM = new THREE.MeshBasicMaterial({ map: shT, transparent: true, depthWrite: false, fog: false, opacity: 0.8 });
  own.push(shT, shG, shM);
  const shI = new THREE.InstancedMesh(shG, shM, specs.length + 1);
  specs.forEach((s, i) => { const k = s.height * (s.build === 'child' ? 0.64 : 1); dm.position.set(s.x, 0.025, s.z); dm.rotation.set(0, 0, 0); dm.scale.set(1.5 * k + 0.3, 1, 1.2 * k + 0.3); dm.updateMatrix(); shI.setMatrixAt(i, dm.matrix); });
  dm.position.set(house.table[0], 0.025, house.table[1]); dm.scale.set(3, 1, 1.6); dm.updateMatrix(); shI.setMatrixAt(specs.length, dm.matrix);
  shI.frustumCulled = false; scene.add(shI);

  // kembang api jauh
  const fw = particlePool(THREE, low ? 500 : 900, { tex, size: 1.6, gravity: -3.5, drag: 0.7 }); scene.add(fw.points); fw.points.geometry.attributes.position.array.fill(-1e5); // partikel mati diparkir di luar layar
  const fwCols = [K('#ffd36a'), K('#ff8a5a'), K('#b7ff8a'), K('#ffe9b0')];
  let nextFw = 0, fwIdx = 0;
  // kilau emas melayang
  const sp = particlePool(THREE, low ? 200 : 420, { tex, size: 0.09, gravity: 0.15, drag: 0.5 }); scene.add(sp.points); sp.points.geometry.attributes.position.array.fill(-1e5);
  const gc = gold.clone().multiplyScalar(1.3);

  return {
    update(t, dt) {
      const T = t * duration / 1000, e = easeInOut(t), ramp = smooth(seg(t, 0, 0.35));
      poses(T);
      rig.update(T);
      kt.update(T);
      ribs.forEach((r) => {
        const p = r.g.attributes.position, hh = r.h;
        for (let i = 0; i < p.count; i++) {
          const y0 = r.base[i * 3 + 1], u = (hh / 2 - y0) / hh;
          p.setX(i, r.base[i * 3] + Math.sin(T * 2.4 - u * 3 + r.ph) * 0.1 * u); p.setZ(i, Math.sin(T * 3 - u * 4 + r.ph) * 0.08 * u);
        }
        p.needsUpdate = true;
      });
      for (let i = 0; i < NB; i++) { const k = (0.55 + 0.45 * Math.sin(T * 3.4 + bph[i])) * ramp; bc[i * 3] = warmC.r * k; bc[i * 3 + 1] = warmC.g * k; bc[i * 3 + 2] = warmC.b * k; }
      bg.attributes.color.needsUpdate = true;
      lampL.intensity = 7 * ramp * (0.92 + 0.08 * Math.sin(T * 7)); porchL.intensity = 8 * ramp * (0.96 + 0.04 * Math.sin(T * 5 + 1));
      gMat.opacity = 0.25 + 0.2 * ramp + 0.04 * Math.sin(T * 5);
      house.glass.color.setScalar(0.4 + 0.6 * ramp);
      clouds.forEach((c) => { c.az += c.v * dt; skyDir(THREE, c.az, c.el, c.r, c.sp.position); });
      sunWide.material.opacity = 0.28 + 0.03 * Math.sin(T * 0.8);
      sf.update(T);
      if (dt > 0) {
        if (T >= nextFw) { nextFw = T + 0.55 + rnd() * 0.6; const c = fwCols[fwIdx++ % fwCols.length]; fw.burst(6 + rnd() * 34, 11 + rnd() * 10, -70 - rnd() * 20, c.clone().multiplyScalar(1.6), low ? 40 : 70, 9, 1.7, rnd); }
        for (let i = 0; i < 2; i++) sp.emit((rnd() - 0.5) * 9, 0.3 + rnd() * 2.6, -1 + rnd() * 4, (rnd() - 0.5) * 0.15, 0.1 + rnd() * 0.25, 0, gc, 2 + rnd() * 1.6);
      }
      fw.update(dt); sp.update(dt);
      const pf = Math.min(1, Math.max(0, (1 - camera.aspect) / 0.5));
      camera.position.set(lerp(-0.5, 0.35, e) + Math.sin(T * 0.6) * 0.04 - pf * 0.4, lerp(1.38, 1.3, e) + pf * 0.15, lerp(4.6, 4.0, e) + pf * 1.5);
      camera.lookAt(lerp(-0.2, 0.0, e) - pf * 0.4, 1.12 - pf * 0.05, 0);
    },
    dispose() {
      [tex, cloudTex, moonTex].forEach((x) => x.dispose());
      own.forEach((o) => o.dispose()); house.dispose(); mq.dispose(); kt.dispose(); rig.dispose(); mat.dispose();
      shI.dispose(); poolI.dispose();
    },
  };
}
