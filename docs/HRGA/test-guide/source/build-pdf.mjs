import fs from 'fs';
import path from 'path';
import puppeteer from 'puppeteer-core';
import { meta, blocks } from './content.mjs';

const OUT = path.resolve('out');
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const inline = (s) => esc(s)
  .replace(/`([^`]+)`/g, '<code>$1</code>')
  .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

const imgTag = (f, cap) => {
  const data = fs.readFileSync(path.join(OUT, `${f}.png`)).toString('base64');
  return `<figure><img src="data:image/png;base64,${data}" alt="${esc(cap)}"><figcaption>${esc(cap)}</figcaption></figure>`;
};

let body = '';
const chapters = [];
let caseCount = 0;

for (const b of blocks) {
  switch (b.t) {
    case 'h1': chapters.push(b.x); body += `<h1>${inline(b.x)}</h1>`; break;
    case 'h2': body += `<h2>${inline(b.x)}</h2>`; break;
    case 'h3': body += `<h3>${inline(b.x)}</h3>`; break;
    case 'p': body += `<p>${inline(b.x)}</p>`; break;
    case 'code': body += `<pre>${esc(b.x)}</pre>`; break;
    case 'pb': body += '<div class="pb"></div>'; break;
    case 'ul': body += `<ul>${b.items.map((i) => `<li>${inline(i)}</li>`).join('')}</ul>`; break;
    case 'ol': body += `<ol>${b.items.map((i) => `<li>${inline(i)}</li>`).join('')}</ol>`; break;
    case 'note': body += `<div class="note ${b.k}">${inline(b.x)}</div>`; break;
    case 'img': body += imgTag(b.f, b.cap); break;
    case 'table': {
      const cols = b.widths.map((w) => `<col style="width:${w}%">`).join('');
      body += `<table><colgroup>${cols}</colgroup><thead><tr>${b.head.map((h) => `<th>${inline(h)}</th>`).join('')}</tr></thead><tbody>${b.rows.map((r) => `<tr>${r.map((c) => `<td>${inline(c)}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
      break;
    }
    case 'case': {
      caseCount += 1;
      body += `<table class="case"><colgroup><col style="width:19%"><col style="width:81%"></colgroup>
        <thead><tr><th colspan="2"><span class="cid">${esc(b.id)}</span> ${inline(b.title)}</th></tr></thead><tbody>
        <tr><th>Login</th><td>${inline(b.who)}</td></tr>
        <tr><th>Data</th><td><ul>${b.data.map((d) => `<li>${inline(d)}</li>`).join('')}</ul></td></tr>
        <tr><th>Langkah</th><td><ol>${b.steps.map((d) => `<li>${inline(d)}</li>`).join('')}</ol></td></tr>
        <tr><th>Hasil yang diharapkan</th><td><ul>${b.expect.map((d) => `<li>${inline(d)}</li>`).join('')}</ul></td></tr>
        <tr><th>Perubahan dana</th><td>${inline(b.fund)}</td></tr>
        <tr><th>Hasil</th><td>☐ Lulus &nbsp;&nbsp; ☐ Gagal &nbsp;&nbsp; Catatan: ______________________________</td></tr>
        </tbody></table>`;
      break;
    }
    default: throw new Error('unknown block ' + b.t);
  }
}

const toc = chapters.map((c) => `<li>${inline(c)}</li>`).join('');

