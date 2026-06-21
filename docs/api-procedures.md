# API — Procedures (Procedure)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Procedures](#procedures)
  - [GET /procedures — Lista procedure](#get-procedures)
  - [POST /procedures — Crea procedura](#post-procedures)
  - [GET /procedures/{id} — Dettaglio procedura](#get-proceduresid)
  - [PUT /procedures/{id} — Aggiorna procedura](#put-proceduresid)
  - [DELETE /procedures/{id} — Elimina procedura](#delete-proceduresid)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Procedures

---

### GET /procedures

Restituisce la lista di tutte le procedure. Include la relazione `practiceType` per ogni procedura.

**Auth:** `procedures.view-any`

**Query parameters:**

| Parametro         | Tipo   | Default | Descrizione                                                      |
|-------------------|--------|---------|------------------------------------------------------------------|
| procedure_type_id | int    | —       | Filtra per ID tipo pratica                                       |
| search            | string | —       | Filtra per name procedura (LIKE %...%)                           |

**Comportamento:**
- Le procedure vengono restituite con la relazione `practiceType` caricata.
- Tutti gli utenti con permesso `procedures.view-any` vedono l'intera lista.

**Esempio:**
```
GET /api/v1/procedures?procedure_type_id=1&search=730
```

**Risposta 200:**
```json
[
  {
    "id": 1,
    "procedure_type_id": 1,
    "name": "730 - Dichiarazione redditi",
    "default_notes": "Documenti richiesti: CUD, spese sanitarie, mutuo",
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-18T10:00:00.000000Z",
    "practice_type": {
      "id": 1,
      "name": "730",
      "description": "Dichiarazione dei redditi"
    }
  },
  {
    "id": 2,
    "procedure_type_id": 2,
    "name": "ISEE - Corrente",
    "default_notes": "Certificazione attestante la situazione economica",
    "created_at": "2026-03-18T11:00:00.000000Z",
    "updated_at": "2026-03-18T11:00:00.000000Z",
    "practice_type": {
      "id": 2,
      "name": "ISEE",
      "description": "Indicatore della situazione economica equivalente"
    }
  }
]
```

---

### POST /procedures

Crea una nuova procedura. La combinazione di `name` e `procedure_type_id` deve essere univoca.

**Auth:** `procedures.create`

**Content-Type:** `application/json`

**Campi:**

| Campo             | Tipo   | Obbligatorio | Validazione                                           |
|-------------------|--------|--------------|-------------------------------------------------------|
| name              | string | Sì           | Max 255 caratteri. Unico per `procedure_type_id`     |
| procedure_type_id | int    | Sì           | Deve esistere in `practice_types`                     |
| default_notes     | string | No           | Testo libero                                          |

**Vincolo univoco:**
La combinazione di `name` + `procedure_type_id` deve essere univoca. Non e possibile creare due procedure con lo stesso name per lo stesso tipo pratica.

**Esempio:**
```json
{
  "name": "730 - Dichiarazione redditi",
  "procedure_type_id": 1,
  "default_notes": "Documenti richiesti: CUD, spese sanitarie, mutuo"
}
```

**Risposta 201:**
```json
{
  "message": "Procedure created.",
  "data": {
    "id": 10,
    "procedure_type_id": 1,
    "name": "730 - Dichiarazione redditi",
    "default_notes": "Documenti richiesti: CUD, spese sanitarie, mutuo",
    "created_at": "2026-03-23T09:00:00.000000Z",
    "updated_at": "2026-03-23T09:00:00.000000Z",
    "practice_type": {
      "id": 1,
      "name": "730",
      "description": "Dichiarazione dei redditi"
    }
  }
}
```

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | Manca il permesso `procedures.create`                          |
| 422    | Validazione fallita (es. name duplicato per procedure_type_id) |

---

### GET /procedures/{id}

Restituisce il dettaglio completo di una procedura con la relazione `practiceType` e il conteggio delle pratiche associate.

**Auth:** `procedures.view-any`

**URL parameters:**

| Parametro | Tipo | Descrizione  |
|-----------|------|--------------|
| id        | int  | ID procedura |

**Comportamento:**
- Carica: `practiceType` (relazione completa).
- Include: `practices_count` (numero di pratiche che usano questa procedura).

**Risposta 200:**
```json
{
  "data": {
    "id": 1,
    "procedure_type_id": 1,
    "name": "730 - Dichiarazione redditi",
    "default_notes": "Documenti richiesti: CUD, spese sanitarie, mutuo",
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-18T10:00:00.000000Z",
    "practice_type": {
      "id": 1,
      "name": "730",
      "description": "Dichiarazione dei redditi"
    },
    "practices_count": 15
  }
}
```

**Errori:**

| Status | Causa                        |
|--------|------------------------------|
| 403    | Manca il permesso di lettura |
| 404    | Procedura non trovata        |

---

### PUT /procedures/{id}

Aggiorna i dati di una procedura esistente. La combinazione di `name` e `procedure_type_id` deve rimanere univoca.

**Auth:** `procedures.update`

**URL parameters:**

| Parametro | Tipo | Descrizione  |
|-----------|------|--------------|
| id        | int  | ID procedura |

**Content-Type:** `application/json`

**Campi (tutti opzionali in una singola richiesta, ma vedi validazione):**

| Campo             | Tipo   | Validazione                                            |
|-------------------|--------|--------------------------------------------------------|
| name              | string | Max 255 caratteri. Unico per `procedure_type_id`      |
| procedure_type_id | int    | Deve esistere in `practice_types`                      |
| default_notes     | string | Testo libero                                           |

**Comportamento:**
- Se si cambia `name` o `procedure_type_id`, viene verificato che la nuova combinazione non esista gia.
- Il campo `name` e richiesto nella validazione (non puo essere omesso se presente altro).

**Esempio:**
```json
{
  "name": "730 - Dichiarazione redditi aggiornata",
  "default_notes": "Nuove note di default"
}
```

**Risposta 200:**
```json
{
  "message": "Procedure updated.",
  "data": {
    "id": 1,
    "procedure_type_id": 1,
    "name": "730 - Dichiarazione redditi aggiornata",
    "default_notes": "Nuove note di default",
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-23T14:30:00.000000Z",
    "practice_type": {
      "id": 1,
      "name": "730",
      "description": "Dichiarazione dei redditi"
    }
  }
}
```

**Errori:**

| Status | Causa                                            |
|--------|--------------------------------------------------|
| 403    | Manca il permesso `procedures.update`            |
| 404    | Procedura non trovata                            |
| 422    | Validazione fallita (es. name duplicato)         |

---

### DELETE /procedures/{id}

Elimina una procedura. L'operazione fallisce se esistono pratiche che utilizzano questa procedura.

**Auth:** `procedures.delete`

**URL parameters:**

| Parametro | Tipo | Descrizione  |
|-----------|------|--------------|
| id        | int  | ID procedura |

**Comportamento:**
- Se esistono pratiche collegate a questa procedura, viene restituito errore 409.
- Altrimenti, la procedura viene eliminata definitivamente.

**Risposta 200 (successo):**
```json
{
  "message": "Procedure deleted."
}
```

**Risposta 409 (conflitto):**
```json
{
  "message": "Cannot delete procedure with attached practices."
}
```

**Errori:**

| Status | Causa                                            |
|--------|--------------------------------------------------|
| 403    | Manca il permesso `procedures.delete`            |
| 404    | Procedura non trovata                            |
| 409    | Esistono pratiche collegate alla procedura       |

---

## Struttura oggetti

### Procedure

| Campo             | Tipo              | Descrizione                                            |
|-------------------|-------------------|--------------------------------------------------------|
| id                | int               | Identificatore univoco                                 |
| procedure_type_id | int               | ID del tipo pratica (da `practice_types`)              |
| name              | string            | Nome della procedura                                   |
| default_notes     | string \| null    | Note predefinite per la procedura                      |
| created_at        | datetime (ISO8601)| Data creazione                                         |
| updated_at        | datetime (ISO8601)| Data ultima modifica                                   |

### PracticeType (relazione inline)

| Campo       | Tipo   | Descrizione                              |
|-------------|--------|------------------------------------------|
| id          | int    | Identificatore univoco                   |
| name        | string | Nome del tipo pratica                    |
| description | string | Descrizione del tipo pratica             |

**Campi calcolati in show:**
- `practices_count`: numero di pratiche che utilizzano questa procedura

**Vincolo univoco:**
La combinazione `name` + `procedure_type_id` è univoca nella tabella `procedures`.

---

## Autorizzazioni e comportamento per ruolo

| Operazione              | superadmin | admin | employee | cliente |
|-------------------------|:----------:|:-----:|:--------:|:-------:|
| Lista procedure         | ✅          | ✅    | ✅       | ❌      |
| Crea procedura          | ✅          | ✅    | ❌       | ❌      |
| Vedi dettaglio procedura| ✅          | ✅    | ✅       | ❌      |
| Aggiorna procedura      | ✅          | ✅    | ❌       | ❌      |
| Elimina procedura       | ✅          | ✅    | ❌       | ❌      |

**Note:**
- Gli utenti `employee` possono visualizzare le procedure ma non modificarle o eliminarle.
- I permessi sono basati sulle ability del modello `Procedure` (`viewAny`, `create`, `update`, `delete`).

---

## Errori comuni

| Status | Struttura risposta                                                             |
|--------|--------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                            |
| 403    | `{ "message": "This action is unauthorized." }`                                |
| 404    | `{ "message": "No query results for model [Procedure]." }`                     |
| 409    | `{ "message": "Cannot delete procedure with attached practices." }`            |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`         |

### Esempi di errori 422

**Nome duplicato per lo stesso tipo pratica:**
```json
{
  "message": "The name has already been taken.",
  "errors": {
    "name": ["The name has already been taken."]
  }
}
```

**Tipo pratica non esistente:**
```json
{
  "message": "The selected procedure type id is invalid.",
  "errors": {
    "procedure_type_id": ["The selected procedure type id is invalid."]
  }
}
```
