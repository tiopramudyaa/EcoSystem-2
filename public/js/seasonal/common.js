// Helper bersama untuk adegan 3D musiman. Tidak ada yang berjalan saat import.

export const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
export const lerp = (a, b, t) => a + (b - a) * t;
export const smooth = (t) => { t = clamp(t); return t * t * (3 - 2 * t); };
export const easeInOut = (t) => { t = clamp(t); return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; };
export const easeOut = (t) => 1 - Math.pow(1 - clamp(t), 3);
/** Segmen waktu: 0 sebelum a, 1 setelah b. */
export const seg = (t, a, b) => clamp((t - a) / (b - a));

/** PRNG deterministik supaya tiap pemutaran konsisten. */
export function rng(seed = 1) {
  let s = seed >>> 0;
  return () => {
    s += 0x6d2b79f5;
    let t = s;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

/** Warna dari palette. primary_rgb berformat 'r g b'. */
export function pal(THREE, palette) {
  const c = (h) => new THREE.Color(h || '#ffffff');
  const rgb = (s) => {
    const p = String(s || '0 0 0').trim().split(/[\s,]+/).map(Number);
    return new THREE.Color(p[0] / 255, p[1] / 255, p[2] / 255);
  };
  const from = c(palette.login_from), to = c(palette.login_to), accent = c(palette.accent);
  return {
    from, to, accent,
    primary: rgb(palette.primary_rgb), primaryDark: rgb(palette.primary_dark_rgb),
    // turunan
    warm: accent.clone().lerp(new THREE.Color('#fff4dc'), 0.55),
    deep: from.clone().multiplyScalar(0.55),
    mix: (a, b, t) => a.clone().lerp(b, t),
  };
}

/** Texture kanvas kecil. */
export function canvasTex(THREE, w, h, draw, srgb = true) {
  const c = document.createElement('canvas');
  c.width = w; c.height = h;
  draw(c.getContext('2d'), w, h);
  const t = new THREE.CanvasTexture(c);
  if (srgb) t.colorSpace = THREE.SRGBColorSpace;
  return t;
}

export function glowTexture(THREE) {
  return canvasTex(THREE, 64, 64, (g, w, h) => {
    const r = g.createRadialGradient(w / 2, h / 2, 0, w / 2, h / 2, w / 2);
    r.addColorStop(0, 'rgba(255,255,255,1)');
    r.addColorStop(0.25, 'rgba(255,255,255,0.55)');
    r.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = r; g.fillRect(0, 0, w, h);
  });
}

/** Kubah langit bergradasi. stops: [[0..1 (bawah ke atas), '#hex'], ...] */
export function skyDome(THREE, stops, radius = 400) {
  const tex = canvasTex(THREE, 8, 256, (g, w, h) => {
    const gr = g.createLinearGradient(0, h, 0, 0);
    stops.forEach(([p, col]) => gr.addColorStop(p, typeof col === 'string' ? col : '#' + col.getHexString()));
    g.fillStyle = gr; g.fillRect(0, 0, w, h);
  });
  const m = new THREE.Mesh(
    new THREE.SphereGeometry(radius, 24, 16),
    new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false, depthWrite: false }),
  );
  m.renderOrder = -10;
  return m;
}

