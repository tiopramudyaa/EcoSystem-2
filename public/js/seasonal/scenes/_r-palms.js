// Palem kurma/kelapa siluet: batang melengkung dan pelepah berkartu alfa, semua digabung jadi satu geometri (1 draw call).
// Tidak ada yang berjalan saat import. Pemanggil membuat material (map dari palmTexture) dan membuang geometri.

/** specs: [{ x, y, z, h, lean, rot, fr }]. Mengembalikan BufferGeometry dengan position, normal, uv, color. */
export function buildPalmGeometry(THREE, rnd, specs, { fronds = 12, segs = 5 } = {}) {
  const pos = [], nor = [], uv = [], col = [], idx = [];
  const base = () => pos.length / 3;
  const c = new THREE.Color();
  for (const sp of specs) {
    const { x, y, z, h, lean = 0.12, rot = 0 } = sp;
    const cr = Math.cos(rot), sr = Math.sin(rot);
    const P = (lx, ly, lz) => [x + lx * cr + lz * sr, y + ly, z - lx * sr + lz * cr]; // putar di sumbu y
    const tone = 0.75 + rnd() * 0.35;
    c.setRGB(0.03 * tone, 0.05 * tone, 0.065 * tone);
    // batang: 8 cincin, 5 sisi
    const R = 8, N = 5, b0 = base();
    const path = [];
    for (let i = 0; i <= R; i++) { const u = i / R; path.push([lean * h * u * u, h * u, 0, 0.42 - 0.17 * u]); }
    for (let i = 0; i <= R; i++) {
      const [px, py, pz, r] = path[i];
      for (let k = 0; k < N; k++) {
        const a = k / N * Math.PI * 2, nx = Math.cos(a), nz = Math.sin(a);
        const p = P(px + nx * r, py, pz + nz * r), n = P(nx, 0, nz);
        pos.push(p[0], p[1], p[2]); nor.push(n[0] - x, 0, n[2] - z); uv.push(0.05, 0.5); col.push(c.r * (0.8 + 0.2 * (k % 2)), c.g, c.b);
      }
    }
    for (let i = 0; i < R; i++) for (let k = 0; k < N; k++) {
      const a = b0 + i * N + k, b = b0 + i * N + (k + 1) % N, d = b0 + (i + 1) * N + k, e = b0 + (i + 1) * N + (k + 1) % N;
      idx.push(a, d, b, b, d, e);
    }
    // pelepah: kartu melengkung ke bawah, dua kolom (lipatan V)
    const top = path[R], FL = 0.2 + rnd() * 0.05;
    for (let f = 0; f < fronds; f++) {
      const az = f / fronds * Math.PI * 2 + rnd() * 0.3, len = (6.5 + rnd() * 2.2) * (h / 15), up = 0.55 - (f % 3) * 0.28 + rnd() * 0.15;
      const dx = Math.cos(az), dz = Math.sin(az), wdt = len * 0.52 * (0.85 + rnd() * 0.3);
      const b1 = base();
      for (let s = 0; s <= segs; s++) {
        const t = s / segs, along = t * len, drop = (t * t) * len * (0.5 + 0.55 * (1 - up)) - t * len * up * 0.5;
        const cx = top[0] + dx * along, cy = top[1] - drop + 0.3, cz = top[2] + dz * along;
        for (const sd of [-1, 0, 1]) {
          const off = sd * wdt * 0.5, fold = Math.abs(sd) * wdt * 0.12;
          const p = P(cx - dz * off, cy - fold, cz + dx * off);
          pos.push(p[0], p[1], p[2]); nor.push(0, 1, 0);
          uv.push(0.234 + t * 0.743, 0.5 + sd * 0.47); col.push(c.r * 1.1, c.g * 1.15, c.b * 1.1);
        }
      }
      for (let s = 0; s < segs; s++) for (let q = 0; q < 2; q++) {
        const a = b1 + s * 3 + q, b = b1 + s * 3 + q + 1, d = b1 + (s + 1) * 3 + q, e = b1 + (s + 1) * 3 + q + 1;
        idx.push(a, b, d, b, e, d);
      }
    }
    void FL;
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
  g.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
  g.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
  g.setAttribute('color', new THREE.Float32BufferAttribute(col, 3));
  g.setIndex(idx);
  g.computeBoundingSphere();
  return g;
}
