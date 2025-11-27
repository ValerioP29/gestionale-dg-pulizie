<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="280" alt="Laravel Logo">
</p>

<h1 align="center">Gestionale DG Pulizie</h1>

Applicazione gestionale sviluppata per la digitalizzazione operativa e amministrativa della società **DG Pulizie**.  
Basata su **Laravel 11 + Filament + Vue 3 + Tailwind + Sanctum**, con PostgreSQL come database.

---

## 🚀 Stack Tecnologico

| Livello | Tecnologia | Scopo |
|----------|-------------|--------|
| Backend | Laravel 11 | Core logica, API, autenticazione |
| Admin Panel | Filament 3 | Gestione ruoli, utenti, documenti, log |
| Frontend | Vue 3 + Vite + Tailwind + Flowbite | SPA/PWA per dipendenti e responsabili |
| Database | PostgreSQL | Persistenza dati |
| Auth | Laravel Sanctum | Session-based login SPA |
| Ruoli | Spatie Permission | Ruoli e permessi granulari |
| Storage | AWS S3 (prod) / Local (dev) | Archiviazione file |
| Versioning | Git + GitHub | Gestione versioni e branching model |

---

## ⚙️ Installazione locale (sviluppo)

### 1️⃣ Clona il repository
```bash
git clone git@github.com:ValerioP29/gestionale-dg-pulizie.git
cd gestionale-dg-pulizie/laravel
    
### 2️⃣ Installa dipendenze backend
composer install
cp .env.example .env
php artisan key:generate

3️⃣ Configura .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gestionale_dev
DB_USERNAME=gest_user
DB_PASSWORD=localpass

FILESYSTEM_DISK=local
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
SESSION_DOMAIN=localhost

4️⃣ Crea e popola database
php artisan migrate
php artisan db:seed --class=RolesSeeder

5️⃣ Installa dipendenze frontend
npm install

6️⃣ Avvia i server
npm run dev:full

🔐 Autenticazione

SPA (Vue) e Filament condividono Sanctum come sistema di sessione.

API base (routes/api.php) gestisce login/logout/me.

Ruoli gestiti con Policies.

Esempio rapido login da frontend (Axios):

import axios from "axios";
axios.defaults.withCredentials = true;
await axios.get("/sanctum/csrf-cookie");
await axios.post("/login", { email, password });

🧩 Struttura cartelle principale:
/app
  /Models (User.php con HasRoles)
  /Providers
/config
/database
/public
/resources
  /js
    /src/helpers/auth.js
    /components
    /App.vue
  /css/app.css
/routes
  web.php
  api.php

🧰 Script NPM
Script	Scopo
npm run dev	Vite dev server
npm run build	Compila per produzione
npm run preview	Anteprima build
npm run dev:full	Avvia Laravel + Vite insieme

🌩️ Storage remoto (S3)

In .env.production:
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<key_prod>
AWS_SECRET_ACCESS_KEY=<secret_prod>
AWS_DEFAULT_REGION=eu-south-1
AWS_BUCKET=<bucket_prod>
In sviluppo, resta local.

🧑‍💻 Accesso pannello admin

URL: http://127.0.0.1:8000/admin
Utente seedato:
Email: admin@example.test
Password: password

🔖 Git & Branch Model
Branch	Scopo
main	Produzione
develop	Sviluppo
feature/*	Funzionalità
hotfix/*	Correzioni urgenti

## 🧵 Queue worker (produzione)

Per eseguire in modo asincrono i job notturni (calcolo sessioni, rigenerazione report, marcatura report finali):

1. Imposta `QUEUE_CONNECTION=database` (o `redis` se l'infrastruttura è disponibile) e avvia le migration per la coda (`php artisan queue:table && php artisan migrate`).
2. Avvia un worker dedicato: `php artisan queue:work --tries=3 --backoff=10` e ricaricalo con `php artisan queue:restart` dopo ogni deploy.
3. Scheduler cron: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.

Esempio di configurazione Supervisor (`/etc/supervisor/conf.d/gestionale-queue.conf`):

```
[program:gestionale-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --backoff=10
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/gestionale-queue.log
stopwaitsecs=3600
```

🧾 Licenza

Software proprietario © 2025 DG Pulizie.
Uso riservato interno e aziendale.
