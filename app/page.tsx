"use client";

import { useEffect, useState } from "react";
import { clinicalRecord, invoices as seedInvoices, patients as seedPatients, sessions } from "@/lib/demo-data";
import type { ClinicalRecord, Invoice, Patient } from "@/lib/types";
import { isSupabaseConfigured, supabase } from "@/lib/supabase";
import { findCity, italianCities } from "@/lib/italian-cities";
import { calculateTaxCode } from "@/lib/tax-code";
import { deleteInvoice, deletePatient, loadClinicalRecord, loadInvoices, loadPatients, saveClinicalRecord, saveInvoice, savePatient } from "@/lib/data";
import { Icon, type IconName } from "@/components/icons";
import { Badge, Button, Empty, Input, Select, TextArea } from "@/components/ui";

type View = "dashboard" | "patients" | "accounting" | "settings";
type PatientTab = "personal" | "clinical" | "sessions" | "invoices" | "documents";

const money = (value: number) => new Intl.NumberFormat("it-IT", { style: "currency", currency: "EUR" }).format(value);
const prettyDate = (value: string) => value ? new Intl.DateTimeFormat("it-IT").format(new Date(value)) : "-";
const age = (birthDate: string) => birthDate ? Math.floor((Date.now() - new Date(birthDate).getTime()) / 31557600000) : "-";
const emptyPatient: Patient = { id: "", firstName: "", lastName: "", phone: "", email: "", profession: "", birthDate: "", gender: "F", birthCity: "", taxCode: "", address: "", city: "", province: "", zip: "", lastVisit: "" };

export default function App() {
  const [loggedIn, setLoggedIn] = useState(false);
  const [authReady, setAuthReady] = useState(!isSupabaseConfigured);
  const [view, setView] = useState<View>("dashboard");
  const [patientList, setPatientList] = useState(isSupabaseConfigured ? [] : seedPatients);
  const [invoiceList, setInvoiceList] = useState(isSupabaseConfigured ? [] : seedInvoices);
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null);
  const [patientModal, setPatientModal] = useState<Patient | null>(null);
  const [invoiceModal, setInvoiceModal] = useState(false);
  const [mobileNav, setMobileNav] = useState(false);
  useEffect(() => {
    if (!supabase) return;
    supabase.auth.getSession().then(({ data }) => {
      setLoggedIn(Boolean(data.session));
      setAuthReady(true);
    });
    const { data } = supabase.auth.onAuthStateChange((_event, session) => setLoggedIn(Boolean(session)));
    return () => data.subscription.unsubscribe();
  }, []);
  useEffect(() => {
    if (!loggedIn || !isSupabaseConfigured) return;
    Promise.all([loadPatients(), loadInvoices()])
      .then(([patients, invoices]) => {
        setPatientList(patients);
        setInvoiceList(invoices);
      })
      .catch((error) => alert(`Errore durante il caricamento dei dati: ${error.message}`));
  }, [loggedIn]);
  const storePatient = async (patient: Patient) => {
    try {
      const saved = isSupabaseConfigured ? await savePatient(patient) : { ...patient, id: patient.id || `p${Date.now()}`, lastVisit: patient.lastVisit || new Date().toISOString().slice(0, 10) };
      setPatientList(patientList.some(x => x.id === saved.id) ? patientList.map(x => x.id === saved.id ? saved : x) : [saved, ...patientList]);
      setSelectedPatient(selectedPatient?.id === saved.id ? saved : selectedPatient);
      setPatientModal(null);
    } catch (error) { alert(`Impossibile salvare il paziente: ${(error as Error).message}`); }
  };
  const removePatient = async (patient: Patient) => {
    if (!confirm("Eliminare definitivamente questo paziente?")) return;
    try {
      if (isSupabaseConfigured) await deletePatient(patient.id);
      setPatientList(patientList.filter(x => x.id !== patient.id));
      setSelectedPatient(null);
    } catch (error) { alert(`Impossibile eliminare il paziente: ${(error as Error).message}`); }
  };
  const storeInvoice = async (invoice: Invoice) => {
    try {
      const saved = isSupabaseConfigured ? await saveInvoice(invoice) : { ...invoice, id: `f${Date.now()}` };
      setInvoiceList([saved, ...invoiceList]);
      setInvoiceModal(false);
    } catch (error) { alert(`Impossibile salvare la fattura: ${(error as Error).message}`); }
  };
  const removeInvoice = async (id: string) => {
    try {
      if (isSupabaseConfigured) await deleteInvoice(id);
      setInvoiceList(invoiceList.filter(x => x.id !== id));
    } catch (error) { alert(`Impossibile eliminare la fattura: ${(error as Error).message}`); }
  };
  if (!authReady) return <div className="grid min-h-screen place-items-center bg-[#f4f8f7] text-sm font-bold text-sage">Caricamento area riservata...</div>;
  if (!loggedIn) return <Login onLogin={() => setLoggedIn(true)}/>;
  const go = (next: View) => { setSelectedPatient(null); setView(next); setMobileNav(false); };
  return <div className="min-h-screen bg-[#f7faf9]">
    <Sidebar view={view} go={go} logout={() => { supabase?.auth.signOut(); setLoggedIn(false); }} open={mobileNav}/>
    <main className="min-h-screen md:ml-64">
      <Header view={view} onMenu={() => setMobileNav(!mobileNav)} />
      <div className="mx-auto max-w-[1450px] p-5 md:p-8">
        {selectedPatient ? <PatientDetail patient={selectedPatient} invoices={invoiceList} onBack={() => setSelectedPatient(null)} onEdit={() => setPatientModal(selectedPatient)} onDelete={() => removePatient(selectedPatient)}/> :
        view === "dashboard" ? <Dashboard patients={patientList} invoices={invoiceList} go={go} newPatient={() => setPatientModal({ ...emptyPatient })} newInvoice={() => setInvoiceModal(true)}/> :
        view === "patients" ? <Patients patients={patientList} onOpen={setSelectedPatient} onNew={() => setPatientModal({ ...emptyPatient })}/> :
        view === "accounting" ? <Accounting invoices={invoiceList} patients={patientList} onNew={() => setInvoiceModal(true)} onDelete={removeInvoice}/> :
        <Settings/>}
      </div>
    </main>
    {patientModal && <PatientModal value={patientModal} close={() => setPatientModal(null)} save={storePatient}/>}
    {invoiceModal && <InvoiceModal patients={patientList} close={() => setInvoiceModal(false)} save={storeInvoice}/>}
  </div>;
}

