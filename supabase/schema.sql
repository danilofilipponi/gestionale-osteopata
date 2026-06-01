-- OsteoCare MVP - schema PostgreSQL per Supabase
create extension if not exists "uuid-ossp";

create table public.utenti (
  id uuid primary key references auth.users(id) on delete cascade,
  nome text not null,
  cognome text not null,
  email text not null,
  ruolo text not null default 'osteopata',
  created_at timestamptz not null default now()
);

create table public.pazienti (
  id uuid primary key default uuid_generate_v4(),
  user_id uuid not null references public.utenti(id) on delete cascade,
  cognome text not null,
  nome text not null,
  telefono text,
  email text,
  professione text,
  data_nascita date,
  sesso text check (sesso in ('M', 'F', 'Altro')),
  comune_nascita text,
  codice_fiscale varchar(16),
  indirizzo text,
  citta text,
  provincia varchar(2),
  cap varchar(5),
  consenso_privacy_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table public.cartelle_cliniche (
  id uuid primary key default uuid_generate_v4(),
  paziente_id uuid not null unique references public.pazienti(id) on delete cascade,
  user_id uuid not null references public.utenti(id) on delete cascade,
  motivo_consulto text,
  inizio_sintomatologia text,
  descrizione_dolore text,
  indagini_eseguite text,
  trattamenti_eseguiti text,
  traumi text,
  chirurgia text,
  problematiche_viscerali text,
  protesi_vista_plantari text,
  ortodonzia text,
  anamnesi_familiare_parto text,
  abitudini_vita_sport text,
  sfera_fisica_psichica text,
  farmaci text,
  updated_at timestamptz not null default now()
);

create table public.sedute (
  id uuid primary key default uuid_generate_v4(),
  paziente_id uuid not null references public.pazienti(id) on delete cascade,
  user_id uuid not null references public.utenti(id) on delete cascade,
  data timestamptz not null,
  trattamento text not null,
  note text,
  importo numeric(10,2),
  created_at timestamptz not null default now()
);

create table public.fatture (
  id uuid primary key default uuid_generate_v4(),
  paziente_id uuid not null references public.pazienti(id) on delete restrict,
  user_id uuid not null references public.utenti(id) on delete cascade,
  numero text not null,
  data date not null,
  prestazione text not null,
  descrizione text,
  regime_iva text not null default 'esente',
  aliquota_iva numeric(5,2) not null default 0,
  imponibile numeric(10,2) not null,
  totale numeric(10,2) not null,
  stato text not null default 'non_pagata' check (stato in ('pagata', 'non_pagata')),
  created_at timestamptz not null default now(),
  unique(user_id, numero)
);

create table public.righe_fattura (
  id uuid primary key default uuid_generate_v4(),
  fattura_id uuid not null references public.fatture(id) on delete cascade,
  descrizione text not null,
  quantita numeric(10,2) not null default 1,
  prezzo_unitario numeric(10,2) not null,
  aliquota_iva numeric(5,2) not null default 0
);

create table public.pagamenti (
  id uuid primary key default uuid_generate_v4(),
  fattura_id uuid not null references public.fatture(id) on delete cascade,
  user_id uuid not null references public.utenti(id) on delete cascade,
  data date not null,
  importo numeric(10,2) not null,
  metodo text,
  note text,
  created_at timestamptz not null default now()
);

create table public.documenti (
  id uuid primary key default uuid_generate_v4(),
  paziente_id uuid not null references public.pazienti(id) on delete cascade,
  user_id uuid not null references public.utenti(id) on delete cascade,
  nome_file text not null,
  storage_path text not null,
  tipo text,
  created_at timestamptz not null default now()
);

create index pazienti_user_idx on public.pazienti(user_id);
create index sedute_paziente_idx on public.sedute(paziente_id);
create index fatture_paziente_idx on public.fatture(paziente_id);
create index fatture_data_idx on public.fatture(data);
create index documenti_paziente_idx on public.documenti(paziente_id);

alter table public.utenti enable row level security;
alter table public.pazienti enable row level security;
alter table public.cartelle_cliniche enable row level security;
alter table public.sedute enable row level security;
alter table public.fatture enable row level security;
alter table public.righe_fattura enable row level security;
alter table public.pagamenti enable row level security;
alter table public.documenti enable row level security;

create policy "utente vede il proprio profilo" on public.utenti for select using (id = auth.uid());
create policy "utente modifica il proprio profilo" on public.utenti for update using (id = auth.uid());

create policy "utente gestisce i propri pazienti" on public.pazienti for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce le proprie cartelle" on public.cartelle_cliniche for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce le proprie sedute" on public.sedute for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce le proprie fatture" on public.fatture for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce i propri pagamenti" on public.pagamenti for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce i propri documenti" on public.documenti for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "utente gestisce le righe delle proprie fatture" on public.righe_fattura for all using (
  exists (select 1 from public.fatture where fatture.id = righe_fattura.fattura_id and fatture.user_id = auth.uid())
) with check (
  exists (select 1 from public.fatture where fatture.id = righe_fattura.fattura_id and fatture.user_id = auth.uid())
);
