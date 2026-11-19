import { diagrams } from './diagrams.mjs';
import fs from 'fs';
import path from 'path';
import { pathToFileURL } from 'url';
import { execFileSync } from 'child_process';

const out = path.resolve('out');
fs.mkdirSync(out, { recursive: true });
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

for (const [name, fn] of Object.entries(diagrams)) {
  const svg = fn();
  const [, w, h] = svg.match(/width="(\d+)" height="(\d+)"/);
  fs.writeFileSync(path.join(out, `${name}.svg`), svg);
  const html = path.join(out, `${name}.html`);
  fs.writeFileSync(html, `<!doctype html><meta charset="utf-8"><style>html,body{margin:0;background:#fff}</style>${svg}`);
  execFileSync(chrome, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=2', `--window-size=${w},${h}`, `--screenshot=${path.join(out, name + '.png')}`, pathToFileURL(html).href], { stdio: 'ignore' });
  console.log(name, `${w}x${h}`, fs.existsSync(path.join(out, name + '.png')) ? 'ok' : 'MISSING');
}
