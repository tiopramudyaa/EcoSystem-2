// Imlek: naga legenda (tubuh tabung bersisik, kepala bertanduk bercabang, kaki berotot bercakar memegang mutiara api) menyapu diagonal
// di atas halaman pesta: lampion bertingkat, tiang lampion, atap siluet, jendela potong kertas, kabut, kembang api, koin dan kelopak.
import { pal, rng, skyDome, stars, glowTexture, glowSprite, canvasTex, particlePool, easeInOut, smooth, lerp } from '../common.js';
import { ribbon } from './_b-geom.js';
import { sculptHead, EYE, JAW_PIVOT } from './_b-dragonhead.js';

const NR = 120, RAD = 10, G = 2.3, HG = 2.7; // cincin tubuh, sisi, skala tubuh, skala kepala

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
  const cMist = mkClouds(16, -60, 60, 0.3, 4, -20, 6, 24, K('#d0584c'), 0.12);

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
  const bGeo = own(new THREE.BoxGeometry(1, 1, 1)), rGeo = own(new THREE.ConeGeometry(0.75, 1, 4)), pGeo = own(new THREE.PlaneGeometry(1, 1));
  const building = (x, z, w, h, d, s) => {
    const b = new THREE.Group(); b.position.set(x, 0, z);
    const part = (g, m, px, py, pz, sx, sy, sz, ry = 0) => { const o = new THREE.Mesh(g, m); o.position.set(px, py, pz); o.scale.set(sx, sy, sz); o.rotation.y = ry; b.add(o); };
    part(bGeo, wallM, 0, h / 2, 0, w, h, d);
    part(rGeo, roofM, 0, h + 1.1, 0, w * 1.55, 2.2, d * 1.55, Math.PI / 4);
    part(bGeo, trimM, 0, h + 0.05, 0, w * 1.08, 0.35, d * 1.08);
    part(rGeo, roofM, 0, h + 3.6, 0, w, 2.6, d, Math.PI / 4);
    part(pGeo, winM, -w * 0.22, h * 0.55, d / 2 + 0.05, w * 0.34, h * 0.4, 1);
    part(pGeo, winM, w * 0.22, h * 0.55, d / 2 + 0.05, w * 0.34, h * 0.4, 1);
    b.scale.setScalar(s); scene.add(b);
  };
  [[-62, -70, 1], [-16, -78, 0.9], [10, -80, 1], [60, -74, 1.1], [-84, -60, 1.1], [88, -64, 1], [-38, -52, 1.1], [30, -56, 1], [-52, -40, 1], [52, -42, 1.1], [-22, -64, 0.8], [76, -52, 0.9]]
    .forEach(([x, z, sc2], i) => building(x, z, 8 + (i % 3), 6 + (i % 4) * 2, 8, sc2));
  building(-30, -34, 9, 11, 8, 1.1); building(34, -36, 10, 12, 8, 1.1);

  // halaman: batu bata dan genangan cahaya hangat (emissive) di bawah tiang lampion
  const POLES = []; for (let i = 0; i < 9; i++) { POLES.push([-28 + i * 7, 12], [-28 + i * 7 + 3, -6]); }
  const gTex = canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#32090c'; g.fillRect(0, 0, w, h); g.strokeStyle = '#4a1014'; g.lineWidth = 2;
    for (let y = 0; y < h; y += 16) { g.beginPath(); g.moveTo(0, y); g.lineTo(w, y); g.stroke(); for (let x = (y / 16 % 2) * 12; x < w; x += 24) { g.beginPath(); g.moveTo(x, y); g.lineTo(x, y + 16); g.stroke(); } }
  });
  const gEm = canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    POLES.forEach(([x, z]) => {
      const u = (x + 65) / 130 * w, v = (z + 75) / 110 * h, gr = g.createRadialGradient(u, v, 0, u, v, 30); gr.addColorStop(0, 'rgba(255,120,50,0.6)'); gr.addColorStop(1, 'rgba(255,60,20,0)');
      g.save(); g.translate(u, v); g.scale(1.6, 1); g.translate(-u, -v); g.fillStyle = gr; g.fillRect(u - 40, v - 40, 80, 80); g.restore();
    });
  }, true);
  const plaza = new THREE.Mesh(own(new THREE.PlaneGeometry(130, 110)), own(new THREE.MeshStandardMaterial({ map: gTex, emissiveMap: gEm, emissive: K('#ffffff'), emissiveIntensity: 0.55, roughness: 0.6 })));
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

  // naga: tubuh dengan tekstur sisik, perut emas terang
  const scaleTex = canvasTex(THREE, 128, 128, (g, w, h) => {
    g.fillStyle = '#c24a16'; g.fillRect(0, 0, w, h);
    for (let y = 0; y < h + 16; y += 16) for (let x = -16; x < w + 16; x += 32) {
      const ox = ((y / 16) % 2) * 16;
      const gr = g.createRadialGradient(x + ox, y - 4, 1, x + ox, y, 18); gr.addColorStop(0, '#ffcf6a'); gr.addColorStop(0.6, '#e08a22'); gr.addColorStop(1, '#8a2a08');
      g.fillStyle = gr; g.beginPath(); g.arc(x + ox, y, 16, 0, Math.PI); g.fill();
      g.strokeStyle = '#4a1204'; g.lineWidth = 2; g.stroke();
    }
  });
  scaleTex.wrapS = scaleTex.wrapT = THREE.RepeatWrapping; scaleTex.anisotropy = 2;
  const nV = NR * (RAD + 1), tp = new Float32Array(nV * 3), tn = new Float32Array(nV * 3), tu = new Float32Array(nV * 2), tc = new Float32Array(nV * 3), ti = [];
  const cosT = new Float32Array(RAD + 1), sinT = new Float32Array(RAD + 1);
  const red = K('#c81a22'), gBelly = K('#ffd987'), cc = new THREE.Color();
  for (let j = 0; j <= RAD; j++) { const a = j / RAD * Math.PI * 2; cosT[j] = Math.cos(a); sinT[j] = Math.sin(a); }
  for (let i = 0; i < NR; i++) {
    const s = i / (NR - 1);
    for (let j = 0; j <= RAD; j++) {
      const k = i * (RAD + 1) + j;
      tu[k * 2] = s * 46; tu[k * 2 + 1] = j / RAD * 2;
      const bel = smooth((-cosT[j] + 0.1) / 0.55);
      cc.copy(red).lerp(gBelly, bel).multiplyScalar(1 - s * 0.2 + 0.2 * Math.max(0, cosT[j]));
      tc[k * 3] = cc.r; tc[k * 3 + 1] = cc.g; tc[k * 3 + 2] = cc.b;
      if (i < NR - 1 && j < RAD) ti.push(k, k + RAD + 1, k + 1, k + 1, k + RAD + 1, k + RAD + 2);
    }
  }
  const bodyG = own(new THREE.BufferGeometry());
  bodyG.setAttribute('position', new THREE.BufferAttribute(tp, 3)); bodyG.setAttribute('normal', new THREE.BufferAttribute(tn, 3));
  bodyG.setAttribute('uv', new THREE.BufferAttribute(tu, 2)); bodyG.setAttribute('color', new THREE.BufferAttribute(tc, 3)); bodyG.setIndex(ti);
  const bodyM = own(new THREE.MeshStandardMaterial({ map: scaleTex, bumpMap: scaleTex, bumpScale: 2.2, vertexColors: true, roughness: 0.36, metalness: 0.45, emissive: K('#7a1a00'), emissiveIntensity: 0.5 }));
  const body = new THREE.Mesh(bodyG, bodyM); body.frustumCulled = false; scene.add(body);
  const R = {}; ['cx', 'cy', 'cz', 'tx', 'ty', 'tz', 'nx', 'ny', 'nz', 'bx', 'by', 'bz', 'rr'].forEach((k) => { R[k] = new Float32Array(NR); });
  const radius = (s) => G * 1.1 * Math.pow(1 - s, 0.8) * (0.78 + 0.22 * smooth(s / 0.09)) + 0.05 * G;

  // duri punggung emas (instanced)
  const spikeG = own(new THREE.ConeGeometry(0.5, 1, 5)); spikeG.translate(0, 0.5, 0);
  const spikeM = own(new THREE.MeshStandardMaterial({ color: '#ffc23a', emissive: '#c05a08', emissiveIntensity: 0.7, roughness: 0.35, metalness: 0.4 }));
  const SPK = []; for (let i = 3; i < NR - 6; i += 2) SPK.push(i);
  const spikes = new THREE.InstancedMesh(spikeG, spikeM, SPK.length); spikes.frustumCulled = false; scene.add(spikes);

  // kepala: dipahat di _b-dragonhead.js (profil samping)
  const hd = sculptHead(THREE);
  const headG = own(hd.head), jawG = own(hd.jaw);
  const headM = own(new THREE.MeshStandardMaterial({ map: scaleTex, bumpMap: scaleTex, bumpScale: 1.4, vertexColors: true, roughness: 0.4, metalness: 0.3, emissive: K('#4a0808'), emissiveIntensity: 0.5 }));
  const eyeM = own(new THREE.MeshBasicMaterial({ color: K('#ffb81a').multiplyScalar(1.8) })), eyeG = own(new THREE.SphereGeometry(EYE[3], 10, 8));
  const head = new THREE.Group(); head.matrixAutoUpdate = false; scene.add(head);
  head.add(new THREE.Mesh(headG, headM));
  const jaw = new THREE.Group(); jaw.position.set(JAW_PIVOT[0], JAW_PIVOT[1], JAW_PIVOT[2]); jaw.add(new THREE.Mesh(jawG, headM)); head.add(jaw);
  [-1, 1].forEach((s) => { const e = new THREE.Mesh(eyeG, eyeM); e.position.set(EYE[0], EYE[1], s * EYE[2]); e.scale.set(1, 0.8, 0.8); head.add(e); });
  const headLight = new THREE.PointLight(K('#ffcf70'), 60, 26, 1.6); scene.add(headLight);

  // kumis, surai, sirip ekor: pita dinamis dengan keterlambatan terhadap gerak kepala
  const ribM = own(new THREE.MeshBasicMaterial({ color: K('#ffc94a').multiplyScalar(1.2), side: THREE.DoubleSide }));
  const finM = own(new THREE.MeshBasicMaterial({ color: K('#ff5a24').multiplyScalar(1.15), side: THREE.DoubleSide }));
  const mkRib = (n, m) => { const r = ribbon(THREE, n); const o = new THREE.Mesh(r.geo, m); o.frustumCulled = false; scene.add(o); own(r.geo); return r; };
  const WH = [[2.2, -0.05, 0.3, 1, 0.55, 8, 0.16, 0.9], [2.2, -0.05, -0.3, -1, 0.55, 8, 0.16, 2.1], [1.4, -0.55, 0.12, 1, -0.2, 6, 0.2, 3.0], [1.4, -0.55, -0.12, -1, -0.2, 6, 0.2, 4.2], [-0.9, 0.5, 0.25, 1, 0.1, 7, 0.3, 5.1], [-0.9, 0.5, -0.25, -1, 0.1, 7, 0.3, 6.3]]
    .map((d) => ({ d, r: mkRib(14, ribM) }));
  const fins = [0, 1, 2].map((i) => ({ i, r: mkRib(16, finM) }));

  // kaki berotot: lathe meruncing, bertekstur sisik, jumbai di sendi, 4 cakar per kaki
  const limbG = own(new THREE.LatheGeometry([[0.36, 0], [0.55, 0.1], [0.66, 0.3], [0.52, 0.62], [0.4, 1]].map(([x, y]) => new THREE.Vector2(x, y)), 12));
  const pawG = own(new THREE.SphereGeometry(0.5, 10, 8)), talG = own(new THREE.ConeGeometry(0.1, 0.8, 6)); talG.translate(0, 0.4, 0);
  const tuftG = own(new THREE.ConeGeometry(0.22, 1, 5)); tuftG.translate(0, 0.5, 0);
  const limbM = own(new THREE.MeshStandardMaterial({ map: scaleTex, bumpMap: scaleTex, bumpScale: 1.5, color: '#ffb54e', roughness: 0.42, metalness: 0.4, emissive: K('#7a1a00'), emissiveIntensity: 0.5 }));
  const talM = own(new THREE.MeshStandardMaterial({ color: '#f6eed6', roughness: 0.35 })), tuftM = own(new THREE.MeshStandardMaterial({ color: '#ff8a1e', emissive: '#a03a00', emissiveIntensity: 0.7, roughness: 0.5 }));
  const iLimb = new THREE.InstancedMesh(limbG, limbM, 8), iPaw = new THREE.InstancedMesh(pawG, limbM, 4), iTal = new THREE.InstancedMesh(talG, talM, 16), iTuft = new THREE.InstancedMesh(tuftG, tuftM, 24);
  [iLimb, iPaw, iTal, iTuft].forEach((m) => { m.frustumCulled = false; scene.add(m); });
  const LEGS = [{ ring: 4, side: 1, fore: true }, { ring: 4, side: -1, fore: true }, { ring: 46, side: 1 }, { ring: 46, side: -1 }];
  const L1 = 6.6, L2 = 6.0;

  // mutiara api
  const pearlM = own(new THREE.MeshBasicMaterial({ color: K('#fff0c0').multiplyScalar(1.6) })), pearl = new THREE.Mesh(own(new THREE.SphereGeometry(0.75, 20, 14)), pearlM); scene.add(pearl);
  const pg1 = glowSprite(THREE, tex, K('#ffb347'), 5, 0.45), pg2 = glowSprite(THREE, tex, K('#ff6a1a'), 9.5, 0.16); scene.add(pg1, pg2);
  const pLight = new THREE.PointLight(K('#ffb347'), 45, 26, 1.6); scene.add(pLight);
  const flame = particlePool(THREE, 320, { tex, size: 1.6, gravity: 3.5, drag: 1.2 }); scene.add(flame.points);
  const fc1 = K('#ff7a1a').multiplyScalar(1.5), fc2 = K('#ffd36a').multiplyScalar(1.6);

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

  const v1 = new THREE.Vector3(), v2 = new THREE.Vector3(), v3 = new THREE.Vector3(), v4 = new THREE.Vector3(), v5 = new THREE.Vector3(), v6 = new THREE.Vector3();
  const mx = new THREE.Matrix4(), qa = new THREE.Quaternion(), UPV = new THREE.Vector3(0, 1, 0), sc = new THREE.Vector3();
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

  // tulang punggung: kepala memimpin, gelombang S merambat ke ekor, ayunan ekor membesar
  function spine(hx, hy, hz, T) {
    for (let i = 0; i < NR; i++) {
      const s = i / (NR - 1), env = smooth(s * 4) * (0.45 + 1.0 * s);
      R.cx[i] = hx - 42 * s + 2.4 * Math.sin(s * 7.5 - T * 2.0) * env;
      R.cy[i] = Math.max(3, hy - 11 * s + 4.6 * Math.sin(s * 8.5 - T * 2.4 + 1) * env + 0.8 * Math.sin(s * 3 - T * 1.1));
      R.cz[i] = hz - 10 * s + 4.6 * Math.cos(s * 6.5 - T * 1.7) * env;
      R.rr[i] = radius(s);
    }
    for (let i = 0; i < NR; i++) {
      const p = Math.max(i - 1, 0), n = Math.min(i + 1, NR - 1);
      v1.set(R.cx[p] - R.cx[n], R.cy[p] - R.cy[n], R.cz[p] - R.cz[n]).normalize();
      v2.crossVectors(v1, UPV); if (v2.lengthSq() < 1e-4) v2.set(0, 0, 1); v2.normalize();
      v3.crossVectors(v2, v1);
      R.tx[i] = v1.x; R.ty[i] = v1.y; R.tz[i] = v1.z; R.bx[i] = v2.x; R.by[i] = v2.y; R.bz[i] = v2.z; R.nx[i] = v3.x; R.ny[i] = v3.y; R.nz[i] = v3.z;
      const r = R.rr[i];
      for (let j = 0; j <= RAD; j++) {
        const k = (i * (RAD + 1) + j) * 3, c = cosT[j], sn = sinT[j];
        const dx = c * v3.x + sn * v2.x, dy = c * v3.y + sn * v2.y, dz = c * v3.z + sn * v2.z;
        tp[k] = R.cx[i] + dx * r; tp[k + 1] = R.cy[i] + dy * r; tp[k + 2] = R.cz[i] + dz * r;
        tn[k] = dx; tn[k + 1] = dy; tn[k + 2] = dz;
      }
    }
    bodyG.attributes.position.needsUpdate = true; bodyG.attributes.normal.needsUpdate = true;
  }
  let ctxT = 0;
  const pitchHead = () => { // moncong sedikit terangkat; v1 depan, v2 atas
    const a = 0.12 + 0.04 * Math.sin(ctxT * 1.3), c = Math.cos(a), sn = Math.sin(a);
    v1.set(R.tx[0] * c + R.nx[0] * sn, R.ty[0] * c + R.ny[0] * sn, R.tz[0] * c + R.nz[0] * sn);
    v2.set(R.nx[0] * c - R.tx[0] * sn, R.ny[0] * c - R.ty[0] * sn, R.nz[0] * c - R.tz[0] * sn);
  };
  const ik = (S, Tg, pole, out) => {
    v4.subVectors(Tg, S); const d = Math.min(v4.length(), L1 + L2 - 0.05); v4.normalize();
    const a = (L1 * L1 - L2 * L2 + d * d) / (2 * d), h = Math.sqrt(Math.max(0.01, L1 * L1 - a * a));
    v5.copy(pole).addScaledVector(v4, -pole.dot(v4)).normalize();
    out.copy(S).addScaledVector(v4, a).addScaledVector(v5, h);
  };
  const SH = new THREE.Vector3(), EL = new THREE.Vector3(), TG = new THREE.Vector3(), PL = new THREE.Vector3(), PE = new THREE.Vector3(), HEADP = new THREE.Vector3(), PREV = new THREE.Vector3(), VEL = new THREE.Vector3(), SD = new THREE.Vector3();
  const place = (idx, A, B, thick) => { v1.subVectors(B, A); const len = v1.length(); qa.setFromUnitVectors(UPV, v1.normalize()); mx.compose(A, qa, sc.set(thick, len, thick)); iLimb.setMatrixAt(idx, mx); };
  let tuftN = 0;
  const tufts = (J, dir, pole, big) => { // 3 jumbai menyebar dari sendi
    SD.crossVectors(dir, pole).normalize();
    for (let k = -1; k <= 1; k++) { v6.copy(pole).addScaledVector(dir, -0.5).addScaledVector(SD, k * 0.55).normalize(); qa.setFromUnitVectors(UPV, v6); mx.compose(J, qa, sc.set(big, big * 2.6, big)); iTuft.setMatrixAt(tuftN++, mx); }
  };
  const HS = { x: -24, y: 5, z: -6 }, HE = { x: -1, y: 8.8, z: 6 };

  return {
    update(t, dt) {
      const T = t * duration / 1000, e = 1 - Math.pow(1 - t, 2.6), ramp = smooth(t / 0.25);
      ctxT = T;
      spine(lerp(HS.x, HE.x, e) + Math.sin(T * 0.8) * 0.4, lerp(HS.y, HE.y, e) + Math.sin(T * 1.2) * 0.5, lerp(HS.z, HE.z, e), T);
      // kepala
      pitchHead(); v3.set(R.bx[0], R.by[0], R.bz[0]);
      HEADP.set(R.cx[0], R.cy[0], R.cz[0]).addScaledVector(v1, 0.6);
      if (dt > 0) { v6.subVectors(HEADP, PREV).divideScalar(dt).clampLength(0, 40); VEL.lerp(v6, Math.min(1, dt * 6)); }
      PREV.copy(HEADP);
      head.matrix.makeBasis(v1, v2, v3); head.matrix.scale(sc.set(HG, HG, HG)); head.matrix.setPosition(HEADP); head.matrixWorldNeedsUpdate = true;
      jaw.rotation.z = -(0.2 + 0.05 * Math.sin(T * 3.2)); head.updateMatrixWorld(true);
      headLight.position.copy(HEADP).addScaledVector(v2, 3); headLight.intensity = 60 * ramp;
      // duri punggung
      for (let q = 0; q < SPK.length; q++) {
        const i = SPK[q], r = R.rr[i];
        v4.set(R.nx[i] - R.tx[i] * 0.7, R.ny[i] - R.ty[i] * 0.7, R.nz[i] - R.tz[i] * 0.7).normalize();
        v5.set(R.bx[i], R.by[i], R.bz[i]); v6.crossVectors(v4, v5);
        mx.makeBasis(v6, v4, v5); const h = r * 1.1 + 0.25 * G, w = r * 0.3 + 0.1 * G; mx.scale(sc.set(w, h, w));
        mx.setPosition(R.cx[i] + R.nx[i] * r * 0.92, R.cy[i] + R.ny[i] * r * 0.92, R.cz[i] + R.nz[i] * r * 0.92); spikes.setMatrixAt(q, mx);
      }
      spikes.instanceMatrix.needsUpdate = true;
      // mutiara di depan moncong
      TG.copy(HEADP).addScaledVector(v1, 3.5 * HG).addScaledVector(v2, -1.0 * HG - Math.sin(T * 3) * 0.15);
      pearl.position.copy(TG); pg1.position.copy(TG); pg2.position.copy(TG); pLight.position.copy(TG); pLight.intensity = 45 * ramp * (0.9 + 0.1 * Math.sin(T * 9));
      pg1.material.opacity = 0.4 + 0.05 * Math.sin(T * 7);
      if (dt > 0) for (let i = 0; i < 5; i++) { const a = rnd() * 6.28; flame.emit(TG.x + Math.cos(a) * 0.9, TG.y + rnd() * 0.3, TG.z + Math.sin(a) * 0.9, Math.cos(a) * 0.8, 1.5 + rnd() * 2, Math.sin(a) * 0.8, rnd() < 0.5 ? fc1 : fc2, 0.7 + rnd() * 0.5); }
      flame.update(dt);
      // kaki
      tuftN = 0;
      for (let q = 0; q < 4; q++) {
        const lg = LEGS[q], i = lg.ring;
        v1.set(R.tx[i], R.ty[i], R.tz[i]); v2.set(R.nx[i], R.ny[i], R.nz[i]); v3.set(R.bx[i], R.by[i], R.bz[i]);
        SH.set(R.cx[i], R.cy[i], R.cz[i]).addScaledVector(v3, lg.side * R.rr[i] * 0.8).addScaledVector(v2, -R.rr[i] * 0.45);
        if (lg.fore) { PE.copy(TG).addScaledVector(v3, lg.side * 0.85).addScaledVector(v2, -0.1); PL.copy(v2).multiplyScalar(-0.5).addScaledVector(v3, lg.side * 1.0); }
        else { PE.copy(SH).addScaledVector(v3, lg.side * 5.2).addScaledVector(v1, -2.4 + Math.sin(T * 2.2 + q) * 0.9).addScaledVector(v2, -4.0 + Math.sin(T * 1.7 + q * 2) * 0.9); PL.copy(v2).addScaledVector(v1, 0.3); }
        ik(SH, PE, PL, EL);
        place(q * 2, SH, EL, 1.0); place(q * 2 + 1, EL, PE, 0.75);
        v4.subVectors(EL, SH).normalize(); if (!lg.fore) tufts(EL, v4, PL, 0.5);
        v1.subVectors(PE, EL).normalize(); v5.copy(PL).addScaledVector(v1, -PL.dot(v1)).normalize(); // pole tegak lurus lengan bawah
        if (!lg.fore) tufts(PE, v1, v5, 0.4);
        mx.compose(PE, qa.identity(), sc.set(0.8, 0.7, 0.8)); iPaw.setMatrixAt(q, mx);
        v4.crossVectors(v1, v5).normalize();
        for (let k = 0; k < 4; k++) { // empat cakar menggenggam mutiara (kaki depan) atau melengkung ke depan-bawah
          if (lg.fore) v6.subVectors(TG, PE).normalize().multiplyScalar(0.9).addScaledVector(v4, (k - 1.5) * 0.5);
          else v6.copy(v1).multiplyScalar(0.7).addScaledVector(v4, (k - 1.5) * 0.5).addScaledVector(v5, -0.5);
          v6.normalize(); qa.setFromUnitVectors(UPV, v6); mx.compose(v3.copy(PE).addScaledVector(v1, 0.35), qa, sc.set(1.0, 1.2, 1.0)); iTal.setMatrixAt(q * 4 + k, mx);
        }
      }
      iLimb.instanceMatrix.needsUpdate = iPaw.instanceMatrix.needsUpdate = iTal.instanceMatrix.needsUpdate = iTuft.instanceMatrix.needsUpdate = true;
      // pita dari kepala (terlambat mengikuti kecepatan kepala) dan sirip ekor
      pitchHead(); v3.set(R.bx[0], R.by[0], R.bz[0]);
      for (let w = 0; w < WH.length; w++) {
        const { d, r } = WH[w], lx = d[0], ly = d[1], lz = d[2], sg = d[3], outw = d[4], len = d[5], wd = d[6], ph = d[7];
        v4.copy(HEADP).addScaledVector(v1, lx * HG).addScaledVector(v2, ly * HG).addScaledVector(v3, lz * HG);
        for (let j = 0; j < 14; j++) {
          const u = j / 13, wv = Math.sin(T * 3.4 - u * 6 + ph) * 0.9 * u, wv2 = Math.sin(T * 2.3 - u * 4 + ph * 1.7) * 0.7 * u, k = 1 - u * 0.6;
          v5.copy(v4).addScaledVector(v1, -u * len * 0.8 + 0.1 * u * len).addScaledVector(v3, sg * outw * u * 3.2 * (1 - u * 0.4) + wv2).addScaledVector(v2, wv - u * u * 2.2 + 0.5 * u);
          v5.addScaledVector(VEL, -0.1 * u * u);
          r.set(j, v5.x, v5.y, v5.z, v2.x * wd * k, v2.y * wd * k, v2.z * wd * k);
        }
        r.commit();
      }
      const tl = NR - 1; v1.set(-R.tx[tl], -R.ty[tl], -R.tz[tl]); v3.set(R.bx[tl], R.by[tl], R.bz[tl]); v2.set(R.nx[tl], R.ny[tl], R.nz[tl]);
      for (let f = 0; f < 3; f++) {
        const r = fins[f].r, i = fins[f].i, L = 12 - Math.abs(i - 1) * 2.2, sp = (i - 1) * 1.1;
        for (let j = 0; j < 16; j++) {
          const u = j / 15, wd = Math.pow(Math.sin(Math.PI * Math.min(1, u * 1.05)), 0.8) * 1.7;
          v5.set(R.cx[tl], R.cy[tl], R.cz[tl]).addScaledVector(v1, u * L).addScaledVector(v3, sp * u + Math.sin(T * 3.1 - u * 5 + i) * 1.5 * u * u).addScaledVector(v2, Math.sin(T * 2.4 - u * 4 + i * 2) * 1.3 * u * u + u * u * 1.0);
          r.set(j, v5.x, v5.y, v5.z, v2.x * wd, v2.y * wd, v2.z * wd);
        }
        r.commit();
      }
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
      camera.position.set(lerp(-3, 1, k2) + Math.sin(T * 0.6) * 0.3, lerp(5, 7.5, k2), lerp(36, 31, k2));
      camera.lookAt(lerp(-5, 2, k2), lerp(9.5, 11.5, k2), -2);
    },
    dispose() { tex.dispose(); cloudTex.dispose(); scaleTex.dispose(); lanTex.dispose(); winTex.dispose(); gTex.dispose(); gEm.dispose(); vigTex.dispose(); owned.forEach((o) => o.dispose && o.dispose()); [iBody, iCap, iCord, iTas, spikes, iLimb, iPaw, iTal, iTuft].forEach((m) => m.dispose()); camera.remove(vig); scene.remove(camera); },
  };
}
