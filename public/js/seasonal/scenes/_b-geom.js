// Helper geometri untuk adegan lunar dan flag: penggabung bagian berwarna vertex dan pita dinamis. Tidak ada yang berjalan saat import.

/** Penggabung geometri bawaan THREE menjadi satu BufferGeometry dengan warna vertex. */
export function merger(THREE) {
  const pos = [], nor = [], col = [], uvs = [], idx = [];
  const M = new THREE.Matrix4(), N3 = new THREE.Matrix3(), v = new THREE.Vector3(), q = new THREE.Quaternion();
  const e = new THREE.Euler(), p = new THREE.Vector3(), s = new THREE.Vector3(), a = new THREE.Vector3(), UP = new THREE.Vector3(0, 1, 0);
  const col3 = (c) => (c && c.isColor ? c : new THREE.Color(c));
  const api = {
    /** Tambah geometri dengan posisi, rotasi Euler, skala. */
    add(geo, color, x = 0, y = 0, z = 0, rx = 0, ry = 0, rz = 0, sx = 1, sy = 1, sz = 1) {
      M.compose(p.set(x, y, z), q.setFromEuler(e.set(rx, ry, rz)), s.set(sx, sy, sz));
      return api.addM(geo, color, M);
    },
    addM(geo, color, m) {
      const c = col3(color);
      N3.getNormalMatrix(m);
      const P = geo.attributes.position, Nn = geo.attributes.normal, U = geo.attributes.uv, base = pos.length / 3;
      for (let i = 0; i < P.count; i++) {
        v.fromBufferAttribute(P, i).applyMatrix4(m); pos.push(v.x, v.y, v.z);
        v.fromBufferAttribute(Nn, i).applyMatrix3(N3).normalize(); nor.push(v.x, v.y, v.z);
        col.push(c.r, c.g, c.b);
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

/** Pita dinamis n titik; isi lewat set(j, cx, cy, cz, ox, oy, oz) lalu commit(). Normal tetap menghadap +z. */
export function ribbon(THREE, n) {
  const pos = new Float32Array(n * 6), nor = new Float32Array(n * 6), uv = new Float32Array(n * 4), idx = [];
  for (let j = 0; j < n; j++) {
    nor[j * 6 + 2] = 1; nor[j * 6 + 5] = 1;
    uv[j * 4] = 0; uv[j * 4 + 1] = j / (n - 1); uv[j * 4 + 2] = 1; uv[j * 4 + 3] = j / (n - 1);
    if (j < n - 1) { const k = j * 2; idx.push(k, k + 1, k + 2, k + 1, k + 3, k + 2); }
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  g.setAttribute('normal', new THREE.BufferAttribute(nor, 3));
  g.setAttribute('uv', new THREE.BufferAttribute(uv, 2));
  g.setIndex(idx);
  return {
    geo: g,
    set(j, cx, cy, cz, ox, oy, oz) {
      const i = j * 6;
      pos[i] = cx - ox; pos[i + 1] = cy - oy; pos[i + 2] = cz - oz;
      pos[i + 3] = cx + ox; pos[i + 4] = cy + oy; pos[i + 5] = cz + oz;
    },
    commit() { g.attributes.position.needsUpdate = true; g.computeBoundingSphere(); },
  };
}
