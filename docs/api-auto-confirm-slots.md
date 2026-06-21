# API — Auto Confirm Slots (Slot Auto-Conferma)

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Auto Confirm Slots](#auto-confirm-slots)
  - [GET /auto-confirm-slots — Lista slot auto-conferma](#get-auto-confirm-slots)
  - [POST /auto-confirm-slots — Crea slot auto-conferma](#post-auto-confirm-slots)
  - [DELETE /auto-confirm-slots/{id} — Elimina slot auto-conferma](#delete-auto-confirm-slotsid)
- [Comportamento business](#comportamento-business)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Auto Confirm Slots

Gli slot auto-conferma definiscono fasce orarie ricorrenti in cui gli appuntamenti vengono confermati automaticamente alla creazione, senza bisogno di intervento manuale. La configurazione e' indipendente dagli utenti: vale per tutti gli appuntamenti creati nella fascia oraria corrispondente.

Solo gli utenti con il permesso `auto-confirm-slots.manage` (admin, superadmin) possono accedere a questi endpoint.

---

### GET /auto-confirm-slots

Restituisce la lista completa di tutti gli slot auto-conferma, ordinati per giorno della settimana.

**Auth:** `auto-confirm-slots.manage`

**Risposta 200:**
```json
{
  "data": [
    {
      "id": 1,
      "day_of_week": 1,
      "time_from": "09:00:00",
      "time_to": "17:00:00",
      "created_at": "2026-05-20T10:00:00.000000Z",
      "updated_at": "2026-05-20T10:00:00.000000Z"
    },
    {
      "id": 2,
      "day_of_week": 3,
      "time_from": "09:00:00",
      "time_to": "12:00:00",
      "created_at": "2026-05-20T10:05:00.000000Z",
      "updated_at": "2026-05-20T10:05:00.000000Z"
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
```

**Comportamento:**
- La risposta include il mapping `days` per tradurre i valori numerici dei giorni nei nomi in italiano.
- Se non ci sono slot configurati, `data` ritorna un array vuoto.

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | Manca il permesso `auto-confirm-slots.manage`                  |

---

### POST /auto-confirm-slots

Crea un nuovo slot auto-conferma.

**Auth:** `auto-confirm-slots.manage`

**Content-Type:** `application/json`

**Campi:**

| Campo       | Tipo   | Obbligatorio | Validazione                                       |
|-------------|--------|--------------|---------------------------------------------------|
| day_of_week | int    | Sì           | 0=Domenica, 1=Lunedì, ..., 6=Sabato               |
| time_from   | string | Sì           | Formato `H:i` (es. `"09:00"`), orario di inizio   |
| time_to     | string | Sì           | Formato `H:i`, deve essere dopo `time_from`       |

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
  "message": "Slot auto-conferma creato.",
  "data": {
    "id": 3,
    "day_of_week": 1,
    "time_from": "09:00:00",
    "time_to": "17:00:00",
    "created_at": "2026-05-20T10:10:00.000000Z",
    "updated_at": "2026-05-20T10:10:00.000000Z"
  }
}
```

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | Manca il permesso `auto-confirm-slots.manage`                  |
| 422    | Validazione fallita (es. `time_to` non dopo `time_from`)       |

---

### DELETE /auto-confirm-slots/{id}

Elimina uno slot auto-conferma.

**Auth:** `auto-confirm-slots.manage`

**URL parameters:**

| Parametro | Tipo | Descrizione      |
|-----------|------|------------------|
| id        | int  | ID dello slot    |

**Comportamento:**
- L'eliminazione e' immediata e irreversibile.
- Gli appuntamenti gia' creati non sono influenzati dall'eliminazione dello slot: mantengono il loro status attuale.

**Risposta 200:**
```json
{
  "message": "Slot auto-conferma eliminato."
}
```

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | Manca il permesso `auto-confirm-slots.manage`                  |
| 404    | Slot non trovato                                               |

---

## Comportamento business

Quando un appuntamento viene creato tramite `POST /api/v1/appointments`, il sistema verifica automaticamente se la `scheduled_at` ricade in uno slot auto-conferma:

1. Viene cercato uno slot con lo stesso `day_of_week` della data di prenotazione.
2. Se trovato, si verifica se l'orario (`H:i:s`) ricade tra `time_from` (incluso) e `time_to` (escluso).
3. Se entrambe le condizioni sono soddisfatte, l'appuntamento viene creato con status `confermato` invece di `da_confermare`.

**Effetti collaterali della conferma automatica:**
- Viene creata automaticamente una pratica (se `practice_type_id` e' impostato e `practice_id` e' null).
- Vengono inviate le email di conferma al cliente e all'eventuale utente assegnato.

**Nota:** La verifica avviene solo al momento della creazione. Se l'utente assegnato viene impostato successivamente tramite `PUT /appointments/{id}`, non si innesca la conferma automatica.

---

## Struttura oggetti

### AutoConfirmSlot

| Campo        | Tipo              | Descrizione                                                        |
|--------------|-------------------|--------------------------------------------------------------------|
| id           | int               | Identificatore univoco                                             |
| day_of_week  | int               | Giorno della settimana: 0=Domenica, 1=Lunedì, ..., 6=Sabato       |
| time_from    | time              | Orario di inizio fascia (formato `HH:mm:ss`)                       |
| time_to      | time              | Orario di fine fascia (formato `HH:mm:ss`)                         |
| created_at   | datetime (ISO8601)| Data creazione                                                     |
| updated_at   | datetime (ISO8601)| Data ultima modifica                                               |

---

## Autorizzazioni e comportamento per ruolo

| Operazione                | superadmin | admin | employee | cliente |
|---------------------------|:----------:|:-----:|:--------:|:-------:|
| Lista slot auto-conferma  | ✅          | ✅    | ❌        | ❌      |
| Crea slot auto-conferma   | ✅          | ✅    | ❌        | ❌      |
| Elimina slot auto-conferma| ✅          | ✅    | ❌        | ❌      |

---

## Errori comuni

| Status | Struttura risposta                                                              |
|--------|---------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                             |
| 403    | `{ "message": "This action is unauthorized." }`                                 |
| 404    | `{ "message": "No query results for model [AutoConfirmSlot]." }`                |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`          |
