// Latar dan properti adegan flag: langit terlukis, tanah, punggung bukit dan palem, tiang bendera, bambu runcing silang,
// rumbai merah putih, patung pahlawan di kejauhan. Tidak ada yang berjalan saat import. Semua digabung per material.
import { pal, paintedSky, mergeParts } from '../common.js';
import { buildFigure } from './_h-figures.js';

export const SUN_AZ = -0.52, SUN_EL = 0.07;

/** Warna identitas dari palet tema (merah bendera): dipakai langit, kabut, bukit, tanah. */
export function flagGrade(THREE, palette) {
  const K = (h) => new THREE.Color(h);
  const has = !!(palette && palette.login_from);
  const P = has ? pal(THREE, palette) : null;
  const from = has ? P.from : K('#3d0610'), to = has ? P.to : K('#a80c24');
  const mid = has && palette.login_mid ? K(palette.login_mid) : from.clone().lerp(to, 0.5);
  return { K, from, mid, to };
}

/** Langit fajar: matahari putih keemasan, jingga hangat, lalu merah tua (palet tema) ke atas. */
export function dawnSky(THREE, rnd, palette) {
  const { K, from, mid, to } = flagGrade(THREE, palette);
  return paintedSky(THREE, rnd, {
    span: 3.5, upTop: 1.05, below: 0.12,
    stops: [[0, K('#fff0cf')], [0.05, K('#ffc07c')], [0.14, K('#f58450')], [0.24, to.clone().lerp(K('#ff7a4a'), 0.4)], [0.38, mid.clone().lerp(to, 0.1)], [0.56, mid.clone().lerp(from, 0.45)], [0.8, from.clone().lerp(mid, 0.25)], [1, from.clone()]],
    paint: (g, m) => {
      const puff = (x, y, rx, ry, body, glow, a) => {
        g.save(); g.translate(x, y); g.scale(rx, ry);
        let r = g.createRadialGradient(0, 0, 0, 0, 0, 1); r.addColorStop(0, `rgba(${body},${a})`); r.addColorStop(1, `rgba(${body},0)`);
        g.fillStyle = r; g.fillRect(-1, -1, 2, 2);
        r = g.createRadialGradient(0, 0.35, 0, 0, 0.35, 0.8); r.addColorStop(0, `rgba(${glow},${a * 0.8})`); r.addColorStop(1, `rgba(${glow},0)`);
        g.fillStyle = r; g.fillRect(-1, -1, 2, 2);
        g.restore();
      };
      // pita awan rendah dekat cakrawala (lebih datar, terang oleh fajar)
      for (let i = 0; i < 96; i++) {
        const az = (rnd() - 0.5) * 3.3, el = 0.035 + rnd() * 0.12, near = Math.exp(-Math.pow((az - SUN_AZ) / 0.7, 2));
        puff(m.x(az), m.y(el), 40 + rnd() * 90, 5 + rnd() * 9, '200,70,80', '255,176,100', 0.1 + 0.2 * near + rnd() * 0.06);
      }
      // awan tengah: kumpulan gumpalan lembut
      for (let c = 0; c < 13; c++) {
        const az = (rnd() - 0.5) * 3.1, el = 0.2 + rnd() * 0.4, sc = 0.7 + el;
        const near = Math.exp(-Math.pow((az - SUN_AZ) / 0.9, 2));
        for (let i = 0; i < 14; i++) puff(m.x(az + (rnd() - 0.5) * 0.3), m.y(el + (rnd() - 0.5) * 0.035), (28 + rnd() * 50) * sc, (8 + rnd() * 13) * sc, '150,36,64', '255,140,90', 0.07 + 0.1 * near + rnd() * 0.05);
      }
      // kabut sinar di sekitar matahari
      const sx = m.x(SUN_AZ), sy = m.y(SUN_EL);
      const r = g.createRadialGradient(sx, sy, 0, sx, sy, 300);
      r.addColorStop(0, 'rgba(255,240,200,0.8)'); r.addColorStop(0.25, 'rgba(255,190,120,0.4)'); r.addColorStop(0.6, 'rgba(235,90,70,0.16)'); r.addColorStop(1, 'rgba(220,70,60,0)');
      g.fillStyle = r; g.fillRect(sx - 300, sy - 300, 600, 600);
    },
  });
}

