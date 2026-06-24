# Pubblicazione online

Guida rapida per pubblicare il gestionale su:

```text
https://gestionale.osteopatafilipponi.it
```

## 1. Appena Aruba attiva la VPS

Annotare:

```text
IP pubblico VPS:
utente SSH:
password temporanea o chiave SSH:
sistema operativo:
```

Il sistema operativo consigliato e:

```text
Ubuntu LTS
```

## 2. Creare il sottodominio

Nel pannello DNS di `osteopatafilipponi.it` creare:

```text
Tipo: A
Nome: gestionale
Valore: IP pubblico VPS
TTL: default oppure 3600
```

La propagazione puo richiedere da pochi minuti ad alcune ore.

## 3. Preparare Google Calendar

Nel progetto Google Cloud aggiungere questo URI autorizzato:

```text
https://gestionale.osteopatafilipponi.it/google/calendar/callback
```

In produzione bisognera poi ricollegare Google Calendar dalla pagina:

```text
Impostazioni > Agenda
```

## 4. File ambiente produzione

Sul server copiare:

```text
.env.production.example -> .env
```

Poi compilare almeno questi valori:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gestionale.osteopatafilipponi.it
DB_DATABASE=gestionale_osteopata
DB_USERNAME=gestionale_user
DB_PASSWORD=password_sicura
GOOGLE_CALENDAR_CLIENT_ID=...
GOOGLE_CALENDAR_CLIENT_SECRET=...
GOOGLE_CALENDAR_REDIRECT_URI=https://gestionale.osteopatafilipponi.it/google/calendar/callback
```

Non caricare mai `.env` su GitHub.

## 5. Comandi server principali

Da eseguire dentro la cartella del progetto sul server:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Se gli asset non sono gia compilati:

```bash
npm ci
npm run build
```

## 6. Cron Laravel obbligatorio

In produzione non si usa `schedule:work`. Va configurato il cron:

```bash
* * * * * cd /var/www/gestionale-osteopata-laravel && php artisan schedule:run >> /dev/null 2>&1
```

Serve per:

- backup automatici;
- generazione automatica sedute da agenda;
- future automazioni.

## 7. SSL

Attivare HTTPS con Let's Encrypt sul sottodominio:

```text
gestionale.osteopatafilipponi.it
```

Il gestionale deve girare solo in HTTPS per proteggere login, dati sanitari e cookie.

## 8. Backup

Prima regola:

```text
mai avere una sola copia dei dati sulla VPS
```

Configurare:

- backup interno da Impostazioni > Backup;
- copia esterna alla VPS;
- prova periodica di ripristino.

## 9. Controlli finali post-deploy

Dal server:

```bash
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
```

Dal browser:

- login;
- dashboard;
- pazienti;
- anamnesi;
- sedute;
- agenda;
- sincronizzazione Google Calendar;
- fatture e anteprima PDF;
- consenso privacy;
- contabilita;
- backup manuale.

## 10. Dati iniziali

Dopo la prima pubblicazione:

1. creare o verificare utente admin;
2. importare pazienti;
3. importare fatture;
4. importare contabilita se necessario;
5. collegare Google Calendar;
6. fare un backup subito dopo l'importazione.
