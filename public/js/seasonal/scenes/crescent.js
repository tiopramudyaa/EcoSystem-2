// Ramadan: senja menjelang malam di kolam pantul. Masjid berkubah besar dengan empat setengah kubah dan empat menara,
// hilal tipis berfase asli dengan satu bintang kejora, bintang, palem siluet, lentera fanous menyala yang bergoyang pelan,
// pantulan di air tenang. Semua geometri prosedural, tanpa shadow map. Modul pembantu: _r-mosque, _r-palms, _r-lantern, _r-tex.
import { pal, rng, glowTexture, canvasTex, paintedSky, starField, cloudTexture, moonTexture, phasedMoon, mergeParts, skyDir, easeInOut, lerp, smooth, seg } from '../common.js';
import { buildMosque } from './_r-mosque.js';
import { buildPalmGeometry } from './_r-palms.js';
import { buildLanterns } from './_r-lantern.js';
import { palmTexture } from './_r-tex.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const low = ctx.quality === 'low';
  const P = pal(THREE, palette);
  const rnd = rng(1445);
  const tex = glowTexture(THREE);
  const cloudTex = cloudTexture(THREE, rnd);
  const own = [];
  const K = (h) => new THREE.Color(h);
  const gold = P.accent.clone();

  scene.fog = new THREE.FogExp2(K('#0c2a3e'), 0.0036);
  const sky = paintedSky(THREE, rnd, {
    stops: [[0, K('#3d7488')], [0.05, K('#1f5170')], [0.22, K('#0f2f5e')], [0.6, K('#09154a')], [1, K('#030719')]],
    milky: { az0: 0.1, el0: 0.35, az1: 0.9, el1: 0.95, width: 70, alpha: 0.045, color: '200,225,255' },
    paint: (g, m) => {
      // sisa cahaya senja: pita hangat tipis di horizon sisi kiri bulan
      const x = m.x(-0.55), y = m.y(0.0), r = 380;
      const rg = g.createRadialGradient(x, y, 0, x, y, r); rg.addColorStop(0, 'rgba(255,170,90,0.34)'); rg.addColorStop(0.35, 'rgba(240,120,90,0.14)'); rg.addColorStop(1, 'rgba(200,100,120,0)');
      g.save(); g.scale(1, 0.34); g.translate(0, y * (1 / 0.34 - 1)); g.fillStyle = rg; g.fillRect(x - r, y - r, r * 2, r * 2); g.restore();
    },
  });
  scene.add(sky);
  const mirror = new THREE.Group(); mirror.scale.y = -1; scene.add(mirror);
  const skyM = new THREE.Mesh(sky.geometry, sky.material); skyM.renderOrder = -10; skyM.frustumCulled = false; mirror.add(skyM);
  const sf = starField(THREE, rnd, low ? 600 : 1100, { tex, minEl: 0.05, size: 1.6, color: '#fff6e0' });
  scene.add(sf.group);
  sf.group.children.forEach((c) => { const m = new THREE.Points(c.geometry, c.material.clone()); m.material.opacity = 0.3; m.frustumCulled = false; mirror.add(m); });

  // ---- hilal: bola berfase dengan terminator lembut; bintang kejora di dekatnya ----
  const moonTex = moonTexture(THREE, rnd, { base: '#e2d8bd' });
  const MAZ = -0.4, MEL = 0.38;
  const moonPos = skyDir(THREE, MAZ, MEL, 440);
  const moon = phasedMoon(THREE, moonTex, { radius: 24, sunDir: new THREE.Vector3(0.3, -0.52, -1.3), tint: '#ffe6ad', earth: 0.012, gain: 2.8 });
  moon.position.copy(moonPos); scene.add(moon);
  const sprite = (map, c, s, o, p, k = 1) => { const m = new THREE.Sprite(new THREE.SpriteMaterial({ map, color: c, transparent: true, opacity: o, depthWrite: false, blending: THREE.AdditiveBlending, fog: false })); m.scale.set(s, s * k, 1); m.position.copy(p); scene.add(m); return m; };
  const halo = sprite(tex, '#ffd58a', 150, 0.4, moonPos.clone().multiplyScalar(1.012));
  sprite(tex, '#5fa6c8', 400, 0.1, moonPos.clone().multiplyScalar(1.03));
  const starTex = canvasTex(THREE, 128, 128, (g, w, h) => {
    const rg = g.createRadialGradient(64, 64, 0, 64, 64, 64); rg.addColorStop(0, 'rgba(255,255,255,1)'); rg.addColorStop(0.08, 'rgba(255,248,225,0.95)'); rg.addColorStop(0.3, 'rgba(255,225,170,0.25)'); rg.addColorStop(1, 'rgba(255,225,170,0)');
    g.fillStyle = rg; g.fillRect(0, 0, w, h);
    g.globalCompositeOperation = 'lighter';
    for (const [dx, dy, L] of [[1, 0, 62], [0, 1, 62], [0.7, 0.7, 24], [0.7, -0.7, 24]]) {
      const lg = g.createLinearGradient(64 - dx * L, 64 - dy * L, 64 + dx * L, 64 + dy * L); lg.addColorStop(0, 'rgba(255,240,200,0)'); lg.addColorStop(0.5, 'rgba(255,245,215,0.85)'); lg.addColorStop(1, 'rgba(255,240,200,0)');
      g.strokeStyle = lg; g.lineWidth = L > 30 ? 1.6 : 1; g.beginPath(); g.moveTo(64 - dx * L, 64 - dy * L); g.lineTo(64 + dx * L, 64 + dy * L); g.stroke();
    }
  });
  const kejora = sprite(starTex, '#fff3d6', 34, 0.95, skyDir(THREE, MAZ + 0.145, MEL + 0.055, 438));
  const streak = sprite(tex, '#ffd58a', 60, 0.22, new THREE.Vector3(moonPos.x, -moonPos.y * 0.98, moonPos.z), 3.4);
  const clouds = [];
  for (let i = 0; i < 6; i++) {
    const az = -0.9 + rnd() * 1.8, el = 0.08 + rnd() * 0.5, r = 400 + rnd() * 25, w = r * (0.25 + rnd() * 0.3), near = Math.max(0, 1 - Math.hypot(az - MAZ, el - MEL) / 0.5);
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: K('#4d7aa0').lerp(K('#ffe0b0'), near * 0.5), transparent: true, opacity: 0.18 + near * 0.18, depthWrite: false, fog: false }));
    sp.scale.set(w, w * 0.26, 1); skyDir(THREE, az, el, r, sp.position); scene.add(sp); clouds.push({ sp, az, el, r, v: (rnd() - 0.3) * 0.01 });
  }

  // ---- cahaya ----
  scene.add(new THREE.HemisphereLight(K('#6d8cc4'), K('#0a2a33'), 0.5));
  const moonL = new THREE.DirectionalLight('#b0c8ff', 0.95); moonL.position.set(-40, 55, 35); scene.add(moonL);
  const fillL = new THREE.DirectionalLight('#8ea8d8', 0.35); fillL.position.set(20, 10, 60); scene.add(fillL);
  const warm = [0].map((x) => { const l = new THREE.PointLight('#ffb561', 300, 80, 1.6); l.position.set(x, 4.5, 34); scene.add(l); return l; });
  const lanL = [new THREE.PointLight('#ffa845', 110, 36, 1.6)];
  lanL.forEach((l) => scene.add(l));

  // ---- air tenang ----
  const water = new THREE.Mesh(new THREE.PlaneGeometry(1600, 700), new THREE.MeshBasicMaterial({ color: '#06283a', transparent: true, opacity: 0.62, fog: false }));
  water.rotation.x = -Math.PI / 2; water.position.set(0, 0, -250); scene.add(water); own.push(water.geometry, water.material);
  // riak halus: bidang aditif bergulir pelan
  const rippleTex = canvasTex(THREE, 256, 256, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    for (let i = 0; i < 70; i++) { const y = rnd() * h, x = rnd() * w, L = 14 + rnd() * 50; g.strokeStyle = `rgba(150,190,230,${0.12 + rnd() * 0.25})`; g.lineWidth = 1.2; g.beginPath(); g.moveTo(x, y); g.quadraticCurveTo(x + L / 2, y + (rnd() - 0.5) * 3, x + L, y); g.stroke(); }
  });
  rippleTex.wrapS = rippleTex.wrapT = THREE.RepeatWrapping; rippleTex.repeat.set(30, 16); own.push(rippleTex);
  const ripple = new THREE.Mesh(new THREE.PlaneGeometry(300, 160), new THREE.MeshBasicMaterial({ map: rippleTex, transparent: true, opacity: 0.2, blending: THREE.AdditiveBlending, depthWrite: false, fog: true }));
  ripple.rotation.x = -Math.PI / 2; ripple.position.set(0, 0.05, 60); ripple.renderOrder = 3; scene.add(ripple); own.push(ripple.geometry, ripple.material);
  // bukit jauh berlapis kabut
  const hp = [];
  for (let L = 0; L < 2; L++) for (let i = 0; i < 12; i++) {
    const g = new THREE.SphereGeometry(36 + rnd() * 30, 10, 6, 0, Math.PI * 2, 0, Math.PI / 2);
    hp.push({ geo: g, m: new THREE.Matrix4().compose(new THREE.Vector3(-420 + i * 78 + rnd() * 30, -1, -250 - L * 70), new THREE.Quaternion(), new THREE.Vector3(1.4, 0.3 + rnd() * 0.25, 1)), color: L ? '#0d3441' : '#0a2e3a' });
  }
  const hillG = mergeParts(THREE, hp); hp.forEach((p) => p.geo.dispose()); own.push(hillG);
  const hillMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 1 }); own.push(hillMat);
  scene.add(new THREE.Mesh(hillG, hillMat));
  mirror.add(new THREE.Mesh(hillG, new THREE.MeshBasicMaterial({ vertexColors: true, color: '#8aa' })));

  // ---- masjid ----
  const mq = buildMosque(THREE, rnd, { detail: low ? 'far' : 'full', gold: '#e6c36a', mirror: true });
  const MS = 0.8;
  mq.group.scale.setScalar(MS); mq.group.position.set(0, 0, 0); scene.add(mq.group);
  mq.mirror.scale.setScalar(MS); mirror.add(mq.mirror);

  // ---- palem siluet (di alas masjid) ----
  const palmTex = palmTexture(THREE, rnd); own.push(palmTex);
  const palmSpecs = [[-33.5, 1.2, 8, 19, -0.1], [-31, 1.2, -10, 24, -0.14], [33.5, 1.2, 6, 21, 0.1], [31, 1.2, -9, 26, 0.12], [-27, 1.2, 22, 15, -0.08], [28, 1.2, 21, 16, 0.08]]
    .map(([x, y, z, h, lean], i) => ({ x, y, z, h, lean, rot: i * 1.3 }));
  const palmG = buildPalmGeometry(THREE, rnd, palmSpecs, { fronds: low ? 9 : 13 }); own.push(palmG);
  const palmMat = new THREE.MeshStandardMaterial({ map: palmTex, vertexColors: true, alphaTest: 0.4, side: THREE.DoubleSide, roughness: 1, metalness: 0 }); own.push(palmMat);
  scene.add(new THREE.Mesh(palmG, palmMat));
  const palmMir = new THREE.MeshBasicMaterial({ map: palmTex, color: '#0a1a28', alphaTest: 0.4, side: THREE.DoubleSide }); own.push(palmMir);
  mirror.add(new THREE.Mesh(palmG, palmMir));

  // lampu kota jauh di tepi seberang
  const NS = low ? 70 : 160, sp = new Float32Array(NS * 3), sc = new Float32Array(NS * 3);
  for (let i = 0; i < NS; i++) { const cl = rnd() < 0.6 ? -1 : 1, x = cl * (60 + rnd() * rnd() * 520) + (rnd() - 0.5) * 30; sp[i * 3] = x; sp[i * 3 + 1] = 1.5 + rnd() * 5; sp[i * 3 + 2] = -205 - rnd() * 20; const k = 0.4 + rnd() * 0.6, w = rnd() < 0.25 ? K('#cfe6ff') : K('#ffc477'); sc[i * 3] = w.r * k; sc[i * 3 + 1] = w.g * k; sc[i * 3 + 2] = w.b * k; }
  const shG = new THREE.BufferGeometry(); shG.setAttribute('position', new THREE.BufferAttribute(sp, 3)); shG.setAttribute('color', new THREE.BufferAttribute(sc, 3)); own.push(shG);
  const shM = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 6, sizeAttenuation: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(shM);
  const shP = new THREE.Points(shG, shM); shP.frustumCulled = false; scene.add(shP);
  const shMM = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 6, sizeAttenuation: true, transparent: true, opacity: 0.4, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(shMM);
  const shPM = new THREE.Points(shG, shMM); shPM.frustumCulled = false; mirror.add(shPM);

  // ---- tali dan lentera fanous ----
  const ropeCurve = new THREE.CatmullRomCurve3([-70, -35, 0, 35, 70].map((x) => new THREE.Vector3(x, 25 + 12 * Math.pow(x / 70, 2) * 1.0 + (Math.abs(x) < 36 ? 0 : 0), 36, 0)).map((v) => { v.z = 40; return v; }));
  const ropeAt = (x) => 25.5 + 11.5 * Math.pow(x / 70, 2);
  const ropePts = []; for (let i = 0; i <= 40; i++) { const x = -75 + i * 3.75; ropePts.push(new THREE.Vector3(x, ropeAt(x), 40)); }
  const ropeG = new THREE.TubeGeometry(new THREE.CatmullRomCurve3(ropePts), 60, 0.07, 4); own.push(ropeG);
  const ropeM = new THREE.MeshStandardMaterial({ color: '#4a3a20', roughness: 0.7, emissive: '#2a1c08', emissiveIntensity: 0.5 }); own.push(ropeM);
  scene.add(new THREE.Mesh(ropeG, ropeM));
  void ropeCurve;
  const LN = [[-8.5, 5.8, 1.0, '#ffd9a0'], [-1.5, 4.0, 1.5, '#ffffff'], [7, 6.4, 0.95, '#ff9f8a'], [14.5, 3.4, 1.3, '#ffffff'], [22, 5.2, 1.1, '#ffe2a8'], [-26, 5.4, 1.05, '#ffe2a8'], [30, 3.2, 1.2, '#ffffff']]
    .map(([x, len, s, tint], i) => ({ x, y: ropeAt(x), z: 40, len, s, tint, ph: i * 1.7 }));
  LN.push({ x: -14.5, y: 30, z: 60, len: 12, s: 1.35, tint: '#ffffff', ph: 3.1 }, { x: 12, y: 30, z: 58, len: 12.5, s: 1.2, tint: '#ffe2a8', ph: 5.2 });
  const lan = buildLanterns(THREE, LN, { glow: tex });
  scene.add(lan.group); mirror.add(lan.mirrorHalo);
  // bohlam kecil sepanjang tali
  const NB = 70, bp = new Float32Array(NB * 3), bc = new Float32Array(NB * 3), bph = new Float32Array(NB);
  for (let i = 0; i < NB; i++) { const x = -68 + i * (136 / (NB - 1)); bp[i * 3] = x; bp[i * 3 + 1] = ropeAt(x) - 0.25; bp[i * 3 + 2] = 40.1; bph[i] = rnd() * 6.28; }
  const bg = new THREE.BufferGeometry(); bg.setAttribute('position', new THREE.BufferAttribute(bp, 3)); bg.setAttribute('color', new THREE.BufferAttribute(bc, 3)); own.push(bg);
  const bm = new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 2.2, sizeAttenuation: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }); own.push(bm);
  const bulbs = new THREE.Points(bg, bm); bulbs.frustumCulled = false; scene.add(bulbs);
  const warmC = K('#ffc477');

  // ---- kabut tipis di atas air ----
  const hz = [[-60, 6, -20, 260, 0.1], [50, 7, -10, 300, 0.09], [0, 4, 20, 340, 0.07]].map(([x, y, z, s, o]) => { const m = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: '#6fa0c0', transparent: true, opacity: o, depthWrite: false, fog: false })); m.scale.set(s, s * 0.12, 1); m.position.set(x, y, z); scene.add(m); return m; });

  return {
    update(t, dt) {
      const T = t * duration / 1000, on = smooth(seg(t, 0.05, 0.55));
      lan.update(T);
      lan.glassMat.color.setScalar(0.6 + 0.4 * on);
      const f0 = 0.94 + Math.sin(T * 5) * 0.05;
      lanL[0].position.set(LN[1].x, LN[1].y - 6, 38); lanL[0].intensity = 110 * on * f0;
      mq.mats.glass.color.setRGB(1, 0.8, 0.55).multiplyScalar(0.35 + 0.65 * on);
      warm.forEach((l, i) => { l.intensity = 300 * (0.4 + 0.6 * on) * (0.96 + Math.sin(T * 5 + i) * 0.04); });
      for (let i = 0; i < NB; i++) { const k = (0.5 + 0.5 * Math.sin(T * 3 + bph[i])) * on * 0.8; bc[i * 3] = warmC.r * k; bc[i * 3 + 1] = warmC.g * k; bc[i * 3 + 2] = warmC.b * k; }
      bg.attributes.color.needsUpdate = true;
      rippleTex.offset.set(T * 0.004, T * 0.01);
      clouds.forEach((c) => { c.az += c.v * dt; skyDir(THREE, c.az, c.el, c.r, c.sp.position); });
      halo.material.opacity = 0.38 + Math.sin(T * 1.6) * 0.04;
      streak.material.opacity = 0.2 + Math.sin(T * 2.1) * 0.04;
      kejora.material.opacity = 0.88 + Math.sin(T * 3.1) * 0.08; kejora.material.rotation = Math.sin(T * 0.3) * 0.03;
      sf.update(T);
      void hz;
      const k = easeInOut(t);
      const pf = Math.min(1, Math.max(0, (1 - camera.aspect) / 0.5)); // layar potret: lebih dekat, lensa lebih tinggi
      camera.position.set(lerp(-6, 5, k), lerp(7, 9, k) + pf * 2, lerp(84, 76, k) - pf * 24);
      camera.lookAt(lerp(-2, 2, k), lerp(17, 19, k) + pf * 6, 0);
    },
    dispose() {
      [tex, cloudTex, moonTex, starTex].forEach((x) => x.dispose());
      own.forEach((g) => g.dispose());
      mq.dispose(); lan.dispose(); hillMat.dispose();
    },
  };
}
