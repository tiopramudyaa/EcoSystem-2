// Natal: malam musim dingin. Bulan bertekstur, langit berbintang, desa bersalju, hutan pinus, kabut,
// lalu kereta luncur ditarik 4 rusa terbang melintas. Hanya geometri prosedural.
import { pal, rng, glowTexture, glowSprite, particlePool, easeInOut, lerp, paintedSky, starField, moonTexture, phasedMoon, godRays, cloudTexture, mergeParts, skyDir } from '../common.js';

const hillH = (x, k) => (Math.sin(x * 0.045 + k * 2.1) * 5 + Math.sin(x * 0.11 + k) * 2.2 + Math.sin(x * 0.021 + k * 5) * 6) * (1 + k * 0.25);
const ZS = [-24, -46, -78];
// tinggi permukaan bukit k pada (x, z), sama dengan geometri bukit di bawah
const surf = (k, x, z) => { const yl = ZS[k] - z; return -8 - k * 1.5 + Math.max(-6, hillH(x, k + 1) + (yl > 0 ? 0 : yl * 0.18)); };
const SN = 600;

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1224);
  const tex = glowTexture(THREE);
  const cloudTex = cloudTexture(THREE, rnd);
  const navy = new THREE.Color('#050a1c');
  const dummy = new THREE.Object3D();

  scene.fog = new THREE.FogExp2(new THREE.Color('#16223f').lerp(P.to, 0.08), 0.0105);
  const horizon = new THREE.Color('#2b3d6e').lerp(P.to, 0.1);
  scene.add(paintedSky(THREE, rnd, {
    stops: [[0, horizon], [0.16, new THREE.Color('#1a2850')], [0.5, new THREE.Color('#0a1332')], [1, new THREE.Color('#03061a')]],
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
  const halo2 = glowSprite(THREE, tex, '#7f9ad8', 420, 0.2); halo2.position.copy(moonPos).multiplyScalar(1.03); scene.add(halo2);
  const rays = godRays(THREE, rnd, { n: 9, len: 300, width: 34, color: '#b9ccff', opacity: 0.045 });
  rays.mesh.position.copy(moonPos).multiplyScalar(1.03);
  scene.add(rays.mesh);

  // lapisan awan tipis tersinari bulan
  const clouds = [];
  for (let i = 0; i < 8; i++) {
    const az = -0.9 + rnd() * 1.8, el = 0.08 + rnd() * 0.55, r = 395 + rnd() * 30, w = r * (0.22 + rnd() * 0.32);
    const nearMoon = Math.max(0, 1 - Math.hypot(az - 0.3, el - 0.26) / 0.5);
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: new THREE.Color('#7f94c4').lerp(new THREE.Color('#f2e9d8'), nearMoon * 0.7), transparent: true, opacity: 0.2 + nearMoon * 0.22, depthWrite: false, fog: false }));
    sp.scale.set(w, w * 0.3, 1);
    clouds.push({ sp, az, el, r, v: (rnd() - 0.3) * 0.012 });
    scene.add(sp);
  }

  // cahaya
  scene.add(new THREE.HemisphereLight(new THREE.Color('#9bb0e8').lerp(P.to, 0.15), P.from.clone().lerp(navy, 0.5), 0.5));
  const moonLight = new THREE.DirectionalLight('#cfdcff', 1.0);
  moonLight.position.set(30, 40, -40);
  scene.add(moonLight);
  const fill = new THREE.DirectionalLight('#ffd9b8', 0.4);
  fill.position.set(-8, 12, 40);
  scene.add(fill);
  const lantern = new THREE.PointLight('#ffb454', 60, 28, 1.6);
  scene.add(lantern);

  // bukit bersalju (3 lapis)
  const snowMat = [0, 1, 2].map((k) => new THREE.MeshStandardMaterial({ color: new THREE.Color('#e6eefc').lerp(P.to, 0.04 * k).multiplyScalar(0.9 - k * 0.1), roughness: 0.95 }));
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
    cone(r * 0.55 * 1.07, h * 0.55, y + h * 0.45, '#e9f1ff'); // salju di bagian atas tiap tingkat
    cone(r * 1.02, 0.16, y - 0.02, '#cfdcf5');                 // bibir salju di ujung dahan
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
  const roofs = new THREE.InstancedMesh(roofGeo, new THREE.MeshStandardMaterial({ color: '#dfe8f8', emissive: '#3b4c72', emissiveIntensity: 0.9, roughness: 0.95 }), NH);
  const chims = new THREE.InstancedMesh(boxGeo, new THREE.MeshStandardMaterial({ color: '#3a2d2a', roughness: 1 }), NH);
  const wins = new THREE.InstancedMesh(winGeo, new THREE.MeshBasicMaterial({ color: new THREE.Color('#ffb04d').multiplyScalar(1.5) }), NH * 2);
  const glowPts = [], chimTops = [], SC = 2.1;
  const wallCols = ['#9a6f55', '#7d6458', '#a88460', '#62788a', '#94605a'];
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
  roofP.push({ geo: roofGeo.clone().scale(5.7, 2.6, 8.4), m: tm(0, 4, 0), color: '#dfe8f8' });
  const spire = new THREE.ConeGeometry(2.1, 5.2, 4); spire.translate(0, 2.6, 0);
  roofP.push({ geo: spire, m: tm(0, 9, 4.4, Math.PI / 4), color: '#d4def2' });
  const glowBox = (w, h, d, x, y, z, c) => glow.push({ geo: bx(w, h, d), m: tm(x, y, z), color: c });
  glowBox(0.1, 0.95, 0.12, 0, 14.2, 4.4, '#e8c46a'); glowBox(0.55, 0.1, 0.12, 0, 14.0, 4.4, '#e8c46a');
  for (let i = 0; i < 3; i++) { glowBox(0.06, 1.7, 0.7, 2.53, 1.3, -2.4 + i * 2.2, '#ffb556'); glowBox(0.06, 1.7, 0.7, -2.53, 1.3, -2.4 + i * 2.2, '#ffb556'); }
  glowBox(0.9, 1.4, 0.06, 0, 6.8, 5.78, '#ffc46b'); glowBox(1.0, 1.9, 0.06, 0, 0, 5.78, '#ff9f45');
  const mkMesh = (parts, mat) => { const g = mergeParts(THREE, parts); parts.forEach((q) => q.geo.dispose()); return new THREE.Mesh(g, mat); };
  const church = new THREE.Group();
  church.add(mkMesh(stone, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.9 })));
  church.add(mkMesh(roofP, new THREE.MeshStandardMaterial({ vertexColors: true, emissive: '#3b4c72', emissiveIntensity: 0.8, roughness: 0.95 })));
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
  const fogs = [[-12, -34, 0.05], [-10.5, -58, 0.07], [-9, -86, 0.09]].map(([y, z, o], i) => {
    const m = new THREE.Mesh(new THREE.PlaneGeometry(330, 34), new THREE.MeshBasicMaterial({ map: cloudTex, color: '#5a70a8', transparent: true, opacity: o, depthWrite: false, fog: false }));
    m.rotation.x = -Math.PI / 2; m.position.set(0, y, z); scene.add(m);
    return { m, o, ph: i * 2 };
  });

  // kilau salju di lereng depan
  const NS = 200, spk = new Float32Array(NS * 3), sc = new Float32Array(NS * 3), sph = new Float32Array(NS);
  for (let i = 0; i < NS; i++) { const x = (rnd() - 0.5) * 70, z = -2 - rnd() * 30; spk.set([x, surf(0, x, z) + 0.15, z], i * 3); sph[i] = rnd() * 6.28; }
  const sparkGeo = new THREE.BufferGeometry(); sparkGeo.setAttribute('position', new THREE.BufferAttribute(spk, 3)); sparkGeo.setAttribute('color', new THREE.BufferAttribute(sc, 3));
  const sparkles = new THREE.Points(sparkGeo, new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 4, sizeAttenuation: false, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending }));
  sparkles.frustumCulled = false; scene.add(sparkles);

  // ===== kereta luncur =====
  const red = new THREE.MeshStandardMaterial({ color: '#c8182c', roughness: 0.4, metalness: 0.1 });
  const gold = new THREE.MeshStandardMaterial({ color: P.accent.clone().multiplyScalar(0.9), roughness: 0.3, metalness: 0.85 });
  const white = new THREE.MeshStandardMaterial({ color: '#f5f5f5', roughness: 0.85 });
  const skin = new THREE.MeshStandardMaterial({ color: '#e8b79a', roughness: 0.7 });
  const brown = new THREE.MeshStandardMaterial({ color: '#6b4226', roughness: 0.85 });
  const dark = new THREE.MeshStandardMaterial({ color: '#2a1a12', roughness: 0.9 });

  const fly = new THREE.Group();
  scene.add(fly);
  const sleigh = new THREE.Group();
  fly.add(sleigh);

  // badan: profil samping melengkung, diekstrusi
  const sp = new THREE.Shape();
  sp.moveTo(-1.7, 0.1); sp.lineTo(1.2, 0.1);
  sp.bezierCurveTo(1.6, 0.2, 1.9, 0.7, 1.75, 1.25);
  sp.bezierCurveTo(1.55, 1.05, 1.35, 0.85, 1.1, 0.8);
  sp.lineTo(-1.2, 0.8);
  sp.bezierCurveTo(-1.6, 0.9, -1.9, 1.25, -1.85, 1.55);
  sp.bezierCurveTo(-2.1, 1.0, -2.0, 0.4, -1.7, 0.1);
  const body = new THREE.Mesh(new THREE.ExtrudeGeometry(sp, { depth: 1.1, bevelEnabled: true, bevelThickness: 0.07, bevelSize: 0.07, bevelSegments: 2, curveSegments: 14 }), red);
  body.position.z = -0.55;
  sleigh.add(body);
  // ban emas di tepi atas
  const trim = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.07, 1.22), gold); trim.position.set(-0.05, 0.83, 0); sleigh.add(trim);
  // seluncur (runner) melengkung
  [-0.62, 0.62].forEach((z) => {
    const cv = new THREE.CatmullRomCurve3([[-2.0, -0.55, z], [-1.4, -0.7, z], [0.8, -0.7, z], [1.7, -0.55, z], [2.05, -0.15, z], [1.95, 0.15, z]].map((a) => new THREE.Vector3(...a)));
    sleigh.add(new THREE.Mesh(new THREE.TubeGeometry(cv, 24, 0.045, 6), gold));
    [-0.9, 0.7].forEach((x) => { const s = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.62, 5), gold); s.position.set(x, -0.35, z); sleigh.add(s); });
  });
  // karung hadiah
  const sack = new THREE.Mesh(new THREE.SphereGeometry(0.62, 16, 12), new THREE.MeshStandardMaterial({ color: '#8a5a33', roughness: 1 }));
  sack.scale.set(1.2, 1.1, 1); sack.position.set(-1.15, 1.2, 0); sleigh.add(sack);
  [['#2fa15a', -0.7, 1.0], ['#c9322e', -1.5, 1.1]].forEach(([col, x, y], i) => {
    const b = new THREE.Mesh(new THREE.BoxGeometry(0.35, 0.3, 0.35), new THREE.MeshStandardMaterial({ color: col, roughness: 0.5 }));
    b.position.set(x - 0.2, y + 0.3, i ? 0.25 : -0.25); b.rotation.y = 0.5; sleigh.add(b);
  });

  // Santa
  const santa = new THREE.Group(); santa.position.set(0.35, 0.9, 0); sleigh.add(santa);
  const torso = new THREE.Mesh(new THREE.CapsuleGeometry(0.34, 0.45, 6, 14), red); torso.position.y = 0.3; santa.add(torso);
  const belt = new THREE.Mesh(new THREE.TorusGeometry(0.35, 0.05, 8, 20), dark); belt.rotation.x = Math.PI / 2; belt.position.y = 0.18; santa.add(belt);
  const buckle = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.13, 0.05), gold); buckle.position.set(0, 0.18, 0.36); santa.add(buckle);
  const coat = new THREE.Mesh(new THREE.CylinderGeometry(0.075, 0.075, 0.6, 8), white); coat.position.set(0, 0.35, 0.34); santa.add(coat);
  const head = new THREE.Mesh(new THREE.SphereGeometry(0.22, 18, 14), skin); head.position.y = 0.93; santa.add(head);
  const beard = new THREE.Mesh(new THREE.SphereGeometry(0.24, 18, 14), white); beard.scale.set(1, 0.9, 0.8); beard.position.set(0.03, 0.8, 0.1); santa.add(beard);
  const hat = new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.5, 14), red); hat.position.set(-0.08, 1.24, 0); hat.rotation.z = 0.5; santa.add(hat);
  const hatTrim = new THREE.Mesh(new THREE.TorusGeometry(0.21, 0.06, 8, 18), white); hatTrim.rotation.x = Math.PI / 2; hatTrim.position.y = 1.05; santa.add(hatTrim);
  const pom = new THREE.Mesh(new THREE.SphereGeometry(0.08, 10, 8), white); pom.position.set(-0.27, 1.42, 0); santa.add(pom);
  [-1, 1].forEach((s) => { // lengan memegang tali kekang
    const a = new THREE.Mesh(new THREE.CapsuleGeometry(0.09, 0.4, 4, 8), red); a.position.set(0.35, 0.38, s * 0.3); a.rotation.z = -1.1; santa.add(a);
  });
  // lentera menyala
  const lampMat = new THREE.MeshStandardMaterial({ color: '#ffd27a', emissive: '#ffb347', emissiveIntensity: 2.2 });
  const lamp = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.09, 0.22, 8), lampMat);
  lamp.position.set(1.65, 1.5, 0.55); sleigh.add(lamp);
  const lampHalo = glowSprite(THREE, tex, '#ffb347', 1.6, 0.9); lampHalo.position.copy(lamp.position); sleigh.add(lampHalo);
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.55, 5), gold); pole.position.set(1.65, 1.25, 0.55); sleigh.add(pole);

  // rusa
  const deerMat = brown;
  function makeLeg(len, w = 0.07) {
    const hip = new THREE.Group();
    const up = new THREE.Mesh(new THREE.CapsuleGeometry(w, len, 4, 6), deerMat); up.position.y = -len / 2; hip.add(up);
    const knee = new THREE.Group(); knee.position.y = -len; hip.add(knee);
    const lo = new THREE.Mesh(new THREE.CapsuleGeometry(w * 0.75, len, 4, 6), deerMat); lo.position.y = -len / 2; knee.add(lo);
    const hoof = new THREE.Mesh(new THREE.SphereGeometry(w * 0.9, 6, 5), dark); hoof.position.y = -len - w * 0.4; knee.add(hoof);
    return { hip, knee };
  }
  const antlerCurve = new THREE.CatmullRomCurve3([[0, 0, 0], [0.05, 0.2, 0.06], [-0.05, 0.38, 0.14], [-0.02, 0.55, 0.12]].map((a) => new THREE.Vector3(...a)));
  const antlerGeo = new THREE.TubeGeometry(antlerCurve, 10, 0.022, 5);
  const tineGeo = new THREE.TubeGeometry(new THREE.CatmullRomCurve3([[0, 0, 0], [0.12, 0.1, 0.0], [0.2, 0.22, 0.02]].map((a) => new THREE.Vector3(...a))), 6, 0.016, 4);
  const noseMat = new THREE.MeshStandardMaterial({ color: '#ff2a2a', emissive: '#ff1010', emissiveIntensity: 2.5 });
  const deer = [];
  function makeDeer(x, z, phase, nose) {
    const g = new THREE.Group(); g.position.set(x, -0.05, z);
    const bodyM = new THREE.Mesh(new THREE.CapsuleGeometry(0.28, 0.85, 6, 12), deerMat); bodyM.rotation.z = Math.PI / 2; g.add(bodyM);
    const chest = new THREE.Mesh(new THREE.SphereGeometry(0.3, 12, 10), white); chest.scale.set(0.5, 0.9, 0.8); chest.position.set(0.55, -0.02, 0); g.add(chest);
    const neck = new THREE.Mesh(new THREE.CapsuleGeometry(0.13, 0.5, 4, 8), deerMat); neck.position.set(0.72, 0.4, 0); neck.rotation.z = -0.7; g.add(neck);
    const head = new THREE.Group(); head.position.set(0.98, 0.68, 0); g.add(head);
    const skull = new THREE.Mesh(new THREE.SphereGeometry(0.14, 12, 10), deerMat); skull.scale.set(1.3, 1, 0.9); head.add(skull);
    const snout = new THREE.Mesh(new THREE.CapsuleGeometry(0.07, 0.14, 4, 8), deerMat); snout.rotation.z = Math.PI / 2 - 0.25; snout.position.set(0.2, -0.05, 0); head.add(snout);
    const tip = new THREE.Mesh(new THREE.SphereGeometry(0.05, 8, 6), nose ? noseMat : dark); tip.position.set(0.31, -0.09, 0); head.add(tip);
    [-1, 1].forEach((s) => {
      const a = new THREE.Mesh(antlerGeo, new THREE.MeshStandardMaterial({ color: '#4a3220', roughness: 0.9 }));
      a.position.set(-0.02, 0.1, s * 0.07); a.rotation.set(s * 0.35, 0, -0.35); a.scale.setScalar(1.1);
      const t1 = new THREE.Mesh(tineGeo, a.material); t1.position.set(0.0, 0.25, 0.03); t1.rotation.z = 0.4;
      a.add(t1); head.add(a);
      const ear = new THREE.Mesh(new THREE.ConeGeometry(0.05, 0.14, 5), deerMat); ear.position.set(-0.08, 0.12, s * 0.12); ear.rotation.set(s * 0.8, 0, 0.2); head.add(ear);
    });
    const legs = [];
    [[0.5, 0.17], [0.5, -0.17], [-0.5, 0.17], [-0.5, -0.17]].forEach(([lx, lz], i) => {
      const L = makeLeg(0.34, i < 2 ? 0.065 : 0.08); L.hip.position.set(lx, -0.18, lz); g.add(L.hip); legs.push(L);
    });
    const tail = new THREE.Mesh(new THREE.SphereGeometry(0.07, 8, 6), white); tail.position.set(-0.62, 0.12, 0); g.add(tail);
    fly.add(g);
    deer.push({ g, legs, phase, head, nose });
  }
  makeDeer(2.7, 0.42, 0, false); makeDeer(2.7, -0.42, 1.3, false);
  makeDeer(4.55, 0.42, 2.6, false); makeDeer(4.55, -0.42, 3.9, true);
  const noseLight = new THREE.PointLight('#ff3030', 14, 9, 1.8); fly.add(noseLight);
  noseLight.position.set(5.0, 0.55, -0.42);
  // tali kekang
  [0.42, -0.42].forEach((z) => {
    const cv = new THREE.CatmullRomCurve3([new THREE.Vector3(1.9, 0.5, z * 0.5), new THREE.Vector3(3.0, 0.45, z), new THREE.Vector3(4.2, 0.5, z)]);
    fly.add(new THREE.Mesh(new THREE.TubeGeometry(cv, 10, 0.014, 4), gold));
  });

  // jalur terbang dan partikel
  const path = new THREE.CatmullRomCurve3([[-28, 5, -6], [-12, 1.5, 0], [2, 4.5, -4], [16, 9.5, -10], [30, 14, -16]].map((a) => new THREE.Vector3(...a)));
  const trail = particlePool(THREE, 350, { tex, size: 0.55, gravity: -0.6, drag: 1.2 });
  scene.add(trail.points);
  const snow = particlePool(THREE, SN, { tex, size: 0.32, gravity: -2.2, drag: 0.3, additive: false });
  scene.add(snow.points);
  const white2 = new THREE.Color('#ffffff');
  for (let i = 0; i < SN; i++) snow.emit((rnd() - 0.5) * 70, (rnd() - 0.4) * 30, 10 - rnd() * 70, (rnd() - 0.5) * 0.6, -1.5 - rnd(), (rnd() - 0.5) * 0.6, white2, 1e9);
  const sparkC = P.accent.clone().lerp(white2, 0.35).multiplyScalar(1.4);

  const tmp = new THREE.Vector3(), tan = new THREE.Vector3();
  return {
    update(tt, dt) {
      const T = tt * duration / 1000;
      const k = 0.04 + 0.6 * easeInOut(tt);
      path.getPoint(k, tmp); path.getTangent(k, tan);
      fly.position.copy(tmp);
      fly.rotation.order = 'YZX';
      fly.rotation.set(0, -Math.atan2(tan.z, tan.x), Math.atan2(tan.y, Math.hypot(tan.x, tan.z)));
      fly.scale.setScalar(1.7);
      sleigh.rotation.x = Math.sin(T * 2.2) * 0.03;
      sleigh.position.y = Math.sin(T * 5) * 0.05;
      const ph = T * 9.5;
      deer.forEach((d) => {
        const a = ph + d.phase;
        d.g.position.y = -0.05 + Math.sin(a * 2) * 0.05;
        d.g.rotation.z = Math.sin(a) * 0.05;
        d.head.rotation.z = -0.15 + Math.sin(a + 1) * 0.1;
        d.legs.forEach((L, i) => {
          const front = i < 2, s = Math.sin(a + (i % 2) * 0.7 + (front ? 0 : 1.9));
          L.hip.rotation.z = (front ? -0.85 : 0.5) * s - (front ? 0.25 : -0.25);
          L.knee.rotation.z = front ? Math.max(0, s) * 1.3 : -Math.max(0, -s) * 1.1 + 0.2;
        });
      });
      fly.updateMatrixWorld(true);
      lantern.position.copy(fly.localToWorld(lamp.position.clone().add(sleigh.position)));
      noseLight.intensity = 12 + Math.sin(T * 6) * 3;
      lampHalo.material.opacity = 0.75 + Math.sin(T * 9) * 0.12;
      // jejak kilau di belakang kereta
      if (dt > 0) for (let i = 0; i < 6; i++) {
        const o = fly.localToWorld(new THREE.Vector3(-2.2 - rnd() * 0.5, 0.2 + rnd() * 0.5, (rnd() - 0.5) * 0.8));
        trail.emit(o.x, o.y, o.z, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, sparkC, 1.1 + rnd() * 0.6);
      }
      trail.update(dt);
      // salju: ulang saat jatuh keluar
      const pos = snow.points.geometry.attributes.position;
      snow.update(dt);
      for (let i = 0; i < SN; i++) {
        if (pos.getY(i) < -14) pos.setY(i, 18);
        pos.setX(i, pos.getX(i) + Math.sin(T * 0.8 + i) * dt * 0.5);
      }
      sf.update(T);
      puff(dt); smoke.update(dt);
      clouds.forEach((c) => { c.az += c.v * dt; skyDir(THREE, c.az, c.el, c.r, c.sp.position); });
      fogs.forEach((f) => { f.m.position.x = Math.sin(T * 0.25 + f.ph) * 6; f.m.material.opacity = f.o * (0.85 + 0.15 * Math.sin(T * 0.7 + f.ph)); });
      for (let i = 0; i < NS; i++) { const k = Math.max(0, Math.sin(T * 3.2 + sph[i] * 3)); { const v = k * k * k; sc[i * 3] = v; sc[i * 3 + 1] = v * 0.95; sc[i * 3 + 2] = v; }; }
      sparkGeo.attributes.color.needsUpdate = true;
      rays.mesh.material.opacity = 0.045 + Math.sin(T * 1.3) * 0.008;
      // kamera: dolly pelan dan geser
      camera.position.set(lerp(-3, 3, tt), 2.8 + tt * 1.2, 17 - tt * 3);
      camera.lookAt(lerp(-1, 2, tt), 5 + tt * 1.5, -6);
    },
    dispose() { [tex, cloudTex, moonTex, rays.tex].forEach((t) => t.dispose()); antlerGeo.dispose(); tineGeo.dispose(); pines.dispose(); bodies.dispose(); roofs.dispose(); chims.dispose(); wins.dispose(); [roofGeo, boxGeo, winGeo, pineGeo, gpGeo, sparkGeo].forEach((g) => g.dispose()); },
  };
}
