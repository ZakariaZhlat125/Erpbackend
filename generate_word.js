/**
 * Markdown → Beautiful Word Document (Arabic RTL)
 * MiddleEast ERP - System Analysis Report
 */

const {
  Document, Packer, Paragraph, Table, TableRow, TableCell, TextRun,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType,
  Header, Footer, PageBreak, convertInchesToTwip,
} = require('docx');
const fs = require('fs');

// ── COLOR PALETTE ──────────────────────────────────────────────
const C = {
  navy:       '1B3A6B',
  blue:       '2471A3',
  lightBlue:  '5DADE2',
  accent:     'E67E22',
  success:    '1E8449',
  bg1:        'EBF5FB',
  bg2:        'FEF9E7',
  white:      'FFFFFF',
  dark:       '1C2833',
  textDark:   '2C3E50',
  textGray:   '5D6D7E',
  code:       'F2F3F4',
  border:     'AED6F1',
  divider:    'D5D8DC',
};

// ── HELPERS ────────────────────────────────────────────────────
function stripEmoji(t) {
  return (t || '').replace(/[\u{1F000}-\u{1FFFF}]|[\u{2600}-\u{27BF}]|[\uFE00-\uFE0F]/gu, '').trim();
}

function parseInline(text, base = {}) {
  if (!text || !text.trim()) return [new TextRun({ text: '', font: 'Arial', ...base })];
  const clean = stripEmoji(text);
  const parts = clean.split(/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/g);
  const runs = [];
  for (const part of parts) {
    if (!part) continue;
    if (part.startsWith('**') && part.endsWith('**')) {
      runs.push(new TextRun({ text: part.slice(2, -2), bold: true, font: 'Arial', ...base }));
    } else if (part.startsWith('*') && part.endsWith('*')) {
      runs.push(new TextRun({ text: part.slice(1, -1), italics: true, font: 'Arial', ...base }));
    } else if (part.startsWith('`') && part.endsWith('`')) {
      runs.push(new TextRun({ text: part.slice(1, -1), font: 'Courier New', size: 18, ...base }));
    } else {
      runs.push(new TextRun({ text: part, font: 'Arial', ...base }));
    }
  }
  return runs.length ? runs : [new TextRun({ text: clean, font: 'Arial', ...base })];
}

// ── ELEMENT FACTORIES ──────────────────────────────────────────
function h1(text) {
  return new Paragraph({
    children: [new TextRun({ text: stripEmoji(text), bold: true, color: C.white, size: 56, font: 'Arial' })],
    alignment: AlignmentType.CENTER,
    bidirectional: true,
    shading: { type: ShadingType.CLEAR, fill: C.navy },
    spacing: { before: 400, after: 300 },
    border: { bottom: { style: BorderStyle.THICK, size: 8, color: C.accent } },
  });
}

function h2(text) {
  return new Paragraph({
    children: [new TextRun({ text: stripEmoji(text), bold: true, color: C.white, size: 36, font: 'Arial' })],
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    shading: { type: ShadingType.CLEAR, fill: C.navy },
    spacing: { before: 500, after: 200 },
    border: { left: { style: BorderStyle.THICK, size: 14, color: C.accent } },
    indent: { left: convertInchesToTwip(0.15) },
  });
}

function h3(text) {
  return new Paragraph({
    children: [new TextRun({ text: stripEmoji(text), bold: true, color: C.white, size: 28, font: 'Arial' })],
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    shading: { type: ShadingType.CLEAR, fill: C.blue },
    spacing: { before: 360, after: 140 },
    indent: { left: convertInchesToTwip(0.1) },
  });
}

function h4(text) {
  return new Paragraph({
    children: [
      new TextRun({ text: '▌ ', color: C.accent, bold: true, font: 'Arial', size: 24 }),
      new TextRun({ text: stripEmoji(text), bold: true, color: C.navy, size: 24, font: 'Arial' }),
    ],
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    border: { bottom: { style: BorderStyle.SINGLE, size: 2, color: C.lightBlue } },
    spacing: { before: 280, after: 120 },
  });
}

function para(text) {
  if (!text.trim()) return new Paragraph({ children: [], spacing: { after: 80 } });
  return new Paragraph({
    children: parseInline(text, { color: C.textDark, size: 22 }),
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    spacing: { before: 60, after: 100 },
  });
}

