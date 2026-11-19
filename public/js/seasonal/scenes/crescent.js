// Ramadan: bulan sabit 3D besar, bintang berkelip, siluet masjid, lentera fanous bergantung.
import { pal, rng, skyDome, stars, glowTexture, glowSprite, crescentShape, easeInOut, smooth, seg, lerp } from '../common.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1445);
  const tex = glowTexture(THREE);
  const night = new THREE.Color('#030a14');

  scene.fog = new THREE.FogExp2(P.from.clone().lerp(night, 0.6), 0.0032);
  scene.add(skyDome(THREE, [
    [0.4, P.to.clone().multiplyScalar(0.7)],
    [0.55, P.from.clone().lerp(P.to, 0.25)],
    [0.85, P.from.clone().lerp(night, 0.55)],
    [1, night],
  ]));
  const st = stars(THREE, 700, rnd, { tex, size: 3, minY: 0.15, color: '#fff6dc' });
  scene.add(st.points);

  scene.add(new THREE.HemisphereLight(P.to.clone().lerp(new THREE.Color('#bcd'), 0.3), night, 0.6));
  const key = new THREE.DirectionalLight(P.warm, 0.9);
  key.position.set(-20, 30, 30);
  scene.add(key);

  // bulan sabit
  const crescent = new THREE.Mesh(
    new THREE.ExtrudeGeometry(crescentShape(THREE, 1, 0.42, 0.82), { depth: 0.22, bevelEnabled: true, bevelThickness: 0.06, bevelSize: 0.04, bevelSegments: 3, curveSegments: 48 }),
    new THREE.MeshStandardMaterial({ color: P.warm, emissive: P.accent, emissiveIntensity: 1.1, roughness: 0.45, metalness: 0.2, fog: false }),
  );
  crescent.scale.setScalar(15);
  crescent.position.set(-20, 31, -75);
  crescent.rotation.set(0, 0.35, 0.5);
  scene.add(crescent);
  const glow = glowSprite(THREE, tex, P.accent, 90, 0.5); glow.position.set(-20, 31, -78); scene.add(glow);
  const glow2 = glowSprite(THREE, tex, P.warm, 190, 0.14); glow2.position.copy(glow.position); scene.add(glow2);

  // siluet masjid
  const sil = new THREE.MeshStandardMaterial({ color: night.clone().lerp(P.from, 0.05), roughness: 1 });
  const rim = new THREE.MeshStandardMaterial({ color: P.accent.clone().multiplyScalar(0.8), roughness: 0.4, metalness: 0.7, emissive: P.accent, emissiveIntensity: 0.25 });
  const mosque = new THREE.Group();
  const dome = (x, z, r, base) => {
    const d = new THREE.Mesh(new THREE.SphereGeometry(r, 24, 14, 0, Math.PI * 2, 0, Math.PI * 0.62), sil);
    d.position.set(x, base, z); mosque.add(d);
    const fin = new THREE.Mesh(new THREE.ConeGeometry(r * 0.07, r * 0.5, 6), rim); fin.position.set(x, base + r * 1.12, z); mosque.add(fin);
  };
  const block = (x, z, w, h, d) => { const b = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), sil); b.position.set(x, h / 2 - 2, z); mosque.add(b); };
  const minaret = (x, z, h) => {
    const sh = new THREE.Mesh(new THREE.CylinderGeometry(0.9, 1.15, h, 10), sil); sh.position.set(x, h / 2 - 2, z); mosque.add(sh);
    const bal = new THREE.Mesh(new THREE.CylinderGeometry(1.6, 1.2, 0.8, 10), sil); bal.position.set(x, h * 0.72 - 2, z); mosque.add(bal);
    const top = new THREE.Mesh(new THREE.ConeGeometry(1.1, 4.2, 10), sil); top.position.set(x, h - 2 + 2.1, z); mosque.add(top);
    const tip = new THREE.Mesh(new THREE.SphereGeometry(0.3, 8, 6), rim); tip.position.set(x, h + 0.5, z); mosque.add(tip);
  };
  block(0, 0, 22, 9, 12); block(-13, 0, 6, 6, 9); block(13, 0, 6, 6, 9);
  dome(0, 0, 8.5, 7); dome(-13, 0, 3.4, 4); dome(13, 0, 3.4, 4); dome(-6.5, 5, 2.6, 7); dome(6.5, 5, 2.6, 7);
  minaret(-21, 0, 26); minaret(21, 0, 26); minaret(-30, -2, 18); minaret(30, -2, 18);
  // lengkung gerbang (arches) di muka bangunan
  const archGeo = new THREE.TorusGeometry(1.5, 0.22, 6, 14, Math.PI);
  for (let i = -3; i <= 3; i++) {
    const a = new THREE.Mesh(archGeo, rim); a.position.set(i * 3, 1.4, 6.2); mosque.add(a);
    const w = new THREE.Mesh(new THREE.PlaneGeometry(2.2, 3.6), new THREE.MeshBasicMaterial({ color: P.accent.clone().multiplyScalar(0.85) }));
    w.position.set(i * 3, 0.4, 6.15); mosque.add(w);
  }
  mosque.position.set(2, 0, -55);
  mosque.scale.setScalar(1.15);
  scene.add(mosque);
  // tanah / cakrawala rendah
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(500, 300), new THREE.MeshStandardMaterial({ color: night.clone(), roughness: 1 }));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, -2.2, -70);
  scene.add(ground);
  // bukit/kubah kecil sebagai kedalaman
  for (let i = 0; i < 9; i++) {
    const h = new THREE.Mesh(new THREE.SphereGeometry(5 + rnd() * 4, 12, 8, 0, Math.PI * 2, 0, Math.PI / 2), sil);
    h.scale.y = 0.35; h.position.set(-90 + i * 22 + rnd() * 6, -2.2, -85 - rnd() * 20); scene.add(h);
  }

  // fanous: profil lathe (badan bertingkat + puncak + dasar)
  const prof = [[0.01, -1.2], [0.25, -1.15], [0.28, -1.0], [0.55, -0.6], [0.62, -0.1], [0.5, 0.45], [0.3, 0.78], [0.22, 0.9], [0.32, 0.98], [0.08, 1.2], [0.01, 1.3]].map(([x, y]) => new THREE.Vector2(x, y));
  const bodyGeo = new THREE.LatheGeometry(prof, 6); // 6 segmen = kaca bersegi
  const capGeo = new THREE.LatheGeometry([[0.01, 0.78], [0.3, 0.86], [0.12, 1.05], [0.04, 1.35], [0.01, 1.4]].map(([x, y]) => new THREE.Vector2(x, y)), 12);
  const baseGeo = new THREE.LatheGeometry([[0.01, -1.3], [0.2, -1.3], [0.26, -1.15], [0.02, -1.1]].map(([x, y]) => new THREE.Vector2(x, y)), 12);
  const lanterns = [];
  const defs = [[-11, 17, 6, 1.0], [-4.5, 14.5, 10, 1.15], [3.5, 16, 8, 1.0], [10, 13.5, 11, 1.2]];
  defs.forEach(([x, top, z, s], i) => {
    const col = i % 2 ? P.accent.clone() : P.accent.clone().lerp(new THREE.Color('#ff7a3c'), 0.35);
    const pivot = new THREE.Group(); pivot.position.set(x, top, z); scene.add(pivot);
    const len = 5 + i * 0.8;
    const cord = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, len, 5), rim); cord.position.y = -len / 2; pivot.add(cord);
    const lan = new THREE.Group(); lan.position.y = -len - 1.4 * s; lan.scale.setScalar(s); pivot.add(lan);
    const glass = new THREE.Mesh(bodyGeo, new THREE.MeshStandardMaterial({ color: col.clone().multiplyScalar(0.5), emissive: col, emissiveIntensity: 0.9, roughness: 0.25, flatShading: true, transparent: true, opacity: 0.92 }));
    lan.add(glass);
    const metal = new THREE.MeshStandardMaterial({ color: '#2a1a0c', roughness: 0.5, metalness: 0.8 });
    lan.add(new THREE.Mesh(capGeo, metal), new THREE.Mesh(baseGeo, metal));
    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.1, 0.03, 6, 10), rim); ring.position.y = 1.42; lan.add(ring);
    const light = new THREE.PointLight(col, 26, 22, 1.5); light.position.y = -0.1; lan.add(light);
    const halo = glowSprite(THREE, tex, col, 5.2 * s, 0.75); lan.add(halo);
    lanterns.push({ pivot, ph: i * 1.7, light, halo });
  });

  return {
    update(t, dt) {
      const T = t * duration / 1000;
      lanterns.forEach((l, i) => {
        l.pivot.rotation.z = Math.sin(T * 2.1 + l.ph) * 0.09;
        l.pivot.rotation.x = Math.cos(T * 1.7 + l.ph) * 0.04;
        const f = 0.9 + Math.sin(T * 7 + i * 2) * 0.1;
        l.light.intensity = 26 * f; l.halo.material.opacity = 0.7 * f;
      });
      crescent.rotation.y = 0.35 + Math.sin(T * 0.9) * 0.12;
      glow.material.opacity = 0.45 + Math.sin(T * 1.6) * 0.06;
      st.update(T);
      const k = easeInOut(t);
      camera.position.set(lerp(-3, 3, k), lerp(3.5, 6, k), lerp(30, 24, k));
      camera.lookAt(lerp(-2, 1, k), lerp(11, 13, k), -45);
    },
    dispose() { tex.dispose(); bodyGeo.dispose(); capGeo.dispose(); baseGeo.dispose(); archGeo.dispose(); },
  };
}
