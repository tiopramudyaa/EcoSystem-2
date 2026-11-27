// Sistem percikan GPU untuk kembang api. Satu draw call: quad instansi yang diregangkan searah kecepatan
// (goresan dengan ekor memudar) atau bulatan cahaya lembut. Gerak dihitung analitik di vertex shader
// (drag linear + gravitasi), jadi CPU tidak menyentuh partikel per frame; hanya uniform waktu.
// Tidak ada yang berjalan saat import.

const VERT = `
uniform float uTime; uniform vec2 uRes; uniform float uPx;
attribute vec2 corner; attribute vec3 aP0; attribute vec3 aV0; attribute vec3 aCol; attribute vec4 aT; attribute vec4 aU;
varying vec4 vCol; varying vec2 vUv; varying float vKind;
vec3 posAt(float a) {
  float k = aT.z;
  float e = (1.0 - exp(-k * a)) / k;
  vec3 p = aP0 + aV0 * e;
  p.y += (aU.x / k) * (a - e);
  return p;
}
void main() {
  float age = uTime - aT.x;
  float ttl = aT.y;
  if (age < 0.0 || age > ttl) { gl_Position = vec4(2.0, 2.0, 2.0, 1.0); vCol = vec4(0.0); vUv = vec2(0.0); vKind = 0.0; return; }
  float f = age / ttl;
  vec3 head = posAt(age);
  mat4 vp = projectionMatrix * viewMatrix * modelMatrix;
  vec4 hc = vp * vec4(head, 1.0);
  if (hc.w < 0.5) { gl_Position = vec4(2.0, 2.0, 2.0, 1.0); vCol = vec4(0.0); vUv = vec2(0.0); vKind = 0.0; return; }
  float kind = aU.z;
  vec2 hs = hc.xy / hc.w * uRes * 0.5;
  vec2 pxPos; vec3 col; float al;
  if (kind > 0.5 && kind < 1.5) {
    // bulatan cahaya: tumbuh, memudar
    float rad = aU.y * (1.0 + f * 0.9) * projectionMatrix[1][1] * uRes.y * 0.5 / hc.w;
    pxPos = hs + vec2(corner.x * 2.0 - 1.0, corner.y) * rad;
    vUv = vec2(corner.x * 2.0 - 1.0, corner.y);
    float rise = smoothstep(0.0, 0.08, f);
    al = rise * (1.0 - f) * (1.0 - f);
    col = aCol;
  } else {
    vec3 tail = posAt(max(age - aT.w, 0.0));
    vec4 tc = vp * vec4(tail, 1.0);
    vec2 ts = tc.xy / max(tc.w, 0.5) * uRes * 0.5;
    vec2 d = hs - ts; float len = length(d);
    vec2 dir = len > 0.001 ? d / len : vec2(0.0, 1.0);
    float L = max(len, 2.5 * uPx);
    vec2 tsE = hs - dir * L;
    vec2 perp = vec2(-dir.y, dir.x);
    float w = aU.y * uPx;
    pxPos = mix(tsE, hs, corner.x) + perp * corner.y * w * (0.3 + 0.7 * corner.x);
    vUv = vec2(corner.x, corner.y);
    float heat = 1.0 - smoothstep(0.0, 0.2, f);
    col = mix(aCol, vec3(1.6, 1.5, 1.3), heat * 0.55);
    al = 1.0 - smoothstep(0.45, 1.0, f);
    if (kind > 1.5) { // glitter: berkelip
      float fl = step(0.45, fract(age * 14.0 + aU.w * 7.31));
      al *= 0.25 + 0.75 * fl;
    }
  }
  vCol = vec4(col, al);
  vKind = kind;
  gl_Position = vec4(pxPos / (uRes * 0.5) * hc.w, hc.z, hc.w);
}`;

const FRAG = `
uniform float uGain;
varying vec4 vCol; varying vec2 vUv; varying float vKind;
void main() {
  float a;
  if (vKind > 0.5 && vKind < 1.5) {
    float r = length(vUv);
    a = pow(max(1.0 - r, 0.0), 3.2) * vCol.a;
    gl_FragColor = vec4(vCol.rgb * uGain, a);
  } else {
    float across = 1.0 - pow(abs(vUv.y), 1.6);
    float along = pow(vUv.x, 2.4);
    float core = smoothstep(0.82, 1.0, vUv.x);
    a = across * (along * 0.9 + core * 0.6) * vCol.a;
    gl_FragColor = vec4(vCol.rgb * uGain * (1.0 + core * 0.6), a);
  }
  #include <tonemapping_fragment>
  #include <colorspace_fragment>
}`;

