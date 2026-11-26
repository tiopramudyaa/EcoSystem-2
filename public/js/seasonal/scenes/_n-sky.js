// Pelengkap langit dan lensa untuk adegan Natal: aurora tipis berpita dan vinyet embun beku di tepi layar.
// Tidak ada yang berjalan saat import.
import { canvasTex } from '../common.js';

/** Aurora hijau kebiruan yang sangat halus; satu mesh, hanya offset texture yang bergerak. */
export function createAurora(THREE, rnd, { y = 150, z = -360, width = 520, height = 150, opacity = 0.11 } = {}) {
  const tex = canvasTex(THREE, 512, 128, (g, w, h) => {
    g.clearRect(0, 0, w, h);
    for (let i = 0; i < 150; i++) {
      const x = (i / 150) * w + (rnd() - 0.5) * 4, a = 0.05 + rnd() * 0.16, hh = h * (0.45 + rnd() * 0.5);
      const gr = g.createLinearGradient(0, h, 0, h - hh);
      const tint = rnd() < 0.82 ? '120,235,190' : '110,215,215';
      gr.addColorStop(0, `rgba(${tint},0)`); gr.addColorStop(0.25, `rgba(${tint},${a})`); gr.addColorStop(1, `rgba(${tint},0)`);
      g.fillStyle = gr; g.fillRect(x, h - hh, 3 + rnd() * 5, hh);
    }
  });
  tex.wrapS = THREE.RepeatWrapping;
  const geo = new THREE.PlaneGeometry(width, height, 40, 1);
  const p = geo.attributes.position;
  for (let i = 0; i < p.count; i++) { const x = p.getX(i); p.setZ(i, -Math.pow(x / (width / 2), 2) * 60 + Math.sin(x * 0.03) * 8); p.setY(i, p.getY(i) + Math.sin(x * 0.02 + 1.3) * 14); }
  geo.computeBoundingSphere();
  const mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity, depthWrite: false, blending: THREE.AdditiveBlending, fog: false, side: THREE.DoubleSide });
  const mesh = new THREE.Mesh(geo, mat);
  mesh.position.set(-60, y, z); mesh.frustumCulled = false; mesh.renderOrder = -5;
  return { mesh, tex, update(T) { tex.offset.x = T * 0.006; mat.opacity = opacity * (0.8 + 0.2 * Math.sin(T * 0.9)); } };
}

/** Vinyet embun beku: bidang di depan kamera, tepi putih kebiruan, kristal halus di sudut. Pusat dibiarkan bersih. */
export function createFrost(THREE, rnd, { strength = 0.55 } = {}) {
  const tex = canvasTex(THREE, 512, 288, (g, w, h) => {
    const rg = g.createRadialGradient(w / 2, h / 2, h * 0.38, w / 2, h / 2, w * 0.62);
    rg.addColorStop(0, 'rgba(200,240,225,0)'); rg.addColorStop(0.7, 'rgba(200,240,225,0.10)'); rg.addColorStop(1, 'rgba(225,248,238,0.42)');
    g.fillStyle = rg; g.fillRect(0, 0, w, h);
    g.lineCap = 'round';
    for (const [cx, cy, dx, dy] of [[0, 0, 1, 1], [w, 0, -1, 1], [0, h, 1, -1], [w, h, -1, -1]]) {
      for (let i = 0; i < 14; i++) {
        let x = cx, y = cy, a = Math.atan2(dy, dx) + (rnd() - 0.5) * 1.5, len = 14 + rnd() * 40;
        g.strokeStyle = `rgba(235,250,242,${0.03 + rnd() * 0.07})`; g.lineWidth = 0.6 + rnd() * 1.2;
        g.beginPath(); g.moveTo(x, y);
        for (let k = 0; k < 4; k++) { x += Math.cos(a) * len / 4; y += Math.sin(a) * len / 4; a += (rnd() - 0.5) * 0.9; g.lineTo(x, y); }
        g.stroke();
      }
    }
  });
  const mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: strength, depthTest: false, depthWrite: false, fog: false });
  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), mat);
  mesh.renderOrder = 999; mesh.frustumCulled = false; mesh.position.z = -1;
  return {
    mesh, tex,
    fit(camera) { const h = 2 * Math.tan(camera.fov * Math.PI / 360) * 1.0; mesh.scale.set(h * camera.aspect * 1.002, h * 1.002, 1); },
  };
}