function Login({ onLogin }: { onLogin: () => void }) {
  const [email, setEmail] = useState(isSupabaseConfigured ? "" : "studio@osteocare.it");
  const [password, setPassword] = useState(isSupabaseConfigured ? "" : "password");
  const [forgot, setForgot] = useState(false);
  const [message, setMessage] = useState("");
  const submit = async () => {
    if (isSupabaseConfigured && supabase) {
      const { error } = await supabase.auth.signInWithPassword({ email, password });
      if (error) return setMessage(error.message);
    }
    onLogin();
  };
  const reset = async () => {
    if (!email) return setMessage("Inserisci il tuo indirizzo email.");
    if (isSupabaseConfigured && supabase) await supabase.auth.resetPasswordForEmail(email);
    setMessage("Controlla la tua casella email per reimpostare la password.");
  };
  return <div className="flex min-h-screen bg-[#f4f8f7]">
    <section className="hidden w-[48%] flex-col justify-between bg-[#224e48] p-16 text-white lg:flex">
      <Logo inverse/>
      <div><p className="mb-5 text-xs font-bold uppercase tracking-[.28em] text-[#acd1ca]">Gestionale sanitario</p><h1 className="max-w-xl text-5xl font-bold leading-[1.12]">Il tuo studio,<br/>organizzato con cura.</h1><p className="mt-7 max-w-lg text-lg leading-8 text-[#c9ded9]">Pazienti, cartelle cliniche e contabilità in un unico spazio protetto, semplice e professionale.</p></div>
      <p className="flex items-center gap-2 text-sm text-[#acd1ca]"><Icon name="shield"/> Dati protetti e accesso riservato</p>
    </section>
    <section className="flex flex-1 items-center justify-center p-6"><div className="w-full max-w-md">
      <div className="mb-10 lg:hidden"><Logo/></div>
      <p className="mb-2 text-sm font-bold uppercase tracking-wider text-sage">Area riservata</p>
      <h2 className="text-3xl font-bold">{forgot ? "Recupera password" : "Bentornato"}</h2>
      <p className="mt-3 text-muted">{forgot ? "Riceverai un link per scegliere una nuova password." : "Accedi per gestire il tuo studio."}</p>
      <div className="mt-8 space-y-4"><Input label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)}/>{!forgot && <Input label="Password" type="password" value={password} onChange={(e) => setPassword(e.target.value)}/>}</div>
      {message && <p className="mt-4 rounded-xl bg-mist p-3 text-sm text-sage">{message}</p>}
      <Button className="mt-6 w-full py-3.5" onClick={forgot ? reset : submit}>{forgot ? "Invia link di recupero" : "Accedi al gestionale"}</Button>
      <button className="mt-5 w-full text-center text-sm font-bold text-sage" onClick={() => { setForgot(!forgot); setMessage(""); }}>{forgot ? "Torna al login" : "Password dimenticata?"}</button>
      {!isSupabaseConfigured && <p className="mt-8 text-center text-xs leading-5 text-muted">Modalità dimostrativa attiva<br/>Configura Supabase per abilitare l'accesso reale.</p>}
    </div></section>
  </div>;
}

