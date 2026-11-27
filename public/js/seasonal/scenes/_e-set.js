// Panggung Lebaran: serambi rumah kayu berlantai batu dengan atap genteng, tiang dan balok, jendela yang menyala,
// meja hidangan (opor, ketupat, kue) dengan pelita, hamparan paving, dan untaian ketupat anyaman bergantung.
// Geometri digabung per material (draw call sedikit). Tidak ada yang berjalan saat import.
import { canvasTex, mergeParts } from '../common.js';
import { stoneTexture, windowGlassTexture } from './_r-tex.js';

const rep = (THREE, t) => { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.anisotropy = 1; return t; };

export function woodTexture(THREE, rnd) {
  return rep(THREE, canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#9a8658'; g.fillRect(0, 0, w, h);
    const n = 8, pw = w / n;
    for (let i = 0; i < n; i++) {
      const v = 0.82 + rnd() * 0.3;
      g.fillStyle = `rgb(${Math.round(158 * v)},${Math.round(130 * v)},${Math.round(80 * v)})`; g.fillRect(i * pw, 0, pw, h);
      for (let k = 0; k < 40; k++) { g.strokeStyle = `rgba(${rnd() < 0.5 ? '70,40,18' : '230,190,140'},${0.05 + rnd() * 0.09})`; g.lineWidth = 1 + rnd() * 1.5; const x = i * pw + rnd() * pw; g.beginPath(); g.moveTo(x, 0); g.bezierCurveTo(x + (rnd() - 0.5) * 8, h * 0.3, x + (rnd() - 0.5) * 8, h * 0.6, x + (rnd() - 0.5) * 6, h); g.stroke(); }
      g.fillStyle = 'rgba(40,22,10,0.7)'; g.fillRect(i * pw, 0, 2.5, h);
    }
  }));
}
export function tileTexture(THREE, rnd) {
  return rep(THREE, canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#6a5a30'; g.fillRect(0, 0, w, h);
    const rows = 10, th = h / rows, cw = 42;
    for (let r = 0; r < rows; r++) for (let x = -1; x < w / cw + 1; x++) {
      const px = x * cw + (r % 2) * cw / 2, v = 0.82 + rnd() * 0.36;
      const gr = g.createLinearGradient(0, r * th, 0, r * th + th * 1.4); gr.addColorStop(0, `rgb(${Math.round(150 * v)},${Math.round(122 * v)},${Math.round(66 * v)})`); gr.addColorStop(1, `rgb(${Math.round(88 * v)},${Math.round(72 * v)},${Math.round(38 * v)})`);
      g.fillStyle = gr; g.fillRect(px + 1.5, r * th, cw - 3, th * 1.3);
      g.fillStyle = 'rgba(0,0,0,0.28)'; g.fillRect(px + cw / 2 - 1, r * th, 2, th * 1.3);
    }
    g.fillStyle = 'rgba(0,0,0,0.25)'; for (let r = 0; r < rows; r++) g.fillRect(0, r * th + th * 0.96, w, 4);
  }));
}
export function pavingTexture(THREE, rnd) {
  return rep(THREE, canvasTex(THREE, 512, 512, (g, w, h) => {
    g.fillStyle = '#7a6a54'; g.fillRect(0, 0, w, h);
    const n = 4, c = w / n;
    for (let j = 0; j < n; j++) for (let i = 0; i < n; i++) {
      const v = 0.86 + rnd() * 0.24;
      g.fillStyle = `rgb(${Math.round(190 * v)},${Math.round(160 * v)},${Math.round(120 * v)})`; g.fillRect(i * c + 3, j * c + 3, c - 6, c - 6);
      const gr = g.createLinearGradient(i * c, j * c, i * c + c, j * c + c); gr.addColorStop(0, 'rgba(255,240,200,0.12)'); gr.addColorStop(1, 'rgba(40,30,10,0.14)'); g.fillStyle = gr; g.fillRect(i * c + 3, j * c + 3, c - 6, c - 6);
    }
    for (let i = 0; i < 2500; i++) { g.fillStyle = rnd() < 0.5 ? 'rgba(255,245,210,0.07)' : 'rgba(30,20,8,0.09)'; g.fillRect(rnd() * w, rnd() * h, 1 + rnd() * 3, 1 + rnd() * 3); }
  }));
}
/** Anyaman ketupat: pita diagonal berselang-seling dua warna hijau muda dan tua. */
export function weaveTexture(THREE) {
  return rep(THREE, canvasTex(THREE, 256, 256, (g, w, h) => {
    g.fillStyle = '#7ea23a'; g.fillRect(0, 0, w, h);
    const s = 32;
    for (let k = -h; k < w + h; k += s) {
      g.fillStyle = (Math.round(k / s) % 2) ? '#c9e06a' : '#a9c64c';
      g.beginPath(); g.moveTo(k, 0); g.lineTo(k + s, 0); g.lineTo(k + s + h, h); g.lineTo(k + h, h); g.closePath(); g.fill();
    }
    for (let k = -h; k < w + h; k += s) {
      g.fillStyle = (Math.round(k / s) % 2) ? 'rgba(60,90,20,0.5)' : 'rgba(255,255,200,0.18)';
      for (let y = 0; y < h; y += s * 2) { g.beginPath(); g.moveTo(k + h - y, y); g.lineTo(k + h - y + s, y); g.lineTo(k + h - y - s + s, y + s); g.lineTo(k + h - y - s, y + s); g.closePath(); g.fill(); }
    }
    g.strokeStyle = 'rgba(30,60,10,0.55)'; g.lineWidth = 1.6;
    for (let k = -h; k < w + h; k += s) { g.beginPath(); g.moveTo(k, 0); g.lineTo(k + h, h); g.stroke(); g.beginPath(); g.moveTo(k + h, 0); g.lineTo(k, h); g.stroke(); }
  }));
}

