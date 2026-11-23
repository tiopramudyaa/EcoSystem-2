// HUT RI: upacara bendera saat fajar. Merah Putih (rasio 2:3) berkibar di tiang tinggi pada kain bergelombang berjalan,
// barisan peserta hormat (paskibraka, pramuka, TNI, veteran, warga, anak sekolah) berinstans, bambu runcing menyilang,
// rumbai merah putih, siluet patung pahlawan berkabut di kejauhan. Pembangun ada di berkas _h-*.js.
import { rng, skyDir, glowTexture, glowSprite, godRays, cloudTexture, mergeParts, easeInOut, smooth, seg, lerp, clamp } from '../common.js';
import { createCloth, flagTexture, swayMaterial } from './_h-cloth.js';
import { OUTFITS, figureMaterial, createCrowd } from './_h-figures.js';
import { flagGrade, dawnSky, groundMesh, horizonMesh, flagpole, bambuRuncing, bunting, bambooPoles, heroStatue, SUN_AZ, SUN_EL } from './_h-set.js';

export function create(THREE, ctx) {
  const { scene, camera, duration, palette } = ctx;
  const low = ctx.quality === 'low';
  const rnd = rng(1708);
  camera.fov = 58; camera.updateProjectionMatrix();
  const K = (h) => new THREE.Color(h);
  const disposers = [];
  const track = (o) => { disposers.push(o); return o; };
  const uni = { uT: { value: 0 } };

  const G = flagGrade(THREE, palette);
  scene.fog = new THREE.FogExp2(K('#e8896a').lerp(G.to, 0.25), 0.0052);
  const sky = dawnSky(THREE, rnd, palette); scene.add(sky); track({ dispose() { sky.geometry.dispose(); sky.material.map.dispose(); sky.material.dispose(); } });

  // matahari terbit di kiri cakrawala
  const SUN = skyDir(THREE, SUN_AZ, SUN_EL, 450);
  const sunDir = SUN.clone().normalize();
  const glow = glowTexture(THREE);
  const sunCore = glowSprite(THREE, glow, K('#fff2d2'), 80, 1); sunCore.position.copy(SUN); scene.add(sunCore);
  const sunHalo = glowSprite(THREE, glow, K('#ff9a60'), 420, 0.55); sunHalo.position.copy(SUN); scene.add(sunHalo);
  const rays = godRays(THREE, rnd, { n: low ? 6 : 9, len: 520, width: 70, color: '#ffc096', opacity: 0.075 });
  rays.mesh.position.copy(SUN); scene.add(rays.mesh);

  // cahaya
  scene.add(new THREE.HemisphereLight(K('#ffb090'), K('#3a1620'), 0.7));
  const sunL = new THREE.DirectionalLight(K('#ffb878'), 3.0); sunL.position.copy(sunDir).multiplyScalar(100); scene.add(sunL);
  const fillL = new THREE.DirectionalLight(K('#ffcdb4'), 1.15); fillL.position.set(14, 16, 44); scene.add(fillL);

  const ground = groundMesh(THREE, rnd); scene.add(ground.mesh); track(ground);
  const hor = horizonMesh(THREE, rnd); scene.add(hor.mesh); track(hor);
  const statue = heroStatue(THREE, { x: -56, z: -92, scale: 11 }); scene.add(statue.group); track(statue);

  // kabut pagi rendah (satu draw call)
  const mistTex = cloudTexture(THREE, rnd); mistTex.wrapS = THREE.RepeatWrapping; track(mistTex);
  const mp = [];
  for (let i = 0; i < 6; i++) mp.push({ geo: new THREE.PlaneGeometry(110, 15), m: new THREE.Matrix4().makeTranslation(-60 + i * 25, 4 + (i % 2) * 2.2, -42 - i * 11) });
  const mistG = mergeParts(THREE, mp); mp.forEach((p) => p.geo.dispose());
  const mist = new THREE.Mesh(mistG, new THREE.MeshBasicMaterial({ map: mistTex, color: K('#ff9878'), transparent: true, opacity: 0.34, depthWrite: false, fog: false }));
  mist.frustumCulled = false; scene.add(mist); track({ dispose() { mistG.dispose(); mist.material.dispose(); } });

  // tiang dan bendera
  const PX = -2, PZ = -3, PH = 12.5;
  const pole = flagpole(THREE, { x: PX, z: PZ, height: PH }); scene.add(pole.group); track(pole);
  const FW = 7.5, FH = 5;
  const ftex = flagTexture(THREE, rnd); track(ftex);
  const flag = createCloth(THREE, { map: ftex, size: [FW, FH], seg: low ? [36, 24] : [56, 36], droop: 1, trans: 0.32 });
  flag.uni.uSunV.value.copy(sunDir);
  scene.add(flag.mesh); track({ dispose() { flag.geometry.dispose(); flag.material.dispose(); } });
  const TOPY = pole.top - 0.7, BOT0 = 2.3 + FH;

  // umbul-umbul di tepi (kain meruncing vertikal, merah kiri putih kanan)
  const umbul = [];
  [[-19.5, -5, 12.5], [18.5, -4, 11.5]].forEach(([x, z, h], i) => {
    const cl = createCloth(THREE, { map: ftex, size: [6.4, 1.15], seg: [34, 6], taper: 0.5, droop: 0, trans: 0.5 });
    cl.uni.uSunV.value.copy(sunDir); cl.uni.uAmp.value = 0.55 + i * 0.1;
    cl.mesh.rotation.z = -Math.PI / 2; cl.mesh.position.set(x + 0.08, h - 0.2, z); scene.add(cl.mesh); umbul.push(cl);
    track({ dispose() { cl.geometry.dispose(); cl.material.dispose(); } });
  });
  const poles = bambooPoles(THREE, [{ x: -19.5, z: -5, h: 13 }, { x: 18.5, z: -4, h: 12 }, ]); scene.add(poles.mesh); track(poles);

  // rumbai (bunting) dari tiang ke tiang bambu
  const bGeo = bunting(THREE, rnd, [
    { a: [PX - 0.12, 11.0, PZ + 0.1], b: [-21.5, 9.4, -5], sag: 1.9, n: low ? 22 : 36 },
    { a: [PX + 0.12, 7.2, PZ + 0.1], b: [19.5, 9.2, -4], sag: 1.5, n: low ? 18 : 28 },
  ]);
  const bMat = swayMaterial(THREE, uni);
  const bMesh = new THREE.Mesh(bGeo, bMat); bMesh.frustumCulled = false; scene.add(bMesh); track({ dispose() { bGeo.dispose(); bMat.dispose(); } });

  // bambu runcing menyilang di kiri dan kanan bingkai
  const spears = bambuRuncing(THREE, rnd, [
    { x: -6.0, z: -1.2, d: 1.6, L: 8.4, th: 0.22 }, { x: 2.0, z: -1.2, d: 1.6, L: 8.4, th: 0.22 },
    { x: 10.6, z: 3.5, d: 1.4, L: 6.2, th: 0.23 },
  ]);
  scene.add(spears.mesh); track(spears);

  // barisan hormat
  const fmat = figureMaterial(THREE, uni, { rim: '#ffc08a', power: 2.1, strength: 1.2, fill: 0.07 }); track(fmat);
  const people = [];
  const pick = (a) => a[Math.floor(rnd() * a.length)];
  const kindAt = (x, row, rows) => {
    if (row === rows - 1 && x > -9 && x < 4) return rnd() < 0.5 ? 'sdM' : 'sdF';
    const r = rnd();
    if (x < -8) return r < 0.8 ? 'tni' : 'veteran';
    if (x < -3.5) return r < 0.72 ? pick(['pramuka', 'pramukaF']) : 'sipil';
    if (x < 3) return r < 0.78 ? pick(['paskibra', 'paskibra', 'paskibraF']) : pick(['sipil', 'sipilF', 'veteran']);
    return r < 0.6 ? pick(['paskibra', 'paskibraF']) : pick(['sipil', 'sipilF', 'pramukaF']);
  };
  const rows = low ? 4 : 5, spacing = 1.42;
  for (let r = 0; r < rows; r++) {
    const z = 1.2 + r * 1.62, x0 = -13.5 + (r % 2) * 0.7;
    for (let c = 0; c < 15; c++) {
      
      const x = x0 + c * spacing + (rnd() - 0.5) * 0.3, zz = z + (rnd() - 0.5) * 0.35;
      const kind = kindAt(x, r, rows), o = OUTFITS[kind];
      const face = Math.PI + (PX - x) * 0.012;
      people.push({ kind, x, z: zz, yaw: face + (rnd() - 0.5) * 0.08, s: ((/F$/.test(kind) ? 0.95 : 0.99) + rnd() * 0.09) * (o.child ? 0.74 : 1), ph: rnd() * 6.28, tone: 0.72 + rnd() * 0.42, bright: 0.9 + rnd() * 0.14, detail: low || r < 2 ? 0 : 1 });
    }
  }
  const crowd = createCrowd(THREE, people, fmat); scene.add(crowd.group); track(crowd);

  // debu cahaya (dust motes) hangat
  const NM = low ? 60 : 150, mA = new Float32Array(NM * 3), mB = new Float32Array(NM * 3);
  for (let i = 0; i < NM; i++) { mB[i * 3] = -22 + rnd() * 44; mB[i * 3 + 1] = 0.8 + rnd() * 6.5; mB[i * 3 + 2] = -22 + rnd() * 38; }
  mA.set(mB);
  const mG = new THREE.BufferGeometry(); mG.setAttribute('position', new THREE.BufferAttribute(mA, 3));
  const mM = new THREE.PointsMaterial({ map: glow, color: K('#ffe2b4'), size: 0.13, sizeAttenuation: true, transparent: true, opacity: 0.5, depthWrite: false, blending: THREE.AdditiveBlending, fog: false });
  const motes = new THREE.Points(mG, mM); motes.frustumCulled = false; scene.add(motes); track({ dispose() { mG.dispose(); mM.dispose(); } });

  // peredam bawah layar (pita judul tetap bersih) dan vinyet halus; menempel di kamera
  const vc = document.createElement('canvas'); vc.width = 8; vc.height = 128;
  { const g = vc.getContext('2d'); const gr = g.createLinearGradient(0, 0, 0, 128);
    gr.addColorStop(0, 'rgba(24,10,16,0.30)'); gr.addColorStop(0.14, 'rgba(24,10,16,0)'); gr.addColorStop(0.62, 'rgba(24,10,16,0)'); gr.addColorStop(0.82, 'rgba(24,10,16,0.6)'); gr.addColorStop(1, 'rgba(18,7,12,0.9)');
    g.fillStyle = gr; g.fillRect(0, 0, 8, 128); }
  const vTex = new THREE.CanvasTexture(vc); vTex.colorSpace = THREE.SRGBColorSpace;
  const vGeo = new THREE.PlaneGeometry(1, 1), vMat = new THREE.MeshBasicMaterial({ map: vTex, transparent: true, depthTest: false, depthWrite: false, fog: false, toneMapped: false });
  const veil = new THREE.Mesh(vGeo, vMat); veil.renderOrder = 999; veil.frustumCulled = false; veil.position.z = -1; camera.add(veil); scene.add(camera);
  track({ dispose() { camera.remove(veil); scene.remove(camera); vGeo.dispose(); vMat.dispose(); vTex.dispose(); } });

  const nrm = new THREE.Vector3();
  return {
    update(t, dt) {
      const T = t * duration / 1000;
      uni.uT.value = T;
      const e = easeInOut(seg(t, 0.03, 0.82)), ramp = smooth(t / 0.18);
      // bendera naik, kibar makin penuh
      flag.uni.uT.value = T; flag.uni.uAmp.value = 0.85 + 0.25 * smooth(seg(t, 0.03, 0.5));
      flag.mesh.position.set(PX + 0.13, lerp(BOT0, TOPY, e), PZ);
      for (let i = 0; i < umbul.length; i++) { umbul[i].uni.uT.value = T + i * 1.7; }
      // debu cahaya
      for (let i = 0; i < NM; i++) {
        mA[i * 3] = mB[i * 3] + Math.sin(T * 0.35 + i) * 0.9 + T * 0.18;
        mA[i * 3 + 1] = mB[i * 3 + 1] + Math.sin(T * 0.5 + i * 1.3) * 0.5;
        mA[i * 3 + 2] = mB[i * 3 + 2];
      }
      mG.attributes.position.needsUpdate = true;
      mM.opacity = 0.34 + 0.1 * Math.sin(T * 1.1);
      mistTex.offset.x = T * 0.004;
      sunCore.material.opacity = 0.95 * ramp; sunHalo.material.opacity = 0.5 * ramp + 0.04 * Math.sin(T * 0.9);
      rays.mesh.material.opacity = (0.06 + 0.014 * Math.sin(T * 0.8)) * ramp;
      // kamera: dolly perlahan, menyesuaikan layar potret
      const asp = camera.aspect, pt = clamp((1.5 - asp) / 0.95), k = easeInOut(t);
      const cx = lerp(lerp(8.5, 6.0, k), 2.6, pt), cy = lerp(lerp(2.1, 2.4, k), 1.8, pt), cz = lerp(lerp(18, 16.2, k), 14, pt);
      camera.position.set(cx + Math.sin(T * 0.5) * 0.06, cy, cz);
      camera.lookAt(lerp(lerp(-3.0, -1.5, k), -1.3, pt), lerp(lerp(3.7, 4.1, k), 6.6, pt), -3);
      camera.updateMatrixWorld();
      const hh = 2 * Math.tan(camera.fov * Math.PI / 360); veil.scale.set(hh * asp, hh, 1);
      // tembus cahaya dihitung di ruang pandang
      camera.matrixWorldInverse.copy(camera.matrixWorld).invert();
      nrm.copy(sunDir).transformDirection(camera.matrixWorldInverse);
      flag.uni.uSunV.value.copy(nrm); for (let i = 0; i < umbul.length; i++) umbul[i].uni.uSunV.value.copy(nrm);
    },
    dispose() { disposers.forEach((d) => d.dispose()); },
  };
}
