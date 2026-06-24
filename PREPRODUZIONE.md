# Checklist pre-produzione

## Stato attuale

- Progetto Laravel funzionante in locale.
- Login attivo.
- Registrazione pubblica bloccata.
- Dashboard, pazienti, anamnesi, sedute, agenda, fatture, contabilita e impostazioni presenti.
- Google Calendar collegato in locale.
- Automazione sedute collegata allo scheduler Laravel.
- Test automatici presenti e funzionanti.

## Cose da decidere prima della VPS

1. Dominio definitivo:

```text
gestionale.osteopatafilipponi.it
```

2. VPS:

```text
Aruba Cloud VPS OpenStack O4A8
4 vCPU / 8 GB RAM / 80 GB NVMe
```

3. Database produzione:

```text
MySQL o MariaDB
```

4. Backup esterno:

```text
Aruba Cloud Backup, storage esterno, o altra destinazione cifrata
```

5. Dati iniziali:

- importare solo dati reali;
- evitare dati di prova;
- mantenere una copia Excel locale di sicurezza.

## Sicurezza

- `APP_ENV=production`
- `APP_DEBUG=false`
- HTTPS obbligatorio
- sessioni cifrate
- password admin forte
- accesso SSH solo con password forte o chiave
- database non esposto pubblicamente
- backup non salvato solo sulla VPS
- credenziali Google e SMTP solo nel `.env` di produzione

## File preparati

- `.env.production.example`
- `DEPLOY.md`
- `PREPRODUZIONE.md`

## Prima di pubblicare

Eseguire:

```bash
php artisan test
npm run build
php artisan view:cache
php artisan route:list --except-vendor
php artisan migrate:status
php artisan view:clear
```

## Dopo il deploy

1. Creare DNS:

```text
Tipo: A
Nome: gestionale
Valore: IP VPS
```

2. Attivare SSL.
3. Collegare Google Calendar con il nuovo redirect.
4. Configurare cron Laravel.
5. Configurare backup dalla pagina Impostazioni > Backup.
6. Fare prova completa con un paziente, una seduta, una fattura e un PDF.

## Prova backup

Prima della pubblicazione verificare che il comando crei correttamente lo ZIP locale:

```bash
php artisan backup:run
```

Il backup locale e solo una prima protezione: per i dati sanitari serve anche una copia esterna alla VPS.
