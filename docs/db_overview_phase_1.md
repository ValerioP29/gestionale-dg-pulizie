# 🧱 DG Pulizie – Database Overview (Fase 1)

**Versione:** 1.0  
**Ultimo aggiornamento:** 2025-10-17  
**Autore:** Valerio Persiani  
**Stack:** Laravel 11 / Filament 3 / Vue 3 / PostgreSQL 16  
**Obiettivo:** definire uno schema dati solido, scalabile e coerente con l’architettura DG Pulizie.

---

## 📘 1. Struttura generale
Il database è organizzato in 8 macro-aree logiche:

1. **Utenti e ruoli (auth)**
2. **Cantieri e assegnazioni**
3. **Presenze e timbrature (geo-badge)**
4. **Buste paga e documenti**
5. **Report e statistiche**
6. **Log e audit attività**
7. **Impostazioni e consensi privacy**
8. **Supporto sincronizzazione offline**
9. **Gestione dispositivi**

---

## 👥 1. Users & Roles
Gestione autenticazione e permessi.

**Tabelle principali**
- `users` – anagrafica dipendenti e amministratori  
- `roles`, `model_has_roles`, `role_has_permissions` – gestite da *spatie/laravel-permission*

**Campi chiave (`users`):**
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `id` | bigserial PK | Identificativo |
| `first_name`, `last_name` | varchar | Nome e cognome |
| `email` | varchar(255) | Univoco |
| `password` | varchar(255) | Hash bcrypt |
| `phone` | varchar(30) | Opzionale |
| `active` | boolean | Stato account |
| `created_at`, `updated_at` | timestamp | Timestamps standard |

**Relazioni**
- `User` hasMany `DgPunch`
- `User` belongsToMany `DgSite` (via `DgSiteAssignment`)
- `User` hasMany `DgWorkSession`
- `User` hasMany `DgPayslip`
- `User` hasMany `DgUserConsent`
- `User` hasMany `DgSyncQueue`
- `User` hasMany `DgDevice`

---

## 🏗️ 2. Cantieri e Assegnazioni

### `dg_sites`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `id` | bigserial PK | Identificativo |
| `name` | varchar(150) | Nome cantiere |
| `address` | varchar(255) | Indirizzo completo |
| `latitude` / `longitude` | decimal(10,7) | Coordinate GPS |
| `radius_m` | integer | Raggio operativo |
| `active` | boolean | Stato |
| `created_at`, `updated_at` | timestamp |  |

### `dg_site_assignments`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` | bigint FK | Dipendente assegnato |
| `site_id` | bigint FK | Cantiere |
| `assigned_from` / `assigned_to` | date | Periodo validità |
| `assigned_by` | bigint FK → users.id | Chi ha assegnato |
| `notes` | text | Note interne |
| `created_at`, `updated_at` | timestamp |  |

**Relazioni**
- `DgSite` hasMany `DgSiteAssignment`
- `DgSite` hasMany `DgPunch`
- `DgSiteAssignment` belongsTo `User` e `DgSite`

---

## ⏱️ 3. Presenze e Timbrature

### `dg_punches`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `uuid` | varchar(36) unique | ID universale |
| `user_id` / `site_id` | bigint FK | Relazioni |
| `type` | enum('check_in','check_out') | Tipo timbro |
| `latitude` / `longitude` | decimal(10,7) | Posizione |
| `accuracy_m` | integer | Precisione GPS |
| `device_id` | varchar(255) | ID dispositivo |
| `device_battery` | integer | Stato batteria |
| `network_type` | varchar(50) | WiFi / 4G / Offline |
| `synced_at` | timestamp | Data sincronizzazione |
| `created_at` | timestamp | Data timbro |

### `dg_work_sessions`
Aggregato giornaliero di presenze.

| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` / `site_id` | bigint FK | Relazioni |
| `check_in` / `check_out` | timestamp | Orari effettivi |
| `worked_minutes` | integer | Durata |
| `session_date` | date | Giorno riferimento |
| `status` | enum('complete','incomplete','invalid') | Stato sessione |
| `created_at`, `updated_at` | timestamp |  |

**Relazioni**
- `User` hasMany `DgWorkSession`
- `DgWorkSession` belongsTo `User` e `DgSite`

---

## 💼 4. Buste Paga e Documenti

### `dg_payslips`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` | bigint FK | Dipendente |
| `file_name` / `file_path` | varchar | Nome e path |
| `storage_disk` | varchar(50) default 's3' | Storage |
| `mime_type` | varchar(100) | Tipo file |
| `file_size` | integer | Peso |
| `checksum` | varchar(64) | SHA1 integrità |
| `period_year` / `period_month` | int | Riferimento |
| `uploaded_by` | bigint FK | Amministratore |
| `uploaded_at`, `downloaded_at` | timestamp | Log attività |
| `downloads_count` | integer | Contatore |

---

## 📊 5. Report e Statistiche

### `dg_reports_cache`
Tabella opzionale per caching aggregati.

| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` / `site_id` | bigint FK | Relazioni |
| `period_start` / `period_end` | date | Periodo |
| `worked_hours` | decimal(5,2) | Ore totali |
| `is_valid` | boolean | Stato validità |
| `generated_at` | timestamp | Ultima generazione |

Indici consigliati su `(period_start, period_end)` e `(user_id, site_id)`.

---

## 🧾 6. Log e Audit Attività
Implementato tramite **spatie/laravel-activitylog**.  
Le estensioni future prevedono:
- `ip_address` varchar(45)
- `user_agent` text  

per migliorare la tracciabilità delle azioni amministrative.

---

## 🔐 7. Impostazioni & Consensi Privacy

### `dg_user_consents`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` | bigint FK | Utente |
| `type` | enum('privacy','localization') | Tipo consenso |
| `accepted` | boolean | Stato |
| `accepted_at` / `revoked_at` | timestamp | Log temporale |
| `source` | varchar(50) | Origine consenso (app, admin, webform) |

---

## 🔄 8. Sincronizzazione Offline

### `dg_sync_queue`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` | bigint FK | Utente |
| `uuid` | varchar(36) unique | ID universale |
| `payload` | jsonb | Dati da sincronizzare |
| `status` | enum('pending','processing','synced','error') | Stato |
| `synced` | boolean | Flag sincronizzazione |
| `error_message` | text | Dettaglio errore |
| `retry_count` | int default 0 | Tentativi |
| `synced_at` | timestamp | Ultima sync |
| `created_at`, `updated_at` | timestamp |  |

Serve come buffer per l’app PWA offline.

---

## 📱 9. Dispositivi Registrati

### `dg_devices`
| Campo | Tipo | Descrizione |
|--------|------|-------------|
| `user_id` | bigint FK | Proprietario |
| `device_id` | varchar(255) | Identificativo univoco |
| `platform` | enum('android','ios','pwa') | Tipo dispositivo |
| `registered_at`, `last_sync_at` | timestamp | Log utilizzo |
| `created_at`, `updated_at` | timestamp |  |

---

## ⚙️ 10. Ottimizzazioni PostgreSQL

- Indici su tutte le **FK**  
- Indici su `created_at` per `dg_punches` e `dg_work_sessions`  
- Possibile uso di `uuid_generate_v4()` lato DB per automatizzare UUID  
- Estensione **PostGIS** consigliata per query geospaziali future

---

## 🧭 11. Relazioni Eloquent principali

```text
User
 ├── hasMany DgPunch
 ├── hasMany DgWorkSession
 ├── hasMany DgPayslip
 ├── hasMany DgUserConsent
 ├── hasMany DgSyncQueue
 ├── hasMany DgDevice
 └── belongsToMany DgSite (through DgSiteAssignment)

DgSite
 ├── hasMany DgPunch
 ├── hasMany DgWorkSession
 └── hasMany DgSiteAssignment
