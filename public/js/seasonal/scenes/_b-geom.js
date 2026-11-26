// Helper geometri untuk adegan lunar: penggabung bagian berwarna vertex, tabung dan loft statis, tabung dinamis, pita dinamis.
// Tidak ada yang berjalan saat import.

/** Penggabung geometri menjadi satu BufferGeometry dengan warna vertex. Warna vertex geometri (bila ada) dikalikan warna yang diberikan. */
export function merger(THREE) {
  const pos = [], nor = [], col = [], uvs = [], idx = [];
  const M = new THREE.Matrix4(), N3 = new THREE.Matrix3(), v = new THREE.Vector3(), q = new THREE.Quaternion();
  const e = new THREE.Euler(), p = new THREE.Vector3(), s = new THREE.Vector3(), a = new THREE.Vector3(), UP = new THREE.Vector3(0, 1, 0);
  const col3 = (c) => (c && c.isColor ? c : new THREE.Color(c === undefined || c === null ? '#ffffff' : c));
  const api = {
    /** Tambah geometri dengan posisi, rotasi Euler, skala. */
    add(geo, color, x = 0, y = 0, z = 0, rx = 0, ry = 0, rz = 0, sx = 1, sy = 1, sz = 1) {
      M.compose(p.set(x, y, z), q.setFromEuler(e.set(rx, ry, rz)), s.set(sx, sy, sz));
      return api.addM(geo, color, M);
    },
    addM(geo, color, m) {
      const c = col3(color);
      N3.getNormalMatrix(m);
      const P = geo.attributes.position, Nn = geo.attributes.normal, U = geo.attributes.uv, C = geo.attributes.color, base = pos.length / 3;
      for (let i = 0; i < P.count; i++) {
        v.fromBufferAttribute(P, i).applyMatrix4(m); pos.push(v.x, v.y, v.z);
        v.fromBufferAttribute(Nn, i).applyMatrix3(N3).normalize(); nor.push(v.x, v.y, v.z);
        if (C) col.push(C.getX(i) * c.r, C.getY(i) * c.g, C.getZ(i) * c.b); else col.push(c.r, c.g, c.b);
        if (U) uvs.push(U.getX(i), U.getY(i)); else uvs.push(0, 0);
      }
      if (geo.index) for (let i = 0; i < geo.index.count; i++) idx.push(geo.index.getX(i) + base);
      else for (let i = 0; i < P.count; i++) idx.push(i + base);
      geo.dispose();
      return api;
    },
    /** Silinder meruncing dari titik A (radius r0) ke B (radius r1). */
    limb(A, B, r0, r1, color, seg = 6) {
      a.set(B[0] - A[0], B[1] - A[1], B[2] - A[2]);
      const len = a.length();
      q.setFromUnitVectors(UP, a.normalize());
      M.compose(p.set((A[0] + B[0]) / 2, (A[1] + B[1]) / 2, (A[2] + B[2]) / 2), q, s.set(1, 1, 1));
      return api.addM(new THREE.CylinderGeometry(r1, r0, len, seg, 1), color, M);
    },
    build() {
      const g = new THREE.BufferGeometry();
      g.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
      g.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
      g.setAttribute('color', new THREE.Float32BufferAttribute(col, 3));
      g.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));
      g.setIndex(idx);
      return g;
    },
  };
  return api;
}

/** Catmull-Rom (centripetal tidak perlu) pada array titik [[x,y,z],...], t di 0..1 sepanjang seluruh kurva. Menulis ke out (array 3). */
function catmull(pts, t, out) {
  const n = pts.length - 1, f = Math.min(n - 1e-6, Math.max(0, t * n)), i = Math.floor(f), u = f - i;
  const p0 = pts[Math.max(0, i - 1)], p1 = pts[i], p2 = pts[i + 1], p3 = pts[Math.min(n, i + 2)], u2 = u * u, u3 = u2 * u;
  for (let k = 0; k < 3; k++) out[k] = 0.5 * ((2 * p1[k]) + (-p0[k] + p2[k]) * u + (2 * p0[k] - 5 * p1[k] + 4 * p2[k] - p3[k]) * u2 + (-p0[k] + 3 * p1[k] - 3 * p2[k] + p3[k]) * u3);
  return out;
}

