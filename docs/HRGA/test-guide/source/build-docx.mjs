import fs from 'fs';
import path from 'path';
import {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, ImageRun, Footer, Header,
  AlignmentType, BorderStyle, WidthType, ShadingType, LevelFormat, PageNumber, PageBreak, VerticalAlign,
} from 'docx';
import { meta, blocks } from './content.mjs';

const OUT = path.resolve('out');
const FONT = 'Arial';
const INK = '1C2330', ACCENT = '8A1C1C', MUTED = '5D6878', LINE = 'CFD5DE', SOFT = 'F1F3F6';
const W = 9638; // content width in DXA (A4, 2 cm margins)

// ── inline markup -> runs ──────────────────────────────────────────────────
function runs(text, base = {}) {
  const out = [];
  const re = /(`[^`]+`|\*\*[^*]+\*\*)/g;
  let last = 0, m;
  while ((m = re.exec(text)) !== null) {
    if (m.index > last) out.push(new TextRun({ text: text.slice(last, m.index), font: FONT, ...base }));
    const tok = m[0];
    if (tok.startsWith('`')) out.push(new TextRun({ text: tok.slice(1, -1), font: 'Consolas', size: (base.size || 21) - 2, shading: { type: ShadingType.CLEAR, fill: SOFT, color: 'auto' }, color: base.color, bold: base.bold }));
    else out.push(new TextRun({ text: tok.slice(2, -2), font: FONT, ...base, bold: true }));
    last = m.index + tok.length;
  }
  if (last < text.length) out.push(new TextRun({ text: text.slice(last), font: FONT, ...base }));
  return out;
}

const para = (text, o = {}) => new Paragraph({ children: runs(text, o.run || {}), spacing: { before: o.before ?? 60, after: o.after ?? 120, line: 300 }, alignment: o.align, keepNext: o.keepNext, numbering: o.numbering, indent: o.indent, shading: o.shading, border: o.border });

// ── numbering (bullets + one decimal list per ordered list) ────────────────
const numberingConfigs = [{
  reference: 'bullets',
  levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 270 } } } }],
}];
let listSeq = 0;
const newDecimal = () => {
  const reference = `dec${++listSeq}`;
  numberingConfigs.push({ reference, levels: [{ level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 300 } } } }] });
  return reference;
};
const bullet = (t, o = {}) => para(t, { numbering: { reference: 'bullets', level: 0 }, before: 20, after: 40, ...o });
const stepP = (ref, t) => para(t, { numbering: { reference: ref, level: 0 }, before: 20, after: 40 });

// ── tables ─────────────────────────────────────────────────────────────────
const border = { style: BorderStyle.SINGLE, size: 4, color: LINE };
const borders = { top: border, bottom: border, left: border, right: border };
const margins = { top: 70, bottom: 70, left: 110, right: 110 };

function cellsWidths(percent) {
  const w = percent.map((p) => Math.round((p / 100) * W));
  w[w.length - 1] += W - w.reduce((a, b) => a + b, 0);
  return w;
}

function cell(children, width, o = {}) {
  return new TableCell({ children, width: { size: width, type: WidthType.DXA }, borders, margins, shading: o.fill ? { type: ShadingType.CLEAR, fill: o.fill, color: 'auto' } : undefined, columnSpan: o.span, verticalAlign: o.valign || VerticalAlign.TOP });
}

function simpleTable(head, rows, percent) {
  const widths = cellsWidths(percent);
  const headRow = new TableRow({ tableHeader: true, cantSplit: true, children: head.map((h, i) => cell([para(h, { run: { bold: true, color: 'FFFFFF', size: 19 }, before: 0, after: 0 })], widths[i], { fill: ACCENT })) });
  const body = rows.map((r) => new TableRow({ cantSplit: true, children: r.map((c, i) => cell([para(c, { run: { size: 19 }, before: 0, after: 0 })], widths[i])) }));
  return new Table({ width: { size: W, type: WidthType.DXA }, columnWidths: widths, rows: [headRow, ...body] });
}

