# 📘 Struttura Database — Gestionale DG Pulizie

**Database:** `gestionale_dev`  
**Ambiente:** sviluppo locale  
**Versione PostgreSQL:** 16.x  
**Framework:** Laravel 11 (base + Sanctum + Spatie Permission)

---

## 🧱 Tabelle di sistema Laravel

### 1. `migrations`
Tiene traccia delle migration eseguite e del relativo batch.

### 2. `users`
Tabella base utenti.  
Campi: `id`, `name`, `email`, `password`, `remember_token`, `created_at`, `updated_at`.

### 3. `password_reset_tokens`
Gestisce i token per il recupero password.

---

## ⚙️ Tabelle per cache e job

### 4. `cache`
Contiene le chiavi di cache salvate nel DB.

### 5. `cache_locks`
Gestisce i lock temporanei per la cache.

### 6. `jobs`
Coda principale dei job asincroni.

### 7. `job_batches`
Gestione dei batch di job.

### 8. `failed_jobs`
Traccia dei job falliti.

---

## 🔐 Autenticazione e sessioni

### 9. `sessions`
Contiene le sessioni utente (se `SESSION_DRIVER=database`).

### 10. `personal_access_tokens`
Generata da Laravel Sanctum.  
Contiene i token per l’autenticazione API.

---

## 🧩 Gestione ruoli e permessi (pacchetto Spatie)

### 11. `roles`
Elenco dei ruoli (es. `admin`, `dipendente`, `supervisore`).

### 12. `permissions`
Elenco dei permessi (es. `create_user`, `view_report`).

### 13. `model_has_roles`
Associazione tra un modello (es. `User`) e i ruoli assegnati.

### 14. `model_has_permissions`
Associazione diretta tra un modello e i permessi assegnati.

### 15. `role_has_permissions`
Definisce i permessi appartenenti a ciascun ruolo.

---

## ✅ Riassunto

| Categoria | Tabelle |
|------------|----------|
| **Core Laravel** | `users`, `password_reset_tokens`, `migrations` |
| **Sistema (cache / job)** | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` |
| **Autenticazione e sessioni** | `sessions`, `personal_access_tokens` |
| **Permessi e ruoli (Spatie)** | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |

---

## 🧭 Note operative
- Queste tabelle costituiscono la base del framework e **non vanno modificate**.  
- Tutte le nuove tabelle applicative del gestionale dovranno avere **prefisso `dg_`** (es. `dg_companies`, `dg_employees`, `dg_cantieri`), così da distinguerle chiaramente dalle tabelle di sistema.  
- I nuovi modelli Laravel dovranno puntare a queste tabelle prefissate tramite la proprietà `$table` del model.

---

✍️ Documento aggiornato: **16 Ottobre 2025**  
Responsabile: *Valerio Persiani*  
