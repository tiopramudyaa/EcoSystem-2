// Jenis ledakan kembang api (peony, chrysanthemum, willow, palm, ring, heart, crossette) untuk sistem percikan GPU.
// Semua percikan dijadwalkan di muka (birth di masa depan), jadi per frame tidak ada pekerjaan partikel.
// Tidak ada yang berjalan saat import.

export function createShots(THREE, sp, rnd, density = 1) {
  const tmp = [0, 0, 0], tv = [0, 0, 0];
  const N = (n) => Math.max(4, Math.round(n * density));
  const sph = (s, o) => { const u = rnd() * 2 - 1, th = rnd() * 6.283, r = Math.sqrt(1 - u * u); o[0] = Math.cos(th) * r * s; o[1] = u * s; o[2] = Math.sin(th) * r * s; return o; };
  const C = new THREE.Color();
  // parameter dasar: drag k, gravitasi g
  function glow(b, T0, rad, ttl, amt, c) { sp.spawn(b.x, b.y, b.z, 0, 0, 0, c.r * amt, c.g * amt, c.b * amt, T0, ttl, 1, 0, 0, rad, 1, 0); }

  function rocket(b) {
    const x0 = b.x * 0.5, z0 = -58, y0 = -1.2, a = b.rise, k = 0.35, g = -8;
    const ek = (1 - Math.exp(-k * a)) / k;
    const vx = (b.x - x0) / ek, vz = (b.z - z0) / ek, vy = (b.y - y0 - (g / k) * (a - ek)) / ek;
    const T0 = b.t - a;
    sp.spawn(x0, y0, z0, vx, vy, vz, 1.9, 1.6, 1.1, T0, a, k, 0.45, g, 1.5, 0, 0);
    for (let i = 0; i < N(16); i++) { // percikan ekor yang rontok
      const s = i / 16 * a; sp.posAt(tmp, x0, y0, z0, vx, vy, vz, k, g, s);
      sp.spawn(tmp[0], tmp[1], tmp[2], (rnd() - 0.5) * 3, -2 - rnd() * 3, (rnd() - 0.5) * 3, 1.5, 1.05, 0.55, T0 + s, 0.55 + rnd() * 0.3, 1.2, 0.12, -5, 1.0, 0, 0);
    }
  }
  function core(b, big) {
    glow(b, b.t, big ? 18 : 13, 0.6, 0.6, C.set('#fff1d0'));
    glow(b, b.t, big ? 30 : 24, 1.9, 0.2, b.c1);
    glow(b, b.t + 0.2, big ? 24 : 19, 3.0, 0.04, b.c1); // asap yang disinari warna ledakan
  }
  function glitter(b, parentV, k, g, T0, count) { // kerlip putih di cangkang yang meredup
    for (let i = 0; i < count; i++) {
      const v = parentV[(i * 7) % parentV.length], a = 0.55 + rnd() * 0.5;
      sp.posAt(tmp, b.x, b.y, b.z, v[0], v[1], v[2], k, g, a);
      sp.spawn(tmp[0], tmp[1], tmp[2], (rnd() - 0.5) * 4, (rnd() - 0.5) * 3, (rnd() - 0.5) * 4, 1.8, 1.7, 1.5, T0 + a, 0.9 + rnd() * 0.7, 1.3, 0.05, -2.4, 1.0, 2, rnd());
    }
  }
  const sphere = (b, n, speed, k, g, ttl, trail, w, cA, cB) => {
    const vs = [];
    for (let i = 0; i < n; i++) {
      const v = sph(speed * (0.55 + rnd() * 0.45), [0, 0, 0]); vs.push(v);
      const c = i % 5 === 0 && cB ? cB : cA;
      sp.spawn(b.x, b.y, b.z, v[0], v[1], v[2], c.r, c.g, c.b, b.t, ttl * (0.78 + rnd() * 0.3), k, trail, g, w, 0, rnd());
    }
    return vs;
  };

  const SHOT = {
    peony(b) {
      core(b);
      const v = sphere(b, N(230), 42, 1.5, -7, 2.3, 0.11, 1.5, b.c1, b.c2);
      sphere(b, N(60), 20, 1.4, -6, 1.7, 0.1, 1.4, b.c2, null);
      glitter(b, v, 1.5, -7, b.t, N(26));
    },
    chrys(b) { // krisan: goresan panjang berekor, bola padat
      core(b, true);
      const v = sphere(b, N(150), 40, 1.25, -6.5, 2.7, 0.7, 1.7, b.c1, b.c2);
      glitter(b, v, 1.25, -6.5, b.t, N(34));
    },
    willow(b) { // willow: ekor sangat panjang yang terkulai
      core(b, true);
      const vs = [];
      for (let i = 0; i < N(120); i++) {
        const v = sph(32 + rnd() * 10, [0, 0, 0]); vs.push(v);
        sp.spawn(b.x, b.y, b.z, v[0], v[1], v[2], b.c1.r * 0.9, b.c1.g * 0.8, b.c1.b * 0.6, b.t, 3.4 + rnd() * 0.8, 1.7, 1.7, -10, 1.3, 0, rnd());
      }
      glitter(b, vs, 1.7, -10, b.t, N(20));
    },
    palm(b) { // palem: lengan tebal yang melengkung dan berakhir dengan percikan
      core(b);
      const arms = 9;
      for (let i = 0; i < arms; i++) {
        const a = (i / arms) * 6.283 + rnd() * 0.2, vx = Math.cos(a) * 21, vz = Math.sin(a) * 21, vy = 31 + rnd() * 6;
        sp.spawn(b.x, b.y, b.z, vx, vy, vz, b.c1.r * 1.1, b.c1.g * 1.05, b.c1.b * 0.9, b.t, 2.6, 0.6, 1.0, -10, 2.6, 0, rnd());
        const T0 = b.t + 1.35;
        sp.posAt(tmp, b.x, b.y, b.z, vx, vy, vz, 0.6, -10, 1.35); sp.velAt(tv, vx, vy, vz, 0.6, -10, 1.35);
        for (let j = 0; j < N(7); j++) { const w = sph(7, [0, 0, 0]); sp.spawn(tmp[0], tmp[1], tmp[2], tv[0] * 0.4 + w[0], tv[1] * 0.4 + w[1], tv[2] * 0.4 + w[2], b.c2.r, b.c2.g, b.c2.b, T0, 1.2, 1.4, 0.25, -6, 1.4, 0, rnd()); }
      }
      sphere(b, N(40), 12, 1.3, -5, 1.4, 0.15, 1.4, b.c2, null);
    },
    ring(b) { // cincin dan hati sebagai aksen
      core(b);
      const heart = !!b.heart, n = N(heart ? 84 : 76), tilt = 0.4, s = heart ? 2.1 : 32, ct = Math.cos(tilt), st = Math.sin(tilt);
      for (let i = 0; i < n; i++) {
        const a = (i / n) * 6.283;
        let px, py;
        if (heart) { px = 16 * Math.pow(Math.sin(a), 3); py = 13 * Math.cos(a) - 5 * Math.cos(2 * a) - 2 * Math.cos(3 * a) - Math.cos(4 * a) + 2; px *= s; py *= s; }
        else { px = Math.cos(a) * s; py = Math.sin(a) * s; }
        sp.spawn(b.x, b.y, b.z, px, py * ct, py * st, b.c1.r, b.c1.g, b.c1.b, b.t, 2.0, 1.9, 0.26, -1.4, 1.6, 0, rnd());
      }
      sphere(b, N(30), 11, 1.5, -4, 1.3, 0.12, 1.3, b.c2, null);
    },
    cross(b) { // crossette: tiap batang pecah jadi empat
      core(b);
      const vs = [], arms = N(15);
      for (let i = 0; i < arms; i++) {
        const v = sph(27, [0, 0, 0]); vs.push(v);
        sp.spawn(b.x, b.y, b.z, v[0], v[1], v[2], b.c1.r, b.c1.g, b.c1.b, b.t, 0.62, 0.8, 0.4, -4, 1.7, 0, rnd());
        const ts = 0.62;
        sp.posAt(tmp, b.x, b.y, b.z, v[0], v[1], v[2], 0.8, -4, ts); sp.velAt(tv, v[0], v[1], v[2], 0.8, -4, ts);
        for (let j = 0; j < 4; j++) {
          const a = j * 1.571 + rnd() * 0.5, c = rnd() < 0.5 ? b.c2 : b.c1;
          sp.spawn(tmp[0], tmp[1], tmp[2], tv[0] * 0.3 + Math.cos(a) * 13, tv[1] * 0.3 + Math.sin(a) * 13, tv[2] * 0.3 + (j % 2 ? 6 : -6), c.r, c.g, c.b, b.t + ts, 1.5, 1.1, 0.4, -5, 1.5, 0, rnd());
        }
      }
    },
  };
  return { launch(b) { rocket(b); SHOT[b.kind](b); } };
}
