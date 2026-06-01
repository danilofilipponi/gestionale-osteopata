import { isSupabaseConfigured, supabase } from "./supabase";
import type { ClinicalRecord, Invoice, Patient } from "./types";

type PatientRow = {
  id: string; nome: string; cognome: string; telefono: string | null; email: string | null;
  professione: string | null; data_nascita: string | null; sesso: Patient["gender"] | null;
  comune_nascita: string | null; codice_fiscale: string | null; indirizzo: string | null;
  citta: string | null; provincia: string | null; cap: string | null; updated_at: string;
};
type InvoiceRow = {
  id: string; numero: string; data: string; paziente_id: string; prestazione: string;
  descrizione: string | null; imponibile: number; regime_iva: string; stato: string;
};

const currentUserId = async () => {
  if (!supabase) throw new Error("Supabase non configurato.");
  const { data, error } = await supabase.auth.getUser();
  if (error || !data.user) throw new Error("Sessione non valida. Effettua nuovamente l'accesso.");
  return data.user.id;
};
const patientFromRow = (row: PatientRow): Patient => ({
  id: row.id, firstName: row.nome, lastName: row.cognome, phone: row.telefono ?? "",
  email: row.email ?? "", profession: row.professione ?? "", birthDate: row.data_nascita ?? "",
  gender: row.sesso ?? "Altro", birthCity: row.comune_nascita ?? "", taxCode: row.codice_fiscale ?? "",
  address: row.indirizzo ?? "", city: row.citta ?? "", province: row.provincia ?? "",
  zip: row.cap ?? "", lastVisit: row.updated_at.slice(0, 10),
});
const invoiceFromRow = (row: InvoiceRow): Invoice => ({
  id: row.id, number: row.numero, date: row.data, patientId: row.paziente_id,
  service: row.prestazione, description: row.descrizione ?? "", amount: Number(row.imponibile),
  tax: row.regime_iva === "iva_22" ? "IVA 22%" : "Esente IVA", paid: row.stato === "pagata",
});

export async function loadPatients() {
  if (!isSupabaseConfigured || !supabase) return [];
  const { data, error } = await supabase.from("pazienti").select("*").order("cognome");
  if (error) throw error;
  return (data as PatientRow[]).map(patientFromRow);
}
export async function savePatient(patient: Patient) {
  if (!supabase) throw new Error("Supabase non configurato.");
  const values = {
    user_id: await currentUserId(), nome: patient.firstName, cognome: patient.lastName,
    telefono: patient.phone || null, email: patient.email || null, professione: patient.profession || null,
    data_nascita: patient.birthDate || null, sesso: patient.gender, comune_nascita: patient.birthCity || null,
    codice_fiscale: patient.taxCode || null, indirizzo: patient.address || null, citta: patient.city || null,
    provincia: patient.province || null, cap: patient.zip || null, updated_at: new Date().toISOString(),
  };
  const query = patient.id ? supabase.from("pazienti").update(values).eq("id", patient.id) : supabase.from("pazienti").insert(values);
  const { data, error } = await query.select().single();
  if (error) throw error;
  return patientFromRow(data as PatientRow);
}
export async function deletePatient(id: string) {
  if (!supabase) throw new Error("Supabase non configurato.");
  const { error } = await supabase.from("pazienti").delete().eq("id", id);
  if (error) throw error;
}
export async function loadInvoices() {
  if (!isSupabaseConfigured || !supabase) return [];
  const { data, error } = await supabase.from("fatture").select("*").order("data", { ascending: false });
  if (error) throw error;
  return (data as InvoiceRow[]).map(invoiceFromRow);
}
export async function saveInvoice(invoice: Invoice) {
  if (!supabase) throw new Error("Supabase non configurato.");
  const { data, error } = await supabase.from("fatture").insert({
    user_id: await currentUserId(), paziente_id: invoice.patientId, numero: invoice.number,
    data: invoice.date, prestazione: invoice.service, descrizione: invoice.description || null,
    regime_iva: invoice.tax === "IVA 22%" ? "iva_22" : "esente", aliquota_iva: invoice.tax === "IVA 22%" ? 22 : 0,
    imponibile: invoice.amount, totale: invoice.tax === "IVA 22%" ? invoice.amount * 1.22 : invoice.amount,
    stato: invoice.paid ? "pagata" : "non_pagata",
  }).select().single();
  if (error) throw error;
  return invoiceFromRow(data as InvoiceRow);
}
export async function deleteInvoice(id: string) {
  if (!supabase) throw new Error("Supabase non configurato.");
  const { error } = await supabase.from("fatture").delete().eq("id", id);
  if (error) throw error;
}

const emptyClinicalRecord = (): ClinicalRecord => ({
  reason: "", symptomsStart: "", pain: "", investigations: "", treatments: "", trauma: "",
  surgery: "", visceral: "", devices: "", orthodontics: "", family: "", habits: "",
  wellbeing: "", medicines: "", updatedAt: new Date().toISOString().slice(0, 10),
});
export async function loadClinicalRecord(patientId: string) {
  if (!supabase) return emptyClinicalRecord();
  const { data, error } = await supabase.from("cartelle_cliniche").select("*").eq("paziente_id", patientId).maybeSingle();
  if (error) throw error;
  if (!data) return emptyClinicalRecord();
  return {
    reason: data.motivo_consulto ?? "", symptomsStart: data.inizio_sintomatologia ?? "",
    pain: data.descrizione_dolore ?? "", investigations: data.indagini_eseguite ?? "",
    treatments: data.trattamenti_eseguiti ?? "", trauma: data.traumi ?? "", surgery: data.chirurgia ?? "",
    visceral: data.problematiche_viscerali ?? "", devices: data.protesi_vista_plantari ?? "",
    orthodontics: data.ortodonzia ?? "", family: data.anamnesi_familiare_parto ?? "",
    habits: data.abitudini_vita_sport ?? "", wellbeing: data.sfera_fisica_psichica ?? "",
    medicines: data.farmaci ?? "", updatedAt: data.updated_at.slice(0, 10),
  } satisfies ClinicalRecord;
}
export async function saveClinicalRecord(patientId: string, record: ClinicalRecord) {
  if (!supabase) throw new Error("Supabase non configurato.");
  const updatedAt = new Date().toISOString();
  const { error } = await supabase.from("cartelle_cliniche").upsert({
    paziente_id: patientId, user_id: await currentUserId(), motivo_consulto: record.reason,
    inizio_sintomatologia: record.symptomsStart, descrizione_dolore: record.pain,
    indagini_eseguite: record.investigations, trattamenti_eseguiti: record.treatments,
    traumi: record.trauma, chirurgia: record.surgery, problematiche_viscerali: record.visceral,
    protesi_vista_plantari: record.devices, ortodonzia: record.orthodontics,
    anamnesi_familiare_parto: record.family, abitudini_vita_sport: record.habits,
    sfera_fisica_psichica: record.wellbeing, farmaci: record.medicines, updated_at: updatedAt,
  }, { onConflict: "paziente_id" });
  if (error) throw error;
  return { ...record, updatedAt: updatedAt.slice(0, 10) };
}
