// HUT RI: langit fajar, tiang tinggi, Merah Putih (rasio 2:3) berkibar dengan gelombang per-vertex, konfeti, bokeh.
import { pal, rng, skyDome, glowTexture, glowSprite, canvasTex, easeInOut, lerp } from '../common.js';

const RED = '#E70011', WHITE = '#FFFFFF';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1708);
  const tex = glowTexture(THREE);
  const dawn = P.to.clone().lerp(new THREE.Color('#ffb089'), 0.45);

  scene.fog = new THREE.FogExp2(P.from.clone().lerp(dawn, 0.25).multiplyScalar(0.7), 0.004);
  scene.add(skyDome(THREE, [
    [0.36, dawn.clone().lerp(new THREE.Color('#ffe2c2'), 0.5)],
    [0.48, dawn],
    [0.68, P.to.clone().lerp(P.from, 0.55)],
    [1, P.from.clone().multiplyScalar(0.85)],
  ]));
  const sun = glowSprite(THREE, tex, '#ffd9a8', 110, 0.4); sun.position.set(-85, 14, -150); scene.add(sun);
  const sun2 = glowSprite(THREE, tex, P.accent, 300, 0.12); sun2.position.copy(sun.position); scene.add(sun2);

  // awan lembut
  const cloudTex = canvasTex(THREE, 128, 64, (g, w, h) => {
    const r = g.createRadialGradient(w / 2, h / 2, 2, w / 2, h / 2, w / 2);
    r.addColorStop(0, 'rgba(255,236,224,0.75)'); r.addColorStop(1, 'rgba(255,236,224,0)');
    g.fillStyle = r; g.save(); g.scale(1, 0.5); g.translate(0, h / 2); g.fillRect(0, 0, w, h * 2); g.restore();
  });
  const clouds = [];
  for (let i = 0; i < 16; i++) {
    const c = glowSprite(THREE, cloudTex, '#ffe6d8', 40 + rnd() * 50, 0.5);
    c.scale.y = c.scale.x * 0.3; c.position.set(-110 + rnd() * 220, 2 + rnd() * 30, -70 - rnd() * 70);
    c.material.fog = false; scene.add(c); clouds.push({ c, v: 0.4 + rnd() * 0.6 });
  }

  // cahaya
  scene.add(new THREE.HemisphereLight('#ffe9dc', P.from.clone().lerp(dawn, 0.3), 0.95));
  const key = new THREE.DirectionalLight('#fff0dc', 1.3); key.position.set(-14, 18, 26); scene.add(key);
  const rimL = new THREE.DirectionalLight('#ffb98a', 0.8); rimL.position.set(-30, 6, -30); scene.add(rimL);

  // tiang
  const metal = new THREE.MeshStandardMaterial({ color: '#e4e4ea', roughness: 0.35, metalness: 0.35 });
  const goldM = new THREE.MeshStandardMaterial({ color: P.accent.clone().lerp(new THREE.Color('#ffd56a'), 0.6), roughness: 0.3, metalness: 0.45 });
  const FX = -3.2, TOP = 15;
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.17, TOP + 4, 14), metal); pole.position.set(FX, (TOP - 4) / 2 - 0.0, 0);
  pole.position.y = (TOP + 4) / 2 - 4; scene.add(pole);
  const ball = new THREE.Mesh(new THREE.SphereGeometry(0.32, 20, 14), goldM); ball.position.set(FX, TOP + 0.35, 0); scene.add(ball);
  const base = new THREE.Mesh(new THREE.CylinderGeometry(0.7, 0.9, 0.8, 18), metal); base.position.set(FX, -3.6, 0); scene.add(base);
  const ground = new THREE.Mesh(new THREE.CircleGeometry(60, 40), new THREE.MeshStandardMaterial({ color: P.from.clone().lerp(dawn, 0.15).multiplyScalar(0.3), roughness: 1 }));
  ground.rotation.x = -Math.PI / 2; ground.position.y = -4; scene.add(ground);

  // bendera 2:3 (tinggi:lebar); merah atas, putih bawah
  const FW = 8.4, FH = FW * 2 / 3, SX = 48, SY = 32;
  const flagTex = canvasTex(THREE, 8, 64, (g, w, h) => {
    g.fillStyle = RED; g.fillRect(0, 0, w, h / 2);
    g.fillStyle = WHITE; g.fillRect(0, h / 2, w, h / 2);
  });
  flagTex.magFilter = THREE.NearestFilter; flagTex.minFilter = THREE.NearestFilter; flagTex.generateMipmaps = false;
  const fg = new THREE.PlaneGeometry(FW, FH, SX, SY);
  const fm = new THREE.MeshStandardMaterial({ map: flagTex, side: THREE.DoubleSide, roughness: 0.75, metalness: 0, toneMapped: false });
  const flag = new THREE.Mesh(fg, fm);
  flag.position.set(FX + 0.11 + FW / 2, TOP - 0.3 - FH / 2, 0);
  scene.add(flag);
  // simpul pengikat
  [0.25, 0.75].forEach((u) => { const r = new THREE.Mesh(new THREE.TorusGeometry(0.13, 0.03, 6, 12), goldM); r.position.set(FX + 0.14, TOP - 0.3 - FH * u * 0.5 - 0.1, 0); r.rotation.y = Math.PI / 2; scene.add(r); });
  const p0 = fg.attributes.position, base0 = new Float32Array(p0.array);
  function wave(T) {
    for (let i = 0; i < p0.count; i++) {
      const x0 = base0[i * 3], y0 = base0[i * 3 + 1];
      const u = (x0 + FW / 2) / FW; // 0 di tiang, 1 di ujung bebas
      const amp = 0.1 + 1.5 * u * u;
      const w1 = Math.sin(u * 7.0 - T * 5.2 + y0 * 0.6) * amp;
      const w2 = Math.sin(u * 13.0 - T * 8.1 - y0 * 0.9) * amp * 0.22;
      p0.setZ(i, w1 + w2);
      p0.setX(i, x0 - (w1 * w1) * 0.05 * u); // sedikit menyusut saat berombak
      p0.setY(i, y0 + Math.sin(u * 5 - T * 5) * 0.08 * u);
    }
    p0.needsUpdate = true;
    fg.computeVertexNormals();
  }

  // konfeti merah-putih
  const NC = 260, cGeo = new THREE.PlaneGeometry(0.22, 0.34);
  const cMat = new THREE.MeshBasicMaterial({ side: THREE.DoubleSide, fog: false });
  const conf = new THREE.InstancedMesh(cGeo, cMat, NC);
  const cd = [], dummy = new THREE.Object3D(), col = new THREE.Color();
  for (let i = 0; i < NC; i++) {
    cd.push({ x: (rnd() - 0.5) * 34, y: rnd() * 26 - 4, z: (rnd() - 0.4) * 22, r: rnd() * 6, s: 0.5 + rnd(), v: 1.2 + rnd() * 1.8, w: rnd() * 6 });
    col.set(i % 2 ? RED : WHITE); conf.setColorAt(i, col);
  }
  scene.add(conf);

  // bokeh halus
  const NB = 40, bokeh = [];
  for (let i = 0; i < NB; i++) {
    const b = glowSprite(THREE, tex, i % 3 ? '#ffd2a0' : '#ffffff', 2 + rnd() * 5, 0.08 + rnd() * 0.12);
    b.position.set((rnd() - 0.5) * 50, rnd() * 24 - 2, 4 + rnd() * 12); b.material.fog = false; scene.add(b);
    bokeh.push({ b, y: b.position.y, ph: rnd() * 6, sp: 0.2 + rnd() * 0.4 });
  }

  return {
    update(t, dt) {
      const T = t * duration / 1000;
      wave(T);
      for (let i = 0; i < NC; i++) {
        const c = cd[i];
        c.y -= c.v * dt; c.x += Math.sin(T * 1.5 + c.w) * dt * 0.8; c.r += dt * (1 + c.s);
        if (c.y < -4) c.y = 22;
        dummy.position.set(c.x, c.y, c.z);
        dummy.rotation.set(c.r, c.r * 0.7, c.w);
        dummy.updateMatrix(); conf.setMatrixAt(i, dummy.matrix);
      }
      conf.instanceMatrix.needsUpdate = true;
      bokeh.forEach((o) => { o.b.position.y = o.y + Math.sin(T * o.sp + o.ph) * 0.8; });
      clouds.forEach((c) => { c.c.position.x += c.v * dt * 2.5; if (c.c.position.x > 120) c.c.position.x = -120; });
      const k = easeInOut(t);
      camera.position.set(lerp(-3, 4, k), lerp(5, 9, k), lerp(27, 22, k));
      camera.lookAt(lerp(0, 1.2, k), 11, 0);
    },
    dispose() { tex.dispose(); cloudTex.dispose(); flagTex.dispose(); cGeo.dispose(); conf.dispose(); },
  };
}
