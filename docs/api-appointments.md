# API — Appointments (Appuntamenti)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Appuntamenti (cliente)](#appuntamenti-cliente)
  - [GET /appointments — Lista appuntamenti del cliente](#get-appointments)
  - [POST /appointments — Prenota appuntamento](#post-appointments)
  - [DELETE /appointments/{id} — Cancella appuntamento](#delete-appointmentsid)
- [Appuntamenti (staff)](#appuntamenti-staff)
  - [GET /appointments-manage — Lista gestione appuntamenti](#get-appointments-manage)
  - [GET /appointments/{id} — Dettaglio appuntamento](#get-appointmentsid)
  - [PUT /appointments/{id} — Aggiorna appuntamento](#put-appointmentsid)
  - [PATCH /appointments/{id}/reschedule — Riprogramma appuntamento](#patch-appointmentsidreschedule)
  - [GET /appointments-calendar — Eventi calendario](#get-appointments-calendar)
  - [GET /appointments-practices — Pratiche per modale](#get-appointments-practices)
- [Endpoint di supporto](#endpoint-di-supporto)
  - [GET /users/available — Utenti disponibili](#get-usersavailable)
  - [GET /practice-types — Tipi pratica](#get-practice-types)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Appuntamenti (cliente)

Questi endpoint sono pensati per l'app mobile del cliente. L'utente autenticato deve avere un `clientProfile` associato.

---

### GET /appointments

Restituisce la lista paginata degli appuntamenti del cliente autenticato, ordinati per data crescente.

**Auth:** Utente autenticato con `clientProfile` associato

**Comportamento:**
- Restituisce solo gli appuntamenti del profilo cliente collegato all'utente autenticato.
- Include le relazioni `assignedUser`, `practiceType`, `practice`.

**Risposta 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "client_profile_id": 3,
      "practice_type_id": 2,
      "practice_id": null,
      "assigned_user_id": 4,
      "scheduled_at": "2026-04-10T10:00:00.000000Z",
      "duration_minutes": 60,
      "status": "confermato",
      "notes": null,
      "created_by": 3,
      "created_at": "2026-03-23T09:00:00.000000Z",
      "updated_at": "2026-03-23T09:00:00.000000Z",
      "assigned_user": { "id": 4, "name": "Lucia Verdi" },
      "practice_type": { "id": 2, "name": "ISEE", "color": "#22C55E" },
      "practice": null
    }
  ],
  "per_page": 20,
  "total": 5
}
```

**Errori:**

| Status | Causa                                                        |
|--------|--------------------------------------------------------------|
| 403    | L'utente autenticato non ha un `clientProfile` associato     |

---

### POST /appointments

Prenota un nuovo appuntamento per il cliente autenticato.

**Auth:** Utente autenticato con `clientProfile` associato  
**Policy:** `appointments.create`

**Content-Type:** `application/json`

**Campi:**

| Campo             | Tipo     | Obbligatorio | Validazione                                      |
|-------------------|----------|--------------|--------------------------------------------------|
| client_profile_id | int      | Sì           | Deve corrispondere al profilo del cliente autenticato |
| practice_type_id  | int      | Sì           | Deve esistere in `practice_types`                |
| practice_id       | int      | No           | Deve esistere in `practices`                     |
| scheduled_at      | datetime | Sì           | Data futura (after:now), formato ISO 8601        |
| duration_minutes  | int      | Sì           | Minimo 5 minuti                                  |
| notes             | string   | No           | Max 1000 caratteri                               |
| assigned_user_id  | int      | No           | Deve esistere in `users`                         |

**Comportamento:**
- Lo `status` viene impostato automaticamente a `da_confermare`.
- `created_by` viene impostato all'utente autenticato.
- `client_profile_id` viene verificato lato server: deve corrispondere al profilo del cliente autenticato. Se non corrisponde, restituisce 403.

**Esempio:**
```json
{
  "client_profile_id": 3,
  "practice_type_id": 2,
  "scheduled_at": "2026-04-10T10:00:00.000000Z",
  "duration_minutes": 60,
  "notes": "Preferisco il mattino."
}
```

**Risposta 201:**
```json
{
  "message": "Appuntamento creato.",
  "data": {
    "id": 7,
    "client_profile_id": 3,
    "practice_type_id": 2,
    "practice_id": null,
    "assigned_user_id": null,
    "scheduled_at": "2026-04-10T10:00:00.000000Z",
    "duration_minutes": 60,
    "status": "da_confermare",
    "notes": "Preferisco il mattino.",
    "created_by": 3,
    "created_at": "2026-03-23T09:00:00.000000Z",
    "updated_at": "2026-03-23T09:00:00.000000Z",
    "assigned_user": null,
    "practice_type": { "id": 2, "name": "ISEE", "color": "#22C55E" }
  }
}
```

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | `clientProfile` mancante o `client_profile_id` non corrisponde|
| 422    | Validazione fallita (es. data nel passato)                     |

---

### DELETE /appointments/{id}

Cancella un appuntamento. Non elimina il record: imposta lo `status` a `cancellato`.

**Auth:** Utente autenticato con `clientProfile` associato

**URL parameters:**

| Parametro | Tipo | Descrizione     |
|-----------|------|-----------------|
| id        | int  | ID appuntamento |

**Comportamento:**
- L'utente può cancellare solo i propri appuntamenti (`client_profile_id` deve corrispondere).
- Gli appuntamenti con status `completato` non possono essere cancellati (restituisce 422).

**Risposta 200:**
```json
{
  "message": "Appuntamento cancellato."
}
```

**Errori:**

| Status | Causa                                                        |
|--------|--------------------------------------------------------------|
| 403    | Appuntamento non appartiene al cliente autenticato           |
| 422    | Appuntamento già completato, non cancellabile                |

---

## Appuntamenti (staff)

Questi endpoint sono riservati allo staff (admin, employee) per la gestione completa degli appuntamenti.

---

### GET /appointments-manage

Restituisce la lista paginata per la gestione operativa degli appuntamenti, con filtri per staff e amministrazione.

**Auth:** `appointments.view-any` oppure `appointments.view-own`

**Query parameters:**

| Parametro        | Tipo   | Default | Descrizione |
|------------------|--------|---------|-------------|
| search           | string | —       | Ricerca su cliente, utente assegnato o tipo pratica |
| status           | string | —       | Stato appuntamento |
| client_id        | int    | —       | Filtra per cliente |
| assigned_user_id | int    | —       | Filtra per operatore assegnato |
| practice_type_id | int    | —       | Filtra per tipo pratica |
| from             | date   | —       | Include appuntamenti da questa data |
| to               | date   | —       | Include appuntamenti fino a questa data |
| page             | int    | 1       | Pagina corrente |
| per_page         | int    | 20      | Elementi per pagina, massimo 100 |

**Comportamento:**
- Gli admin e superadmin vedono tutti gli appuntamenti compatibili con i filtri.
- Gli employee vedono solo gli appuntamenti con `assigned_user_id` uguale all'utente autenticato.
- La risposta include `client`, `practice_type` e `assigned_user` già normalizzati per l'app mobile.

**Esempio:**
```
GET /api/v1/appointments-manage?search=mario&status=confermato&assigned_user_id=4&from=2026-03-01&to=2026-03-31&page=1
```

**Risposta 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 15,
      "scheduled_at": "2026-03-28T10:00:00+00:00",
      "duration_minutes": 60,
      "status": "confermato",
      "client": {
        "id": 7,
        "first_name": "Mario",
        "last_name": "Rossi"
      },
      "practice_type": {
        "id": 2,
        "name": "ISEE",
        "color": "#2563EB"
      },
      "assigned_user": {
        "id": 4,
        "name": "Giulia Verdi"
      }
    }
  ],
  "per_page": 20,
  "total": 42,
  "last_page": 3,
  "next_page_url": "..."
}
```

---

### GET /appointments/{id}

Restituisce il dettaglio completo di un appuntamento.

**Auth:** `appointments.view-any` oppure (`appointments.view-own` E l'appuntamento è assegnato all'utente)

**URL parameters:**

| Parametro | Tipo | Descrizione     |
|-----------|------|-----------------|
| id        | int  | ID appuntamento |

**Comportamento:**
- Carica le relazioni: `client`, `assignedUser`, `practiceType`, `practice`, `creator`.

**Risposta 200:**
```json
{
  "data": {
    "id": 1,
    "client_profile_id": 3,
    "practice_type_id": 2,
    "practice_id": null,
    "assigned_user_id": 4,
    "scheduled_at": "2026-04-10T10:00:00.000000Z",
    "duration_minutes": 60,
    "status": "confermato",
    "notes": null,
    "created_by": 1,
    "created_at": "2026-03-23T09:00:00.000000Z",
    "updated_at": "2026-03-23T09:00:00.000000Z",
    "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" },
    "assigned_user": { "id": 4, "name": "Lucia Verdi" },
    "practice_type": { "id": 2, "name": "ISEE", "color": "#22C55E" },
    "practice": null,
    "creator": { "id": 1, "name": "Admin" }
  }
}
```

---

### PUT /appointments/{id}

Aggiorna un appuntamento (stato, utente assegnato, note, data, durata).

**Auth:** `appointments.update` E (superadmin, admin, oppure l'appuntamento è assegnato all'utente)

**URL parameters:**

| Parametro | Tipo | Descrizione     |
|-----------|------|-----------------|
| id        | int  | ID appuntamento |

**Content-Type:** `application/json`

**Campi (tutti opzionali):**

| Campo            | Tipo     | Validazione                                                          |
|------------------|----------|----------------------------------------------------------------------|
| status           | string   | Uno tra: `da_confermare`, `confermato`, `completato`, `cancellato`   |
| assigned_user_id | int      | Deve esistere in `users`                                             |
| notes            | string   | Max 1000 caratteri                                                   |
| scheduled_at     | datetime | Data valida                                                          |
| duration_minutes | int      | Minimo 5 minuti                                                      |

**Comportamento:**
- Se lo `status` viene impostato a `completato` e non esiste una pratica collegata, viene creata automaticamente una pratica di tipo corrispondente al `practice_type_id` dell'appuntamento.

**Risposta 200:**
```json
{
  "message": "Appuntamento aggiornato.",
  "data": {
    "id": 1,
    "status": "completato",
    "assigned_user_id": 4,
    "scheduled_at": "2026-04-10T10:00:00.000000Z",
    "duration_minutes": 60,
    "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" },
    "assigned_user": { "id": 4, "name": "Lucia Verdi" },
    "practice_type": { "id": 2, "name": "ISEE" },
    "practice": { "id": 12, "type": "ISEE", "status": "nuova" }
  }
}
```

---

### PATCH /appointments/{id}/reschedule

Riprogramma la data e la durata di un appuntamento senza modificare altri campi.

**Auth:** `appointments.update` E (superadmin, admin, oppure l'appuntamento è assegnato all'utente)

**URL parameters:**

| Parametro | Tipo | Descrizione     |
|-----------|------|-----------------|
| id        | int  | ID appuntamento |

**Content-Type:** `application/json`

**Campi:**

| Campo            | Tipo     | Obbligatorio | Validazione         |
|------------------|----------|--------------|---------------------|
| scheduled_at     | datetime | Sì           | Data valida         |
| duration_minutes | int      | Sì           | Minimo 5 minuti     |

**Esempio:**
```json
{
  "scheduled_at": "2026-04-15T14:00:00.000000Z",
  "duration_minutes": 45
}
```

**Risposta 200:**
```json
{
  "message": "Appuntamento riprogrammato.",
  "data": {
    "id": 1,
    "scheduled_at": "2026-04-15T14:00:00.000000Z",
    "duration_minutes": 45,
    "status": "confermato"
  }
}
```

---

### GET /appointments-calendar

Restituisce gli appuntamenti di un intervallo di date in formato eventi calendario (compatibile con FullCalendar).

**Auth:** `appointments.view-any` oppure `appointments.view-own`

**Query parameters:**

| Parametro | Tipo | Obbligatorio | Descrizione                         |
|-----------|------|--------------|-------------------------------------|
| from      | date | Sì           | Data inizio intervallo (YYYY-MM-DD) |
| to        | date | Sì           | Data fine intervallo (YYYY-MM-DD)   |

**Comportamento:**
- Gli utenti con `appointments.view-any` vedono tutti gli appuntamenti dell'intervallo.
- Gli utenti con solo `appointments.view-own` vedono solo quelli con `assigned_user_id` = loro ID.

**Esempio:**
```
GET /api/v1/appointments-calendar?from=2026-04-01&to=2026-04-30
```

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Mario Rossi - ISEE",
      "start": "2026-04-10T10:00:00",
      "end": "2026-04-10T11:00:00",
      "backgroundColor": "#22C55E",
      "borderColor": "#22C55E",
      "extendedProps": {
        "status": "confermato",
        "duration_minutes": 60,
        "notes": null,
        "client_id": 3,
        "practice_type_id": 2,
        "assigned_user_id": 4,
        "practice_id": null
      }
    }
  ]
}
```

---

### GET /appointments-practices

Restituisce le pratiche di un cliente filtrate per tipo pratica. Usato per collegare una pratica esistente a un nuovo appuntamento (modale di creazione).

**Auth:** `appointments.create`

**Query parameters:**

| Parametro       | Tipo | Obbligatorio | Validazione                              |
|-----------------|------|--------------|------------------------------------------|
| client_id       | int  | Sì           | Deve esistere in `client_profiles`       |
| practice_type_id| int  | Sì           | Deve esistere in `practice_types`        |

**Esempio:**
```
GET /api/v1/appointments-practices?client_id=3&practice_type_id=2
```

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 5,
      "type": "ISEE",
      "status": "in_lavorazione",
      "reference_year": 2025
    }
  ]
}
```

---

## Endpoint di supporto

---

### GET /users/available

Restituisce gli utenti attivi che hanno almeno una disponibilità configurata. Usato per popolare il selettore dell'operatore nel modale di prenotazione.

**Auth:** Qualsiasi utente autenticato

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 4,
      "name": "Lucia Verdi",
      "availabilities": [
        {
          "id": 1,
          "user_id": 4,
          "day_of_week": 1,
          "time_from": "09:00",
          "time_to": "17:00"
        }
      ]
    }
  ]
}
```

---

### GET /practice-types

Restituisce tutti i tipi pratica ordinati per nome. Usato per popolare i selettori nel modale di prenotazione.

**Auth:** Qualsiasi utente autenticato

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "730",
      "duration_minutes": 60,
      "color": "#3B82F6",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "ISEE",
      "duration_minutes": 45,
      "color": "#22C55E"
    }
  ]
}
```

---

## Struttura oggetti

### Appointment

| Campo             | Tipo              | Descrizione                                                                  |
|-------------------|-------------------|------------------------------------------------------------------------------|
| id                | int               | Identificatore univoco                                                       |
| client_profile_id | int               | ID del profilo cliente                                                       |
| practice_type_id  | int               | ID del tipo pratica                                                          |
| practice_id       | int \| null       | ID della pratica collegata (null se non ancora associata)                    |
| assigned_user_id  | int \| null       | ID dell'operatore assegnato                                                  |
| scheduled_at      | datetime (ISO8601)| Data e ora dell'appuntamento                                                 |
| duration_minutes  | int               | Durata in minuti                                                             |
| status            | string            | Stato: `da_confermare`, `confermato`, `completato`, `cancellato`             |
| notes             | string \| null    | Note                                                                         |
| created_by        | int               | ID dell'utente che ha creato l'appuntamento                                  |
| created_at        | datetime (ISO8601)| Data creazione                                                               |
| updated_at        | datetime (ISO8601)| Data ultima modifica                                                         |

---

## Autorizzazioni e comportamento per ruolo

| Operazione                     | superadmin | admin | employee                  | cliente              |
|-------------------------------|:----------:|:-----:|:-------------------------:|:--------------------:|
| Lista appuntamenti propri      | ✅          | ✅    | ✅                         | ✅ (solo propri)     |
| Prenota appuntamento           | ✅          | ✅    | ✅                         | ✅                   |
| Cancella appuntamento          | ✅          | ✅    | ✅                         | ✅ (solo propri)     |
| Dettaglio appuntamento         | ✅          | ✅    | ✅ (solo assegnati)        | ❌                   |
| Aggiorna appuntamento          | ✅          | ✅    | ✅ (solo assegnati)        | ❌                   |
| Riprogramma appuntamento       | ✅          | ✅    | ✅ (solo assegnati)        | ❌                   |
| Visualizza calendario          | ✅          | ✅    | ✅ (solo assegnati)        | ❌                   |
| Pratiche per modale            | ✅          | ✅    | ✅                         | ❌                   |

---

## Errori comuni

| Status | Struttura risposta                                                              |
|--------|---------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                             |
| 403    | `{ "message": "This action is unauthorized." }`                                 |
| 404    | `{ "message": "No query results for model [Appointment]." }`                    |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`          |
