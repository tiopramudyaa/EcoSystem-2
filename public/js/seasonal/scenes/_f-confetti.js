// Confetti dan kilau emas yang jatuh pelan, sepenuhnya digerakkan GPU (satu draw call, nol pekerjaan CPU per frame).
// Tidak ada yang berjalan saat import.

const VERT = `
uniform float uTime; uniform float uAmt; uniform vec3 uCols[5];
attribute vec2 corner; attribute vec4 iA; attribute vec4 iB;
varying vec3 vCol; varying float vA;
void main() {
  float t = uTime;
  float H = 30.0;
  float y = mod(iA.y - iA.w * t, H) - 2.0;
  float ph = iB.x;
  float vis = step(fract(ph * 7.13), uAmt);
  float edge = smoothstep(0.0, 3.0, y + 2.0) * (1.0 - smoothstep(H - 8.0, H - 2.0, y + 2.0));
  vec3 c = vec3(iA.x + sin(t * 0.8 + ph * 6.28) * 1.2, y, iA.z + cos(t * 0.6 + ph * 9.0) * 1.0);
  float ang = t * iB.z + ph * 40.0;
  float ca = cos(ang), sa = sin(ang);
  vec2 q = corner * iB.y;
  vec3 lp = vec3(q.x, q.y * ca, q.y * sa);
  float cy = cos(ang * 0.7), sy = sin(ang * 0.7);
  lp = vec3(lp.x * cy + lp.z * sy, lp.y, -lp.x * sy + lp.z * cy);
  gl_Position = projectionMatrix * viewMatrix * vec4(c + lp, 1.0);
  float glint = 0.25 + 0.75 * pow(abs(ca), 3.0);
  int ci = int(floor(iB.w * 4.999));
  vec3 col = uCols[0];
  if (ci == 1) col = uCols[1]; else if (ci == 2) col = uCols[2]; else if (ci == 3) col = uCols[3]; else if (ci == 4) col = uCols[4];
  vCol = col; vA = glint * vis * edge;
}`;
const FRAG = `
varying vec3 vCol; varying float vA;
void main() {
  gl_FragColor = vec4(vCol * vA, 1.0);
  #include <tonemapping_fragment>
  #include <colorspace_fragment>
}`;

export function createConfetti(THREE, rnd, { count = 260, colors, spread = 34, zRange = [-6, 22], speed = [0.7, 1.7], size = [0.1, 0.22] } = {}) {
  const geo = new THREE.InstancedBufferGeometry();
  geo.setAttribute('corner', new THREE.BufferAttribute(new Float32Array([-0.5, -0.5, 0.5, -0.5, -0.5, 0.5, 0.5, 0.5]), 2));
  geo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(12), 3));
  geo.setIndex([0, 1, 2, 2, 1, 3]);
  const A = new Float32Array(count * 4), B = new Float32Array(count * 4);
  for (let i = 0; i < count; i++) {
    A[i * 4] = (rnd() - 0.5) * 2 * spread; A[i * 4 + 1] = rnd() * 30; A[i * 4 + 2] = zRange[0] + rnd() * (zRange[1] - zRange[0]); A[i * 4 + 3] = speed[0] + rnd() * (speed[1] - speed[0]);
    B[i * 4] = rnd(); B[i * 4 + 1] = size[0] + rnd() * (size[1] - size[0]); B[i * 4 + 2] = 2 + rnd() * 5; B[i * 4 + 3] = rnd();
  }
  geo.setAttribute('iA', new THREE.InstancedBufferAttribute(A, 4)); geo.setAttribute('iB', new THREE.InstancedBufferAttribute(B, 4));
  geo.instanceCount = count;
  const uniforms = { uTime: { value: 0 }, uAmt: { value: 0 }, uCols: { value: colors.map((c) => new THREE.Color(c)) } };
  const mat = new THREE.ShaderMaterial({ uniforms, vertexShader: VERT, fragmentShader: FRAG, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide });
  const mesh = new THREE.Mesh(geo, mat); mesh.frustumCulled = false;
  return { mesh, set(t, amt) { uniforms.uTime.value = t; uniforms.uAmt.value = amt; }, dispose() { geo.dispose(); mat.dispose(); } };
}