function Logo({ inverse = false }: { inverse?: boolean }) {
  return <div className={`flex items-center gap-3 ${inverse ? "text-white" : "text-ink"}`}><span className={`grid h-11 w-11 place-items-center rounded-2xl ${inverse ? "bg-white/10" : "bg-sage text-white"}`}><Icon name="stethoscope" size={23}/></span><div><b className="text-xl">OsteoCare</b><small className={`block text-[10px] font-bold uppercase tracking-[.18em] ${inverse ? "text-[#acd1ca]" : "text-muted"}`}>Studio Manager</small></div></div>;
}

function Sidebar({ view, go, logout, open }: { view: View; go: (v: View) => void; logout: () => void; open: boolean }) {
  const links: [View, IconName, string][] = [["dashboard", "home", "Dashboard"], ["patients", "patients", "Pazienti"], ["accounting", "invoice", "Contabilita"], ["settings", "settings", "Impostazioni"]];
  return <aside className={`fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-line bg-white p-5 transition-transform md:translate-x-0 ${open ? "translate-x-0" : "-translate-x-full"}`}>
    <Logo/><nav className="mt-12 space-y-1">{links.map(([id, icon, label]) => <button key={id} onClick={() => go(id)} className={`flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition ${view === id ? "bg-mist text-sage" : "text-muted hover:bg-mist"}`}><Icon name={icon}/>{label}</button>)}</nav>
    <div className="mt-auto rounded-2xl bg-mist p-4"><p className="text-xs font-bold uppercase tracking-wide text-muted">Account</p><p className="mt-1 text-sm font-bold">Dr. Andrea Colombo</p><button onClick={logout} className="mt-4 flex items-center gap-2 text-sm font-bold text-muted hover:text-red-600"><Icon name="logout" size={16}/> Esci</button></div>
  </aside>;
}

function Header({ view, onMenu }: { view: View; onMenu: () => void }) {
  const title = { dashboard: "Dashboard", patients: "Area pazienti", accounting: "Contabilita", settings: "Impostazioni" }[view];
  return <header className="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-line bg-white/90 px-5 backdrop-blur md:px-8"><div className="flex items-center gap-3"><button className="md:hidden" onClick={onMenu}><Icon name="menu"/></button><div><h1 className="text-xl font-bold">{title}</h1><p className="hidden text-xs text-muted sm:block">Lunedi, 1 giugno 2026</p></div></div><div className="flex items-center gap-3"><div className="hidden text-right sm:block"><b className="block text-sm">Dr. Andrea Colombo</b><small className="text-muted">Osteopata</small></div><div className="grid h-10 w-10 place-items-center rounded-full bg-[#dbeae7] text-sm font-bold text-sage">AC</div></div></header>;
}

