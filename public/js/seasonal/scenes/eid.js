// Lebaran: senja emas dan zamrud, masjid detail di tengah, rangkaian ketupat berayun di depan, kilau emas.
import { pal, rng, skyDome, glowTexture, glowSprite, canvasTex, particlePool, easeInOut, smooth, seg, lerp } from '../common.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1446);
  const tex = glowTexture(THREE);
  const gold = P.accent.clone();
  const emerald = P.to.clone();

  scene.fog = new THREE.FogExp2(emerald.clone().lerp(gold, 0.25).multiplyScalar(0.4), 0.0034);
  scene.add(skyDome(THREE, [
    [0.38, gold.clone().lerp(new THREE.Color('#fff0c8'), 0.35)],
    [0.47, gold.clone().lerp(emerald, 0.55)],
    [0.62, emerald.clone().multiplyScalar(0.85)],
    [1, P.from.clone().multiplyScalar(0.7)],
  ]));
  const sun = glowSprite(THREE, tex, P.warm, 120, 0.6); sun.position.set(6, 8, -140); scene.add(sun);
  const sun2 = glowSprite(THREE, tex, gold, 300, 0.16); sun2.position.copy(sun.position); scene.add(sun2);
  // awan lembut
  const cloudTex = canvasTex(THREE, 128, 64, (g, w, h) => {
    const r = g.createRadialGradient(w / 2, h / 2, 2, w / 2, h / 2, w / 2);
    r.addColorStop(0, 'rgba(255,240,210,0.55)'); r.addColorStop(1, 'rgba(255,240,210,0)');
    g.fillStyle = r; g.save(); g.scale(1, 0.5); g.translate(0, h / 2); g.fillRect(0, 0, w, h * 2); g.restore();
  });
  for (let i = 0; i < 9; i++) {
    const c = glowSprite(THREE, cloudTex, '#ffe2b0', 50 + rnd() * 40, 0.5);
    c.scale.y = c.scale.x * 0.28; c.position.set(-110 + i * 28 + rnd() * 10, 18 + rnd() * 26, -130 - rnd() * 20);
    c.material.fog = false; scene.add(c);
  }

  scene.add(new THREE.HemisphereLight(new THREE.Color('#ffe9c4'), emerald.clone().multiplyScalar(0.3), 0.85));
  const sunLight = new THREE.DirectionalLight(P.warm, 1.3);
  sunLight.position.set(18, 12, -40);
  scene.add(sunLight);
  const fill = new THREE.DirectionalLight('#ffe6b8', 1.5);
  fill.position.set(-10, 15, 40);
  scene.add(fill);

  // masjid
  const wall = new THREE.MeshStandardMaterial({ color: '#d8c8a2', roughness: 0.8 });
  const goldM = new THREE.MeshStandardMaterial({ color: gold.clone().multiplyScalar(0.95), roughness: 0.28, metalness: 0.9 });
  const domeM = new THREE.MeshStandardMaterial({ color: emerald.clone().lerp(new THREE.Color('#0b7a5a'), 0.5), roughness: 0.35, metalness: 0.35 });
  const glassM = new THREE.MeshStandardMaterial({ color: '#10261d', emissive: gold, emissiveIntensity: 0.7, roughness: 0.4 });
  const m = new THREE.Group();
  const add = (mesh, x, y, z) => { mesh.position.set(x, y, z); m.add(mesh); return mesh; };
  add(new THREE.Mesh(new THREE.BoxGeometry(24, 9, 13), wall), 0, 2.5, 0);
  add(new THREE.Mesh(new THREE.BoxGeometry(30, 1, 15), wall), 0, -1.5, 0); // alas
  add(new THREE.Mesh(new THREE.CylinderGeometry(5.2, 5.5, 3, 24), wall), 0, 8.5, 0);
  add(new THREE.Mesh(new THREE.SphereGeometry(5.6, 32, 16, 0, Math.PI * 2, 0, Math.PI * 0.55), domeM), 0, 10, 0);
  add(new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.15, 3, 6), goldM), 0, 16.1, 0);
  add(new THREE.Mesh(new THREE.SphereGeometry(0.45, 12, 8), goldM), 0, 15, 0);
  [[-9, 4], [9, 4], [-9, -4], [9, -4]].forEach(([x, z]) => {
    add(new THREE.Mesh(new THREE.CylinderGeometry(1.4, 1.5, 2, 12), wall), x, 8, z * 0.8);
    add(new THREE.Mesh(new THREE.SphereGeometry(1.7, 18, 10, 0, Math.PI * 2, 0, Math.PI * 0.55), domeM), x, 9, z * 0.8);
    add(new THREE.Mesh(new THREE.ConeGeometry(0.12, 1.4, 6), goldM), x, 11.4, z * 0.8);
  });
  [-17, 17].forEach((x) => { // menara
    add(new THREE.Mesh(new THREE.CylinderGeometry(1.0, 1.25, 28, 12), wall), x, 12, 0);
    for (let k = 0; k < 3; k++) add(new THREE.Mesh(new THREE.TorusGeometry(1.1, 0.08, 6, 14), goldM), x, 6 + k * 6, 0).rotation.x = Math.PI / 2;
    add(new THREE.Mesh(new THREE.CylinderGeometry(1.9, 1.5, 0.9, 12), wall), x, 21, 0);
    add(new THREE.Mesh(new THREE.CylinderGeometry(1.0, 1.0, 2.2, 12), wall), x, 22.5, 0);
    add(new THREE.Mesh(new THREE.SphereGeometry(1.15, 14, 8, 0, Math.PI * 2, 0, Math.PI * 0.6), domeM), x, 23.6, 0);
    add(new THREE.Mesh(new THREE.ConeGeometry(0.12, 2.2, 6), goldM), x, 26.4, 0);
  });
  // jendela lengkung bercahaya + lengkung emas
  const archGeo = new THREE.TorusGeometry(1.1, 0.14, 6, 14, Math.PI);
  const winGeo = new THREE.CircleGeometry(1.0, 14, 0, Math.PI);
  for (let i = -4; i <= 4; i++) {
    add(new THREE.Mesh(archGeo, goldM), i * 2.6, 0.6, 6.6);
    add(new THREE.Mesh(new THREE.PlaneGeometry(2, 3.2), glassM), i * 2.6, -0.1, 6.55);
    add(new THREE.Mesh(winGeo, glassM), i * 2.6, 1.5, 6.55);
  }
  m.position.set(0, -1, -62);
  m.scale.setScalar(1.15);
  scene.add(m);
  // halaman dan kolam
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(400, 260), new THREE.MeshStandardMaterial({ color: emerald.clone().multiplyScalar(0.16), roughness: 0.95 }));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, -2.5, -70); scene.add(ground);
  const pool = new THREE.Mesh(new THREE.PlaneGeometry(26, 40), new THREE.MeshStandardMaterial({ color: '#145a45', roughness: 0.2, metalness: 0.9 }));
  pool.rotation.x = -Math.PI / 2; pool.position.set(0, -2.4, -38); scene.add(pool);
  const poolGlow = glowSprite(THREE, tex, P.warm, 30, 0.2); poolGlow.scale.y = 7; poolGlow.position.set(0, -1.8, -50); scene.add(poolGlow);

  // ketupat: kubus diputar 45 derajat dengan anyaman diagonal
  const weave = canvasTex(THREE, 128, 128, (g, w, h) => {
    g.fillStyle = '#' + emerald.clone().lerp(new THREE.Color('#0f8a3a'), 0.4).getHexString(); g.fillRect(0, 0, w, h);
    g.lineWidth = 7;
    for (let k = -h; k < w + h; k += 22) {
      g.strokeStyle = '#' + gold.clone().lerp(new THREE.Color('#9ccf5a'), 0.5).getHexString();
      g.beginPath(); g.moveTo(k, 0); g.lineTo(k + h, h); g.stroke();
      g.strokeStyle = '#0c5a34';
      g.beginPath(); g.moveTo(k + h, 0); g.lineTo(k, h); g.stroke();
    }
  });
  weave.wrapS = weave.wrapT = THREE.RepeatWrapping;
  const kGeo = new THREE.BoxGeometry(1.05, 1.05, 0.6, 1, 1, 1);
  const kMat = new THREE.MeshStandardMaterial({ map: weave, roughness: 0.6 });
  const kMat2 = new THREE.MeshStandardMaterial({ map: weave, color: gold.clone().lerp(new THREE.Color('#fff'), 0.2), roughness: 0.6 });
  const strandGeo = new THREE.CylinderGeometry(0.02, 0.02, 1, 4);
  const strandM = new THREE.MeshStandardMaterial({ color: gold, roughness: 0.4, metalness: 0.5 });
  const A = new THREE.Vector3(-17, 10.5, 14), B = new THREE.Vector3(17, 10.5, 14);
  const rope = new THREE.CatmullRomCurve3([A, new THREE.Vector3(-8.5, 8.2, 14), new THREE.Vector3(0, 7.4, 14), new THREE.Vector3(8.5, 8.2, 14), B]);
  scene.add(new THREE.Mesh(new THREE.TubeGeometry(rope, 40, 0.06, 5), strandM));
  const hang = [];
  const NK = 9;
  for (let i = 0; i < NK; i++) {
    const u = (i + 0.5) / NK, p = rope.getPoint(u);
    const piv = new THREE.Group(); piv.position.copy(p); scene.add(piv);
    const L = 1.3 + (i % 3) * 0.5;
    const s = new THREE.Mesh(strandGeo, strandM); s.scale.y = L; s.position.y = -L / 2; piv.add(s);
    const k = new THREE.Mesh(kGeo, i % 2 ? kMat : kMat2); k.rotation.z = Math.PI / 4; k.position.y = -L - 0.65; piv.add(k);
    for (let t = -2; t <= 2; t++) { // rumbai di dasar
      const tl = new THREE.Mesh(strandGeo, strandM); tl.scale.y = 1.0; tl.position.set(t * 0.1, -L - 1.3 - Math.abs(t) * 0.04, 0); piv.add(tl);
    }
    hang.push({ piv, ph: i * 0.9 });
  }

  // kilau dan ledakan emas lembut
  const sp = particlePool(THREE, 1500, { tex, size: 1.6, gravity: -3, drag: 1.0 });
  scene.add(sp.points);
  const gc = gold.clone().multiplyScalar(1.4), wc = P.warm.clone().multiplyScalar(1.4);
  const shots = [[0.32, -26, 30, -90], [0.55, 24, 36, -95], [0.76, -4, 40, -100], [0.92, 14, 26, -90]].map(([t, x, y, z]) => ({ t: t * duration / 1000, x, y, z, f: false }));

  return {
    update(t, dt) {
      const T = t * duration / 1000;
      hang.forEach((h) => { h.piv.rotation.z = Math.sin(T * 2.0 + h.ph) * 0.1; h.piv.rotation.x = Math.sin(T * 1.5 + h.ph * 1.3) * 0.05; });
      shots.forEach((s) => { if (!s.f && T >= s.t) { s.f = true; sp.burst(s.x, s.y, s.z, gc, 160, 22, 1.8, rnd); sp.burst(s.x, s.y, s.z, wc, 60, 12, 1.3, rnd); } });
      if (dt > 0) for (let i = 0; i < 3; i++) sp.emit((rnd() - 0.5) * 60, -1 + rnd() * 8, -20 - rnd() * 50, 0, 1.2 + rnd(), 0, gc, 1.6 + rnd());
      sp.update(dt);
      sun.material.opacity = 0.8 + Math.sin(T * 1.3) * 0.05;
      const k = easeInOut(t);
      camera.position.set(lerp(-5, 4, k), lerp(3.2, 5, k), lerp(30, 26, k));
      camera.lookAt(lerp(-1, 1, k), lerp(9, 10, k), -45);
    },
    dispose() { tex.dispose(); cloudTex.dispose(); weave.dispose(); kGeo.dispose(); strandGeo.dispose(); archGeo.dispose(); winGeo.dispose(); },
  };
}
