// Diagram generator: each function returns a standalone SVG string (print friendly, white background).
const F = 'font-family="Arial, Helvetica, sans-serif"';
const INK = '#1c2330', MUTED = '#5d6878', LINE = '#aab3c0', FILL = '#ffffff';
const C = { project: '#1f6f5c', projectBg: '#e3f2ee', office: '#2b5fb3', officeBg: '#e4edfa', support: '#a45b12', supportBg: '#faeedd', fin: '#6a3fa0', finBg: '#eee7f7', ok: '#237a3b', okBg: '#e3f3e7', warn: '#b26a00', warnBg: '#fdf1dc', bad: '#b3261e', badBg: '#fbe6e4', accent: '#8a1c1c', accentBg: '#f8e9e9' };

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const svg = (w, h, body, defs = '') => `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" ${F} font-size="13"><defs><marker id="ar" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="${MUTED}"/></marker>${defs}</defs><rect width="${w}" height="${h}" fill="#fff"/>${body}</svg>`;
const text = (x, y, s, o = {}) => `<text x="${x}" y="${y}" fill="${o.fill || INK}" font-size="${o.size || 13}" font-weight="${o.weight || 400}" text-anchor="${o.anchor || 'start'}"${o.halo ? ' stroke="#fff" stroke-width="4" paint-order="stroke"' : ''}>${esc(s)}</text>`;
const box = (x, y, w, h, o = {}) => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${o.rx ?? 8}" fill="${o.fill || FILL}" stroke="${o.stroke || LINE}" stroke-width="${o.sw || 1.3}" ${o.dash ? 'stroke-dasharray="5 4"' : ''}/>`;
const arrow = (d) => `<path d="${d}" fill="none" stroke="${MUTED}" stroke-width="1.5" marker-end="url(#ar)"/>`;
const badge = (x, y, n, fill = C.accent) => `<circle cx="${x}" cy="${y}" r="11" fill="${fill}"/>${text(x, y + 4.5, n, { fill: '#fff', size: 12, weight: 700, anchor: 'middle' })}`;
const lines = (x, y, arr, o = {}) => arr.map((s, i) => text(x, y + i * (o.gap || 17), s, o)).join('');

export const diagrams = {};

// 1. Roadmap
diagrams.roadmap = () => {
  const items = [
    ['1', 'Persiapan', 'migrate, buat data dummy,', 'login ke 6 akun uji'],
    ['2', 'Settings', 'Objects and Funds,', 'penanggung jawab, alokasi'],
    ['3', 'Reimbursement', 'Project, Office, Support,', 'campuran, void, kebijakan'],
    ['4', 'Cash Advance', 'Object, penyetuju otomatis,', 'dana, void'],
    ['5', 'CA Report', 'mengikuti Object CA,', 'dana menyesuaikan'],
    ['6', 'Pembersihan', 'hapus data dummy,', 'kembali ke konsep lama'],
  ];
  let b = text(24, 30, 'Urutan pengujian dari awal sampai akhir', { size: 16, weight: 700 });
  items.forEach((it, i) => {
    const col = i % 3, row = Math.floor(i / 3);
    const x = 24 + col * 244, y = 52 + row * 118;
    b += box(x, y, 216, 88, { stroke: i === 5 ? C.warn : C.accent, fill: i === 5 ? C.warnBg : C.accentBg });
    b += badge(x + 22, y + 24, it[0], i === 5 ? C.warn : C.accent);
    b += text(x + 42, y + 29, it[1], { size: 15, weight: 700 });
    b += text(x + 16, y + 54, it[2], { size: 12, fill: MUTED });
    b += text(x + 16, y + 71, it[3], { size: 12, fill: MUTED });
    if (col < 2) b += arrow(`M${x + 216} ${y + 44}H${x + 244}`);
  });
  return svg(760, 262, b);
};

