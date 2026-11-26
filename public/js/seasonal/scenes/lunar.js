// Imlek: naga Tiongkok (lóng) bertubuh panjang berkelok, berkepala kecil bertanduk rusa, berkumis, dengan mutiara bercahaya, menyapu di atas halaman pesta:
// lampion bertingkat, tiang lampion, atap siluet, jendela potong kertas, kabut, kembang api, koin dan kelopak. Naga dipahat di _b-dragon.js.
import { pal, rng, skyDome, stars, glowTexture, canvasTex, particlePool, easeInOut, smooth, lerp } from '../common.js';
import { merger } from './_b-geom.js';
import { createDragon } from './_b-dragon.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(2568);
  const tex = glowTexture(THREE);
  const K = (h) => new THREE.Color(h);
  const gold = P.accent.clone();
  const owned = [];
  const own = (...o) => { owned.push(...o); return o[0]; };

  scene.fog = new THREE.FogExp2(K('#3a070b'), 0.0034);
  scene.add(skyDome(THREE, [[0.42, K('#8a1018')], [0.5, K('#5e0a10')], [0.7, K('#34060a')], [1, K('#1a0204')]]));
  const st = stars(THREE, 150, rnd, { tex, size: 2.2, minY: 0.4, color: '#ffe9c0' }); scene.add(st.points);
  const moon = new THREE.Mesh(own(new THREE.CircleGeometry(6, 40)), own(new THREE.MeshBasicMaterial({ color: K('#e8c88a'), fog: false })));
  moon.position.set(-46, 28, -110); scene.add(moon);

  scene.add(new THREE.HemisphereLight(K('#ffc9a0'), K('#2a0508'), 0.5));
  const key = new THREE.DirectionalLight(K('#ffe0b0'), 1.3); key.position.set(10, 22, 30); scene.add(key);
  const back = new THREE.DirectionalLight(K('#ffb060'), 1.5); back.position.set(-22, 3, -26); scene.add(back);

  // awan belakang dan kabut rendah (Points besar)
  const cloudTex = canvasTex(THREE, 128, 128, (g, w, h) => {
    for (let i = 0; i < 7; i++) {
      const x = w * (0.25 + rnd() * 0.5), y = h * (0.4 + rnd() * 0.2), r = w * (0.22 + rnd() * 0.18);
      const gr = g.createRadialGradient(x, y, 0, x, y, r); gr.addColorStop(0, 'rgba(255,255,255,0.55)'); gr.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = gr; g.fillRect(0, 0, w, h);
    }
  });
  const mkClouds = (n, x0, x1, y0, y1, z0, z1, size, color, op) => {
    const a = new Float32Array(n * 3);
    for (let i = 0; i < n; i++) a.set([x0 + rnd() * (x1 - x0), y0 + rnd() * (y1 - y0), z0 + rnd() * (z1 - z0)], i * 3);
    const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.BufferAttribute(a, 3));
    const m = new THREE.PointsMaterial({ map: cloudTex, color, size, sizeAttenuation: true, transparent: true, opacity: op, depthWrite: false, fog: false });
    const p = new THREE.Points(g, m); p.frustumCulled = false; scene.add(p); own(g, m);
    return { p, a, n };
  };
  const cBack = mkClouds(22, -90, 90, 0, 20, -70, -30, 55, K('#a83a3a'), 0.22);
  const cMist = mkClouds(16, -60, 60, 0.3, 4, -20, 6, 24, K('#a8403c'), 0.06);

  // gedung ala Tionghoa: siluet gelap, jendela potong kertas menyala
  const winTex = canvasTex(THREE, 256, 256, (g, w, h) => {
    g.fillStyle = '#ff9a2e'; g.fillRect(0, 0, w, h);
    const gr = g.createRadialGradient(w / 2, h / 2, 10, w / 2, h / 2, w * 0.7); gr.addColorStop(0, 'rgba(255,240,170,0.9)'); gr.addColorStop(1, 'rgba(255,120,30,0)');
    g.fillStyle = gr; g.fillRect(0, 0, w, h);
    g.strokeStyle = '#5a0a0a'; g.lineWidth = 6;
    for (let i = 0; i <= 4; i++) { g.beginPath(); g.moveTo(i * 64, 0); g.lineTo(i * 64, h); g.stroke(); g.beginPath(); g.moveTo(0, i * 64); g.lineTo(w, i * 64); g.stroke(); }
    g.fillStyle = '#5a0a0a';
    for (let k = 0; k < 8; k++) { g.save(); g.translate(w / 2, h / 2); g.rotate(k * Math.PI / 4); g.beginPath(); g.ellipse(0, -52, 14, 38, 0, 0, 6.3); g.fill(); g.restore(); }
    g.beginPath(); g.arc(w / 2, h / 2, 20, 0, 6.3); g.fill(); g.fillStyle = '#ffd36a'; g.beginPath(); g.arc(w / 2, h / 2, 9, 0, 6.3); g.fill();
    g.lineWidth = 12; g.strokeStyle = '#3a0508'; g.strokeRect(3, 3, w - 6, h - 6);
  });
  const wallM = own(new THREE.MeshStandardMaterial({ color: '#2e090c', roughness: 0.9 })), roofM = own(new THREE.MeshStandardMaterial({ color: '#1c0608', roughness: 0.7 }));
  const trimM = own(new THREE.MeshStandardMaterial({ color: gold, roughness: 0.4, metalness: 0.6, emissive: gold, emissiveIntensity: 0.3 }));
  const winM = own(new THREE.MeshBasicMaterial({ map: winTex, color: K('#ffd9a8') }));
  // gedung digabung per bahan (4 draw call, bukan puluhan)
  const mWall = merger(THREE), mRoof = merger(THREE), mTrim = merger(THREE), mWin = merger(THREE);
  const building = (x, z, w, h, d, s) => {
    const part = (m, g, px, py, pz, sx, sy, sz, ry = 0) => m.add(g, '#ffffff', x + px * s, py * s, z + pz * s, 0, ry, 0, sx * s, sy * s, sz * s);
    part(mWall, new THREE.BoxGeometry(1, 1, 1), 0, h / 2, 0, w, h, d);
    part(mRoof, new THREE.ConeGeometry(0.75, 1, 4), 0, h + 1.1, 0, w * 1.55, 2.2, d * 1.55, Math.PI / 4);
    part(mTrim, new THREE.BoxGeometry(1, 1, 1), 0, h + 0.05, 0, w * 1.08, 0.35, d * 1.08);
    part(mRoof, new THREE.ConeGeometry(0.75, 1, 4), 0, h + 3.6, 0, w, 2.6, d, Math.PI / 4);
    part(mWin, new THREE.PlaneGeometry(1, 1), -w * 0.22, h * 0.55, d / 2 + 0.05, w * 0.34, h * 0.4, 1);
    part(mWin, new THREE.PlaneGeometry(1, 1), w * 0.22, h * 0.55, d / 2 + 0.05, w * 0.34, h * 0.4, 1);
  };
  [[-62, -70, 1], [-16, -78, 0.9], [10, -80, 1], [60, -74, 1.1], [-84, -60, 1.1], [88, -64, 1], [-38, -52, 1.1], [30, -56, 1], [-52, -40, 1], [52, -42, 1.1], [-22, -64, 0.8], [76, -52, 0.9]]
    .forEach(([x, z, sc2], i) => building(x, z, 8 + (i % 3), 6 + (i % 4) * 2, 8, sc2));
  building(-30, -34, 9, 11, 8, 1.1); building(34, -36, 10, 12, 8, 1.1);
  [[mWall, wallM], [mRoof, roofM], [mTrim, trimM], [mWin, winM]].forEach(([m, mat]) => { const g = own(m.build()); const o = new THREE.Mesh(g, mat); o.frustumCulled = false; scene.add(o); });


  // halaman: batu bata dan genangan cahaya hangat (emissive) di bawah tiang lampion
  const POLES = []; for (let i = 0; i < 9; i++) { POLES.push([-28 + i * 7, 12], [-28 + i * 7 + 3, -6]); }
  const gTex = canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#32090c'; g.fillRect(0, 0, w, h); g.strokeStyle = '#4a1014'; g.lineWidth = 2;
    for (let y = 0; y < h; y += 16) { g.beginPath(); g.moveTo(0, y); g.lineTo(w, y); g.stroke(); for (let x = (y / 16 % 2) * 12; x < w; x += 24) { g.beginPath(); g.moveTo(x, y); g.lineTo(x, y + 16); g.stroke(); } }
  });
  const gEm = canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    POLES.forEach(([x, z]) => {
      const u = (x + 65) / 130 * w, v = (z + 75) / 110 * h, gr = g.createRadialGradient(u, v, 0, u, v, 30); gr.addColorStop(0, 'rgba(255,70,28,0.5)'); gr.addColorStop(1, 'rgba(255,40,16,0)');
      g.save(); g.translate(u, v); g.scale(1.6, 1); g.translate(-u, -v); g.fillStyle = gr; g.fillRect(u - 40, v - 40, 80, 80); g.restore();
    });
  }, true);
  const plaza = new THREE.Mesh(own(new THREE.PlaneGeometry(130, 110)), own(new THREE.MeshStandardMaterial({ map: gTex, emissiveMap: gEm, emissive: K('#ffffff'), emissiveIntensity: 0.45, roughness: 1, metalness: 0 })));
  plaza.rotation.x = -Math.PI / 2; plaza.position.set(0, 0.02, -20); scene.add(plaza);
  const ground = new THREE.Mesh(own(new THREE.PlaneGeometry(2000, 1200)), own(new THREE.MeshStandardMaterial({ color: '#1c0507', roughness: 1 })));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, -0.2, -400); scene.add(ground);

  // lampion gantung tiga lapis (instanced)
  const lanTex = canvasTex(THREE, 256, 128, (g, w, h) => {
    const gr = g.createLinearGradient(0, 0, 0, h); gr.addColorStop(0, '#d8141f'); gr.addColorStop(0.5, '#ff3a2a'); gr.addColorStop(1, '#c3101a');
    g.fillStyle = gr; g.fillRect(0, 0, w, h); g.strokeStyle = '#ffcf5a'; g.lineWidth = 3;
    for (let i = 0; i < 16; i++) { g.beginPath(); g.moveTo(i * 16, 0); g.lineTo(i * 16, h); g.stroke(); }
    g.fillStyle = '#ffcf5a'; for (let i = 0; i < 4; i++) { g.beginPath(); g.arc(i * 64 + 32, h / 2, 12, 0, 6.3); g.fill(); g.fillStyle = '#c3101a'; g.beginPath(); g.arc(i * 64 + 32, h / 2, 6, 0, 6.3); g.fill(); g.fillStyle = '#ffcf5a'; }
  });
  const lanBody = own(new THREE.SphereGeometry(1, 20, 14)), lanCap = own(new THREE.CylinderGeometry(0.5, 0.42, 0.24, 14)), lanCord = own(new THREE.CylinderGeometry(0.025, 0.025, 1, 4)), lanTas = own(new THREE.CylinderGeometry(0.03, 0.06, 1, 5));
  const lanM = own(new THREE.MeshStandardMaterial({ map: lanTex, emissiveMap: lanTex, emissive: K('#ffffff'), emissiveIntensity: 0.7, roughness: 0.5 }));
  const goldM = own(new THREE.MeshStandardMaterial({ color: gold, roughness: 0.3, metalness: 0.85, emissive: gold, emissiveIntensity: 0.2 }));
  const redT = own(new THREE.MeshStandardMaterial({ color: '#e5322d', roughness: 0.6, emissive: '#800', emissiveIntensity: 0.4 }));
  const rows = [[26, 9, -8, 1.5, 30], [29.5, 11, -20, 1.3, 42], [33, 12, -34, 1.2, 56]];
  const lamps = [];
  rows.forEach(([top, n, z, sz, hw], r) => {
    const beam = new THREE.Mesh(own(new THREE.CylinderGeometry(0.1, 0.1, hw * 2.2, 6)), goldM); beam.rotation.z = Math.PI / 2; beam.position.set(0, top, z); scene.add(beam);
    for (let i = 0; i < n; i++) lamps.push({ x: -hw + (i + 0.5) * (hw * 2 / n), top, z, s: sz * (0.85 + ((i * 7 + r) % 3) * 0.15), len: 1.0 + ((i * 5 + r) % 3) * 0.7, ph: i * 1.1 + r * 2 });
  });
  const NL = lamps.length, NP = POLES.length;
  const iBody = new THREE.InstancedMesh(lanBody, lanM, NL + NP), iCap = new THREE.InstancedMesh(lanCap, goldM, NL * 2), iCord = new THREE.InstancedMesh(lanCord, goldM, NL + NP), iTas = new THREE.InstancedMesh(lanTas, redT, NL * 3);
  scene.add(iBody, iCap, iCord, iTas);
  const dm = new THREE.Object3D();
  POLES.forEach(([x, z], i) => { // tiang statis dengan lampion di ujung
    dm.rotation.set(0, 0, 0); dm.position.set(x, 3, z); dm.scale.set(2.5, 6, 2.5); dm.updateMatrix(); iCord.setMatrixAt(NL + i, dm.matrix);
    dm.position.set(x, 6.4, z); dm.scale.set(0.95, 0.85, 0.95); dm.updateMatrix(); iBody.setMatrixAt(NL + i, dm.matrix);
  });
  const haloA = new Float32Array((NL + NP) * 3), haloG = new THREE.BufferGeometry(); haloG.setAttribute('position', new THREE.BufferAttribute(haloA, 3));
  POLES.forEach(([x, z], i) => { haloA.set([x, 6.4, z], (NL + i) * 3); });
  const haloM = own(new THREE.PointsMaterial({ map: tex, color: K('#ff4a30'), size: 6, sizeAttenuation: true, transparent: true, opacity: 0.5, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  const halo = new THREE.Points(own(haloG), haloM); halo.frustumCulled = false; scene.add(halo);
  const lampLight = [-14, 14].map((x) => { const l = new THREE.PointLight(K('#ff4a30'), 30, 30, 1.8); l.position.set(x, 9, 6); scene.add(l); return l; });

  // naga
  const dragon = createDragon(THREE, { scene, quality: ctx.quality || 'high', tex, cloudTex, rnd });


  // koin/kelopak jatuh dan kembang api
  const coinPool = particlePool(THREE, 420, { tex, size: 0.65, gravity: -4.5, drag: 0.15 }); scene.add(coinPool.points);
  const fw = particlePool(THREE, 1100, { tex, size: 2.5, gravity: -7, drag: 0.9 }); scene.add(fw.points);
  const gc = gold.clone().multiplyScalar(1.6), rc = K('#ff4a30').multiplyScalar(1.6), pc = K('#ff9ab0').multiplyScalar(1.3);
  const shots = [[0.3, -34, 32, -90, 0], [0.5, -4, 40, -95, 1], [0.7, 40, 34, -90, 0], [0.88, -26, 30, -85, 1]].map(([t, x, y, z, c]) => ({ t: t * duration / 1000, x, y, z, c, f: false }));

  // vinyet gelap menempel pada kamera supaya siluet naga terbaca
  const vigTex = canvasTex(THREE, 128, 128, (g, w, h) => {
    const gr = g.createRadialGradient(w / 2, h / 2, w * 0.22, w / 2, h / 2, w * 0.72); gr.addColorStop(0, 'rgba(10,0,2,0)'); gr.addColorStop(1, 'rgba(10,0,2,0.75)');
    g.fillStyle = gr; g.fillRect(0, 0, w, h);
  });
  const vig = new THREE.Mesh(own(new THREE.PlaneGeometry(1, 1)), own(new THREE.MeshBasicMaterial({ map: vigTex, transparent: true, depthTest: false, depthWrite: false, fog: false })));
  vig.renderOrder = 999; vig.position.z = -0.6; vig.frustumCulled = false; camera.add(vig); scene.add(camera);

  const lanSet = (T) => {
    for (let i = 0; i < NL; i++) {
      const l = lamps[i], th = Math.sin(T * 1.8 + l.ph) * 0.09, s = l.s, ln = l.len;
      const px = l.x + Math.sin(th) * (ln + s), py = l.top - Math.cos(th) * (ln + s) - s * 0.9;
      dm.rotation.set(Math.sin(T * 1.3 + l.ph) * 0.04, 0, th);
      dm.position.set(l.x + Math.sin(th) * ln / 2, l.top - Math.cos(th) * ln / 2, l.z); dm.scale.set(1, ln, 1); dm.updateMatrix(); iCord.setMatrixAt(i, dm.matrix);
      dm.position.set(px, py, l.z); dm.scale.set(s, s * 0.88, s); dm.updateMatrix(); iBody.setMatrixAt(i, dm.matrix);
      haloA[i * 3] = px; haloA[i * 3 + 1] = py; haloA[i * 3 + 2] = l.z;
      dm.scale.setScalar(s); dm.position.set(px, py + s * 0.86, l.z); dm.updateMatrix(); iCap.setMatrixAt(i * 2, dm.matrix);
      dm.position.set(px, py - s * 0.86, l.z); dm.updateMatrix(); iCap.setMatrixAt(i * 2 + 1, dm.matrix);
      for (let k = -1; k <= 1; k++) { dm.position.set(px + k * 0.12 * s + Math.sin(th) * 0.4, py - s * 0.9 - 0.7 * s, l.z); dm.rotation.set(0, 0, th + k * 0.05); dm.scale.set(s, 1.5 * s, s); dm.updateMatrix(); iTas.setMatrixAt(i * 3 + k + 1, dm.matrix); }
    }
    iBody.instanceMatrix.needsUpdate = iCap.instanceMatrix.needsUpdate = iCord.instanceMatrix.needsUpdate = iTas.instanceMatrix.needsUpdate = true;
    haloG.attributes.position.needsUpdate = true;
  };
  const HS = { x: -24, y: 5, z: -6 }, HE = { x: -1, y: 8.8, z: 6 };

  return {
    update(t, dt) {
      const T = t * duration / 1000, e = 1 - Math.pow(1 - t, 2.6), ramp = smooth(t / 0.25);
      dragon.update(T, dt, lerp(HS.x, HE.x, e) + Math.sin(T * 0.8) * 0.4, lerp(HS.y, HE.y, e) + Math.sin(T * 1.2) * 0.5, lerp(HS.z, HE.z, e), ramp);
      // lampion, awan, partikel, vinyet
      lanSet(T);
      haloM.opacity = (0.4 + 0.1 * Math.sin(T * 4)) * ramp;
      for (let l = 0; l < 2; l++) lampLight[l].intensity = 30 * ramp * (0.95 + 0.05 * Math.sin(T * 5 + l));
      cBack.p.position.x = 0; cMist.p.position.x = 0;
      for (let c = 0; c < 2; c++) { const cl = c ? cMist : cBack; for (let i = 0; i < cl.n; i++) { cl.a[i * 3] += dt * (c ? 1.8 : 1.0); if (cl.a[i * 3] > 100) cl.a[i * 3] = -100; } cl.p.geometry.attributes.position.needsUpdate = true; }
      st.update(T);
      for (let s = 0; s < shots.length; s++) { const sh = shots[s]; if (!sh.f && T >= sh.t) { sh.f = true; fw.burst(sh.x, sh.y, sh.z, sh.c ? rc : gc, 200, 26, 1.9, rnd); fw.burst(sh.x, sh.y, sh.z, sh.c ? pc : gc, 70, 14, 1.4, rnd); } }
      if (dt > 0) for (let i = 0; i < 3; i++) coinPool.emit(-34 + rnd() * 68, 28 + rnd() * 4, -10 + rnd() * 24, (rnd() - 0.5) * 1.4, -2 - rnd() * 2, 0, rnd() < 0.3 ? pc : gc, 5.5);
      coinPool.update(dt); fw.update(dt);
      const vh = 2 * 0.6 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * 1.06; vig.scale.set(vh * camera.aspect, vh, 1);
      const k2 = easeInOut(t);
      const hp = dragon.headPos, ft = lerp(0.35, 0.8, k2);
      const pt = camera.aspect < 1 ? 0.85 : 1; // layar potret: kamera lebih dekat
      camera.position.set(lerp(-3, hp.x + 4 * pt, k2) + Math.sin(T * 0.6) * 0.3, lerp(5, hp.y - 0.5, k2) + (pt < 1 ? 1.2 : 0), lerp(31, 20, k2) * pt + (pt < 1 ? 4 : 0));
      camera.lookAt(lerp(-5, hp.x - (pt < 1 ? 2.5 : 0), pt < 1 ? ft + 0.2 : ft), lerp(9.5, hp.y, ft) - (pt < 1 ? 1.5 : 0), lerp(-2, hp.z, ft * 0.8));
    },
    dragon,
    dispose() { dragon.dispose(); tex.dispose(); cloudTex.dispose(); lanTex.dispose(); winTex.dispose(); gTex.dispose(); gEm.dispose(); vigTex.dispose(); owned.forEach((o) => o.dispose && o.dispose()); [iBody, iCap, iCord, iTas].forEach((m) => m.dispose()); camera.remove(vig); scene.remove(camera); },
  };
}