function blockquote(text) {
  const clean = text.replace(/^>\s*/, '');
  return new Paragraph({
    children: parseInline(clean, { color: C.navy, size: 20, italics: true }),
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    shading: { type: ShadingType.CLEAR, fill: C.bg1 },
    border: {
      right: { style: BorderStyle.THICK, size: 10, color: C.accent },
      left:  { style: BorderStyle.SINGLE, size: 2, color: C.border },
      top:   { style: BorderStyle.SINGLE, size: 1, color: C.border },
      bottom:{ style: BorderStyle.SINGLE, size: 1, color: C.border },
    },
    indent: { left: convertInchesToTwip(0.25), right: convertInchesToTwip(0.25) },
    spacing: { before: 120, after: 120 },
  });
}

function listItem(text) {
  const clean = text.replace(/^[-*+]\s+/, '').replace(/^[├└│─]+\s*/, '');
  return new Paragraph({
    children: [
      new TextRun({ text: '◆  ', color: C.accent, bold: true, font: 'Arial', size: 20 }),
      ...parseInline(clean, { color: C.textDark, size: 22 }),
    ],
    alignment: AlignmentType.RIGHT,
    bidirectional: true,
    spacing: { before: 40, after: 40 },
    indent: { right: convertInchesToTwip(0.25) },
  });
}

function codeBlock(lines) {
  const elements = [];
  // top spacer
  elements.push(new Paragraph({
    children: [],
    shading: { type: ShadingType.CLEAR, fill: C.code },
    spacing: { before: 120, after: 0 },
    border: { top: { style: BorderStyle.SINGLE, size: 2, color: C.border }, left: { style: BorderStyle.SINGLE, size: 2, color: C.border }, right: { style: BorderStyle.SINGLE, size: 2, color: C.border } },
    indent: { left: convertInchesToTwip(0.1), right: convertInchesToTwip(0.1) },
  }));
  for (const line of lines) {
    elements.push(new Paragraph({
      children: [new TextRun({ text: line || ' ', font: 'Courier New', size: 17, color: C.dark })],
      shading: { type: ShadingType.CLEAR, fill: C.code },
      spacing: { before: 0, after: 0 },
      border: { left: { style: BorderStyle.SINGLE, size: 2, color: C.border }, right: { style: BorderStyle.SINGLE, size: 2, color: C.border } },
      indent: { left: convertInchesToTwip(0.15), right: convertInchesToTwip(0.15) },
    }));
  }
  elements.push(new Paragraph({
    children: [],
    shading: { type: ShadingType.CLEAR, fill: C.code },
    spacing: { before: 0, after: 120 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 2, color: C.border }, left: { style: BorderStyle.SINGLE, size: 2, color: C.border }, right: { style: BorderStyle.SINGLE, size: 2, color: C.border } },
    indent: { left: convertInchesToTwip(0.1), right: convertInchesToTwip(0.1) },
  }));
  return elements;
}

function hr() {
  return new Paragraph({
    children: [],
    border: { bottom: { style: BorderStyle.SINGLE, size: 3, color: C.divider } },
    spacing: { before: 160, after: 160 },
  });
}

function spacer(size = 80) {
  return new Paragraph({ children: [], spacing: { after: size } });
}

// ── TABLE BUILDER ──────────────────────────────────────────────
function buildTable(rows) {
  const data = rows.filter(r => !r.every(c => /^[-: ]+$/.test(c)));
  if (!data.length) return null;
  const cols = Math.max(...data.map(r => r.length));
  const colW  = Math.floor(8900 / cols);

  const tableRows = data.map((row, ri) => {
    const isHeader = ri === 0;
    const cells = Array.from({ length: cols }, (_, ci) => {
      const cellTxt = (row[ci] || '').trim();
      const clean   = cellTxt.replace(/\*\*/g, '');
      const runs    = isHeader
        ? [new TextRun({ text: stripEmoji(clean), bold: true, color: C.white, size: 20, font: 'Arial' })]
        : parseInline(cellTxt, { color: C.textDark, size: 19 });
      return new TableCell({
        children: [new Paragraph({
          children: runs,
          alignment: AlignmentType.CENTER,
          bidirectional: true,
          spacing: { before: 80, after: 80 },
        })],
        shading: {
          type: ShadingType.CLEAR,
          fill: isHeader ? C.navy : (ri % 2 === 0 ? C.white : C.bg1),
        },
        margins: {
          top:    convertInchesToTwip(0.05),
          bottom: convertInchesToTwip(0.05),
          left:   convertInchesToTwip(0.08),
          right:  convertInchesToTwip(0.08),
        },
        borders: {
          top:    { style: BorderStyle.SINGLE, size: 1, color: C.border },
          bottom: { style: BorderStyle.SINGLE, size: 1, color: C.border },
          left:   { style: BorderStyle.SINGLE, size: 1, color: C.border },
          right:  { style: BorderStyle.SINGLE, size: 1, color: C.border },
        },
        width: { size: colW, type: WidthType.DXA },
      });
    });
    return new TableRow({ children: cells });
  });

  return new Table({
    rows: tableRows,
    width: { size: 100, type: WidthType.PERCENTAGE },
  });
}