// 2. Concept
diagrams.concept = () => {
  const steps = [
    ['Pemohon', 'Memilih Object', 'per baris: Office,', 'Project, atau Support'],
    ['Sistem', 'Menentukan penyetuju', 'otomatis dan', 'memeriksa dana'],
    ['Penyetuju Object', 'Project Admin, PM,', 'Ticket Lead, atau', 'kepala Office'],
    ['Approval Workflow', 'Langkah umum:', 'GA Verification,', 'Accounting, Cashier'],
    ['Dana', 'Berkurang saat', 'disetujui penuh;', 'kembali bila di-void'],
  ];
  let b = text(24, 30, 'Konsep Object dan dana dalam satu gambar', { size: 16, weight: 700 });
  steps.forEach((s, i) => {
    const x = 24 + i * 146, hi = i === 4;
    b += box(x, 56, 128, 132, { stroke: hi ? C.ok : C.accent, fill: hi ? C.okBg : '#fff', sw: hi ? 2 : 1.3 });
    b += text(x + 64, 82, s[0], { weight: 700, size: 13, anchor: 'middle' });
    b += `<line x1="${x + 14}" x2="${x + 114}" y1="92" y2="92" stroke="${LINE}"/>`;
    b += lines(x + 64, 114, s.slice(1), { size: 12, fill: MUTED, anchor: 'middle' });
    if (i < 4) b += arrow(`M${x + 128} 122H${x + 146}`);
  });
  b += text(24, 224, 'Pemohon tidak memilih penyetuju dan tidak melihat sisa dana. Dana hanya diperiksa bila Object punya alokasi.', { size: 12, fill: MUTED });
  b += text(24, 242, 'Mengaktifkan saklar di Settings mengubah dokumen BARU saja; dokumen lama tetap memakai alurnya.', { size: 12, fill: MUTED });
  return svg(760, 262, b);
};

// 3. Swimlane with demo names
diagrams.approvers = () => {
  const H = 44, ys = [96, 156, 216];
  const lane = (y, name, color, bg) => box(24, y - H / 2, 112, H, { fill: bg, stroke: color }) + text(80, y + 4.5, name, { weight: 700, anchor: 'middle' });
  const step = (x, y, w, label, sub, dash) => box(x, y - H / 2, w, H, { dash }) + text(x + w / 2, y - 3, label, { anchor: 'middle', weight: 600 }) + text(x + w / 2, y + 13, sub, { anchor: 'middle', size: 11, fill: MUTED });
  let b = text(24, 30, 'Object menentukan penyetuju; Approval Workflow sama untuk semua', { size: 16, weight: 700 });
  b += lane(ys[0], 'Project', C.project, C.projectBg) + step(156, ys[0], 160, 'Project Admin', 'Sari Demo') + step(336, ys[0], 160, 'Project Manager', 'Rudi Demo');
  b += lane(ys[1], 'Office', C.office, C.officeBg) + step(156, ys[1], 160, 'Penanggung jawab', 'Dewi Demo');
  b += lane(ys[2], 'Support', C.support, C.supportBg) + step(156, ys[2], 160, 'Ticket Lead', 'Gita Demo') + step(336, ys[2], 160, 'Support Manager', 'opsional', true);
  b += box(556, 58, 180, 200, { fill: C.finBg, stroke: C.fin, sw: 2 }) + text(646, 82, 'Approval Workflow', { weight: 700, anchor: 'middle' });
  b += box(574, 100, 144, 52) + text(646, 122, 'GA Verification', { anchor: 'middle', weight: 600 }) + text(646, 139, 'Fajar Demo (Finance)', { anchor: 'middle', size: 11, fill: MUTED });
  b += box(574, 176, 144, 52, { dash: true }) + text(646, 198, 'Accounting, Cashier', { anchor: 'middle', weight: 600 }) + text(646, 215, 'bila Anda tambahkan', { anchor: 'middle', size: 11, fill: MUTED });
  b += arrow('M316 96H336') + arrow('M496 96H556') + arrow('M316 156H556') + arrow('M316 216H336') + arrow('M496 216H556') + arrow('M646 152V176');
  b += text(24, 286, 'Garis putus-putus: langkah opsional. Pemohon yang juga penyetuju dilewati dan dicatat; orang yang sama hanya diminta sekali.', { size: 11.5, fill: MUTED });
  return svg(760, 304, b);
};