/**
 * Tabung statis sepanjang kurva Catmull-Rom. pts: titik kontrol; rad: fungsi (u)->radius atau array radius per titik kontrol;
 * opts: { seg, radial, flat (skala sumbu normal kedua, 1 = bundar), c0, c1 (warna awal dan akhir), uvk (skala uv sepanjang), tip (menutup ujung) }.
 */
export function tube(THREE, pts, rad, opts = {}) {
  const seg = opts.seg || 14, R = opts.radial || 8, flat = opts.flat === undefined ? 1 : opts.flat, uvk = opts.uvk || 1;
  const c0 = new THREE.Color(opts.c0 || '#ffffff'), c1 = new THREE.Color(opts.c1 || opts.c0 || '#ffffff'), cc = new THREE.Color();
  const radAt = typeof rad === 'number' ? () => rad : typeof rad === 'function' ? rad : (u) => { const f = u * (rad.length - 1), i = Math.min(rad.length - 2, Math.floor(f)); return rad[i] + (rad[i + 1] - rad[i]) * (f - i); };
  const n = seg + 1, P = new Float32Array(n * (R + 1) * 3), N = new Float32Array(n * (R + 1) * 3), C = new Float32Array(n * (R + 1) * 3), UV = new Float32Array(n * (R + 1) * 2), idx = [];
  const A = [0, 0, 0], B = [0, 0, 0], T = new THREE.Vector3(), nr = new THREE.Vector3(), bn = new THREE.Vector3(), prev = new THREE.Vector3(), ref = new THREE.Vector3(0, 0, 1);
  let len = 0, last = null;
  for (let i = 0; i < n; i++) {
    const u = i / seg; catmull(pts, u, A); catmull(pts, Math.min(1, u + 0.002), B);
    if (u > 0.998) { catmull(pts, u - 0.002, B); T.set(A[0] - B[0], A[1] - B[1], A[2] - B[2]); } else T.set(B[0] - A[0], B[1] - A[1], B[2] - A[2]);
    T.normalize();
    if (i === 0) { nr.crossVectors(T, ref); if (nr.lengthSq() < 1e-3) nr.crossVectors(T, prev.set(0, 1, 0)); nr.normalize(); } else { nr.addScaledVector(T, -nr.dot(T)).normalize(); }
    bn.crossVectors(T, nr).normalize();
    if (last) len += Math.hypot(A[0] - last[0], A[1] - last[1], A[2] - last[2]);
    last = [A[0], A[1], A[2]];
    const r = radAt(u); cc.copy(c0).lerp(c1, u);
    for (let j = 0; j <= R; j++) {
      const a = j / R * Math.PI * 2, cs = Math.cos(a), sn = Math.sin(a), k = (i * (R + 1) + j) * 3;
      const dx = cs * nr.x + sn * bn.x * flat, dy = cs * nr.y + sn * bn.y * flat, dz = cs * nr.z + sn * bn.z * flat;
      P[k] = A[0] + dx * r; P[k + 1] = A[1] + dy * r; P[k + 2] = A[2] + dz * r;
      const m = Math.hypot(cs * nr.x + sn * bn.x / Math.max(flat, 0.2), cs * nr.y + sn * bn.y / Math.max(flat, 0.2), cs * nr.z + sn * bn.z / Math.max(flat, 0.2)) || 1;
      N[k] = (cs * nr.x + sn * bn.x / Math.max(flat, 0.2)) / m; N[k + 1] = (cs * nr.y + sn * bn.y / Math.max(flat, 0.2)) / m; N[k + 2] = (cs * nr.z + sn * bn.z / Math.max(flat, 0.2)) / m;
      C[k] = cc.r; C[k + 1] = cc.g; C[k + 2] = cc.b;
      UV[(i * (R + 1) + j) * 2] = j / R; UV[(i * (R + 1) + j) * 2 + 1] = len * uvk;
      if (i < n - 1 && j < R) { const q0 = i * (R + 1) + j; idx.push(q0, q0 + 1, q0 + R + 1, q0 + 1, q0 + R + 2, q0 + R + 1); }
    }
  }
  if (opts.tip !== false) { // titik penutup ujung
    const base = P.length / 3, ext = new Float32Array(P.length + 3), en = new Float32Array(N.length + 3), ec = new Float32Array(C.length + 3), eu = new Float32Array(UV.length + 2);
    ext.set(P); en.set(N); ec.set(C); eu.set(UV);
    ext[base * 3] = A[0] + T.x * radAt(1) * 0.8; ext[base * 3 + 1] = A[1] + T.y * radAt(1) * 0.8; ext[base * 3 + 2] = A[2] + T.z * radAt(1) * 0.8;
    en[base * 3] = T.x; en[base * 3 + 1] = T.y; en[base * 3 + 2] = T.z; ec[base * 3] = cc.r; ec[base * 3 + 1] = cc.g; ec[base * 3 + 2] = cc.b; eu[base * 2] = 0.5; eu[base * 2 + 1] = len * uvk;
    const row = (n - 1) * (R + 1);
    for (let j = 0; j < R; j++) idx.push(row + j, row + j + 1, base);
    const g = new THREE.BufferGeometry();
    g.setAttribute('position', new THREE.BufferAttribute(ext, 3)); g.setAttribute('normal', new THREE.BufferAttribute(en, 3)); g.setAttribute('color', new THREE.BufferAttribute(ec, 3)); g.setAttribute('uv', new THREE.BufferAttribute(eu, 2)); g.setIndex(idx);
    return g;
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(P, 3)); g.setAttribute('normal', new THREE.BufferAttribute(N, 3)); g.setAttribute('color', new THREE.BufferAttribute(C, 3)); g.setAttribute('uv', new THREE.BufferAttribute(UV, 2)); g.setIndex(idx);
  return g;
}

