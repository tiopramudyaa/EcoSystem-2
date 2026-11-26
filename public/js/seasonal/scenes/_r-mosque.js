// Pembangun masjid berkubah (gaya Utsmani/Nusantara): kubah utama dengan tambur berjendela, empat setengah kubah,
// kubah sudut, portal berlengkung runcing, deretan jendela berbingkai, empat menara berbalkon dan bertopi runcing.
// Hasil: grup mesh (5 material, 5 draw call) dan satu mesh pantulan terbakar (1 draw call). Tidak ada yang berjalan saat import.
// detail 'full' untuk Ramadan, 'far' untuk latar Lebaran (lebih sedikit segmen dan jendela).
import { mergeParts } from '../common.js';
import { stoneTexture, starPatternTexture, windowGlassTexture, domeTexture } from './_r-tex.js';

export function buildMosque(THREE, rnd, { detail = 'far', gold = '#e6c36a', mirror = true, dome: domeTint = '#a9bbd8', stone: stoneTint = null } = {}) {
  const far = detail === 'far';
  const own = [];
  const stone = [], pat = [], dome = [], gld = [], glass = [], mir = [];
  const Q = new THREE.Quaternion(), E = new THREE.Euler(), V = new THREE.Vector3(), S = new THREE.Vector3();
  const M4 = (x, y, z, ry = 0, rx = 0, rz = 0) => new THREE.Matrix4().compose(V.set(x, y, z), Q.setFromEuler(E.set(rx, ry, rz)), S.set(1, 1, 1));
  const seg = (n) => (far ? Math.max(8, Math.round(n * 0.5)) : n);
  const TILE = 5.5, GM = '#6d5a30';
  // koordinat UV kotak dalam meter / TILE (per sisi menurut normal)
  const uvBox = (geo) => {
    const p = geo.attributes.position, n = geo.attributes.normal, u = geo.attributes.uv;
    for (let i = 0; i < p.count; i++) {
      const ax = Math.abs(n.getX(i)), ay = Math.abs(n.getY(i)), az = Math.abs(n.getZ(i));
      if (az >= ax && az >= ay) u.setXY(i, p.getX(i) / TILE, p.getY(i) / TILE);
      else if (ax >= ay) u.setXY(i, p.getZ(i) / TILE, p.getY(i) / TILE);
      else u.setXY(i, p.getX(i) / TILE, p.getZ(i) / TILE);
    }
    return geo;
  };
  const cool = new THREE.Color(stoneTint || '#a4b0bc');
  const add = (arr, geo, m, color, uvScale, mc, mirrorOn = true) => {
    if (arr === stone) color = new THREE.Color(color).lerp(cool, stoneTint ? 0.5 : 0.3);
    arr.push({ geo, m, color, uvScale });
    if (mirror && mirrorOn) mir.push({ geo, m, color: mc || color });
  };
  const sbox = (w, h, d, x, y, z, color = '#d6d0bf', mc = '#46526e', mo = true) => add(stone, uvBox(new THREE.BoxGeometry(w, h, d)), M4(x, y, z), color, null, mc, mo);
  const pbox = (w, h, d, x, y, z, ru, rv, color = '#ffffff', mc = '#3a4a62') => {
    const g = new THREE.BoxGeometry(w, h, d); const p = g.attributes.position, n = g.attributes.normal, u = g.attributes.uv;
    for (let i = 0; i < p.count; i++) { const az = Math.abs(n.getZ(i)) > 0.5; u.setXY(i, (az ? p.getX(i) : p.getZ(i)) / ru, p.getY(i) / rv); }
    add(pat, g, M4(x, y, z), color, null, mc);
  };
  const cyl = (arr, rt, rb, h, x, y, z, color, segs = 16, mc) => {
    const g = new THREE.CylinderGeometry(rt, rb, h, seg(segs), 1);
    add(arr, g, M4(x, y, z), color, arr === stone ? [Math.PI * (rt + rb) / TILE, h / TILE] : null, mc);
  };
  // lengkung runcing; sp = tinggi awal lengkung
  const arch = (w, h) => {
    const s = new THREE.Shape(), hs = h - w * 0.62, hw = w / 2;
    s.moveTo(-hw, 0); s.lineTo(-hw, hs);
    s.bezierCurveTo(-hw, hs + (h - hs) * 0.55, -w * 0.17, h - (h - hs) * 0.16, 0, h);
    s.bezierCurveTo(w * 0.17, h - (h - hs) * 0.16, hw, hs + (h - hs) * 0.55, hw, hs);
    s.lineTo(hw, 0); s.closePath();
    return s;
  };
  const archGeo = (w, h) => { const g = new THREE.ShapeGeometry(arch(w, h), 5); const u = g.attributes.uv, p = g.attributes.position; for (let i = 0; i < p.count; i++) u.setXY(i, (p.getX(i) + w / 2) / w, p.getY(i) / h); return g; };
  const frameGeo = (w, h, f, depth) => {
    const s = arch(w + 2 * f, h + f * 1.15), hole = new THREE.Path();
    hole.setFromPoints(arch(w, h).getPoints(far ? 3 : 5));
    s.holes.push(hole);
    return new THREE.ExtrudeGeometry(s, { depth, bevelEnabled: false, curveSegments: far ? 3 : 5 });
  };
  const window2 = (w, h, x, y, z, ry, tint = '#ffffff', frame = 0.3, depth = 0.38) => {
    add(glass, archGeo(w, h), M4(x, y, z, ry), tint, null, '#c88a45');
    add(stone, uvBox(frameGeo(w, h, frame, depth)), M4(x, y, z - 0.02, ry), '#e4ddcc', null, '#56627e', false);
  };

  // ===== ukuran induk =====
  const HW = 32, HD = 28, PY = 1.8, HH = 12, TOP = PY + HH;
  sbox(72, 1.2, 52, 0, 0.6, 0, '#8d8c84', '#3b4660');
  sbox(64, 0.6, 45, 0, 1.5, 0, '#a7a597', '#44506b');
  sbox(HW, HH, HD, 0, PY + HH / 2, 0, '#d9d3c0', '#4a5670');
  pbox(HW + 0.4, 1.3, HD + 0.4, 0, TOP - 1.4, 0, 2.6, 1.3);
  sbox(HW + 1, 0.55, HD + 1, 0, TOP + 0.05, 0, '#efe8d6', '#566280');
  add(gld, new THREE.BoxGeometry(HW + 1.1, 0.14, HD + 1.1), M4(0, TOP + 0.38, 0), gold, null, GM);
  pbox(HW + 0.3, 0.9, HD + 0.3, 0, PY + 0.55, 0, 2.6, 0.9);

  // ===== portal tengah (iwan) =====
  const pz = HD / 2;
  sbox(10.4, 12.6, 2.6, 0, PY + 6.3, pz + 1.3, '#ded8c6', '#4f5b76');
  pbox(10.6, 1.4, 2.8, 0, PY + 11.9, pz + 1.3, 2.6, 1.4);
  sbox(11.2, 0.5, 3.0, 0, PY + 12.85, pz + 1.3, '#efe8d6', '#566280');
  add(stone, uvBox(frameGeo(6.2, 9.8, 0.38, 0.3)), M4(0, PY, pz + 2.58), '#ece5d2', null, '#58647e', false);
  add(gld, frameGeo(4.85, 8.3, 0.38, 0.4), M4(0, PY, pz + 2.58), gold, null, GM, false);
  add(glass, archGeo(3.6, 7.2), M4(0, PY, pz + 2.64), '#ffd890', null, '#d89040');
  sbox(0.5, 4.2, 0.4, -1.7, PY + 2.1, pz + 2.7, '#8c5a2a', '#2a2018', false);
  sbox(0.5, 4.2, 0.4, 1.7, PY + 2.1, pz + 2.7, '#8c5a2a', '#2a2018', false);

  // ===== jendela deret =====
  const nL = 9, gap = 3.25;
  for (let i = 0; i < nL; i++) {
    const x = (i - (nL - 1) / 2) * gap;
    if (Math.abs(x) < 6.2) continue;
    window2(2.1, 5.0, x, PY + 1.6, pz + 0.01, 0, '#ffffff', 0.3, 0.36);
    window2(1.5, 3.0, x, PY + 7.4, pz + 0.01, 0, '#fff0c8', 0.24, 0.3);
  }
  if (!far) {
    for (const s of [-1, 1]) for (let i = 0; i < 5; i++) {
      const z = (i - 2) * 5.2;
      window2(2.1, 5.0, s * HW / 2 - 0.01, PY + 1.6, z, s * Math.PI / 2, '#ffffff', 0.3, 0.36);
      window2(1.5, 3.0, s * HW / 2 - 0.01, PY + 7.4, z, s * Math.PI / 2, '#fff0c8', 0.24, 0.3);
    }
  }

  // ===== kubah utama =====
  const DR = 8.4, DH = 4.4, DY = TOP + 0.7, DTOP = DY + DH;
  sbox(19.5, 0.7, 19.5, 0, TOP + 0.35, 0, '#c9c3b0', '#434e68');
  cyl(stone, DR, DR + 0.2, DH, 0, DY + DH / 2, 0, '#e2dcc9', 28, '#4c5873');
  const nWin = far ? 8 : 16;
  for (let i = 0; i < nWin; i++) {
    const a = (i + 0.5) / nWin * Math.PI * 2;
    window2(1.3, 2.9, Math.sin(a) * (DR + 0.18), DY + 0.5, Math.cos(a) * (DR + 0.18), a, '#fff0c0', 0.16, 0.22);
  }
  const ringG = new THREE.CylinderGeometry(DR + 0.32, DR + 0.32, 0.8, seg(28), 1, true);
  const ru = ringG.attributes.uv; for (let i = 0; i < ru.count; i++) ru.setXY(i, ru.getX(i) * 2 * Math.PI * DR / 2.6, ru.getY(i));
  add(pat, ringG, M4(0, DTOP - 0.5, 0), '#ffffff', null, '#344258');
  add(gld, new THREE.TorusGeometry(DR + 0.42, 0.1, 6, seg(40)), M4(0, DTOP + 0.02, 0, 0, Math.PI / 2), gold, null, GM);
  const domeProfile = (R, H, n) => { const p = []; for (let k = 0; k <= n; k++) { const th = Math.PI / 2 * (1 - k / n), c = Math.cos(th); p.push(new THREE.Vector2(Math.max(0.001, R * Math.sin(th)), H * c + H * 0.1 * Math.pow(c, 10))); } return p; };
  const domeGeo = (R, H, phiLen = Math.PI * 2, n = far ? 8 : 12) => new THREE.LatheGeometry(domeProfile(R, H, n), seg(32), 0, phiLen);
  const DOMEC = domeTint, DOMEM = '#3d4f6e';
  add(dome, domeGeo(9.0, 9.6), M4(0, DTOP - 0.1, 0), DOMEC, null, DOMEM);
  cyl(gld, 0.5, 0.9, 1.4, 0, DTOP + 9.7, 0, gold, 12, GM);
  add(gld, new THREE.SphereGeometry(0.55, 12, 8), M4(0, DTOP + 10.8, 0), gold, null, GM);
  cyl(gld, 0.06, 0.14, 3.2, 0, DTOP + 12.3, 0, gold, 6, GM);
  add(gld, new THREE.TorusGeometry(0.9, 0.17, 6, seg(24), Math.PI * 1.45), M4(0, DTOP + 13.5, 0, 0, 0, Math.PI * 0.275 + 0.35), gold, null, GM);
  const starG = new THREE.CylinderGeometry(0.34, 0.34, 0.1, 5); starG.rotateX(Math.PI / 2);
  add(gld, starG, M4(0.55, DTOP + 13.7, 0), gold, null, GM);

  // ===== setengah kubah di empat sisi =====
  const HR = 6.2;
  [[0, 1, 7.6], [0, -1, 7.6], [1, 0, 9.6], [-1, 0, 9.6]].forEach(([dx, dz, d]) => {
    add(dome, domeGeo(HR, HR * 1.05, Math.PI), M4(dx * d, TOP, dz * d, Math.atan2(-dz, dx)), DOMEC, [0.5, 1], DOMEM);
    cyl(gld, 0.05, 0.1, 1.6, dx * d, TOP + HR * 1.05 + 0.7, dz * d, gold, 5, GM);
  });
  // ===== kubah sudut =====
  [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([sx, sz]) => {
    const x = sx * (HW / 2 - 1.9), z = sz * (HD / 2 - 1.9);
    cyl(stone, 1.7, 1.8, 2.2, x, TOP + 1.2, z, '#e2dcc9', 14, '#4c5873');
    add(dome, domeGeo(2.1, 2.6, Math.PI * 2, 8), M4(x, TOP + 2.2, z), DOMEC, null, DOMEM);
    cyl(gld, 0.03, 0.07, 1.2, x, TOP + 5.3, z, gold, 5, GM);
  });

  // ===== menara =====
  const mx = HW / 2 + 1.8, mz = HD / 2 + 1.0;
  [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([sx, sz]) => {
    const x = sx * mx, z = sz * mz;
    sbox(3.6, 5.6, 3.6, x, PY + 2.8, z, '#d6d0be', '#46526e');
    pbox(3.7, 1.0, 3.7, x, PY + 5.0, z, 2.4, 1.0);
    const y0 = PY + 5.5;
    cyl(stone, 1.35, 1.5, 3.2, x, y0 + 1.6, z, '#e8e2cf', 12, '#4c5873');
    const shaft = (y1, y2, r1, r2) => cyl(stone, r2, r1, y2 - y1, x, (y1 + y2) / 2, z, '#e2dcc9', 14, '#4c5873');
    shaft(y0 + 3.2, y0 + 20, 1.12, 1.0);
    const bal = (yb, rb) => {
      add(stone, new THREE.CylinderGeometry(rb, rb * 0.62, 1.1, seg(16), 1), M4(x, yb, z), '#eee6d2', [rb * 6 / TILE, 0.2], '#56627e');
      add(stone, new THREE.CylinderGeometry(rb * 0.62, rb * 0.38, 0.9, seg(16), 1), M4(x, yb - 0.95, z), '#d8d0bc', [rb * 4 / TILE, 0.2], '#4a5672');
      add(gld, new THREE.TorusGeometry(rb * 0.92, 0.07, 6, seg(28)), M4(x, yb + 1.1, z, 0, Math.PI / 2), gold, null, GM);
      const nb = far ? 10 : 20;
      for (let k = 0; k < nb; k++) { const a = k / nb * Math.PI * 2; add(gld, new THREE.BoxGeometry(0.07, 1.0, 0.07), M4(Math.sin(a) * rb * 0.92 + x, yb + 0.58, Math.cos(a) * rb * 0.92 + z), gold, null, GM, false); }
    };
    bal(y0 + 20.4, 2.0);
    shaft(y0 + 21, y0 + 30, 0.95, 0.82);
    bal(y0 + 30.4, 1.55);
    shaft(y0 + 31, y0 + 34.5, 0.78, 0.7);
    cyl(gld, 0.9, 0.9, 0.22, x, y0 + 34.6, z, gold, 12, GM);
    add(dome, new THREE.CylinderGeometry(0.001, 0.98, 8.6, seg(14), 1), M4(x, y0 + 38.9, z), DOMEC, [3, 1], DOMEM);
    add(gld, new THREE.SphereGeometry(0.3, 8, 6), M4(x, y0 + 43.4, z), gold, null, GM);
    cyl(gld, 0.04, 0.07, 2.4, x, y0 + 44.6, z, gold, 5, GM);
    add(gld, new THREE.TorusGeometry(0.42, 0.08, 5, 14, Math.PI * 1.45), M4(x, y0 + 46.2, z, 0, 0, Math.PI * 0.275 + 0.35), gold, null, GM);
    for (const yy of [y0 + 7, y0 + 12.5, y0 + 17, y0 + 25.5]) for (const f of [[0, 1], [1, 0], [0, -1], [-1, 0]]) {
      const rr = yy > y0 + 21 ? 0.88 : 1.07;
      add(glass, new THREE.PlaneGeometry(0.26, 1.5), M4(x + f[0] * rr, yy, z + f[1] * rr, Math.atan2(f[0], f[1])), '#ffc560', null, '#c98a40', false);
    }
  });

  // ===== rakit mesh =====
  const mk = (parts) => { const g = mergeParts(THREE, parts); own.push(g); return g; };
  const sTex = stoneTexture(THREE, rnd), pTex = starPatternTexture(THREE), gTex = windowGlassTexture(THREE), dTex = domeTexture(THREE, rnd);
  own.push(sTex, pTex, gTex, dTex);
  const mats = {
    stone: new THREE.MeshStandardMaterial({ map: sTex, vertexColors: true, roughness: 0.92, metalness: 0 }),
    pat: new THREE.MeshStandardMaterial({ map: pTex, vertexColors: true, roughness: 0.55, metalness: 0.2, emissiveMap: pTex, emissive: new THREE.Color('#e2bb55'), emissiveIntensity: 0.1 }),
    dome: new THREE.MeshStandardMaterial({ map: dTex, vertexColors: true, roughness: 0.5, metalness: 0.35, side: THREE.DoubleSide }),
    gold: new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.3, metalness: 0.85, emissive: new THREE.Color(gold), emissiveIntensity: 0.28 }),
    glass: new THREE.MeshBasicMaterial({ map: gTex, vertexColors: true, side: THREE.DoubleSide, color: new THREE.Color(1, 0.8, 0.55) }),
  };
  own.push(...Object.values(mats));
  const group = new THREE.Group();
  for (const [k, arr] of [['stone', stone], ['pat', pat], ['dome', dome], ['gold', gld], ['glass', glass]]) group.add(new THREE.Mesh(mk(arr), mats[k]));
  let mirrorMesh = null;
  if (mirror) {
    const mm = new THREE.MeshBasicMaterial({ vertexColors: true, side: THREE.DoubleSide }); own.push(mm);
    mirrorMesh = new THREE.Mesh(mk(mir), mm);
  }
  // bebaskan geometri sumber (hasil gabungan sudah menyalin atributnya)
  [stone, pat, dome, gld, glass].forEach((a) => a.forEach((p) => p.geo.dispose()));
  return { group, mirror: mirrorMesh, mats, height: PY + 52, dispose() { own.forEach((o) => o.dispose()); } };
}