// 4. Fund lifecycle
diagrams.lifecycle = () => {
  const lane = (y, title, items, color) => {
    let s = text(24, y - 14, title, { weight: 700, size: 14, fill: color });
    items.forEach((it, i) => {
      const x = 24 + i * 182;
      s += box(x, y, 158, 78, { stroke: it.c || LINE, fill: it.bg || '#fff', sw: it.c ? 2 : 1.3 });
      s += text(x + 79, y + 26, it.t, { weight: 700, anchor: 'middle' });
      s += lines(x + 79, y + 46, it.l, { size: 11.5, fill: MUTED, anchor: 'middle', gap: 15 });
      if (i < items.length - 1) s += arrow(`M${x + 158} ${y + 39}H${x + 182}`);
    });
    return s;
  };
  let b = text(24, 28, 'Kapan dana berubah', { size: 16, weight: 700 });
  b += lane(76, 'Reimbursement', [
    { t: 'Submitted', l: ['dana belum berubah', 'tampil: menunggu'] },
    { t: 'In review', l: ['penyetuju bekerja', 'dana belum berubah'] },
    { t: 'Approved', l: ['dana BERKURANG', 'sisa dicek ulang'], c: C.ok, bg: C.okBg },
    { t: 'Void (Finance)', l: ['dana KEMBALI', 'alasan wajib'], c: C.bad, bg: C.badBg },
  ], C.accent);
  b += lane(206, 'Cash Advance dan Report', [
    { t: 'CA Approved', l: ['dana berkurang', 'sebesar uang muka'], c: C.ok, bg: C.okBg },
    { t: 'CAR dikirim', l: ['dana tetap', 'sebesar uang muka'] },
    { t: 'CAR disetujui', l: ['CA settled: dana =', 'yang dibelanjakan'], c: C.ok, bg: C.okBg },
    { t: 'CA di-void', l: ['hanya bila belum', 'punya laporan'], c: C.bad, bg: C.badBg },
  ], C.accent);
  b += box(24, 322, 712, 62, { fill: C.warnBg, stroke: C.warn });
  b += text(40, 346, 'Dibelanjakan lebih sedikit dari uang muka: selisihnya kembali ke dana (refund).', { size: 12.5 });
  b += text(40, 366, 'Dibelanjakan lebih banyak: dana berkurang sebesar kelebihan (claim) dan dapat di bawah nol, ditandai Over fund allocation.', { size: 12.5 });
  return svg(760, 404, b);
};