/** Tanah lapangan upacara: tekstur rumput berembun yang berulang. */
export function groundMesh(THREE, rnd) {
  const c = document.createElement('canvas'); c.width = c.height = 256;
  const g = c.getContext('2d');
  g.fillStyle = '#5f5c30'; g.fillRect(0, 0, 256, 256);
  for (let i = 0; i < 2600; i++) { const x = rnd() * 256, y = rnd() * 256, k = rnd(); g.fillStyle = k < 0.35 ? 'rgba(140,130,62,0.35)' : k < 0.7 ? 'rgba(60,34,22,0.42)' : 'rgba(200,190,120,0.2)'; g.fillRect(x, y, 1 + rnd() * 1.5, 1 + rnd() * 3); }
  for (let i = 0; i < 90; i++) { g.fillStyle = 'rgba(80,60,35,0.18)'; g.beginPath(); g.ellipse(rnd() * 256, rnd() * 256, 3 + rnd() * 8, 1 + rnd() * 3, rnd() * 3, 0, 6.28); g.fill(); }
  const tex = new THREE.CanvasTexture(c);
  tex.colorSpace = THREE.SRGBColorSpace; tex.wrapS = tex.wrapT = THREE.RepeatWrapping; tex.repeat.set(70, 60); tex.anisotropy = 4;
  const geo = new THREE.PlaneGeometry(700, 600);
  const mat = new THREE.MeshBasicMaterial({ map: tex, color: '#c8a088' });
  const mesh = new THREE.Mesh(geo, mat);
  mesh.rotation.x = -Math.PI / 2; mesh.position.set(0, 0, -240);
  return { mesh, dispose() { tex.dispose(); geo.dispose(); mat.dispose(); } };
}

/** Punggung bukit berlapis dan siluet palem (MeshBasicMaterial, berkabut). Satu mesh. */
export function horizonMesh(THREE, rnd) {
  const pos = [], col = [], idx = [];
  const C = new THREE.Color();
  const ridge = (z, amp, base, color, seed) => {
    const b = pos.length / 3, n = 150;
    C.set(color);
    for (let i = 0; i <= n; i++) {
      const x = -900 + (1800 * i) / n;
      const h = base + amp * (0.5 + 0.5 * Math.sin(x * 0.021 + seed) * 0.6 + 0.5 * Math.sin(x * 0.057 + seed * 2.1) * 0.3 + 0.5 * Math.sin(x * 0.13 + seed * 3) * 0.1);
      pos.push(x, h, z, x, -6, z); col.push(C.r, C.g, C.b, C.r, C.g, C.b);
    }
    for (let i = 0; i < n; i++) { const a = b + i * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
  };
  ridge(-300, 26, 6, '#96404a', 1.3);
  ridge(-215, 20, 4, '#722f3c', 4.1);
  ridge(-150, 14, 2, '#512330', 2.2);
  // palem siluet (batang melengkung + pelepah)
  C.set('#3a1a24');
  const quad = (a, b, c2, d) => { const o = pos.length / 3; [a, b, c2, d].forEach((p) => { pos.push(p[0], p[1], p[2]); col.push(C.r, C.g, C.b); }); idx.push(o, o + 1, o + 2, o, o + 2, o + 3, o, o + 2, o + 1, o, o + 3, o + 2); };
  const palm = (x, z, hgt, lean) => {
    C.set(['#6a2c3a', '#5e2634', '#74323e'][Math.floor(rnd() * 3)]);
    let px = x, py = 0;
    const w0 = hgt * 0.022;
    for (let s = 0; s < 4; s++) {
      const nx = px + lean * hgt * 0.07 * (s + 1), ny = py + hgt * 0.25, w1 = w0 * (1 - s * 0.14);
      quad([px - w0 * (1 - s * 0.14), py, z], [px + w0 * (1 - s * 0.14), py, z], [nx + w1 * 0.85, ny, z], [nx - w1 * 0.85, ny, z]);
      px = nx; py = ny;
    }
    for (let f = 0; f < 13; f++) {
      const ang = (f / 13) * Math.PI * 2 + rnd() * 0.3, L = hgt * (0.34 + rnd() * 0.08), dx = Math.cos(ang), dz = Math.sin(ang) * 0.7, w = L * 0.17, up = 0.32 + rnd() * 0.18;
      const p1 = [px + dx * L * 0.5, py + L * up, z + dz * L * 0.5], p2 = [px + dx * L, py - L * (0.2 + rnd() * 0.22), z + dz * L];
      const nx = -dz, nz = dx; // tegak lurus mendatar
      const o = pos.length / 3;
      [[px, py, z], [p1[0] + nx * w, p1[1], p1[2] + nz * w * 0.3], [p1[0] - nx * w, p1[1], p1[2] - nz * w * 0.3], p2].forEach((p) => { pos.push(p[0], p[1], p[2]); col.push(C.r, C.g, C.b); });
      idx.push(o, o + 1, o + 2, o + 1, o + 3, o + 2);
    }
  };
  for (let i = 0; i < 10; i++) palm(-125 + i * 26 + rnd() * 12, -105 - rnd() * 40, 12 + rnd() * 8, (rnd() - 0.5) * 0.9);
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); geo.setAttribute('color', new THREE.Float32BufferAttribute(col, 3)); geo.setIndex(idx);
  const mat = new THREE.MeshBasicMaterial({ vertexColors: true, side: THREE.DoubleSide });
  const mesh = new THREE.Mesh(geo, mat); mesh.frustumCulled = false;
  return { mesh, dispose() { geo.dispose(); mat.dispose(); } };
}

