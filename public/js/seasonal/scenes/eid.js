// Lebaran: halaman rumah saat senja, keluarga besar bersalaman, berpelukan, sungkem; masjid di latar, rangkaian ketupat dua warna.
import { pal, rng, skyDome, glowTexture, glowSprite, canvasTex, particlePool, easeInOut, smooth, lerp } from '../common.js';
import { peopleMaterial, createCrowd } from './_people.js';

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1446);
  const tex = glowTexture(THREE);
  const K = (h) => new THREE.Color(h);
  const gold = K('#e8b63c'), janur = P.accent.clone(), dkGreen = P.primary.clone(), deep = P.from.clone();
  const owned = []; // geometri/material/texture yang dibuat di sini (selain material orang)

  scene.fog = new THREE.FogExp2(K('#c9a43a').lerp(dkGreen, 0.5).multiplyScalar(0.8), 0.011);
  scene.add(skyDome(THREE, [
    [0.46, K('#ffeeb0')], [0.52, K('#f4bd4a')], [0.57, K('#b9a73c')], [0.64, dkGreen.clone().lerp(K('#2f7d32'), 0.3)], [1, deep.clone().multiplyScalar(0.8)],
  ]));
  const sun = glowSprite(THREE, tex, K('#ffd978'), 110, 0.55); sun.position.set(-16, 9, -140); scene.add(sun);
  const sun2 = glowSprite(THREE, tex, K('#ffcf6a'), 300, 0.08); sun2.position.copy(sun.position); scene.add(sun2);
  // berkas cahaya tipis dari matahari
  const rayTex = canvasTex(THREE, 16, 128, (g, w, h) => {
    const r = g.createLinearGradient(0, 0, 0, h); r.addColorStop(0, 'rgba(255,230,150,0.0)'); r.addColorStop(0.35, 'rgba(255,230,150,0.5)'); r.addColorStop(1, 'rgba(255,230,150,0)');
    g.fillStyle = r; g.fillRect(0, 0, w, h);
  });
  const rays = [];
  for (let i = 0; i < 6; i++) {
    const m = new THREE.Mesh(new THREE.PlaneGeometry(7 + rnd() * 6, 150), new THREE.MeshBasicMaterial({ map: rayTex, transparent: true, opacity: 0.1, blending: THREE.AdditiveBlending, depthWrite: false, fog: false, side: THREE.DoubleSide }));
    m.position.set(-16, 14, -138); m.rotation.z = -0.9 + i * 0.32; m.geometry.translate(0, -75, 0); scene.add(m); rays.push(m);
  }

  // cahaya: hemisphere + rim hangat dari belakang + isi lembut dari depan + titik pelita
  scene.add(new THREE.HemisphereLight(K('#ffd98a'), K('#8a7436'), 0.45));
  const rim = new THREE.DirectionalLight(K('#ffb24d'), 1.3); rim.position.set(-14, 7, -24); scene.add(rim);
  const key = new THREE.DirectionalLight(K('#ffe8bd'), 0.6); key.position.set(5, 7, 14); scene.add(key);
  const pl = [[-2.5, 3.6, 3.4, 10], [3.4, 3.2, 2.6, 10], [7.5, 2.6, 0.5, 6], [-7.5, 3.0, 1.5, 7], [0.5, 2.2, 5.5, 6]].map(([x, y, z, i]) => { const l = new THREE.PointLight(K('#ffbf5e'), i, 12, 2); l.position.set(x, y, z); scene.add(l); return l; });

  // tanah dan teras
  const tileTex = canvasTex(THREE, 256, 256, (g, w, h) => {
    g.fillStyle = '#a88a58'; g.fillRect(0, 0, w, h); g.strokeStyle = '#8a7048'; g.lineWidth = 3;
    for (let i = 0; i <= 4; i++) { g.beginPath(); g.moveTo(i * 64, 0); g.lineTo(i * 64, h); g.stroke(); g.beginPath(); g.moveTo(0, i * 64); g.lineTo(w, i * 64); g.stroke(); }
    for (let i = 0; i < 500; i++) { g.fillStyle = `rgba(${rnd() < 0.5 ? '255,235,170' : '40,30,10'},0.06)`; g.fillRect(rnd() * w, rnd() * h, 6, 6); }
  });
  tileTex.wrapS = tileTex.wrapT = THREE.RepeatWrapping; tileTex.repeat.set(6, 3);
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(500, 400), new THREE.MeshStandardMaterial({ color: dkGreen.clone().lerp(K('#c9a43a'), 0.25).multiplyScalar(0.5), roughness: 1 }));
  ground.rotation.x = -Math.PI / 2; ground.position.set(0, -0.02, -120); scene.add(ground);
  const deck = new THREE.Mesh(new THREE.BoxGeometry(26, 0.3, 11), new THREE.MeshStandardMaterial({ map: tileTex, roughness: 0.8, emissive: K('#4a3512'), emissiveMap: tileTex, emissiveIntensity: 0.22 }));
  deck.position.set(0, -0.15, 0.5); scene.add(deck);
  const edge = new THREE.Mesh(new THREE.BoxGeometry(26.4, 0.12, 0.3), new THREE.MeshStandardMaterial({ color: '#cdb97a', roughness: 0.7 }));
  edge.position.set(0, 0.04, 6.1); scene.add(edge);

  // masjid di latar
  const wall = new THREE.MeshStandardMaterial({ color: '#cbbf8a', roughness: 0.85 });
  const goldM = new THREE.MeshStandardMaterial({ color: gold, roughness: 0.3, metalness: 0.85 });
  const domeM = new THREE.MeshStandardMaterial({ color: dkGreen.clone().multiplyScalar(0.8), roughness: 0.4, metalness: 0.3 });
  const glassM = new THREE.MeshBasicMaterial({ color: K('#ffc85a').multiplyScalar(1.3) });
  owned.push(wall, goldM, domeM, glassM);
  const mq = new THREE.Group();
  const madd = (geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); mq.add(m); owned.push(geo); return m; };
  madd(new THREE.BoxGeometry(24, 9, 13), wall, 0, 4.5, 0);
  madd(new THREE.CylinderGeometry(5.2, 5.5, 3, 24), wall, 0, 10.5, 0);
  madd(new THREE.SphereGeometry(5.6, 28, 14, 0, Math.PI * 2, 0, Math.PI * 0.55), domeM, 0, 12, 0);
  madd(new THREE.CylinderGeometry(0.15, 0.15, 3, 6), goldM, 0, 18.1, 0);
  [[-9, 3], [9, 3], [-9, -3], [9, -3]].forEach(([x, z]) => {
    madd(new THREE.CylinderGeometry(1.4, 1.5, 2, 12), wall, x, 10, z);
    madd(new THREE.SphereGeometry(1.7, 14, 8, 0, Math.PI * 2, 0, Math.PI * 0.55), domeM, x, 11, z);
    madd(new THREE.ConeGeometry(0.12, 1.4, 6), goldM, x, 13.4, z);
  });
  [-17, 17].forEach((x) => {
    madd(new THREE.CylinderGeometry(1.0, 1.25, 28, 12), wall, x, 14, 0);
    for (let k = 0; k < 3; k++) madd(new THREE.TorusGeometry(1.1, 0.08, 6, 14), goldM, x, 8 + k * 6, 0).rotation.x = Math.PI / 2;
    madd(new THREE.CylinderGeometry(1.9, 1.5, 0.9, 12), wall, x, 23, 0);
    madd(new THREE.CylinderGeometry(1.0, 1.0, 2.2, 12), wall, x, 24.5, 0);
    madd(new THREE.SphereGeometry(1.15, 12, 8, 0, Math.PI * 2, 0, Math.PI * 0.6), domeM, x, 25.6, 0);
    madd(new THREE.ConeGeometry(0.12, 2.2, 6), goldM, x, 28.4, 0);
  });
  const winArch = new THREE.CircleGeometry(0.95, 12, 0, Math.PI), winBox = new THREE.PlaneGeometry(1.9, 3);
  owned.push(winArch, winBox);
  for (let i = -4; i <= 4; i++) { madd(winBox, glassM, i * 2.6, 3.4, 6.55); madd(winArch, glassM, i * 2.6, 4.9, 6.55); }
  mq.position.set(0, 0, -62); mq.scale.setScalar(1.25); scene.add(mq);

  // pohon kelapa siluet (batang dan pelepah instanced)
  const trunkG = new THREE.CylinderGeometry(0.3, 0.5, 1, 6), frondG = new THREE.ConeGeometry(0.7, 6, 4);
  frondG.rotateZ(Math.PI / 2); frondG.translate(3, 0, 0); frondG.scale(1, 0.22, 1);
  const palmM = new THREE.MeshStandardMaterial({ color: '#17381a', roughness: 1 });
  owned.push(trunkG, frondG, palmM);
  const palms = [[-24, -26, 14], [-17, -40, 17], [22, -28, 15], [27, -44, 19]];
  const trunks = new THREE.InstancedMesh(trunkG, palmM, palms.length), fronds = new THREE.InstancedMesh(frondG, palmM, palms.length * 7);
  const dm = new THREE.Object3D();
  palms.forEach(([x, z, h], i) => {
    dm.position.set(x, h / 2, z); dm.rotation.set(0, 0, (x < 0 ? 1 : -1) * 0.06); dm.scale.set(1, h, 1); dm.updateMatrix(); trunks.setMatrixAt(i, dm.matrix);
    for (let k = 0; k < 7; k++) {
      dm.position.set(x + (x < 0 ? 1 : -1) * h * 0.06, h, z); dm.rotation.set(0, k * 0.9, -0.55 - (k % 2) * 0.35); dm.scale.set(1.0, 1.0, 1.0); dm.updateMatrix(); fronds.setMatrixAt(i * 7 + k, dm.matrix);
    }
  });
  scene.add(trunks, fronds);

  // tiang dan balok teras
  const woodM = new THREE.MeshStandardMaterial({ color: '#8a5a2c', roughness: 0.8, emissive: '#2a1608', emissiveIntensity: 0.6 }); owned.push(woodM);
  const postG = new THREE.CylinderGeometry(0.14, 0.17, 9, 8), beamG = new THREE.BoxGeometry(19.5, 0.4, 0.5); owned.push(postG, beamG);
  [-9.4, 9.4].forEach((x) => { const p = new THREE.Mesh(postG, woodM); p.position.set(x, 4.5, -3.2); scene.add(p); });
  const beam = new THREE.Mesh(beamG, woodM); beam.position.set(0, 8.9, -3.2); scene.add(beam);

  // ketupat dua warna: hijau tua dan janur
  const wv = canvasTex(THREE, 128, 128, (g, w, h) => {
    g.fillStyle = '#' + dkGreen.clone().multiplyScalar(0.8).getHexString(); g.fillRect(0, 0, w, h);
    const lt = '#' + janur.clone().lerp(K('#9fb83a'), 0.25).getHexString(), dk = '#' + dkGreen.clone().multiplyScalar(0.95).getHexString();
    g.lineWidth = 11;
    for (let k = -h; k < w + h; k += 32) { g.strokeStyle = dk; g.beginPath(); g.moveTo(k, 0); g.lineTo(k + h, h); g.stroke(); }
    for (let k = -h, i = 0; k < w + h; k += 32, i++) for (let y = 0; y < h; y += 32) {
      if (((y / 32) + i) % 2) continue;
      g.strokeStyle = lt; g.beginPath(); g.moveTo(k + h - y - 20, y - 4 + 20); g.lineTo(k + h - y + 20, y + 28 - 20); g.stroke();
    }
    g.lineWidth = 2; g.strokeStyle = 'rgba(10,40,12,0.5)';
    for (let k = 0; k < w; k += 16) { g.beginPath(); g.moveTo(k, 0); g.lineTo(k + h, h); g.stroke(); }
  });
  wv.wrapS = wv.wrapT = THREE.RepeatWrapping; wv.anisotropy = 2;
  const kGeo = new THREE.BoxGeometry(0.62, 0.62, 0.42), kMat = new THREE.MeshStandardMaterial({ map: wv, roughness: 0.65 });
  const strandG = new THREE.CylinderGeometry(0.014, 0.014, 1, 4), tasG = new THREE.ConeGeometry(0.07, 0.5, 6);
  const goldB = new THREE.MeshStandardMaterial({ color: gold, emissive: gold, emissiveIntensity: 0.35, roughness: 0.35, metalness: 0.8 });
  owned.push(wv, kGeo, kMat, strandG, tasG, goldB);
  const rope = new THREE.CatmullRomCurve3([[-9.4, 8.7, -3.2], [-4.7, 7.7, -3.2], [0, 7.3, -3.2], [4.7, 7.7, -3.2], [9.4, 8.7, -3.2]].map((a) => new THREE.Vector3(...a)));
  const ropeG = new THREE.TubeGeometry(rope, 40, 0.035, 5); owned.push(ropeG);
  scene.add(new THREE.Mesh(ropeG, goldB));
  const hang = []; // ketupat bergantung: {x,y,z,L,ph,amp,yaw}
  const NG = 13;
  for (let i = 0; i < NG; i++) { const p = rope.getPoint((i + 0.5) / NG); hang.push({ x: p.x, y: p.y, z: -3.2, L: 0.7 + (i % 3) * 0.45, ph: i * 0.9, amp: 0.07 }); }
  [[-6.6, 1.6], [6.9, 1.2]].forEach(([x, z], s) => { for (let j = 0; j < 3; j++) hang.push({ x, y: 7.4 - j * 1.55, z, L: 0.35, ph: s * 2 + j * 1.1, amp: 0.05 }); });
  const kInst = new THREE.InstancedMesh(kGeo, kMat, hang.length), sInst = new THREE.InstancedMesh(strandG, goldB, hang.length), tInst = new THREE.InstancedMesh(tasG, goldB, hang.length);
  const tint = new THREE.Color();
  hang.forEach((h, i) => { kInst.setColorAt(i, tint.set(i % 2 ? '#ffffff' : '#e4ff9a')); });
  scene.add(kInst, sInst, tInst);
  // pita janur melengkung
  const ribM = new THREE.MeshStandardMaterial({ color: janur.clone().lerp(K('#9bc24a'), 0.2), side: THREE.DoubleSide, roughness: 0.6, emissive: janur, emissiveIntensity: 0.12 }); owned.push(ribM);
  const ribs = [];
  for (let i = 0; i < 6; i++) {
    const p = rope.getPoint((i + 0.5) / 6 + 0.04), g = new THREE.PlaneGeometry(0.2, 2.1, 1, 10); owned.push(g);
    const m = new THREE.Mesh(g, ribM); m.position.set(p.x, p.y, -3.1); scene.add(m);
    ribs.push({ g, base: new Float32Array(g.attributes.position.array), ph: i * 1.3 });
  }

  // pelita: lentera gantung dan lampu minyak di tepi teras
  const lanG = new THREE.CylinderGeometry(0.26, 0.2, 0.62, 6), capG = new THREE.CylinderGeometry(0.2, 0.28, 0.1, 6);
  const lanM = new THREE.MeshBasicMaterial({ color: K('#ffb347').multiplyScalar(1.5) }); owned.push(lanG, capG, lanM);
  const lans = [[-7.2, 6.5, -2.8], [-3.2, 6.0, -2.9], [3.0, 6.0, -2.9], [7.4, 6.4, -2.8], [-8.6, 4.3, 0.6], [8.8, 4.0, 0.2]];
  const lanI = new THREE.InstancedMesh(lanG, lanM, lans.length), capI = new THREE.InstancedMesh(capG, goldB, lans.length * 2);
  const lamps = []; for (let i = 0; i < 10; i++) lamps.push([-9 + i * 2, 0.14, 5.5]);
  const lampG = new THREE.SphereGeometry(0.09, 8, 6); owned.push(lampG);
  const lampI = new THREE.InstancedMesh(lampG, lanM, lamps.length);
  lamps.forEach(([x, y, z], i) => { dm.position.set(x, y, z); dm.rotation.set(0, 0, 0); dm.scale.set(1, 0.8, 1); dm.updateMatrix(); lampI.setMatrixAt(i, dm.matrix); });
  scene.add(lanI, capI, lampI);
  const glowPts = [];
  lans.forEach((l) => glowPts.push(l[0], l[1], l[2], 6.5));
  lamps.forEach((l) => glowPts.push(l[0], l[1] + 0.2, l[2], 2.2));
  [[-6, 3.2, -24], [8, 4.2, -30], [20, 3.5, -40], [-22, 3, -36]].forEach((l) => glowPts.push(l[0], l[1], l[2], 4));
  const gGeo = new THREE.BufferGeometry(); gGeo.setAttribute('position', new THREE.Float32BufferAttribute(glowPts.filter((_, i) => i % 4 < 3), 3));
  const gMat = new THREE.PointsMaterial({ map: tex, color: K('#ffb347'), size: 2.6, sizeAttenuation: true, transparent: true, opacity: 0.45, depthWrite: false, blending: THREE.AdditiveBlending, fog: false });
  owned.push(gGeo, gMat);
  const glowP = new THREE.Points(gGeo, gMat); glowP.frustumCulled = false; scene.add(glowP);

  // genangan cahaya hangat di lantai (aditif, satu draw call)
  const poolG = new THREE.PlaneGeometry(1, 1); poolG.rotateX(-Math.PI / 2);
  const poolM = (new THREE.MeshBasicMaterial({ map: tex, color: K('#ffa040'), transparent: true, opacity: 0.28, depthWrite: false, blending: THREE.AdditiveBlending, fog: false })); owned.push(poolG, poolM);
  const pools = [...lamps.map((l) => [l[0], l[2], 3.6]), [7.4, -2.4, 6], [-8.6, 0.6, 5], [8.8, 0.2, 5], [0, 1, 11], [-3, 2.5, 7], [3.5, 2, 7]];
  const poolI = new THREE.InstancedMesh(poolG, poolM, pools.length);
  pools.forEach(([x, z, sz], i) => { dm.position.set(x, 0.03, z); dm.rotation.set(0, 0, 0); dm.scale.set(sz * 1.3, 1, sz); dm.updateMatrix(); poolI.setMatrixAt(i, dm.matrix); });
  scene.add(poolI);

  // meja: ketupat, opor, kue
  const table = new THREE.Group(); table.position.set(7.4, 0, -2.4); scene.add(table);
  const clothM = new THREE.MeshStandardMaterial({ color: dkGreen.clone().multiplyScalar(1.15), roughness: 0.8 }), trimM = new THREE.MeshStandardMaterial({ color: gold, roughness: 0.5, metalness: 0.5 });
  const tG = new THREE.BoxGeometry(2.6, 0.92, 1.2), trimG = new THREE.BoxGeometry(2.64, 0.07, 1.24); owned.push(clothM, trimM, tG, trimG);
  const tb = new THREE.Mesh(tG, clothM); tb.position.y = 0.46; table.add(tb);
  const tr = new THREE.Mesh(trimG, trimM); tr.position.y = 0.93; table.add(tr);
  const bowl = new THREE.LatheGeometry([[0, 0], [0.2, 0.02], [0.36, 0.2], [0.4, 0.26], [0.36, 0.26], [0.3, 0.18], [0, 0.1]].map(([x, y]) => new THREE.Vector2(x, y)), 14);
  const bowlM = new THREE.MeshStandardMaterial({ color: '#efe4c4', roughness: 0.4, side: THREE.DoubleSide }), oporG = new THREE.CircleGeometry(0.31, 14), oporM = new THREE.MeshStandardMaterial({ color: '#d98a2b', roughness: 0.5, emissive: '#6a3a05', emissiveIntensity: 0.4 });
  owned.push(bowl, bowlM, oporG, oporM);
  const b1 = new THREE.Mesh(bowl, bowlM); b1.position.set(-0.7, 0.96, 0); table.add(b1);
  const o1 = new THREE.Mesh(oporG, oporM); o1.rotation.x = -Math.PI / 2; o1.position.set(-0.7, 1.2, 0); table.add(o1);
  const smallK = new THREE.InstancedMesh(kGeo, kMat, 5);
  [[0.05, 0.1], [0.4, -0.1], [0.75, 0.15], [0.25, 0.25], [0.6, -0.25]].forEach(([x, z], i) => { dm.position.set(x, 1.2 + (i > 2 ? 0.2 : 0), z); dm.rotation.set(0.1, i, Math.PI / 4); dm.scale.setScalar(0.55); dm.updateMatrix(); smallK.setMatrixAt(i, dm.matrix); });
  table.add(smallK);
  const swG = new THREE.SphereGeometry(0.1, 8, 6), swM = new THREE.MeshStandardMaterial({ roughness: 0.6 }); owned.push(swG, swM);
  const sweets = new THREE.InstancedMesh(swG, swM, 12);
  for (let i = 0; i < 12; i++) { dm.position.set(0.95 + (i % 4) * 0.2 - 0.3, 1.04 + (i > 7 ? 0.14 : 0), -0.25 + Math.floor(i / 4) * 0.22); dm.rotation.set(0, 0, 0); dm.scale.setScalar(1); dm.updateMatrix(); sweets.setMatrixAt(i, dm.matrix); sweets.setColorAt(i, tint.set(['#e0a84a', '#f1e3c0', '#9b5a2b', '#d4e157'][i % 4])); }
  table.add(sweets);

  // keluarga
  const sil = peopleMaterial(THREE, { rim: '#ffb455', power: 2.0, strength: 1.5, fill: 0.3 });
  const lit = peopleMaterial(THREE, { rim: '#ffd590', power: 2.6, strength: 0.8, fill: 0.48 });
  const CREAM = '#efe4c2', JAN = '#c9dd55', DKG = '#2c7d36', BATIK = '#6a3d1a', MAR = '#5b1e1e', HIJ = '#f1e8cc';
  const sk = ['#b87a4f', '#c98f62', '#d9a577', '#a86e45'];
  const spec = (mat, o) => Object.assign({ material: mat, hair: '#1b1411', lively: 1.5, look: -(o.yaw || 0) * 0.45 }, o);
  const crowd = createCrowd(THREE, [
    spec(sil, { x: -4.1, z: -0.9, yaw: 1.2, pose: 'handshake', top: CREAM, lower: 'sarung', bottom: MAR, hat: 'peci', skin: sk[0], height: 1.1, accent: '#caa24a' }),
    spec(sil, { x: -3.1, z: -0.9, yaw: -1.2, pose: 'handshake', top: DKG, lower: 'sarung', bottom: '#3d2a1a', hat: 'peci', skin: sk[1], height: 1.14, accent: '#caa24a' }),
    spec(lit, { x: -0.25, z: 0.7, yaw: 1.25, pose: 'bow', top: JAN, bottom: CREAM, hat: 'peci', skin: sk[2], child: true, height: 1.15 }),
    spec(lit, { x: 0.5, z: 0.7, yaw: -1.0, pose: 'handshake', top: '#1e5d2a', lower: 'skirt', bottom: BATIK, hat: 'hijab', hatColor: HIJ, skin: sk[3], height: 1.0, accent: '#caa24a' }),
    spec(lit, { x: 2.85, z: 0.1, yaw: 1.35, pose: 'hug', top: JAN, lower: 'skirt', bottom: BATIK, hat: 'hijab', hatColor: HIJ, skin: sk[0], height: 1.07 }),
    spec(lit, { x: 3.4, z: 0.1, yaw: -1.35, pose: 'hug', top: DKG, lower: 'skirt', bottom: BATIK, hat: 'hijab', hatColor: '#e0bf5a', skin: sk[1], height: 0.97 }),
    spec(lit, { x: -1.0, z: 2.3, yaw: 0.22, pose: 'carry', top: '#256b2f', lower: 'sarung', bottom: MAR, hat: 'peci', skin: sk[2], height: 1.12, accent: '#caa24a', tray: ['#2e7d32', '#cfe05a', '#2e7d32', '#e0a84a'] }),
    spec(lit, { x: 1.9, z: 2.3, yaw: -0.28, pose: 'carry', top: '#f1e5be', lower: 'skirt', bottom: BATIK, hat: 'hijab', hatColor: '#d9b44a', skin: sk[3], height: 1.04, tray: ['#e0a84a', '#f1e3c0', '#9b5a2b', '#d4e157'] }),
    spec(sil, { x: 5.2, z: -0.5, yaw: -0.15, pose: 'wave', mirror: true, top: DKG, bottom: '#1c1c1c', hat: 'peci', skin: sk[0], height: 1.1 }),
    spec(sil, { x: -5.3, z: 0.8, yaw: 0.2, pose: 'stand', top: JAN, lower: 'skirt', bottom: '#245f2d', hat: 'hijab', hatColor: HIJ, skin: sk[2], child: true, height: 1.12 }),
  ], sil);
  scene.add(crowd.group);

  // kilau emas
  const sp = particlePool(THREE, 900, { tex, size: 0.2, gravity: 0.4, drag: 0.5 }); scene.add(sp.points);
  const gc = gold.clone().multiplyScalar(1.5), jc = janur.clone().multiplyScalar(1.1);

  const ribRef = ribs[0].g.attributes.position;
  void ribRef;
  return {
    update(t, dt) {
      const T = t * duration / 1000, e = easeInOut(t), ramp = smooth(t / 0.3);
      for (let i = 0; i < hang.length; i++) {
        const h = hang[i], th = Math.sin(T * 1.9 + h.ph) * h.amp, s = Math.sin(th), c = Math.cos(th), L = h.L;
        dm.position.set(h.x + s * L * 0.5, h.y - c * L * 0.5, h.z); dm.rotation.set(0, 0, th); dm.scale.set(1, L, 1); dm.updateMatrix(); sInst.setMatrixAt(i, dm.matrix);
        dm.position.set(h.x + s * (L + 0.43), h.y - c * (L + 0.43), h.z); dm.rotation.set(0, Math.sin(T * 1.1 + h.ph) * 0.35, th + Math.PI / 4); dm.scale.set(1, 1, 1); dm.updateMatrix(); kInst.setMatrixAt(i, dm.matrix);
        dm.position.set(h.x + s * (L + 0.96), h.y - c * (L + 0.96), h.z); dm.rotation.set(Math.PI, 0, th); dm.updateMatrix(); tInst.setMatrixAt(i, dm.matrix);
      }
      kInst.instanceMatrix.needsUpdate = sInst.instanceMatrix.needsUpdate = tInst.instanceMatrix.needsUpdate = true;
      ribs.forEach((r) => {
        const p = r.g.attributes.position;
        for (let i = 0; i < p.count; i++) {
          const y0 = r.base[i * 3 + 1], u = (1.05 - y0) / 2.1; // 0 di atas, 1 di ujung
          p.setX(i, r.base[i * 3] + Math.sin(T * 2.6 - u * 3 + r.ph) * 0.12 * u);
          p.setY(i, y0 - u * 0.1 * Math.sin(T * 1.4 + r.ph));
          p.setZ(i, Math.sin(T * 3.1 - u * 4 + r.ph) * 0.1 * u);
        }
        p.needsUpdate = true;
      });
      for (let i = 0; i < lans.length; i++) {
        const l = lans[i], th = Math.sin(T * 1.5 + i) * 0.06;
        dm.position.set(l[0] + Math.sin(th) * 0.4, l[1] - 0.35, l[2]); dm.rotation.set(0, 0, th); dm.scale.set(1, 1, 1); dm.updateMatrix(); lanI.setMatrixAt(i, dm.matrix);
        dm.position.y = l[1] - 0.35 + 0.36; capI.setMatrixAt(i * 2, dm.matrix);
        dm.position.y = l[1] - 0.35 - 0.36; capI.setMatrixAt(i * 2 + 1, dm.matrix);
      }
      lanI.instanceMatrix.needsUpdate = capI.instanceMatrix.needsUpdate = true;
      pl.forEach((l, i) => { l.intensity = ([7, 7, 4, 5, 4][i]) * ramp * (0.92 + 0.08 * Math.sin(T * 6 + i * 2)); });
      gMat.opacity = 0.2 + 0.2 * ramp + 0.05 * Math.sin(T * 5);
      rays.forEach((r, i) => { r.material.opacity = (0.03 + 0.02 * Math.sin(T * 0.9 + i)) * ramp; });
      crowd.update(dt);
      if (dt > 0) for (let i = 0; i < 6; i++) sp.emit((rnd() - 0.5) * 22, 0.4 + rnd() * 3.5, -2 + rnd() * 8, (rnd() - 0.5) * 0.3, 0.3 + rnd() * 0.6, 0, rnd() < 0.3 ? jc : gc, 1.8 + rnd() * 1.6);
      sp.update(dt);
      camera.position.set(lerp(-1.5, 1.4, e) + Math.sin(T * 0.7) * 0.12, 1.5 + Math.sin(T * 0.9) * 0.05, lerp(9.6, 8.3, e));
      camera.lookAt(lerp(0.6, -0.2, e), 2.3, 0);
    },
    dispose() { tex.dispose(); rayTex.dispose(); tileTex.dispose(); owned.forEach((o) => o.dispose && o.dispose()); crowd.dispose(); sil.dispose(); lit.dispose(); [kInst, sInst, tInst, lanI, capI, lampI, trunks, fronds, smallK, sweets, poolI].forEach((m) => m.dispose()); rays.forEach((r) => { r.geometry.dispose(); r.material.dispose(); }); },
  };
}
