import fs from 'fs';
import JSZip from 'jszip';

const file = process.argv[2];
const zip = await JSZip.loadAsync(fs.readFileSync(file));
const out = new JSZip();

// [Content_Types].xml first, then everything else; no folder entries.
const names = Object.keys(zip.files).filter((n) => !zip.files[n].dir);
names.sort((a, b) => (a === '[Content_Types].xml' ? -1 : b === '[Content_Types].xml' ? 1 : 0));
for (const n of names) out.file(n, await zip.files[n].async('nodebuffer'));

fs.writeFileSync(file, await out.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' }));
const check = await JSZip.loadAsync(fs.readFileSync(file));
console.log('entries', Object.keys(check.files).length, 'folders', Object.keys(check.files).filter((n) => n.endsWith('/')).length, 'first', Object.keys(check.files)[0]);