// ── COVER PAGE ─────────────────────────────────────────────────
function coverPage() {
  const meta = [
    ['التقنية',      'Laravel 11 (PHP 8.2+)'],
    ['المصادقة',     'Laravel Sanctum'],
    ['الصلاحيات',    'Spatie Permission'],
    ['نوع النظام',   'SaaS Multi-Tenant'],
    ['تاريخ التحليل','مايو 2026'],
  ];

  const metaTable = new Table({
    rows: meta.map(([k, v], i) => new TableRow({
      children: [
        new TableCell({
          children: [new Paragraph({ children: [new TextRun({ text: v, font: 'Arial', size: 22 })], alignment: AlignmentType.CENTER })],
          shading: { type: ShadingType.CLEAR, fill: i % 2 === 0 ? C.white : C.bg1 },
          margins: { top: convertInchesToTwip(0.08), bottom: convertInchesToTwip(0.08), left: convertInchesToTwip(0.12), right: convertInchesToTwip(0.12) },
          borders: { top: { style: BorderStyle.SINGLE, size: 1, color: C.border }, bottom: { style: BorderStyle.SINGLE, size: 1, color: C.border }, left: { style: BorderStyle.SINGLE, size: 1, color: C.border }, right: { style: BorderStyle.SINGLE, size: 1, color: C.border } },
          width: { size: 5500, type: WidthType.DXA },
        }),
        new TableCell({
          children: [new Paragraph({ children: [new TextRun({ text: k, font: 'Arial', bold: true, color: C.white, size: 22 })], alignment: AlignmentType.RIGHT, bidirectional: true })],
          shading: { type: ShadingType.CLEAR, fill: C.navy },
          margins: { top: convertInchesToTwip(0.08), bottom: convertInchesToTwip(0.08), left: convertInchesToTwip(0.12), right: convertInchesToTwip(0.12) },
          borders: { top: { style: BorderStyle.SINGLE, size: 1, color: C.border }, bottom: { style: BorderStyle.SINGLE, size: 1, color: C.border }, left: { style: BorderStyle.SINGLE, size: 1, color: C.border }, right: { style: BorderStyle.SINGLE, size: 1, color: C.border } },
          width: { size: 3500, type: WidthType.DXA },
        }),
      ],
    })),
    width: { size: 65, type: WidthType.PERCENTAGE },
  });

  return [
    spacer(1440),
    new Paragraph({
      children: [new TextRun({ text: 'MiddleEast ERP', bold: true, color: C.white, size: 80, font: 'Arial' })],
      alignment: AlignmentType.CENTER,
      shading: { type: ShadingType.CLEAR, fill: C.navy },
      spacing: { before: 300, after: 0 },
      border: { top: { style: BorderStyle.THICK, size: 10, color: C.accent } },
    }),
    new Paragraph({
      children: [new TextRun({ text: 'نظام ERP سحابي متعدد المستأجرين', bold: true, color: C.white, size: 44, font: 'Arial' })],
      alignment: AlignmentType.CENTER,
      bidirectional: true,
      shading: { type: ShadingType.CLEAR, fill: C.navy },
      spacing: { before: 0, after: 0 },
    }),
    new Paragraph({
      children: [new TextRun({ text: 'SaaS Multi-Tenant ERP System', color: C.lightBlue, size: 30, font: 'Arial', italics: true })],
      alignment: AlignmentType.CENTER,
      shading: { type: ShadingType.CLEAR, fill: C.navy },
      spacing: { before: 60, after: 0 },
    }),
    new Paragraph({
      children: [new TextRun({ text: ' ', size: 8, font: 'Arial' })],
      shading: { type: ShadingType.CLEAR, fill: C.accent },
      border: { bottom: { style: BorderStyle.THICK, size: 6, color: C.accent } },
      spacing: { before: 0, after: 0 },
    }),
    new Paragraph({ children: [new TextRun({ text: ' ', size: 8 })], shading: { type: ShadingType.CLEAR, fill: C.navy }, spacing: { before: 0, after: 0 } }),
    spacer(640),
    metaTable,
    spacer(800),
    new Paragraph({
      children: [new TextRun({ text: 'وثيقة التحليل الشامل', color: C.textGray, size: 24, font: 'Arial', italics: true })],
      alignment: AlignmentType.CENTER,
      bidirectional: true,
    }),
    new Paragraph({
      children: [new PageBreak()],
    }),
  ];
}