// 5. Settings map
diagrams.settings = () => {
  const row = (y, cells, o = {}) => cells.map(([x, t, bold]) => text(x, y, t, { size: 11.5, weight: bold ? 700 : 400, fill: o.fill || MUTED })).join('');
  const check = (x, y, on) => box(x, y - 11, 14, 14, { rx: 3, stroke: on ? C.ok : LINE, fill: on ? C.okBg : '#fff' }) + (on ? text(x + 7, y, 'v', { size: 11, weight: 700, fill: C.ok, anchor: 'middle' }) : '');
  const btn = (x, y, w, t) => box(x, y - 14, w, 22, { rx: 5, stroke: C.accent, fill: C.accentBg }) + text(x + w / 2, y + 1, t, { size: 11, weight: 700, fill: C.accent, anchor: 'middle' });
  let b = text(24, 28, 'Peta halaman Settings: Objects and Funds', { size: 16, weight: 700 });
  b += box(24, 44, 712, 520, { stroke: LINE });
  b += text(40, 68, 'Reimbursement  >  Settings  >  Objects and Funds', { size: 12, fill: MUTED });

  // 1. checklist
  b += box(40, 80, 680, 72, { fill: '#f6f7f9' }) + badge(40, 80, '1');
  b += text(60, 102, 'Setup checklist', { weight: 700 });
  b += row(122, [[60, '✓ Switch on'], [190, '✓ Every office has a responsible person'], [470, '! Fallback approvers'], [610, '✓ Funds']]);
  b += row(140, [[60, 'Tanda hijau = siap. Tanda seru = masih ada yang kurang, lengkap dengan namanya.']], { fill: MUTED });

  // 2. flow and policy
  b += box(40, 162, 680, 130) + badge(40, 162, '2');
  b += text(60, 184, 'Flow and policy', { weight: 700 });
  b += check(60, 204, true) + text(82, 204, 'Use Objects and funds for new reimbursements', { size: 11.5, fill: MUTED });
  b += check(60, 224, true) + text(82, 224, 'Use Objects and funds for new cash advances (and their reports)', { size: 11.5, fill: MUTED });
  b += row(246, [[60, 'Project approvals:', true], [180, 'Admin ✓   Manager ✓   Order   Fallback role / person'], [470, 'Support approvals:', true], [590, 'Lead ✓   Fallback']]);
  b += row(270, [[60, 'When an amount exceeds the remaining fund:', true], [330, 'Project [Warn]   Office [Warn]   Support [Warn]']]) + btn(588, 272, 120, 'Save flow and policy');

  // 3. offices
  b += box(40, 302, 680, 112) + badge(40, 302, '3');
  b += text(60, 324, 'Office funds and responsible person', { weight: 700 });
  b += row(346, [[60, 'Office', true], [230, 'Monthly fund', true], [390, 'Responsible role', true], [540, 'Responsible person', true]], { fill: INK });
  b += row(366, [[60, 'Demo Jakarta'], [270, '5.000.000'], [390, 'HO GA'], [540, 'Dewi Demo']]) + check(230, 366, true);
  b += row(386, [[60, 'Demo Bandung'], [270, '-'], [390, '-'], [540, 'Dewi Demo']]) + check(230, 386, false);
  b += row(406, [[60, 'Demo Surabaya'], [270, '-'], [390, '-'], [540, '(sengaja kosong)']]) + check(230, 406, false) + btn(626, 322, 84, 'Save offices');

  // 4. allocations
  b += box(40, 424, 680, 130) + badge(40, 424, '4');
  b += text(60, 446, 'Project and ticket fund allocations', { weight: 700 });
  b += row(468, [[60, 'Object', true], [250, 'Category', true], [370, 'Used', true], [450, 'Remaining', true], [560, 'Allocation', true]], { fill: INK });
  b += row(488, [[60, 'Demo ERP Alpha'], [250, 'Transport'], [370, '0'], [450, '10.000.000'], [560, '10000000']]) + btn(650, 490, 56, 'Update');
  b += row(508, [[60, 'DEMO-T-0001'], [250, 'Accommodation'], [370, '0'], [450, '3.000.000'], [560, '3000000']]) + btn(650, 510, 56, 'Update');
  b += row(534, [[60, '[Project v]  [project]  [category]  [amount]']]) + btn(588, 536, 120, 'Add allocation');
  return svg(760, 584, b);
};