/** Tiang bendera: dua mesh (logam putih dan emas). Mengembalikan { group, top, dispose }. */
export function flagpole(THREE, { x, z, height = 14 }) {
  const metal = new THREE.MeshStandardMaterial({ color: '#e8e6df', roughness: 0.32, metalness: 0.45 });
  const gold = new THREE.MeshStandardMaterial({ color: '#ffd45a', roughness: 0.35, metalness: 0.2, emissive: '#a87414' });
  const stone = new THREE.MeshStandardMaterial({ color: '#f1ebdc', roughness: 0.9, emissive: '#4a3a30' });
  const M = new THREE.Matrix4(), T = (px, py, pz) => M.clone().makeTranslation(px, py, pz);
  const mp = [], gp = [], sp = [];
  const cyl = (arr, rt, rb, h, y, seg = 14, col = '#ffffff') => arr.push({ geo: new THREE.CylinderGeometry(rt, rb, h, seg), m: T(0, y, 0), color: col });
  // pelataran bertingkat dan podium
  cyl(sp, 2.6, 2.7, 0.25, 0.125, 28, '#bdb6a6'); cyl(sp, 2.0, 2.1, 0.3, 0.4, 28, '#d3ccbb'); cyl(sp, 1.3, 1.4, 0.55, 0.82, 24, '#e1dbca');
  cyl(mp, 0.09, 0.15, height * 0.55, 1.1 + height * 0.275); cyl(mp, 0.065, 0.09, height * 0.45, 1.1 + height * 0.55 + height * 0.225);
  cyl(mp, 0.14, 0.14, 0.08, 1.1 + height * 0.55); // sambungan
  cyl(gp, 0.17, 0.17, 0.1, 1.15); // cincin dasar emas
  // finial: bola dan bintang emas
  const top = 1.1 + height;
  gp.push({ geo: new THREE.SphereGeometry(0.17, 14, 10), m: T(0, top + 0.1, 0) });
  const st = new THREE.Shape(), R = 0.46, r = 0.19;
  for (let i = 0; i < 10; i++) { const a = Math.PI / 2 + (i * Math.PI) / 5, rr = i % 2 ? r : R; (i ? st.lineTo : st.moveTo).call(st, Math.cos(a) * rr, Math.sin(a) * rr); }
  st.closePath();
  const sg = new THREE.ExtrudeGeometry(st, { depth: 0.07, bevelEnabled: true, bevelThickness: 0.015, bevelSize: 0.015, bevelSegments: 1 });
  sg.translate(0, 0, -0.035);
  gp.push({ geo: sg, m: T(0, top + 0.62, 0) });
  gp.push({ geo: new THREE.ConeGeometry(0.05, 0.3, 8), m: T(0, top + 1.2, 0) });
  // tali kerek (halyard): dua garis tipis di sisi tiang
  mp.push({ geo: new THREE.CylinderGeometry(0.012, 0.012, height - 0.6, 3), m: T(0.15, 1.1 + (height - 0.6) / 2 + 0.3, 0.1), color: '#9d8a62' });
  mp.push({ geo: new THREE.CylinderGeometry(0.012, 0.012, height - 0.6, 3), m: T(0.17, 1.1 + (height - 0.6) / 2 + 0.3, 0.12), color: '#9d8a62' });
  const mk = (parts, mat) => { const g = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose()); g.deleteAttribute('uv'); const me = new THREE.Mesh(g, mat); return me; };
  metal.vertexColors = true; gold.vertexColors = true; stone.vertexColors = true;
  gp.forEach((p) => { p.color = p.color || '#ffffff'; });
  const group = new THREE.Group();
  const meshes = [mk(mp, metal), mk(gp, gold), mk(sp, stone)];
  // lencana karangan laurel emas di muka podium
  const lp = [];
  const leafG = new THREE.SphereGeometry(1, 5, 4);
  const q = new THREE.Quaternion(), e = new THREE.Euler(), s = new THREE.Vector3(), p = new THREE.Vector3();
  for (const side of [-1, 1]) for (let i = 0; i < 9; i++) {
    const a = Math.PI * (0.62 + i * 0.105), cx = Math.cos(a) * 0.55 * side * -1, cy = Math.sin(a) * 0.38 + 0.74;
    e.set(0, 0, (a * side) + (side > 0 ? 0.5 : -0.5) + Math.PI / 2 * 0); q.setFromEuler(e);
    lp.push({ geo: leafG.clone(), m: new THREE.Matrix4().compose(p.set(cx, cy, 1.43), q, s.set(0.1, 0.045, 0.02)), color: '#e3b94c' });
  }
  leafG.dispose();
  const wr = mk(lp, gold);
  group.add(...meshes, wr);
  group.position.set(x, 0, z);
  return { group, top, dispose() { meshes.concat([wr]).forEach((m) => m.geometry.dispose()); metal.dispose(); gold.dispose(); stone.dispose(); } };
}