function Dashboard({ patients, invoices, go, newPatient, newInvoice }: { patients: Patient[]; invoices: Invoice[]; go: (v: View) => void; newPatient: () => void; newInvoice: () => void }) {
  const revenue = invoices.filter(i => i.paid).reduce((sum, i) => sum + i.amount, 0);
  return <><div className="flex flex-wrap items-end justify-between gap-4"><div><p className="text-sm text-muted">Buongiorno, Andrea</p><h2 className="mt-1 text-3xl font-bold">Il tuo studio, oggi.</h2></div><div className="flex gap-2"><Button variant="secondary" icon="plus" onClick={newPatient}>Nuovo paziente</Button><Button icon="plus" onClick={newInvoice}>Nuova fattura</Button></div></div>
    <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><Metric icon="patients" label="Pazienti totali" value={String(patients.length)} detail="+3 questo mese"/><Metric icon="calendar" label="Appuntamenti oggi" value="6" detail="Prossimo alle 10:30"/><Metric icon="euro" label="Incassi del mese" value={money(revenue)} detail="+12% rispetto ad aprile"/><Metric icon="clock" label="Fatture da saldare" value={String(invoices.filter(i=>!i.paid).length)} detail={money(invoices.filter(i=>!i.paid).reduce((s,i)=>s+i.amount,0))}/></div>
    <div className="mt-8 grid gap-6 xl:grid-cols-[1.4fr_.8fr]"><Card><SectionTitle title="Attivita recenti" subtitle="Gli ultimi aggiornamenti dello studio"/><div className="mt-5 divide-y divide-line">{patients.slice(0,4).map(p=><div key={p.id} className="flex items-center gap-4 py-4"><Avatar patient={p}/><div className="min-w-0 flex-1"><b className="block text-sm">{p.firstName} {p.lastName}</b><span className="text-xs text-muted">Trattamento osteopatico completato</span></div><span className="hidden text-xs text-muted sm:block">{prettyDate(p.lastVisit)}</span></div>)}</div></Card>
    <Card><SectionTitle title="Accesso rapido" subtitle="Le aree del gestionale"/><div className="mt-5 grid gap-3"><Quick icon="patients" text="Gestisci pazienti" onClick={()=>go("patients")}/><Quick icon="invoice" text="Contabilita e fatture" onClick={()=>go("accounting")}/><Quick icon="settings" text="Impostazioni studio" onClick={()=>go("settings")}/></div></Card></div></>;
}

