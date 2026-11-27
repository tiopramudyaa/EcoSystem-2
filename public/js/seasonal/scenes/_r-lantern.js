// Lentera fanous gantung: badan oktagonal berpola berlubang yang menyala dari dalam, tutup berkubah bawang, dasar dan ujung berumbai,
// rantai tipis. Semua lentera memakai tiga InstancedMesh (kaca, logam, rantai) dan satu Points untuk cahaya lembut.
// Tidak ada yang berjalan saat import. update(T) tanpa alokasi.
import { canvasTex } from '../common.js';

export function lanternTexture(THREE) {
  return canvasTex(THREE, 512, 256, (g, w, h) => {
    g.fillStyle = '#1b0f06'; g.fillRect(0, 0, w, h);
    for (let p = 0; p < 8; p++) {
      const x0 = p * 64, cx = x0 + 32;
      g.save(); g.beginPath(); g.moveTo(x0 + 8, 214); g.lineTo(x0 + 8, 100); g.quadraticCurveTo(x0 + 8, 66, cx, 40); g.quadraticCurveTo(x0 + 56, 66, x0 + 56, 100); g.lineTo(x0 + 56, 214); g.closePath(); g.clip();
      const gr = g.createLinearGradient(0, 40, 0, 214); gr.addColorStop(0, '#fff1b8'); gr.addColorStop(0.5, '#ffc45e'); gr.addColorStop(1, '#ff8a2a');
      g.fillStyle = gr; g.fillRect(x0, 30, 64, 190);
      // kilau tengah panel, redup ke tepi
      const rg = g.createLinearGradient(x0, 0, x0 + 64, 0); rg.addColorStop(0, 'rgba(90,30,0,0.55)'); rg.addColorStop(0.5, 'rgba(255,255,230,0.0)'); rg.addColorStop(1, 'rgba(90,30,0,0.55)');
      g.fillStyle = rg; g.fillRect(x0, 30, 64, 190);
      g.strokeStyle = 'rgba(40,18,4,0.95)'; g.lineWidth = 2.2;
      for (let k = -160; k < 160; k += 12) { g.beginPath(); g.moveTo(x0 + k, 40); g.lineTo(x0 + k + 150, 214); g.stroke(); g.beginPath(); g.moveTo(x0 + k + 150, 40); g.lineTo(x0 + k, 214); g.stroke(); }
      // medalion bintang delapan di tengah
      g.fillStyle = 'rgba(40,18,4,0.95)'; g.beginPath(); for (let k = 0; k < 16; k++) { const a = k * Math.PI / 8, r = k % 2 ? 7 : 15; g.lineTo(cx + Math.cos(a) * r, 128 + Math.sin(a) * r); } g.closePath(); g.fill();
      g.fillStyle = '#ffe9a0'; g.beginPath(); g.arc(cx, 128, 4, 0, 6.3); g.fill();
      g.restore();
      g.fillStyle = '#ffd27a';
      for (let k = 0; k < 6; k++) { g.beginPath(); g.arc(x0 + 12 + k * 8, 22, 2.4, 0, 6.3); g.fill(); g.beginPath(); g.arc(x0 + 12 + k * 8, 238, 2.4, 0, 6.3); g.fill(); }
    }
  });
}

/**
 * list: [{ x, y, z, len, s, tint, ph }] (y = titik gantung). Mengembalikan { group, update(T), mirrorHalo, dispose() }.
 * halo: Points aditif; mirrorHalo: Points kedua yang berbagi geometri (untuk pantulan).
 */
