# Flusso dati utente (PWA)

## API chiamate dopo il login
- La PWA effettua `POST /api/login` tramite `useAuthStore.login()` (`src/stores/auth.js`). L'endpoint ora restituisce `{ token, user }`, dove `token` è un Personal Access Token Sanctum usato come Bearer dal client.
- Dopo l'autenticazione, le schermate protette richiamano:
  - `GET /api/me` per il profilo (`Profile.vue`).
  - `GET /api/mobile/work-sessions/current` per stato sessione e cantiere assegnato (`session.js` usato dalla home/badge).
  - `GET /api/mobile/payroll` per le buste paga (`payroll.js`).

## Dove vengono salvati i dati
- **Token**: `useAuthStore` conserva il token in stato Pinia e in `localStorage` con chiave `dg-pulizie-token` (`src/utils/storage.js`).
- **Sessione di lavoro**: `useSessionStore` salva `assignedSite` e `activeSession` ottenuti da `/api/mobile/work-sessions/current` o dalla timbratura (`/api/mobile/work-sessions/punch`).
- **Buste paga**: `usePayrollStore` salva la lista `payrolls` caricata da `/api/mobile/payroll`.
- Non esiste uno store dedicato ai dati anagrafici; la pagina profilo mantiene i dati in uno `ref` locale (`profile` in `Profile.vue`).

## Da dove legge la pagina profilo
- `Profile.vue` chiama `GET /api/me` e si aspetta i campi `first_name`, `last_name`, `email`, `main_site_name` per popolare la vista.
- L'API attuale restituisce solo `id`, `name`, `role`, `active` attraverso `AuthenticatedUserResource` (usato sia in `/api/login` sia in `/api/me`). I campi richiesti dalla UI risultano quindi `null`/`-`, motivo per cui il profilo appare vuoto nonostante il login riesca.

## Collegamento buste paga e cantieri
- **Buste paga**: l'endpoint `GET /api/mobile/payroll` filtra per `user_id` dell'utente autenticato e `visible_to_employee = true`, quindi è già collegato al dipendente lato API. La PWA mostra la lista e consente il download tramite `GET /api/mobile/payroll/{id}/download`.
- **Cantieri e badge**: la PWA ottiene `assignedSite` da `/api/mobile/work-sessions/current`. Il backend usa `SiteResolverService::resolveAssignedSite()` per determinare il cantiere corrente dall'assegnazione attiva (`dg_site_assignments`) o dal `main_site_id` dell'utente, e include questi dati nella risposta. Le timbrature (`/api/mobile/work-sessions/punch`) usano lo stesso resolver per legare la sessione di lavoro al cantiere.

## Cosa serve per mostrare dati completi e collegamenti
- **Profilo utente**: estendere `AuthenticatedUserResource` (e quindi `/api/me` e `/api/login`) includendo `first_name`, `last_name`, `email`, `main_site_id` e nome/indirizzo del cantiere principale (es. `main_site_name`, `main_site_address`). In alternativa, creare un `UserProfileResource` più completo e usarlo per `/api/me`.
- **Buste paga**: gli endpoint già filtrano per utente; assicurarsi che il caricamento in Filament imposti `visible_to_employee=true`, `user_id` corretto e `file_path/storage_disk` validi. La PWA leggerà automaticamente la lista.
- **Cantieri assegnati e badge**: l'assegnazione è già considerata tramite `SiteResolverService`. Per mostrarli chiaramente in app si può:
  - esporre un endpoint (es. `/api/mobile/assigned-sites`) che restituisca il cantiere attuale e la cronologia delle assegnazioni;
  - mostrare in Home il nome/indirizzo del cantiere da `assignedSite` e, se assente, indicare che non ci sono assegnazioni attive.
- **Flusso di login/token**: la PWA salva un Bearer token (`data.token`) generato da Sanctum. `/api/login` ora crea un Personal Access Token e lo restituisce insieme alla risorsa utente; `/api/me` continua a usare la stessa risorsa per mantenere i dati allineati dopo il refresh.
