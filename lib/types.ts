export type Gender = "M" | "F" | "Altro";

export interface Patient {
  id: string;
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
  profession: string;
  birthDate: string;
  gender: Gender;
  birthCity: string;
  taxCode: string;
  address: string;
  city: string;
  province: string;
  zip: string;
  lastVisit: string;
}

export interface Invoice {
  id: string;
  number: string;
  date: string;
  patientId: string;
  service: string;
  description: string;
  amount: number;
  tax: "Esente IVA" | "IVA 22%";
  paid: boolean;
}

export interface Session {
  id: string;
  patientId: string;
  date: string;
  treatment: string;
  notes: string;
  amount: number;
}

export interface ClinicalRecord {
  reason: string;
  symptomsStart: string;
  pain: string;
  investigations: string;
  treatments: string;
  trauma: string;
  surgery: string;
  visceral: string;
  devices: string;
  orthodontics: string;
  family: string;
  habits: string;
  wellbeing: string;
  medicines: string;
  updatedAt: string;
}