export function buildLanterns(THREE, list, { glow, gold = '#d8a84a' } = {}) {
  const own = [];
  const n = list.length;
  const tex = lanternTexture(THREE); own.push(tex);
  // badan: Lathe oktagonal
  const prof = [];
  for (let i = 0; i <= 14; i++) { const s = i / 14; prof.push(new THREE.Vector2(0.14 + 0.62 * Math.pow(Math.sin(Math.PI * (0.06 + s * 0.88)), 0.7), -1.0 + s * 2.0)); }
  const bodyG = new THREE.LatheGeometry(prof, 8); own.push(bodyG);
  // logam: tutup berkubah bawang, cincin atas, dasar, ujung, rusuk sudut
  const parts = [];
  const lat = (pts, segs = 16) => new THREE.LatheGeometry(pts.map(([x, y]) => new THREE.Vector2(x, y)), segs);
  parts.push(lat([[0.02, 1.0], [0.6, 1.02], [0.74, 1.1], [0.55, 1.2], [0.34, 1.42], [0.44, 1.62], [0.2, 1.85], [0.06, 2.1], [0.02, 2.3]]));
  parts.push(lat([[0.01, -1.4], [0.18, -1.38], [0.3, -1.28], [0.56, -1.12], [0.48, -1.0], [0.01, -1.0]]));
  parts.push(lat([[0.01, -1.7], [0.1, -1.68], [0.12, -1.5], [0.01, -1.4]], 8));
  parts.push(lat([[0.01, 2.3], [0.09, 2.34], [0.16, 2.5], [0.09, 2.62], [0.01, 2.64]], 8));
  const ribs = [];
  for (let k = 0; k < 8; k++) {
    const a = k / 8 * Math.PI * 2 + Math.PI / 8, ca = Math.cos(a), sa = Math.sin(a), tx = -sa * 0.022, tz = ca * 0.022;
    for (let i = 0; i < prof.length - 1; i++) {
      const p0 = prof[i], p1 = prof[i + 1], r0 = p0.x / Math.cos(Math.PI / 8) + 0.004, r1 = p1.x / Math.cos(Math.PI / 8) + 0.004;
      ribs.push([ca * r0 - tx, p0.y, sa * r0 - tz], [ca * r0 + tx, p0.y, sa * r0 + tz], [ca * r1 + tx, p1.y, sa * r1 + tz], [ca * r1 - tx, p1.y, sa * r1 - tz]);
    }
  }
  const rp = [], ri = [];
  for (let q = 0; q < ribs.length; q += 4) { const b = q; rp.push(...ribs[q], ...ribs[q + 1], ...ribs[q + 2], ...ribs[q + 3]); ri.push(b, b + 1, b + 2, b, b + 2, b + 3); }
  const ribG = new THREE.BufferGeometry(); ribG.setAttribute('position', new THREE.Float32BufferAttribute(rp, 3)); ribG.setIndex(ri); ribG.computeVertexNormals(); parts.push(ribG);
  // gabung (semua indexed, hanya position+normal)
  let nv = 0, ni = 0; parts.forEach((g) => { nv += g.attributes.position.count; ni += g.index.count; });
  const mp = new Float32Array(nv * 3), mn = new Float32Array(nv * 3), mi = new Uint32Array(ni);
  let ov = 0, oi = 0;
  parts.forEach((g) => { const P = g.attributes.position, N = g.attributes.normal; mp.set(P.array, ov * 3); mn.set(N.array, ov * 3); for (let i = 0; i < g.index.count; i++) mi[oi++] = g.index.getX(i) + ov; ov += P.count; g.dispose(); });
  const metalG = new THREE.BufferGeometry(); metalG.setAttribute('position', new THREE.BufferAttribute(mp, 3)); metalG.setAttribute('normal', new THREE.BufferAttribute(mn, 3)); metalG.setIndex(new THREE.BufferAttribute(mi, 1)); own.push(metalG);
  const cordG = new THREE.CylinderGeometry(0.025, 0.025, 1, 4); cordG.translate(0, -0.5, 0); own.push(cordG);

  const glassM = new THREE.MeshBasicMaterial({ map: tex, side: THREE.DoubleSide, fog: false });
  const metalM = new THREE.MeshStandardMaterial({ color: gold, roughness: 0.32, metalness: 0.9, emissive: new THREE.Color(gold), emissiveIntensity: 0.32, side: THREE.DoubleSide });
  const cordM = new THREE.MeshStandardMaterial({ color: '#6b5430', roughness: 0.5, metalness: 0.7, emissive: new THREE.Color('#3a2a10'), emissiveIntensity: 0.4 });
  own.push(glassM, metalM, cordM);
  const glass = new THREE.InstancedMesh(bodyG, glassM, n), metal = new THREE.InstancedMesh(metalG, metalM, n), cord = new THREE.InstancedMesh(cordG, cordM, n);
  [glass, metal, cord].forEach((m) => { m.frustumCulled = false; });
  const tint = new THREE.Color();
  list.forEach((l, i) => glass.setColorAt(i, tint.set(l.tint || '#ffffff')));
  // halo
  const hp = new Float32Array(n * 3), hc = new Float32Array(n * 3);
  const haloG = new THREE.BufferGeometry(); haloG.setAttribute('position', new THREE.BufferAttribute(hp, 3)); haloG.setAttribute('color', new THREE.BufferAttribute(hc, 3)); own.push(haloG);
  const mkHalo = (size, op) => new THREE.PointsMaterial({ map: glow, vertexColors: true, size, sizeAttenuation: true, transparent: true, opacity: op, depthWrite: false, blending: THREE.AdditiveBlending, fog: false });
  const haloM = mkHalo(10, 0.85), haloMM = mkHalo(10, 0.35); own.push(haloM, haloMM);
  const halo = new THREE.Points(haloG, haloM), mirrorHalo = new THREE.Points(haloG, haloMM);
  halo.frustumCulled = mirrorHalo.frustumCulled = false;
  const warm = list.map((l) => new THREE.Color(l.tint || '#ffffff').lerp(new THREE.Color('#ff9b3a'), 0.55));
  const group = new THREE.Group(); group.add(cord, metal, glass, halo);
  const d = new THREE.Object3D();
  const api = {
    group, mirrorHalo, glassMat: glassM, count: n,
    /** posisi dunia lentera i (tengah badan) setelah update; ditulis ke out */
    update(T, amp = 1) {
      for (let i = 0; i < n; i++) {
        const l = list[i], s = l.s, tz = Math.sin(T * (1.25 + (i % 3) * 0.18) + l.ph) * 0.045 * amp, tx = Math.cos(T * 0.95 + l.ph * 1.3) * 0.025 * amp;
        // rantai dari titik gantung ke bawah
        d.position.set(l.x, l.y, l.z); d.rotation.set(tx, 0, tz); d.scale.set(1, l.len, 1); d.updateMatrix(); cord.setMatrixAt(i, d.matrix);
        // pivot berayun: badan di bawah rantai
        const D = l.len + 2.7 * s;
        d.position.set(l.x + Math.sin(tz) * D, l.y - Math.cos(tz) * D, l.z - Math.sin(tx) * D);
        d.rotation.set(tx, l.ph * 0.5 + T * 0.05, tz); d.scale.setScalar(s); d.updateMatrix(); glass.setMatrixAt(i, d.matrix); metal.setMatrixAt(i, d.matrix);
        hp[i * 3] = d.position.x; hp[i * 3 + 1] = d.position.y; hp[i * 3 + 2] = d.position.z;
        const f = 0.9 + 0.1 * Math.sin(T * 5.3 + i * 2.1) + 0.04 * Math.sin(T * 13 + i);
        hc[i * 3] = warm[i].r * f; hc[i * 3 + 1] = warm[i].g * f; hc[i * 3 + 2] = warm[i].b * f;
      }
      glass.instanceMatrix.needsUpdate = metal.instanceMatrix.needsUpdate = cord.instanceMatrix.needsUpdate = true;
      haloG.attributes.position.needsUpdate = haloG.attributes.color.needsUpdate = true;
    },
    dispose() { own.forEach((o) => o.dispose()); glass.dispose(); metal.dispose(); cord.dispose(); },
  };
  return api;
}