// 6. Form schematic
diagrams.form = () => {
  let b = text(24, 28, 'Form pengajuan saat Objects aktif (skematik)', { size: 16, weight: 700 });
  b += box(24, 44, 712, 468, { stroke: LINE });
  b += text(40, 68, 'My Reimbursement  >  Submit Reimbursement', { size: 12, fill: MUTED });
  b += box(40, 80, 330, 30, { fill: '#f6f7f9' }) + text(52, 100, 'Reimbursement Title *  e.g. Client visit expenses', { size: 11.5, fill: MUTED });
  b += box(390, 80, 330, 30, { fill: '#f6f7f9' }) + text(402, 100, 'Supporting Document *  https://drive.google.com/...', { size: 11.5, fill: MUTED });
  // items table
  b += box(40, 124, 680, 150, { stroke: LINE });
  b += `<rect x="40" y="124" width="680" height="28" rx="8" fill="#f1f3f6"/>`;
  b += text(52, 143, 'No   Description & Receipt Date', { size: 11, weight: 700, fill: MUTED }) + text(330, 143, 'OBJECT', { size: 11, weight: 700, fill: MUTED }) + text(520, 143, 'Receipt No.', { size: 11, weight: 700, fill: MUTED }) + text(640, 143, 'Amount (IDR)', { size: 11, weight: 700, fill: MUTED });
  b += box(52, 162, 250, 26, { fill: '#fff' }) + text(62, 179, 'e.g. Taxi fare to client office', { size: 11, fill: MUTED });
  b += box(52, 196, 118, 24) + text(60, 212, 'From date', { size: 10.5, fill: MUTED }) + box(184, 196, 118, 24) + text(192, 212, 'To date', { size: 10.5, fill: MUTED });
  b += box(320, 160, 180, 26) + text(330, 177, 'Project                      v', { size: 11.5 });
  b += box(320, 192, 180, 26) + text(330, 209, 'Demo ERP Alpha         v', { size: 11.5 });
  b += box(320, 224, 180, 26, { dash: true }) + text(330, 241, 'Fund category: Transport v', { size: 11 });
  b += box(520, 160, 90, 26) + text(530, 177, 'INV-001', { size: 11.5 }) + box(630, 160, 80, 26) + text(702, 177, '2500000', { size: 11.5, anchor: 'end' });
  b += badge(516, 173, '1') + badge(516, 237, '2', C.support);
  // preview
  b += text(40, 300, 'REVIEWED BY (ASSIGNED AUTOMATICALLY)', { size: 11, weight: 700, fill: MUTED }) + badge(300, 296, '3');
  b += box(40, 308, 680, 76, { fill: '#f6f7f9' });
  [['1. Project Admin: Demo ERP Alpha', 52, 330], ['2. Project Manager: Demo ERP Alpha', 258, 330], ['3. GA Verification', 484, 330]].forEach(([t, x, y]) => { b += box(x, y - 4, 196, 24, { fill: '#fff' }) + text(x + 8, y + 12, t, { size: 11 }); });
  b += text(52, 372, 'Pemohon tidak memilih penyetuju. Daftar mengikuti Object pada setiap baris.', { size: 11, fill: MUTED });
  // reason
  b += box(40, 396, 680, 64, { fill: C.warnBg, stroke: C.warn }) + badge(40, 396, '4', C.warn);
  b += text(52, 416, 'Reason for exceeding the fund *  (muncul hanya bila nominal melebihi dana)', { size: 11.5, weight: 700, fill: C.warn });
  b += box(52, 424, 656, 28, { fill: '#fff' }) + text(62, 442, 'Explain why this amount is needed (at least 10 characters)', { size: 11, fill: MUTED });
  b += box(40, 470, 130, 28, { fill: C.accent, stroke: C.accent }) + text(105, 489, 'Submit Request', { size: 12, weight: 700, fill: '#fff', anchor: 'middle' });
  b += badge(184, 484, '5');
  return svg(760, 524, b);
};

// 7. Fund tracker step chart (Project / Transport)
diagrams.tracker = () => {
  const pts = [
    ['Awal', 10.0], ['RB-01\napprove', 7.5], ['RB-02\nvoid', 10.0], ['RB-06\nover', -2.0], ['RB-06\nvoid', 10.0],
    ['CA-01\napprove', 7.5], ['CAR-01\nsettled', 8.0], ['CA-03\napprove', 7.0], ['CAR-03\nsettled', 6.6], ['CA-04\napprove', 6.1], ['CA-04\nvoid', 6.6],
  ];
  const L = 70, R = 730, T = 60, Bm = 300, minV = -3, maxV = 11;
  const X = (i) => L + (i + 0.5) * ((R - L) / pts.length);
  const Y = (v) => Bm - ((v - minV) / (maxV - minV)) * (Bm - T);
  let b = text(24, 28, 'Sisa dana Project Demo ERP Alpha, kategori Transport (juta rupiah)', { size: 16, weight: 700 });
  for (let v = -2; v <= 10; v += 2) {
    b += `<line x1="${L}" x2="${R}" y1="${Y(v)}" y2="${Y(v)}" stroke="${v === 0 ? INK : '#e3e7ed'}" stroke-width="${v === 0 ? 1.4 : 1}"/>` + text(L - 8, Y(v) + 4, v, { anchor: 'end', size: 11, fill: MUTED });
  }
  b += text(L - 8, T - 12, 'Rp juta', { anchor: 'end', size: 11, fill: MUTED });
  let path = '';
  pts.forEach((p, i) => {
    const x = X(i), y = Y(p[1]);
    path += (i === 0 ? 'M' : 'L') + `${x} ${Y(i === 0 ? p[1] : pts[i - 1][1])}` + (i === 0 ? '' : `L${x} ${y}`);
  });
  b += `<path d="${path}" fill="none" stroke="${C.accent}" stroke-width="2"/>`;
  pts.forEach((p, i) => {
    const x = X(i), y = Y(p[1]), neg = p[1] < 0;
    b += `<circle cx="${x}" cy="${y}" r="5" fill="${neg ? C.bad : C.accent}"/>`;
    b += text(x + (neg ? 0 : 12), y + (neg ? 20 : -9), p[1].toFixed(1), { anchor: neg ? 'middle' : 'start', size: 11, weight: 700, fill: neg ? C.bad : INK, halo: true });
    p[0].split('\n').forEach((ln, k) => { b += text(x, Bm + 18 + k * 14, ln, { anchor: 'middle', size: 10.5, fill: MUTED }); });
  });
  b += text(24, 352, 'Titik merah: melebihi alokasi (kebijakan Warn) lalu dikembalikan lewat void. Nilai akhir yang diharapkan: 6,6 juta.', { size: 11.5, fill: MUTED });
  return svg(760, 372, b);
};