/** Rumah serambi. Mengembalikan { meshes: [...], glass: material kaca, dispose() }. */
export function buildHouse(THREE, rnd, { low = false } = {}) {
  const own = [];
  const wood = [], roof = [], stone = [], glass = [], misc = [];
  const Q = new THREE.Quaternion(), E = new THREE.Euler(), V = new THREE.Vector3(), S = new THREE.Vector3(1, 1, 1);
  const M4 = (x, y, z, rx = 0, ry = 0, rz = 0) => new THREE.Matrix4().compose(V.set(x, y, z), Q.setFromEuler(E.set(rx, ry, rz)), S);
  const T = 2.4;
  const uvBox = (geo, tile = T) => {
    const p = geo.attributes.position, n = geo.attributes.normal, u = geo.attributes.uv;
    for (let i = 0; i < p.count; i++) {
      const ax = Math.abs(n.getX(i)), ay = Math.abs(n.getY(i)), az = Math.abs(n.getZ(i));
      if (az >= ax && az >= ay) u.setXY(i, p.getX(i) / tile, p.getY(i) / tile);
      else if (ax >= ay) u.setXY(i, p.getZ(i) / tile, p.getY(i) / tile);
      else u.setXY(i, p.getX(i) / tile, p.getZ(i) / tile);
    }
    return geo;
  };
  const add = (arr, geo, m, color, uvScale) => { arr.push({ geo, m, color, uvScale }); };
  const box = (arr, w, h, d, x, y, z, color, rx = 0, ry = 0, rz = 0, tile = T) => add(arr, uvBox(new THREE.BoxGeometry(w, h, d), tile), M4(x, y, z, rx, ry, rz), color);
  const col = (r, h, x, y, z, color, arr = wood) => { const g = new THREE.CylinderGeometry(r, r * 1.08, h, low ? 8 : 12, 1); const u = g.attributes.uv; for (let i = 0; i < u.count; i++) u.setXY(i, u.getX(i) * 0.6, u.getY(i) * h / T); add(arr, g, M4(x, y, z), color); };

  const X0 = -12, X1 = 0.8, WZ = -5.6, FZ = -2.0, WH = 3.5;
  // lantai serambi dan fondasi
  box(stone, X1 - X0, 0.34, FZ - WZ + 0.9, (X0 + X1) / 2, 0.17, (WZ + FZ + 0.9) / 2, '#d8c7a6');
  box(stone, 3.6, 0.17, 0.4, -3.3, 0.085, FZ + 1.1, '#cdbd9c');
  // dinding belakang papan, dengan pintu dan jendela menyala
  box(wood, X1 - X0, WH, 0.3, (X0 + X1) / 2, WH / 2 + 0.34, WZ, '#c4b68a');
  box(wood, X1 - X0, 0.32, 0.4, (X0 + X1) / 2, 0.5, WZ + 0.1, '#5e502c');
  const gl = (w, h, x, y, z, c = '#ffffff') => add(glass, new THREE.PlaneGeometry(w, h), M4(x, y, z), c);
  const door = (x) => { box(wood, 1.9, 2.9, 0.14, x, 1.79, WZ + 0.2, '#5a3a1e'); gl(1.5, 2.55, x, 1.75, WZ + 0.29, '#ffd890'); box(wood, 0.08, 2.55, 0.05, x, 1.75, WZ + 0.31, '#4a2e16'); box(wood, 1.5, 0.08, 0.05, x, 1.95, WZ + 0.31, '#4a2e16'); };
  const win = (x, y) => { box(wood, 1.75, 1.7, 0.14, x, y, WZ + 0.2, '#5a3a1e'); gl(1.4, 1.35, x, y, WZ + 0.29, '#ffcf80'); box(wood, 0.07, 1.35, 0.05, x, y, WZ + 0.31, '#4a2e16'); box(wood, 1.4, 0.07, 0.05, x, y, WZ + 0.31, '#4a2e16'); };
  door(-3.3); door(-9.6); win(-6.6, 2.0); win(-0.9, 2.0);
  // tiang, balok, plafon
  for (let x = -11.5; x <= 0.5; x += 3.95) col(0.14, WH - 0.1, x, WH / 2 + 0.34, FZ, '#8a7646');
  col(0.14, WH - 0.1, 5.5, WH / 2 + 0.34, FZ, '#8a7646');
  box(wood, 4.8, 0.3, 0.32, 3.1, WH + 0.2, FZ, '#6a5c2c'); box(wood, 4.8, 0.18, 0.2, 3.1, WH - 0.35, FZ, '#7c6c3a');
  for (let i = 0; i < 9; i++) box(wood, 0.07, 0.08, 2.6, 0.9 + i * 0.55, WH + 0.3, FZ - 1.3, '#6a5c2c');
  box(wood, X1 - X0, 0.32, 0.34, (X0 + X1) / 2, WH + 0.26, FZ, '#6a5c2c');
  box(wood, X1 - X0, 0.18, 0.2, (X0 + X1) / 2, WH - 0.35, FZ, '#7c6c3a');
  box(wood, X1 - X0, 0.08, FZ - WZ + 1.2, (X0 + X1) / 2, WH + 0.2, (WZ + FZ) / 2 - 0.4, '#7a6a3a');
  // atap pelana ke arah belakang
  const rl = 6.2, ra = Math.atan2(2.1, 5.1);
  box(roof, X1 - X0 + 2, 0.14, rl, (X0 + X1) / 2, WH + 1.15, (FZ + WZ) / 2 - 0.45, '#ffffff', ra, 0, 0, 1.6);
  box(wood, X1 - X0 + 2, 0.16, 0.2, (X0 + X1) / 2, WH + 0.5, FZ + 0.5, '#5e502c');
  // pagar rendah sisi kanan dan lampu kisi
  // ---- meja hidangan ----
  const tx = 2.75, tz = -1.55;
  box(wood, 2.3, 0.07, 0.95, tx, 0.88, tz, '#5e502c');
  [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([a, b]) => box(wood, 0.07, 0.86, 0.07, tx + a * 1.05, 0.43, tz + b * 0.4, '#5a3a1e'));
  // taplak hijau dengan renda emas
  box(misc, 2.45, 0.025, 1.05, tx, 0.93, tz, '#2f7d3a');
  box(misc, 2.45, 0.03, 0.06, tx, 0.936, tz + 0.5, '#e0b84a'); box(misc, 2.45, 0.03, 0.06, tx, 0.936, tz - 0.5, '#e0b84a');
  box(misc, 0.5, 0.012, 0.9, tx, 0.94, tz, '#d9c36a');
  const lat = (pts, segs = 16) => new THREE.LatheGeometry(pts.map(([x, y]) => new THREE.Vector2(x, y)), segs);
  const bowl = lat([[0, 0], [0.07, 0.01], [0.14, 0.06], [0.17, 0.14], [0.165, 0.145], [0.13, 0.08], [0, 0.03]]);
  const plate = lat([[0, 0], [0.2, 0.0], [0.24, 0.02], [0.235, 0.025], [0.19, 0.012], [0, 0.012]]);
  add(misc, bowl.clone(), M4(tx - 0.8, 0.945, tz + 0.1, 0, 0, 0), '#efe6cf');
  add(misc, new THREE.CylinderGeometry(0.148, 0.148, 0.012, 14), M4(tx - 0.8, 1.065, tz + 0.1), '#e59a2d');
  for (let i = 0; i < 4; i++) add(misc, new THREE.SphereGeometry(0.036, 6, 4), M4(tx - 0.86 + (i % 2) * 0.1, 1.08, tz + 0.06 + (i > 1 ? 0.1 : 0)), '#b85a1a');
  add(misc, plate.clone(), M4(tx - 0.2, 0.945, tz - 0.08), '#f0e8d4');
  add(misc, bowl.clone(), M4(tx + 0.55, 0.945, tz + 0.1), '#e8e0c8');
  add(misc, new THREE.CylinderGeometry(0.15, 0.15, 0.01, 14), M4(tx + 0.55, 1.062, tz + 0.1), '#9a3a1c');
  add(misc, plate.clone(), M4(tx + 0.15, 0.945, tz + 0.22), '#f0e8d4');
  // toples kue
  [[-0.35, 0.28, '#e9c46a'], [0.95, -0.2, '#d4a24a'], [0.8, 0.22, '#c9e06a']].forEach(([a, b, c]) => { add(misc, new THREE.CylinderGeometry(0.1, 0.1, 0.2, 12), M4(tx + a, 1.05, tz + b), '#dfeee6'); add(misc, new THREE.CylinderGeometry(0.103, 0.103, 0.025, 12), M4(tx + a, 1.168, tz + b), c); });
  // pelita (lampu minyak): alas, kaca, penutup
  const lx = tx + 1.02, lz = tz + 0.05, ly = 0.94;
  add(misc, new THREE.CylinderGeometry(0.09, 0.11, 0.06, 10), M4(lx, ly + 0.03, lz), '#c9a24a');
  add(glass, new THREE.CylinderGeometry(0.07, 0.085, 0.34, 10, 1, true), M4(lx, ly + 0.23, lz), '#ffd070');
  add(misc, new THREE.CylinderGeometry(0.05, 0.075, 0.05, 10), M4(lx, ly + 0.42, lz), '#c9a24a');
  const mk = (parts, uv) => { const g = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose()); own.push(g); return g; };
  const wT = woodTexture(THREE, rnd), rT = tileTexture(THREE, rnd), sT = stoneTexture(THREE, rnd), gT = windowGlassTexture(THREE);
  own.push(wT, rT, sT, gT);
  const wM = new THREE.MeshStandardMaterial({ map: wT, vertexColors: true, roughness: 0.85 });
  const rM = new THREE.MeshStandardMaterial({ map: rT, vertexColors: true, roughness: 0.8 });
  const sM = new THREE.MeshStandardMaterial({ map: sT, vertexColors: true, roughness: 0.9 });
  const gM = new THREE.MeshBasicMaterial({ map: gT, vertexColors: true, side: THREE.DoubleSide });
  const mM = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.55 });
  own.push(wM, rM, sM, gM, mM);
  const meshes = [new THREE.Mesh(mk(wood), wM), new THREE.Mesh(mk(roof), rM), new THREE.Mesh(mk(stone), sM), new THREE.Mesh(mk(glass), gM), new THREE.Mesh(mk(misc), mM)];
  meshes.forEach((m) => { m.frustumCulled = false; });
  return { meshes, glass: gM, lamp: [lx, ly + 0.25, lz], table: [tx, tz], dispose() { own.forEach((o) => o.dispose()); } };
}

