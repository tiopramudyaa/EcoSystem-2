// Naga Tiongkok untuk adegan lunar: tubuh loft bersisik dengan perut pelat krem, punggung berduri ramping, surai di leher, kepala kecil proporsional,
// empat kaki ramping bercakar lima, ekor berumbai, kumis dan janggut mengalir, mutiara bercahaya, awan tipis. Semua dalam sedikit draw call
// (badan, duri, kepala, rahang, tulang, mata, kaki, cakar, telapak, kumis, ekor, mutiara). Tidak ada yang berjalan saat import.
import { smooth, lerp, particlePool, glowSprite } from '../common.js';
import { tube, merger, dynTubes, ribbons } from './_b-geom.js';
import { sculptHead, JAW_PIVOT } from './_b-dragonhead.js';
import { skinTextures } from './_b-dragonskin.js';

const HG = 1.05; // skala kepala (panjang kepala sekitar 4.9 unit dunia sebelum skala adegan)

export function createDragon(THREE, o) {
  const { scene, quality, tex, cloudTex, rnd } = o;
  const low = quality === 'low';
  const NR = low ? 96 : 150, RAD = low ? 10 : 18;
  const KS = o.scale || 1.3; // skala keseluruhan naga terhadap adegan
  const root = new THREE.Group(); root.scale.setScalar(KS); scene.add(root);
  const owned = [];
  const own = (x) => { owned.push(x); return x; };
  const K = (h) => new THREE.Color(h);

  const bodyTex = skinTextures(THREE, 24), headTex = skinTextures(THREE, 14);
  owned.push(bodyTex.map, bodyTex.bump, headTex.map, headTex.bump);
  const skinM = (t, bs) => own(new THREE.MeshStandardMaterial({ map: t.map, bumpMap: t.bump, bumpScale: bs, vertexColors: true, roughness: 0.42, metalness: 0.18, emissive: K('#5a0a0a'), emissiveIntensity: 0.55 }));
  const bodyM = skinM(bodyTex, 2.6), headM = skinM(headTex, 2.2);
  const boneM = own(new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.4, metalness: 0.2, emissive: K('#3a1a05'), emissiveIntensity: 0.6 }));
  const eyeM = own(new THREE.MeshBasicMaterial({ vertexColors: true }));

  // ----- tubuh -----
  const nV = NR * (RAD + 1), tp = new Float32Array(nV * 3), tn = new Float32Array(nV * 3), tu = new Float32Array(nV * 2), tc = new Float32Array(nV * 3), ti = [];
  const cosT = new Float32Array(RAD + 1), sinT = new Float32Array(RAD + 1);
  for (let j = 0; j <= RAD; j++) { const a = j / RAD * Math.PI * 2; cosT[j] = Math.cos(a); sinT[j] = Math.sin(a); }
  const radius = (s) => (s < 0.1 ? lerp(0.58, 1.4, smooth(s / 0.1)) : 0.14 + 1.36 * Math.pow(1 - s, 0.75));
  const TOTAL = 52;
  let vAcc = 0;
  for (let i = 0; i < NR; i++) {
    const s = i / (NR - 1), r = radius(s);
    vAcc += (TOTAL / NR) / (2 * Math.PI * Math.max(r, 0.55)) * 1.0;
    const dark = 1 - 0.28 * s;
    for (let j = 0; j <= RAD; j++) {
      const k = i * (RAD + 1) + j;
      tu[k * 2] = j / RAD; tu[k * 2 + 1] = vAcc;
      tc[k * 3] = dark; tc[k * 3 + 1] = dark; tc[k * 3 + 2] = dark;
      if (i < NR - 1 && j < RAD) ti.push(k, k + RAD + 1, k + 1, k + 1, k + RAD + 1, k + RAD + 2);
    }
  }
  const bodyG = own(new THREE.BufferGeometry());
  bodyG.setAttribute('position', new THREE.BufferAttribute(tp, 3)); bodyG.setAttribute('normal', new THREE.BufferAttribute(tn, 3));
  bodyG.setAttribute('uv', new THREE.BufferAttribute(tu, 2)); bodyG.setAttribute('color', new THREE.BufferAttribute(tc, 3)); bodyG.setIndex(ti);
  const body = new THREE.Mesh(bodyG, bodyM); body.frustumCulled = false; root.add(body);
  const R = {}; ['cx', 'cy', 'cz', 'tx', 'ty', 'tz', 'nx', 'ny', 'nz', 'bx', 'by', 'bz', 'rr'].forEach((k) => { R[k] = new Float32Array(NR); });

  // ----- duri punggung dan surai (satu InstancedMesh) -----
  const bladeG = own(tube(THREE, [[0, 0, 0], [0.04, 0.45, 0], [0.2, 0.9, 0], [0.5, 1.25, 0]], [0.2, 0.15, 0.07, 0.0], { seg: 8, radial: 4, flat: 0.28, tip: false, c0: '#ffffff' }));
  const bladeM = own(new THREE.MeshStandardMaterial({ roughness: 0.38, metalness: 0.3, emissive: K('#7a3406'), emissiveIntensity: 0.7 }));
  const SPK = []; for (let i = 2; i < NR - 6; i += low ? 4 : 3) SPK.push(i);
  const NB = SPK.length + 20;
  const blades = new THREE.InstancedMesh(bladeG, bladeM, NB); blades.frustumCulled = false; root.add(blades);
  const cMane = K('#ff6a1c'), cGold = K('#ffc43a'), cTmp = new THREE.Color();
  SPK.forEach((i, q) => { const m = Math.max(0, 1 - i / 16); cTmp.copy(cGold).lerp(cMane, m); blades.setColorAt(q, cTmp); });
  for (let q = SPK.length; q < NB; q++) blades.setColorAt(q, cMane);
  blades.instanceColor.needsUpdate = true;

  // ----- kepala -----
  const hd = sculptHead(THREE, low);
  const head = new THREE.Group(); head.matrixAutoUpdate = false; root.add(head);
  const mk = (g, m) => { own(g); const o2 = new THREE.Mesh(g, m); o2.frustumCulled = false; return o2; };
  head.add(mk(hd.skin, headM), mk(hd.bone, boneM), mk(hd.eyes, eyeM));
  const jaw = new THREE.Group(); jaw.position.set(JAW_PIVOT[0], JAW_PIVOT[1], JAW_PIVOT[2]); jaw.add(mk(hd.jawSkin, headM), mk(hd.jawBone, boneM)); head.add(jaw);
  const headLight = new THREE.PointLight(K('#ffcf80'), 40, 24, 1.6); root.add(headLight);

  // ----- kumis, janggut: tabung dinamis -----
  const WC = [['#ffcf4a', '#fff0b8'], ['#ffcf4a', '#fff0b8'], ['#ffc040', '#ffe9a0'], ['#ffc040', '#ffe9a0'], ['#f8e6bc', '#ffffff'], ['#f8e6bc', '#ffffff'], ['#f8e6bc', '#ffffff']];
  const WN = 22, wh = ribbons(THREE, WC.length, WN, (u, c) => { c.set('#e8a82c').lerp(K('#ffd98a'), u); });
  const whM = own(new THREE.MeshBasicMaterial({ vertexColors: true, side: THREE.DoubleSide }));
  const whMesh = mk(own(wh.geo), whM); root.add(whMesh);
  const wpx = new Float32Array(WN), wpy = new Float32Array(WN), wpz = new Float32Array(WN);

  // ----- kaki -----
  const LN = 14, LR = low ? 6 : 8;
  const legs = dynTubes(THREE, 4, LN, LR, [['#ffffff'], ['#ffffff'], ['#ffffff'], ['#ffffff']]);
  { const uv = legs.geo.attributes.uv; for (let t = 0; t < 4 * LN; t++) for (let j = 0; j <= LR; j++) uv.setX(t * (LR + 1) + j, 0.2 + 0.12 * Math.cos(j / LR * Math.PI * 2)); } // tanpa pita perut di kaki
  const legM = own(new THREE.MeshStandardMaterial({ map: bodyTex.map, bumpMap: bodyTex.bump, bumpScale: 2.0, roughness: 0.42, metalness: 0.15, emissive: K('#5a0a0a'), emissiveIntensity: 0.55 }));
  const legMesh = mk(own(legs.geo), legM); root.add(legMesh);
  // jari burung/harimau: ruas berbuku-buku lalu cakar mengait ke bawah (sumbu y = arah jari, lengkung ke +x)
  const dm = merger(THREE);
  dm.addM(tube(THREE, [[0, 0, 0], [0, 0.3, 0], [0.03, 0.62, 0], [0.1, 0.95, 0]], [0.13, 0.16, 0.11, 0.09], { seg: 8, radial: 7, tip: false, c0: '#b02818', c1: '#d8452a' }), null, new THREE.Matrix4());
  dm.addM(tube(THREE, [[0.1, 0.93, 0], [0.2, 1.15, 0], [0.42, 1.34, 0], [0.62, 1.24, 0]], [0.095, 0.075, 0.042, 0.0], { seg: 8, radial: 7, tip: false, c0: '#fbf3da' }), null, new THREE.Matrix4());
  const clawG = own(dm.build());
  const clawM = own(new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.38, emissive: K('#4a1a08'), emissiveIntensity: 0.5 }));
  const claws = new THREE.InstancedMesh(clawG, clawM, 20); claws.frustumCulled = false; root.add(claws);
  const palmG = own(new THREE.SphereGeometry(1, 10, 8));
  const palmM = own(new THREE.MeshStandardMaterial({ color: '#b02a1c', roughness: 0.5, emissive: K('#5a0a0a'), emissiveIntensity: 0.5 }));
  const palms = new THREE.InstancedMesh(palmG, palmM, 4); palms.frustumCulled = false; root.add(palms);
  const LEGS = [{ ring: 8, side: 1, fore: true }, { ring: 8, side: -1, fore: true }, { ring: Math.round(NR * 0.45), side: 1 }, { ring: Math.round(NR * 0.45), side: -1 }];
  const L1 = 3.4, L2 = 3.1;

  // ----- ekor berumbai -----
  const NF = 7, FN = 16;
  const finR = ribbons(THREE, NF, FN, (u, c) => { if (u < 0.5) c.set('#e8321c').lerp(K('#ff8a24'), u * 2); else c.set('#ff8a24').lerp(K('#ffd25a'), (u - 0.5) * 2); });
  const finM = own(new THREE.MeshBasicMaterial({ vertexColors: true, side: THREE.DoubleSide }));
  root.add(mk(own(finR.geo), finM));

  // ----- mutiara dan bara -----
  const pearlTex = (() => { const c = document.createElement('canvas'); c.width = c.height = 64; const g = c.getContext('2d'); const gr = g.createRadialGradient(24, 22, 2, 32, 32, 32); gr.addColorStop(0, '#ffffff'); gr.addColorStop(0.5, '#fff0c8'); gr.addColorStop(1, '#f2b84a'); g.fillStyle = gr; g.fillRect(0, 0, 64, 64); g.strokeStyle = 'rgba(210,120,30,0.5)'; g.lineWidth = 3; g.beginPath(); g.arc(32, 32, 14, 0.4, 4.2); g.stroke(); g.beginPath(); g.arc(32, 32, 7, 3.4, 7.2); g.stroke(); const t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; owned.push(t); return t; })();
  const pearl = new THREE.Mesh(own(new THREE.SphereGeometry(0.44, low ? 12 : 20, low ? 8 : 14)), own(new THREE.MeshBasicMaterial({ map: pearlTex, color: K('#ffffff').multiplyScalar(1.25) }))); root.add(pearl);
  const pg1 = glowSprite(THREE, tex, K('#ffc060'), 2.4, 0.3), pg2 = glowSprite(THREE, tex, K('#ff7a2a'), 4.2, 0.12); root.add(pg1, pg2); owned.push(pg1.material, pg2.material);
  const embers = particlePool(THREE, low ? 90 : 180, { tex, size: 0.42, gravity: 1.2, drag: 1.4 }); root.add(embers.points); owned.push(embers.points.geometry, embers.points.material);
  const ec1 = K('#ff7a1a').multiplyScalar(1.3), ec2 = K('#ffd36a').multiplyScalar(1.4);

  // ----- awan tipis di sekitar tubuh -----
  const NW = low ? 8 : 14, wispA = new Float32Array(NW * 3), wispG = own(new THREE.BufferGeometry()); wispG.setAttribute('position', new THREE.BufferAttribute(wispA, 3));
  const wispM = own(new THREE.PointsMaterial({ map: cloudTex, color: K('#ff9a80'), size: 7, sizeAttenuation: true, transparent: true, opacity: 0.1, depthWrite: false, fog: false }));
  const wisps = new THREE.Points(wispG, wispM); wisps.frustumCulled = false; root.add(wisps);
  const wisp = []; for (let i = 0; i < NW; i++) wisp.push({ ring: 4 + Math.floor(i / NW * (NR - 14)), side: i % 2 ? 1 : -1, off: 2.5 + rnd() * 2.5, dy: -1.2 + rnd() * 2.4, ph: rnd() * 6.3 });

  // ----- vektor kerja -----
  const UPV = new THREE.Vector3(0, 1, 0);
  const v1 = new THREE.Vector3(), v2 = new THREE.Vector3(), v3 = new THREE.Vector3(), v4 = new THREE.Vector3(), v5 = new THREE.Vector3(), v6 = new THREE.Vector3();
  const w1 = new THREE.Vector3(), w2 = new THREE.Vector3(), w3 = new THREE.Vector3(), mx = new THREE.Matrix4(), qa = new THREE.Quaternion(), sc = new THREE.Vector3();
  const headWorld = new THREE.Vector3(), HEADP = new THREE.Vector3(), PREV = new THREE.Vector3(), VEL = new THREE.Vector3(), TG = new THREE.Vector3();
  const F = new THREE.Vector3(), U = new THREE.Vector3(), S = new THREE.Vector3();
  const SH = new THREE.Vector3(), EL = new THREE.Vector3(), PE = new THREE.Vector3(), PL = new THREE.Vector3(), CC = new THREE.Vector3();

  function spine(hx, hy, hz, T) {
    for (let i = 0; i < NR; i++) {
      const s = i / (NR - 1), env = smooth(s * 4) * (0.45 + 1.0 * s);
      R.cx[i] = hx - 42 * s + 2.4 * Math.sin(s * 7.5 - T * 2.0) * env;
      R.cy[i] = Math.max(2.5, hy - 11 * s + 3.6 * Math.sin(s * 8.5 - T * 2.4 + 1) * env + 0.8 * Math.sin(s * 3 - T * 1.1));
      R.cz[i] = hz - 10 * s + 3.4 * Math.cos(s * 6.5 - T * 1.7) * env;
      R.rr[i] = radius(s);
    }
    for (let i = 0; i < NR; i++) {
      const p = Math.max(i - 1, 0), n = Math.min(i + 1, NR - 1);
      v1.set(R.cx[p] - R.cx[n], R.cy[p] - R.cy[n], R.cz[p] - R.cz[n]).normalize();
      v2.crossVectors(v1, UPV); if (v2.lengthSq() < 1e-4) v2.set(0, 0, 1); v2.normalize();
      v3.crossVectors(v2, v1);
      R.tx[i] = v1.x; R.ty[i] = v1.y; R.tz[i] = v1.z; R.bx[i] = v2.x; R.by[i] = v2.y; R.bz[i] = v2.z; R.nx[i] = v3.x; R.ny[i] = v3.y; R.nz[i] = v3.z;
      const r = R.rr[i];
      for (let j = 0; j <= RAD; j++) {
        const k = (i * (RAD + 1) + j) * 3, c = cosT[j], sn = sinT[j];
        const dx = c * v3.x + sn * v2.x, dy = c * v3.y + sn * v2.y, dz = c * v3.z + sn * v2.z;
        tp[k] = R.cx[i] + dx * r; tp[k + 1] = R.cy[i] + dy * r; tp[k + 2] = R.cz[i] + dz * r;
        tn[k] = dx; tn[k + 1] = dy; tn[k + 2] = dz;
      }
    }
    bodyG.attributes.position.needsUpdate = true; bodyG.attributes.normal.needsUpdate = true;
  }
  const ik = (Sp, Tg, pole, out) => {
    v4.subVectors(Tg, Sp); const d = Math.min(v4.length(), L1 + L2 - 0.05); v4.normalize();
    const a = (L1 * L1 - L2 * L2 + d * d) / (2 * d), h = Math.sqrt(Math.max(0.01, L1 * L1 - a * a));
    v5.copy(pole).addScaledVector(v4, -pole.dot(v4)).normalize();
    out.copy(Sp).addScaledVector(v4, a).addScaledVector(v5, h);
  };
  const setBlade = (q, px, py, pz, ax, ay, az, bxv, byv, bzv, w, h, lean) => { // sumbu y = (a), tunduk ke arah (b) sebesar lean
    const c = Math.cos(lean), s = Math.sin(lean);
    w1.set(ax * c + bxv * s, ay * c + byv * s, az * c + bzv * s).normalize(); // y'
    w2.set(bxv * c - ax * s, byv * c - ay * s, bzv * c - az * s).normalize(); // x' (arah lengkung)
    w3.crossVectors(w2, w1);
    mx.makeBasis(w2, w1, w3); mx.scale(sc.set(w, h, w)); mx.setPosition(px, py, pz); blades.setMatrixAt(q, mx);
  };

  const api = {
    paws: [new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3()],
    ringPos(i, out) { return out.set(R.cx[i], R.cy[i], R.cz[i]).multiplyScalar(KS); },
    headPos: headWorld, pearlPos: TG, head,
    update(T, dt, hx, hy, hz, ramp, yaw) {
      hx /= KS; hy /= KS; hz /= KS;
      spine(hx, hy, hz, T);
      // arah kepala: menyusur leher, sedikit terangkat dan menoleh ke kamera
      const sp = 0.12 + 0.05 * Math.sin(T * 1.3), sy = yaw === undefined ? 0.22 : yaw;
      F.set(R.tx[0] + R.nx[0] * sp + R.bx[0] * sy, R.ty[0] * 0.4 + 0.08 + R.ny[0] * sp * 0.5 + R.by[0] * sy, R.tz[0] + R.nz[0] * sp + R.bz[0] * sy).normalize();
      U.set(R.nx[0], R.ny[0], R.nz[0]).addScaledVector(F, -(R.nx[0] * F.x + R.ny[0] * F.y + R.nz[0] * F.z)).normalize();
      S.crossVectors(F, U);
      HEADP.set(R.cx[0], R.cy[0], R.cz[0]).addScaledVector(F, 0.95 * HG);
      if (dt > 0) { v6.subVectors(HEADP, PREV).divideScalar(dt).clampLength(0, 40); VEL.lerp(v6, Math.min(1, dt * 6)); }
      PREV.copy(HEADP); headWorld.copy(HEADP).multiplyScalar(KS);
      head.matrix.makeBasis(F, U, S); head.matrix.scale(sc.set(HG, HG, HG)); head.matrix.setPosition(HEADP); head.matrixWorldNeedsUpdate = true;
      jaw.rotation.z = -(0.22 + 0.05 * Math.sin(T * 2.6)); head.updateMatrixWorld(true);
      headLight.position.copy(HEADP).addScaledVector(U, 3).addScaledVector(F, 2); headLight.intensity = 40 * ramp;

      // duri punggung dan surai
      let q = 0;
      for (let a = 0; a < SPK.length; a++, q++) {
        const i = SPK[a], r = R.rr[i], mane = Math.max(0, 1 - i / 20);
        const h = (0.55 * r + 0.25) * (1 + mane * 1.5), w = (0.28 * r + 0.1) * (1 + mane * 1.3);
        setBlade(q, R.cx[i] + R.nx[i] * r * 0.93, R.cy[i] + R.ny[i] * r * 0.93, R.cz[i] + R.nz[i] * r * 0.93, R.nx[i], R.ny[i], R.nz[i], -R.tx[i], -R.ty[i], -R.tz[i], w, h, 0.4 + mane * 0.5 + 0.18 * Math.sin(T * 3.2 - i * 0.35));
      }
      // jumbai di siku dan pergelangan kaki (disisipkan di akhir)
      const tuftStart = q;
      // kaki: dihitung dulu agar jumbai tahu posisinya
      let tn2 = 0, cl = 0;
      TG.copy(HEADP).addScaledVector(F, 4.5 * HG + Math.sin(T * 2.2) * 0.2).addScaledVector(U, -0.1 * HG + Math.sin(T * 3) * 0.12);
      for (let li = 0; li < 4; li++) {
        const lg = LEGS[li], i = lg.ring, r = R.rr[i];
        v1.set(R.tx[i], R.ty[i], R.tz[i]); v2.set(R.nx[i], R.ny[i], R.nz[i]); v3.set(R.bx[i], R.by[i], R.bz[i]);
        SH.set(R.cx[i], R.cy[i], R.cz[i]).addScaledVector(v3, lg.side * r * 0.72).addScaledVector(v2, -r * 0.5);
        if (lg.fore) { PE.copy(SH).addScaledVector(v1, 3.2 + Math.sin(T * 2.4 + li) * 0.5).addScaledVector(v2, -1.1 + Math.sin(T * 1.9 + li) * 0.5).addScaledVector(v3, lg.side * 1.7); PL.copy(v3).multiplyScalar(lg.side).addScaledVector(v2, 0.3).addScaledVector(v1, -0.4); } else { PE.copy(SH).addScaledVector(v1, -2.6 + Math.sin(T * 2.2 + li) * 0.7).addScaledVector(v2, -3.0 + Math.sin(T * 1.7 + li * 2) * 0.6).addScaledVector(v3, lg.side * 1.5); PL.copy(v3).multiplyScalar(lg.side).addScaledVector(v1, 0.7).addScaledVector(v2, 0.2); }
        ik(SH, PE, PL, EL);
        // lengkung kuadrat melalui siku
        CC.copy(EL).multiplyScalar(2).addScaledVector(SH, -0.5).addScaledVector(PE, -0.5);
        for (let j = 0; j < LN; j++) {
          const u = j / (LN - 1), a0 = (1 - u) * (1 - u), a1 = 2 * u * (1 - u), a2 = u * u;
          const rad = u < 0.3 ? lerp(0.36, 0.25, u / 0.3) : u < 0.55 ? lerp(0.25, 0.27, (u - 0.3) / 0.25) : u < 0.75 ? lerp(0.27, 0.32, (u - 0.55) / 0.2) : lerp(0.32, 0.2, (u - 0.75) / 0.25);
          legs.set(li, j, SH.x * a0 + CC.x * a1 + PE.x * a2, SH.y * a0 + CC.y * a1 + PE.y * a2, SH.z * a0 + CC.z * a1 + PE.z * a2, j === LN - 1 ? rad * 0.9 : rad);
        }
        api.paws[li].copy(PE).multiplyScalar(KS);
        v4.subVectors(PE, EL).normalize();
        v5.subVectors(EL, SH).normalize();
        // telapak berbantalan: elips pipih di pergelangan
        v6.set(0, -1, 0).addScaledVector(v4, v4.y); v6.normalize();
        mx.makeBasis(w1.copy(v3).multiplyScalar(0.42), w2.copy(v6).multiplyScalar(0.24), w3.copy(v4).multiplyScalar(0.34)); mx.setPosition(PE.x + v4.x * 0.1, PE.y + v4.y * 0.1, PE.z + v4.z * 0.1); palms.setMatrixAt(li, mx);
        // jumbai api di siku (2), lengan bawah (1), pergelangan (2)
        setBlade(tuftStart + tn2++, EL.x, EL.y, EL.z, PL.x, PL.y, PL.z, -v5.x, -v5.y, -v5.z, 0.3, 1.5, 0.6 + 0.15 * Math.sin(T * 3 + li));
        setBlade(tuftStart + tn2++, EL.x, EL.y, EL.z, PL.x, PL.y, PL.z, v5.x, v5.y, v5.z, 0.24, 1.1, 0.8);
        setBlade(tuftStart + tn2++, (EL.x + PE.x) / 2, (EL.y + PE.y) / 2, (EL.z + PE.z) / 2, PL.x, PL.y, PL.z, -v4.x, -v4.y, -v4.z, 0.2, 0.9, 0.7);
        setBlade(tuftStart + tn2++, PE.x, PE.y, PE.z, PL.x, PL.y, PL.z, -v4.x, -v4.y, -v4.z, 0.36, 1.3, 0.5 + 0.1 * Math.sin(T * 3.4 + li));
        setBlade(tuftStart + tn2++, PE.x, PE.y, PE.z, v6.x, v6.y, v6.z, -v4.x, -v4.y, -v4.z, 0.18, 0.8, 0.9);
        // lima jari menyebar seperti kaki elang, dengan cakar kait
        for (let k = 0; k < 5; k++) {
          const ph = (k - 2) * 0.62, len = [0.6, 0.82, 0.95, 0.82, 0.6][k];
          v1.copy(v4).multiplyScalar(Math.cos(ph)).addScaledVector(v3, Math.sin(ph)).addScaledVector(v6, 0.38 + 0.12 * Math.abs(ph)); v1.normalize();
          w1.set(0, -1, 0); w1.addScaledVector(v1, -w1.dot(v1)); if (w1.lengthSq() < 1e-4) w1.set(1, 0, 0); w1.normalize();
          w3.crossVectors(w1, v1);
          const qx = PE.x + v1.x * 0.12, qy = PE.y + v1.y * 0.12, qz = PE.z + v1.z * 0.12; mx.makeBasis(w1.multiplyScalar(0.7), v1.multiplyScalar(0.7 * len), w3.multiplyScalar(0.7)); mx.setPosition(qx, qy, qz); claws.setMatrixAt(cl++, mx);
        }
      }
      legs.commit();
      blades.instanceMatrix.needsUpdate = true; claws.instanceMatrix.needsUpdate = true; palms.instanceMatrix.needsUpdate = true;

      // kumis (dari sudut bibir atas) dan janggut: pita pipih yang mengalir ke belakang
      for (let w = 0; w < WC.length; w++) {
        const beard = w >= 4, long = w < 2;
        const rx = beard ? 1.9 + (w - 4) * 0.3 : (long ? 0.5 : 1.4), ry = beard ? -0.9 : (long ? -0.25 : -0.3), rz = beard ? (w - 5) * 0.16 : (w % 2 ? 1 : -1) * 0.74;
        const sg = rz >= 0 ? 1 : -1, L = beard ? 3.0 + (w - 4) * 0.6 : (long ? 7.5 : 4.6), ph = w * 1.7;
        for (let j = 0; j < WN; j++) {
          const u = j / (WN - 1), wv = Math.sin(T * 3.2 - u * 5.5 + ph) * 0.7 * u, wv2 = Math.sin(T * 2.3 - u * 4 + ph * 1.7) * 0.6 * u;
          let lx, ly, lz;
          if (beard) { lx = rx - u * L * (0.6 + u * 0.4); ly = ry - u * 1.4 - u * u * 1.2 + wv * 0.4; lz = rz + wv2 * 0.5 + sg * u * 0.3; } else {
            lx = rx + (long ? 0.9 : 0.5) * Math.sin(Math.PI * Math.min(1, u * 1.3)) - L * Math.pow(u, 1.5);
            ly = ry + 0.45 * Math.sin(Math.PI * u) - 0.6 * u + wv * 1.1;
            lz = rz + sg * (0.9 * Math.sin(Math.PI * Math.min(1, u * 0.9)) + 0.5 * u) + wv2 * 1.1;
          }
          v4.copy(HEADP).addScaledVector(F, lx * HG).addScaledVector(U, ly * HG).addScaledVector(S, lz * HG).addScaledVector(VEL, -0.04 * u * u * L);
          wpx[j] = v4.x; wpy[j] = v4.y; wpz[j] = v4.z;
        }
        for (let j = 0; j < WN; j++) { // lebar pita tegak lurus tangen dan arah pandang (sisi kepala)
          const p = j ? j - 1 : 0, n = j < WN - 1 ? j + 1 : j;
          const tx = wpx[n] - wpx[p], ty = wpy[n] - wpy[p], tz = wpz[n] - wpz[p];
          let ox = ty * S.z - tz * S.y, oy = tz * S.x - tx * S.z, oz = tx * S.y - ty * S.x;
          const m = Math.hypot(ox, oy, oz) || 1, u = j / (WN - 1), hw = ((beard ? 0.035 : 0.045) * HG * Math.pow(1 - u, 0.8) + 0.004) / m;
          wh.set(w, j, wpx[j], wpy[j], wpz[j], ox * hw, oy * hw, oz * hw);
        }
      }
      wh.commit();

      // ekor berumbai
      const tl = NR - 1;
      for (let f = 0; f < NF; f++) {
        const sp = (f - (NF - 1) / 2), L = 10.5 - Math.abs(sp) * 1.4;
        for (let j = 0; j < FN; j++) {
          const u = j / (FN - 1), wd = Math.pow(Math.sin(Math.PI * Math.min(1, u * 1.04)), 0.7) * (1.5 - Math.abs(sp) * 0.15);
          const wv = Math.sin(T * 3.1 - u * 5 + f * 0.8) * 1.4 * u * u, wv2 = Math.sin(T * 2.4 - u * 4 + f * 1.3) * 1.2 * u * u;
          v4.set(R.cx[tl], R.cy[tl], R.cz[tl]).addScaledVector(v1.set(-R.tx[tl], -R.ty[tl], -R.tz[tl]), u * L).addScaledVector(v2.set(R.bx[tl], R.by[tl], R.bz[tl]), sp * 0.85 * u + wv).addScaledVector(v3.set(R.nx[tl], R.ny[tl], R.nz[tl]), wv2 + u * u * 1.2 - 0.2 * Math.abs(sp) * u);
          finR.set(f, j, v4.x, v4.y, v4.z, v3.x * wd * 0.5, v3.y * wd * 0.5, v3.z * wd * 0.5);
        }
      }
      finR.commit();

      // mutiara melayang di depan moncong dan bara halus
      pearl.position.copy(TG); pg1.position.copy(TG); pg2.position.copy(TG); pg1.material.opacity = (0.3 + 0.05 * Math.sin(T * 7)) * ramp; pearl.rotation.y = T * 0.8;
      if (dt > 0) {
        if (rnd() < 0.7) { const a = rnd() * 6.28; embers.emit(TG.x + Math.cos(a) * 0.5, TG.y + 0.2, TG.z + Math.sin(a) * 0.5, Math.cos(a) * 0.4 - VEL.x * 0.1, 0.8 + rnd() * 1.2, Math.sin(a) * 0.4, rnd() < 0.5 ? ec1 : ec2, 0.9 + rnd() * 0.5); }
        if (rnd() < 0.5) embers.emit(HEADP.x + F.x * 3.8 * HG, HEADP.y + F.y * 3.8 * HG + 0.1, HEADP.z + F.z * 3.8 * HG, -VEL.x * 0.15 + (rnd() - 0.5), 0.6 + rnd(), -VEL.z * 0.15 + (rnd() - 0.5), ec1, 0.8);
      }
      embers.update(dt);

      // awan tipis
      for (let a = 0; a < NW; a++) {
        const w2 = wisp[a], i = w2.ring;
        wispA[a * 3] = R.cx[i] + R.bx[i] * w2.side * w2.off + Math.sin(T * 0.6 + w2.ph) * 1.2; wispA[a * 3 + 1] = R.cy[i] + w2.dy - 1.2; wispA[a * 3 + 2] = R.cz[i] + R.bz[i] * w2.side * w2.off - 1.5;
      }
      wispG.attributes.position.needsUpdate = true;
    },
    dispose() {
      owned.forEach((x) => x && x.dispose && x.dispose());
      [blades, claws, palms].forEach((m) => m.dispose());
    },
  };
  return api;
}