/** Bambu runcing: pasangan menyilang dengan pita merah putih. Satu mesh vertex-color. */
export function bambuRuncing(THREE, rnd, pairs) {
  const parts = [];
  const Q = new THREE.Quaternion(), E = new THREE.Euler(), S = new THREE.Vector3(1, 1, 1), P = new THREE.Vector3();
  const place = (x, y, z, rz, ry) => new THREE.Matrix4().compose(P.set(x, y, z), Q.setFromEuler(E.set(0, ry, rz)), S);
  const bam = (bx, bz, rz, ry, L) => {
    // sumbu lokal bambu: y ke atas; titik pada jarak d: (sin(-rz)... )
    const nodes = 5, segL = L / nodes;
    const dir = new THREE.Vector3(-Math.sin(rz) * Math.cos(ry), Math.cos(rz), Math.sin(rz) * Math.sin(ry)).normalize();
    const at = (d) => ({ x: bx + dir.x * d, y: dir.y * d, z: bz + dir.z * d });
    const body = ['#a8a24a', '#b4ac52', '#9d9744'];
    for (let n = 0; n < nodes; n++) {
      const d0 = n * segL, d1 = (n + 1) * segL, r0 = 0.085 - (0.04 * d0) / L, r1 = 0.085 - (0.04 * d1) / L, mid = at((d0 + d1) / 2);
      parts.push({ geo: new THREE.CylinderGeometry(r1, r0, segL * 0.96, 7, 1, true), m: place(mid.x, mid.y, mid.z, rz, ry), color: body[n % 3] });
      const nd = at(d1); parts.push({ geo: new THREE.CylinderGeometry(r1 * 1.22, r1 * 1.22, 0.05, 7), m: place(nd.x, nd.y, nd.z, rz, ry), color: '#7d7330' });
    }
    const tipP = at(L + 0.42);
    parts.push({ geo: new THREE.ConeGeometry(0.044, 0.95, 7), m: place(tipP.x, tipP.y, tipP.z, rz, ry), color: '#d9d08a' });
    return at;
  };
  const ribbon = (p, col) => { // lilitan kain pada bambu
    parts.push({ geo: new THREE.CylinderGeometry(0.1, 0.1, 0.1, 8, 1, true), m: new THREE.Matrix4().makeTranslation(p.x, p.y, p.z), color: col });
  };
  const tail = (p, col, len, sway) => { // ujung pita menjuntai
    const g = new THREE.PlaneGeometry(0.1, len); g.translate(0, -len / 2, 0);
    parts.push({ geo: g, m: new THREE.Matrix4().compose(P.set(p.x + 0.1, p.y - 0.05, p.z + 0.1), Q.setFromEuler(E.set(0, 0.5, sway)), S), color: col });
  };
  pairs.forEach(({ x, z, d = 1.8, L = 8, th = 0.2, ry = 0 }) => {
    const aT = bam(x - d / 2, z, -th, ry, L), bT = bam(x + d / 2, z, th, ry, L);
    const h = d / (2 * Math.tan(th)) / Math.cos(th);
    const cp = aT(h * 1.0);
    ribbon({ x: cp.x + 0.0, y: cp.y, z: cp.z }, '#e70011'); ribbon({ x: cp.x, y: cp.y + 0.1, z: cp.z }, '#f4f2ec');
    tail(cp, '#e70011', 0.9, 0.08); tail({ x: cp.x + 0.12, y: cp.y, z: cp.z }, '#f4f2ec', 0.8, -0.05);
    const tp = aT(L * 0.88); ribbon(tp, '#e70011'); tail(tp, '#e70011', 0.7, 0.1);
    const tq = bT(L * 0.9); ribbon(tq, '#f4f2ec'); tail(tq, '#f4f2ec', 0.7, -0.1);
  });
  const geo = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose()); geo.deleteAttribute('uv');
  const mat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.62, side: THREE.DoubleSide });
  const mesh = new THREE.Mesh(geo, mat); mesh.frustumCulled = false;
  return { mesh, dispose() { geo.dispose(); mat.dispose(); } };
}