// 8. Budget check panel (approver side)
diagrams.budget = () => {
  const edges = [300, 380, 452, 566, 714];
  const heads = ['ALLOCATION', 'APPROVED', 'WAITING', 'THIS DOCUMENT', 'AFTER THIS DOCUMENT'];
  let b = text(24, 28, 'Panel Budget check yang dilihat penyetuju', { size: 16, weight: 700 });
  b += box(24, 44, 712, 300, { stroke: LINE });
  b += text(44, 74, 'Budget check', { size: 16, weight: 700 });
  b += text(44, 94, 'What is left of the fund if you approve this reimbursement.', { size: 11.5, fill: MUTED });
  b += box(596, 58, 120, 26, { rx: 13, fill: C.okBg, stroke: C.ok }) + text(656, 76, 'Within the fund', { size: 11.5, weight: 700, fill: C.ok, anchor: 'middle' });
  b += '<rect x="40" y="110" width="680" height="30" rx="6" fill="#f1f3f6"/>';
  b += text(52, 130, 'FUND', { size: 10, weight: 700, fill: MUTED });
  heads.forEach((t, i) => { b += text(edges[i], 130, t, { size: 10, weight: 700, fill: MUTED, anchor: 'end' }); });
  b += text(52, 162, 'Demo ERP Alpha, Transport', { size: 12.5, weight: 700 }) + text(52, 178, 'Project', { size: 11, fill: MUTED });
  ['10.000.000', '0', '0', '2.500.000', '7.500.000'].forEach((t, i) => { b += text(edges[i], 168, t, { size: 12.5, anchor: 'end', weight: i >= 3 ? 700 : 400 }); });
  b += '<line x1="40" x2="720" y1="190" y2="190" stroke="#e3e7ed"/>';
  b += text(52, 218, 'DEMO-T-0001, Transport', { size: 12.5, weight: 700 }) + text(52, 234, 'Support', { size: 11, fill: MUTED });
  ['1.500.000', '600.000', '0', '1.900.000', '-1.000.000'].forEach((t, i) => { b += text(edges[i], 224, t, { size: 12.5, anchor: 'end', weight: i >= 3 ? 700 : 400, fill: i === 4 ? C.bad : INK }); });
  b += text(714, 241, 'Over the fund. The requester gave a reason.', { size: 10.5, anchor: 'end', fill: C.bad });
  b += '<line x1="40" x2="720" y1="250" y2="250" stroke="#e3e7ed"/>';
  b += text(52, 274, 'DEMO-BDG - Demo Bandung (Office) has no fund set, so its budget is not checked.', { size: 11.5, fill: MUTED });
  b += text(52, 304, 'Approved = dokumen lain yang sudah disetujui.  Waiting = dokumen lain yang masih menunggu (informasi, belum mengurangi).', { size: 10.5, fill: MUTED });
  b += text(52, 322, 'Pada kebijakan Block, baris merah berarti persetujuan terakhir akan ditolak.', { size: 10.5, fill: MUTED });
  return svg(760, 366, b);
};
