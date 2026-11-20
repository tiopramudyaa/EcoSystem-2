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

// ===== Tambahan ronde 3 (append-only; dipakai sleigh, fireworks, crescent) =====

/** Titik pada bola langit dari azimut (rad, kanan positif, 0 = lurus ke -z) dan elevasi. */
export function skyDir(THREE, az, el, r, out) {
  const v = out || new THREE.Vector3();
  return v.set(Math.sin(az) * Math.cos(el) * r, Math.sin(el) * r, -Math.cos(az) * Math.cos(el) * r);
}

/** Gabungkan banyak geometri jadi satu. parts: [{ geo, m?: Matrix4, color?, uvScale?: [u, v] }]. Atribut: position, normal, color, uv. */
export function mergeParts(THREE, parts) {
  let n = 0, ni = 0;
  for (const p of parts) { const c = p.geo.attributes.position.count; n += c; ni += p.geo.index ? p.geo.index.count : c; }
  const pos = new Float32Array(n * 3), nor = new Float32Array(n * 3), col = new Float32Array(n * 3), uv = new Float32Array(n * 2), idx = new Uint32Array(ni);
  const v = new THREE.Vector3(), nm = new THREE.Matrix3(), c = new THREE.Color(), I = new THREE.Matrix4();
  let o = 0, oi = 0;
  for (const { geo, m, color = '#ffffff', uvScale } of parts) {
    const P = geo.attributes.position, N = geo.attributes.normal, U = geo.attributes.uv, M = m || I;
    nm.getNormalMatrix(M); c.set(color);
    for (let i = 0; i < P.count; i++) {
      const j = o + i;
      v.fromBufferAttribute(P, i).applyMatrix4(M); pos[j * 3] = v.x; pos[j * 3 + 1] = v.y; pos[j * 3 + 2] = v.z;
      if (N) { v.fromBufferAttribute(N, i).applyMatrix3(nm).normalize(); nor[j * 3] = v.x; nor[j * 3 + 1] = v.y; nor[j * 3 + 2] = v.z; }
      col[j * 3] = c.r; col[j * 3 + 1] = c.g; col[j * 3 + 2] = c.b;
      if (U) { uv[j * 2] = U.getX(i) * (uvScale ? uvScale[0] : 1); uv[j * 2 + 1] = U.getY(i) * (uvScale ? uvScale[1] : 1); }
    }
    if (geo.index) for (let i = 0; i < geo.index.count; i++) idx[oi++] = geo.index.getX(i) + o;
    else for (let i = 0; i < P.count; i++) idx[oi++] = o + i;
    o += P.count;
  }
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  g.setAttribute('normal', new THREE.BufferAttribute(nor, 3));
  g.setAttribute('color', new THREE.BufferAttribute(col, 3));
  g.setAttribute('uv', new THREE.BufferAttribute(uv, 2));
  g.setIndex(new THREE.BufferAttribute(idx, 1));
  return g;
}

/**
 * Langit terlukis (gradasi + pita bimasakti) pada bola parsial yang hanya menutup sudut pandang depan,
 * sehingga 1024 px cukup tajam. stops: [[0..1 (horizon ke zenit), warna], ...].
 * paint(g, m) opsional; m = { W, H, k, x(az), y(el) }.
 */
