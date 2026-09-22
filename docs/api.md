# CAF API Documentation

> **Base URL:** `https://<host>/api/v1`
>
> **Format:** JSON — include `Accept: application/json` on every request.
>
> **Auth:** Laravel Sanctum personal access tokens.  
> La app raccoglie email + password nativamente (con eventuale codice 2FA); il server rilascia una coppia `access_token` + `refresh_token`.  
> Ogni richiesta protetta deve includere:
> ```
> Authorization: Bearer <access_token>
> ```
>
> **Token lifetimes:** access token = **1 ora** · refresh token = **180 giorni** (single use, rotation)

---

## Table of Contents

- [Authentication](#authentication)
  - [Sanctum token flow](#sanctum-token-flow)
  - [Hardcoded app constants](#hardcoded-app-constants)
  - [POST /login](#post-login)
  - [POST /tokens/refresh](#post-tokensrefresh)
  - [Authenticated requests (auto refresh)](#authenticated-requests-auto-refresh)
  - [POST /logout](#post-logout)
  - [POST /email-password-reset](#post-email-password-reset)
  - [POST /password-reset](#post-password-reset)
- [Clients](#clients) — Vedi [api-clients.md](api-clients.md) per doc completa
  - [GET /clients](#get-clients)
  - [POST /clients](#post-clients)
  - [GET /clients/{id}](#get-clientsid)
  - [PUT /clients/{id}](#put-clientsid)
  - [DELETE /clients/{id}](#delete-clientsid)
- [Client Documents](#client-documents)
  - [POST /clients/{id}/documents](#post-clientsiddocuments)
  - [GET /clients/{clientId}/documents/{documentId}/download](#get-clientsclientiddocumentsdocumentiddownload)
  - [DELETE /clients/{clientId}/documents/{documentId}](#delete-clientsclientiddocumentsdocumentid)
- [Practices (Pratiche)](#practices) — Vedi [api-practices.md](api-practices.md) per doc completa
- [Appointments (Appuntamenti)](#appointments) — Vedi [api-appointments.md](api-appointments.md) per doc completa
- [Users (Utenti)](#users) — Vedi [api-users.md](api-users.md) per doc completa
- [Notifications](#notifications)
- [Dashboard Notices](#dashboard-notices)
- [Practice Types (Tipi Pratica)](#practice-types) — Vedi [api-practice-types.md](api-practice-types.md) per doc completa
- [Auto Confirm Slots (Slot Auto-Conferma)](#auto-confirm-slots) — Vedi [api-auto-confirm-slots.md](api-auto-confirm-slots.md) per doc completa
- [Error Handling](#error-handling)
- [Permissions Reference](#permissions-reference)
- [Changelog](#changelog)

---

## Authentication

This API uses **Laravel Sanctum** personal access tokens.  
The app collects email + password natively (with an optional two-factor code); the server responds with a token **pair**: `access_token` (short lived, `Authorization: Bearer`) + `refresh_token` (long lived, single use, rotation).

### Required dependencies (React Native / Expo)

```bash
npx expo install expo-secure-store
```

### Sanctum token flow

```
App                         Server (Laravel Sanctum)
  |                               |
  |-- 1. POST /login ------------>|
  |   { email, password }         |
  |   (+ two_factor_code se 2FA)  |
  |                               |
  |<-- 2. access_token + ---------|
  |    refresh_token + user       |
  |                               |
  |-- 3. API calls with Bearer --->  (access token valid 1 hour)
  |                               |
  |-- 4. POST /tokens/refresh -->|  (when access token expires)
  |   { "token": <refresh> }      |
  |                               |-- refresh token revoked (rotation)
  |                               |
  |<-- 5. new access_token + ----|
  |    new refresh_token          |  (refresh token valid 180 days)
```

> **Token rotation:** the refresh token is single use. Each `/tokens/refresh` call revokes the submitted token and issues a fresh pair — always store the latest `refresh_token`.

> Store both tokens in `expo-secure-store` — **never** in AsyncStorage or local state.

---

### Hardcoded app constants

```javascript
// constants/auth.js
export const BASE_URL                    = process.env.EXPO_PUBLIC_API_URL ?? 'https://<host>';
export const LOGIN_ENDPOINT              = `${BASE_URL}/api/v1/login`;
export const REFRESH_ENDPOINT            = `${BASE_URL}/api/v1/tokens/refresh`;
export const LOGOUT_ENDPOINT             = `${BASE_URL}/api/v1/logout`;
export const EMAIL_PASSWORD_RESET_ENDPOINT = `${BASE_URL}/api/v1/email-password-reset`;
export const PASSWORD_RESET_ENDPOINT     = `${BASE_URL}/api/v1/password-reset`;
```

> **Note:** all auth endpoints live **inside** `/api/v1`.

---

### POST /login

Native login with email + password. If the account has two-factor authentication enabled, the first attempt returns a challenge; the app then shows a code field and calls the same endpoint with `two_factor_code` (the password must still be sent).

**Auth required:** No

**Throttle:** 20 attempts / minute

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | yes | Account email |
| password | string | yes | Account password |
| two_factor_code | string | no | TOTP code or recovery code, when 2FA is enabled |

**Success response — 200 OK:**
```json
{
  "message": "Autenticazione riuscita.",
  "token_type": "Bearer",
  "access_token": "1|d5c8abf3e0c2b1a4c3d0e1f2a3b4c5d6",
  "refresh_token": "2|a4f7d9b2c1e0f3a2b4c5d6e7f8a9b0c1",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Mario Rossi",
    "email": "mario.rossi@example.com",
    "role": "adminapp",
    "role_name": "contabile",
    "permissions": ["clients.manage"],
    "branches": []
  }
}
```

**Error responses:**

| Status | Condition | Body |
|--------|-----------|------|
| 401 | invalid credentials | `{"message": "Credenziali non valide."}` |
| 401 | 2FA challenge (no code sent, 2FA enabled) | `{"message": "Richiesta verifica a due fattori.", "two_factor_required": true}` |
| 401 | 2FA code invalid | `{"message": "Codice di verifica non valido.", "two_factor_required": false}` |
| 403 | account disabled | `{"message": "Account disattivato."}` |

> `expires_in` is in seconds (3600 = 1 hour). The 2FA code is a standard TOTP (Google Authenticator / Apple Keychain) or one of the user's recovery codes.

---

### POST /tokens/refresh

Rotates the token pair. The submitted refresh token is validated (name prefix + `expires_at`) and **deleted**; a fresh access + refresh pair is issued in the same transaction.

**Auth required:** No (the refresh token itself authenticates)

**Throttle:** 30 attempts / minute

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| token | string | yes | The `refresh_token` issued by `/login` or a previous `/tokens/refresh` |

**Success response — 200 OK:**
```json
{
  "token_type": "Bearer",
  "access_token": "3|b7e2c9d4f5a6b8c0d1e2f3a4b5c6d7e8",
  "refresh_token": "4|c8f3d0e5a6b7c9d1e2f3a4b5c6d7e8f9",
  "expires_in": 3600
}
```

> Store the **new** `refresh_token` — the one you submitted is immediately revoked. If the same token is submitted twice (e.g. a retried request), the second call fails with 401.

**Error response — 401 Unauthorized:**
```json
{
  "message": "Token di refresh non valido."
}
```

> Returned when the token is unknown, not a `mobile-refresh` token, expired, or the user's account has been disabled. On any 401, clear the stored tokens and send the user to the login screen.

---

### Authenticated requests (auto refresh)

Always include the Bearer token and `Accept: application/json`:

```javascript
// services/apiFetch.js
import { getAccessToken, getRefreshToken, saveTokens, clearTokens } from './tokenService';
import { BASE_URL, REFRESH_ENDPOINT } from '../constants/auth';

export async function apiFetch(path, options = {}) {
  const accessToken = await getAccessToken();

  let response = await fetch(`${BASE_URL}/api/v1${path}`, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${accessToken}`,
      'Accept': 'application/json',
    },
  });

  // Auto-refresh on 401
  if (response.status === 401) {
    const refreshToken = await getRefreshToken();
    if (!refreshToken) {
      navigateToLogin();
      return response;
    }

    const refreshed = await refreshAccessToken(refreshToken);
    if (!refreshed) {
      navigateToLogin();
      return response;
    }

    // Retry with the new access token
    response = await fetch(`${BASE_URL}/api/v1${path}`, {
      ...options,
      headers: {
        ...options.headers,
        'Authorization': `Bearer ${refreshed.access_token}`,
        'Accept': 'application/json',
      },
    });
  }

  return response;
}

export async function refreshAccessToken(refreshToken) {
  const response = await fetch(REFRESH_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ token: refreshToken }),
  });

  if (!response.ok) {
    // Refresh token expired or revoked — user must log in again
    await clearTokens();
    return null;
  }

  const tokens = await response.json();
  await saveTokens(tokens.access_token, tokens.refresh_token);
  return tokens;
}
```

`tokenService.js` stores both tokens in `expo-secure-store` (`access_token`, `refresh_token`) and exposes `getAccessToken()`, `getRefreshToken()`, `saveTokens()`, `clearTokens()`.

---

### POST /logout

Revokes the current access token. If the `refresh_token` is included in the body, the refresh token is revoked too (full session termination, also blocks further refreshes).

**Auth required:** Yes (access token)

**Request body (optional):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| refresh_token | string | no | Revokes the session so it cannot be renewed |

**Success response — 200 OK:**
```json
{
  "message": "Disconnesso con successo."
}
```

---

### POST /email-password-reset

Sends an 8-character reset code by email. Always responds 200 regardless of whether the email exists (no account enumeration). The code is stored hashed in `password_reset_tokens` and expires after 1 hour; a new code invalidates the previous one.

**Auth required:** No

**Throttle:** 5 requests / minute

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | yes | Account email |

**Success response — 200 OK:**
```json
{
  "message": "Se l'indirizzo esiste, riceverai un'email con il codice di reset."
}
```

---

### POST /password-reset

Consumes the reset code and sets a new password.

**Auth required:** No

**Throttle:** 5 requests / minute

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | yes | Same email used in `/email-password-reset` |
| token | string | yes | The 8-character reset code from the email |
| password | string | yes | New password, at least 8 characters |
| password_confirmation | string | yes | Must match `password` |

**Success response — 200 OK:**
```json
{
  "message": "Password reimpostata."
}
```

**Error response — 401 Unauthorized:**
```json
{
  "message": "Codice di reset non valido o scaduto."
}
```

---

## Notifications

Endpoint disponibili per alimentare la campanella dell'app mobile e tablet:

- `GET /notifications` — lista notifiche dell'utente autenticato
- `GET /notifications/unread-count` — numero notifiche non lette
- `POST /notifications/{id}/read` — segna una notifica come letta
- `POST /notifications/read-all` — segna tutte le notifiche come lette

Le notifiche sono basate sulle Laravel database notifications già persistite nella tabella `notifications`.

---

## Dashboard Notices

Endpoint disponibile per i blocchi notice/alert della dashboard operatore:

- `GET /dashboard/notices`

Le notice non sono una nuova entità persistente: il backend le deriva da scadenze e dati dashboard già presenti, rispettando lo scope dell'utente autenticato.

---

## Clients

> All client endpoints require authentication and appropriate permissions.  
> See [Permissions Reference](#permissions-reference).

---

### GET /clients

Return a paginated list of client profiles.

**Auth required:** Yes — `clients.view-any`

**Query parameters:**

| Parameter | Type   | Required | Description                                               |
|-----------|--------|----------|-----------------------------------------------------------|
| search    | string | No       | Filter by first name, last name, fiscal code, or phone    |
| page      | int    | No       | Page number (default: 1)                                  |

**Example request:**
```
GET /api/v1/clients?search=rossi&page=2
Authorization: Bearer <token>
Accept: application/json
```

**Success response — 200 OK:**
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
  "first_page_url": "http://<host>/api/v1/clients?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://<host>/api/v1/clients?page=1",
  "next_page_url": null,
  "path": "http://<host>/api/v1/clients",
  "per_page": 20,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

---

### POST /clients

Create a new client profile. Optionally create a linked user account.

**Auth required:** Yes — `clients.create`

**Request body (`application/json`):**

| Field          | Type    | Required                        | Validation                              |
|----------------|---------|---------------------------------|-----------------------------------------|
| first_name     | string  | Yes                             | max:100                                 |
| last_name      | string  | Yes                             | max:100                                 |
| phone          | string  | Yes                             | max:20                                  |
| date_of_birth  | date    | Yes                             | before:today                            |
| fiscal_code    | string  | No                              | exactly 16 chars, unique                |
| email          | string  | No                              | valid email, max:255                    |
| address        | string  | No                              | max:255                                 |
| city           | string  | No                              | max:100                                 |
| province       | string  | No                              | exactly 2 chars (e.g. `MI`)             |
| postal_code    | string  | No                              | exactly 5 digits                        |
| notes          | string  | No                              | max:1000                                |
| create_account | boolean | No                              | If `true`, creates a linked user account|
| account_email  | string  | Required if `create_account=true` | valid email, unique in users table    |

**Example request:**
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
  "postal_code": "20100"
}
```

**Success response — 201 Created:**
```json
{
  "message": "Client created successfully.",
  "data": {
    "id": 2,
    "user_id": null,
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
    "user": null
  }
}
```

---

### GET /clients/{id}

Return a single client with their linked user and documents.

**Auth required:** Yes — `clients.view-any` OR (`clients.view-own` AND the client's `user_id` matches the authenticated user)

**URL parameters:**

| Parameter | Type | Description   |
|-----------|------|---------------|
| id        | int  | Client profile ID |

**Success response — 200 OK:**
```json
{
  "data": {
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
    "user": null,
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
        "uploaded_by_user": {
          "id": 1,
          "name": "Admin",
          "email": "admin@example.com"
        }
      }
    ]
  }
}
```

**Error response — 403 Forbidden:** User lacks permission to view this client.

**Error response — 404 Not Found:** Client does not exist.

---

### PUT /clients/{id}

Update an existing client profile.

**Auth required:** Yes — `clients.update` AND (superadmin, admin, or `created_by` matches authenticated user)

**URL parameters:**

| Parameter | Type | Description       |
|-----------|------|-------------------|
| id        | int  | Client profile ID |

**Request body:** Same fields as [POST /clients](#post-clients), all required.  
`create_account` and `account_email` are accepted but have no effect on update.

**Success response — 200 OK:**
```json
{
  "message": "Client updated successfully.",
  "data": {
    "id": 1,
    "first_name": "Mario",
    "last_name": "Rossi",
    "...": "...",
    "user": null,
    "documents": []
  }
}
```

---

### DELETE /clients/{id}

Delete a client profile.

**Auth required:** Yes — `clients.delete`  
Superadmin can delete any client. Admin can delete all except other admins.

**URL parameters:**

| Parameter | Type | Description       |
|-----------|------|-------------------|
| id        | int  | Client profile ID |

**Success response — 200 OK:**
```json
{
  "message": "Client deleted."
}
```

**Error response — 403 Forbidden:** Insufficient role/permission.

---

## Client Documents

---

### POST /clients/{id}/documents

Upload one or more documents for a client. Uses `multipart/form-data`.

**Auth required:** Yes — `documents.upload`

**URL parameters:**

| Parameter | Type | Description       |
|-----------|------|-------------------|
| id        | int  | Client profile ID |

**Form data:**

| Field           | Type           | Required | Validation                                      |
|-----------------|----------------|----------|-------------------------------------------------|
| files[]         | file (array)   | Yes      | At least 1 file. Types: `pdf, jpg, jpeg, png, doc, docx`. Max 10 MB each. |
| descriptions[]  | string (array) | No       | Optional description per file. Max 255 chars each. Indexes must match `files[]`. |

**Example (React Native — using FormData):**
```javascript
const formData = new FormData();

formData.append('files[]', {
  uri: fileUri,
  name: 'documento.pdf',
  type: 'application/pdf',
});
formData.append('descriptions[]', 'Carta identità');

const response = await fetch(`${BASE_URL}/api/v1/clients/${clientId}/documents`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    // Do NOT set Content-Type manually — let fetch set multipart boundary
  },
  body: formData,
});
```

**Success response — 201 Created:**
```json
{
  "message": "Documents uploaded.",
  "data": [
    {
      "id": 1,
      "client_profile_id": 1,
      "uploaded_by": 1,
      "original_name": "documento.pdf",
      "mime_type": "application/pdf",
      "file_size": 204800,
      "description": "Carta identità",
      "created_at": "2026-03-19T08:44:50.000000Z",
      "updated_at": "2026-03-19T08:44:50.000000Z"
    }
  ]
}
```

**Error response — 422 Unprocessable Entity:** Invalid file type or size exceeded.

---

### GET /clients/{clientId}/documents/{documentId}/download

Download a document file. Returns the binary file stream with the original filename.

**Auth required:** Yes — `documents.download` AND (superadmin / admin / employee, OR the document belongs to the authenticated user's client profile)

**URL parameters:**

| Parameter  | Type | Description  |
|------------|------|--------------|
| clientId   | int  | Client profile ID |
| documentId | int  | Document ID  |

**Response:** Binary file stream (`Content-Disposition: attachment; filename="original_name"`).

**Example (React Native — using expo-file-system):**
```javascript
import * as FileSystem from 'expo-file-system';

const result = await FileSystem.downloadAsync(
  `${BASE_URL}/api/v1/clients/${clientId}/documents/${documentId}/download`,
  FileSystem.documentDirectory + 'documento.pdf',
  {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  }
);
```

---

### DELETE /clients/{clientId}/documents/{documentId}

Delete a document (file + database record).

**Auth required:** Yes — `documents.delete`

**URL parameters:**

| Parameter  | Type | Description       |
|------------|------|-------------------|
| clientId   | int  | Client profile ID |
| documentId | int  | Document ID       |

**Success response — 200 OK:**
```json
{
  "message": "Document deleted."
}
```

---

## Error Handling

All errors follow a consistent JSON structure:

```json
{
  "message": "Human readable error message.",
  "errors": {
    "field_name": ["Validation error detail."]
  }
}
```

The `errors` key is present only on **422 Unprocessable Entity** (validation failures).

| HTTP Status | Meaning                                                                 |
|-------------|-------------------------------------------------------------------------|
| 200         | Success                                                                 |
| 201         | Created                                                                 |
| 401         | Unauthenticated — missing or invalid Bearer token                      |
| 403         | Forbidden — authenticated but insufficient permissions                  |
| 404         | Not found — resource does not exist                                     |
| 422         | Validation error — check `errors` object for field-level details        |
| 500         | Server error                                                            |

> **Important for React Native:** Always include `Accept: application/json` in every request header. Without it, the API may return HTML redirects (302) instead of JSON errors on auth failures.

---

## Permissions Reference

Roles and their permissions on the API:

| Permission          | superadmin | admin | employee | cliente |
|---------------------|:----------:|:-----:|:--------:|:-------:|
| clients.view-any    | ✅          | ✅    | ✅        | ❌       |
| clients.view-own    | ✅          | ✅    | ✅        | ✅       |
| clients.create      | ✅          | ✅    | ✅        | ❌       |
| clients.update      | ✅          | ✅    | ✅        | ❌       |
| clients.delete      | ✅          | ✅    | ❌        | ❌       |
| documents.upload    | ✅          | ✅    | ✅        | ❌       |
| documents.download  | ✅          | ✅    | ✅        | ✅       |
| documents.delete    | ✅          | ✅    | ❌        | ❌       |

---

## Changelog

| Version | Date       | Description                                                 |
|---------|------------|-------------------------------------------------------------|
| v1.5    | 2026-09-22 | Auth: switched from OAuth PKCE (Laravel Passport) to Sanctum personal access tokens. Native login (email + password + 2FA) at POST /login; refresh with rotation at POST /tokens/refresh (access=1h, refresh=180d single use); POST /logout revokes the session; password reset via email code at POST /email-password-reset + POST /password-reset. Removed Passport. |
| v1.4    | 2026-05-20 | Added Auto Confirm Slots API (GET/POST/DELETE /auto-confirm-slots). Appointments created within configured time slots are auto-confirmed. Admin-only feature. |
| v1.3    | 2026-03-31 | Added Procedure API endpoints (CRUD). Updated Practice schema with procedure_id field and auto-derivation logic. |
| v1.0    | 2026-03-19 | Initial API: auth (login/logout), client CRUD, document upload/download/delete |
| v1.1    | 2026-03-19 | Auth: switched to Password Grant, added refresh token (access=1h, refresh=6 months), added POST /refresh endpoint |
| v1.2    | 2026-03-19 | Auth: switched to OAuth 2.0 Authorization Code + PKCE. Removed /login and /refresh endpoints. Login now handled by server browser page + deep link `com.cafapp://callback`. Token exchange via native `/oauth/token`. No client secret. |

<!-- 
  HOW TO UPDATE THIS FILE
  ========================
  When adding a new endpoint:
  1. Add an entry to the Table of Contents
  2. Add a new section under the appropriate group (or create a new group)
  3. Document: auth required, URL params, request body (table), example request, success response (JSON), error responses
  4. Update the Changelog at the bottom with the new version and date

  Section template:
  ---
  ### METHOD /path

  One-line description.

  **Auth required:** Yes/No — `permission.name`

  **URL parameters:** (if any)

  **Request body:** (table or "None")

  **Success response — XXX:**
  ```json
  { ... }
  ```

  **Error responses:** (list notable ones)
-->