/**
 * Loft penampang superellipse sepanjang sumbu x. rings: [{x, y0 (bawah), y1 (atas), w (setengah lebar), n (eksponen, 2 = elips)}].
 * color: fungsi (ringIndex, phi, out) menulis Color. uv: u = jarak sepanjang * uk, v = sudut (0 di puncak, 0.5 di bawah) * vk.
 */
export function loft(THREE, rings, radial, color, uk = 1, vk = 1) {
  const R = radial, N = rings.length, P = new Float32Array(N * (R + 1) * 3), Nr = new Float32Array(N * (R + 1) * 3), Cl = new Float32Array(N * (R + 1) * 3), UV = new Float32Array(N * (R + 1) * 2), idx = [];
  const cc = new THREE.Color(), cs = new Float32Array(R + 1), sn = new Float32Array(R + 1);
  for (let j = 0; j <= R; j++) { const a = j / R * Math.PI * 2; cs[j] = Math.cos(a); sn[j] = Math.sin(a); }
  let len = 0;
  const pt = (i, j, o) => {
    const r = rings[i], e = 2 / (r.n || 2), c = cs[j], s = sn[j], yc = (r.y0 + r.y1) / 2, hh = (r.y1 - r.y0) / 2;
    o[0] = r.x; o[1] = yc + Math.sign(s) * Math.pow(Math.abs(s), e) * hh; o[2] = Math.sign(c) * Math.pow(Math.abs(c), e) * r.w;
  };
  const a = [0, 0, 0], b = [0, 0, 0], c2 = [0, 0, 0], d = [0, 0, 0];
  for (let i = 0; i < N; i++) {
    if (i) len += Math.hypot(rings[i].x - rings[i - 1].x, (rings[i].y0 + rings[i].y1) / 2 - (rings[i - 1].y0 + rings[i - 1].y1) / 2);
    for (let j = 0; j <= R; j++) {
      const k = (i * (R + 1) + j) * 3;
      pt(i, j, a); P[k] = a[0]; P[k + 1] = a[1]; P[k + 2] = a[2];
      pt(Math.min(N - 1, i + 1), j, b); pt(Math.max(0, i - 1), j, c2);
      pt(i, (j + 1) % R, d); pt(i, (j + R - 1) % R, a);
      // normal = (dv x du), arah luar
      const ux = b[0] - c2[0], uy = b[1] - c2[1], uz = b[2] - c2[2], vx = d[0] - a[0], vy = d[1] - a[1], vz = d[2] - a[2];
      let nx = vy * uz - vz * uy, ny = vz * ux - vx * uz, nz = vx * uy - vy * ux; const m = Math.hypot(nx, ny, nz) || 1;
      nx /= m; ny /= m; nz /= m;
      const ox = P[k] - rings[i].x, oy = P[k + 1] - (rings[i].y0 + rings[i].y1) / 2, oz = P[k + 2];
      if (nx * ox * 0 + ny * oy + nz * oz < 0) { nx = -nx; ny = -ny; nz = -nz; }
      Nr[k] = nx; Nr[k + 1] = ny; Nr[k + 2] = nz;
      color(i, Math.atan2(sn[j], cs[j]), cc); Cl[k] = cc.r; Cl[k + 1] = cc.g; Cl[k + 2] = cc.b;
      const ph = (Math.atan2(sn[j], cs[j]) - Math.PI / 2 + Math.PI * 4) % (Math.PI * 2);
      UV[(i * (R + 1) + j) * 2] = (j === R ? 1 : ph / (Math.PI * 2)) * vk; UV[(i * (R + 1) + j) * 2 + 1] = len * uk;
      if (i < N - 1 && j < R) { const q0 = i * (R + 1) + j; idx.push(q0, q0 + 1, q0 + R + 1, q0 + 1, q0 + R + 2, q0 + R + 1); }
    }
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(P, 3)); g.setAttribute('normal', new THREE.BufferAttribute(Nr, 3)); g.setAttribute('color', new THREE.BufferAttribute(Cl, 3)); g.setAttribute('uv', new THREE.BufferAttribute(UV, 2)); g.setIndex(idx);
  return g;
}

