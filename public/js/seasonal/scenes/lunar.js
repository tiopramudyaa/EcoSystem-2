// Imlek: suasana merah-emas, lampion merah bergantung, naga emas bersisik meliuk di awan, koin jatuh, kembang api.
import { pal, rng, skyDome, stars, glowTexture, glowSprite, canvasTex, particlePool, easeInOut, smooth, seg, lerp } from '../common.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(2568);
  const tex = glowTexture(THREE);
  const gold = P.accent.clone();
  const dark = new THREE.Color('#1c0305');

  scene.fog = new THREE.FogExp2(P.from.clone().lerp(P.to, 0.3).multiplyScalar(0.8), 0.0055);
  scene.add(skyDome(THREE, [
    [0.35, P.to.clone().lerp(gold, 0.2)],
    [0.5, P.to.clone().multiplyScalar(0.75)],
    [0.75, P.from.clone().lerp(P.to, 0.15)],
    [1, P.from.clone().multiplyScalar(0.7)],
  ]));
  const st = stars(THREE, 160, rnd, { tex, size: 2.2, minY: 0.35, color: '#ffe9c0' });
  scene.add(st.points);
  const sun = glowSprite(THREE, tex, gold, 200, 0.35); sun.position.set(0, 12, -120); scene.add(sun);

  scene.add(new THREE.HemisphereLight('#ffd9b0', P.from.clone().multiplyScalar(0.6), 0.7));
  const key = new THREE.DirectionalLight('#ffe2b0', 1.5); key.position.set(10, 25, 30); scene.add(key);
  const back = new THREE.DirectionalLight(gold, 0.9); back.position.set(-20, 10, -30); scene.add(back);

  // awan
  const cloudTex = canvasTex(THREE, 128, 64, (g, w, h) => {
    const r = g.createRadialGradient(w / 2, h / 2, 2, w / 2, h / 2, w / 2);
    r.addColorStop(0, 'rgba(255,210,170,0.7)'); r.addColorStop(1, 'rgba(255,210,170,0)');
    g.fillStyle = r; g.save(); g.scale(1, 0.5); g.translate(0, h / 2); g.fillRect(0, 0, w, h * 2); g.restore();
  });
  const clouds = [];
  for (let i = 0; i < 14; i++) {
    const c = glowSprite(THREE, cloudTex, '#ffc9a8', 30 + rnd() * 30, 0.45);
    c.scale.y = c.scale.x * 0.3; c.position.set(-70 + rnd() * 140, 0 + rnd() * 18, -30 - rnd() * 60);
    scene.add(c); clouds.push({ c, v: 0.3 + rnd() * 0.5 });
  }

  // lampion: bola merah bergaris, tutup emas, rumbai
  const redM = new THREE.MeshStandardMaterial({ color: '#c8102e', emissive: '#e0141c', emissiveIntensity: 0.9, roughness: 0.45 });
  const goldM = new THREE.MeshStandardMaterial({ color: gold, roughness: 0.3, metalness: 0.9 });
  const lampGeo = new THREE.SphereGeometry(1, 20, 14);
  const capGeo = new THREE.CylinderGeometry(0.42, 0.42, 0.22, 14);
  const tassGeo = new THREE.CylinderGeometry(0.02, 0.02, 1, 4);
  const ribGeo = new THREE.TorusGeometry(1, 0.025, 5, 24);
  const lamps = [];
  const rows = [[18, 8, 12], [15, 6, 0]];
  rows.forEach(([top, n, z], r) => {
    const beam = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.08, 60, 6), goldM);
    beam.rotation.z = Math.PI / 2; beam.position.set(0, top + 1.2, z); scene.add(beam);
    for (let i = 0; i < n; i++) {
      const x = -26 + (i + 0.5) * (52 / n), s = 1.2 + (i % 2) * 0.25 - r * 0.15, len = 1.2 + ((i * 7) % 3) * 0.5;
      const piv = new THREE.Group(); piv.position.set(x, top + 1.2, z); scene.add(piv);
      const cord = new THREE.Mesh(tassGeo, goldM); cord.scale.y = len; cord.position.y = -len / 2; piv.add(cord);
      const L = new THREE.Group(); L.position.y = -len - s * 0.95; piv.add(L);
      const b = new THREE.Mesh(lampGeo, redM); b.scale.set(s, s * 0.88, s); L.add(b);
      for (let k = 0; k < 3; k++) { const rb = new THREE.Mesh(ribGeo, goldM); rb.scale.set(s, s * 0.88, s); rb.rotation.y = k * Math.PI / 3; L.add(rb); }
      const c1 = new THREE.Mesh(capGeo, goldM); c1.position.y = s * 0.85; c1.scale.setScalar(s * 0.9); L.add(c1);
      const c2 = c1.clone(); c2.position.y = -s * 0.85; L.add(c2);
      for (let t = -2; t <= 2; t++) { const ts = new THREE.Mesh(tassGeo, new THREE.MeshStandardMaterial({ color: '#e5322d', roughness: 0.6 })); ts.scale.y = 1.4; ts.position.set(t * 0.09 * s, -s * 0.95 - 0.8, 0); L.add(ts); }
      const halo = glowSprite(THREE, tex, '#ff3a2a', s * 4.2, 0.55); L.add(halo);
      lamps.push({ piv, ph: i * 1.1 + r, halo });
    }
  });
  const lampLight = [new THREE.PointLight('#ff4a30', 80, 30, 1.6), new THREE.PointLight('#ff4a30', 80, 30, 1.6)];
  lampLight[0].position.set(-12, 12, 14); lampLight[1].position.set(12, 12, 14); lampLight.forEach((l) => scene.add(l));

  // naga: badan bersegmen, sisik lewat texture, kepala bertanduk
  const scaleTex = canvasTex(THREE, 64, 64, (g, w, h) => {
    g.fillStyle = '#' + gold.clone().multiplyScalar(0.85).getHexString(); g.fillRect(0, 0, w, h);
    g.strokeStyle = '#7a4a05'; g.lineWidth = 2;
    for (let y = 0; y < h + 16; y += 16) for (let x = -16; x < w + 16; x += 16) {
      const ox = ((y / 16) % 2) * 8;
      g.beginPath(); g.arc(x + ox, y, 8, 0, Math.PI); g.stroke();
    }
  });
  scaleTex.wrapS = scaleTex.wrapT = THREE.RepeatWrapping; scaleTex.repeat.set(2, 1);
  const bodyM = new THREE.MeshStandardMaterial({ map: scaleTex, color: '#ffffff', roughness: 0.3, metalness: 0.75, emissive: gold, emissiveIntensity: 0.12 });
  const redD = new THREE.MeshStandardMaterial({ color: '#d4181f', roughness: 0.5, emissive: '#7a0a0a', emissiveIntensity: 0.4 });
  const NS = 38, segGeo = new THREE.SphereGeometry(1, 14, 10), spikeGeo = new THREE.ConeGeometry(0.22, 0.7, 5);
  const dragon = new THREE.Group(); scene.add(dragon);
  const segs = [];
  for (let i = 0; i < NS; i++) {
    const rad = 1.7 * (i < 3 ? 0.8 + i * 0.1 : Math.max(0.28, 1 - (i - 3) / 40 * 0.85 - (i > 30 ? (i - 30) * 0.03 : 0)));
    const m = new THREE.Mesh(segGeo, bodyM); m.scale.set(rad * 1.25, rad, rad); dragon.add(m);
    const sp = new THREE.Mesh(spikeGeo, redD); sp.position.y = rad * 0.95; sp.scale.setScalar(rad * 1.2); m.add(sp);
    sp.scale.set(rad * 1.2 / (rad * 1.25), 1.2, 1.2); // koreksi skala induk
    segs.push({ m, rad });
  }
  // kepala
  const head = new THREE.Group(); dragon.add(head);
  const skull = new THREE.Mesh(new THREE.SphereGeometry(1, 18, 12), bodyM); skull.scale.set(1.5, 0.85, 0.95); head.add(skull);
  const snout = new THREE.Mesh(new THREE.CapsuleGeometry(0.42, 0.7, 6, 12), bodyM); snout.rotation.z = Math.PI / 2; snout.position.set(1.5, -0.1, 0); head.add(snout);
  const jaw = new THREE.Mesh(new THREE.CapsuleGeometry(0.3, 0.7, 4, 10), redD); jaw.rotation.z = Math.PI / 2; jaw.position.set(1.4, -0.55, 0); head.add(jaw);
  const pearl = new THREE.Mesh(new THREE.SphereGeometry(0.3, 12, 10), new THREE.MeshStandardMaterial({ color: '#fff4d6', emissive: '#ffd27a', emissiveIntensity: 1.8 }));
  pearl.position.set(2.6, -0.65, 0); head.add(pearl);
  const eyeM = new THREE.MeshStandardMaterial({ color: '#fff', emissive: '#fff2a0', emissiveIntensity: 1.2 });
  [-1, 1].forEach((s) => {
    const eye = new THREE.Mesh(new THREE.SphereGeometry(0.16, 8, 8), eyeM); eye.position.set(0.7, 0.4, s * 0.62); head.add(eye);
    const horn = new THREE.Mesh(new THREE.TubeGeometry(new THREE.CatmullRomCurve3([[0, 0, 0], [-0.4, 0.5, s * 0.15], [-1.0, 0.95, s * 0.3], [-1.6, 1.0, s * 0.4]].map((a) => new THREE.Vector3(...a))), 12, 0.11, 6), redD);
    horn.position.set(0.2, 0.55, s * 0.4); head.add(horn);
    const wh = new THREE.Mesh(new THREE.TubeGeometry(new THREE.CatmullRomCurve3([[0, 0, 0], [0.6, -0.25, s * 0.6], [1.0, -0.9, s * 1.1], [0.5, -1.4, s * 1.5], [-0.3, -1.5, s * 1.7]].map((a) => new THREE.Vector3(...a))), 16, 0.035, 4), goldM);
    wh.position.set(2.0, -0.3, s * 0.2); head.add(wh);
  });
  const mane = new THREE.Mesh(new THREE.ConeGeometry(0.9, 1.8, 8), redD); mane.rotation.z = Math.PI / 2; mane.position.set(-1.2, 0.2, 0); head.add(mane);
  head.scale.setScalar(1.5);
  const dLight = new THREE.PointLight('#ffcf70', 40, 22, 1.6); head.add(dLight);

  // koin dan kembang api
  const coinPool = particlePool(THREE, 450, { tex, size: 0.55, gravity: -4.5, drag: 0.15 });
  scene.add(coinPool.points);
  const fw = particlePool(THREE, 1200, { tex, size: 2.4, gravity: -7, drag: 0.9 });
  scene.add(fw.points);
  const gc = gold.clone().multiplyScalar(1.6), rc = new THREE.Color('#ff4a30').multiplyScalar(1.6);
  const shots = [[0.38, -30, 34, -75, 0], [0.58, 28, 30, -70, 1], [0.8, -2, 40, -80, 0]].map(([t, x, y, z, c]) => ({ t: t * duration / 1000, x, y, z, c, f: false }));

  const P0 = new THREE.Vector3(), P1 = new THREE.Vector3();
  // lintasan: u adalah panjang busur kasar sepanjang sumbu x
  const path = (u, out) => out.set(u, 6 + Math.sin(u * 0.2) * 2.6, -3 + Math.cos(u * 0.17) * 3);
  const spacing = 2.1;
  return {
    update(t, dt) {
      const T = t * duration / 1000;
      const hu = lerp(-34, 20, easeInOut(seg(t, 0, 1)));
      path(hu, P0);
      head.position.copy(P0);
      path(hu + 0.6, P1);
      head.rotation.order = 'YZX';
      head.rotation.set(0, -Math.atan2(P1.z - P0.z, 0.6), Math.atan2(P1.y - P0.y, 0.6));
      let prev = P0.clone();
      segs.forEach((s, i) => {
        path(hu - (i + 1) * spacing - 0.8, P1);
        P1.y += Math.sin(T * 3 - i * 0.5) * 0.15; // gelombang kecil
        s.m.position.copy(P1);
        s.m.rotation.order = 'YZX';
        s.m.rotation.set(0, -Math.atan2(prev.z - P1.z, prev.x - P1.x), Math.atan2(prev.y - P1.y, Math.hypot(prev.x - P1.x, prev.z - P1.z)));
        prev = P1.clone();
      });
      lamps.forEach((l) => { l.piv.rotation.z = Math.sin(T * 1.8 + l.ph) * 0.1; l.piv.rotation.x = Math.sin(T * 1.3 + l.ph) * 0.04; l.halo.material.opacity = 0.5 + Math.sin(T * 4 + l.ph) * 0.1; });
      clouds.forEach((c) => { c.c.position.x += c.v * dt * 3; if (c.c.position.x > 80) c.c.position.x = -80; });
      shots.forEach((s) => { if (!s.f && T >= s.t) { s.f = true; fw.burst(s.x, s.y, s.z, s.c ? rc : gc, 180, 26, 1.9, rnd); fw.burst(s.x, s.y, s.z, gc, 60, 14, 1.4, rnd); } });
      if (dt > 0) for (let i = 0; i < 3; i++) coinPool.emit(-30 + rnd() * 60, 24 + rnd() * 4, -4 - rnd() * 24, (rnd() - 0.5) * 1.2, -2 - rnd() * 2, 0, gc, 5);
      coinPool.update(dt); fw.update(dt);
      pearl.material.emissiveIntensity = 1.6 + Math.sin(T * 5) * 0.4;
      const k = easeInOut(t);
      camera.position.set(lerp(-2, 2, k), lerp(4, 5.5, k), lerp(32, 28, k));
      camera.lookAt(0, lerp(7, 8, k), -8);
    },
    dispose() { tex.dispose(); cloudTex.dispose(); scaleTex.dispose(); lampGeo.dispose(); capGeo.dispose(); tassGeo.dispose(); ribGeo.dispose(); segGeo.dispose(); spikeGeo.dispose(); },
  };
}
