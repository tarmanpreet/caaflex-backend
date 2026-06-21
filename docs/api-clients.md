# API — Clients

> **Base URL:** `https://<host>/api/v1`  
> **Auth:** Bearer token (OAuth 2.0 PKCE). Vedi `api.md` per il flusso completo.  
> **Headers richiesti su ogni chiamata:**
> ```
> Authorization: Bearer <access_token>
> Accept: application/json
> ```

---

## Indice

- [Clients](#clients)
  - [GET /clients — Lista paginata](#get-clients)
  - [GET /clients/search — Ricerca autocomplete](#get-clientssearch)
  - [POST /clients — Crea cliente](#post-clients)
  - [GET /clients/{id} — Dettaglio cliente](#get-clientsid)
  - [PUT /clients/{id} — Aggiorna cliente](#put-clientsid)
  - [DELETE /clients/{id} — Elimina cliente](#delete-clientsid)
- [Documenti cliente](#documenti-cliente)
  - [POST /clients/{id}/documents — Carica documenti](#post-clientsiddocuments)
  - [GET /clients/{clientId}/documents/{documentId}/download — Scarica documento](#get-clientsclientiddocumentsdocumentiddownload)
  - [DELETE /clients/{clientId}/documents/{documentId} — Elimina documento](#delete-clientsclientiddocumentsdocumentid)
- [Struttura oggetti](#struttura-oggetti)
- [Autorizzazioni e comportamento per ruolo](#autorizzazioni-e-comportamento-per-ruolo)
- [Errori comuni](#errori-comuni)

---

## Clients

---

### GET /clients

Restituisce la lista paginata dei profili cliente. Ogni pagina contiene 20 risultati. Include la relazione `user` se il cliente ha un account collegato.

**Auth:** `clients.view-any`

**Query parameters:**

| Parametro | Tipo   | Default | Descrizione                                                     |
|-----------|--------|---------|-----------------------------------------------------------------|
| search    | string | —       | Filtra per nome, cognome, codice fiscale o telefono (LIKE %...%) |
| page      | int    | 1       | Numero di pagina                                                |

**Comportamento:**
- La ricerca opera in OR su `first_name`, `last_name`, `fiscal_code`, `phone`.
- I risultati includono sempre la relazione `user` (null se il cliente non ha account).
- L'ordinamento è per data di creazione discendente (default Eloquent).

**Esempio:**
```
GET /api/v1/clients?search=rossi&page=1
```

**Risposta 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": null,
      "first_name": "Mario",
      "last_name": "Rossi",
      "phone": "3331234567",
      "date_of_birth": "1985-06-15",
      "fiscal_code": "RSSMRA85H15F205Z",
      "email": "mario.rossi@example.com",
      "address": "Via Roma 1",
      "city": "Milano",
      "province": "MI",
      "postal_code": "20100",
      "notes": null,
      "created_by": 1,
      "created_at": "2026-03-18T10:00:00.000000Z",
      "updated_at": "2026-03-18T10:00:00.000000Z",
      "user": null
    }
  ],
  "first_page_url": "https://<host>/api/v1/clients?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "https://<host>/api/v1/clients?page=3",
  "next_page_url": "https://<host>/api/v1/clients?page=2",
  "path": "https://<host>/api/v1/clients",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 42
}
```

---

### GET /clients/search

Ricerca leggera pensata per autocomplete e select. Restituisce solo `id`, `first_name`, `last_name` formattati come coppie `value`/`label`. Supporta paginazione lato client.

**Auth:** `clients.view-any`

**Query parameters:**

| Parametro | Tipo   | Default | Descrizione                                          |
|-----------|--------|---------|------------------------------------------------------|
| q         | string | —       | Testo da cercare su nome, cognome, codice fiscale     |
| per_page  | int    | 15      | Risultati per pagina (min 1, max 100)                |
| page      | int    | 1       | Numero di pagina                                     |

**Comportamento:**
- Se `q` è vuoto restituisce tutti i clienti ordinati per cognome e nome.
- La ricerca opera in OR su `first_name`, `last_name`, `fiscal_code`.
- `hasMore` indica se esiste una pagina successiva (utile per infinite scroll).
- Il campo `label` è nel formato `"Cognome Nome"`.

**Esempio:**
```
GET /api/v1/clients/search?q=ros&per_page=10&page=1
```

**Risposta 200:**
```json
{
  "results": [
    { "value": 1, "label": "Rossi Mario" },
    { "value": 4, "label": "Rossi Giovanni" }
  ],
  "hasMore": false
}
```

---

### POST /clients

Crea un nuovo profilo cliente. Opzionalmente crea un account utente collegato al profilo.

**Auth:** `clients.create`

**Content-Type:** `application/json`

**Campi:**

| Campo          | Tipo    | Obbligatorio                      | Validazione                                    |
|----------------|---------|-----------------------------------|------------------------------------------------|
| first_name     | string  | Sì                                | max 100 caratteri                              |
| last_name      | string  | Sì                                | max 100 caratteri                              |
| phone          | string  | Sì                                | max 20 caratteri                               |
| date_of_birth  | date    | Sì                                | formato `YYYY-MM-DD`, deve essere nel passato  |
| fiscal_code    | string  | No                                | esattamente 16 caratteri, unico               |
| email          | string  | No                                | formato email valido, max 255                  |
| address        | string  | No                                | max 255 caratteri                              |
| city           | string  | No                                | max 100 caratteri                              |
| province       | string  | No                                | esattamente 2 caratteri (es. `MI`)             |
| postal_code    | string  | No                                | esattamente 5 caratteri                        |
| notes          | string  | No                                | max 1000 caratteri                             |
| create_account | boolean | No (default false)                | Se `true` crea un account utente collegato     |
| account_email  | string  | Obbligatorio se `create_account=true` | email valida, unica nella tabella `users`  |

**Comportamento:**
- `created_by` viene impostato automaticamente all'utente autenticato.
- Se `create_account=true`: viene creato un `User` con ruolo `cliente`, password casuale, e inviato il link di reset password. L'`id` dell'utente viene salvato su `client_profiles.user_id`.
- Se `create_account=false` (default): `user_id` rimane `null`.
- `account_email` è separata da `email`: la prima è per il login, la seconda è il contatto del cliente.

**Esempio:**
```json
{
  "first_name": "Mario",
  "last_name": "Rossi",
  "phone": "3331234567",
  "date_of_birth": "1985-06-15",
  "fiscal_code": "RSSMRA85H15F205Z",
  "email": "mario.rossi@example.com",
  "city": "Milano",
  "province": "MI",
  "postal_code": "20100",
  "create_account": true,
  "account_email": "mario.rossi@login.com"
}
```

**Risposta 201:**
```json
{
  "message": "Client created successfully.",
  "data": {
    "id": 2,
    "user_id": 5,
    "first_name": "Mario",
    "last_name": "Rossi",
    "phone": "3331234567",
    "date_of_birth": "1985-06-15",
    "fiscal_code": "RSSMRA85H15F205Z",
    "email": "mario.rossi@example.com",
    "address": null,
    "city": "Milano",
    "province": "MI",
    "postal_code": "20100",
    "notes": null,
    "created_by": 1,
    "created_at": "2026-03-19T08:00:00.000000Z",
    "updated_at": "2026-03-19T08:00:00.000000Z",
    "user": {
      "id": 5,
      "name": "Mario Rossi",
      "email": "mario.rossi@login.com"
    }
  }
}
```

**Errori:**

| Status | Causa                                                          |
|--------|----------------------------------------------------------------|
| 403    | Utente non ha il permesso `clients.create`                     |
| 422    | Validazione fallita (es. `fiscal_code` già esistente)          |

---

### GET /clients/{id}

Restituisce il dettaglio completo di un cliente con l'utente collegato e la lista dei documenti caricati. Ogni documento include i dati di chi lo ha caricato.

**Auth:** `clients.view-any` oppure (`clients.view-own` E `client.user_id` corrisponde all'utente autenticato)

**URL parameters:**

| Parametro | Tipo | Descrizione       |
|-----------|------|-------------------|
| id        | int  | ID profilo cliente |

**Comportamento:**
- Carica sempre le relazioni `user` e `documents.uploadedBy`.
- Un cliente con ruolo `cliente` può vedere solo il proprio profilo (`clients.view-own`).
- Staff (admin, employee) con `clients.view-any` può vedere qualsiasi profilo.

**Risposta 200:**
```json
{
  "data": {
    "id": 1,
    "user_id": 5,
    "first_name": "Mario",
    "last_name": "Rossi",
    "phone": "3331234567",
    "date_of_birth": "1985-06-15",
    "fiscal_code": "RSSMRA85H15F205Z",
    "email": "mario.rossi@example.com",
    "address": "Via Roma 1",
    "city": "Milano",
    "province": "MI",
    "postal_code": "20100",
    "notes": null,
    "created_by": 1,
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-18T10:00:00.000000Z",
    "user": {
      "id": 5,
      "name": "Mario Rossi",
      "email": "mario.rossi@login.com"
    },
    "documents": [
      {
        "id": 1,
        "client_profile_id": 1,
        "uploaded_by": 1,
        "original_name": "carta_identita.pdf",
        "mime_type": "application/pdf",
        "file_size": 204800,
        "description": "Carta d'identità",
        "created_at": "2026-03-19T08:44:50.000000Z",
        "updated_at": "2026-03-19T08:44:50.000000Z",
        "uploaded_by": {
          "id": 1,
          "name": "Admin",
          "email": "admin@example.com"
        }
      }
    ]
  }
}
```

**Errori:**

| Status | Causa                                           |
|--------|-------------------------------------------------|
| 403    | Utente non autorizzato a vedere questo cliente  |
| 404    | Cliente non trovato                             |

---

### PUT /clients/{id}

Aggiorna i dati di un profilo cliente esistente. Restituisce il profilo aggiornato con relazioni `user` e `documents`.

**Auth:** `clients.update` E (superadmin, oppure admin, oppure `created_by` corrisponde all'utente autenticato)

**URL parameters:**

| Parametro | Tipo | Descrizione       |
|-----------|------|-------------------|
| id        | int  | ID profilo cliente |

**Content-Type:** `application/json`

**Campi:** Stessi di [POST /clients](#post-clients). Tutti i campi obbligatori restano obbligatori.

> `create_account` e `account_email` sono accettati ma ignorati in update — non creano né modificano l'account utente collegato. Per gestire l'account usare le API utente.

**Comportamento:**
- `fiscal_code` deve essere unico ma esclude il cliente corrente dalla verifica duplicati.
- `account_email` in update: se presente, deve essere unica escludendo l'utente già collegato al cliente.
- I campi nullable (`fiscal_code`, `email`, `address`, `city`, `province`, `postal_code`, `notes`) possono essere passati come `null` per cancellarli.

**Risposta 200:**
```json
{
  "message": "Client updated successfully.",
  "data": {
    "id": 1,
    "user_id": 5,
    "first_name": "Mario",
    "last_name": "Bianchi",
    "phone": "3331234567",
    "date_of_birth": "1985-06-15",
    "fiscal_code": "RSSMRA85H15F205Z",
    "email": "mario.bianchi@example.com",
    "address": "Via Roma 1",
    "city": "Milano",
    "province": "MI",
    "postal_code": "20100",
    "notes": null,
    "created_by": 1,
    "created_at": "2026-03-18T10:00:00.000000Z",
    "updated_at": "2026-03-22T09:00:00.000000Z",
    "user": { "id": 5, "name": "Mario Rossi", "email": "mario.rossi@login.com" },
    "documents": []
  }
}
```

**Errori:**

| Status | Causa                                                               |
|--------|---------------------------------------------------------------------|
| 403    | Manca `clients.update` o l'utente non è il creatore/admin           |
| 404    | Cliente non trovato                                                 |
| 422    | Validazione fallita                                                 |

---

### DELETE /clients/{id}

Elimina un profilo cliente. Non elimina l'account utente collegato né i documenti fisici.

**Auth:** `clients.delete`

**URL parameters:**

| Parametro | Tipo | Descrizione       |
|-----------|------|-------------------|
| id        | int  | ID profilo cliente |

**Comportamento per ruolo:**
- **superadmin:** può eliminare qualsiasi cliente.
- **admin:** può eliminare tutti tranne i clienti il cui `user` ha ruolo `admin`.
- **employee / cliente:** non ha il permesso `clients.delete` — riceve 403.

**Risposta 200:**
```json
{
  "message": "Client deleted."
}
```

**Errori:**

| Status | Causa                                                        |
|--------|--------------------------------------------------------------|
| 403    | Permesso mancante o ruolo insufficiente                      |
| 404    | Cliente non trovato                                          |

---

## Documenti cliente

---

### POST /clients/{id}/documents

Carica uno o più file associati a un cliente. Usa `multipart/form-data`. I file vengono salvati su disco locale del server.

**Auth:** `documents.upload`

**URL parameters:**

| Parametro | Tipo | Descrizione       |
|-----------|------|-------------------|
| id        | int  | ID profilo cliente |

**Form data:**

| Campo          | Tipo           | Obbligatorio | Validazione                                                          |
|----------------|----------------|--------------|----------------------------------------------------------------------|
| files[]        | file (array)   | Sì           | Almeno 1 file. Tipi ammessi: `pdf, jpg, jpeg, png, doc, docx`. Max 10 MB ciascuno. |
| descriptions[] | string (array) | No           | Descrizione opzionale per ogni file. Max 255 caratteri. Gli indici devono corrispondere a quelli di `files[]`. |

**Comportamento:**
- `uploaded_by` viene impostato automaticamente all'utente autenticato.
- Se si caricano 3 file, `descriptions[0]` corrisponde al primo file, `descriptions[1]` al secondo, ecc. Gli indici mancanti producono `description = null`.
- Il file viene salvato in `storage/app/client-documents/{client_id}/`.
- Non è esposto il path fisico — il download avviene solo tramite l'endpoint dedicato.

**Esempio (React Native):**
```javascript
const formData = new FormData();

formData.append('files[]', {
  uri: fileUri,
  name: 'carta_identita.pdf',
  type: 'application/pdf',
});
formData.append('descriptions[]', "Carta d'identità");

await apiFetch(`/clients/${clientId}/documents`, {
  method: 'POST',
  // Non impostare Content-Type — fetch lo imposta automaticamente con il boundary
  body: formData,
});
```

**Risposta 201:**
```json
{
  "message": "Documents uploaded.",
  "data": [
    {
      "id": 3,
      "client_profile_id": 1,
      "uploaded_by": 1,
      "original_name": "carta_identita.pdf",
      "mime_type": "application/pdf",
      "file_size": 204800,
      "description": "Carta d'identità",
      "created_at": "2026-03-22T09:00:00.000000Z",
      "updated_at": "2026-03-22T09:00:00.000000Z"
    }
  ]
}
```

**Errori:**

| Status | Causa                                                     |
|--------|-----------------------------------------------------------|
| 403    | Manca il permesso `documents.upload`                      |
| 404    | Cliente non trovato                                       |
| 422    | File mancante, tipo non ammesso, o dimensione superata    |

---

### GET /clients/{clientId}/documents/{documentId}/download

Scarica il file fisico di un documento. La risposta è lo stream binario del file con l'header `Content-Disposition: attachment`.

**Auth:** `documents.download` E (superadmin, admin, employee, oppure il documento appartiene al profilo del cliente autenticato)

**URL parameters:**

| Parametro  | Tipo | Descrizione        |
|------------|------|--------------------|
| clientId   | int  | ID profilo cliente |
| documentId | int  | ID documento       |

**Comportamento:**
- Il filename nell'header `Content-Disposition` è il nome originale del file al momento dell'upload.
- Un utente con ruolo `cliente` può scaricare solo i documenti del proprio profilo.

**Risposta 200:** Stream binario del file.
```
Content-Disposition: attachment; filename="carta_identita.pdf"
Content-Type: application/pdf
```

**Esempio (React Native con expo-file-system):**
```javascript
import * as FileSystem from 'expo-file-system';

const localPath = FileSystem.documentDirectory + 'carta_identita.pdf';

const result = await FileSystem.downloadAsync(
  `${BASE_URL}/api/v1/clients/${clientId}/documents/${documentId}/download`,
  localPath,
  { headers: { Authorization: `Bearer ${token}` } }
);

// result.uri contiene il path locale del file scaricato
```

**Errori:**

| Status | Causa                                                      |
|--------|------------------------------------------------------------|
| 403    | Permesso mancante o documento non appartiene al cliente    |
| 404    | Cliente o documento non trovati                            |

---

### DELETE /clients/{clientId}/documents/{documentId}

Elimina un documento: rimuove il record dal database e il file fisico dal disco.

**Auth:** `documents.delete`

**URL parameters:**

| Parametro  | Tipo | Descrizione        |
|------------|------|--------------------|
| clientId   | int  | ID profilo cliente |
| documentId | int  | ID documento       |

**Comportamento:**
- L'operazione è irreversibile — il file viene cancellato dal disco.
- Non c'è vincolo di ruolo aggiuntivo oltre al permesso: chiunque abbia `documents.delete` può eliminare qualsiasi documento.

**Risposta 200:**
```json
{
  "message": "Document deleted."
}
```

**Errori:**

| Status | Causa                              |
|--------|------------------------------------|
| 403    | Manca il permesso `documents.delete` |
| 404    | Cliente o documento non trovati    |

---

## Struttura oggetti

### ClientProfile

| Campo         | Tipo            | Descrizione                                              |
|---------------|-----------------|----------------------------------------------------------|
| id            | int             | Identificatore univoco                                   |
| user_id       | int \| null     | ID dell'utente collegato (null se nessun account)        |
| first_name    | string          | Nome                                                     |
| last_name     | string          | Cognome                                                  |
| phone         | string          | Telefono                                                 |
| date_of_birth | date (Y-m-d)    | Data di nascita                                          |
| fiscal_code   | string \| null  | Codice fiscale (16 caratteri)                            |
| email         | string \| null  | Email di contatto del cliente                            |
| address       | string \| null  | Indirizzo                                                |
| city          | string \| null  | Città                                                    |
| province      | string \| null  | Sigla provincia (2 caratteri)                            |
| postal_code   | string \| null  | CAP (5 caratteri)                                        |
| notes         | string \| null  | Note interne                                             |
| created_by    | int             | ID dell'utente che ha creato il profilo                  |
| created_at    | datetime (ISO8601) | Data creazione                                        |
| updated_at    | datetime (ISO8601) | Data ultima modifica                                  |

### ClientDocument

| Campo             | Tipo            | Descrizione                                         |
|-------------------|-----------------|-----------------------------------------------------|
| id                | int             | Identificatore univoco                              |
| client_profile_id | int             | ID del profilo cliente proprietario                 |
| uploaded_by       | int             | ID dell'utente che ha caricato il file              |
| original_name     | string          | Nome originale del file al momento dell'upload      |
| mime_type         | string          | MIME type rilevato dal server                       |
| file_size         | int             | Dimensione in byte                                  |
| description       | string \| null  | Descrizione opzionale                               |
| created_at        | datetime (ISO8601) | Data upload                                      |
| updated_at        | datetime (ISO8601) | Data ultima modifica                             |

> `disk_path` (path fisico sul server) non è mai esposto nelle risposte API.

---

## Autorizzazioni e comportamento per ruolo

| Operazione               | superadmin | admin | employee | cliente         |
|--------------------------|:----------:|:-----:|:--------:|:---------------:|
| Lista clienti            | ✅          | ✅    | ✅        | ❌              |
| Ricerca autocomplete     | ✅          | ✅    | ✅        | ❌              |
| Crea cliente             | ✅          | ✅    | ✅        | ❌              |
| Vedi qualsiasi cliente   | ✅          | ✅    | ✅        | ❌              |
| Vedi proprio profilo     | ✅          | ✅    | ✅        | ✅              |
| Aggiorna cliente         | ✅          | ✅    | ✅ (solo creati da sé) | ❌  |
| Elimina cliente          | ✅          | ✅ (non altri admin) | ❌ | ❌        |
| Carica documenti         | ✅          | ✅    | ✅        | ❌              |
| Scarica documenti        | ✅          | ✅    | ✅        | ✅ (solo propri)|
| Elimina documenti        | ✅          | ✅    | ❌        | ❌              |

> **Superadmin bypass:** il `Gate::before` restituisce `true` per il ruolo `superadmin` — tutte le verifiche Policy vengono saltate.

---

## Errori comuni

| Status | Struttura risposta                                                                   |
|--------|--------------------------------------------------------------------------------------|
| 401    | `{ "message": "Unauthenticated." }`                                                  |
| 403    | `{ "message": "This action is unauthorized." }`                                      |
| 404    | `{ "message": "No query results for model [ClientProfile]." }`                       |
| 422    | `{ "message": "...", "errors": { "campo": ["messaggio di errore"] } }`               |

> Includi sempre `Accept: application/json` — senza di esso i 401/403 possono restituire un redirect HTML invece del JSON.
