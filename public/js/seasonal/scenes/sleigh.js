// Natal: malam musim dingin. Bulan bertekstur, langit berbintang, desa bersalju, hutan pinus, kabut,
// lalu kereta luncur ditarik 4 rusa terbang melintas. Hanya geometri prosedural.
import { pal, rng, glowTexture, glowSprite, particlePool, easeInOut, lerp, paintedSky, starField, moonTexture, phasedMoon, godRays, cloudTexture, mergeParts, skyDir } from '../common.js';
import { buildReindeer } from './_n-reindeer.js';
import { buildSleigh, buildHarness } from './_n-sleigh.js';
import { createAurora, createFrost } from './_n-sky.js';

const hillH = (x, k) => (Math.sin(x * 0.045 + k * 2.1) * 5 + Math.sin(x * 0.11 + k) * 2.2 + Math.sin(x * 0.021 + k * 5) * 6) * (1 + k * 0.25);
const ZS = [-24, -46, -78];
// tinggi permukaan bukit k pada (x, z), sama dengan geometri bukit di bawah
const surf = (k, x, z) => { const yl = ZS[k] - z; return -8 - k * 1.5 + Math.max(-6, hillH(x, k + 1) + (yl > 0 ? 0 : yl * 0.18)); };
const SN = 600;

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const low = ctx.quality === 'low';
  const P = pal(THREE, palette);
  const rnd = rng(1224);
  const tex = glowTexture(THREE);
  const cloudTex = cloudTexture(THREE, rnd);
  const navy = new THREE.Color('#04110e');
  const dummy = new THREE.Object3D();

  scene.fog = new THREE.FogExp2(new THREE.Color('#0f2a26').lerp(P.to, 0.08), 0.0105);
  const horizon = new THREE.Color('#245046').lerp(P.to, 0.1);
  scene.add(paintedSky(THREE, rnd, {
    stops: [[0, horizon], [0.16, new THREE.Color('#10342f')], [0.5, new THREE.Color('#07201d')], [1, new THREE.Color('#02100f')]],
    milky: { az0: -0.7, el0: 0.3, az1: 0.45, el1: 0.98, width: 85, alpha: 0.075 },
  }));
  const sf = starField(THREE, rnd, 1100, { tex, minEl: 0.06, size: 1.7, color: '#fff6e6' });
  scene.add(sf.group);

  // bulan bertekstur, halo lembut, sinar tipis
  const moonTex = moonTexture(THREE, rnd);
  const moonPos = skyDir(THREE, 0.3, 0.26, 440);
  const moon = phasedMoon(THREE, moonTex, { radius: 30, sunDir: new THREE.Vector3(-0.22, 0.18, 1), tint: '#fff1d6', earth: 0.1, gain: 1.35 });
  moon.position.copy(moonPos);
  scene.add(moon);
  const halo = glowSprite(THREE, tex, '#cfe0ff', 190, 0.5); halo.position.copy(moonPos).multiplyScalar(1.012); scene.add(halo);
  const halo2 = glowSprite(THREE, tex, '#6fb5a0', 420, 0.2); halo2.position.copy(moonPos).multiplyScalar(1.03); scene.add(halo2);
  const rays = godRays(THREE, rnd, { n: 9, len: 300, width: 34, color: '#c4eadb', opacity: 0.045 });
  rays.mesh.position.copy(moonPos).multiplyScalar(1.03);
  scene.add(rays.mesh);

  // lapisan awan tipis tersinari bulan
  const clouds = [];
  for (let i = 0; i < 5; i++) {
    const az = -0.9 + rnd() * 1.8, el = 0.08 + rnd() * 0.55, r = 395 + rnd() * 30, w = r * (0.22 + rnd() * 0.32);
    const nearMoon = Math.max(0, 1 - Math.hypot(az - 0.3, el - 0.26) / 0.5);
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: new THREE.Color('#6f9c90').lerp(new THREE.Color('#f2e9d8'), nearMoon * 0.7), transparent: true, opacity: 0.2 + nearMoon * 0.22, depthWrite: false, fog: false }));
    sp.scale.set(w, w * 0.3, 1);
    clouds.push({ sp, az, el, r, v: (rnd() - 0.3) * 0.012 });
    scene.add(sp);
  }

  // cahaya
  scene.add(new THREE.HemisphereLight(new THREE.Color('#8fc4ae').lerp(P.to, 0.15), P.from.clone().lerp(navy, 0.5), 0.5));
  const moonLight = new THREE.DirectionalLight('#d6efe4', 1.0);
  moonLight.position.set(30, 40, -40);
  scene.add(moonLight);
  const fill = new THREE.DirectionalLight('#ffd9b8', 0.4);
  fill.position.set(-8, 12, 40);
  scene.add(fill);
  const lantern = new THREE.PointLight('#ffb454', 60, 28, 1.6);
  scene.add(lantern);

  // bukit bersalju (3 lapis)
  const snowMat = [0, 1, 2].map((k) => new THREE.MeshStandardMaterial({ color: new THREE.Color('#eef8f3').lerp(P.to, 0.04 * k).multiplyScalar(0.9 - k * 0.1), roughness: 0.95 }));
  ZS.forEach((z, k) => {
    const g = new THREE.PlaneGeometry(260, 60, 90, 8);
    const p = g.attributes.position;
    for (let i = 0; i < p.count; i++) {
      const x = p.getX(i), v = p.getY(i);
      p.setZ(i, Math.max(-6, hillH(x, k + 1) + (v > 0 ? 0 : v * 0.18)));
    }
    g.computeVertexNormals();
    const m = new THREE.Mesh(g, snowMat[k]);
    m.rotation.x = -Math.PI / 2; m.position.set(0, -8 - k * 1.5, z);
    scene.add(m);
  });

  const base = new THREE.Mesh(new THREE.PlaneGeometry(700, 320), snowMat[1]); // alas supaya tidak ada celah di antara bukit
  base.rotation.x = -Math.PI / 2; base.position.set(0, -17.4, -70); scene.add(base);

  // pinus berselimut salju: satu geometri gabungan, banyak instance
  const pp = [];
  const cone = (r, h, y, col) => pp.push({ geo: new THREE.ConeGeometry(r, h, 9, 1, true), m: new THREE.Matrix4().makeTranslation(0, y + h / 2, 0), color: col });
  for (let i = 0; i < 5; i++) {
    const r = 1.55 - i * 0.27, h = 1.7, y = 0.7 + i * 0.95;
    cone(r, h, y, '#1f5a48');
    cone(r * 0.55 * 1.07, h * 0.55, y + h * 0.45, '#e6f6ee'); // salju di bagian atas tiap tingkat
    cone(r * 1.02, 0.16, y - 0.02, '#c8e4d8');                 // bibir salju di ujung dahan
  }
  pp.push({ geo: new THREE.CylinderGeometry(0.12, 0.18, 0.9, 6), m: new THREE.Matrix4().makeTranslation(0, 0.45, 0), color: '#2b1d14' });
  const pineGeo = mergeParts(THREE, pp); pp.forEach((q) => q.geo.dispose());
  const pineMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.95, side: THREE.DoubleSide });
  const NP = 380, pines = new THREE.InstancedMesh(pineGeo, pineMat, NP);
  const col = new THREE.Color();
  for (let i = 0, n = 0; n < NP && i < 4000; i++) {
    const k = n % 3, x = (rnd() - 0.5) * 230, z = ZS[k] + (rnd() - 0.2) * 22 - 6;
    if (k === 0 && Math.abs(x) < 26) continue;
    if (k === 2 && x > -48 && x < 52 && z < -74) continue; // ruang untuk desa
    const s = (0.75 + rnd() * 0.8) * (k === 0 ? 1.2 : 1);
    dummy.position.set(x, surf(k, x, z) - 0.2, z); dummy.rotation.set((rnd() - 0.5) * 0.1, rnd() * 6.28, (rnd() - 0.5) * 0.1); dummy.scale.set(s, s * (0.9 + rnd() * 0.3), s);
    dummy.updateMatrix(); pines.setMatrixAt(n, dummy.matrix);
    col.setScalar(0.65 + rnd() * 0.5); pines.setColorAt(n, col);
    n++;
  }
  scene.add(pines);

  // desa kecil di bukit tengah: rumah, jendela hangat, cerobong asap
  const NH = 16, houses = [];
  for (let i = 0; i < NH; i++) {
    const x = -38 + i * 5.2 + (rnd() - 0.5) * 2.4, z = -79 - rnd() * 9;
    houses.push({ x, z, y: surf(2, x, z) - 0.15, w: 3.4 + rnd() * 2.2, h: 2.4 + rnd() * 1.1, d: 3 + rnd() * 1.6, rh: 1.4 + rnd() * 0.8, ry: (rnd() - 0.5) * 0.7 });
  }
  houses[5].w = 7.5; houses[5].h = 3.6; houses[5].d = 5.2; houses[5].rh = 2.2; // pondok besar
  const roofShape = new THREE.Shape(); roofShape.moveTo(-0.5, 0); roofShape.lineTo(0.5, 0); roofShape.lineTo(0, 1); roofShape.closePath();
  const roofGeo = new THREE.ExtrudeGeometry(roofShape, { depth: 1, bevelEnabled: false }); roofGeo.translate(0, 0, -0.5);
  const boxGeo = new THREE.BoxGeometry(1, 1, 1); boxGeo.translate(0, 0.5, 0);
  const winGeo = new THREE.PlaneGeometry(1, 1);
  const bodies = new THREE.InstancedMesh(boxGeo, new THREE.MeshStandardMaterial({ roughness: 0.9 }), NH);
  const roofs = new THREE.InstancedMesh(roofGeo, new THREE.MeshStandardMaterial({ color: '#dcefe6', emissive: '#2f5a4c', emissiveIntensity: 0.9, roughness: 0.95 }), NH);
  const chims = new THREE.InstancedMesh(boxGeo, new THREE.MeshStandardMaterial({ color: '#3a2d2a', roughness: 1 }), NH);
  const wins = new THREE.InstancedMesh(winGeo, new THREE.MeshBasicMaterial({ color: new THREE.Color('#ffb04d').multiplyScalar(1.5) }), NH * 2);
  const glowPts = [], chimTops = [], SC = 2.1;
  const wallCols = ['#b07a52', '#8f6e5c', '#b58c5c', '#6f9487', '#a56a5c'];
  let wn = 0;
  houses.forEach((H, i) => {
    const c = Math.cos(H.ry), s = Math.sin(H.ry), W = H.w * SC, Hh = H.h * SC, D = H.d * SC;
    dummy.rotation.set(0, H.ry, 0);
    dummy.position.set(H.x, H.y, H.z); dummy.scale.set(W, Hh, D); dummy.updateMatrix(); bodies.setMatrixAt(i, dummy.matrix);
    bodies.setColorAt(i, col.set(wallCols[i % 5]));
    dummy.position.set(H.x, H.y + Hh, H.z); dummy.scale.set(W * 1.12, H.rh * SC, D * 1.08); dummy.updateMatrix(); roofs.setMatrixAt(i, dummy.matrix);
    const cx = W * 0.27, cy = Hh + H.rh * SC * 0.5;
    dummy.position.set(H.x + cx * c, H.y + cy, H.z - cx * s); dummy.scale.set(0.55 * SC, 1.2 * SC, 0.55 * SC); dummy.updateMatrix(); chims.setMatrixAt(i, dummy.matrix);
    chimTops.push([H.x + cx * c, H.y + cy + 1.2 * SC, H.z - cx * s]);
    [-1, 1].forEach((sd) => {
      if (rnd() < 0.22) return;
      const lx = sd * W * 0.24, ly = Hh * 0.52, lz = D / 2 + 0.04;
      dummy.position.set(H.x + lx * c + lz * s, H.y + ly, H.z - lx * s + lz * c);
      dummy.scale.set(0.62 * SC, 0.78 * SC, 1); dummy.updateMatrix(); wins.setMatrixAt(wn++, dummy.matrix);
      glowPts.push(dummy.position.x, dummy.position.y, dummy.position.z + 0.1);
    });
  });
  bodies.count = roofs.count = chims.count = NH; wins.count = wn;
  scene.add(bodies, roofs, chims, wins);
  const gpGeo = new THREE.BufferGeometry(); gpGeo.setAttribute('position', new THREE.Float32BufferAttribute(glowPts, 3));
  const winGlow = new THREE.Points(gpGeo, new THREE.PointsMaterial({ map: tex, color: '#ff9f45', size: 22, sizeAttenuation: false, transparent: true, opacity: 0.5, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  scene.add(winGlow);

  // gereja kecil dengan menara dan jendela berkaca menyala
  const stone = [], roofP = [], glow = [];
  const tm = (x, y, z, ry = 0) => new THREE.Matrix4().makeTranslation(x, y, z).multiply(new THREE.Matrix4().makeRotationY(ry));
  const bx = (w, h, d) => { const g = new THREE.BoxGeometry(w, h, d); g.translate(0, h / 2, 0); return g; };
  stone.push({ geo: bx(5, 4, 8), m: tm(0, 0, 0), color: '#a9a39d' }, { geo: bx(2.7, 9, 2.7), m: tm(0, 0, 4.4), color: '#a9a39d' });
  roofP.push({ geo: roofGeo.clone().scale(5.7, 2.6, 8.4), m: tm(0, 4, 0), color: '#dcefe6' });
  const spire = new THREE.ConeGeometry(2.1, 5.2, 4); spire.translate(0, 2.6, 0);
  roofP.push({ geo: spire, m: tm(0, 9, 4.4, Math.PI / 4), color: '#cfe6dc' });
  const glowBox = (w, h, d, x, y, z, c) => glow.push({ geo: bx(w, h, d), m: tm(x, y, z), color: c });
  glowBox(0.1, 0.95, 0.12, 0, 14.2, 4.4, '#e8c46a'); glowBox(0.55, 0.1, 0.12, 0, 14.0, 4.4, '#e8c46a');
  for (let i = 0; i < 3; i++) { glowBox(0.06, 1.7, 0.7, 2.53, 1.3, -2.4 + i * 2.2, '#ffb556'); glowBox(0.06, 1.7, 0.7, -2.53, 1.3, -2.4 + i * 2.2, '#ffb556'); }
  glowBox(0.9, 1.4, 0.06, 0, 6.8, 5.78, '#ffc46b'); glowBox(1.0, 1.9, 0.06, 0, 0, 5.78, '#ff9f45');
  const mkMesh = (parts, mat) => { const g = mergeParts(THREE, parts); parts.forEach((q) => q.geo.dispose()); return new THREE.Mesh(g, mat); };
  const church = new THREE.Group();
  church.add(mkMesh(stone, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.9 })));
  church.add(mkMesh(roofP, new THREE.MeshStandardMaterial({ vertexColors: true, emissive: '#2f5a4c', emissiveIntensity: 0.8, roughness: 0.95 })));
  church.add(mkMesh(glow, new THREE.MeshBasicMaterial({ vertexColors: true })));
  church.position.set(-6, surf(2, -6, -84) - 0.2, -84); church.scale.setScalar(1.7); church.rotation.y = 0.1;
  scene.add(church);

  // asap cerobong (partikel aditif lembut), sudah berjalan sejak frame pertama
  const smoke = particlePool(THREE, 200, { tex, size: 3.4, gravity: 0.45, drag: 0.55 });
  scene.add(smoke.points);
  const smokeC = new THREE.Color(0.1, 0.115, 0.16), chimIdx = [0, 2, 4, 6, 9, 11, 13];
  const puff = (dtp) => chimIdx.forEach((ci) => {
    const t = chimTops[ci];
    if (rnd() < dtp * 5) smoke.emit(t[0], t[1], t[2], 0.7 + rnd() * 0.5, 0.9 + rnd() * 0.5, (rnd() - 0.5) * 0.3, smokeC, 2.6 + rnd());
  });
  for (let i = 0; i < 70; i++) { puff(0.04); smoke.update(0.04); }

  // kabut tanah di antara bukit
  const fogs = [[-12, -40, 0.06], [-9.5, -80, 0.09]].map(([y, z, o], i) => {
    const m = new THREE.Mesh(new THREE.PlaneGeometry(330, 34), new THREE.MeshBasicMaterial({ map: cloudTex, color: '#4c8576', transparent: true, opacity: o, depthWrite: false, fog: false }));
    m.rotation.x = -Math.PI / 2; m.position.set(0, y, z); scene.add(m);
    return { m, o, ph: i * 2 };
  });

  // kilau salju di lereng depan
  const NS = 200, spk = new Float32Array(NS * 3), sc = new Float32Array(NS * 3), sph = new Float32Array(NS);
  for (let i = 0; i < NS; i++) { const x = (rnd() - 0.5) * 70, z = -2 - rnd() * 30; spk.set([x, surf(0, x, z) + 0.15, z], i * 3); sph[i] = rnd() * 6.28; }
  const sparkGeo = new THREE.BufferGeometry(); sparkGeo.setAttribute('position', new THREE.BufferAttribute(spk, 3)); sparkGeo.setAttribute('color', new THREE.BufferAttribute(sc, 3));
  const sparkles = new THREE.Points(sparkGeo, new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 4, sizeAttenuation: false, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending }));
  sparkles.frustumCulled = false; scene.add(sparkles);

  // ===== kereta luncur dan rusa (geometri gabungan, sedikit draw call) =====
  const SCALE = 1.85;
  const fly = new THREE.Group();
  scene.add(fly);
  const sleighGroup = new THREE.Group();
  fly.add(sleighGroup);
  const redHex = '#' + P.primary.clone().lerp(new THREE.Color('#cc2a2e'), 0.85).getHexString(); // merah hanya aksen kecil di kereta
  const goldHex = '#' + P.accent.clone().lerp(new THREE.Color('#c9962f'), 0.45).getHexString();
  const sl = buildSleigh(THREE, { red: redHex, gold: goldHex });
  const bodyMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.55, metalness: 0.12, emissive: '#1a0a08', emissiveIntensity: 0.6 });
  sleighGroup.add(new THREE.Mesh(sl.sleigh, bodyMat));

  // lentera menyala di haluan
  const lampMat = new THREE.MeshBasicMaterial({ color: new THREE.Color('#ffc880').multiplyScalar(1.5) });
  const lampGeo = new THREE.CylinderGeometry(0.1, 0.08, 0.22, 8);
  const lamp = new THREE.Mesh(lampGeo, lampMat); lamp.position.set(1.72, 1.55, 0.55); sleighGroup.add(lamp);
  const lampHalo = glowSprite(THREE, tex, '#ffb347', 1.8, 0.85); lampHalo.position.copy(lamp.position); sleighGroup.add(lampHalo);
  const poleGeo = new THREE.CylinderGeometry(0.02, 0.02, 0.4, 5);
  const pole = new THREE.Mesh(poleGeo, new THREE.MeshStandardMaterial({ color: goldHex, roughness: 0.4, metalness: 0.5 })); pole.position.set(1.72, 1.32, 0.55); sleighGroup.add(pole);

  const deerMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.85, emissive: '#1c0f08', emissiveIntensity: 0.5 });
  const dA = buildReindeer(THREE, { redNose: false }), dB = buildReindeer(THREE, { redNose: true });
  const DS = 1.12, deer = [];
  const pairs = [[4.3, 0.62, 0.58], [7.0, 0.66, 0.58]];
  let noseGlow = null;
  pairs.forEach(([x, y, z], pi) => [1, -1].forEach((s, si) => {
    const R = (pi === 1 && s === 1) ? dB : dA;
    const g = new THREE.Group(); g.position.set(x + si * 0.18, y + si * 0.04, s * z); g.scale.setScalar(DS); g.rotation.y = s * 0.04;
    g.add(new THREE.Mesh(R.body, deerMat));
    const lf = new THREE.Mesh(R.legsF, deerMat); lf.position.set(...R.shoulder); g.add(lf);
    const lb = new THREE.Mesh(R.legsB, deerMat); lb.position.set(...R.hip); g.add(lb);
    if (R === dB) { noseGlow = glowSprite(THREE, tex, '#ff3b2a', 0.75, 0.55); noseGlow.position.set(...R.nose); g.add(noseGlow); }
    fly.add(g);
    deer.push({ g, lf, lb, ph: pi * 1.7 + si * 0.9, y: g.position.y, x: g.position.x });
  }));
  const harnessGeo = buildHarness(THREE, { hands: sl.hands, front: sl.front, pairs: pairs.map(([x, y, z]) => [x + 0.09, y, z * 0.9]), gold: goldHex, red: redHex });
  const harnessMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.5, metalness: 0.3 });
  sleighGroup.add(new THREE.Mesh(harnessGeo, harnessMat));

  // jalur terbang: melintasi bulan di tengah durasi
  const path = new THREE.CatmullRomCurve3([[-30, 3.5, -3], [-9, 7, -6], [9, 11.8, -12], [25, 15.5, -20], [42, 19, -30]].map((a) => new THREE.Vector3(...a)));

  // pita cahaya di belakang kereta (ring buffer waktu, tanpa alokasi per frame)
  const NR = 44, hist = new Float32Array(NR * 3), rpos = new Float32Array(NR * 2 * 3), rcol = new Float32Array(NR * 2 * 3);
  const ribGeo = new THREE.BufferGeometry();
  ribGeo.setAttribute('position', new THREE.BufferAttribute(rpos, 3)); ribGeo.setAttribute('color', new THREE.BufferAttribute(rcol, 3));
  const ridx = [];
  for (let i = 0; i < NR - 1; i++) { const a = i * 2; ridx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
  ribGeo.setIndex(ridx);
  const ribbon = new THREE.Mesh(ribGeo, new THREE.MeshBasicMaterial({ vertexColors: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide, fog: false }));
  ribbon.frustumCulled = false; scene.add(ribbon);
  const ribC = P.accent.clone().multiplyScalar(0.95);
  let histN = 0, histAcc = 0;

  const trail = particlePool(THREE, low ? 140 : 300, { tex, size: 0.4, gravity: -0.5, drag: 1.2 });
  scene.add(trail.points);
  const SNN = 60;
  const snow = particlePool(THREE, SN, { tex, size: 0.3, gravity: -2.2, drag: 0.3, additive: false });
  const snowNear = particlePool(THREE, SNN, { tex, size: 0.55, gravity: -1.6, drag: 0.3, additive: false });
  scene.add(snow.points, snowNear.points);
  const white2 = new THREE.Color('#ffffff'), dimW = new THREE.Color('#9fd0be');
  for (let i = 0; i < SN; i++) snow.emit((rnd() - 0.5) * 70, (rnd() - 0.4) * 30, 10 - rnd() * 70, (rnd() - 0.5) * 0.6, -1.5 - rnd(), (rnd() - 0.5) * 0.6, white2, 1e9);
  for (let i = 0; i < SNN; i++) snowNear.emit((rnd() - 0.5) * 20, (rnd() - 0.3) * 16, 3 + rnd() * 9, (rnd() - 0.5) * 0.5, -1.2 - rnd() * 0.6, (rnd() - 0.5) * 0.4, dimW, 1e9);
  const sparkC = P.accent.clone().lerp(white2, 0.35).multiplyScalar(1.3);

  const aurora = createAurora(THREE, rnd); scene.add(aurora.mesh);
  const frost = createFrost(THREE, rnd, { strength: low ? 0.4 : 0.55 });
  scene.add(camera); camera.add(frost.mesh);

  const tmp = new THREE.Vector3(), tan = new THREE.Vector3(), tp = new THREE.Vector3(), lampW = new THREE.Vector3();
  const BASE_Y = 0;
  return {
    update(tt, dt) {
      const T = tt * duration / 1000;
      const k = 0.03 + 0.62 * easeInOut(tt);
      path.getPoint(k, tmp); path.getTangent(k, tan);
      fly.position.copy(tmp);
      fly.position.y += Math.sin(T * 2.1) * 0.35;
      fly.rotation.order = 'YZX';
      fly.rotation.set(0, -Math.atan2(tan.z, tan.x), Math.atan2(tan.y, Math.hypot(tan.x, tan.z)) + Math.sin(T * 2.1 + 0.6) * 0.03);
      fly.scale.setScalar(SCALE);
      sleighGroup.rotation.x = Math.sin(T * 1.7) * 0.025;
      sleighGroup.position.y = Math.sin(T * 4.2) * 0.05 + BASE_Y;
      // rusa: lompatan terbang; kaki depan terjulur ke depan, kaki belakang ke belakang, mengayun
      const ph = T * 7.2;
      for (const d of deer) {
        const a = ph + d.ph;
        d.g.position.y = d.y + Math.sin(a) * 0.09;
        d.g.rotation.z = Math.sin(a - 0.5) * 0.07 + 0.02;
        d.lf.rotation.z = Math.sin(a + 0.4) * 0.38 + 0.1;
        d.lb.rotation.z = Math.sin(a - 0.2) * 0.34 - 0.08;
      }
      fly.updateMatrixWorld(true);
      lampW.copy(lamp.position); sleighGroup.localToWorld(lampW);
      lantern.position.copy(lampW);
      lantern.intensity = 55 + Math.sin(T * 9) * 6;
      lampHalo.material.opacity = 0.7 + Math.sin(T * 9) * 0.1;
      if (noseGlow) noseGlow.material.opacity = 0.45 + Math.sin(T * 5) * 0.1;
      // pita cahaya: titik di belakang kereta masuk ke riwayat tiap 0.03 dtk
      tp.set(-2.2, 0.35, 0); sleighGroup.localToWorld(tp);
      if (dt > 0) {
        histAcc += dt;
        if (histAcc >= 0.03 || histN === 0) {
          histAcc = 0;
          hist.copyWithin(3, 0, (NR - 1) * 3);
          hist[0] = tp.x; hist[1] = tp.y; hist[2] = tp.z;
          histN = Math.min(NR, histN + 1);
        }
        for (let i = 0; i < (low ? 3 : 5); i++) {
          trail.emit(tp.x + (rnd() - 0.5) * 0.6, tp.y + (rnd() - 0.5) * 0.7, tp.z + (rnd() - 0.5) * 0.5, (rnd() - 0.5) * 0.7, -0.2 - rnd() * 0.6, (rnd() - 0.5) * 0.7, sparkC, 1.0 + rnd() * 0.9);
        }
      }
      for (let i = 0; i < NR; i++) {
        const live = i < histN, f = live ? 1 - i / NR : 0, w = 0.22 * f * f * SCALE * 0.5, br = f * f * f * 0.34;
        const j = Math.min(i, histN - 1) * 3, o = i * 6;
        const hx = hist[j] || tp.x, hy = hist[j + 1] || tp.y, hz = hist[j + 2] || tp.z;
        rpos[o] = hx; rpos[o + 1] = hy + w; rpos[o + 2] = hz; rpos[o + 3] = hx; rpos[o + 4] = hy - w; rpos[o + 5] = hz;
        const sh = (0.75 + 0.25 * Math.sin(T * 14 + i * 0.7)) * Math.min(1, i / 4 + 0.15);
        for (let q = 0; q < 2; q++) { rcol[o + q * 3] = ribC.r * br * sh; rcol[o + q * 3 + 1] = ribC.g * br * sh; rcol[o + q * 3 + 2] = ribC.b * br * sh; }
      }
      ribGeo.attributes.position.needsUpdate = true; ribGeo.attributes.color.needsUpdate = true;
      trail.update(dt);
      // salju: ulang saat jatuh keluar
      const pos = snow.points.geometry.attributes.position;
      snow.update(dt);
      for (let i = 0; i < SN; i++) {
        if (pos.getY(i) < -14) pos.setY(i, 18);
        pos.setX(i, pos.getX(i) + Math.sin(T * 0.8 + i) * dt * 0.5);
      }
      const pn = snowNear.points.geometry.attributes.position;
      snowNear.update(dt);
      for (let i = 0; i < SNN; i++) {
        if (pn.getY(i) < -6) pn.setY(i, 11);
        pn.setX(i, pn.getX(i) + Math.sin(T * 0.7 + i * 1.7) * dt * 0.9);
      }
      sf.update(T);
      aurora.update(T);
      puff(dt); smoke.update(dt);
      clouds.forEach((c) => { c.az += c.v * dt; skyDir(THREE, c.az, c.el, c.r, c.sp.position); });
      fogs.forEach((f) => { f.m.position.x = Math.sin(T * 0.25 + f.ph) * 6; f.m.material.opacity = f.o * (0.85 + 0.15 * Math.sin(T * 0.7 + f.ph)); });
      for (let i = 0; i < NS; i++) { const kk = Math.max(0, Math.sin(T * 3.2 + sph[i] * 3)); const v = kk * kk * kk; sc[i * 3] = v; sc[i * 3 + 1] = v * 0.95; sc[i * 3 + 2] = v; }
      sparkGeo.attributes.color.needsUpdate = true;
      rays.mesh.material.opacity = 0.045 + Math.sin(T * 1.3) * 0.008;
      // kamera: dolly pelan dan geser
      camera.position.set(lerp(-3, 3, tt), 2.8 + tt * 1.2, 17 - tt * 3);
      camera.lookAt(lerp(-1, 2, tt), 5 + tt * 1.5, -6);
      frost.fit(camera);
    },
    dispose() {
      [tex, cloudTex, moonTex, rays.tex, aurora.tex, frost.tex].forEach((t) => t.dispose());
      pines.dispose(); bodies.dispose(); roofs.dispose(); chims.dispose(); wins.dispose();
      [roofGeo, boxGeo, winGeo, pineGeo, gpGeo, sparkGeo, sl.sleigh, harnessGeo, dA.body, dA.legsF, dA.legsB, dB.body, dB.legsF, dB.legsB, ribGeo, lampGeo, poleGeo].forEach((g) => g.dispose());
      [bodyMat, deerMat, harnessMat, lampMat].forEach((m) => m.dispose());
      camera.remove(frost.mesh); scene.remove(camera); frost.mesh.geometry.dispose(); frost.mesh.material.dispose();
    },
  };
}
