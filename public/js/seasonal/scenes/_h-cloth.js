// Kain bendera untuk adegan flag: tekstur tenun, material gelombang berjalan di GPU (normal lipatan analitik,
// tembus cahaya dari belakang), dan material ayun untuk rumbai kecil. Tidak ada yang berjalan saat import.

export const FLAG_RED = '#E70011'; // Merah resmi bendera RI (Pantone 186 C, setara sRGB E70011)
export const FLAG_WHITE = '#FFFFFF';

/** Tekstur kain 3:2 (lebar:tinggi): merah atas, putih bawah (2:3 bendera), serat tenun halus, jahitan tepi. */
export function flagTexture(THREE, rnd, W = 768, H = 512) {
  const c = document.createElement('canvas'); c.width = W; c.height = H;
  const g = c.getContext('2d');
  g.fillStyle = FLAG_RED; g.fillRect(0, 0, W, H / 2);
  g.fillStyle = FLAG_WHITE; g.fillRect(0, H / 2, W, H / 2);
  // tenun: garis lungsin dan pakan sangat halus
  for (let x = 0; x < W; x += 2) { g.fillStyle = (x % 4 ? 'rgba(0,0,0,0.028)' : 'rgba(255,255,255,0.05)'); g.fillRect(x, 0, 1, H); }
  for (let y = 0; y < H; y += 2) { g.fillStyle = (y % 4 ? 'rgba(0,0,0,0.028)' : 'rgba(255,255,255,0.05)'); g.fillRect(0, y, W, 1); }
  // bercak benang tak rata
  for (let i = 0; i < 700; i++) {
    const x = rnd() * W, y = rnd() * H, l = 6 + rnd() * 30, h = rnd() < 0.5;
    g.fillStyle = rnd() < 0.5 ? 'rgba(0,0,0,0.035)' : 'rgba(255,255,255,0.06)';
    if (h) g.fillRect(x, y, l, 1); else g.fillRect(x, y, 1, l);
  }
  // naungan merah sedikit lebih dalam di tengah panel supaya tidak datar
  const gr = g.createLinearGradient(0, 0, 0, H / 2); gr.addColorStop(0, 'rgba(0,0,0,0)'); gr.addColorStop(1, 'rgba(60,0,0,0.05)');
  g.fillStyle = gr; g.fillRect(0, 0, W, H / 2);
  // sisi tiang: pita tepi dengan jahitan dan dua lubang tali
  g.fillStyle = 'rgba(0,0,0,0.07)'; g.fillRect(0, 0, 14, H);
  g.fillStyle = 'rgba(255,255,255,0.35)'; g.fillRect(14, 0, 1, H);
  g.strokeStyle = 'rgba(40,20,20,0.35)'; g.setLineDash([4, 3]); g.lineWidth = 1; g.strokeRect(5, 4, W - 10, H - 8); g.setLineDash([]);
  [H * 0.12, H * 0.88].forEach((y) => { g.fillStyle = '#b79a52'; g.beginPath(); g.arc(7, y, 3.2, 0, 6.283); g.fill(); g.fillStyle = 'rgba(0,0,0,0.5)'; g.beginPath(); g.arc(7, y, 1.5, 0, 6.283); g.fill(); });
  const t = new THREE.CanvasTexture(c);
  t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 4; t.generateMipmaps = true; t.minFilter = THREE.LinearMipmapLinearFilter;
  return t;
}

const WAVE = `
uniform float uT; uniform float uAmp; uniform vec2 uSize; uniform float uTaper; uniform float uDroop;
vec3 clothP(vec2 p) {
  float u = clamp(p.x / uSize.x, 0.0, 1.0), v = p.y / uSize.y;
  float k = u * (0.35 + 0.65 * u);
  float A = uAmp * uSize.x * (0.02 + 0.14 * k);
  float ph = u * 8.6 - uT * 4.4 + v * 1.9;
  float z = A * sin(ph);
  z += A * 0.5 * sin(u * 12.5 - uT * 6.6 + v * 3.6 + 1.3);
  z += A * 0.2 * sin(u * 24.0 - uT * 9.5 - v * 6.0 + 0.4) * u;
  z += A * 0.55 * sin(uT * 0.9 + v * 1.2 + u * 1.6) * u;
  float x = p.x - A * 0.2 * (1.0 - cos(ph)) * u;
  float y = p.y * (1.0 - uTaper * u) - uDroop * u * u * uSize.y * (0.10 + 0.04 * sin(uT * 1.1)) + A * 0.22 * sin(u * 5.0 - uT * 4.0 + 1.0) * u;
  return vec3(x, y, z);
}`;

