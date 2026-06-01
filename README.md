# OsteoCare

MVP navigabile di un gestionale web per studio di osteopatia, sviluppato con Next.js, React, TypeScript, Tailwind CSS e predisposizione Supabase.

## Avvio locale

Su Windows e sufficiente fare doppio clic su `AVVIA-GESTIONALE.bat`.

In alternativa:

```bash
npm install
npm run dev
```

Aprire `http://localhost:3000`. Senza variabili Supabase il progetto usa la modalita dimostrativa; e sufficiente premere il pulsante di accesso.

## Collegamento Supabase

1. Creare un progetto Supabase.
2. Eseguire `supabase/schema.sql` nell'editor SQL.
3. Eseguire `supabase/profile-trigger.sql` nell'editor SQL.
4. Copiare `.env.example` in `.env.local` e compilare URL e chiave pubblica.
5. Creare un utente da Supabase Authentication. Il relativo profilo in `public.utenti` viene creato automaticamente.

## Contenuto MVP

- login protetto, recupero password e predisposizione sessione Supabase;
- dashboard responsive;
- elenco, ricerca, inserimento, modifica ed eliminazione pazienti salvati su Supabase;
- anagrafica e cartella clinica salvate su Supabase, con sezioni predisposte per sedute, fatture e documenti;
- elenco fatture Supabase con filtri, inserimento, eliminazione con conferma ed esportazione PDF;
- schema PostgreSQL relazionale con Row Level Security.

Per completare l'uso in produzione vanno collegati sedute e pagamenti, esteso il dataset dimostrativo dei comuni con il dataset completo ISTAT e configurato Supabase Storage per i documenti.
