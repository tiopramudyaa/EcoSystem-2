// HUT RI: upacara pengibaran Merah Putih (rasio 2:3) saat fajar. Bendera naik di tiang sambil berkibar, barisan peserta hormat
// (paskibraka, pramuka, veteran, warga, anak) sebagai siluet berlatar cahaya fajar, bambu runcing di kedua sisi, konfeti tipis.
import { pal, rng, skyDome, glowTexture, glowSprite, canvasTex, easeInOut, smooth, seg, lerp } from '../common.js';
import { peopleMaterial, createCrowd } from './_people.js';

const RED = '#E70011', WHITE = '#FFFFFF';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1708);
  const tex = glowTexture(THREE);
  const K = (h) => new THREE.Color(h);
  const owned = [];
  const own = (...o) => { owned.push(...o); return o[0]; };

  const horizon = K('#ffc08a');
  scene.fog = new THREE.FogExp2(K('#d98a7a').lerp(P.from, 0.25).multiplyScalar(0.8), 0.0075);
  scene.add(skyDome(THREE, [
    [0.45, K('#ffe0b0')], [0.5, K('#ffab72')], [0.57, K('#e0668a')], [0.68, K('#6a4f9e')], [1, K('#1a1d48').lerp(P.from, 0.25)],
  ]));
  const SUN = new THREE.Vector3(-14, 9, -120);
  const sun = glowSprite(THREE, tex, K('#fff0c8'), 70, 0.95); sun.position.copy(SUN); scene.add(sun);
  const sun2 = glowSprite(THREE, tex, horizon, 260, 0.35); sun2.position.copy(SUN); scene.add(sun2);
  // sinar fajar (god rays) halus
  const rayTex = canvasTex(THREE, 16, 128, (g, w, h) => {
    const r = g.createLinearGradient(0, 0, 0, h); r.addColorStop(0, 'rgba(255,220,170,0)'); r.addColorStop(0.3, 'rgba(255,220,170,0.55)'); r.addColorStop(1, 'rgba(255,220,170,0)');
    g.fillStyle = r; g.fillRect(0, 0, w, h);
  });
  const rays = [];
  for (let i = 0; i < 7; i++) {
    const g = own(new THREE.PlaneGeometry(6 + rnd() * 9, 190)); g.translate(0, 95, 0);
    const m = new THREE.Mesh(g, own(new THREE.MeshBasicMaterial({ map: rayTex, transparent: true, opacity: 0.1, blending: THREE.AdditiveBlending, depthWrite: false, fog: false, side: THREE.DoubleSide })));
    m.position.copy(SUN); m.position.z += 2; m.rotation.z = -1.15 + i * 0.33; scene.add(m); rays.push(m);
  }
  // awan fajar (Points)
  const cloudTex = canvasTex(THREE, 128, 64, (g, w, h) => {
    for (let i = 0; i < 5; i++) {
      const x = w * (0.2 + rnd() * 0.6), y = h * (0.4 + rnd() * 0.2), r = w * (0.15 + rnd() * 0.15);
      const gr = g.createRadialGradient(x, y, 0, x, y, r); gr.addColorStop(0, 'rgba(255,255,255,0.6)'); gr.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = gr; g.fillRect(0, 0, w, h);
    }
  });
  const nC = 18, cA = new Float32Array(nC * 3);
  for (let i = 0; i < nC; i++) cA.set([-90 + rnd() * 180, 6 + rnd() * 26, -80 - rnd() * 30], i * 3);
  const cG = own(new THREE.BufferGeometry()); cG.setAttribute('position', new THREE.BufferAttribute(cA, 3));
  const clouds = new THREE.Points(cG, own(new THREE.PointsMaterial({ map: cloudTex, color: K('#ffb9a0'), size: 55, sizeAttenuation: true, transparent: true, opacity: 0.45, depthWrite: false, fog: false })));
  clouds.frustumCulled = false; scene.add(clouds);

  // cahaya: hemisphere + rim dari fajar + isi dingin dari depan
  scene.add(new THREE.HemisphereLight(K('#ffbfa0'), K('#2a3050'), 0.32));
  const rim = new THREE.DirectionalLight(K('#ffbd85'), 2.4); rim.position.set(-16, 8, -60); scene.add(rim);
  const fill = new THREE.DirectionalLight(K('#c8d4ff'), 0.4); fill.position.set(6, 8, 30); scene.add(fill);

  // tanah dan bukit jauh
  const ground = new THREE.Mesh(own(new THREE.PlaneGeometry(1000, 600)), own(new THREE.MeshStandardMaterial({ color: '#22301f', roughness: 1 })));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, 0, -200); scene.add(ground);
  const hillM = own(new THREE.MeshStandardMaterial({ color: '#2a2f4a', roughness: 1 })), hillG = own(new THREE.SphereGeometry(1, 16, 8, 0, Math.PI * 2, 0, Math.PI / 2));
  [[-70, -110, 60, 9], [20, -130, 80, 12], [95, -105, 55, 8], [-20, -95, 40, 5]].forEach(([x, z, w, h]) => { const m = new THREE.Mesh(hillG, hillM); m.position.set(x, 0, z); m.scale.set(w, h, 14); scene.add(m); });

  // tiang dan bendera
  const metal = own(new THREE.MeshStandardMaterial({ color: '#e4e4ea', roughness: 0.35, metalness: 0.4 }));
  const goldM = own(new THREE.MeshStandardMaterial({ color: '#e8c15a', roughness: 0.3, metalness: 0.6 }));
  const PX = -0.6, PZ = -4, TOP = 14.8;
  const pole = new THREE.Mesh(own(new THREE.CylinderGeometry(0.1, 0.16, TOP + 0.5, 12)), metal); pole.position.set(PX, TOP / 2 + 0.25, PZ); scene.add(pole);
  const ball = new THREE.Mesh(own(new THREE.SphereGeometry(0.3, 16, 12)), goldM); ball.position.set(PX, TOP + 0.55, PZ); scene.add(ball);
  const base = new THREE.Mesh(own(new THREE.CylinderGeometry(0.7, 0.9, 0.6, 16)), metal); base.position.set(PX, 0.3, PZ); scene.add(base);
  const FW = 7.8, FH = FW * 2 / 3, SX = 40, SY = 26;
  const flagTex = canvasTex(THREE, 8, 64, (g, w, h) => { g.fillStyle = RED; g.fillRect(0, 0, w, h / 2); g.fillStyle = WHITE; g.fillRect(0, h / 2, w, h / 2); });
  flagTex.magFilter = THREE.NearestFilter; flagTex.minFilter = THREE.NearestFilter; flagTex.generateMipmaps = false;
  const fg = own(new THREE.PlaneGeometry(FW, FH, SX, SY));
  const flag = new THREE.Mesh(fg, own(new THREE.MeshStandardMaterial({ map: flagTex, side: THREE.DoubleSide, roughness: 0.75, toneMapped: false, emissive: K('#ffffff'), emissiveMap: flagTex, emissiveIntensity: 0.7 })));
  flag.frustumCulled = false; scene.add(flag);
  const p0 = fg.attributes.position, base0 = new Float32Array(p0.array);
  function wave(T, amp) {
    for (let i = 0; i < p0.count; i++) {
      const x0 = base0[i * 3], y0 = base0[i * 3 + 1], u = (x0 + FW / 2) / FW;
      const a = (0.08 + 1.35 * u * u) * amp;
      const w1 = Math.sin(u * 7.0 - T * 5.0 + y0 * 0.5) * a, w2 = Math.sin(u * 13.0 - T * 8.0 - y0 * 0.8) * a * 0.2;
      p0.setZ(i, w1 + w2);
      p0.setX(i, x0 - w1 * w1 * 0.05 * u);
      p0.setY(i, y0 + Math.sin(u * 5 - T * 5) * 0.07 * u * amp);
    }
    p0.needsUpdate = true; fg.computeVertexNormals();
  }

  // bambu runcing: batang, ruas, ujung runcing, pita merah putih (instanced)
  const bamG = own(new THREE.CylinderGeometry(0.07, 0.11, 1, 7)); bamG.translate(0, 0.5, 0);
  const tipG = own(new THREE.ConeGeometry(0.07, 0.9, 7)); tipG.translate(0, 0.45, 0);
  const nodeG = own(new THREE.TorusGeometry(0.1, 0.022, 5, 10)); nodeG.rotateX(Math.PI / 2);
  const ribG = own(new THREE.CylinderGeometry(0.125, 0.125, 0.22, 8, 1, true));
  const bamM = own(new THREE.MeshStandardMaterial({ color: '#a39a45', roughness: 0.7 })), ribM = own(new THREE.MeshStandardMaterial({ roughness: 0.8, side: THREE.DoubleSide }));
  const stalks = [];
  [-1, 1].forEach((sd) => { for (let k = 0; k < 9; k++) stalks.push({ sd, x: sd * (9.2 + (k % 3) * 0.5 + (k / 3 | 0) * 0.45), z: -2.5 - (k % 3) * 0.7 - (k / 3 | 0) * 0.3, h: 6.8 + ((k * 5) % 4) * 0.55, lean: 0.07 + (k % 4) * 0.035 }); });
  const NB = stalks.length, bam = new THREE.InstancedMesh(bamG, bamM, NB), tip = new THREE.InstancedMesh(tipG, bamM, NB), nodes = new THREE.InstancedMesh(nodeG, bamM, NB * 3), rbn = new THREE.InstancedMesh(ribG, ribM, NB);
  const dm = new THREE.Object3D(), col = new THREE.Color();
  stalks.forEach((s, i) => {
    const th = -s.sd * s.lean, sx = -Math.sin(th), cy = Math.cos(th); // arah batang
    const at = (d) => dm.position.set(s.x + sx * d, cy * d, s.z);
    dm.rotation.set(0, 0, th);
    at(0); dm.scale.set(1, s.h, 1); dm.updateMatrix(); bam.setMatrixAt(i, dm.matrix);
    at(s.h); dm.scale.set(1, 1, 1); dm.updateMatrix(); tip.setMatrixAt(i, dm.matrix);
    for (let n = 0; n < 3; n++) { at(s.h * (0.2 + n * 0.25)); dm.updateMatrix(); nodes.setMatrixAt(i * 3 + n, dm.matrix); }
    at(s.h * 0.86); dm.updateMatrix(); rbn.setMatrixAt(i, dm.matrix);
    rbn.setColorAt(i, col.set(i % 2 ? RED : WHITE));
  });
  scene.add(bam, tip, nodes, rbn);

  // kerumunan: baris siluet (rigid, 1 draw per orang) + beberapa figur depan yang terang
  const sil = peopleMaterial(THREE, { rim: '#ffb98a', power: 2.0, strength: 1.5, fill: 0.1 });
  const lit = peopleMaterial(THREE, { rim: '#ffd0a0', power: 2.6, strength: 0.9, fill: 0.3 });
  const SK = ['#b87a4f', '#c98f62', '#d9a577', '#a86e45', '#8f5d3a'];
  const pick = (a) => a[Math.floor(rnd() * a.length)];
  const kind = (row, k) => {
    const r = rnd(), sk = pick(SK), base = { skin: sk, pose: 'salute', yaw: Math.PI };
    const fem = rnd() < 0.4;
    const pk = row === 0 ? 0.6 : row === 1 ? 0.3 : 0.12, sc = 0.25, vt = row >= 2 ? 0.22 : 0.1;
    if (r < pk) return fem
      ? Object.assign(base, { top: '#f4f4f4', bottom: '#eeeeee', lower: 'skirt', hat: 'hijab', hatColor: '#f4f4f4', sash: '#d4111b', height: 0.98 })
      : Object.assign(base, { top: '#f4f4f4', bottom: '#ededed', hat: 'cap', hatColor: '#f4f4f4', sash: '#d4111b', hair: '#161010', height: 1.08 });
    if (r < pk + sc) return Object.assign(base, { top: '#a06f3a', bottom: '#7a4d26', hat: 'beret', hatColor: '#6a4220', collar: '#d4111b', height: 1.0 });
    if (r < pk + sc + vt) return Object.assign(base, { top: '#6f7a45', bottom: '#5e6a3a', hat: 'cap', hatColor: '#4f5a2e', hair: '#d8d8d8', height: 1.0, collar: '#e0c060' });
    if (r < 0.9 || row >= 3) return fem
      ? Object.assign(base, { top: pick(['#c0392b', '#f4f4f4', '#3f5a8a', '#c9a24a']), lower: 'skirt', bottom: '#5a3a1e', hat: 'hijab', hatColor: pick(['#f4f4f4', '#d4111b', '#2a3f6a']), height: 0.98 })
      : Object.assign(base, { top: pick(['#f0e6d0', '#7a1a1a', '#2a3f6a', '#e8e8e8']), bottom: '#252525', hat: 'peci', hatColor: '#111', height: 1.08 });
    return Object.assign(base, { top: pick(['#d4111b', '#f4f4f4']), bottom: pick(['#f4f4f4', '#d4111b']), hat: 'hair', child: true, height: 1.12 });
  };
  const rows = [[6.0, 12, 1.2], [4.0, 13, 1.2], [2.0, 14, 1.2], [0.0, 15, 1.2]];
  const specs = [];
  rows.forEach(([z, n, sp], row) => { for (let k = 0; k < n; k++) specs.push(Object.assign(kind(row, k), { rigid: true, x: (k - (n - 1) / 2) * sp + (rnd() - 0.5) * 0.25 + (row % 2) * 0.3, z: z + (rnd() - 0.5) * 0.3, material: sil })); });
  const crowd = createCrowd(THREE, specs, sil); scene.add(crowd.group);
  const front = createCrowd(THREE, [
    { x: -6.4, z: 8.2, pose: 'salute', top: '#d4111b', bottom: '#f4f4f4', hat: 'hair', child: true, height: 1.15, skin: SK[1] },
    { x: -3.2, z: 8.0, pose: 'salute', top: '#f4f4f4', bottom: '#f4f4f4', hat: 'cap', hatColor: '#f4f4f4', sash: '#d4111b', height: 1.1, skin: SK[2] },
    { x: 0.4, z: 8.3, pose: 'salute', top: '#6f7a45', bottom: '#5e6a3a', hat: 'cap', hatColor: '#4f5a2e', hair: '#d8d8d8', collar: '#e0c060', height: 1.04, skin: SK[3] },
    { x: 3.6, z: 8.0, pose: 'salute', top: '#f4f4f4', bottom: '#eeeeee', lower: 'skirt', hat: 'hijab', hatColor: '#f4f4f4', sash: '#d4111b', height: 1.0, skin: SK[0] },
    { x: 6.8, z: 8.2, pose: 'salute', top: '#a06f3a', bottom: '#7a4d26', hat: 'beret', hatColor: '#6a4220', collar: '#d4111b', height: 1.06, skin: SK[4] },
  ].map((s) => Object.assign(s, { yaw: Math.PI, material: lit })), lit);
  scene.add(front.group);

  // konfeti merah putih yang tertahan
  const NC = 110, cGeo = own(new THREE.PlaneGeometry(0.2, 0.3)), conf = new THREE.InstancedMesh(cGeo, own(new THREE.MeshBasicMaterial({ side: THREE.DoubleSide, fog: false })), NC);
  const cd = [];
  for (let i = 0; i < NC; i++) { cd.push({ x: (rnd() - 0.5) * 30, y: 6 + rnd() * 18, z: -6 + rnd() * 14, r: rnd() * 6, v: 0.7 + rnd() * 0.9, w: rnd() * 6 }); conf.setColorAt(i, col.set(i % 2 ? RED : WHITE)); }
  scene.add(conf);
  // bokeh hangat
  const nB = 40, bA = new Float32Array(nB * 3);
  for (let i = 0; i < nB; i++) bA.set([(rnd() - 0.5) * 50, 2 + rnd() * 18, -10 + rnd() * 18], i * 3);
  const bG = own(new THREE.BufferGeometry()); bG.setAttribute('position', new THREE.BufferAttribute(bA, 3));
  const bMat = own(new THREE.PointsMaterial({ map: tex, color: K('#ffd2a0'), size: 3.2, sizeAttenuation: true, transparent: true, opacity: 0.22, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  const bok = new THREE.Points(bG, bMat); bok.frustumCulled = false; scene.add(bok);

  return {
    update(t, dt) {
      const T = t * duration / 1000, e = easeInOut(seg(t, 0.04, 0.84)), ramp = smooth(t / 0.2);
      wave(T, 0.55 + 0.45 * smooth(seg(t, 0.05, 0.4)));
      const cy = lerp(2.6 + FH / 2, TOP - 0.3 - FH / 2, e);
      flag.position.set(PX + 0.11 + FW / 2, cy, PZ);
      crowd.update(dt); front.update(dt);
      for (let i = 0; i < NC; i++) {
        const c = cd[i]; c.y -= c.v * dt; c.r += dt * 1.5; c.x += Math.sin(T * 1.4 + c.w) * dt * 0.5; if (c.y < 3) c.y = 24;
        dm.position.set(c.x, c.y, c.z); dm.rotation.set(c.r, c.r * 0.7, c.w); dm.scale.set(1, 1, 1); dm.updateMatrix(); conf.setMatrixAt(i, dm.matrix);
      }
      conf.instanceMatrix.needsUpdate = true;
      for (let i = 0; i < nC; i++) { cA[i * 3] += dt * 1.2; if (cA[i * 3] > 100) cA[i * 3] = -100; }
      cG.attributes.position.needsUpdate = true;
      sun.material.opacity = 0.9 * ramp + 0.04 * Math.sin(T * 1.3);
      rays.forEach((r, i) => { r.material.opacity = (0.08 + 0.04 * Math.sin(T * 0.9 + i)) * ramp * (0.6 + 0.4 * smooth(t / 0.8)); });
      bMat.opacity = 0.14 + 0.1 * Math.sin(T * 1.5);
      const k = easeInOut(t);
      camera.position.set(lerp(-1.4, 1.2, k) + Math.sin(T * 0.6) * 0.1, lerp(3.2, 3.8, k), lerp(20, 17.5, k));
      camera.lookAt(lerp(-0.4, 0.2, k), lerp(5.4, 6.4, k), -4);
    },
    dispose() { tex.dispose(); rayTex.dispose(); cloudTex.dispose(); flagTex.dispose(); owned.forEach((o) => o.dispose && o.dispose()); crowd.dispose(); front.dispose(); sil.dispose(); lit.dispose(); [bam, tip, nodes, rbn, conf].forEach((m) => m.dispose()); rays.forEach((r) => r.geometry.dispose()); },
  };
}
