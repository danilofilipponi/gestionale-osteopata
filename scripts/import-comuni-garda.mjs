import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";

const root = process.cwd();
const zipPath = path.join(root, "data", "source", "gi_db_comuni-2026-05-29-e1c47.zip");
const extractDir = path.join(root, "data", "source", "garda-2026-05-29");
const outputPath = path.join(root, "lib", "italian-cities.ts");

if (!fs.existsSync(zipPath)) {
  throw new Error(`File non trovato: ${zipPath}`);
}

fs.mkdirSync(extractDir, { recursive: true });
if (!fs.existsSync(path.join(extractDir, "json", "gi_comuni_cap.json"))) {
  execFileSync("powershell.exe", [
    "-NoProfile",
    "-Command",
    `Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::ExtractToDirectory('${zipPath.replaceAll("'", "''")}','${extractDir.replaceAll("'", "''")}')`,
  ]);
}

const source = JSON.parse(fs.readFileSync(path.join(extractDir, "json", "gi_comuni_cap.json"), "utf8"));
const grouped = new Map();

for (const row of source) {
  if (!row.denominazione_ita || !row.sigla_provincia || !row.codice_belfiore) continue;
  const key = `${row.denominazione_ita}|${row.sigla_provincia}|${row.codice_belfiore}`;
  const existing = grouped.get(key);
  if (existing) {
    if (row.cap && !existing.caps.includes(row.cap)) existing.caps.push(row.cap);
    continue;
  }
  grouped.set(key, {
    name: row.denominazione_ita,
    province: row.sigla_provincia,
    zip: row.cap || "",
    caps: row.cap ? [row.cap] : [],
    cadastralCode: row.codice_belfiore,
    region: row.denominazione_regione,
    istatCode: row.codice_istat,
  });
}

const cities = [...grouped.values()]
  .map((city) => ({ ...city, caps: city.caps.sort(), zip: city.caps[0] || city.zip }))
  .sort((a, b) => a.name.localeCompare(b.name, "it") || a.province.localeCompare(b.province, "it"));

const serialized = JSON.stringify(cities, null, 2).replaceAll('"name"', "name")
  .replaceAll('"province"', "province")
  .replaceAll('"zip"', "zip")
  .replaceAll('"caps"', "caps")
  .replaceAll('"cadastralCode"', "cadastralCode")
  .replaceAll('"region"', "region")
  .replaceAll('"istatCode"', "istatCode");

const file = `export interface ItalianCity {
  name: string;
  province: string;
  zip: string;
  caps: string[];
  cadastralCode: string;
  region: string;
  istatCode: string;
}

export const italianCities: ItalianCity[] = ${serialized};

const normalizeCityName = (value: string) =>
  value
    .normalize("NFD")
    .replace(/[\\u0300-\\u036f]/g, "")
    .replace(/[’]/g, "'")
    .replace(/\\s+/g, " ")
    .trim()
    .toLowerCase();

export const findCity = (name: string) => {
  const normalized = normalizeCityName(name);
  return italianCities.find((city) => normalizeCityName(city.name) === normalized);
};
`;

fs.writeFileSync(outputPath, file);

const find = (name, province) => cities.find((city) => city.name === name && city.province === province);
const report = {
  sourceRows: source.length,
  generatedCities: cities.length,
  examples: {
    Milano: find("Milano", "MI"),
    Roma: find("Roma", "RM"),
    Monza: find("Monza", "MB"),
    Mappano: find("Mappano", "TO"),
    Valsamoggia: find("Valsamoggia", "BO"),
    CastegneroNanto: find("Castegnero Nanto", "VI"),
  },
};

console.log(JSON.stringify(report, null, 2));
