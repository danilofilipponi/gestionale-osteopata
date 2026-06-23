# Deploy Gestionale Osteopata

Questa guida serve per portare il gestionale dalla macchina locale alla VPS di produzione.

## Ambiente consigliato

- VPS Aruba Cloud OpenStack O4A8 o superiore.
- Ubuntu LTS.
- Nginx.
- PHP 8.3 o superiore.
- MariaDB/MySQL.
- Composer.
- Node.js solo per compilare gli asset, oppure asset gia compilati dal PC locale.
- HTTPS con Let's Encrypt.

Dominio consigliato:

```text
gestionale.osteopatafilipponi.it
```

## Prima del deploy

1. Verificare che il progetto sia pulito:

```bash
git status
```

2. Eseguire i test:

```bash
php artisan test
```

3. Compilare il frontend:

```bash
npm run build
```

4. Verificare che `.env`, backup, database locale e file temporanei non siano tracciati da Git.

## File da non pubblicare nel repository

Non devono finire su GitHub:

- `.env`
- `.env.production`
- database locali
- backup locali
- file temporanei
- credenziali Google Calendar
- credenziali SMTP
- chiavi di accesso server
- cartelle `vendor`, `node_modules`, `storage/logs`, `storage/framework/cache`

## Preparazione server

Sul server installare:

```bash
sudo apt update
sudo apt install nginx mariadb-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath unzip git
```

Installare Composer se non presente.

Creare database e utente dedicato:

```sql
CREATE DATABASE gestionale_osteopata CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestionale_user'@'localhost' IDENTIFIED BY 'PASSWORD_SICURA';
GRANT ALL PRIVILEGES ON gestionale_osteopata.* TO 'gestionale_user'@'localhost';
FLUSH PRIVILEGES;
```

## Configurazione progetto sul server

1. Clonare il repository.
2. Copiare `.env.production.example` in `.env`.
3. Inserire valori reali in `.env`.
4. Generare la chiave se `APP_KEY` e vuota:

```bash
php artisan key:generate
```

5. Installare dipendenze:

```bash
composer install --no-dev --optimize-autoloader
```

6. Eseguire migrazioni:

```bash
php artisan migrate --force
```

7. Collegare storage pubblico se servira usare file accessibili via web:

```bash
php artisan storage:link
```

8. Ottimizzare Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Nginx

Esempio server block:

```nginx
server {
    listen 80;
    server_name gestionale.osteopatafilipponi.it;
    root /var/www/gestionale-osteopata-laravel/public;

    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Cron Laravel

In locale ricordati che servono due finestre PowerShell:

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
php artisan schedule:work
```

In produzione invece serve un cron, non una finestra aperta:

```bash
* * * * * cd /var/www/gestionale-osteopata-laravel && php artisan schedule:run >> /dev/null 2>&1
```

Questo serve per:

- generazione automatica sedute da agenda;
- future automazioni;
- eventuali backup automatici.

## Google Calendar

Nel pannello Google Cloud aggiornare l'URI di redirect:

```text
https://gestionale.osteopatafilipponi.it/google/calendar/callback
```

Poi inserire nel `.env`:

```text
GOOGLE_CALENDAR_CLIENT_ID=
GOOGLE_CALENDAR_CLIENT_SECRET=
GOOGLE_CALENDAR_REDIRECT_URI=https://gestionale.osteopatafilipponi.it/google/calendar/callback
```

## Backup

Regola minima:

- backup giornaliero database;
- backup file caricati e documenti generati;
- copia esterna al VPS;
- conservazione almeno 30 giorni;
- test periodico di ripristino.

I dati sanitari non devono avere come unica copia il disco della VPS.

## Permessi Laravel

Il server web deve poter scrivere in:

```text
storage
bootstrap/cache
```

Esempio:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Controlli finali

Prima di considerare il deploy completato:

```bash
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
php artisan test
```

Verificare dal browser:

- login;
- dashboard;
- pazienti;
- agenda;
- fatture;
- contabilita;
- impostazioni;
- generazione PDF fattura;
- generazione consenso privacy;
- sincronizzazione Google Calendar;
- backup configurato.
