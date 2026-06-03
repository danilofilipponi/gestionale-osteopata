-- Aggiorna il profilo applicativo dello studio gia creato.
-- Eseguire in Supabase SQL Editor dopo profile-trigger.sql.

update public.utenti
set
  nome = 'Danilo',
  cognome = 'Filipponi',
  ruolo = 'osteopata'
where email is not null;