function caseTable(b) {
  const widths = [1750, W - 1750];
  const label = (t) => cell([para(t, { run: { bold: true, size: 19 }, before: 0, after: 0 })], widths[0], { fill: SOFT });
  const ref = newDecimal();
  const rows = [
    new TableRow({ cantSplit: true, children: [cell([new Paragraph({ children: [new TextRun({ text: `${b.id}  `, font: FONT, bold: true, color: 'FFFFFF', size: 23 }), ...runs(b.title, { bold: true, color: 'FFFFFF', size: 23 })], spacing: { before: 0, after: 0 }, keepNext: true })], W, { fill: ACCENT, span: 2 })] }),
    new TableRow({ cantSplit: true, children: [label('Login'), cell([para(b.who, { run: { size: 19 }, before: 0, after: 0 })], widths[1])] }),
    new TableRow({ children: [label('Data'), cell(b.data.map((d) => bullet(d, { run: { size: 19 } })), widths[1])] }),
    new TableRow({ children: [label('Langkah'), cell(b.steps.map((d) => new Paragraph({ children: runs(d, { size: 19 }), numbering: { reference: ref, level: 0 }, spacing: { before: 20, after: 40, line: 280 } })), widths[1])] }),
    new TableRow({ children: [label('Hasil yang diharapkan'), cell(b.expect.map((d) => bullet(d, { run: { size: 19 } })), widths[1])] }),
    new TableRow({ cantSplit: true, children: [label('Perubahan dana'), cell([para(b.fund, { run: { size: 19 }, before: 0, after: 0 })], widths[1])] }),
    new TableRow({ cantSplit: true, children: [label('Hasil'), cell([para('☐ Lulus      ☐ Gagal      Catatan: ______________________________', { run: { size: 19 }, before: 0, after: 0 })], widths[1])] }),
  ];
  return new Table({ width: { size: W, type: WidthType.DXA }, columnWidths: widths, rows });
}

function note(kind, text) {
  const colors = { info: ['2B5FB3', 'E4EDFA'], warn: ['B26A00', 'FDF1DC'] }[kind] || [ACCENT, 'F8E9E9'];
  const none = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
  return new Table({
    width: { size: W, type: WidthType.DXA }, columnWidths: [W],
    rows: [new TableRow({ cantSplit: true, children: [new TableCell({
      children: [para(text, { run: { size: 20 }, before: 0, after: 0 })], width: { size: W, type: WidthType.DXA },
      borders: { top: none, bottom: none, right: none, left: { style: BorderStyle.SINGLE, size: 24, color: colors[0] } },
      shading: { type: ShadingType.CLEAR, fill: colors[1], color: 'auto' }, margins: { top: 110, bottom: 110, left: 190, right: 150 },
    })] })],
  });
}

const spacer = () => new Paragraph({ children: [], spacing: { before: 0, after: 100 } });

function image(f, cap) {
  const buf = fs.readFileSync(path.join(OUT, `${f}.png`));
  const w = buf.readUInt32BE(16), h = buf.readUInt32BE(20);
  const width = 600, height = Math.round((h / w) * width);
  return [
    new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 120, after: 40 }, keepNext: true, children: [new ImageRun({ type: 'png', data: buf, transformation: { width, height }, altText: { title: cap, description: cap, name: f } })] }),
    new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 160 }, children: [new TextRun({ text: cap, font: FONT, size: 17, color: MUTED })] }),
  ];
}

// ── assemble ───────────────────────────────────────────────────────────────
const children = [];

// cover
children.push(new Paragraph({ spacing: { before: 3200, after: 0 }, children: [] }));
children.push(new Paragraph({ border: { top: { style: BorderStyle.SINGLE, size: 36, color: ACCENT, space: 1 } }, spacing: { before: 0, after: 300 }, indent: { right: 7600 }, children: [] }));
children.push(new Paragraph({ spacing: { before: 0, after: 160 }, children: [new TextRun({ text: meta.title, font: FONT, bold: true, size: 60, color: INK })] }));
children.push(new Paragraph({ spacing: { before: 0, after: 700 }, children: [new TextRun({ text: meta.subtitle, font: FONT, size: 30, color: MUTED })] }));
for (const line of [meta.version, meta.audience, meta.branch]) children.push(new Paragraph({ spacing: { before: 0, after: 100 }, children: [new TextRun({ text: line, font: FONT, size: 20, color: MUTED })] }));
children.push(new Paragraph({ children: [new PageBreak()] }));

