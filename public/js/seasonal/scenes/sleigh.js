// Natal: malam bersalju, bulan, bukit berpinus, kereta luncur ditarik 4 rusa terbang melintas.
import { pal, rng, skyDome, stars, glowTexture, glowSprite, particlePool, easeInOut, smooth, seg, lerp } from '../common.js';

const hillH = (x, k) => (Math.sin(x * 0.045 + k * 2.1) * 5 + Math.sin(x * 0.11 + k) * 2.2 + Math.sin(x * 0.021 + k * 5) * 6) * (1 + k * 0.25);

export function create(THREE, ctx) {
  const { scene, camera, palette, duration } = ctx;
  const P = pal(THREE, palette);
  const rnd = rng(1224);
  const tex = glowTexture(THREE);
  const navy = new THREE.Color('#050a1c');

  scene.fog = new THREE.FogExp2(P.from.clone().lerp(navy, 0.4).lerp(P.to, 0.25).multiplyScalar(0.75), 0.011);
  scene.add(skyDome(THREE, [
    [0.42, P.to.clone().multiplyScalar(0.62).lerp(navy, 0.1)],
    [0.55, P.from.clone().lerp(P.to, 0.3).lerp(navy, 0.35)],
    [0.8, P.from.clone().lerp(navy, 0.6)],
    [1, navy],
  ]));
  const st = stars(THREE, 420, rnd, { tex, size: 2.6, minY: 0.18, color: '#fff6e6' });
  scene.add(st.points);

  // bulan dan halo
  const moon = new THREE.Mesh(new THREE.SphereGeometry(6, 32, 24), new THREE.MeshBasicMaterial({ color: '#fff3d6', fog: false }));
  moon.position.set(16, 25, -110);
  scene.add(moon);
  const halo = glowSprite(THREE, tex, P.warm, 60, 0.55); halo.position.copy(moon.position); scene.add(halo);
  const halo2 = glowSprite(THREE, tex, P.accent, 140, 0.18); halo2.position.copy(moon.position); scene.add(halo2);

  // cahaya
  scene.add(new THREE.HemisphereLight(new THREE.Color('#9bb0e8').lerp(P.to, 0.2), P.from.clone().lerp(navy, 0.5), 0.7));
  const moonLight = new THREE.DirectionalLight('#cfdcff', 1.3);
  moonLight.position.set(30, 40, -40);
  scene.add(moonLight);
  const fill = new THREE.DirectionalLight('#ffd9b8', 1.1);
  fill.position.set(-8, 12, 40);
  scene.add(fill);
  const lantern = new THREE.PointLight('#ffb454', 60, 28, 1.6);
  scene.add(lantern);

  // bukit bersalju (3 lapis) + pinus
  const snowMat = [0, 1, 2].map((k) => new THREE.MeshStandardMaterial({ color: new THREE.Color('#e6eefc').lerp(P.to, 0.04 * k).multiplyScalar(0.9 - k * 0.1), roughness: 0.95, flatShading: false }));
  const zs = [-24, -46, -78];
  zs.forEach((z, k) => {
    const g = new THREE.PlaneGeometry(260, 60, 90, 8);
    const p = g.attributes.position;
    for (let i = 0; i < p.count; i++) {
      const x = p.getX(i), v = p.getY(i); // v: -30..30 (kedalaman)
      p.setZ(i, Math.max(-6, hillH(x, k + 1) + (v > 0 ? 0 : v * 0.18)));
    }
    g.computeVertexNormals();
    const m = new THREE.Mesh(g, snowMat[k]);
    m.rotation.x = -Math.PI / 2; m.position.set(0, -8 - k * 1.5, z);
    scene.add(m);
  });
  const lower = new THREE.ConeGeometry(1.5, 3.4, 8), upper = new THREE.ConeGeometry(1.05, 2.8, 8);
  const pineMat = new THREE.MeshStandardMaterial({ color: '#ffffff', roughness: 0.9 });
  const N = 260, pinesA = new THREE.InstancedMesh(lower, pineMat, N), pinesB = new THREE.InstancedMesh(upper, pineMat, N);
  const dummy = new THREE.Object3D(), c = new THREE.Color();
  for (let i = 0; i < N; i++) {
    const k = i % 3, x = (rnd() - 0.5) * 230, z = zs[k] + (rnd() - 0.2) * 22 - 6;
    const y = -8 - k * 1.5 + Math.max(-6, hillH(x, k + 1)) + 1.4, s = 0.8 + rnd() * 0.9;
    dummy.position.set(x, y, z); dummy.scale.setScalar(s); dummy.updateMatrix(); pinesA.setMatrixAt(i, dummy.matrix);
    dummy.position.y = y + 2.2 * s; dummy.updateMatrix(); pinesB.setMatrixAt(i, dummy.matrix);
    c.set('#0d2b21').lerp(new THREE.Color('#f2f7ff'), rnd() * 0.45 + (k === 2 ? 0.1 : 0));
    pinesA.setColorAt(i, c); pinesB.setColorAt(i, c);
  }
  scene.add(pinesA, pinesB);

  // ===== kereta luncur =====
  const red = new THREE.MeshStandardMaterial({ color: '#c8182c', roughness: 0.4, metalness: 0.1 });
  const gold = new THREE.MeshStandardMaterial({ color: P.accent.clone().multiplyScalar(0.9), roughness: 0.3, metalness: 0.85 });
  const white = new THREE.MeshStandardMaterial({ color: '#f5f5f5', roughness: 0.85 });
  const skin = new THREE.MeshStandardMaterial({ color: '#e8b79a', roughness: 0.7 });
  const brown = new THREE.MeshStandardMaterial({ color: '#6b4226', roughness: 0.85 });
  const dark = new THREE.MeshStandardMaterial({ color: '#2a1a12', roughness: 0.9 });

  const fly = new THREE.Group();
  scene.add(fly);
  const sleigh = new THREE.Group();
  fly.add(sleigh);

  // badan: profil samping melengkung, diekstrusi
  const sp = new THREE.Shape();
  sp.moveTo(-1.7, 0.1); sp.lineTo(1.2, 0.1);
  sp.bezierCurveTo(1.6, 0.2, 1.9, 0.7, 1.75, 1.25);
  sp.bezierCurveTo(1.55, 1.05, 1.35, 0.85, 1.1, 0.8);
  sp.lineTo(-1.2, 0.8);
  sp.bezierCurveTo(-1.6, 0.9, -1.9, 1.25, -1.85, 1.55);
  sp.bezierCurveTo(-2.1, 1.0, -2.0, 0.4, -1.7, 0.1);
  const body = new THREE.Mesh(new THREE.ExtrudeGeometry(sp, { depth: 1.1, bevelEnabled: true, bevelThickness: 0.07, bevelSize: 0.07, bevelSegments: 2, curveSegments: 14 }), red);
  body.position.z = -0.55;
  sleigh.add(body);
  // ban emas di tepi atas
  const trim = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.07, 1.22), gold); trim.position.set(-0.05, 0.83, 0); sleigh.add(trim);
  // seluncur (runner) melengkung
  [-0.62, 0.62].forEach((z) => {
    const cv = new THREE.CatmullRomCurve3([[-2.0, -0.55, z], [-1.4, -0.7, z], [0.8, -0.7, z], [1.7, -0.55, z], [2.05, -0.15, z], [1.95, 0.15, z]].map((a) => new THREE.Vector3(...a)));
    sleigh.add(new THREE.Mesh(new THREE.TubeGeometry(cv, 24, 0.045, 6), gold));
    [-0.9, 0.7].forEach((x) => { const s = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.62, 5), gold); s.position.set(x, -0.35, z); sleigh.add(s); });
  });
  // karung hadiah
  const sack = new THREE.Mesh(new THREE.SphereGeometry(0.62, 16, 12), new THREE.MeshStandardMaterial({ color: '#8a5a33', roughness: 1 }));
  sack.scale.set(1.2, 1.1, 1); sack.position.set(-1.15, 1.2, 0); sleigh.add(sack);
  [['#2fa15a', -0.7, 1.0], ['#c9322e', -1.5, 1.1]].forEach(([col, x, y], i) => {
    const b = new THREE.Mesh(new THREE.BoxGeometry(0.35, 0.3, 0.35), new THREE.MeshStandardMaterial({ color: col, roughness: 0.5 }));
    b.position.set(x - 0.2, y + 0.3, i ? 0.25 : -0.25); b.rotation.y = 0.5; sleigh.add(b);
  });

  // Santa
  const santa = new THREE.Group(); santa.position.set(0.35, 0.9, 0); sleigh.add(santa);
  const torso = new THREE.Mesh(new THREE.CapsuleGeometry(0.34, 0.45, 6, 14), red); torso.position.y = 0.3; santa.add(torso);
  const belt = new THREE.Mesh(new THREE.TorusGeometry(0.35, 0.05, 8, 20), dark); belt.rotation.x = Math.PI / 2; belt.position.y = 0.18; santa.add(belt);
  const buckle = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.13, 0.05), gold); buckle.position.set(0, 0.18, 0.36); santa.add(buckle);
  const coat = new THREE.Mesh(new THREE.CylinderGeometry(0.075, 0.075, 0.6, 8), white); coat.position.set(0, 0.35, 0.34); santa.add(coat);
  const head = new THREE.Mesh(new THREE.SphereGeometry(0.22, 18, 14), skin); head.position.y = 0.93; santa.add(head);
  const beard = new THREE.Mesh(new THREE.SphereGeometry(0.24, 18, 14), white); beard.scale.set(1, 0.9, 0.8); beard.position.set(0.03, 0.8, 0.1); santa.add(beard);
  const hat = new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.5, 14), red); hat.position.set(-0.08, 1.24, 0); hat.rotation.z = 0.5; santa.add(hat);
  const hatTrim = new THREE.Mesh(new THREE.TorusGeometry(0.21, 0.06, 8, 18), white); hatTrim.rotation.x = Math.PI / 2; hatTrim.position.y = 1.05; santa.add(hatTrim);
  const pom = new THREE.Mesh(new THREE.SphereGeometry(0.08, 10, 8), white); pom.position.set(-0.27, 1.42, 0); santa.add(pom);
  [-1, 1].forEach((s) => { // lengan memegang tali kekang
    const a = new THREE.Mesh(new THREE.CapsuleGeometry(0.09, 0.4, 4, 8), red); a.position.set(0.35, 0.38, s * 0.3); a.rotation.z = -1.1; santa.add(a);
  });
  // lentera menyala
  const lampMat = new THREE.MeshStandardMaterial({ color: '#ffd27a', emissive: '#ffb347', emissiveIntensity: 2.2 });
  const lamp = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.09, 0.22, 8), lampMat);
  lamp.position.set(1.65, 1.5, 0.55); sleigh.add(lamp);
  const lampHalo = glowSprite(THREE, tex, '#ffb347', 1.6, 0.9); lampHalo.position.copy(lamp.position); sleigh.add(lampHalo);
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.55, 5), gold); pole.position.set(1.65, 1.25, 0.55); sleigh.add(pole);

  // rusa
  const deerMat = brown;
  function makeLeg(len, w = 0.07) {
    const hip = new THREE.Group();
    const up = new THREE.Mesh(new THREE.CapsuleGeometry(w, len, 4, 6), deerMat); up.position.y = -len / 2; hip.add(up);
    const knee = new THREE.Group(); knee.position.y = -len; hip.add(knee);
    const lo = new THREE.Mesh(new THREE.CapsuleGeometry(w * 0.75, len, 4, 6), deerMat); lo.position.y = -len / 2; knee.add(lo);
    const hoof = new THREE.Mesh(new THREE.SphereGeometry(w * 0.9, 6, 5), dark); hoof.position.y = -len - w * 0.4; knee.add(hoof);
    return { hip, knee };
  }
  const antlerCurve = new THREE.CatmullRomCurve3([[0, 0, 0], [0.05, 0.2, 0.06], [-0.05, 0.38, 0.14], [-0.02, 0.55, 0.12]].map((a) => new THREE.Vector3(...a)));
  const antlerGeo = new THREE.TubeGeometry(antlerCurve, 10, 0.022, 5);
  const tineGeo = new THREE.TubeGeometry(new THREE.CatmullRomCurve3([[0, 0, 0], [0.12, 0.1, 0.0], [0.2, 0.22, 0.02]].map((a) => new THREE.Vector3(...a))), 6, 0.016, 4);
  const noseMat = new THREE.MeshStandardMaterial({ color: '#ff2a2a', emissive: '#ff1010', emissiveIntensity: 2.5 });
  const deer = [];
  function makeDeer(x, z, phase, nose) {
    const g = new THREE.Group(); g.position.set(x, -0.05, z);
    const bodyM = new THREE.Mesh(new THREE.CapsuleGeometry(0.28, 0.85, 6, 12), deerMat); bodyM.rotation.z = Math.PI / 2; g.add(bodyM);
    const chest = new THREE.Mesh(new THREE.SphereGeometry(0.3, 12, 10), white); chest.scale.set(0.5, 0.9, 0.8); chest.position.set(0.55, -0.02, 0); g.add(chest);
    const neck = new THREE.Mesh(new THREE.CapsuleGeometry(0.13, 0.5, 4, 8), deerMat); neck.position.set(0.72, 0.4, 0); neck.rotation.z = -0.7; g.add(neck);
    const head = new THREE.Group(); head.position.set(0.98, 0.68, 0); g.add(head);
    const skull = new THREE.Mesh(new THREE.SphereGeometry(0.14, 12, 10), deerMat); skull.scale.set(1.3, 1, 0.9); head.add(skull);
    const snout = new THREE.Mesh(new THREE.CapsuleGeometry(0.07, 0.14, 4, 8), deerMat); snout.rotation.z = Math.PI / 2 - 0.25; snout.position.set(0.2, -0.05, 0); head.add(snout);
    const tip = new THREE.Mesh(new THREE.SphereGeometry(0.05, 8, 6), nose ? noseMat : dark); tip.position.set(0.31, -0.09, 0); head.add(tip);
    [-1, 1].forEach((s) => {
      const a = new THREE.Mesh(antlerGeo, new THREE.MeshStandardMaterial({ color: '#4a3220', roughness: 0.9 }));
      a.position.set(-0.02, 0.1, s * 0.07); a.rotation.set(s * 0.35, 0, -0.35); a.scale.setScalar(1.1);
      const t1 = new THREE.Mesh(tineGeo, a.material); t1.position.set(0.0, 0.25, 0.03); t1.rotation.z = 0.4;
      a.add(t1); head.add(a);
      const ear = new THREE.Mesh(new THREE.ConeGeometry(0.05, 0.14, 5), deerMat); ear.position.set(-0.08, 0.12, s * 0.12); ear.rotation.set(s * 0.8, 0, 0.2); head.add(ear);
    });
    const legs = [];
    [[0.5, 0.17], [0.5, -0.17], [-0.5, 0.17], [-0.5, -0.17]].forEach(([lx, lz], i) => {
      const L = makeLeg(0.34, i < 2 ? 0.065 : 0.08); L.hip.position.set(lx, -0.18, lz); g.add(L.hip); legs.push(L);
    });
    const tail = new THREE.Mesh(new THREE.SphereGeometry(0.07, 8, 6), white); tail.position.set(-0.62, 0.12, 0); g.add(tail);
    fly.add(g);
    deer.push({ g, legs, phase, head, nose });
  }
  makeDeer(2.7, 0.42, 0, false); makeDeer(2.7, -0.42, 1.3, false);
  makeDeer(4.55, 0.42, 2.6, false); makeDeer(4.55, -0.42, 3.9, true);
  const noseLight = new THREE.PointLight('#ff3030', 14, 9, 1.8); fly.add(noseLight);
  noseLight.position.set(5.0, 0.55, -0.42);
  // tali kekang
  [0.42, -0.42].forEach((z) => {
    const cv = new THREE.CatmullRomCurve3([new THREE.Vector3(1.9, 0.5, z * 0.5), new THREE.Vector3(3.0, 0.45, z), new THREE.Vector3(4.2, 0.5, z)]);
    fly.add(new THREE.Mesh(new THREE.TubeGeometry(cv, 10, 0.014, 4), gold));
  });

  // jalur terbang dan partikel
  const path = new THREE.CatmullRomCurve3([[-28, 5, -6], [-12, 1.5, 0], [2, 4.5, -4], [16, 9.5, -10], [30, 14, -16]].map((a) => new THREE.Vector3(...a)));
  const trail = particlePool(THREE, 600, { tex, size: 0.55, gravity: -0.6, drag: 1.2 });
  scene.add(trail.points);
  const snow = particlePool(THREE, 1100, { tex, size: 0.32, gravity: -2.2, drag: 0.3, additive: false });
  scene.add(snow.points);
  const white2 = new THREE.Color('#ffffff');
  for (let i = 0; i < 1100; i++) snow.emit((rnd() - 0.5) * 70, (rnd() - 0.4) * 30, 10 - rnd() * 70, (rnd() - 0.5) * 0.6, -1.5 - rnd(), (rnd() - 0.5) * 0.6, white2, 1e9);
  const sparkC = P.accent.clone().lerp(white2, 0.35).multiplyScalar(1.4);

  const tmp = new THREE.Vector3(), tan = new THREE.Vector3();
  return {
    update(tt, dt) {
      const T = tt * duration / 1000;
      const k = 0.02 + 0.78 * easeInOut(tt);
      path.getPoint(k, tmp); path.getTangent(k, tan);
      fly.position.copy(tmp);
      fly.rotation.order = 'YZX';
      fly.rotation.set(0, -Math.atan2(tan.z, tan.x), Math.atan2(tan.y, Math.hypot(tan.x, tan.z)));
      fly.scale.setScalar(1.7);
      sleigh.rotation.x = Math.sin(T * 2.2) * 0.03;
      sleigh.position.y = Math.sin(T * 5) * 0.05;
      const ph = T * 9.5;
      deer.forEach((d) => {
        const a = ph + d.phase;
        d.g.position.y = -0.05 + Math.sin(a * 2) * 0.05;
        d.g.rotation.z = Math.sin(a) * 0.05;
        d.head.rotation.z = -0.15 + Math.sin(a + 1) * 0.1;
        d.legs.forEach((L, i) => {
          const front = i < 2, s = Math.sin(a + (i % 2) * 0.7 + (front ? 0 : 1.9));
          L.hip.rotation.z = (front ? -0.85 : 0.5) * s - (front ? 0.25 : -0.25);
          L.knee.rotation.z = front ? Math.max(0, s) * 1.3 : -Math.max(0, -s) * 1.1 + 0.2;
        });
      });
      fly.updateMatrixWorld(true);
      lantern.position.copy(fly.localToWorld(lamp.position.clone().add(sleigh.position)));
      noseLight.intensity = 12 + Math.sin(T * 6) * 3;
      lampHalo.material.opacity = 0.75 + Math.sin(T * 9) * 0.12;
      // jejak kilau di belakang kereta
      if (dt > 0) for (let i = 0; i < 6; i++) {
        const o = fly.localToWorld(new THREE.Vector3(-2.2 - rnd() * 0.5, 0.2 + rnd() * 0.5, (rnd() - 0.5) * 0.8));
        trail.emit(o.x, o.y, o.z, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, (rnd() - 0.5) * 0.8, sparkC, 1.1 + rnd() * 0.6);
      }
      trail.update(dt);
      // salju: ulang saat jatuh keluar
      const pos = snow.points.geometry.attributes.position;
      snow.update(dt);
      for (let i = 0; i < 1100; i++) {
        if (pos.getY(i) < -14) pos.setY(i, 18);
        pos.setX(i, pos.getX(i) + Math.sin(T * 0.8 + i) * dt * 0.5);
      }
      st.update(T);
      // kamera: dolly pelan dan geser
      camera.position.set(lerp(-3, 3, tt), 2.8 + tt * 1.2, 17 - tt * 3);
      camera.lookAt(lerp(-1, 2, tt), 5 + tt * 1.5, -6);
    },
    dispose() { tex.dispose(); lower.dispose(); upper.dispose(); antlerGeo.dispose(); tineGeo.dispose(); pinesA.dispose(); pinesB.dispose(); },
  };
}