export function paintedSky(THREE, rnd, { stops, milky = null, paint = null, span = 2.3, upTop = 1.0, below = 0.15, radius = 500 } = {}) {
  const W = 1024, H = 512, tot = upTop + below;
  const m = { W, H, k: H / tot, x: (az) => W * (0.5 - az / span), y: (el) => H * (upTop - el) / tot };
  const tex = canvasTex(THREE, W, H, (g) => {
    const css = (c) => (typeof c === 'string' ? c : '#' + c.getHexString());
    g.fillStyle = css(stops[0][1]); g.fillRect(0, 0, W, H);
    const gr = g.createLinearGradient(0, 0, 0, m.y(0));
    stops.forEach(([p, col]) => gr.addColorStop(1 - p, css(col)));
    g.fillStyle = gr; g.fillRect(0, 0, W, m.y(0) + 1);
    if (milky) {
      const { az0, el0, az1, el1, width = 70, color = '255,244,225', alpha = 0.09 } = milky;
      for (let i = 0; i < 170; i++) {
        const s = rnd(), jx = (rnd() - 0.5) * width * 1.3, jy = (rnd() - 0.5) * width * 0.9;
        const x = m.x(az0 + (az1 - az0) * s) + jx, y = m.y(el0 + (el1 - el0) * s) + jy, r = width * (0.25 + rnd() * 0.5);
        const rg = g.createRadialGradient(x, y, 0, x, y, r);
        const a = alpha * (0.4 + rnd() * 0.9) * (1 - Math.abs(jx / (width * 0.65)) * 0.5);
        rg.addColorStop(0, `rgba(${color},${a})`); rg.addColorStop(1, `rgba(${color},0)`);
        g.fillStyle = rg; g.fillRect(x - r, y - r, r * 2, r * 2);
      }
      for (let i = 0; i < 26; i++) {
        const s = rnd(), x = m.x(az0 + (az1 - az0) * s) + (rnd() - 0.5) * width * 0.5, y = m.y(el0 + (el1 - el0) * s) + (rnd() - 0.5) * width * 0.4, r = 10 + rnd() * 22;
        const rg = g.createRadialGradient(x, y, 0, x, y, r);
        rg.addColorStop(0, 'rgba(5,8,20,0.1)'); rg.addColorStop(1, 'rgba(5,8,20,0)');
        g.fillStyle = rg; g.fillRect(x - r, y - r, r * 2, r * 2);
      }
    }
    if (paint) paint(g, m);
  });
  const geo = new THREE.SphereGeometry(radius, 48, 24, Math.PI * 1.5 - span / 2, span, Math.PI / 2 - upTop, tot);
  const mesh = new THREE.Mesh(geo, new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false, depthWrite: false }));
  mesh.renderOrder = -10;
  mesh.frustumCulled = false;
  return mesh;
}

/** Bintang padat hanya di sudut pandang depan: dua Points (kecil dan besar), berkelip. */
export function starField(THREE, rnd, count, { span = 2.3, upTop = 1.0, radius = 450, color = '#ffffff', tex, size = 1.7, bigShare = 0.1, minEl = 0.0 } = {}) {
  const tints = [new THREE.Color(color), new THREE.Color('#ffe2b4'), new THREE.Color('#b4ccff')];
  const mk = (n, sz) => {
    const pos = new Float32Array(n * 3), col = new Float32Array(n * 3), ph = new Float32Array(n), orig = new Float32Array(n * 3), v = new THREE.Vector3();
    for (let i = 0; i < n; i++) {
      const az = (rnd() - 0.5) * span * 0.96, el = minEl + Math.pow(rnd(), 0.8) * (upTop - minEl);
      skyDir(THREE, az, el, radius, v); pos[i * 3] = v.x; pos[i * 3 + 1] = v.y; pos[i * 3 + 2] = v.z;
      const t = tints[rnd() < 0.7 ? 0 : 1 + (rnd() < 0.5 ? 0 : 1)], b = 0.22 + Math.pow(rnd(), 2.2) * 0.9;
      orig[i * 3] = col[i * 3] = t.r * b; orig[i * 3 + 1] = col[i * 3 + 1] = t.g * b; orig[i * 3 + 2] = col[i * 3 + 2] = t.b * b;
      ph[i] = rnd() * 6.28;
    }
    const g = new THREE.BufferGeometry();
    g.setAttribute('position', new THREE.BufferAttribute(pos, 3)); g.setAttribute('color', new THREE.BufferAttribute(col, 3));
    const pts = new THREE.Points(g, new THREE.PointsMaterial({ size: sz, map: tex, vertexColors: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, sizeAttenuation: false, fog: false }));
    pts.frustumCulled = false;
    return { pts, g, col, orig, ph, n };
  };
  const nb = Math.round(count * bigShare), A = mk(count - nb, size), B = mk(nb, size * 2.2);
  const group = new THREE.Group(); group.add(A.pts, B.pts);
  return {
    group,
    update(time) {
      for (const S of [A, B]) {
        for (let i = 0; i < S.n; i++) {
          const k = 0.6 + 0.4 * Math.sin(time * (2.4 + (i % 5) * 0.5) + S.ph[i]);
          S.col[i * 3] = S.orig[i * 3] * k; S.col[i * 3 + 1] = S.orig[i * 3 + 1] * k; S.col[i * 3 + 2] = S.orig[i * 3 + 2] * k;
        }
        S.g.attributes.color.needsUpdate = true;
      }
    },
  };
}