// table of contents (static list)
const chapterTitles = blocks.filter((b) => b.t === 'h1').map((b) => b.x);
children.push(new Paragraph({ heading: 'Heading1', children: [new TextRun({ text: 'Daftar isi', font: FONT })] }));
chapterTitles.forEach((t) => children.push(new Paragraph({ spacing: { before: 60, after: 100 }, children: runs(t, { size: 23 }) })));
const caseCount = blocks.filter((b) => b.t === 'case').length;
children.push(para(`Panduan ini memuat ${caseCount} skenario uji, 7 diagram, dan lembar hasil pengujian pada bab terakhir.`, { before: 200 }));
children.push(new Paragraph({ children: [new PageBreak()] }));

for (const b of blocks) {
  switch (b.t) {
    case 'h1': children.push(new Paragraph({ heading: 'Heading1', children: runs(b.x) })); break;
    case 'h2': children.push(new Paragraph({ heading: 'Heading2', children: runs(b.x) })); break;
    case 'h3': children.push(new Paragraph({ heading: 'Heading3', children: runs(b.x) })); break;
    case 'p': children.push(para(b.x)); break;
    case 'code': children.push(new Paragraph({ spacing: { before: 60, after: 140 }, shading: { type: ShadingType.CLEAR, fill: SOFT, color: 'auto' }, border: { left: { style: BorderStyle.SINGLE, size: 12, color: ACCENT, space: 6 } }, children: [new TextRun({ text: b.x, font: 'Consolas', size: 20 })] })); break;
    case 'pb': children.push(new Paragraph({ children: [new PageBreak()] })); break;
    case 'ul': b.items.forEach((i) => children.push(bullet(i))); children.push(spacer()); break;
    case 'ol': { const ref = newDecimal(); b.items.forEach((i) => children.push(stepP(ref, i))); children.push(spacer()); break; }
    case 'note': children.push(note(b.k, b.x), spacer()); break;
    case 'img': children.push(...image(b.f, b.cap)); break;
    case 'table': children.push(simpleTable(b.head, b.rows, b.widths), spacer()); break;
    case 'case': children.push(caseTable(b), spacer()); break;
    default: throw new Error('unknown block ' + b.t);
  }
}

const heading = (size, color, before) => ({ run: { font: FONT, size, bold: true, color }, paragraph: { spacing: { before, after: 140 }, keepNext: true, keepLines: true } });

const doc = new Document({
  creator: 'Tim Eco', title: meta.title, description: meta.subtitle,
  styles: {
    default: { document: { run: { font: FONT, size: 21, color: INK } } },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true, ...heading(36, INK, 360), paragraph: { spacing: { before: 360, after: 160 }, keepNext: true, keepLines: true, outlineLevel: 0, border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: ACCENT, space: 4 } } } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true, ...heading(25, ACCENT, 280), paragraph: { spacing: { before: 280, after: 120 }, keepNext: true, keepLines: true, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true, ...heading(22, INK, 200), paragraph: { spacing: { before: 200, after: 100 }, keepNext: true, keepLines: true, outlineLevel: 2 } },
    ],
  },
  numbering: { config: numberingConfigs },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1134, bottom: 1134, left: 1134, right: 1134 } } },
    footers: {
      default: new Footer({ children: [new Paragraph({
        border: { top: { style: BorderStyle.SINGLE, size: 4, color: LINE, space: 4 } },
        tabStops: [{ type: 'right', position: W }],
        children: [new TextRun({ text: meta.title, font: FONT, size: 16, color: MUTED }), new TextRun({ text: '\tHalaman ', font: FONT, size: 16, color: MUTED }), new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 16, color: MUTED }), new TextRun({ text: ' dari ', font: FONT, size: 16, color: MUTED }), new TextRun({ children: [PageNumber.TOTAL_PAGES], font: FONT, size: 16, color: MUTED })],
      })] }),
    },
    children,
  }],
});

const buf = await Packer.toBuffer(doc);
const file = path.join(OUT, 'Panduan-Pengujian-Objects-and-Funds.docx');
fs.writeFileSync(file, buf);
console.log('docx ok', buf.length, 'bytes');