/**
 * Banyak tabung dinamis dalam satu geometri (kumis, janggut, kaki). Isi lewat set(tube, j, x, y, z, radius) lalu commit().
 * Bingkai dihitung dari tangen tiap titik tanpa alokasi per frame.
 */
export function dynTubes(THREE, count, n, R, c0s) {
  const per = n * (R + 1), pos = new Float32Array(count * per * 3), nor = new Float32Array(count * per * 3), col = new Float32Array(count * per * 3), uv = new Float32Array(count * per * 2), idx = [];
  const cen = new Float32Array(count * n * 3), rad = new Float32Array(count * n), cs = new Float32Array(R + 1), sn = new Float32Array(R + 1);
  for (let j = 0; j <= R; j++) { const a = j / R * Math.PI * 2; cs[j] = Math.cos(a); sn[j] = Math.sin(a); }
  const cc = new THREE.Color();
  for (let t = 0; t < count; t++) {
    const ca = new THREE.Color(c0s[t][0]), cb = new THREE.Color(c0s[t][1] || c0s[t][0]);
    for (let i = 0; i < n; i++) {
      cc.copy(ca).lerp(cb, i / (n - 1));
      for (let j = 0; j <= R; j++) {
        const k = t * per + i * (R + 1) + j; col[k * 3] = cc.r; col[k * 3 + 1] = cc.g; col[k * 3 + 2] = cc.b; uv[k * 2] = j / R; uv[k * 2 + 1] = i / (n - 1) * 4;
        if (i < n - 1 && j < R) idx.push(k, k + 1, k + R + 1, k + 1, k + R + 2, k + R + 1);
      }
    }
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3)); g.setAttribute('normal', new THREE.BufferAttribute(nor, 3)); g.setAttribute('color', new THREE.BufferAttribute(col, 3)); g.setAttribute('uv', new THREE.BufferAttribute(uv, 2)); g.setIndex(idx);
  let tx = 0, ty = 0, tz = 0, nx = 0, ny = 0, nz = 0, bx = 0, by = 0, bz = 0;
  return {
    geo: g,
    set(t, j, x, y, z, r) { const i = t * n + j; cen[i * 3] = x; cen[i * 3 + 1] = y; cen[i * 3 + 2] = z; rad[i] = r; },
    commit() {
      for (let t = 0; t < count; t++) {
        for (let i = 0; i < n; i++) {
          const a = t * n + Math.max(0, i - 1), b = t * n + Math.min(n - 1, i + 1), c = t * n + i;
          tx = cen[b * 3] - cen[a * 3]; ty = cen[b * 3 + 1] - cen[a * 3 + 1]; tz = cen[b * 3 + 2] - cen[a * 3 + 2];
          let m = Math.hypot(tx, ty, tz) || 1; tx /= m; ty /= m; tz /= m;
          // referensi: sumbu y, atau sumbu z bila tangen hampir vertikal
          let rx = 0, ry = 1, rz = 0; if (Math.abs(ty) > 0.9) { ry = 0; rz = 1; }
          nx = ty * rz - tz * ry; ny = tz * rx - tx * rz; nz = tx * ry - ty * rx; m = Math.hypot(nx, ny, nz) || 1; nx /= m; ny /= m; nz /= m;
          bx = ty * nz - tz * ny; by = tz * nx - tx * nz; bz = tx * ny - ty * nx;
          const r = rad[c];
          for (let j = 0; j <= R; j++) {
            const k = (t * per + i * (R + 1) + j) * 3, dx = cs[j] * nx + sn[j] * bx, dy = cs[j] * ny + sn[j] * by, dz = cs[j] * nz + sn[j] * bz;
            pos[k] = cen[c * 3] + dx * r; pos[k + 1] = cen[c * 3 + 1] + dy * r; pos[k + 2] = cen[c * 3 + 2] + dz * r;
            nor[k] = dx; nor[k + 1] = dy; nor[k + 2] = dz;
          }
        }
      }
      g.attributes.position.needsUpdate = true; g.attributes.normal.needsUpdate = true; g.computeBoundingSphere();
    },
  };
}