/**
 * Bendera / rumbai kain. size [w, h]; segmen X, Y. opts: map, taper (0..1), droop, tone (warna dasar bila tanpa map).
 * Mengembalikan { mesh, geometry, material, uni }. Pangkal kain di x=0 (tiang), tepi atas di y=0, tergantung ke -y.
 */
export function createCloth(THREE, { map = null, size = [6, 4], seg = [56, 36], taper = 0, droop = 1, color = '#ffffff', sun, trans = 0.5 } = {}) {
  const geo = new THREE.PlaneGeometry(size[0], size[1], seg[0], seg[1]);
  geo.translate(size[0] / 2, -size[1] / 2, 0);
  const uni = { uT: { value: 0 }, uAmp: { value: 1 }, uSize: { value: new THREE.Vector2(size[0], size[1]) }, uTaper: { value: taper }, uDroop: { value: droop }, uSunV: { value: new THREE.Vector3(0, 0, -1) }, uTrans: { value: trans }, uSunCol: { value: new THREE.Color('#ffc58a') } };
  const mat = new THREE.MeshStandardMaterial({ map, color, side: THREE.DoubleSide, roughness: 0.82, metalness: 0, toneMapped: false });
  mat.onBeforeCompile = (sh) => {
    Object.assign(sh.uniforms, uni);
    sh.vertexShader = sh.vertexShader
      .replace('#include <common>', '#include <common>\n' + WAVE)
      .replace('#include <beginnormal_vertex>', `vec3 cP0 = clothP(position.xy);
vec3 cPx = clothP(position.xy + vec2(0.05, 0.0));
vec3 cPy = clothP(position.xy + vec2(0.0, -0.05));
vec3 objectNormal = normalize(cross(cPx - cP0, cPy - cP0));
#ifdef USE_TANGENT
vec3 objectTangent = vec3(tangent.xyz);
#endif`)
      .replace('#include <begin_vertex>', 'vec3 transformed = cP0;');
    sh.fragmentShader = sh.fragmentShader
      .replace('#include <common>', '#include <common>\nuniform vec3 uSunV; uniform float uTrans; uniform vec3 uSunCol;')
      .replace('#include <opaque_fragment>', `{
  vec3 N = normal;
  float dS = dot(N, uSunV);
  float front = saturate(dS), back = saturate(-dS);
  float fillD = saturate(dot(N, normalize(vec3(0.55, 0.3, 0.8))));
  float sh = 0.5 + 0.62 * fillD + 0.12 * front;
  vec3 warm = vec3(1.0, 0.93, 0.84);
  float rimE = pow(1.0 - saturate(abs(dot(N, normalize(vViewPosition)))), 2.5);
  outgoingLight = diffuseColor.rgb * (sh + uTrans * back * 0.9 + rimE * 0.18) * mix(vec3(1.0), warm, 0.2 * (front + back)) * 1.0;
}
#include <opaque_fragment>`);
  };
  mat.customProgramCacheKey = () => 'hcloth';
  const mesh = new THREE.Mesh(geo, mat);
  mesh.frustumCulled = false;
  return { mesh, geometry: geo, material: mat, uni };
}

/** Material ayun untuk rumbai kecil (vertex color, atribut aPh dan aHang). */
export function swayMaterial(THREE, uni) {
  const m = new THREE.MeshStandardMaterial({ vertexColors: true, side: THREE.DoubleSide, roughness: 0.85, toneMapped: true });
  m.onBeforeCompile = (sh) => {
    sh.uniforms.uT = uni.uT;
    sh.vertexShader = sh.vertexShader
      .replace('#include <common>', '#include <common>\nattribute float aPh; attribute float aHang; uniform float uT;')
      .replace('#include <begin_vertex>', `#include <begin_vertex>
transformed.z += sin(uT * 1.9 + aPh) * 0.1 * aHang + sin(uT * 3.3 + aPh * 2.0) * 0.03 * aHang;
transformed.x += sin(uT * 1.2 + aPh * 1.7) * 0.05 * aHang;`);
  };
  m.customProgramCacheKey = () => 'hsway';
  return m;
}
