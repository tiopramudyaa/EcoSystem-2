// Tahun Baru: siluet kota malam, kembang api berlapis, pantulan di air, kamera maju pelan.
import { pal, rng, skyDome, stars, glowTexture, particlePool, skyline, smooth, easeInOut, seg } from '../common.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(2025);
  const tex = glowTexture(THREE);
  const WATER = -2;

  scene.fog = new THREE.FogExp2(P.to.clone().multiplyScalar(0.28), 0.0042);
  scene.add(skyDome(THREE, [
    [0.3, P.to.clone().multiplyScalar(0.95).lerp(P.accent, 0.1)],
    [0.42, P.to.clone().multiplyScalar(0.55)],
    [0.62, P.from.clone().multiplyScalar(0.85)],
    [1, P.from.clone().multiplyScalar(0.3)],
  ]));
  const st = stars(THREE, 520, rnd, { tex, size: 2.6, minY: 0.2, color: '#e8eeff' });
  scene.add(st.points);

  scene.add(new THREE.HemisphereLight(P.to.clone().lerp(new THREE.Color('#9db4ff'), 0.5), P.deep, 0.55));
  const key = new THREE.DirectionalLight(new THREE.Color('#9fb2e8'), 0.35);
  key.position.set(-20, 40, 30);
  scene.add(key);
  const flash = new THREE.PointLight(P.accent, 0, 160, 1.4);
  flash.position.set(0, 40, -40);
  scene.add(flash);

  // kota: dua lapis, yang jauh lebih pucat berkabut
  const far = skyline(THREE, rng(7), { count: 60, spread: 300, z: -150, minH: 8, maxH: 34, lit: 0.22, color: '#0b1020', windowColor: '#ffcf80' });
  const near = skyline(THREE, rng(11), { count: 40, spread: 240, z: -100, minH: 6, maxH: 22, lit: 0.28, color: '#05070d', windowColor: '#ffd98a' });
  scene.add(far, near);

  // air dengan kilau
  const water = new THREE.Mesh(
    new THREE.PlaneGeometry(500, 260),
    new THREE.MeshStandardMaterial({ color: '#070b16', roughness: 0.3, metalness: 0.5 }),
  );
  water.rotation.x = -Math.PI / 2;
  water.position.set(0, WATER, -90);
  scene.add(water);

  // kembang api + pantulan (Points kedua memakai geometri yang sama, dibalik)
  const pool = particlePool(THREE, 2300, { tex, size: 4.2, gravity: -9, drag: 0.9 });
  scene.add(pool.points);
  const refl = new THREE.Points(pool.points.geometry, pool.points.material.clone());
  refl.material.opacity = 0.32;
  refl.scale.y = -1;
  refl.position.y = 2 * WATER;
  refl.frustumCulled = false;
  scene.add(refl);

  const second = P.to.clone().lerp(new THREE.Color('#ffffff'), 0.4).multiplyScalar(1.4);
  const cols = [P.accent.clone().multiplyScalar(1.5), P.warm.clone().multiplyScalar(1.5), second, P.accent.clone().lerp(new THREE.Color('#ff8a3c'), 0.5).multiplyScalar(1.4)];
  const S = duration / 2800;
  const plan = [
    [0.30, -26, 38, -118, 0], [0.70, 22, 46, -125, 2], [1.05, 0, 54, -130, 1], [1.35, -38, 42, -122, 3],
    [1.65, 34, 40, -118, 0], [1.95, -12, 58, -135, 2], [2.2, 14, 48, -128, 1], [2.5, 0, 52, -126, -1],
  ].map(([t, x, y, z, c]) => ({ t: t * S, x, y, z, c, fired: false }));

  function fire(b) {
    b.fired = true;
    if (b.c < 0) { // penutup besar emas
      pool.burst(b.x, b.y, b.z, cols[0], 420, 46, 2.2, rnd);
      pool.burst(b.x, b.y, b.z, cols[1], 220, 30, 1.8, rnd);
    } else {
      pool.burst(b.x, b.y, b.z, cols[b.c], 200, 34, 1.9, rnd);
      pool.burst(b.x, b.y, b.z, cols[(b.c + 1) % 4], 70, 18, 1.5, rnd);
    }
    flash.color.copy(b.c < 0 ? cols[0] : cols[b.c]);
    flash.position.set(b.x, b.y + 10, b.z + 25);
    flash.intensity = b.c < 0 ? 2600 : 1500;
  }

  return {
    update(t, dt) {
      const T = t * duration / 1000;
      plan.forEach((b) => {
        const L = b.t - 0.5 * S;
        if (!b.fired && T >= L && T < b.t && dt > 0) {
          const k = (T - L) / (0.5 * S), y = WATER + (b.y - WATER) * (1 - Math.pow(1 - k, 2));
          pool.emit(b.x, y, b.z, (rnd() - 0.5) * 3, -6 - rnd() * 6, (rnd() - 0.5) * 3, cols[1], 0.5);
          pool.emit(b.x, y, b.z, (rnd() - 0.5) * 2, -3, (rnd() - 0.5) * 2, cols[0], 0.7);
        }
        if (!b.fired && T >= b.t) fire(b);
      });
      flash.intensity *= Math.exp(-4 * dt);
      pool.update(dt);
      st.update(T);
      // kamera maju pelan dan naik sedikit
      const k = easeInOut(t);
      camera.position.set(Math.sin(t * 1.2) * 3, 12 + 3 * k, 34 - 14 * k);
      camera.lookAt(0, 30 + 5 * smooth(seg(t, 0, 1)), -90);
    },
    dispose() { tex.dispose(); refl.material.dispose(); },
  };
}
