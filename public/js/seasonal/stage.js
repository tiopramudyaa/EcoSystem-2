// Panggung 3D untuk loading screen musiman. Tidak ada yang berjalan saat import.
// API: supportsWebGL(), preload(scene), play(canvas, { scene, palette, duration })

const SCENES = ['sleigh', 'fireworks', 'crescent', 'eid', 'lunar', 'flag'];
const THREE_URL = new URL('../../vendor/three/three.module.min.js', import.meta.url).href;

let webglOk = null;
let threePromise = null;
const scenePromises = {};

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

export async function play(canvas, opts = {}) {
  const { scene: name, palette } = opts;
  const duration = Math.max(600, opts.duration || 2800);
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
    try { if (renderer) { renderer.dispose(); renderer.forceContextLoss(); } } catch (e) { /* abaikan */ }
    resolveDone();
  };

  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
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
    impl = mod.create(THREE, { scene: scn, camera, renderer, palette, width, height, duration });

    onResize = () => { [width, height] = size(); camera.aspect = width / height; camera.updateProjectionMatrix(); };
    window.addEventListener('resize', onResize);

    let elapsed = 0, last = performance.now();
    const frame = (now) => {
      if (ended) return;
      raf = requestAnimationFrame(frame);
      const dtRaw = (now - last) / 1000;
      last = now;
      if (document.visibilityState !== 'visible') return;
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

    // render satu frame awal sehingga kegagalan terdeteksi sebelum play() resolve
    impl.update(0, 0);
    canvas.style.opacity = '0';
    renderer.render(scn, camera);
    raf = requestAnimationFrame((now) => { last = now; frame(now); });
  } catch (e) {
    cleanup();
    throw e;
  }

  return { done, stop: cleanup };
}
