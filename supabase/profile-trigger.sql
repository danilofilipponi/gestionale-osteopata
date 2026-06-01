-- Eseguire dopo schema.sql.
-- Crea il profilo applicativo per gli utenti Auth esistenti e futuri.

create or replace function public.crea_profilo_utente()
returns trigger
language plpgsql
security definer set search_path = ''
as $$
begin
  insert into public.utenti (id, nome, cognome, email)
  values (
    new.id,
    coalesce(new.raw_user_meta_data ->> 'nome', 'Professionista'),
    coalesce(new.raw_user_meta_data ->> 'cognome', 'Studio'),
    coalesce(new.email, '')
  )
  on conflict (id) do nothing;
  return new;
end;
$$;

drop trigger if exists on_auth_user_created on auth.users;

create trigger on_auth_user_created
  after insert on auth.users
  for each row execute procedure public.crea_profilo_utente();

insert into public.utenti (id, nome, cognome, email)
select
  id,
  coalesce(raw_user_meta_data ->> 'nome', 'Professionista'),
  coalesce(raw_user_meta_data ->> 'cognome', 'Studio'),
  coalesce(email, '')
from auth.users
on conflict (id) do nothing;