// ── MARKDOWN PARSER ────────────────────────────────────────────
function parseMarkdown(md) {
  const lines  = md.split('\n');
  const elems  = [];
  let inCode   = false;
  let codeLines = [];
  let inTable  = false;
  let tableRows = [];

  const flushTable = () => {
    if (tableRows.length) {
      const t = buildTable(tableRows);
      if (t) { elems.push(t); elems.push(spacer(140)); }
    }
    tableRows = [];
    inTable   = false;
  };

  const flushCode = () => {
    if (codeLines.length) {
      elems.push(...codeBlock(codeLines));
    }
    codeLines = [];
    inCode    = false;
  };

  for (let i = 0; i < lines.length; i++) {
    const raw = lines[i];
    const t   = raw.trim();

    // ── code block toggle
    if (t.startsWith('```')) {
      if (inCode)  { flushCode(); }
      else         { if (inTable) flushTable(); inCode = true; }
      continue;
    }
    if (inCode) { codeLines.push(raw); continue; }

    // ── table rows
    if (t.startsWith('|') && t.endsWith('|')) {
      const cells = t.slice(1, -1).split('|').map(c => c.trim());
      if (!cells.every(c => /^[-: ]+$/.test(c))) tableRows.push(cells);
      inTable = true;
      continue;
    }
    if (inTable) flushTable();

    // ── headings
    if      (t.startsWith('#### ')) { elems.push(h4(t.slice(5))); }
    else if (t.startsWith('### '))  { elems.push(h3(t.slice(4))); }
    else if (t.startsWith('## '))   { elems.push(h2(t.slice(3))); }
    else if (t.startsWith('# '))    { elems.push(h1(t.slice(2))); }
    // ── hr
    else if (/^---+$/.test(t))      { elems.push(hr()); }
    // ── blockquote
    else if (t.startsWith('> '))    { elems.push(blockquote(t)); }
    // ── list
    else if (/^[-*+]\s/.test(t) || /^[├└│]/.test(t)) { elems.push(listItem(t)); }
    // ── empty
    else if (!t)                    { elems.push(spacer(80)); }
    // ── paragraph
    else                            { elems.push(para(t)); }
  }

  if (inTable) flushTable();
  if (inCode)  flushCode();

  return elems;
}

// ── MAIN ───────────────────────────────────────────────────────
async function main() {
  console.log('Reading SYSTEM_ANALYSIS.md ...');
  const md = fs.readFileSync('SYSTEM_ANALYSIS.md', 'utf8');

  console.log('Parsing content ...');
  const content = parseMarkdown(md);

  console.log('Building Word document ...');
  const doc = new Document({
    sections: [{
      properties: {
        page: {
          margin: {
            top:    convertInchesToTwip(1.0),
            bottom: convertInchesToTwip(1.0),
            left:   convertInchesToTwip(1.1),
            right:  convertInchesToTwip(1.1),
          },
        },
      },
      headers: {
        default: new Header({
          children: [new Paragraph({
            children: [new TextRun({ text: 'MiddleEast ERP  |  وثيقة التحليل الشامل', font: 'Arial', size: 18, color: C.textGray })],
            alignment: AlignmentType.LEFT,
            border: { bottom: { style: BorderStyle.SINGLE, size: 1, color: C.divider } },
          })],
        }),
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            children: [new TextRun({ text: 'SaaS Multi-Tenant ERP  |  مايو 2026', font: 'Arial', size: 16, color: C.textGray })],
            alignment: AlignmentType.RIGHT,
            bidirectional: true,
            border: { top: { style: BorderStyle.SINGLE, size: 1, color: C.divider } },
          })],
        }),
      },
      children: [
        ...coverPage(),
        ...content,
      ],
    }],
  });

  const buffer = await Packer.toBuffer(doc);
  const out = 'SYSTEM_ANALYSIS.docx';
  fs.writeFileSync(out, buffer);
  const size = (fs.statSync(out).size / 1024).toFixed(1);
  console.log(`Done! Saved: ${out}  (${size} KB)  |  Elements: ${content.length}`);
}

main().catch(e => { console.error(e); process.exit(1); });
