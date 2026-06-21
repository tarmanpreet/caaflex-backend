# API — Practice Types (Tipi Pratica)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Practice Types](#practice-types)
  - [GET /practice-types-manage — Lista tipi pratica](#get-practice-types-manage)
  - [POST /practice-types-manage — Crea tipo pratica](#post-practice-types-manage)
  - [PUT /practice-types-manage/{id} — Aggiorna tipo pratica](#put-practice-types-manageid)
  - [DELETE /practice-types-manage/{id} — Elimina tipo pratica](#delete-practice-types-manageid)
- [Endpoint pubblico (supporto appuntamenti)](#endpoint-pubblico)
  - [GET /practice-types — Lista per selettori](#get-practice-types-1)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Practice Types

I tipi pratica definiscono le categorie di servizio offerte (es. "730", "ISEE") con la durata predefinita degli appuntamenti e il colore usato nel calendario.

> **Nota sui path:** Gli endpoint di gestione usano il prefisso `/practice-types-manage` per evitare conflitti con `/practice-types` (endpoint di supporto usato dai selettori appuntamenti).

---

### GET /practice-types-manage

Restituisce la lista completa di tutti i tipi pratica, ordinati per nome.

**Auth:** `practice-types.view-any`

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
      "color": "#22C55E",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### POST /practice-types-manage

Crea un nuovo tipo pratica.

**Auth:** `practice-types.create`

**Content-Type:** `application/json`

**Campi:**

| Campo            | Tipo   | Obbligatorio | Validazione                                         |
|------------------|--------|--------------|-----------------------------------------------------|
| name             | string | Sì           | Max 255 caratteri, unico nella tabella              |
| duration_minutes | int    | Sì           | Numero intero positivo (durata in minuti)            |
| color            | string | Sì           | Colore esadecimale nel formato `#RRGGBB`             |

**Esempio:**
```json
{
  "name": "RED INPS",
  "duration_minutes": 30,
  "color": "#F59E0B"
}
```

**Risposta 201:**
```json
{
  "message": "Tipo pratica creato.",
  "data": {
    "id": 5,
    "name": "RED INPS",
    "duration_minutes": 30,
    "color": "#F59E0B",
    "created_at": "2026-03-23T10:00:00.000000Z",
    "updated_at": "2026-03-23T10:00:00.000000Z"
  }
}
```

**Errori:**

| Status | Causa                                                |
|--------|------------------------------------------------------|
| 403    | Manca il permesso `practice-types.create`            |
| 422    | Validazione fallita (es. nome già esistente)         |

---

### PUT /practice-types-manage/{id}

Aggiorna un tipo pratica esistente.

**Auth:** `practice-types.update`

**URL parameters:**

| Parametro | Tipo | Descrizione      |
|-----------|------|------------------|
| id        | int  | ID tipo pratica  |

**Content-Type:** `application/json`

**Campi:** Stessi di [POST /practice-types-manage](#post-practice-types-manage). Tutti obbligatori.

**Comportamento:**
- `name` deve essere unico escludendo il tipo pratica corrente dalla verifica duplicati.

**Risposta 200:**
```json
{
  "message": "Tipo pratica aggiornato.",
  "data": {
    "id": 5,
    "name": "RED INPS Aggiornato",
    "duration_minutes": 45,
    "color": "#EF4444",
    "created_at": "2026-03-23T10:00:00.000000Z",
    "updated_at": "2026-03-23T11:00:00.000000Z"
  }
}
```

**Errori:**

| Status | Causa                                            |
|--------|--------------------------------------------------|
| 403    | Manca il permesso `practice-types.update`        |
| 404    | Tipo pratica non trovato                         |
| 422    | Validazione fallita                              |

---

### DELETE /practice-types-manage/{id}

Elimina un tipo pratica.

**Auth:** `practice-types.delete`

**URL parameters:**

| Parametro | Tipo | Descrizione      |
|-----------|------|------------------|
| id        | int  | ID tipo pratica  |

**Comportamento:**
- L'eliminazione è irreversibile.
- Se esistono appuntamenti o pratiche collegati al tipo pratica, il database potrebbe restituire un errore di vincolo di integrità referenziale (dipende dalla configurazione delle foreign key).

**Risposta 200:**
```json
{
  "message": "Tipo pratica eliminato."
}
```

**Errori:**

| Status | Causa                                          |
|--------|------------------------------------------------|
| 403    | Manca il permesso `practice-types.delete`      |
| 404    | Tipo pratica non trovato                       |

---

## Endpoint pubblico

---

### GET /practice-types

Restituisce tutti i tipi pratica ordinati per nome. Endpoint leggero usato per popolare i selettori negli appuntamenti. Non richiede permessi specifici — basta essere autenticati.

**Auth:** Qualsiasi utente autenticato

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "730",
      "duration_minutes": 60,
      "color": "#3B82F6"
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

### PracticeType

| Campo            | Tipo              | Descrizione                                          |
|------------------|-------------------|------------------------------------------------------|
| id               | int               | Identificatore univoco                               |
| name             | string            | Nome del tipo pratica (es. "730", "ISEE")            |
| duration_minutes | int               | Durata predefinita degli appuntamenti in minuti      |
| color            | string            | Colore esadecimale usato nel calendario (`#RRGGBB`)  |
| created_at       | datetime (ISO8601)| Data creazione                                       |
| updated_at       | datetime (ISO8601)| Data ultima modifica                                 |

---

## Autorizzazioni e comportamento per ruolo

| Operazione              | superadmin | admin | employee | cliente |
|-------------------------|:----------:|:-----:|:--------:|:-------:|
| Lista tipi pratica      | ✅          | ✅    | ✅        | ❌      |
| Crea tipo pratica       | ✅          | ✅    | ❌        | ❌      |
| Aggiorna tipo pratica   | ✅          | ✅    | ❌        | ❌      |
| Elimina tipo pratica    | ✅          | ✅    | ❌        | ❌      |
| Selettore (GET /practice-types) | ✅  | ✅    | ✅        | ❌      |

---

## Errori comuni

| Status | Struttura risposta                                                              |
|--------|---------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                             |
| 403    | `{ "message": "This action is unauthorized." }`                                 |
| 404    | `{ "message": "No query results for model [PracticeType]." }`                   |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`          |
