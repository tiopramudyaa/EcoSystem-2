// Panggung 3D untuk loading screen musiman. Tidak ada yang berjalan saat import.
// API: supportsWebGL(), preload(scene), play(canvas, { scene, palette, duration, reuseRenderer }), release(canvas)
//
// reuseRenderer: renderer dan program shader dipertahankan per canvas supaya pemutaran berikutnya tidak
// mengompilasi ulang (pratinjau memutar adegan berulang). Pemanggil wajib memanggil release(canvas) di akhir.

const SCENES = ['sleigh', 'fireworks', 'crescent', 'eid', 'lunar', 'flag'];
const THREE_URL = new URL('../../vendor/three/three.module.min.js', import.meta.url).href;

let webglOk = null;
let threePromise = null;
const scenePromises = {};
const keptRenderers = new WeakMap();

// Lepas renderer yang dipertahankan lewat reuseRenderer (aman dipanggil berkali-kali).
export function release(canvas) {
  const r = keptRenderers.get(canvas);
  if (!r) return;
  keptRenderers.delete(canvas);
  try { r.dispose(); r.forceContextLoss(); } catch (e) { /* abaikan */ }
}

// Tier kualitas: perangkat lemah (memori atau core sedikit, Save-Data) memakai pixel ratio 1 dan
// tanpa antialias. Jaga tambahan: bila 10 frame pertama rata-rata di atas 40 ms, pixel ratio turun ke 1, dan bila sudah 1, skala render turun ke 0.75.
function qualityTier() {
  try {
    const n = navigator;
    const weak = (n.deviceMemory && n.deviceMemory <= 4) || (n.hardwareConcurrency && n.hardwareConcurrency <= 4)
      || (n.connection && n.connection.saveData);
    return weak ? 'low' : 'high';
  } catch (e) { return 'high'; }
}

export function supportsWebGL() {
  if (webglOk !== null) return webglOk;
  try {
    const c = document.createElement('canvas');
    const gl = c.getContext('webgl2') || c.getContext('webgl');
    webglOk = !!gl;
    if (gl) { const x = gl.getExtension('WEBGL_lose_context'); if (x) x.loseContext(); }
  } catch (e) { webglOk = false; }
  return webglOk;
}

function loadThree() {
  return threePromise || (threePromise = import(THREE_URL).catch((e) => { threePromise = null; throw e; }));
}

function loadScene(name) {
  if (!SCENES.includes(name)) return Promise.reject(new Error('Unknown scene: ' + name));
  return scenePromises[name] || (scenePromises[name] = import('./scenes/' + name + '.js').catch((e) => { delete scenePromises[name]; throw e; }));
}

export function preload(scene) {
  try {
    return Promise.all([loadThree(), loadScene(scene)]).then(() => undefined, () => undefined);
  } catch (e) { return Promise.resolve(); }
}

// Mengunggah semua tekstur adegan ke GPU secara bertahap (initTexture), agar draw pertama tidak
// membeku. Jeda memakai rAF dengan batas waktu 50 ms supaya tab latar tidak menahan selamanya.
async function warmTextures(renderer, scn, isEnded) {
  if (!renderer.initTexture) return;
  const texs = new Set();
  const add = (v) => { if (v && v.isTexture) texs.add(v); };
  add(scn.background); add(scn.environment);
  scn.traverse((o) => {
    const ms = o.material ? (Array.isArray(o.material) ? o.material : [o.material]) : [];
    ms.forEach((m) => {
      for (const k in m) add(m[k]);
      if (m.uniforms) for (const k in m.uniforms) add(m.uniforms[k] && m.uniforms[k].value);
    });
  });
  let slice = performance.now();
  for (const tx of texs) {
    if (isEnded()) return;
    try { renderer.initTexture(tx); } catch (e) { /* abaikan: diunggah saat draw */ }
    if (performance.now() - slice > 12) {
      await new Promise((r) => { const t = setTimeout(r, 50); requestAnimationFrame(() => { clearTimeout(t); r(); }); });
      slice = performance.now();
    }
  }
}