/** Rumbai merah putih (bunting) pada tali melengkung. Atribut aPh dan aHang untuk ayun. */
export function bunting(THREE, rnd, strings) {
  const pos = [], col = [], idx = [], ph = [], hang = [];
  const R = new THREE.Color('#e70011'), W = new THREE.Color('#fbf8f2'), Tw = new THREE.Color('#6b5a3c');
  const push = (x, y, z, c, hg, p) => { pos.push(x, y, z); col.push(c.r, c.g, c.b); hang.push(hg); ph.push(p); return pos.length / 3 - 1; };
  strings.forEach(({ a, b, sag = 1.4, n = 26, size = 0.62 }) => {
    const pt = (t) => [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t - sag * 4 * t * (1 - t), a[2] + (b[2] - a[2]) * t];
    for (let i = 0; i < n; i++) {
      const p0 = pt(i / n), p1 = pt((i + 1) / n);
      // tali: pita tipis (dua segitiga)
      const w = 0.012, u0 = push(p0[0], p0[1] + w, p0[2], Tw, 0, 0), u1 = push(p1[0], p1[1] + w, p1[2], Tw, 0, 0), d0 = push(p0[0], p0[1] - w, p0[2], Tw, 0, 0), d1 = push(p1[0], p1[1] - w, p1[2], Tw, 0, 0);
      idx.push(u0, d0, u1, u1, d0, d1);
      if (i % 1 === 0) {
        const c = i % 2 ? W : R, phs = rnd() * 6.28;
        const m = [(p0[0] + p1[0]) / 2, (p0[1] + p1[1]) / 2, (p0[2] + p1[2]) / 2], hw = Math.hypot(p1[0] - p0[0], p1[2] - p0[2]) * 0.46;
        const dx = (p1[0] - p0[0]) / (Math.hypot(p1[0] - p0[0], p1[2] - p0[2]) || 1), dz = (p1[2] - p0[2]) / (Math.hypot(p1[0] - p0[0], p1[2] - p0[2]) || 1);
        const l = push(m[0] - dx * hw, m[1] - 0.01, m[2] - dz * hw, c, 0, phs), r = push(m[0] + dx * hw, m[1] - 0.01, m[2] + dz * hw, c, 0, phs), t = push(m[0], m[1] - size * (0.9 + rnd() * 0.2), m[2], c, 1, phs), mm = push(m[0], m[1] - size * 0.45, m[2], c, 0.4, phs);
        idx.push(l, mm, t, mm, r, t, l, r, mm);
      }
    }
  });
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); geo.setAttribute('color', new THREE.Float32BufferAttribute(col, 3));
  geo.setAttribute('aPh', new THREE.Float32BufferAttribute(ph, 1)); geo.setAttribute('aHang', new THREE.Float32BufferAttribute(hang, 1)); geo.setIndex(idx); geo.computeVertexNormals();
  return geo;
}