export function createSparks(THREE, { capacity = 4000, water = -2.2, width = 640, height = 360, pixelRatio = 1 } = {}) {
  const geo = new THREE.InstancedBufferGeometry();
  geo.setAttribute('corner', new THREE.BufferAttribute(new Float32Array([0, -1, 1, -1, 0, 1, 1, 1]), 2));
  geo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(12), 3)); // tidak dipakai, agar bounding aman
  geo.setIndex([0, 1, 2, 2, 1, 3]);
  const P0 = new Float32Array(capacity * 3), V0 = new Float32Array(capacity * 3), COL = new Float32Array(capacity * 3);
  const T = new Float32Array(capacity * 4), U = new Float32Array(capacity * 4);
  for (let i = 0; i < capacity; i++) T[i * 4] = 1e9;
  const attrs = {
    aP0: new THREE.InstancedBufferAttribute(P0, 3), aV0: new THREE.InstancedBufferAttribute(V0, 3), aCol: new THREE.InstancedBufferAttribute(COL, 3),
    aT: new THREE.InstancedBufferAttribute(T, 4), aU: new THREE.InstancedBufferAttribute(U, 4),
  };
  for (const k in attrs) { attrs[k].setUsage(THREE.DynamicDrawUsage); geo.setAttribute(k, attrs[k]); }
  geo.instanceCount = 0;
  const uniforms = { uTime: { value: 0 }, uRes: { value: new THREE.Vector2(width, height) }, uPx: { value: pixelRatio }, uGain: { value: 1 } };
  const mat = new THREE.ShaderMaterial({ uniforms, vertexShader: VERT, fragmentShader: FRAG, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide });
  const mesh = new THREE.Mesh(geo, mat);
  mesh.frustumCulled = false;
  // salinan terbalik untuk pantulan di air (bahan sendiri, geometri dibagi)
  const mMat = new THREE.ShaderMaterial({ uniforms: { uTime: uniforms.uTime, uRes: uniforms.uRes, uPx: uniforms.uPx, uGain: { value: 0.28 } }, vertexShader: VERT, fragmentShader: FRAG, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide });
  const mirror = new THREE.Mesh(geo, mMat);
  mirror.scale.y = -1; mirror.position.y = 2 * water; mirror.frustumCulled = false;

  let cursor = 0, dirty = false, used = 0;
  const api = {
    mesh, mirror, capacity,
    /** Posisi analitik (sama dengan shader) untuk menjadwalkan anak percikan. */
    posAt(out, x, y, z, vx, vy, vz, k, g, a) {
      const e = (1 - Math.exp(-k * a)) / k;
      out[0] = x + vx * e; out[1] = y + vy * e + (g / k) * (a - e); out[2] = z + vz * e;
      return out;
    },
    velAt(out, vx, vy, vz, k, g, a) {
      const ek = Math.exp(-k * a);
      out[0] = vx * ek; out[1] = (vy - g / k) * ek + g / k; out[2] = vz * ek;
      return out;
    },
    /** kind: 0 goresan, 1 bulatan cahaya, 2 kerlip. wpx: lebar goresan (px) atau radius dunia (bulatan). */
    spawn(x, y, z, vx, vy, vz, r, g, b, birth, ttl, drag, trail, grav, wpx, kind, seed) {
      const i = cursor; cursor = (cursor + 1) % capacity;
      P0[i * 3] = x; P0[i * 3 + 1] = y; P0[i * 3 + 2] = z;
      V0[i * 3] = vx; V0[i * 3 + 1] = vy; V0[i * 3 + 2] = vz;
      COL[i * 3] = r; COL[i * 3 + 1] = g; COL[i * 3 + 2] = b;
      T[i * 4] = birth; T[i * 4 + 1] = ttl; T[i * 4 + 2] = drag; T[i * 4 + 3] = trail;
      U[i * 4] = grav; U[i * 4 + 1] = wpx; U[i * 4 + 2] = kind; U[i * 4 + 3] = seed || 0;
      dirty = true; if (i + 1 > used) { used = i + 1; geo.instanceCount = used; }
    },
    flush() { if (!dirty) return; dirty = false; for (const k in attrs) attrs[k].needsUpdate = true; },
    setTime(t) { uniforms.uTime.value = t; },
    setSize(w, h) { uniforms.uRes.value.set(w, h); },
    dispose() { geo.dispose(); mat.dispose(); mMat.dispose(); },
  };
  return api;
}
