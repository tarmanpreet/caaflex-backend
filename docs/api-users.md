# API — Users (Utenti)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Users](#users)
  - [POST /users — Crea utente](#post-users)
  - [GET /users — Lista utenti](#get-users)
  - [GET /users/{id} — Dettaglio utente](#get-usersid)
  - [PUT /users/{id} — Aggiorna utente](#put-usersid)
  - [POST /users/{id}/toggle-active — Attiva/disattiva utente](#post-usersidtoggle-active)
- [Disponibilità utente](#disponibilità-utente)
  - [GET /users/{id}/availabilities — Lista disponibilità](#get-usersidavailabilities)
  - [POST /users/{id}/availabilities — Salva disponibilità](#post-usersidavailabilities)
  - [DELETE /availabilities/{id} — Elimina disponibilità](#delete-availabilitiesid)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Users

---

### POST /users

Crea un nuovo utente per l'area amministrativa o operativa.

**Auth:** `users.create`

**Content-Type:** `application/json`

**Campi:**

| Campo              | Tipo    | Obbligatorio | Validazione |
|--------------------|---------|--------------|-------------|
| name               | string  | Sì           | Max 255 caratteri |
| email              | string  | Sì           | Email valida, unica |
| password           | string  | Sì           | Minimo 8 caratteri |
| password_confirmation | string | Sì         | Deve coincidere con `password` |
| role               | string  | Sì           | Ruolo esistente con guard `web` |
| is_active          | boolean | No           | Default `true` |
| practice_type_ids  | array   | No           | Array di ID validi in `practice_types` |
| practice_type_ids.*| int     | No           | Deve esistere in `practice_types` |

**Comportamento:**
- L'utente viene creato con password hashata.
- Viene assegnato un solo ruolo tramite `assignRole`.
- Se il ruolo è `employee`, i tipi pratica vengono sincronizzati sulla pivot `practice_type_user`.
- Se `is_active` non viene passato, il backend imposta `true`.

**Esempio:**
```json
{
  "name": "Mario Rossi",
  "email": "mario.rossi@email.it",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "employee",
  "is_active": true,
  "practice_type_ids": [1, 2]
}
```

**Risposta 201:**
```json
{
  "data": {
    "id": 12,
    "name": "Mario Rossi",
    "email": "mario.rossi@email.it",
    "role_name": "employee",
    "is_active": true,
    "practice_types": [
      { "id": 1, "name": "730" },
      { "id": 2, "name": "ISEE" }
    ]
  },
  "message": "User created successfully."
}
```

**Errori:**

| Status | Causa |
|--------|-------|
| 403    | Manca il permesso `users.create` |
| 422    | Validazione fallita |

---

### GET /users

Restituisce la lista paginata degli utenti con i loro ruoli e il conteggio delle pratiche assegnate.

**Auth:** `users.view-any`

**Query parameters:**

| Parametro | Tipo   | Default | Descrizione                                                     |
|-----------|--------|---------|-----------------------------------------------------------------|
| search    | string | —       | Filtra per nome o email (LIKE %...%)                            |
| page      | int    | 1       | Numero di pagina                                                |

**Comportamento:**
- I risultati includono sempre la relazione `roles`.
- Ogni utente include due contatori: `assigned_practices_count` (totale) e `open_practices_count` (pratiche non in stato `completata` o `annullata`).
- Ordinamento per nome ascendente.

**Esempio:**
```
GET /api/v1/users?search=anna&page=1
```

**Risposta 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 2,
      "name": "Anna Bianchi",
      "email": "anna@example.com",
      "is_active": true,
      "created_at": "2026-01-10T00:00:00.000000Z",
      "updated_at": "2026-03-20T00:00:00.000000Z",
      "assigned_practices_count": 12,
      "open_practices_count": 3,
      "roles": [
        { "id": 2, "name": "employee", "guard_name": "web" }
      ]
    }
  ],
  "per_page": 20,
  "total": 8,
  "last_page": 1,
  "next_page_url": null,
  "prev_page_url": null
}
```

---

### GET /users/{id}

Restituisce il dettaglio completo di un utente con pratiche attive, pratiche chiuse (paginate), ruoli e tipi pratica assegnati.

**Auth:** `users.view` oppure l'utente vede se stesso

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID utente   |

**Query parameters:**

| Parametro     | Tipo   | Default | Descrizione                                               |
|---------------|--------|---------|-----------------------------------------------------------|
| active_search | string | —       | Filtra le pratiche attive per tipo o stato                |
| active_page   | int    | 1       | Pagina delle pratiche attive                              |
| closed_search | string | —       | Filtra le pratiche chiuse per tipo o stato                |
| closed_page   | int    | 1       | Pagina delle pratiche chiuse                              |

**Comportamento:**
- Carica sempre `roles`.
- Se l'utente ha il ruolo `employee`, carica anche `practiceTypes` (i tipi pratica assegnati).
- Le pratiche attive sono quelle con stato diverso da `completata` e `annullata`, ordinate per `updated_at` decrescente, paginate a 10.
- Le pratiche chiuse sono quelle con stato `completata` o `annullata`, ordinate per `updated_at` decrescente, paginate a 10.
- Ogni pratica include la relazione `client`.

**Risposta 200:**
```json
{
  "data": {
    "user": {
      "id": 2,
      "name": "Anna Bianchi",
      "email": "anna@example.com",
      "is_active": true,
      "roles": [{ "id": 2, "name": "employee" }],
      "practice_types": [
        { "id": 1, "name": "730", "color": "#3B82F6" }
      ]
    },
    "activePractices": {
      "current_page": 1,
      "data": [
        {
          "id": 5,
          "type": "730",
          "status": "in_lavorazione",
          "reference_year": 2025,
          "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" }
        }
      ],
      "per_page": 10,
      "total": 3
    },
    "closedPractices": {
      "current_page": 1,
      "data": [],
      "per_page": 10,
      "total": 0
    },
    "availableRoles": ["superadmin", "admin", "employee", "cliente"],
    "allPracticeTypes": [
      { "id": 1, "name": "730", "color": "#3B82F6" },
      { "id": 2, "name": "ISEE", "color": "#22C55E" }
    ],
    "practiceFilters": {
      "active_search": null,
      "closed_search": null
    }
  }
}
```

---

### PUT /users/{id}

Aggiorna i dati di un utente (nome, email, ruolo, tipi pratica assegnati).

**Auth:** `users.update` E (superadmin, admin)

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID utente   |

**Content-Type:** `application/json`

**Campi:**

| Campo            | Tipo   | Obbligatorio | Validazione                                          |
|------------------|--------|--------------|------------------------------------------------------|
| name             | string | Sì           | Max 255 caratteri                                    |
| email            | string | Sì           | Email valida, unica (esclude l'utente corrente)      |
| role             | string | No           | Uno dei ruoli esistenti: `superadmin`, `admin`, `employee`, `cliente` |
| practice_type_ids| array  | No           | Array di ID di tipi pratica da assegnare all'utente  |
| practice_type_ids.*| int  | —            | Deve esistere in `practice_types`                    |

**Comportamento:**
- Se `role` è presente, viene sostituito il ruolo corrente dell'utente (sync sul ruolo singolo).
- Se `practice_type_ids` è presente, viene sincronizzata la tabella pivot `practice_type_user`.

**Esempio:**
```json
{
  "name": "Anna Bianchi",
  "email": "anna.bianchi@example.com",
  "role": "employee",
  "practice_type_ids": [1, 2]
}
```

**Risposta 200:**
```json
{
  "message": "Utente aggiornato.",
  "data": {
    "id": 2,
    "name": "Anna Bianchi",
    "email": "anna.bianchi@example.com",
    "is_active": true,
    "created_at": "2026-01-10T00:00:00.000000Z",
    "updated_at": "2026-03-23T10:00:00.000000Z"
  }
}
```

**Errori:**

| Status | Causa                                       |
|--------|---------------------------------------------|
| 403    | Manca il permesso o ruolo insufficiente     |
| 404    | Utente non trovato                          |
| 422    | Validazione fallita (es. email duplicata)   |

---

### POST /users/{id}/toggle-active

Attiva o disattiva un utente. Inverte il valore corrente di `is_active`.

**Auth:** `users.toggle-active`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID utente   |

**Corpo:** Nessuno

**Comportamento:**
- Se `is_active = true` → diventa `false` (utente disattivato).
- Se `is_active = false` → diventa `true` (utente attivato).
- Un utente disattivato non può accedere all'app (il middleware `EnsureUserIsActive` blocca le richieste).

**Risposta 200:**
```json
{
  "message": "Utente disattivato.",
  "data": {
    "id": 2,
    "name": "Anna Bianchi",
    "email": "anna.bianchi@example.com",
    "is_active": false
  }
}
```

> Il campo `message` è `"Utente attivato."` o `"Utente disattivato."` in base al nuovo stato.

**Errori:**

| Status | Causa                                             |
|--------|---------------------------------------------------|
| 403    | Manca il permesso `users.toggle-active`           |
| 404    | Utente non trovato                                |

---

## Disponibilità utente

Le disponibilità definiscono gli slot orari in cui un operatore (employee) è disponibile per appuntamenti.

---

### GET /users/{id}/availabilities

Restituisce le disponibilità di un utente, ordinate per giorno della settimana.

**Auth:** Permesso `user-availabilities.manage`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID utente   |

**Risposta 200:**
```json
{
  "data": {
    "targetUser": {
      "id": 4,
      "name": "Lucia Verdi",
      "email": "lucia@example.com"
    },
    "availabilities": [
      {
        "id": 1,
        "user_id": 4,
        "day_of_week": 1,
        "time_from": "09:00:00",
        "time_to": "17:00:00",
        "created_at": "2026-03-01T00:00:00.000000Z",
        "updated_at": "2026-03-01T00:00:00.000000Z"
      },
      {
        "id": 2,
        "user_id": 4,
        "day_of_week": 3,
        "time_from": "09:00:00",
        "time_to": "13:00:00"
      }
    ],
    "days": {
      "0": "Domenica",
      "1": "Lunedì",
      "2": "Martedì",
      "3": "Mercoledì",
      "4": "Giovedì",
      "5": "Venerdì",
      "6": "Sabato"
    }
  }
}
```

---

### POST /users/{id}/availabilities

Crea o aggiorna la disponibilità di un utente per un dato giorno della settimana. Se esiste già una disponibilità per quel giorno, viene sovrascritta (`updateOrCreate`).

**Auth:** Permesso `user-availabilities.manage`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID utente   |

**Content-Type:** `application/json`

**Campi:**

| Campo       | Tipo   | Obbligatorio | Validazione                                      |
|-------------|--------|--------------|--------------------------------------------------|
| day_of_week | int    | Sì           | Intero tra 0 (domenica) e 6 (sabato)             |
| time_from   | string | Sì           | Formato `HH:MM`                                  |
| time_to     | string | Sì           | Formato `HH:MM`, deve essere dopo `time_from`    |

**Esempio:**
```json
{
  "day_of_week": 1,
  "time_from": "09:00",
  "time_to": "17:00"
}
```

**Risposta 201:**
```json
{
  "message": "Disponibilità salvata.",
  "data": {
    "id": 1,
    "user_id": 4,
    "day_of_week": 1,
    "time_from": "09:00:00",
    "time_to": "17:00:00",
    "created_at": "2026-03-23T10:00:00.000000Z",
    "updated_at": "2026-03-23T10:00:00.000000Z"
  }
}
```

**Errori:**

| Status | Causa                                                    |
|--------|----------------------------------------------------------|
| 403    | Manca il permesso `user-availabilities.manage`           |
| 422    | Validazione fallita (es. `time_to` prima di `time_from`) |

---

### DELETE /availabilities/{id}

Elimina una disponibilità specifica.

**Auth:** Permesso `user-availabilities.manage`

**URL parameters:**

| Parametro | Tipo | Descrizione            |
|-----------|------|------------------------|
| id        | int  | ID della disponibilità |

**Risposta 200:**
```json
{
  "message": "Disponibilità eliminata."
}
```

**Errori:**

| Status | Causa                                              |
|--------|----------------------------------------------------|
| 403    | Manca il permesso `user-availabilities.manage`     |
| 404    | Disponibilità non trovata                          |

---

## Struttura oggetti

### User

| Campo      | Tipo              | Descrizione                                                      |
|------------|-------------------|------------------------------------------------------------------|
| id         | int               | Identificatore univoco                                           |
| name       | string            | Nome completo                                                    |
| email      | string            | Email di accesso                                                 |
| is_active  | bool              | Se `false`, l'utente non può accedere all'app                   |
| created_at | datetime (ISO8601)| Data creazione                                                   |
| updated_at | datetime (ISO8601)| Data ultima modifica                                             |

### UserAvailability

| Campo       | Tipo              | Descrizione                                               |
|-------------|-------------------|-----------------------------------------------------------|
| id          | int               | Identificatore univoco                                    |
| user_id     | int               | ID dell'utente                                            |
| day_of_week | int               | Giorno della settimana: 0 = domenica, 1 = lunedì, ..., 6 = sabato |
| time_from   | string (HH:MM:SS) | Ora di inizio disponibilità                               |
| time_to     | string (HH:MM:SS) | Ora di fine disponibilità                                 |
| created_at  | datetime (ISO8601)| Data creazione                                            |
| updated_at  | datetime (ISO8601)| Data ultima modifica                                      |

---

## Autorizzazioni e comportamento per ruolo

| Operazione                    | superadmin | admin | employee | cliente |
|-------------------------------|:----------:|:-----:|:--------:|:-------:|
| Lista utenti                  | ✅          | ✅    | ❌        | ❌      |
| Dettaglio utente              | ✅          | ✅    | ✅ (solo sé)| ❌   |
| Aggiorna utente               | ✅          | ✅    | ❌        | ❌      |
| Attiva/disattiva utente       | ✅          | ✅    | ❌        | ❌      |
| Gestisci disponibilità        | ✅          | ✅    | ❌        | ❌      |

---

## Errori comuni

| Status | Struttura risposta                                                             |
|--------|--------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                            |
| 403    | `{ "message": "This action is unauthorized." }`                                |
| 404    | `{ "message": "No query results for model [User]." }`                          |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`         |