/** Texture permukaan bulan: maria, kawah, bintik. 1024x512 (equirect). */
export function moonTexture(THREE, rnd, { base = '#d8d3c4', mare = '#5f6068' } = {}) {
  return canvasTex(THREE, 1024, 512, (g, W, H) => {
    g.fillStyle = base; g.fillRect(0, 0, W, H);
    const blob = (x, y, r, col, a) => {
      for (const dx of [-W, 0, W]) {
        const rg = g.createRadialGradient(x + dx, y, 0, x + dx, y, r);
        rg.addColorStop(0, col.replace('A', a)); rg.addColorStop(0.7, col.replace('A', a * 0.7)); rg.addColorStop(1, col.replace('A', 0));
        g.fillStyle = rg; g.fillRect(x + dx - r, y - r, r * 2, r * 2);
      }
    };
    const mc = 'rgba(' + new THREE.Color(mare).toArray().map((v) => Math.round(Math.pow(v, 1 / 2.2) * 255)).join(',') + ',A)';
    [[250, 190, 90], [330, 250, 70], [190, 270, 60], [280, 130, 55], [370, 170, 45], [220, 220, 80], [300, 300, 40]].forEach(([x, y, r]) => {
      for (let i = 0; i < 7; i++) blob(x + (rnd() - 0.5) * r * 0.9, y + (rnd() - 0.5) * r * 0.7, r * (0.35 + rnd() * 0.5), mc, 0.2 + rnd() * 0.14);
    });
    for (let i = 0; i < 700; i++) blob(rnd() * W, rnd() * H, 18 + rnd() * 50, mc, 0.03 + rnd() * 0.05);
    for (let i = 0; i < 4200; i++) { g.fillStyle = rnd() < 0.5 ? 'rgba(255,255,255,0.07)' : 'rgba(40,40,50,0.08)'; g.fillRect(rnd() * W, rnd() * H, 1 + rnd() * 2, 1 + rnd() * 2); }
    for (let i = 0; i < 230; i++) {
      const x = rnd() * W, y = 30 + rnd() * (H - 60), r = 2 + Math.pow(rnd(), 3) * 26;
      const rg = g.createRadialGradient(x, y, r * 0.2, x, y, r);
      rg.addColorStop(0, 'rgba(60,60,70,0.30)'); rg.addColorStop(0.85, 'rgba(90,90,100,0.16)'); rg.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = rg; g.beginPath(); g.arc(x, y, r, 0, 6.283); g.fill();
      g.strokeStyle = 'rgba(255,255,250,0.30)'; g.lineWidth = Math.max(1, r * 0.12); g.beginPath(); g.arc(x, y, r, 3.6, 5.3); g.stroke();
    }
    for (let i = 0; i < 9; i++) {
      const x = rnd() * W, y = 60 + rnd() * (H - 120);
      for (let s = 0; s < 14; s++) {
        const a = rnd() * 6.283, l = 20 + rnd() * 70;
        g.strokeStyle = 'rgba(255,255,255,0.06)'; g.lineWidth = 1; g.beginPath(); g.moveTo(x, y); g.lineTo(x + Math.cos(a) * l, y + Math.sin(a) * l * 0.8); g.stroke();
      }
      blob(x, y, 9, 'rgba(255,255,255,A)', 0.5);
    }
  });
}

/**
 * Bulan berfase: bola berpencahayaan Lambert dengan terminator lembut, tekstur, bayangan bumi redup.
 * sunDir = arah cahaya (dunia). Mengembalikan mesh (ShaderMaterial, tanpa kabut).
 */
