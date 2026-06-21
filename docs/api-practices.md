# API — Practices (Pratiche)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Practices](#practices)
  - [GET /practices — Lista pratiche](#get-practices)
  - [POST /practices — Crea pratica](#post-practices)
  - [GET /practices/{id} — Dettaglio pratica](#get-practicesid)
  - [PUT /practices/{id} — Aggiorna pratica](#put-practicesid)
  - [DELETE /practices/{id} — Elimina pratica](#delete-practicesid)
  - [POST /practices/{id}/assign — Assegna utenti](#post-practicesidassign)
- [Note pratica](#note-pratica)
  - [GET /practices/{id}/notes — Lista note](#get-practicesidnotes)
  - [POST /practices/{id}/notes — Aggiungi nota](#post-practicesidnotes)
- [Documenti pratica](#documenti-pratica)
  - [POST /practices/{id}/documents — Carica documenti](#post-practicesiddocuments)
  - [GET /practices/{id}/documents/{documentId}/download — Scarica documento](#get-practicesiddocumentsdocumentiddownload)
  - [DELETE /practices/{id}/documents/{documentId} — Elimina documento](#delete-practicesiddocumentsdocumentid)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Practices

---

### GET /practices

Restituisce la lista paginata delle pratiche. Il risultato varia in base al ruolo: gli utenti con `practices.view-any` vedono tutte le pratiche, quelli con solo `practices.view-own` vedono solo le pratiche a loro assegnate.

**Auth:** `practices.view-any` oppure `practices.view-own`

**Query parameters:**

| Parametro       | Tipo   | Default | Descrizione                                                          |
|-----------------|--------|---------|----------------------------------------------------------------------|
| search          | string | —       | Filtra per tipo pratica o nome/cognome cliente (LIKE %...%)          |
| status          | string | —       | Filtra per stato (`nuova`, `in_lavorazione`, `in_attesa_documenti`, `completata`, `annullata`, `sospesa`) |
| type            | string | —       | Filtra per tipo (`730`, `ISEE`, `IMU_TASI`, `RED_INPS`, `SUCCESSIONE`, `BONUS_AGEVOLAZIONI`, `ALTRO`) |
| reference_year| int    | —       | Filtra per anno di riferimento                                       |
| page            | int    | 1       | Numero di pagina                                                     |

**Comportamento:**
- Le pratiche vengono restituite ordinate per data di aggiornamento decrescente.
- Ogni pratica include le relazioni `client` e `assignedUsers`.
- Gli utenti con `practices.view-own` (senza `practices.view-any`) vedono solo le pratiche in cui il loro ID è nella tabella pivot `practice_user`.

**Esempio:**
```
GET /api/v1/practices?status=in_lavorazione&type=730&page=1
```

**Risposta 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "client_profile_id": 3,
      "practice_type_id": null,
      "procedure_id": null,
      "type": "730",
      "status": "in_lavorazione",
      "reference_year": 2025,
      "notes": "Redditi da lavoro dipendente.",
      "created_by": 1,
      "created_at": "2026-03-18T10:00:00.000000Z",
      "updated_at": "2026-03-20T14:00:00.000000Z",
      "client": {
        "id": 3,
        "first_name": "Mario",
        "last_name": "Rossi"
      },
      "assigned_users": [
        {
          "id": 2,
          "name": "Anna Bianchi",
          "email": "anna@example.com",
          "pivot": { "assigned_at": "2026-03-18T10:05:00.000000Z" }
        }
      ]
    }
  ],
  "per_page": 20,
  "total": 42,
  "last_page": 3,
  "next_page_url": "https://<host>/api/v1/practices?page=2",
  "prev_page_url": null
}
```

---

### POST /practices

Crea una nuova pratica. Opzionalmente assegna utenti alla pratica al momento della creazione.

**Auth:** `practices.create`

**Content-Type:** `application/json`

**Campi:**

| Campo              | Tipo    | Obbligatorio | Validazione                                                                      |
|--------------------|---------|--------------|----------------------------------------------------------------------------------|
| client_profile_id  | int     | Sì           | Deve esistere in `client_profiles`                                               |
| type               | string  | Sì           | Uno tra: `730`, `ISEE`, `IMU_TASI`, `RED_INPS`, `SUCCESSIONE`, `BONUS_AGEVOLAZIONI`, `ALTRO` |
| status             | string  | No           | Uno tra: `nuova`, `in_lavorazione`, `in_attesa_documenti`, `completata`, `annullata`, `sospesa` (default: `nuova`) |
| reference_year   | int     | No           | Anno intero tra 2000 e 2100                                                      |
| notes              | string  | No           | Testo libero                                                                     |
| procedure_id       | int     | No           | Deve esistere in `procedures`                                                    |
| practice_type_id   | int     | No           | Deve esistere in `practice_types`                                                |
| user_ids           | array   | No           | Array di ID utente da assegnare alla pratica                                     |
| user_ids.*         | int     | —            | Deve esistere in `users`                                                         |

**Comportamento:**
- `created_by` viene impostato automaticamente all'utente autenticato.
- Se `user_ids` è presente, gli utenti vengono assegnati alla pratica (tabella pivot `practice_user` con `assigned_at` = now).
- Viene registrato un log di stato iniziale in `practice_status_logs`.

**Esempio:**
```json
{
  "client_profile_id": 3,
  "type": "730",
  "reference_year": 2025,
  "notes": "Redditi da lavoro dipendente.",
  "user_ids": [2, 4]
}
```

**Risposta 201:**
```json
{
  "message": "Practice created.",
  "data": {
    "id": 10,
    "client_profile_id": 3,
    "practice_type_id": null,
    "procedure_id": null,
    "type": "730",
    "status": "nuova",
    "reference_year": 2025,
    "notes": "Redditi da lavoro dipendente.",
    "created_by": 1,
    "created_at": "2026-03-23T09:00:00.000000Z",
    "updated_at": "2026-03-23T09:00:00.000000Z",
    "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" },
    "assigned_users": [
      { "id": 2, "name": "Anna Bianchi", "pivot": { "assigned_at": "2026-03-23T09:00:00.000000Z" } }
    ]
  }
}
```

**Errori:**

| Status | Causa                                         |
|--------|-----------------------------------------------|
| 403    | Manca il permesso `practices.create`          |
| 422    | Validazione fallita (es. tipo non valido)     |

---

### GET /practices/{id}

Restituisce il dettaglio completo di una pratica con tutte le relazioni caricate.

**Auth:** `practices.view-any` oppure (`practices.view-own` E l'utente è assegnato alla pratica)

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Comportamento:**
- Carica: `client`, `assignedUsers`, `notes.author`, `documents.uploader`, `statusLogs.user`.
- Un utente con solo `practices.view-own` riceve 403 se non è assegnato alla pratica.

**Risposta 200:**
```json
{
  "data": {
    "id": 1,
    "client_profile_id": 3,
    "practice_type_id": null,
    "procedure_id": null,
    "type": "730",
    "status": "in_lavorazione",
    "reference_year": 2025,
    "notes": "Redditi da lavoro dipendente.",
    "created_by": 1,
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-20T14:00:00.000000Z",
    "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" },
    "assigned_users": [
      { "id": 2, "name": "Anna Bianchi", "pivot": { "assigned_at": "2026-03-18T10:05:00.000000Z" } }
    ],
    "notes_list": [
      {
        "id": 1,
        "practice_id": 1,
        "user_id": 1,
        "body": "Prima presa in carico.",
        "created_at": "2026-03-18T10:10:00.000000Z",
        "author": { "id": 1, "name": "Admin" }
      }
    ],
    "documents": [
      {
        "id": 1,
        "original_name": "dichiarazione.pdf",
        "mime_type": "application/pdf",
        "file_size": 102400,
        "description": "CU 2025",
        "created_at": "2026-03-18T11:00:00.000000Z",
        "uploader": { "id": 1, "name": "Admin" }
      }
    ],
    "status_logs": [
      {
        "id": 1,
        "practice_id": 1,
        "user_id": 1,
        "old_status": null,
        "new_status": "nuova",
        "created_at": "2026-03-18T10:00:00.000000Z",
        "user": { "id": 1, "name": "Admin" }
      }
    ]
  }
}
```

**Errori:**

| Status | Causa                                                |
|--------|------------------------------------------------------|
| 403    | Utente non assegnato alla pratica o permesso mancante |
| 404    | Pratica non trovata                                  |

---

### PUT /practices/{id}

Aggiorna i dati di una pratica esistente. Se lo stato cambia, viene registrato un log in `practice_status_logs`.

**Auth:** `practices.update` E (superadmin, admin, oppure l'utente è assegnato alla pratica)

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Content-Type:** `application/json`

**Campi (tutti opzionali):**

| Campo            | Tipo   | Validazione                                                                      |
|------------------|--------|----------------------------------------------------------------------------------|
| type             | string | Uno tra: `730`, `ISEE`, `IMU_TASI`, `RED_INPS`, `SUCCESSIONE`, `BONUS_AGEVOLAZIONI`, `ALTRO` |
| status           | string | Uno tra: `nuova`, `in_lavorazione`, `in_attesa_documenti`, `completata`, `annullata`, `sospesa` |
| reference_year | int    | Anno tra 2000 e 2100                                                             |
| notes            | string | Testo libero                                                                     |
| procedure_id     | int    | Deve esistere in `procedures`                                                    |
| practice_type_id | int    | Deve esistere in `practice_types`                                                |
| user_ids         | array  | Array di ID utente — sostituisce gli utenti assegnati (sync)                     |

**Comportamento:**
- Se `status` cambia rispetto a quello attuale, viene creato automaticamente un record in `practice_status_logs`.
- Se `user_ids` è presente, viene eseguita una `sync` sulla tabella pivot (rimuove chi non è nell'array, aggiunge chi non c'era).

**Risposta 200:**
```json
{
  "message": "Practice updated.",
  "data": {
    "id": 1,
    "practice_type_id": null,
    "procedure_id": null,
    "type": "730",
    "status": "completata",
    "reference_year": 2025,
    "client": { "id": 3, "first_name": "Mario", "last_name": "Rossi" },
    "assigned_users": []
  }
}
```

**Errori:**

| Status | Causa                                                     |
|--------|-----------------------------------------------------------|
| 403    | Manca il permesso o l'utente non è assegnato alla pratica |
| 404    | Pratica non trovata                                       |
| 422    | Validazione fallita                                       |

---

### DELETE /practices/{id}

Elimina una pratica. L'operazione è irreversibile.

**Auth:** `practices.delete`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Risposta 200:**
```json
{
  "message": "Practice deleted."
}
```

**Errori:**

| Status | Causa                               |
|--------|-------------------------------------|
| 403    | Manca il permesso `practices.delete`|
| 404    | Pratica non trovata                 |

---

### POST /practices/{id}/assign

Sostituisce gli utenti assegnati alla pratica. Equivale a una `sync`: gli utenti non inclusi nell'array vengono rimossi.

**Auth:** `practices.assign`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Content-Type:** `application/json`

**Campi:**

| Campo      | Tipo  | Obbligatorio | Validazione                    |
|------------|-------|--------------|--------------------------------|
| user_ids   | array | Sì           | Array non vuoto                |
| user_ids.* | int   | —            | Deve esistere in `users`       |

**Esempio:**
```json
{
  "user_ids": [2, 5]
}
```

**Risposta 200:**
```json
{
  "message": "Users assigned."
}
```

**Errori:**

| Status | Causa                                         |
|--------|-----------------------------------------------|
| 403    | Manca il permesso `practices.assign`          |
| 422    | `user_ids` mancante o ID utente non esistente |

---

## Note pratica

---

### GET /practices/{id}/notes

Restituisce tutte le note associate a una pratica, ordinate per data di creazione ascendente.

**Auth:** Accesso alla pratica (stesse regole di `GET /practices/{id}`)

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 1,
      "practice_id": 1,
      "user_id": 1,
      "body": "Prima presa in carico.",
      "created_at": "2026-03-18T10:10:00.000000Z",
      "updated_at": "2026-03-18T10:10:00.000000Z",
      "author": { "id": 1, "name": "Admin" }
    }
  ]
}
```

---

### POST /practices/{id}/notes

Aggiunge una nota alla pratica.

**Auth:** `practices.add-note` (oppure permesso `createNote` sulla pratica)

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Content-Type:** `application/json`

**Campi:**

| Campo | Tipo   | Obbligatorio | Validazione  |
|-------|--------|--------------|--------------|
| body  | string | Sì           | Testo libero |

**Esempio:**
```json
{
  "body": "Cliente ha consegnato il CU. In attesa di firma."
}
```

**Risposta 201:**
```json
{
  "message": "Note added.",
  "data": {
    "id": 2,
    "practice_id": 1,
    "user_id": 1,
    "body": "Cliente ha consegnato il CU. In attesa di firma.",
    "created_at": "2026-03-23T10:00:00.000000Z",
    "updated_at": "2026-03-23T10:00:00.000000Z",
    "author": { "id": 1, "name": "Admin" }
  }
}
```

**Errori:**

| Status | Causa                                       |
|--------|---------------------------------------------|
| 403    | Utente non autorizzato ad aggiungere note   |
| 422    | `body` mancante                             |

---

## Documenti pratica

---

### POST /practices/{id}/documents

Carica uno o più file associati a una pratica. Usa `multipart/form-data`.

**Auth:** `practices.upload-document`

**URL parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| id        | int  | ID pratica  |

**Form data:**

| Campo          | Tipo           | Obbligatorio | Validazione                                                               |
|----------------|----------------|--------------|---------------------------------------------------------------------------|
| files[]        | file (array)   | Sì           | Almeno 1 file. Tipi ammessi: `pdf, jpg, jpeg, png, doc, docx`. Max 10 MB. |
| descriptions[] | string (array) | No           | Descrizione opzionale per file. Max 255 caratteri. Indici corrispondenti. |

**Comportamento:**
- I file vengono salvati in `storage/app/practice-documents/{practice_id}/`.
- `uploaded_by` impostato automaticamente all'utente autenticato.

**Risposta 201:**
```json
{
  "message": "Documents uploaded.",
  "data": [
    {
      "id": 5,
      "practice_id": 1,
      "uploaded_by": 1,
      "original_name": "cu_2025.pdf",
      "mime_type": "application/pdf",
      "file_size": 102400,
      "description": "CU 2025",
      "created_at": "2026-03-23T10:00:00.000000Z",
      "updated_at": "2026-03-23T10:00:00.000000Z"
    }
  ]
}
```

**Errori:**

| Status | Causa                                                  |
|--------|--------------------------------------------------------|
| 403    | Manca il permesso di upload documenti sulla pratica    |
| 422    | File mancante, tipo non ammesso o dimensione superata  |

---

### GET /practices/{id}/documents/{documentId}/download

Scarica il file fisico di un documento pratica.

**Auth:** `practices.download-document`

**URL parameters:**

| Parametro  | Tipo | Descrizione  |
|------------|------|--------------|
| id         | int  | ID pratica   |
| documentId | int  | ID documento |

**Risposta 200:** Stream binario del file.
```
Content-Disposition: attachment; filename="cu_2025.pdf"
Content-Type: application/pdf
```

**Errori:**

| Status | Causa                                        |
|--------|----------------------------------------------|
| 403    | Manca il permesso di download                |
| 404    | Pratica o documento non trovati              |

---

### DELETE /practices/{id}/documents/{documentId}

Elimina un documento pratica: rimuove il record dal DB e il file fisico dal disco.

**Auth:** `practices.delete-document`

**URL parameters:**

| Parametro  | Tipo | Descrizione  |
|------------|------|--------------|
| id         | int  | ID pratica   |
| documentId | int  | ID documento |

**Risposta 200:**
```json
{
  "message": "Document deleted."
}
```

---

## Struttura oggetti

### Practice

| Campo             | Tipo             | Descrizione                                                                          |
|-------------------|------------------|--------------------------------------------------------------------------------------|
| id                | int              | Identificatore univoco                                                               |
| client_profile_id | int              | ID del profilo cliente proprietario                                                  |
| practice_type_id  | int \| null      | ID del tipo pratica (da `practice_types`)                                            |
| procedure_id      | int \| null      | ID della procedura associata (da `procedures`)                                       |
| type              | string           | Tipo pratica: `730`, `ISEE`, `IMU_TASI`, `RED_INPS`, `SUCCESSIONE`, `BONUS_AGEVOLAZIONI`, `ALTRO` |
| status            | string           | Stato: `nuova`, `in_lavorazione`, `in_attesa_documenti`, `completata`, `annullata`, `sospesa` |
| reference_year  | int \| null      | Anno di riferimento fiscale                                                          |
| notes             | string \| null   | Note interne                                                                         |
| created_by        | int              | ID dell'utente che ha creato la pratica                                              |
| created_at        | datetime (ISO8601)| Data creazione                                                                      |
| updated_at        | datetime (ISO8601)| Data ultima modifica                                                                |

### PracticeNote

| Campo       | Tipo             | Descrizione                            |
|-------------|------------------|----------------------------------------|
| id          | int              | Identificatore univoco                 |
| practice_id | int              | ID della pratica                       |
| user_id     | int              | ID dell'autore                         |
| body        | string           | Testo della nota                       |
| created_at  | datetime (ISO8601)| Data creazione                        |
| updated_at  | datetime (ISO8601)| Data ultima modifica                  |

### PracticeDocument

| Campo       | Tipo             | Descrizione                                    |
|-------------|------------------|------------------------------------------------|
| id          | int              | Identificatore univoco                         |
| practice_id | int              | ID della pratica                               |
| uploaded_by | int              | ID dell'utente che ha caricato il file         |
| original_name | string         | Nome originale del file                        |
| mime_type   | string           | MIME type                                      |
| file_size   | int              | Dimensione in byte                             |
| description | string \| null   | Descrizione opzionale                          |
| created_at  | datetime (ISO8601)| Data upload                                   |

---

## Autorizzazioni e comportamento per ruolo

| Operazione              | superadmin | admin | employee               | cliente |
|-------------------------|:----------:|:-----:|:----------------------:|:-------:|
| Lista pratiche          | ✅          | ✅    | ✅ (solo assegnate)     | ❌      |
| Crea pratica            | ✅          | ✅    | ✅                      | ❌      |
| Vedi dettaglio pratica  | ✅          | ✅    | ✅ (solo assegnate)     | ❌      |
| Aggiorna pratica        | ✅          | ✅    | ✅ (solo assegnate)     | ❌      |
| Elimina pratica         | ✅          | ✅    | ❌                      | ❌      |
| Assegna utenti          | ✅          | ✅    | ❌                      | ❌      |
| Aggiungi nota           | ✅          | ✅    | ✅ (solo assegnate)     | ❌      |
| Carica documenti        | ✅          | ✅    | ✅                      | ❌      |
| Scarica documenti       | ✅          | ✅    | ✅                      | ❌      |
| Elimina documenti       | ✅          | ✅    | ❌                      | ❌      |

---

## Note sul comportamento procedure_id e practice_type_id

Quando si crea o si aggiorna una pratica, il sistema gestisce la relazione tra `procedure_id` e `practice_type_id` in modo automatico:

- Quando si fornisce `procedure_id` senza `practice_type_id`, il sistema auto-deriva `practice_type_id` dalla procedura
- Se entrambi sono forniti, devono corrispondere (`procedure.procedure_type_id == practice_type_id`)

Questo comportamento si applica sia alle chiamate `POST /practices` che `PUT /practices/{id}`.

---

## Errori comuni

| Status | Struttura risposta                                                             |
|--------|--------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                            |
| 403    | `{ "message": "This action is unauthorized." }`                                |
| 404    | `{ "message": "No query results for model [Practice]." }`                      |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`         |
