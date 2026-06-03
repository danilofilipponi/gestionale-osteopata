import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { execFileSync } from "node:child_process";

const source = "C:\\Users\\Utente\\Desktop\\Comuni.xlsx";
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), "audit-comuni-"));
execFileSync("powershell.exe", [
  "-NoProfile",
  "-Command",
  `Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::ExtractToDirectory('${source.replaceAll("'", "''")}','${tmp.replaceAll("'", "''")}')`,
]);

const xml = fs.readFileSync(path.join(tmp, "xl", "worksheets", "sheet1.xml"), "utf8");
const rows = [...xml.matchAll(/<row\b[^>]*>([\s\S]*?)<\/row>/g)].map((m) => m[1]);

function decode(value = "") {
  return value
    .replaceAll("&amp;", "&")
    .replaceAll("&lt;", "<")
    .replaceAll("&gt;", ">")
    .replaceAll("&quot;", '"')
    .replaceAll("&apos;", "'");
}

function cellValue(cell) {
  const inline = cell.match(/<is><t[^>]*>([\s\S]*?)<\/t><\/is>/);
  if (inline) return decode(inline[1]).trim();
  const raw = cell.match(/<v>([\s\S]*?)<\/v>/);
  return raw ? decode(raw[1]).trim() : "";
}

function parseRow(row) {
  const values = [];
  for (const cell of row.matchAll(/<c\b[^>]*>([\s\S]*?)<\/c>/g)) values.push(cellValue(cell[0]));
  return values;
}

const [headers, ...body] = rows.map(parseRow);
const records = body
  .map((r) => ({
    id: r[0],
    comune: r[1],
    provincia: r[2],
    codiceCatastale: r[3],
    cap: r[4],
    regione: r[5],
  }))
  .filter((r) => r.comune);

const validProvince = new Set("AG AL AN AO AP AQ AR AT AV BA BG BI BL BN BO BR BS BT BZ CA CB CE CH CL CN CO CR CS CT CZ EN FC FE FG FI FM FR GE GO GR IM IS KR LC LE LI LO LT LU MB MC ME MI MN MO MS MT NA NO NU OR PA PC PD PE PG PI PN PO PR PT PU PV PZ RA RC RE RG RI RM RN RO SA SI SO SP SR SS SU SV TA TE TN TO TP TR TS TV UD VA VB VC VE VI VR VT VV".split(" "));
const byKey = new Map();
const duplicates = [];
const invalidCaps = [];
const suspiciousCaps = [];
const missingCadastral = [];
const invalidCadastral = [];
const invalidProvince = [];

for (const r of records) {
  const key = `${r.comune}|${r.provincia}`;
  if (byKey.has(key)) duplicates.push(r);
  byKey.set(key, r);
  if (!/^\d{5}$/.test(r.cap)) invalidCaps.push(r);
  if (["00000", "12345"].includes(r.cap)) suspiciousCaps.push(r);
  if (!r.codiceCatastale) missingCadastral.push(r);
  else if (!/^[A-Z][0-9]{3}$/.test(r.codiceCatastale)) invalidCadastral.push(r);
  if (!validProvince.has(r.provincia)) invalidProvince.push(r);
}

const find = (name, province) => records.find((r) => r.comune === name && (!province || r.provincia === province));
const expected = [
  ["MILANO", "MI", "F205"],
  ["ROMA", "RM", "H501"],
  ["TORINO", "TO", "L219"],
  ["MONZA", "MB", "F704"],
  ["BERGAMO", "BG", "A794"],
  ["COMO", "CO", "C933"],
  ["LECCO", "LC", "E507"],
  ["CAGLIARI", "CA", "B354"],
  ["CITTA' SANT'ANGELO", "PE", "C750"],
  ["MISILISCEMI", "TP", "M432"],
  ["MAPPANO", "TO", "M316"],
  ["VALSAMOGGIA", "BO", "M320"],
  ["MONTEMAGNO MONFERRATO", "AT", "F556"],
];

const expectedResults = expected.map(([name, province, cadastral]) => {
  const r = find(name, province);
  return {
    name,
    province,
    found: Boolean(r),
    codiceCatastale: r?.codiceCatastale ?? null,
    expectedCodiceCatastale: cadastral,
    ok: Boolean(r && r.codiceCatastale === cadastral),
    cap: r?.cap ?? null,
  };
});

const provinceCounts = {};
const regionCounts = {};
for (const r of records) {
  provinceCounts[r.provincia] = (provinceCounts[r.provincia] ?? 0) + 1;
  regionCounts[r.regione] = (regionCounts[r.regione] ?? 0) + 1;
}
const italianRecords = records.filter((r) => r.provincia !== "EE" && r.regione);
const activeLikeRecords = italianRecords.filter((r) => r.codiceCatastale && /^\d{5}$/.test(r.cap) && !["00000", "12345"].includes(r.cap));
const provinceCountsItaly = {};
for (const r of italianRecords) provinceCountsItaly[r.provincia] = (provinceCountsItaly[r.provincia] ?? 0) + 1;

const report = {
  source,
  modifiedAt: fs.statSync(source).mtime.toISOString(),
  headers,
  rowsIncludingHeader: rows.length,
  records: records.length,
  italianRecords: italianRecords.length,
  activeLikeRecords: activeLikeRecords.length,
  uniqueComuneProvincia: byKey.size,
  expectedActiveMunicipalitiesAround2026: 7894,
  differenceVsExpectedActive: records.length - 7894,
  duplicateCount: duplicates.length,
  invalidCapCount: invalidCaps.length,
  suspiciousCapCount: suspiciousCaps.length,
  missingCadastralCount: missingCadastral.length,
  invalidCadastralCount: invalidCadastral.length,
  invalidProvinceCount: invalidProvince.length,
  provinceCount: Object.keys(provinceCounts).length,
  italianProvinceCount: Object.keys(provinceCountsItaly).length,
  regionCount: Object.keys(regionCounts).length,
  expectedResults,
  samples: {
    suspiciousCaps: suspiciousCaps.slice(0, 30),
    missingCadastral: missingCadastral.slice(0, 30),
    invalidProvince: invalidProvince.slice(0, 30),
    duplicates: duplicates.slice(0, 30),
  },
};

console.log(JSON.stringify(report, null, 2));