/** Bintang berkelap-kelip sebagai Points. Mengembalikan { points, update(time) }. */
export function stars(THREE, count, rnd, { radius = 300, minY = 0.05, size = 2.2, color = '#ffffff', tex } = {}) {
  const pos = new Float32Array(count * 3), col = new Float32Array(count * 3), ph = new Float32Array(count);
  const base = new THREE.Color(color);
  for (let i = 0; i < count; i++) {
    const th = rnd() * Math.PI * 2, y = minY + rnd() * (1 - minY), r = Math.sqrt(1 - y * y);
    pos.set([Math.cos(th) * r * radius, y * radius, -Math.abs(Math.sin(th)) * r * radius - 20], i * 3);
    ph[i] = rnd() * 6.28;
    const b = 0.4 + rnd() * 0.6;
    col.set([base.r * b, base.g * b, base.b * b], i * 3);
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  g.setAttribute('color', new THREE.BufferAttribute(col, 3));
  const mat = new THREE.PointsMaterial({
    size, map: tex, vertexColors: true, transparent: true, depthWrite: false,
    blending: THREE.AdditiveBlending, sizeAttenuation: false, fog: false,
  });
  const points = new THREE.Points(g, mat);
  const orig = col.slice();
  return {
    points,
    update(time) {
      for (let i = 0; i < count; i++) {
        const k = 0.65 + 0.35 * Math.sin(time * 3 + ph[i]);
        col[i * 3] = orig[i * 3] * k; col[i * 3 + 1] = orig[i * 3 + 1] * k; col[i * 3 + 2] = orig[i * 3 + 2] * k;
      }
      g.attributes.color.needsUpdate = true;
    },
  };
}

/**
 * Kolam partikel aditif tunggal (Points) untuk ledakan, percikan, salju, koin.
 * Tiap partikel: posisi, kecepatan, umur, warna. Menggunakan gravitasi dan drag.
 */
export function particlePool(THREE, capacity, { tex, size = 0.5, gravity = -6, drag = 0.6, additive = true, sizeAttenuation = true } = {}) {
  const pos = new Float32Array(capacity * 3), vel = new Float32Array(capacity * 3);
  const col = new Float32Array(capacity * 3), base = new Float32Array(capacity * 3);
  const life = new Float32Array(capacity), max = new Float32Array(capacity);
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  g.setAttribute('color', new THREE.BufferAttribute(col, 3));
  const mat = new THREE.PointsMaterial({
    size, map: tex, vertexColors: true, transparent: true, depthWrite: false, fog: false,
    blending: additive ? THREE.AdditiveBlending : THREE.NormalBlending, sizeAttenuation,
  });
  const points = new THREE.Points(g, mat);
  points.frustumCulled = false;
  let cursor = 0;
  const api = {
    points, capacity,
    emit(x, y, z, vx, vy, vz, color, ttl) {
      const i = cursor; cursor = (cursor + 1) % capacity;
      pos[i * 3] = x; pos[i * 3 + 1] = y; pos[i * 3 + 2] = z;
      vel[i * 3] = vx; vel[i * 3 + 1] = vy; vel[i * 3 + 2] = vz;
      base[i * 3] = color.r; base[i * 3 + 1] = color.g; base[i * 3 + 2] = color.b;
      life[i] = ttl; max[i] = ttl;
    },
    /** Ledakan bola. */
    burst(x, y, z, color, n, speed, ttl, rnd) {
      for (let k = 0; k < n; k++) {
        const u = rnd() * 2 - 1, th = rnd() * 6.283, r = Math.sqrt(1 - u * u), s = speed * (0.72 + rnd() * 0.28);
        api.emit(x, y, z, Math.cos(th) * r * s, u * s, Math.sin(th) * r * s, color, ttl * (0.7 + rnd() * 0.5));
      }
    },
    update(dt) {
      const dk = Math.exp(-drag * dt);
      for (let i = 0; i < capacity; i++) {
        if (life[i] <= 0) { col[i * 3] = col[i * 3 + 1] = col[i * 3 + 2] = 0; continue; }
        life[i] -= dt;
        vel[i * 3] *= dk; vel[i * 3 + 1] = vel[i * 3 + 1] * dk + gravity * dt; vel[i * 3 + 2] *= dk;
        pos[i * 3] += vel[i * 3] * dt; pos[i * 3 + 1] += vel[i * 3 + 1] * dt; pos[i * 3 + 2] += vel[i * 3 + 2] * dt;
        const f = Math.max(0, life[i] / max[i]), k = f * f;
        col[i * 3] = base[i * 3] * k; col[i * 3 + 1] = base[i * 3 + 1] * k; col[i * 3 + 2] = base[i * 3 + 2] * k;
      }
      g.attributes.position.needsUpdate = true;
      g.attributes.color.needsUpdate = true;
    },
  };
  return api;
}

/** Bulatan lembut berwarna (sprite aditif) untuk halo cahaya. */
export function glowSprite(THREE, tex, color, scale, opacity = 1) {
  const s = new THREE.Sprite(new THREE.SpriteMaterial({
    map: tex, color, transparent: true, opacity, depthWrite: false, blending: THREE.AdditiveBlending, fog: false,
  }));
  s.scale.set(scale, scale, 1);
  return s;
}

/** Bentuk bulan sabit (THREE.Shape), dipakai crescent. */
export function crescentShape(THREE, R = 1, offset = 0.42, inner = 0.82) {
  const s = new THREE.Shape();
  const a0 = Math.acos((R * R + offset * offset - inner * inner * R * R) / (2 * R * offset)) || 1;
  // lengkung luar penuh, lalu lengkung dalam (lingkaran lebih kecil bergeser)
  s.absarc(0, 0, R, a0, Math.PI * 2 - a0, false);
  const b = Math.atan2(Math.sin(a0) * R, Math.cos(a0) * R - offset);
  s.absarc(offset, 0, R * inner, Math.PI * 2 - b, b, true);
  return s;
}

/** Bebaskan semua yang ada di grup/scene: geometri, material, texture. */
export function disposeTree(root) {
  const seen = new Set();
  const killMat = (m) => {
    if (!m || seen.has(m)) return; seen.add(m);
    for (const k in m) { const v = m[k]; if (v && v.isTexture && !seen.has(v)) { seen.add(v); v.dispose(); } }
    m.dispose();
  };
  root.traverse((o) => {
    if (o.geometry && !seen.has(o.geometry)) { seen.add(o.geometry); o.geometry.dispose(); }
    if (o.material) (Array.isArray(o.material) ? o.material : [o.material]).forEach(killMat);
    if (o.isInstancedMesh && o.dispose) o.dispose();
  });
}

/** Lantera/benda gantung: tali + ayunan sederhana. Mengembalikan grup pivot. */
export function swayPivot(THREE, x, y, z) {
  const p = new THREE.Group();
  p.position.set(x, y, z);
  return p;
}

/** Siluet bangunan acak: satu mesh gabungan, jendela menyala lewat emissiveMap. */
export function skyline(THREE, rnd, { count = 40, spread = 120, z = -60, minH = 6, maxH = 34, color = '#05070d', windowColor = '#ffd98a', lit = 0.3, y0 = -2 } = {}) {
  const tex = canvasTex(THREE, 64, 128, (g, w, h) => {
    g.fillStyle = '#000'; g.fillRect(0, 0, w, h);
    for (let y = 8; y < h - 6; y += 16) for (let x = 8; x < w - 8; x += 16) {
      if (rnd() < lit) { g.fillStyle = windowColor; g.fillRect(x, y, 8, 9); }
    }
  });
  tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
  const pos = [], nor = [], uvs = [], idx = [];
  let x = -spread / 2, base = 0;
  for (let i = 0; i < count && x < spread / 2; i++) {
    const w = 3 + rnd() * 6, h = minH + rnd() * (maxH - minH), d = 4 + rnd() * 4;
    const g = new THREE.BoxGeometry(w, h, d);
    const p = g.attributes.position, n = g.attributes.normal, u = g.attributes.uv, ix = g.index;
    const cx = x + w / 2, cz = z - rnd() * 14;
    for (let k = 0; k < p.count; k++) {
      pos.push(p.getX(k) + cx, p.getY(k) + h / 2 + y0, p.getZ(k) + cz);
      nor.push(n.getX(k), n.getY(k), n.getZ(k));
      uvs.push(u.getX(k) * Math.max(1, Math.round(w / 3)), u.getY(k) * Math.max(1, Math.round(h / 6)));
    }
    for (let k = 0; k < ix.count; k++) idx.push(ix.getX(k) + base);
    base += p.count;
    g.dispose();
    x += w * (0.7 + rnd() * 0.5);
  }
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
  geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
  geo.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));
  geo.setIndex(idx);
  const mat = new THREE.MeshStandardMaterial({ color, emissiveMap: tex, emissive: new THREE.Color('#ffffff'), emissiveIntensity: 0.75, roughness: 1 });
  return new THREE.Mesh(geo, mat);
}
