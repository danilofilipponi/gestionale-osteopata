import { findCity } from "./italian-cities";
import type { Patient } from "./types";

const months = "ABCDEHLMPRST";
const consonants = (value: string) => value.toUpperCase().replace(/[^A-Z]/g, "").replace(/[AEIOU]/g, "");
const vowels = (value: string) => value.toUpperCase().replace(/[^AEIOU]/g, "");
const surnameCode = (value: string) => `${consonants(value)}${vowels(value)}XXX`.slice(0, 3);
const nameCode = (value: string) => {
  const letters = consonants(value);
  return letters.length >= 4 ? `${letters[0]}${letters[2]}${letters[3]}` : `${letters}${vowels(value)}XXX`.slice(0, 3);
};
const even: Record<string, number> = Object.fromEntries("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("").map((x, i) => [x, i < 10 ? i : i - 10]));
const oddValues = [1,0,5,7,9,13,15,17,19,21,2,4,18,20,11,3,6,8,12,14,16,10,22,25,24,23];
const odd: Record<string, number> = Object.fromEntries("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("").map((x, i) => [x, i < 10 ? oddValues[i] : oddValues[i - 10]]));

export function calculateTaxCode(patient: Patient) {
  if (!patient.firstName || !patient.lastName || !patient.birthDate || !patient.birthCity) return "";
  const city = findCity(patient.birthCity);
  if (!city) return "";
  const date = new Date(patient.birthDate);
  let day = date.getUTCDate();
  if (patient.gender === "F") day += 40;
  const partial = `${surnameCode(patient.lastName)}${nameCode(patient.firstName)}${String(date.getUTCFullYear()).slice(-2)}${months[date.getUTCMonth()]}${String(day).padStart(2, "0")}${city.cadastralCode}`;
  const checksum = partial.split("").reduce((sum, char, index) => sum + (index % 2 === 0 ? odd[char] : even[char]), 0) % 26;
  return `${partial}${String.fromCharCode(65 + checksum)}`;
}