/** Untaian ketupat bergantung dari balok: InstancedMesh ketupat (kantung belah ketupat beranyam), tali, dan umbai janur. */
export function buildKetupat(THREE, strings, { low = false } = {}) {
  const own = [];
  const weave = weaveTexture(THREE); own.push(weave);
  // kantung: kubus yang dibulatkan, diputar 45 derajat sehingga tampil sebagai belah ketupat
  const g = new THREE.BoxGeometry(0.34, 0.34, 0.26, 4, 4, 3);
  const p = g.attributes.position, u = g.attributes.uv, nrm = new THREE.Vector3(), v3 = new THREE.Vector3();
  for (let i = 0; i < p.count; i++) {
    v3.fromBufferAttribute(p, i); const r = Math.max(Math.abs(v3.x) / 0.17, Math.abs(v3.y) / 0.17, Math.abs(v3.z) / 0.13);
    nrm.copy(v3).normalize(); const k = 0.17 * 1.28 * r; // puncak tetap, sisi menggembung
    v3.lerp(nrm.multiplyScalar(k), 0.55); v3.z *= 0.95; p.setXYZ(i, v3.x, v3.y, v3.z); u.setXY(i, v3.x * 2.6 + 0.5, v3.y * 2.6 + 0.5);
  }
  g.computeVertexNormals();
  own.push(g);
  const mat = new THREE.MeshStandardMaterial({ map: weave, roughness: 0.7, emissive: new THREE.Color('#1b3a08'), emissiveIntensity: 0.25 }); own.push(mat);
  const n = strings.length;
  const inst = new THREE.InstancedMesh(g, mat, n);
  const tint = new THREE.Color();
  strings.forEach((s, i) => inst.setColorAt(i, tint.set(s.tint || '#ffffff')));
  const sg = new THREE.CylinderGeometry(0.012, 0.012, 1, 4); sg.translate(0, -0.5, 0); own.push(sg);
  const sm = new THREE.MeshStandardMaterial({ color: '#d8b24a', roughness: 0.5, metalness: 0.4, emissive: '#5a4210', emissiveIntensity: 0.4 }); own.push(sm);
  const strand = new THREE.InstancedMesh(sg, sm, n);
  // umbai janur: tiga helai kecil menggantung
  const tg = new THREE.ConeGeometry(0.03, 0.26, 4); tg.translate(0, -0.13, 0); own.push(tg);
  const tm = new THREE.MeshStandardMaterial({ color: '#c8dc58', roughness: 0.6, emissive: '#3a5010', emissiveIntensity: 0.35 }); own.push(tm);
  const tassel = new THREE.InstancedMesh(tg, tm, n * 3);
  [inst, strand, tassel].forEach((m) => { m.frustumCulled = false; });
  const d = new THREE.Object3D();
  return {
    meshes: [strand, inst, tassel],
    update(T) {
      for (let i = 0; i < n; i++) {
        const s = strings[i], th = Math.sin(T * 1.6 + s.ph) * 0.06 + Math.sin(T * 0.9 + s.ph * 2) * 0.025, sn = Math.sin(th), cs = Math.cos(th), L = s.L;
        d.position.set(s.x, s.y, s.z); d.rotation.set(0, 0, th); d.scale.set(1, L, 1); d.updateMatrix(); strand.setMatrixAt(i, d.matrix);
        const cy = L + 0.23 * s.s;
        d.position.set(s.x + sn * cy, s.y - cs * cy, s.z); d.rotation.set(Math.sin(T * 0.8 + s.ph) * 0.12, Math.sin(T * 0.6 + s.ph) * 0.4, th + Math.PI / 4); d.scale.setScalar(s.s); d.updateMatrix(); inst.setMatrixAt(i, d.matrix);
        const by = L + 0.47 * s.s;
        for (let k = 0; k < 3; k++) {
          d.position.set(s.x + sn * by + (k - 1) * 0.025 * s.s, s.y - cs * by, s.z + (k - 1) * 0.012); d.rotation.set(0.1 * (k - 1), 0, th + (k - 1) * 0.12 + Math.sin(T * 2 + s.ph + k) * 0.05); d.scale.setScalar(s.s); d.updateMatrix(); tassel.setMatrixAt(i * 3 + k, d.matrix);
        }
      }
      inst.instanceMatrix.needsUpdate = strand.instanceMatrix.needsUpdate = tassel.instanceMatrix.needsUpdate = true;
    },
    dispose() { own.forEach((o) => o.dispose()); inst.dispose(); strand.dispose(); tassel.dispose(); },
  };
}
