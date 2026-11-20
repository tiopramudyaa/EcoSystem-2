// Ramadan: bulan sabit sungguhan (bola berfase), langit biru tua ke hijau-biru, masjid berkubah dan menara
// terpantul di air tenang, lentera fanous berlubang bergoyang di depan, untaian lampu hangat. Geometri prosedural.
import { pal, rng, glowTexture, canvasTex, paintedSky, starField, cloudTexture, moonTexture, phasedMoon, mergeParts, skyDir, easeInOut, lerp, smooth, seg } from '../common.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1445);
  const tex = glowTexture(THREE);
  const cloudTex = cloudTexture(THREE, rnd);
  const own = [];
  const gold = P.accent.clone();

  scene.fog = new THREE.FogExp2(new THREE.Color('#0c2e3a'), 0.0042);
  const sky = paintedSky(THREE, rnd, {
    stops: [[0, new THREE.Color('#1b6b73')], [0.1, new THREE.Color('#12506a')], [0.32, new THREE.Color('#0b2a58')], [0.7, new THREE.Color('#070f35')], [1, new THREE.Color('#030719')]],
    milky: { az0: 0.1, el0: 0.35, az1: 0.9, el1: 0.95, width: 70, alpha: 0.05, color: '200,225,255' },
  });
  scene.add(sky);
  const mirror = new THREE.Group(); mirror.scale.y = -1; scene.add(mirror);
  const skyM = new THREE.Mesh(sky.geometry, sky.material); skyM.renderOrder = -10; skyM.frustumCulled = false; mirror.add(skyM);
  const sf = starField(THREE, rnd, 1000, { tex, minEl: 0.05, size: 1.7, color: '#fff6e0' });
  scene.add(sf.group);
  sf.group.children.forEach((c) => { const m = new THREE.Points(c.geometry, c.material.clone()); m.material.opacity = 0.35; m.frustumCulled = false; mirror.add(m); });

  // bulan sabit: bola berfase dengan terminator, bercahaya di tepi bawah
  const moonTex = moonTexture(THREE, rnd, { base: '#e2d8bd' });
  const moonPos = skyDir(THREE, -0.44, 0.26, 440);
  const moon = phasedMoon(THREE, moonTex, { radius: 34, sunDir: new THREE.Vector3(0.32, -0.42, -1.35), tint: '#ffe7b0', earth: 0.07, gain: 2.6 });
  moon.position.copy(moonPos); scene.add(moon);
  const sprite = (c, s, o, p, k = 1) => { const m = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, color: c, transparent: true, opacity: o, depthWrite: false, blending: THREE.AdditiveBlending, fog: false })); m.scale.set(s, s * k, 1); m.position.copy(p); scene.add(m); return m; };
  const halo = sprite('#ffd58a', 210, 0.55, moonPos.clone().multiplyScalar(1.012));
  sprite('#5fa6c8', 440, 0.14, moonPos.clone().multiplyScalar(1.03));
  const streak = sprite('#ffd58a', 70, 0.3, new THREE.Vector3(moonPos.x, -moonPos.y, moonPos.z), 3.2);
  const clouds = [];
  for (let i = 0; i < 6; i++) {
    const az = -0.9 + rnd() * 1.8, el = 0.1 + rnd() * 0.5, r = 400 + rnd() * 25, w = r * (0.25 + rnd() * 0.3), near = Math.max(0, 1 - Math.hypot(az + 0.44, el - 0.26) / 0.5);
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: cloudTex, color: new THREE.Color('#5a86a8').lerp(new THREE.Color('#ffe9c0'), near * 0.6), transparent: true, opacity: 0.2 + near * 0.2, depthWrite: false, fog: false }));
    sp.scale.set(w, w * 0.28, 1); skyDir(THREE, az, el, r, sp.position); scene.add(sp); clouds.push({ sp, az, el, r, v: (rnd() - 0.3) * 0.01 });
  }

  // cahaya
  scene.add(new THREE.HemisphereLight(new THREE.Color('#5d7fb8'), new THREE.Color('#0a2a33'), 0.42));
  const moonL = new THREE.DirectionalLight('#a9c4ff', 0.9); moonL.position.set(-40, 50, -30); scene.add(moonL);
  const fillL = new THREE.DirectionalLight('#8ea8d8', 0.4); fillL.position.set(10, 8, 40); scene.add(fillL);
  const warm = [-14, 16].map((x) => { const l = new THREE.PointLight('#ffb561', 300, 70, 1.5); l.position.set(x + 6, 6, -40); scene.add(l); return l; });

  // ---- air tenang ----
  const water = new THREE.Mesh(new THREE.PlaneGeometry(1600, 700), new THREE.MeshStandardMaterial({ color: '#04202a', roughness: 0.05, metalness: 0.3, transparent: true, opacity: 0.58 }));
  water.rotation.x = -Math.PI / 2; water.position.set(0, 0, -250); scene.add(water);
  // bukit jauh berlapis kabut
  const hp = [];
  for (let L = 0; L < 2; L++) for (let i = 0; i < 12; i++) {
    const g = new THREE.SphereGeometry(36 + rnd() * 30, 10, 6, 0, Math.PI * 2, 0, Math.PI / 2);
    hp.push({ geo: g, m: new THREE.Matrix4().compose(new THREE.Vector3(-420 + i * 78 + rnd() * 30, -1, -250 - L * 70), new THREE.Quaternion(), new THREE.Vector3(1.4, 0.3 + rnd() * 0.25, 1)), color: L ? '#0d3441' : '#0a2e3a' });
  }
  const hillG = mergeParts(THREE, hp); hp.forEach((p) => p.geo.dispose()); own.push(hillG);
  const hillMat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 1 });
  scene.add(new THREE.Mesh(hillG, hillMat));
  mirror.add(new THREE.Mesh(hillG, new THREE.MeshBasicMaterial({ vertexColors: true, color: '#8aa' })));

  // ---- masjid: kubah utama + semi kubah + 4 menara berbalkon ----
  const stone = [], gld = [], glow = [];
  const M4 = (x, y, z, ry = 0, sx = 1, sy = 1, sz = 1) => new THREE.Matrix4().compose(new THREE.Vector3(x, y, z), new THREE.Quaternion().setFromEuler(new THREE.Euler(0, ry, 0)), new THREE.Vector3(sx, sy, sz));
  const put = (arr, geo, x, y, z, color, ry = 0, s = [1, 1, 1]) => arr.push({ geo, m: M4(x, y, z, ry, s[0], s[1], s[2]), color });
  const box = (w, h, d) => new THREE.BoxGeometry(w, h, d);
  const SC = '#98a2ba', SD = '#808ba6', LEAD = '#6b7d99';
  put(stone, box(60, 1.6, 44), 0, 0.8, 0, '#7b839a');
  put(stone, box(24, 11, 22), 0, 7.1, 0, SC);
  put(stone, box(26, 0.7, 24), 0, 12.9, 0, SD);
  put(stone, new THREE.CylinderGeometry(8, 8.4, 4.2, 32), 0, 15.3, 0, SC);
  const dome = (r, x, y, z, color) => { const g = new THREE.SphereGeometry(r, 28, 14, 0, Math.PI * 2, 0, Math.PI / 2); put(stone, g, x, y, z, color); };
  dome(9.2, 0, 17.4, 0, LEAD);
  put(stone, new THREE.CylinderGeometry(0.9, 1, 2.6, 12), 0, 27.7, 0, SD);
  dome(1.3, 0, 29, 0, LEAD);
  put(gld, new THREE.CylinderGeometry(0.08, 0.14, 4.2, 6), 0, 32.1, 0, '#e6c36a');
  put(gld, new THREE.SphereGeometry(0.45, 10, 8), 0, 30.2, 0, '#e6c36a');
  const cres = new THREE.TorusGeometry(0.55, 0.11, 6, 16, Math.PI * 1.5); put(gld, cres, 0, 33.2, 0, '#e6c36a');
  [[0, 11, 9.5, 6.6], [0, 11, -9.5, 6.6], [10, 11, 0, 6.6], [-10, 11, 0, 6.6]].forEach(([x, y, z, r]) => { dome(r, x, y + 1.6, z, LEAD); put(gld, new THREE.CylinderGeometry(0.06, 0.1, 2.2, 6), x, y + 1.6 + r + 0.9, z, '#e6c36a'); });
  [[-11.5, -10.4], [11.5, -10.4], [-11.5, 10.4], [11.5, 10.4]].forEach(([x, z]) => { put(stone, new THREE.CylinderGeometry(2.8, 3, 2.4, 14), x, 14.2, z, SC); dome(3.2, x, 15.4, z, LEAD); put(gld, new THREE.CylinderGeometry(0.05, 0.08, 1.8, 6), x, 19.4, z, '#e6c36a'); });
  // menara
  [[-19, 14], [19, 14], [-19, -14], [19, -14]].forEach(([x, z]) => {
    put(stone, new THREE.CylinderGeometry(1.5, 2.1, 3, 8), x, 3.1, z, SD);
    put(stone, new THREE.CylinderGeometry(0.85, 1.3, 33, 14), x, 19.6, z, SC);
    [24, 33].forEach((y, i) => {
      put(stone, new THREE.CylinderGeometry(2.1 - i * 0.2, 1.55, 0.8, 16), x, y, z, SD);
      put(gld, new THREE.TorusGeometry(2.0 - i * 0.2, 0.07, 6, 22), x, y + 0.95, z, '#e6c36a'); // pagar balkon
      gld[gld.length - 1].m = M4(x, y + 0.95, z).multiply(new THREE.Matrix4().makeRotationX(Math.PI / 2));
    });
    put(stone, new THREE.CylinderGeometry(1.2, 1.4, 5, 14), x, 36.1, z, SC);
    put(stone, new THREE.ConeGeometry(1.5, 8.4, 14), x, 42.8, z, LEAD);
    put(gld, new THREE.SphereGeometry(0.32, 8, 6), x, 47.2, z, '#e6c36a');
    put(gld, new THREE.CylinderGeometry(0.04, 0.07, 2.6, 5), x, 48.6, z, '#e6c36a');
    [10, 17, 29].forEach((y) => put(glow, box(0.26, 1.5, 0.12), x, y, z + (z > 0 ? 1.18 : 1.18), '#ffb04d'));
  });
  // jendela dan gerbang berlengkung (menyala hangat)
  const arch = (w, h) => { const s = new THREE.Shape(); s.moveTo(-w / 2, 0); s.lineTo(-w / 2, h - w / 2); s.absarc(0, h - w / 2, w / 2, Math.PI, 0, true); s.lineTo(w / 2, 0); s.closePath(); return new THREE.ShapeGeometry(s, 10); };
  const win = (w, h, x, y, z, ry, c = '#ffb04d') => put(glow, arch(w, h), x, y, z, c, ry);
  for (let i = -3; i <= 3; i++) if (Math.abs(i) > 0) win(2.2, 4.6, i * 3.3, 2.2, 11.06, 0);
  for (let i = -2; i <= 2; i++) win(1.8, 3.6, i * 4.4, 7.4, 11.06, 0, '#ffc46b');
  win(4.2, 9, 0, 2.2, 11.07, 0, '#ffa23a');
  for (let i = -2; i <= 2; i++) { win(2, 4.2, 12.06, 2.4, i * 4.2, Math.PI / 2); win(1.6, 3.4, 12.06, 7.4, i * 4.2, Math.PI / 2, '#ffc46b'); }
  for (let a = -75; a <= 75; a += 15) { const r = a * Math.PI / 180; win(1.1, 2.4, Math.sin(r) * 8.2, 14.1, Math.cos(r) * 8.2, r, '#ffd48a'); }
  [-1, 1].forEach((s) => { for (let a = 0; a < 5; a++) win(0.9, 1.9, s * 10 + (a - 2) * 1.1, 13.1, 14.5 - Math.abs(a - 2) * 0.1, 0, '#ffd48a'); });
  const mk = (parts) => { const g = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose()); own.push(g); return g; };
  const gS = mk(stone), gG = mk(gld), gW = mk(glow);
  const mosque = new THREE.Group(), mosqueM = new THREE.Group();
  mosque.position.set(6, 0, -54); mosqueM.position.copy(mosque.position); mirror.add(mosqueM); scene.add(mosque);
  const winMat = new THREE.MeshBasicMaterial({ vertexColors: true, side: THREE.DoubleSide });
  mosque.add(new THREE.Mesh(gS, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.85, metalness: 0.05 })));
  mosque.add(new THREE.Mesh(gG, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.3, metalness: 0.85, emissive: gold, emissiveIntensity: 0.3 })));
  mosque.add(new THREE.Mesh(gW, winMat));
  const mirStone = new THREE.MeshBasicMaterial({ vertexColors: true, color: '#7a8aa8' }), mirGold = new THREE.MeshBasicMaterial({ vertexColors: true, color: '#8a7440' }), mirWin = new THREE.MeshBasicMaterial({ vertexColors: true, color: '#c89a60', side: THREE.DoubleSide });
  mosqueM.add(new THREE.Mesh(gS, mirStone), new THREE.Mesh(gG, mirGold), new THREE.Mesh(gW, mirWin));

  // ---- dermaga depan ----
  const pier = new THREE.Mesh(new THREE.BoxGeometry(120, 1, 40), new THREE.MeshStandardMaterial({ color: '#0d1a22', roughness: 0.7, metalness: 0.2, transparent: true }));
  pier.renderOrder = 10; pier.position.set(0, -0.1, 40); scene.add(pier);
  const rail = new THREE.Mesh(new THREE.BoxGeometry(120, 0.12, 0.12), new THREE.MeshStandardMaterial({ color: '#1a1f28', metalness: 0.7, roughness: 0.4 })); rail.position.set(0, 1.5, 20.4); scene.add(rail);

  // ---- lentera fanous: badan oktagonal dengan pola berlubang (emissive) ----
  const lanTex = canvasTex(THREE, 512, 256, (g, w, h) => {
    g.fillStyle = '#140c05'; g.fillRect(0, 0, w, h);
    for (let p = 0; p < 8; p++) {
      const x0 = p * 64, cx = x0 + 32;
      g.save(); g.beginPath(); g.moveTo(x0 + 9, 206); g.lineTo(x0 + 9, 104); g.arc(cx, 104, 23, Math.PI, 0); g.lineTo(x0 + 55, 206); g.closePath(); g.clip();
      const gr = g.createLinearGradient(0, 70, 0, 206); gr.addColorStop(0, '#ffe08a'); gr.addColorStop(1, '#ff8f2e'); g.fillStyle = gr; g.fillRect(x0, 60, 64, 150);
      g.strokeStyle = '#1c1008'; g.lineWidth = 2.2;
      for (let k = -160; k < 160; k += 11) { g.beginPath(); g.moveTo(x0 + k, 60); g.lineTo(x0 + k + 150, 210); g.stroke(); g.beginPath(); g.moveTo(x0 + k + 150, 60); g.lineTo(x0 + k, 210); g.stroke(); }
      g.restore();
      g.fillStyle = '#ffd27a';
      for (let k = 0; k < 6; k++) { g.beginPath(); g.arc(x0 + 10 + k * 8.8, 30, 2.6, 0, 6.3); g.fill(); g.beginPath(); g.arc(x0 + 10 + k * 8.8, 232, 2.6, 0, 6.3); g.fill(); }
      g.beginPath(); for (let k = 0; k < 8; k++) { const a = k * 0.785, r = k % 2 ? 4 : 9; g.lineTo(cx + Math.cos(a) * r, 52 + Math.sin(a) * r); } g.closePath(); g.fill();
    }
  });
  const prof = [];
  for (let i = 0; i <= 14; i++) { const s = i / 14; prof.push(new THREE.Vector2(0.12 + 0.62 * Math.pow(Math.sin(Math.PI * (0.08 + s * 0.84)), 0.7), -1.25 + s * 2.5)); }
  const bodyG = new THREE.LatheGeometry(prof, 8);
  const capG = new THREE.LatheGeometry([[0.01, 1.2], [0.5, 1.24], [0.62, 1.34], [0.4, 1.5], [0.22, 1.72], [0.3, 1.9], [0.1, 2.15], [0.03, 2.5], [0.01, 2.55]].map(([x, y]) => new THREE.Vector2(x, y)), 14);
  const baseG = new THREE.LatheGeometry([[0.01, -1.55], [0.2, -1.52], [0.3, -1.4], [0.55, -1.3], [0.45, -1.2], [0.01, -1.2]].map(([x, y]) => new THREE.Vector2(x, y)), 14);
  const tasG = new THREE.ConeGeometry(0.12, 0.5, 8); tasG.translate(0, -1.8, 0);
  own.push(bodyG, capG, baseG, tasG);
  const brass = new THREE.MeshStandardMaterial({ color: '#7a5a2a', metalness: 0.9, roughness: 0.35, emissive: gold, emissiveIntensity: 0.15 });
  const glass = new THREE.MeshStandardMaterial({ color: '#2a1a0a', metalness: 0.5, roughness: 0.5, emissive: '#ffffff', emissiveMap: lanTex, emissiveIntensity: 1.5, side: THREE.DoubleSide });
  const lanterns = [];
  [[-11.5, 19, 6, 1.5], [-6.5, 21, 10, 1.9], [4, 18, 3, 1.2], [11, 20.5, 8, 1.7], [17.5, 19, 12, 2.1], [-24, 17, -2, 1.0], [-1.5, 15.5, -6, 0.85]].forEach(([x, top, z, s], i) => {
    const pivot = new THREE.Group(); pivot.position.set(x, top, z); scene.add(pivot);
    const len = 12;
    const cord = new THREE.Mesh(new THREE.CylinderGeometry(0.03 * s, 0.03 * s, len, 5), brass); cord.position.y = 2; pivot.add(cord); own.push(cord.geometry);
    const lan = new THREE.Group(); lan.position.y = -3.5; lan.scale.setScalar(s); pivot.add(lan);
    lan.add(new THREE.Mesh(bodyG, glass), new THREE.Mesh(capG, brass), new THREE.Mesh(baseG, brass), new THREE.Mesh(tasG, brass));
    const halo = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, color: '#ffb561', transparent: true, opacity: 0.55, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
    halo.scale.set(5.2 * s, 5.2 * s, 1); lan.add(halo);
    let light = null;
    if (i < 4) { light = new THREE.PointLight('#ffa845', 45 * s, 26, 1.6); light.position.y = 0; lan.add(light); }
    lanterns.push({ pivot, ph: i * 1.7, halo, light, s });
  });

  // ---- untaian lampu hangat ----
  const NSTR = 2, NB = 40, sp = new Float32Array(NSTR * NB * 3), sc = new Float32Array(NSTR * NB * 3), sph = new Float32Array(NSTR * NB);
  const strs = [[17.5, 4.5, 3], [15, 3.5, -14]];
  const warmC = new THREE.Color('#ffc477');
  const sg = new THREE.BufferGeometry(); sg.setAttribute('position', new THREE.BufferAttribute(sp, 3)); sg.setAttribute('color', new THREE.BufferAttribute(sc, 3)); own.push(sg);
  const sPts = new THREE.Points(sg, new THREE.PointsMaterial({ map: tex, vertexColors: true, size: 15, sizeAttenuation: false, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  sPts.frustumCulled = false; scene.add(sPts);
  for (let i = 0; i < NSTR * NB; i++) sph[i] = rnd() * 6.28;

  return {
    update(t, dt) {
      const T = t * duration / 1000, on = smooth(seg(t, 0.05, 0.55));
      lanterns.forEach((l, i) => {
        l.pivot.rotation.z = Math.sin(T * 1.7 + l.ph) * 0.07; l.pivot.rotation.x = Math.cos(T * 1.3 + l.ph) * 0.035;
        const f = 0.92 + Math.sin(T * 6 + i * 2) * 0.08;
        l.halo.material.opacity = 0.5 * f; if (l.light) l.light.intensity = 45 * l.s * f;
      });
      winMat.color.setScalar(0.35 + 0.65 * on);
      warm.forEach((l, i) => { l.intensity = 300 * (0.4 + 0.6 * on) * (0.95 + Math.sin(T * 5 + i) * 0.05); });
      for (let s = 0; s < NSTR; s++) {
        const [yt, sag, z] = strs[s];
        for (let i = 0; i < NB; i++) {
          const u = i / (NB - 1), j = s * NB + i;
          sp[j * 3] = lerp(-50, 50, u) + Math.sin(T * 0.8 + s) * 0.3; sp[j * 3 + 1] = yt - sag * 4 * u * (1 - u) + Math.sin(T * 1.6 + u * 6) * 0.12; sp[j * 3 + 2] = z;
          const k = (0.55 + 0.45 * Math.sin(T * 4 + sph[j])) * on * (s ? 0.7 : 1);
          sc[j * 3] = warmC.r * k; sc[j * 3 + 1] = warmC.g * k; sc[j * 3 + 2] = warmC.b * k;
        }
      }
      sg.attributes.position.needsUpdate = true; sg.attributes.color.needsUpdate = true;
      clouds.forEach((c) => { c.az += c.v * dt; skyDir(THREE, c.az, c.el, c.r, c.sp.position); });
      halo.material.opacity = 0.5 + Math.sin(T * 1.6) * 0.05;
      streak.material.opacity = 0.26 + Math.sin(T * 2.1) * 0.05;
      sf.update(T);
      const k = easeInOut(t);
      camera.position.set(lerp(-4, 3, k), 5.4 + k * 0.8, lerp(30, 25, k));
      camera.lookAt(lerp(-3, 2, k), lerp(15, 17, k), -55);
    },
    dispose() {
      [tex, cloudTex, moonTex, lanTex].forEach((x) => x.dispose());
      own.forEach((g) => g.dispose());
      [winMat, mirStone, mirGold, mirWin, hillMat].forEach((m) => m.dispose());
    },
  };
}