export function phasedMoon(THREE, map, { radius = 10, sunDir, tint = '#fff4dc', earth = 0.05, gain = 1.25, uOff = 0 } = {}) {
  const mat = new THREE.ShaderMaterial({
    uniforms: { map: { value: map }, sunDir: { value: sunDir.clone().normalize() }, tint: { value: new THREE.Color(tint) }, earth: { value: earth }, gain: { value: gain }, uOff: { value: uOff } },
    vertexShader: 'varying vec3 vN; varying vec2 vUv; varying vec3 vV; void main(){ vUv = uv; vN = normalize(mat3(modelMatrix) * normal); vec4 wp = modelMatrix * vec4(position,1.0); vV = normalize(cameraPosition - wp.xyz); gl_Position = projectionMatrix * viewMatrix * wp; }',
    fragmentShader: 'uniform sampler2D map; uniform vec3 sunDir; uniform vec3 tint; uniform float earth; uniform float gain; uniform float uOff; varying vec3 vN; varying vec2 vUv; varying vec3 vV;\n'
      + 'void main(){ vec3 n = normalize(vN); float d = dot(n, normalize(sunDir)); float lit = smoothstep(-0.03, 0.16, d); float lam = pow(max(d, 0.0), 0.55);\n'
      + ' vec3 a = texture2D(map, vec2(vUv.x + uOff, vUv.y)).rgb; float fr = pow(max(dot(n, normalize(vV)), 0.0), 0.35);\n'
      + ' vec3 c = a * tint * (lit * (0.18 + 0.95 * lam) * gain + earth * vec3(0.55, 0.65, 1.0)) * mix(0.7, 1.0, fr);\n'
      + ' gl_FragColor = vec4(c, clamp(lit + earth * 2.5, 0.0, 1.0));\n#include <tonemapping_fragment>\n#include <colorspace_fragment>\n}',
  });
  mat.transparent = true; mat.depthWrite = false; // sisi gelap tembus ke langit
  const mesh = new THREE.Mesh(new THREE.SphereGeometry(radius, 64, 48), mat);
  mesh.frustumCulled = false;
  return mesh;
}

/** Sinar cahaya: bilah tipis aditif memancar dari titik asal (satu draw call). Mengembalikan { mesh, tex }. */
export function godRays(THREE, rnd, { n = 8, len = 140, width = 12, color = '#cfdcff', opacity = 0.05 } = {}) {
  const tex = canvasTex(THREE, 128, 32, (g, w, h) => {
    const gx = g.createLinearGradient(0, 0, w, 0); gx.addColorStop(0, 'rgba(255,255,255,0.9)'); gx.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = gx; g.fillRect(0, 0, w, h);
    g.globalCompositeOperation = 'destination-in';
    const gy = g.createLinearGradient(0, 0, 0, h); gy.addColorStop(0, 'rgba(0,0,0,0)'); gy.addColorStop(0.5, 'rgba(0,0,0,1)'); gy.addColorStop(1, 'rgba(0,0,0,0)');
    g.fillStyle = gy; g.fillRect(0, 0, w, h);
  });
  const parts = [];
  for (let i = 0; i < n; i++) {
    const a = (i / n) * 6.283 + (rnd() - 0.5) * 0.5, L = len * (0.55 + rnd() * 0.6), Wd = width * (0.5 + rnd());
    const m = new THREE.Matrix4().makeRotationZ(a).multiply(new THREE.Matrix4().makeTranslation(L / 2, 0, 0));
    parts.push({ geo: new THREE.PlaneGeometry(L, Wd), m });
  }
  const geo = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose());
  const mesh = new THREE.Mesh(geo, new THREE.MeshBasicMaterial({ map: tex, color, transparent: true, opacity, depthWrite: false, blending: THREE.AdditiveBlending, fog: false }));
  mesh.frustumCulled = false;
  return { mesh, tex };
}

/** Texture awan lembut 256x128 (alpha putih), untuk sprite/bidang awan. */
export function cloudTexture(THREE, rnd) {
  return canvasTex(THREE, 256, 128, (g, w, h) => {
    for (let i = 0; i < 38; i++) {
      const t = rnd(), x = w * (0.12 + 0.76 * t), y = h * (0.58 - Math.sin(t * Math.PI) * 0.16 + (rnd() - 0.5) * 0.22), r = 14 + rnd() * 30 * (0.5 + Math.sin(t * Math.PI));
      const rg = g.createRadialGradient(x, y, 0, x, y, r);
      rg.addColorStop(0, `rgba(255,255,255,${0.16 + rnd() * 0.16})`); rg.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = rg; g.fillRect(x - r, y - r, r * 2, r * 2);
    }
  });
}