const html = `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>${esc(meta.title)}</title><style>
@page { size: A4; margin: 18mm 16mm 20mm 16mm; }
* { box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.45; color: #1c2330; }
h1 { font-size: 18pt; margin: 26px 0 10px; padding-bottom: 6px; border-bottom: 2px solid #8a1c1c; page-break-after: avoid; }
h2 { font-size: 12.5pt; margin: 20px 0 8px; color: #8a1c1c; page-break-after: avoid; }
h3 { font-size: 11pt; margin: 14px 0 6px; page-break-after: avoid; }
p { margin: 6px 0 9px; }
ul, ol { margin: 6px 0 10px; padding-left: 20px; }
li { margin: 3px 0; }
code { font-family: Consolas, "Courier New", monospace; font-size: 9pt; background: #f1f3f6; padding: 1px 4px; border-radius: 3px; }
pre { font-family: Consolas, "Courier New", monospace; font-size: 9.5pt; background: #f1f3f6; border: 1px solid #dde2ea; border-radius: 6px; padding: 8px 12px; margin: 8px 0 12px; white-space: pre-wrap; }
table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; font-size: 9pt; table-layout: fixed; }
th, td { border: 1px solid #cfd5de; padding: 5px 7px; vertical-align: top; text-align: left; word-wrap: break-word; }
thead th { background: #8a1c1c; color: #fff; }
tr { page-break-inside: avoid; }
figure { margin: 12px 0 14px; text-align: center; page-break-inside: avoid; }
figure img { width: 100%; border: 1px solid #e3e7ed; border-radius: 6px; }
figcaption { font-size: 8.5pt; color: #5d6878; margin-top: 4px; }
.note { border-left: 4px solid #8a1c1c; background: #f8e9e9; padding: 8px 12px; margin: 10px 0; border-radius: 0 6px 6px 0; page-break-inside: avoid; }
.note.warn { border-color: #b26a00; background: #fdf1dc; }
.note.info { border-color: #2b5fb3; background: #e4edfa; }
table.case { margin: 14px 0 16px; page-break-inside: avoid; }
table.case thead th { font-size: 10.5pt; }
table.case tbody th { background: #f1f3f6; color: #1c2330; width: 19%; font-size: 8.5pt; text-transform: none; }
table.case td ul, table.case td ol { margin: 0; padding-left: 16px; }
table.case td li { margin: 2px 0; }
.cid { display: inline-block; background: #fff; color: #8a1c1c; border-radius: 4px; padding: 0 6px; margin-right: 6px; font-weight: 700; }
.pb { page-break-after: always; }
.cover { height: 235mm; display: flex; flex-direction: column; justify-content: center; page-break-after: always; }
.cover .bar { width: 70px; height: 6px; background: #8a1c1c; margin-bottom: 22px; }
.cover h1 { border: 0; font-size: 30pt; line-height: 1.15; margin: 0 0 10px; padding: 0; }
.cover .sub { font-size: 15pt; color: #5d6878; margin-bottom: 38px; }
.cover .meta { font-size: 10pt; color: #5d6878; line-height: 1.7; }
.toc { page-break-after: always; }
.toc h1 { margin-top: 0; }
.toc ol { font-size: 11.5pt; line-height: 1.9; }
</style></head><body>
<div class="cover"><div class="bar"></div><h1>${esc(meta.title)}</h1><div class="sub">${esc(meta.subtitle)}</div>
<div class="meta">${esc(meta.version)}<br>${esc(meta.audience)}<br>${esc(meta.branch)}</div></div>
<div class="toc"><h1>Daftar isi</h1><ol>${toc}</ol>
<p>Panduan ini memuat ${caseCount} skenario uji, ${blocks.filter((b) => b.t === 'img').length} diagram, dan lembar hasil pengujian pada bab terakhir.</p></div>
${body}
</body></html>`;

fs.writeFileSync(path.join(OUT, 'guide.html'), html);

const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setContent(html, { waitUntil: 'load' });
const pdfPath = path.join(OUT, 'Panduan-Pengujian-Objects-and-Funds.pdf');
await page.pdf({
  path: pdfPath, format: 'A4', printBackground: true, displayHeaderFooter: true,
  margin: { top: '18mm', bottom: '20mm', left: '16mm', right: '16mm' },
  headerTemplate: '<div></div>',
  footerTemplate: `<div style="width:100%;font-family:Arial;font-size:8px;color:#5d6878;padding:0 16mm;display:flex;justify-content:space-between"><span>${esc(meta.title)}</span><span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span></div>`,
});
await browser.close();
console.log('pdf ok', fs.statSync(pdfPath).size, 'bytes,', caseCount, 'cases');