export async function play(canvas, opts = {}) {
  const { scene: name, palette } = opts;
  const duration = Math.max(600, opts.duration || 2800);
  const reuse = !!opts.reuseRenderer;
  if (!supportsWebGL()) throw new Error('WebGL unavailable');
  if (!SCENES.includes(name)) throw new Error('Unknown scene: ' + name);
  if (!palette) throw new Error('Palette required');

  const [THREE, mod] = await Promise.all([loadThree(), loadScene(name)]);

  let renderer = null, scn = null, impl = null, raf = 0, ended = false, onResize = null;
  let resolveDone;
  const done = new Promise((r) => { resolveDone = r; });

  const cleanup = () => {
    if (ended) return;
    ended = true;
    cancelAnimationFrame(raf);
    if (onResize) window.removeEventListener('resize', onResize);
    try { impl && impl.dispose && impl.dispose(); } catch (e) { /* abaikan */ }
    try {
      if (scn) scn.traverse((o) => {
        if (o.geometry) o.geometry.dispose();
        if (o.material) (Array.isArray(o.material) ? o.material : [o.material]).forEach((m) => {
          for (const k in m) { const v = m[k]; if (v && v.isTexture) v.dispose(); }
          m.dispose();
        });
      });
    } catch (e) { /* abaikan */ }
    // Renderer yang dipertahankan dilepas oleh release(canvas), bukan di sini.
    try { if (renderer && !reuse) { renderer.dispose(); renderer.forceContextLoss(); } } catch (e) { /* abaikan */ }
    resolveDone();
  };

  try {
    const tier = qualityTier();
    renderer = (reuse && keptRenderers.get(canvas)) || new THREE.WebGLRenderer({ canvas, antialias: tier === 'high', alpha: false });
    if (reuse) keptRenderers.set(canvas, renderer);
    renderer.setPixelRatio(tier === 'high' ? Math.min(window.devicePixelRatio || 1, 1.5) : 1);
    renderer.shadowMap.enabled = false;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.0;

    const size = () => {
      const w = Math.max(2, canvas.clientWidth || window.innerWidth), h = Math.max(2, canvas.clientHeight || window.innerHeight);
      renderer.setSize(w, h, false);
      return [w, h];
    };
    let [width, height] = size();

    scn = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 1200);
    impl = mod.create(THREE, { scene: scn, camera, renderer, palette, width, height, duration, quality: tier });

    // Layar potret (ponsel): fov vertikal dilebarkan supaya cakupan horizontal tidak menyempit
    // dan tokoh utama tidak terpotong; dibatasi 85 derajat agar tidak terdistorsi.
    const baseFov = camera.fov;
    const fitAspect = () => {
      const a = width / height;
      camera.aspect = a;
      camera.fov = a >= 1 ? baseFov : Math.min(85, (2 * Math.atan(Math.tan((baseFov * Math.PI) / 360) / a) * 180) / Math.PI);
      camera.updateProjectionMatrix();
    };
    fitAspect();
    onResize = () => { [width, height] = size(); fitAspect(); };
    window.addEventListener('resize', onResize);

    let elapsed = 0, last = performance.now(), sampled = 0, sampleSum = 0, downgraded = false, skipped = 0;
    const frame = (now) => {
      if (ended) return;
      raf = requestAnimationFrame(frame);
      const dtRaw = (now - last) / 1000;
      last = now;
      if (document.visibilityState !== 'visible') return;
      // Frame awal dan hitch tunggal (>250 ms, mis. tab baru kembali) tidak dihitung: bukan tanda GPU lemah.
      if (!downgraded && sampled < 10 && dtRaw > 0 && skipped < 3) skipped++;
      else if (!downgraded && sampled < 10 && dtRaw > 0 && dtRaw < 0.25) {
        sampleSum += dtRaw; sampled++;
        if (sampled === 10 && sampleSum / 10 > 0.04) {
          downgraded = true;
          // Lambat: turun ke pixel ratio 1; bila sudah 1 (layar DPR 1 atau tier rendah), turunkan skala render ke 0.75.
          renderer.setPixelRatio(renderer.getPixelRatio() > 1 ? 1 : 0.75);
          [width, height] = size();
        }
      }
      const dt = Math.min(Math.max(dtRaw, 0), 1 / 30);
      elapsed += dt * 1000;
      const t = Math.min(1, elapsed / duration);
      try {
        impl.update(t, dt);
        // fade in lembut pada 12% pertama
        canvas.style.opacity = String(Math.min(1, t / 0.12));
        renderer.render(scn, camera);
      } catch (e) { cleanup(); return; }
      if (t >= 1) cleanup();
    };

    // Kompilasi shader di luar jalur render: compileAsync memakai KHR_parallel_shader_compile (bila ada)
    // sehingga thread utama tidak membeku 300 sampai 1000 ms pada render pertama. Dibatasi 2,5 detik;
    // bila gagal atau tidak didukung, render pertama mengompilasi seperti biasa.
    impl.update(0, 0);
    canvas.style.opacity = '0';
    if (renderer.compileAsync) {
      try { await Promise.race([renderer.compileAsync(scn, camera), new Promise((r) => setTimeout(r, 2500))]); } catch (e) { /* abaikan */ }
    }
    if (ended) return { done, stop: cleanup };
    // Unggah tekstur bertahap: 20 sampai 30 tekstur adegan (canvas prosedural) bila diunggah sekaligus di
    // draw pertama membeku 400 sampai 700 ms. Di sini dipecah per ~12 ms dengan jeda antar frame.
    await warmTextures(renderer, scn, () => ended);
    if (ended) return { done, stop: cleanup };
    // render satu frame awal sehingga kegagalan terdeteksi sebelum play() resolve
    renderer.render(scn, camera);
    raf = requestAnimationFrame((now) => { last = now; frame(now); });
  } catch (e) {
    cleanup();
    throw e;
  }

  return { done, stop: cleanup };
}