function Metric({ icon, label, value, detail }: { icon: IconName; label: string; value: string; detail: string }) {
  return <Card><div className="flex items-center justify-between"><span className="rounded-xl bg-mist p-2.5 text-sage"><Icon name={icon}/></span><Icon name="chart" size={16} className="text-[#a9c6c0]"/></div><p className="mt-5 text-xs font-bold uppercase tracking-wide text-muted">{label}</p><b className="mt-1 block text-2xl">{value}</b><small className="mt-2 block text-muted">{detail}</small></Card>;
}
function Quick({ icon, text, onClick }: { icon: IconName; text: string; onClick: () => void }) { return <button onClick={onClick} className="flex items-center gap-3 rounded-xl border border-line p-4 text-left text-sm font-bold transition hover:border-sage hover:bg-mist"><span className="text-sage"><Icon name={icon}/></span><span className="flex-1">{text}</span><Icon name="arrow" size={16} className="text-muted"/></button>; }
function Card({ children, className="" }: { children: React.ReactNode; className?: string }) { return <section className={`rounded-2xl border border-line bg-white p-5 shadow-card ${className}`}>{children}</section>; }
function SectionTitle({ title, subtitle }: { title: string; subtitle?: string }) { return <div><h3 className="font-bold">{title}</h3>{subtitle && <p className="mt-1 text-sm text-muted">{subtitle}</p>}</div>; }
function Avatar({ patient }: { patient: Patient }) { return <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#dbeae7] text-xs font-bold text-sage">{patient.firstName[0]}{patient.lastName[0]}</span>; }

function Patients({ patients, onOpen, onNew }: { patients: Patient[]; onOpen: (p: Patient) => void; onNew: () => void }) {
  const [query, setQuery] = useState("");
  const shown = patients.filter(p => `${p.firstName} ${p.lastName} ${p.phone}`.toLowerCase().includes(query.toLowerCase()));
  return <><div className="flex flex-wrap items-end justify-between gap-4"><div><h2 className="text-2xl font-bold">Pazienti</h2><p className="mt-1 text-sm text-muted">{patients.length} pazienti registrati nello studio</p></div><Button icon="plus" onClick={onNew}>Nuovo paziente</Button></div>
  <Card className="mt-7"><div className="relative max-w-lg"><Icon name="search" className="absolute left-3 top-3 text-muted"/><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="Cerca per nome, cognome o telefono..." className="w-full rounded-xl border border-line py-3 pl-10 pr-3 text-sm outline-none focus:border-sage"/></div>
  <div className="mt-5 overflow-x-auto"><table className="w-full min-w-[760px] text-left text-sm"><thead><tr className="border-b border-line text-xs uppercase tracking-wide text-muted"><th className="pb-3">Paziente</th><th className="pb-3">Contatti</th><th className="pb-3">Eta</th><th className="pb-3">Ultima visita</th><th className="pb-3">Codice fiscale</th><th/></tr></thead><tbody>{shown.map(p=><tr key={p.id} className="border-b border-line last:border-0"><td className="py-4"><button className="flex items-center gap-3 text-left" onClick={()=>onOpen(p)}><Avatar patient={p}/><b>{p.lastName} {p.firstName}</b></button></td><td><span className="block">{p.phone}</span><small className="text-muted">{p.email}</small></td><td>{age(p.birthDate)} anni</td><td>{prettyDate(p.lastVisit)}</td><td className="font-mono text-xs">{p.taxCode}</td><td><Button variant="ghost" onClick={()=>onOpen(p)}>Apri <Icon name="arrow" size={15}/></Button></td></tr>)}</tbody></table></div></Card></>;
}

function PatientDetail({ patient, invoices, onBack, onEdit, onDelete }: { patient: Patient; invoices: Invoice[]; onBack: () => void; onEdit: () => void; onDelete: () => void }) {
  const [tab, setTab] = useState<PatientTab>("personal");
  const patientInvoices = invoices.filter(i=>i.patientId===patient.id);
  const tabs: [PatientTab,string][] = [["personal","Anagrafica"],["clinical","Cartella clinica"],["sessions","Sedute / trattamenti"],["invoices","Fatture"],["documents","Documenti"]];
  return <><button onClick={onBack} className="mb-5 flex items-center gap-2 text-sm font-bold text-muted hover:text-sage"><span className="rotate-180"><Icon name="arrow" size={16}/></span>Torna ai pazienti</button>
  <Card><div className="flex flex-wrap items-center gap-4"><Avatar patient={patient}/><div className="flex-1"><h2 className="text-2xl font-bold">{patient.firstName} {patient.lastName}</h2><p className="mt-1 text-sm text-muted">{patient.taxCode} · {age(patient.birthDate)} anni</p></div><Button variant="danger" icon="trash" onClick={onDelete}>Elimina</Button><Button variant="secondary" icon="edit" onClick={onEdit}>Modifica anagrafica</Button></div>
  <div className="mt-7 flex gap-1 overflow-x-auto border-b border-line">{tabs.map(([id,label])=><button key={id} onClick={()=>setTab(id)} className={`whitespace-nowrap border-b-2 px-4 py-3 text-sm font-bold ${tab===id?"border-sage text-sage":"border-transparent text-muted"}`}>{label}</button>)}</div>
  <div className="pt-6">{tab==="personal"?<Personal patient={patient}/>:tab==="clinical"?<Clinical patientId={patient.id}/>:tab==="sessions"?<Sessions/>:tab==="invoices"?<InvoiceTable invoices={patientInvoices} patients={[patient]}/>:<Empty icon="file" title="Nessun documento caricato" text="Questa sezione raccogliera referti, consensi e documenti del paziente."/>}</div></Card></>;
}

function Personal({ patient: p }: { patient: Patient }) { const rows = [["Nome e cognome",`${p.firstName} ${p.lastName}`],["Telefono",p.phone],["Email",p.email],["Professione",p.profession],["Data di nascita",`${prettyDate(p.birthDate)} (${age(p.birthDate)} anni)`],["Sesso",p.gender],["Luogo di nascita",p.birthCity],["Codice fiscale",p.taxCode],["Residenza",p.address],["Citta / CAP",`${p.city} (${p.province}) · ${p.zip}`]]; return <div className="grid gap-x-10 gap-y-5 md:grid-cols-2">{rows.map(([a,b])=><div key={a} className="border-b border-line pb-3"><p className="text-xs font-bold uppercase tracking-wide text-muted">{a}</p><p className="mt-1 text-sm font-bold">{b}</p></div>)}</div>; }
function Clinical({ patientId }: { patientId: string }) { const [record,setRecord]=useState<ClinicalRecord>(clinicalRecord); useEffect(()=>{if(isSupabaseConfigured)loadClinicalRecord(patientId).then(setRecord).catch(error=>alert(`Impossibile caricare la cartella clinica: ${error.message}`));},[patientId]); const fields: [keyof ClinicalRecord,string][]=[["reason","Motivo del consulto"],["symptomsStart","Data inizio sintomatologia"],["pain","Descrizione e irradiazione del dolore"],["investigations","Indagini eseguite"],["treatments","Trattamenti eseguiti"],["trauma","Traumi"],["surgery","Chirurgia"],["visceral","Problematiche viscerali"],["devices","Protesi / vista / plantari"],["orthodontics","Ortodonzia"],["family","Anamnesi familiare / parto"],["habits","Abitudini di vita / sport"],["wellbeing","Sfera fisica e psichica"],["medicines","Farmaci"]]; const store=async()=>{try{setRecord(isSupabaseConfigured?await saveClinicalRecord(patientId,record):{...record,updatedAt:new Date().toISOString().slice(0,10)});}catch(error){alert(`Impossibile salvare la cartella clinica: ${(error as Error).message}`);}}; return <><div className="mb-5 flex flex-wrap items-center justify-between gap-3"><p className="text-sm text-muted">Ultimo aggiornamento: <b>{prettyDate(record.updatedAt)}</b></p><Button icon="check" onClick={store}>Salva cartella clinica</Button></div><div className="grid gap-4 md:grid-cols-2">{fields.map(([key,label])=><TextArea key={key} label={label} value={record[key]} onChange={e=>setRecord({...record,[key]:e.target.value})}/>)}</div></>; }
function Sessions() { return <div className="space-y-3">{sessions.map(s=><div key={s.id} className="rounded-xl border border-line p-4"><div className="flex flex-wrap justify-between gap-2"><b className="text-sm">{prettyDate(s.date)} · {s.treatment}</b><Badge>{money(s.amount)}</Badge></div><p className="mt-2 text-sm text-muted">{s.notes}</p></div>)}</div>; }

function Accounting({ invoices, patients, onNew, onDelete }: { invoices: Invoice[]; patients: Patient[]; onNew: ()=>void; onDelete: (id:string)=>void }) {
  const [query,setQuery]=useState(""); const [year,setYear]=useState("2026"); const [month,setMonth]=useState("all");
  const shown=invoices.filter(i=>{const p=patients.find(p=>p.id===i.patientId);return `${p?.firstName} ${p?.lastName}`.toLowerCase().includes(query.toLowerCase())&&i.date.startsWith(year)&&(month==="all"||i.date.slice(5,7)===month);});
  return <><div className="flex flex-wrap items-end justify-between gap-4"><div><h2 className="text-2xl font-bold">Contabilita</h2><p className="mt-1 text-sm text-muted">Fatture, incassi e pagamenti dello studio</p></div><Button icon="plus" onClick={onNew}>Nuova fattura</Button></div>
  <div className="mt-7 grid gap-4 sm:grid-cols-3"><Metric icon="invoice" label="Fatture emesse" value={String(shown.length)} detail="Nel periodo selezionato"/><Metric icon="euro" label="Totale incassato" value={money(shown.filter(i=>i.paid).reduce((s,i)=>s+i.amount,0))} detail="Fatture saldate"/><Metric icon="clock" label="Da incassare" value={money(shown.filter(i=>!i.paid).reduce((s,i)=>s+i.amount,0))} detail="Pagamenti in attesa"/></div>
  <Card className="mt-6"><div className="grid gap-3 md:grid-cols-[1fr_170px_170px]"><Input label="Cerca paziente" value={query} onChange={e=>setQuery(e.target.value)} placeholder="Nome o cognome..."/><Select label="Mese" value={month} onChange={e=>setMonth(e.target.value)}><option value="all">Tutti i mesi</option><option value="05">Maggio</option><option value="04">Aprile</option></Select><Select label="Anno" value={year} onChange={e=>setYear(e.target.value)}><option>2026</option><option>2025</option></Select></div><div className="mt-6"><InvoiceTable invoices={shown} patients={patients} onDelete={onDelete}/></div></Card></>;
}
function InvoiceTable({ invoices, patients, onDelete }: { invoices: Invoice[]; patients: Patient[]; onDelete?: (id:string)=>void }) { if(!invoices.length)return <Empty title="Nessuna fattura trovata" text="Non ci sono fatture per i filtri selezionati."/>; return <div className="overflow-x-auto"><table className="w-full min-w-[720px] text-left text-sm"><thead><tr className="border-b border-line text-xs uppercase tracking-wide text-muted"><th className="pb-3">Numero</th><th className="pb-3">Data</th><th className="pb-3">Paziente</th><th className="pb-3">Prestazione</th><th className="pb-3">Importo</th><th className="pb-3">Stato</th><th/></tr></thead><tbody>{invoices.map(i=>{const p=patients.find(p=>p.id===i.patientId);return <tr key={i.id} className="border-b border-line last:border-0"><td className="py-4 font-bold">{i.number}</td><td>{prettyDate(i.date)}</td><td>{p?.lastName} {p?.firstName}</td><td>{i.service}</td><td className="font-bold">{money(i.amount)}</td><td><Badge tone={i.paid?"green":"amber"}>{i.paid?"Pagata":"Da saldare"}</Badge></td><td><div className="flex justify-end gap-1"><Button variant="ghost" icon="download" onClick={()=>downloadInvoice(i,p)}>PDF</Button>{onDelete&&<Button variant="danger" icon="trash" onClick={()=>confirm("Eliminare definitivamente questa fattura?")&&onDelete(i.id)} aria-label="Elimina fattura"/>}</div></td></tr>})}</tbody></table></div>; }
async function downloadInvoice(i: Invoice, p?: Patient) { const { jsPDF } = await import("jspdf"); const doc=new jsPDF(); doc.setFontSize(20); doc.text("FATTURA",150,22); doc.setFontSize(11); doc.text("Studio OsteoCare",20,22); doc.text("Dr. Andrea Colombo - Osteopata",20,29); doc.text(`Fattura n. ${i.number}`,20,52); doc.text(`Data: ${prettyDate(i.date)}`,20,59); doc.text(`Paziente: ${p?.firstName??""} ${p?.lastName??""}`,20,75); doc.text(`Codice fiscale: ${p?.taxCode??""}`,20,82); doc.line(20,96,190,96); doc.text(i.description,20,108); doc.text(money(i.amount),165,108,{align:"right"}); doc.line(20,116,190,116); doc.setFontSize(13); doc.text(`Totale: ${money(i.amount)}`,190,130,{align:"right"}); doc.setFontSize(9); doc.text(i.tax==="Esente IVA"?"Operazione esente IVA ai sensi dell'art. 10 DPR 633/72":i.tax,20,150); doc.save(`fattura-${i.number.replace("/","-")}.pdf`); }

function Settings() { return <><h2 className="text-2xl font-bold">Impostazioni</h2><p className="mt-1 text-sm text-muted">Configura i dati dello studio e la sicurezza.</p><div className="mt-7 grid gap-5 lg:grid-cols-2"><Card><SectionTitle title="Dati dello studio" subtitle="Informazioni utilizzate nei documenti fiscali"/><div className="mt-5 space-y-4"><Input label="Denominazione" defaultValue="Studio OsteoCare"/><Input label="Professionista" defaultValue="Dr. Andrea Colombo"/><Input label="Indirizzo" defaultValue="Via della Salute 12, Milano"/><Input label="Partita IVA" defaultValue="IT01234567890"/><Button>Salva modifiche</Button></div></Card><Card><SectionTitle title="Privacy e sicurezza" subtitle="Predisposizione per dati sanitari sensibili"/><div className="mt-5 space-y-4"><Setting icon="shield" title="Protezione dati GDPR" text="Row Level Security attiva sul database Supabase."/><Setting icon="lock" title="Accesso protetto" text="Sessione persistente e route riservate agli utenti autenticati."/><Setting icon="file" title="Documenti e consensi" text="Archivio documentale predisposto per i consensi privacy."/></div></Card></div></>; }
function Setting({icon,title,text}:{icon:IconName;title:string;text:string}) { return <div className="flex gap-3 rounded-xl bg-mist p-4"><span className="text-sage"><Icon name={icon}/></span><div><b className="text-sm">{title}</b><p className="mt-1 text-xs leading-5 text-muted">{text}</p></div></div>; }

function PatientModal({ value, close, save }: { value: Patient; close:()=>void; save:(p:Patient)=>void }) { const [p,setP]=useState(value); const f=(key:keyof Patient)=>(e:React.ChangeEvent<HTMLInputElement|HTMLSelectElement>)=>setP({...p,[key]:e.target.value}); const completeCity=()=>{const city=findCity(p.city);if(city)setP({...p,province:city.province,zip:city.zip});}; const computeTaxCode=()=>{const code=calculateTaxCode(p);if(code)setP({...p,taxCode:code});}; return <Modal title={p.id?"Modifica paziente":"Nuovo paziente"} close={close}><datalist id="italian-cities">{italianCities.map(c=><option key={c.name} value={c.name}/>)}</datalist><div className="grid gap-4 sm:grid-cols-2"><Input label="Nome *" value={p.firstName} onChange={f("firstName")}/><Input label="Cognome *" value={p.lastName} onChange={f("lastName")}/><Input label="Telefono" value={p.phone} onChange={f("phone")}/><Input label="Email" value={p.email} onChange={f("email")}/><Input label="Professione" value={p.profession} onChange={f("profession")}/><Input label="Data di nascita" type="date" value={p.birthDate} onChange={f("birthDate")}/><Select label="Sesso" value={p.gender} onChange={f("gender")}><option>F</option><option>M</option><option>Altro</option></Select><Input label="Comune di nascita" list="italian-cities" value={p.birthCity} onChange={f("birthCity")} onBlur={computeTaxCode} placeholder="Cerca comune..."/><div><Input label="Codice fiscale" value={p.taxCode} onChange={f("taxCode")} placeholder="Calcolo automatico o modifica manuale"/><button onClick={computeTaxCode} className="mt-2 text-xs font-bold text-sage">Calcola automaticamente</button></div><Input label="Indirizzo" value={p.address} onChange={f("address")}/><Input label="Citta di residenza" list="italian-cities" value={p.city} onChange={f("city")} onBlur={completeCity} placeholder="Cerca comune..."/><Input label="Provincia" value={p.province} onChange={f("province")}/><Input label="CAP" value={p.zip} onChange={f("zip")}/></div><div className="mt-6 flex justify-end gap-2"><Button variant="secondary" onClick={close}>Annulla</Button><Button onClick={()=>p.firstName&&p.lastName&&save(p)}>Salva paziente</Button></div></Modal>; }
function InvoiceModal({ patients, close, save }: { patients:Patient[]; close:()=>void; save:(f:Invoice)=>void }) { const [f,setF]=useState<Invoice>({id:"",number:`2026/${String(43).padStart(3,"0")}`,date:new Date().toISOString().slice(0,10),patientId:patients[0]?.id||"",service:"Trattamento osteopatico",description:"Seduta individuale",amount:75,tax:"Esente IVA",paid:false}); const set=(key:keyof Invoice)=>(e:React.ChangeEvent<HTMLInputElement|HTMLSelectElement>)=>setF({...f,[key]:key==="amount"?Number(e.target.value):key==="paid"?e.target.value==="true":e.target.value}); return <Modal title="Nuova fattura" close={close}><div className="grid gap-4 sm:grid-cols-2"><Input label="Numero fattura *" value={f.number} onChange={set("number")}/><Input label="Data *" type="date" value={f.date} onChange={set("date")}/><Select label="Paziente *" value={f.patientId} onChange={set("patientId")}>{patients.map(p=><option key={p.id} value={p.id}>{p.lastName} {p.firstName}</option>)}</Select><Input label="Prestazione" value={f.service} onChange={set("service")}/><Input label="Descrizione" value={f.description} onChange={set("description")}/><Input label="Importo" type="number" value={f.amount} onChange={set("amount")}/><Select label="Regime IVA" value={f.tax} onChange={set("tax")}><option>Esente IVA</option><option>IVA 22%</option></Select><Select label="Pagamento" value={String(f.paid)} onChange={set("paid")}><option value="false">Da saldare</option><option value="true">Pagata</option></Select></div><div className="mt-5 rounded-xl bg-mist p-4 text-right"><span className="text-sm text-muted">Totale fattura</span><b className="ml-4 text-xl">{money(f.tax==="IVA 22%"?f.amount*1.22:f.amount)}</b></div><div className="mt-6 flex justify-end gap-2"><Button variant="secondary" onClick={close}>Annulla</Button><Button onClick={()=>save(f)}>Emetti fattura</Button></div></Modal>; }
function Modal({title,close,children}:{title:string;close:()=>void;children:React.ReactNode}) { return <div className="fixed inset-0 z-50 flex items-center justify-center bg-[#17312d]/35 p-4"><div className="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl md:p-7"><div className="mb-6 flex items-center justify-between"><h2 className="text-xl font-bold">{title}</h2><Button variant="ghost" icon="x" onClick={close} aria-label="Chiudi"/></div>{children}</div></div>; }