/** Banyak pita dinamis (count pita, tiap pita n titik) dalam satu geometri; isi lewat set(k, j, cx, cy, cz, ox, oy, oz) lalu commit(). Warna: colorFn(u, Color). */
export function ribbons(THREE, count, n, colorFn) {
  const per = n * 2, pos = new Float32Array(count * per * 3), nor = new Float32Array(count * per * 3), uv = new Float32Array(count * per * 2), col = new Float32Array(count * per * 3), idx = [];
  const cc = new THREE.Color();
  for (let k = 0; k < count; k++) {
    for (let j = 0; j < n; j++) {
      const b = k * per + j * 2;
      nor[b * 3 + 2] = 1; nor[b * 3 + 5] = 1;
      uv.set([0, j / (n - 1), 1, j / (n - 1)], b * 2);
      if (colorFn) colorFn(j / (n - 1), cc); else cc.setRGB(1, 1, 1);
      col.set([cc.r, cc.g, cc.b, cc.r, cc.g, cc.b], b * 3);
      if (j < n - 1) idx.push(b, b + 1, b + 2, b + 1, b + 3, b + 2);
    }
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  g.setAttribute('normal', new THREE.BufferAttribute(nor, 3));
  g.setAttribute('uv', new THREE.BufferAttribute(uv, 2));
  g.setAttribute('color', new THREE.BufferAttribute(col, 3));
  g.setIndex(idx);
  return {
    geo: g,
    set(k, j, cx, cy, cz, ox, oy, oz) {
      const i = (k * per + j * 2) * 3;
      pos[i] = cx - ox; pos[i + 1] = cy - oy; pos[i + 2] = cz - oz;
      pos[i + 3] = cx + ox; pos[i + 4] = cy + oy; pos[i + 5] = cz + oz;
    },
    commit() { g.attributes.position.needsUpdate = true; g.computeBoundingSphere(); },
  };
}
