// Penonton siluet untuk adegan Tahun Baru: empat pose (kedua tangan terangkat, melambai, ponsel, berdiri) sebagai
// InstancedMesh gabungan, dengan cahaya tepi (rim) yang menyala mengikuti kilatan kembang api.
// Tidak ada yang berjalan saat import.
import { mergeParts } from '../common.js';

function person(THREE, pose) {
  const parts = [];
  const M = new THREE.Matrix4(), R = new THREE.Matrix4(), E = new THREE.Euler();
  const add = (geo, x, y, z, rx = 0, ry = 0, rz = 0, sx = 1, sy = 1, sz = 1) => {
    M.makeTranslation(x, y, z);
    R.makeRotationFromEuler(E.set(rx, ry, rz)); M.multiply(R);
    R.makeScale(sx, sy, sz); M.multiply(R);
    parts.push({ geo, m: M.clone() });
  };
  add(new THREE.CapsuleGeometry(0.24, 0.5, 3, 8), 0, 1.1, 0, 0, 0, 0, 1.25, 1, 0.8);
  add(new THREE.CylinderGeometry(0.24, 0.2, 0.42, 7), 0, 0.5, 0, 0, 0, 0, 1, 1, 0.8);
  add(new THREE.CylinderGeometry(0.085, 0.1, 0.9, 5), -0.11, 0.45, 0);
  add(new THREE.CylinderGeometry(0.085, 0.1, 0.9, 5), 0.11, 0.45, 0);
  add(new THREE.CylinderGeometry(0.075, 0.09, 0.14, 6), 0, 1.58, 0);
  add(new THREE.SphereGeometry(0.135, 10, 8), 0, 1.74, 0, 0, 0, 0, 1, 1.1, 1);
  // ang: sudut dari bawah (0 = lurus ke bawah), positif menjauh dari badan
  const arm = (side, ang, bend) => {
    const sx = side * 0.31, sy = 1.42, a = ang * side;
    const ex = sx + Math.sin(a) * 0.34, ey = sy - Math.cos(a) * 0.34;
    add(new THREE.CylinderGeometry(0.068, 0.078, 0.34, 6), (sx + ex) / 2, (sy + ey) / 2, 0, 0, 0, a);
    const b2 = a + bend * side, hx = ex + Math.sin(b2) * 0.34, hy = ey - Math.cos(b2) * 0.34;
    add(new THREE.CylinderGeometry(0.056, 0.066, 0.34, 6), (ex + hx) / 2, (ey + hy) / 2, 0, 0, 0, b2);
    add(new THREE.SphereGeometry(0.068, 6, 5), hx + Math.sin(b2) * 0.04, hy - Math.cos(b2) * 0.04, 0);
    return [hx, hy];
  };
  if (pose === 'up') { arm(1, 2.55, 0.25); arm(-1, 2.55, 0.25); }
  else if (pose === 'wave') { arm(1, 2.75, -0.3); arm(-1, 0.18, 0.1); }
  else if (pose === 'phone') { const h = arm(1, 2.35, -0.55); add(new THREE.BoxGeometry(0.1, 0.18, 0.02), h[0] - 0.03, h[1] + 0.08, 0.05); arm(-1, 0.18, 0.1); }
  else { arm(1, 0.16, 0.1); arm(-1, 0.16, 0.1); }
  const g = mergeParts(THREE, parts); parts.forEach((p) => p.geo.dispose());
  return g;
}

/**
 * specs: [{ x, z, s, pose, ph, hop, yaw }]. Mengembalikan { group, update(T, flash), dispose }.
 * flash: 0..1 kekuatan cahaya tepi dari kilatan kembang api.
 */
export function createCrowd(THREE, specs, { y = 0, rim = '#ffd9a0' } = {}) {
  const uniforms = { uRim: { value: new THREE.Color(rim) }, uFlash: { value: 0.25 } };
  const mat = new THREE.MeshStandardMaterial({ color: '#07080d', roughness: 1, metalness: 0 });
  mat.onBeforeCompile = (sh) => {
    sh.uniforms.uRim = uniforms.uRim; sh.uniforms.uFlash = uniforms.uFlash;
    sh.fragmentShader = 'uniform vec3 uRim; uniform float uFlash;\n' + sh.fragmentShader.replace(
      '#include <emissivemap_fragment>',
      '#include <emissivemap_fragment>\n totalEmissiveRadiance += uRim * pow(1.0 - clamp(dot(normalize(normal), normalize(vViewPosition)), 0.0, 1.0), 4.2) * uFlash;',
    );
  };
  const group = new THREE.Group();
  const geos = [], metas = [];
  const dummy = new THREE.Object3D();
  for (const pose of ['up', 'wave', 'phone', 'stand']) {
    const list = specs.filter((s) => s.pose === pose);
    if (!list.length) continue;
    const g = person(THREE, pose); geos.push(g);
    const im = new THREE.InstancedMesh(g, mat, list.length);
    im.frustumCulled = false;
    group.add(im); metas.push({ im, list });
  }
  return {
    group,
    update(T, flash) {
      uniforms.uFlash.value = 0.02 + flash * 0.55;
      for (const { im, list } of metas) {
        for (let i = 0; i < list.length; i++) {
          const s = list[i];
          const hop = s.hop * Math.max(0, Math.sin(T * 5.2 + s.ph));
          dummy.position.set(s.x, y + hop * 0.14, s.z);
          dummy.rotation.set(0, s.yaw || 0, Math.sin(T * 2.6 + s.ph) * 0.045 * s.hop);
          dummy.scale.setScalar(s.s);
          dummy.updateMatrix(); im.setMatrixAt(i, dummy.matrix);
        }
        im.instanceMatrix.needsUpdate = true;
      }
    },
    dispose() { geos.forEach((g) => g.dispose()); mat.dispose(); metas.forEach((m) => m.im.dispose()); },
  };
}