/** Tiang bambu tinggi (untuk umbul-umbul dan tali rumbai). */
export function bambooPoles(THREE, poles) {
  const parts = [];
  poles.forEach(({ x, z, h, r = 0.06 }) => {
    parts.push({ geo: new THREE.CylinderGeometry(r * 0.6, r, h, 7, 1, true), m: new THREE.Matrix4().makeTranslation(x, h / 2, z), color: '#a89f48' });
    for (let i = 1; i < 6; i++) parts.push({ geo: new THREE.CylinderGeometry(r * 1.3, r * 1.3, 0.05, 7), m: new THREE.Matrix4().makeTranslation(x, (h * i) / 6, z), color: '#7d7330' });
  });
  const geo = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose()); geo.deleteAttribute('uv');
  const mat = new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.7 });
  const mesh = new THREE.Mesh(geo, mat); mesh.frustumCulled = false;
  return { mesh, dispose() { geo.dispose(); mat.dispose(); } };
}

/** Patung pahlawan di kejauhan (siluet berkabut): figur berdiri dengan bambu runcing terangkat di atas alas bertingkat. */
export function heroStatue(THREE, { x, z, scale = 7, color = '#4a3f5c' }) {
  const fig = buildFigure(THREE, 'hero', 1);
  const mat = new THREE.MeshBasicMaterial({ color, side: THREE.DoubleSide });
  const parts = [];
  parts.push({ geo: new THREE.BoxGeometry(3.4, 1.0, 2.4), m: new THREE.Matrix4().makeTranslation(0, 0.5, 0), color: '#fff' });
  parts.push({ geo: new THREE.BoxGeometry(2.6, 1.0, 1.8), m: new THREE.Matrix4().makeTranslation(0, 1.5, 0), color: '#fff' });
  parts.push({ geo: new THREE.BoxGeometry(1.9, 2.2, 1.3), m: new THREE.Matrix4().makeTranslation(0, 3.1, 0), color: '#fff' });
  const base = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose());
  const group = new THREE.Group();
  const bm = new THREE.Mesh(base, mat), fm = new THREE.Mesh(fig, mat);
  fm.scale.setScalar(scale / 2.0); fm.position.y = 4.2;
  // bambu runcing di tangan terangkat (tangan hero: x -0.325, y 2.08 pada skala figur)
  const k = scale / 2.0, hx = -0.325 * k, hy = 4.2 + 2.08 * k;
  const spG = new THREE.CylinderGeometry(0.035, 0.06, 8.6, 5); spG.translate(0, 4.3, 0);
  const sp = new THREE.Mesh(spG, mat); sp.position.set(hx, hy - 3.0, 0); sp.rotation.z = -0.16;
  const tipG = new THREE.ConeGeometry(0.07, 1.1, 5); tipG.translate(0, 9.15, 0);
  const tip = new THREE.Mesh(tipG, mat); tip.position.copy(sp.position); tip.rotation.z = -0.16;
  group.add(bm, fm, sp, tip);
  group.position.set(x, 0, z);
  group.rotation.y = 0.35;
  return { group, dispose() { base.dispose(); fig.dispose(); spG.dispose(); tipG.dispose(); mat.dispose(); } };
}
