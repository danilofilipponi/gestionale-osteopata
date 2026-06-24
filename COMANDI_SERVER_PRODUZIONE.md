# Comandi server produzione

Promemoria operativo per installare il gestionale su VPS Ubuntu.

Dominio previsto:

```text
gestionale.osteopatafilipponi.it
```

Cartella prevista:

```text
/var/www/gestionale-osteopata-laravel
```

## 1. Aggiornare server

```bash
sudo apt update
sudo apt upgrade -y
```

## 2. Installare pacchetti base

```bash
sudo apt install -y nginx mariadb-server unzip git curl ca-certificates
```

## 3. Installare PHP e moduli necessari

```bash
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
```

## 4. Installare Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

## 5. Installare Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 6. Creare database

Entrare in MariaDB:

```bash
sudo mariadb
```

Dentro MariaDB:

```sql
CREATE DATABASE gestionale_osteopata CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestionale_user'@'localhost' IDENTIFIED BY 'INSERIRE_PASSWORD_SICURA';
GRANT ALL PRIVILEGES ON gestionale_osteopata.* TO 'gestionale_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 7. Clonare progetto

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/danilofilipponi/gestionale-osteopata.git gestionale-osteopata-laravel
sudo chown -R $USER:www-data /var/www/gestionale-osteopata-laravel
cd /var/www/gestionale-osteopata-laravel
```

## 8. Preparare file ambiente

```bash
cp .env.production.example .env
nano .env
```

Compilare almeno:

```text
APP_KEY=
APP_URL=https://gestionale.osteopatafilipponi.it
DB_PASSWORD=...
GOOGLE_CALENDAR_CLIENT_ID=...
GOOGLE_CALENDAR_CLIENT_SECRET=...
GOOGLE_CALENDAR_REDIRECT_URI=https://gestionale.osteopatafilipponi.it/google/calendar/callback
```

Poi:

```bash
php artisan key:generate
```

## 9. Installare progetto

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
```

## 10. Caricare file privati

Caricare la firma privata qui:

```text
storage/app/private/firma-filipponi-danilo.png
```

Poi correggere permessi:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 11. Ottimizzare Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 12. Configurare Nginx

Creare file:

```bash
sudo nano /etc/nginx/sites-available/gestionale-osteopata
```

Contenuto:

```nginx
server {
    listen 80;
    server_name gestionale.osteopatafilipponi.it;
    root /var/www/gestionale-osteopata-laravel/public;

    index index.php index.html;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

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

Attivare sito:

```bash
sudo ln -s /etc/nginx/sites-available/gestionale-osteopata /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 13. Attivare HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d gestionale.osteopatafilipponi.it
```

## 14. Attivare cron Laravel

```bash
crontab -e
```

Aggiungere:

```bash
* * * * * cd /var/www/gestionale-osteopata-laravel && php artisan schedule:run >> /dev/null 2>&1
```

## 15. Controlli finali

```bash
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
```

Dal browser verificare:

```text
https://gestionale.osteopatafilipponi.it
```

Controllare:

- login;
- dashboard;
- pazienti;
- agenda;
- Google Calendar;
- fatture PDF;
- privacy;
- backup manuale.

## 16. Comando deploy aggiornamenti futuri

Quando il gestionale e gia online:

```bash
cd /var/www/gestionale-osteopata-laravel
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```
