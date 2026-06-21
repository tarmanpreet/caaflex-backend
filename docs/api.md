# CAF API Documentation

> **Base URL:** `https://<host>/api/v1`
>
> **Format:** JSON — include `Accept: application/json` on every request.
>
> **Auth:** OAuth 2.0 Authorization Code + PKCE (Laravel Passport).  
> The app apre un browser sulla pagina di login del server, l'utente inserisce le credenziali, il server rilascia un `code` che l'app scambia per `access_token` + `refresh_token`.  
> Ogni richiesta protetta deve includere:
> ```
> Authorization: Bearer <access_token>
> ```
>
> **Token lifetimes:** access token = **1 ora** · refresh token = **6 mesi**

---

## Table of Contents

- [Authentication](#authentication)
  - [OAuth 2.0 Authorization Code + PKCE Flow](#oauth-20-authorization-code--pkce-flow)
  - [Step 1 — Generate PKCE parameters](#step-1--generate-pkce-parameters)
  - [Step 2 — Open the authorization URL in a browser](#step-2--open-the-authorization-url-in-a-browser)
  - [Step 3 — Handle the deep link callback](#step-3--handle-the-deep-link-callback)
  - [Step 4 — Exchange code for tokens](#step-4--exchange-code-for-tokens)
  - [Step 5 — Make authenticated requests](#step-5--make-authenticated-requests)
  - [Step 6 — Refresh the access token](#step-6--refresh-the-access-token)
  - [POST /logout](#post-logout)
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

This API uses **OAuth 2.0 Authorization Code flow with PKCE** (Proof Key for Code Exchange).  
The credentials (client ID, redirect URI) are hardcoded in the app. There is **no client secret** — the PKCE verifier is the security mechanism.

### Required dependencies (React Native / Expo)

```bash
npx expo install expo-auth-session expo-crypto expo-secure-store expo-web-browser
```

### OAuth 2.0 Authorization Code + PKCE Flow

```
App                         Server (Laravel Passport)
 |                               |
 |-- 1. Generate code_verifier   |
 |   + code_challenge (S256)     |
 |                               |
 |-- 2. Open browser ----------->|
 |   GET /oauth/authorize        |
 |   ?client_id=...              |
 |   &code_challenge=...         |
 |   &response_type=code         |
 |                               |-- User sees login page
 |                               |   User enters credentials
 |                               |-- Server validates credentials
 |                               |
 |<-- 3. Deep link callback -----|
 |   com.cafapp://callback       |
 |   ?code=AUTHORIZATION_CODE    |
 |                               |
 |-- 4. POST /oauth/token ------>|
 |   grant_type=authorization_code
 |   code=AUTHORIZATION_CODE     |
 |   code_verifier=...           |
 |                               |-- Server verifies PKCE
 |<-- access_token + ------------|
 |    refresh_token              |
 |                               |
 |-- 5. API calls with Bearer -->|  (access token valid 1 hour)
 |                               |
 |-- 6. POST /oauth/token ------>|  (when access token expires)
 |   grant_type=refresh_token    |
 |   refresh_token=...           |
 |                               |
 |<-- new access_token + --------|  (refresh token valid 6 months)
 |    new refresh_token          |
```

---

### Hardcoded app constants

```javascript
// auth/constants.js
export const CLIENT_ID     = '019d059e-b655-72d0-985a-d97c17895019';
export const REDIRECT_URI  = 'com.cafapp://callback';
export const BASE_URL      = 'https://<host>';            // no trailing slash
export const AUTH_ENDPOINT = `${BASE_URL}/oauth/authorize`;
export const TOKEN_ENDPOINT = `${BASE_URL}/oauth/token`;
```

> **Note:** The OAuth endpoints (`/oauth/authorize`, `/oauth/token`) are **outside** `/api/v1/`. They are provided directly by Laravel Passport.

---

### Step 1 — Generate PKCE parameters

```javascript
import * as Crypto from 'expo-crypto';

/**
 * Generate a cryptographically random code_verifier (43–128 chars, base64url).
 */
export function generateCodeVerifier() {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return btoa(String.fromCharCode(...array))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=/g, '');
}

/**
 * Derive code_challenge = BASE64URL(SHA256(code_verifier))
 */
export async function generateCodeChallenge(verifier) {
  const digest = await Crypto.digestStringAsync(
    Crypto.CryptoDigestAlgorithm.SHA256,
    verifier,
    { encoding: Crypto.CryptoEncoding.BASE64 }
  );
  // Convert standard base64 → base64url
  return digest.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

/**
 * Generate a random state parameter (CSRF protection).
 */
export function generateState() {
  return Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
}
```

---

### Step 2 — Open the authorization URL in a browser

```javascript
import * as WebBrowser from 'expo-web-browser';
import * as Linking from 'expo-linking';
import {
  CLIENT_ID, REDIRECT_URI, AUTH_ENDPOINT,
  generateCodeVerifier, generateCodeChallenge, generateState,
} from './auth/constants';

export async function startLogin() {
  const codeVerifier  = generateCodeVerifier();
  const codeChallenge = await generateCodeChallenge(codeVerifier);
  const state         = generateState();

  const params = new URLSearchParams({
    client_id:             CLIENT_ID,
    redirect_uri:          REDIRECT_URI,
    response_type:         'code',
    scope:                 '',
    state,
    code_challenge:        codeChallenge,
    code_challenge_method: 'S256',
  });

  const authUrl = `${AUTH_ENDPOINT}?${params.toString()}`;

  // Store verifier + state so we can use them when the callback arrives
  await SecureStore.setItemAsync('pkce_code_verifier', codeVerifier);
  await SecureStore.setItemAsync('pkce_state', state);

  // Open the login page in the system browser
  await WebBrowser.openAuthSessionAsync(authUrl, REDIRECT_URI);
}
```

> **`WebBrowser.openAuthSessionAsync`** opens a secure in-app browser (SFSafariViewController on iOS, Custom Tabs on Android) and automatically intercepts the redirect back to `com.cafapp://callback`.

---

### Step 3 — Handle the deep link callback

After the user logs in on the server, the browser redirects to:
```
com.cafapp://callback?code=AUTHORIZATION_CODE&state=RANDOM_STATE
```

You must register a deep link handler in your app (e.g. in `App.js` or a navigation listener):

```javascript
import * as Linking from 'expo-linking';
import * as SecureStore from 'expo-secure-store';
import { exchangeCodeForTokens } from './auth/tokenService';

// Register once (e.g. in App.js useEffect)
Linking.addEventListener('url', async ({ url }) => {
  const { queryParams } = Linking.parse(url);

  if (!queryParams?.code) return;

  const savedState = await SecureStore.getItemAsync('pkce_state');
  if (queryParams.state !== savedState) {
    console.error('State mismatch — possible CSRF attack');
    return;
  }

  await exchangeCodeForTokens(queryParams.code);
});
```

**Configure deep link in `app.json`:**
```json
{
  "expo": {
    "scheme": "com.cafapp"
  }
}
```

---

### Step 4 — Exchange code for tokens

```javascript
// auth/tokenService.js
import * as SecureStore from 'expo-secure-store';
import { CLIENT_ID, REDIRECT_URI, TOKEN_ENDPOINT } from './constants';

export async function exchangeCodeForTokens(authorizationCode) {
  const codeVerifier = await SecureStore.getItemAsync('pkce_code_verifier');

  const response = await fetch(TOKEN_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json',
    },
    body: new URLSearchParams({
      grant_type:    'authorization_code',
      client_id:     CLIENT_ID,
      redirect_uri:  REDIRECT_URI,
      code:          authorizationCode,
      code_verifier: codeVerifier,
    }).toString(),
  });

  if (!response.ok) {

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
    const error = await response.json();
    throw new Error(error.error_description ?? 'Token exchange failed');
  }

  const tokens = await response.json();
  // tokens = { token_type, access_token, expires_in, refresh_token }

  await saveTokens(tokens.access_token, tokens.refresh_token);

  // Clean up PKCE ephemeral values
  await SecureStore.deleteItemAsync('pkce_code_verifier');
  await SecureStore.deleteItemAsync('pkce_state');

  return tokens;
}

export async function saveTokens(accessToken, refreshToken) {
  await SecureStore.setItemAsync('access_token', accessToken);
  await SecureStore.setItemAsync('refresh_token', refreshToken);
}

export async function getAccessToken() {
  return SecureStore.getItemAsync('access_token');
}

export async function getRefreshToken() {
  return SecureStore.getItemAsync('refresh_token');
}
```

**Token exchange success response — 200 OK:**
```json
{
  "token_type": "Bearer",
  "access_token": "eyJ0eXAiOiJKV1Qi...",
  "expires_in": 3600,
  "refresh_token": "def50200f344e551..."
}
```

> `expires_in` is in seconds (3600 = 1 hour). Store both tokens in `expo-secure-store` — **never** in AsyncStorage or local state.

---

### Step 5 — Make authenticated requests

Always include the Bearer token and `Accept: application/json`:

```javascript
// auth/apiFetch.js
import { getAccessToken, getRefreshToken, saveTokens } from './tokenService';
import { CLIENT_ID, TOKEN_ENDPOINT, BASE_URL } from './constants';

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
```

---

### Step 6 — Refresh the access token

The access token expires after **1 hour**. Use the refresh token to obtain a new pair without asking the user to log in again.  
The refresh token is valid for **6 months**. Each refresh issues a new refresh token (rotation) — always store the latest one.

```javascript
// auth/tokenService.js (continued)
export async function refreshAccessToken(refreshToken) {
  const response = await fetch(TOKEN_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json',
    },
    body: new URLSearchParams({
      grant_type:    'refresh_token',
      client_id:     CLIENT_ID,
      refresh_token: refreshToken,
    }).toString(),
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

export async function clearTokens() {
  await SecureStore.deleteItemAsync('access_token');
  await SecureStore.deleteItemAsync('refresh_token');
}
```

**Refresh success response — 200 OK:**
```json
{
  "token_type": "Bearer",
  "access_token": "eyJ0eXAiOiJKV1Qi...",
  "expires_in": 3600,
  "refresh_token": "def50200a1b2c3d4..."
}
```

> Store the **new** `refresh_token` — the old one is immediately revoked.

**Error response — 401 Unauthorized:**
```json
{
  "error": "invalid_grant",
  "error_description": "The refresh token is invalid.",
  "hint": "Token has been revoked",
  "message": "The refresh token is invalid."
}
```

> When refresh fails with 401, the session has expired (after 6 months) or been revoked. Clear stored tokens and redirect the user to the login flow.

---

### POST /logout

Revoke the current Bearer token.

**Auth required:** Yes

**Request body:** None

**Success response — 200 OK:**
```json
{
  "message": "Logged out successfully."
}
```

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
