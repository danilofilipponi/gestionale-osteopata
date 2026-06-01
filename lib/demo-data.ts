import type { ClinicalRecord, Invoice, Patient, Session } from "./types";

export const patients: Patient[] = [
  { id: "p1", firstName: "Sofia", lastName: "Bianchi", phone: "347 455 8210", email: "sofia.bianchi@email.it", profession: "Insegnante", birthDate: "1988-04-12", gender: "F", birthCity: "Milano", taxCode: "BNCSFO88D52F205A", address: "Via Manzoni 24", city: "Milano", province: "MI", zip: "20121", lastVisit: "2026-05-28" },
  { id: "p2", firstName: "Marco", lastName: "Rossi", phone: "333 812 1090", email: "marco.rossi@email.it", profession: "Architetto", birthDate: "1979-11-03", gender: "M", birthCity: "Monza", taxCode: "RSSMRC79S03F704Z", address: "Via Dante 8", city: "Monza", province: "MB", zip: "20900", lastVisit: "2026-05-26" },
  { id: "p3", firstName: "Elena", lastName: "Conti", phone: "349 568 4371", email: "elena.conti@email.it", profession: "Avvocata", birthDate: "1992-07-21", gender: "F", birthCity: "Bergamo", taxCode: "CNTLNE92L61A794E", address: "Via Roma 16", city: "Bergamo", province: "BG", zip: "24121", lastVisit: "2026-05-22" },
  { id: "p4", firstName: "Luca", lastName: "Ferrari", phone: "335 902 7834", email: "luca.ferrari@email.it", profession: "Commercialista", birthDate: "1985-01-16", gender: "M", birthCity: "Como", taxCode: "FRRLCU85A16C933X", address: "Via Milano 41", city: "Como", province: "CO", zip: "22100", lastVisit: "2026-05-18" },
  { id: "p5", firstName: "Giulia", lastName: "Romano", phone: "340 234 6651", email: "giulia.romano@email.it", profession: "Designer", birthDate: "1996-09-08", gender: "F", birthCity: "Lecco", taxCode: "RMNGLI96P48E507G", address: "Corso Matteotti 7", city: "Lecco", province: "LC", zip: "23900", lastVisit: "2026-05-15" },
];

export const invoices: Invoice[] = [
  { id: "f1", number: "2026/042", date: "2026-05-28", patientId: "p1", service: "Trattamento osteopatico", description: "Seduta individuale", amount: 75, tax: "Esente IVA", paid: true },
  { id: "f2", number: "2026/041", date: "2026-05-26", patientId: "p2", service: "Trattamento osteopatico", description: "Seduta individuale", amount: 75, tax: "Esente IVA", paid: true },
  { id: "f3", number: "2026/040", date: "2026-05-22", patientId: "p3", service: "Prima visita osteopatica", description: "Valutazione e trattamento", amount: 90, tax: "Esente IVA", paid: false },
  { id: "f4", number: "2026/039", date: "2026-05-18", patientId: "p4", service: "Trattamento osteopatico", description: "Seduta individuale", amount: 75, tax: "Esente IVA", paid: true },
];

export const sessions: Session[] = [
  { id: "s1", patientId: "p1", date: "2026-05-28", treatment: "Trattamento cervicale e dorsale", notes: "Riduzione della tensione. Controllo tra 3 settimane.", amount: 75 },
  { id: "s2", patientId: "p1", date: "2026-05-06", treatment: "Valutazione posturale", notes: "Mobilita cervicale migliorata.", amount: 75 },
  { id: "s3", patientId: "p1", date: "2026-04-10", treatment: "Prima visita osteopatica", notes: "Raccolta anamnesi e primo trattamento.", amount: 90 },
];

export const clinicalRecord: ClinicalRecord = {
  reason: "Cervicalgia ricorrente associata a cefalea tensiva.",
  symptomsStart: "Da circa sei mesi, con peggioramento nelle ultime settimane.",
  pain: "Dolore cervicale con irradiazione verso la regione scapolare destra.",
  investigations: "RX rachide cervicale eseguita a febbraio 2026.",
  treatments: "Fisioterapia nel 2024 con beneficio temporaneo.",
  trauma: "Nessun trauma recente riferito.",
  surgery: "Appendicectomia nel 2008.",
  visceral: "Nessuna problematica rilevante.",
  devices: "Utilizza occhiali da vista. Nessun plantare.",
  orthodontics: "Pregresso trattamento ortodontico.",
  family: "Anamnesi familiare negativa per patologie rilevanti.",
  habits: "Attivita sedentaria. Yoga due volte alla settimana.",
  wellbeing: "Periodo lavorativo intenso con sonno discontinuo.",
  medicines: "Nessuna terapia farmacologica continuativa.",
  updatedAt: "2026-05-28",
};
