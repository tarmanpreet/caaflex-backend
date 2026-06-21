# API Documentation - Filiali (Branches)

## Base URL
```
https://your-domain.com/api/v1
```

Tutte le route richiedono autenticazione via **Laravel Passport** (Bearer Token).

---

## Branch Model

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | - | ID univoco della filiale |
| `name` | string | yes | Nome della filiale |
| `address` | string | yes | Indirizzo completo (via, numero) |
| `city` | string | yes | Città |
| `province` | string (2) | yes | Provincia (2 lettere, es. "MI") |
| `postal_code` | string | yes | CAP |
| `phone` | string\|null | no | Telefono |
| `vat_number` | string\|null | no | PIVA / Codice Fiscale |
| `is_active` | boolean | no | Stato attivo/inattivo (default: true) |
| `created_at` | datetime | - | Data creazione |
| `updated_at` | datetime | - | Data ultimo aggiornamento |
| `employees` | array | - | Lista employee assegnati (quando eager loaded) |

### Employee Object (nested)
```json
{
  "id": 1,
  "name": "Mario Rossi",
  "email": "mario@caf.it",
  "pivot": {
    "branch_id": 1,
    "user_id": 1,
    "assigned_at": "2026-05-20T10:00:00.000000Z"
  }
}
```

---

## Endpoints

### 1. GET `/branches/active`

Restituisce tutte le filiali attive. Utile per dropdown/select senza bisogno di permessi admin.

**Auth:** Bearer Token (qualsiasi utente autenticato)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Sede Milano",
      "address": "Via Roma 1",
      "city": "Milano",
      "province": "MI",
      "postal_code": "20100",
      "phone": "+39 02 1234567"
    },
    {
      "id": 2,
      "name": "Sede Roma",
      "address": "Via Napoli 10",
      "city": "Roma",
      "province": "RM",
      "postal_code": "00100",
      "phone": "+39 06 7654321"
    }
  ]
}
```

---

### 2. GET `/branches`

Restituisce tutte le filiali con employee assegnati.

**Auth:** Bearer Token (admin/superadmin)

**Query Params:** Nessuno

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Sede Milano",
      "address": "Via Roma 1",
      "city": "Milano",
      "province": "MI",
      "postal_code": "20100",
      "phone": "+39 02 1234567",
      "vat_number": "12345678901",
      "is_active": true,
      "created_at": "2026-05-20T10:00:00.000000Z",
      "updated_at": "2026-05-20T10:00:00.000000Z",
      "employees": [
        {
          "id": 1,
          "name": "Mario Rossi",
          "email": "mario@caf.it",
          "pivot": {
            "branch_id": 1,
            "user_id": 1,
            "assigned_at": "2026-05-20T10:00:00.000000Z"
          }
        }
      ]
    }
  ]
}
```

**Response 403 (forbidden):**
```json
{
  "message": "This action is unauthorized."
}
```

---

### 3. GET `/branches/{branch}`

Dettaglio di una singola filiale con employee.

**Auth:** Bearer Token (admin/superadmin)

**Response 200:**
```json
{
  "data": {
    "id": 1,
    "name": "Sede Milano",
    "address": "Via Roma 1",
    "city": "Milano",
    "province": "MI",
    "postal_code": "20100",
    "phone": "+39 02 1234567",
    "vat_number": "12345678901",
    "is_active": true,
    "created_at": "2026-05-20T10:00:00.000000Z",
    "updated_at": "2026-05-20T10:00:00.000000Z",
    "employees": []
  }
}
```

**Response 404:**
```json
{
  "message": "No query results for model [App\\Models\\Branch] 1"
}
```

---

### 4. POST `/branches`

Crea una nuova filiale.

**Auth:** Bearer Token (admin/superadmin)

**Request Body:**
```json
{
  "name": "Sede Napoli",
  "address": "Via Toledo 50",
  "city": "Napoli",
  "province": "NA",
  "postal_code": "80100",
  "phone": "+39 081 1234567",
  "vat_number": "98765432109",
  "is_active": true
}
```

**Validation Rules:**

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `address` | required, string, max:255 |
| `city` | required, string, max:100 |
| `province` | required, string, size:2 |
| `postal_code` | required, string, max:10 |
| `phone` | nullable, string, max:30 |
| `vat_number` | nullable, string, max:20 |
| `is_active` | boolean |

**Response 201:**
```json
{
  "message": "Filiale creata.",
  "data": {
    "id": 3,
    "name": "Sede Napoli",
    "address": "Via Toledo 50",
    "city": "Napoli",
    "province": "NA",
    "postal_code": "80100",
    "phone": "+39 081 1234567",
    "vat_number": "98765432109",
    "is_active": true,
    "created_at": "2026-05-20T12:00:00.000000Z",
    "updated_at": "2026-05-20T12:00:00.000000Z"
  }
}
```

**Response 422 (validation error):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "province": ["The province must be exactly 2 characters."]
  }
}
```

---

### 5. PUT/PATCH `/branches/{branch}`

Aggiorna una filiale esistente.

**Auth:** Bearer Token (admin/superadmin)

**Request Body:** (stesso schema di POST, tutti i campi opzionali per partial update)
```json
{
  "name": "Sede Napoli Centro",
  "phone": "+39 081 9999999"
}
```

**Response 200:**
```json
{
  "message": "Filiale aggiornata.",
  "data": {
    "id": 3,
    "name": "Sede Napoli Centro",
    "address": "Via Toledo 50",
    "city": "Napoli",
    "province": "NA",
    "postal_code": "80100",
    "phone": "+39 081 9999999",
    "vat_number": "98765432109",
    "is_active": true,
    "created_at": "2026-05-20T12:00:00.000000Z",
    "updated_at": "2026-05-20T14:00:00.000000Z"
  }
}
```

---

### 6. DELETE `/branches/{branch}`

Elimina una filiale.

**Auth:** Bearer Token (admin/superadmin)

**Note:** Non è possibile eliminare l'unica filiale esistente (deve essercene almeno 1).

**Response 200:**
```json
{
  "message": "Filiale eliminata."
}
```

**Response 403 (ultima filiale):**
```json
{
  "message": "This action is unauthorized."
}
```

---

### 7. POST `/branches/{branch}/sync-employees`

Assegna o rimuove employee da una filiale.

**Auth:** Bearer Token (admin/superadmin)

**Request Body:**
```json
{
  "user_ids": [1, 3, 5]
}
```

**Validation Rules:**

| Field | Rules |
|-------|-------|
| `user_ids` | required, array |
| `user_ids.*` | integer, exists:users,id |

**Response 200:**
```json
{
  "message": "Employee assegnati alla filiale.",
  "data": {
    "id": 1,
    "name": "Sede Milano",
    "address": "Via Roma 1",
    "city": "Milano",
    "province": "MI",
    "postal_code": "20100",
    "phone": "+39 02 1234567",
    "vat_number": "12345678901",
    "is_active": true,
    "created_at": "2026-05-20T10:00:00.000000Z",
    "updated_at": "2026-05-20T10:00:00.000000Z",
    "employees": [
      {
        "id": 1,
        "name": "Mario Rossi",
        "email": "mario@caf.it",
        "pivot": {
          "branch_id": 1,
          "user_id": 1,
          "assigned_at": "2026-05-20T10:00:00.000000Z"
        }
      },
      {
        "id": 3,
        "name": "Luca Bianchi",
        "email": "luca@caf.it",
        "pivot": {
          "branch_id": 1,
          "user_id": 3,
          "assigned_at": "2026-05-20T14:00:00.000000Z"
        }
      }
    ]
  }
}
```

Per rimuovere tutti gli employee, invia array vuoto:
```json
{
  "user_ids": []
}
```

---

## Changes to Existing APIs

### Summary

| API Endpoint | Change | Breaking? |
|-------------|--------|-----------|
| `GET /appointments` | Response include `branch_id` | **No** - nuovo campo aggiunto |
| `POST /appointments` | Accetta `branch_id` opzionale | **No** - campo opzionale |
| `PUT/PATCH /appointments/{id}` | Accetta `branch_id` opzionale | **No** - campo opzionale |
| `GET /appointments/{id}` | Response include `branch_id` | **No** - nuovo campo aggiunto |
| `GET /appointments-manage` | Filtro employee per filiali assegnate | **No** - filtro trasparente |
| `GET /appointments-calendar` | Filtro employee per filiali assegnate | **No** - filtro trasparente |
| `GET /practices` | Response include `branch_id`, filtro employee per filiali | **No** - nuovo campo + filtro trasparente |
| `POST /practices` | Accetta `branch_id` opzionale | **No** - campo opzionale |
| `PUT/PATCH /practices/{id}` | Accetta `branch_id` opzionale | **No** - campo opzionale |
| `GET /practices/{id}` | Response include `branch_id` | **No** - nuovo campo aggiunto |
| `GET /users` | Response include `branches` per employee | **No** - nuovo campo aggiunto |
| `GET /users/{id}` | Response include `branches` per employee | **No** - nuovo campo aggiunto |
| `PUT/PATCH /users/{id}` | Accetta `branch_ids` opzionale | **No** - campo opzionale |

**Nessun breaking change.** Tutti i cambiamenti sono additivi e backward-compatible.

---

### 1. Appointments API - Changes

#### GET `/appointments`
**Cambiamento:** Response include ora `branch_id` per ogni appuntamento.

**Response (per cliente):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "client_profile_id": 1,
      "practice_type_id": 2,
      "scheduled_at": "2026-05-25T10:00:00.000000Z",
      "duration_minutes": 30,
      "assigned_user_id": 3,
      "branch_id": 1,
      "status": "da_confermare",
      "notes": "Note appuntamento",
      "created_by": 2,
      "created_at": "2026-05-20T12:00:00.000000Z",
      "updated_at": "2026-05-20T12:00:00.000000Z"
    }
  ],
  "links": {},
  "meta": {}
}
```

#### GET `/appointments/{id}`
**Cambiamento:** Response include ora `branch_id`.

**Response:**
```json
{
  "data": {
    "id": 1,
    "client_profile_id": 1,
    "practice_type_id": 2,
    "scheduled_at": "2026-05-25T10:00:00.000000Z",
    "duration_minutes": 30,
    "assigned_user_id": 3,
    "branch_id": 1,
    "status": "da_confermare",
    "notes": "Note appuntamento",
    "created_by": 2,
    "client": { ... },
    "assigned_user": { ... },
    "practice_type": { ... },
    "practice": { ... },
    "creator": { ... }
  }
}
```

#### POST `/appointments`
**Cambiamento:** Accetta ora `branch_id` opzionale nel request body.

**Request Body:**
```json
{
  "client_profile_id": 1,
  "practice_type_id": 2,
  "scheduled_at": "2026-05-25T10:00:00",
  "duration_minutes": 30,
  "assigned_user_id": 3,
  "branch_id": 1,
  "notes": "Appuntamento per dichiarazione 730"
}
```

**Validation Rules aggiuntive:**

| Field | Rules |
|-------|-------|
| `branch_id` | nullable, exists:branches,id |

#### PUT/PATCH `/appointments/{id}`
**Cambiamento:** Accetta ora `branch_id` opzionale per modificare la filiale dell'appuntamento.

**Request Body:**
```json
{
  "status": "confermato",
  "assigned_user_id": 5,
  "branch_id": 2,
  "notes": "Note aggiornate"
}
```

**Validation Rules aggiuntive:**

| Field | Rules |
|-------|-------|
| `branch_id` | nullable, exists:branches,id |

#### GET `/appointments-manage` (Admin/Employee)
**Cambiamento:** Gli employee vedono solo appuntamenti delle filiali a cui sono assegnati (più appuntamenti senza filiale).

**Comportamento:**
- **Admin/Superadmin:** Vedono tutti gli appuntamenti
- **Employee:** Vedono solo appuntamenti dove `branch_id` è NULL o è una delle loro filiali assegnate
- **Cliente:** Vedono solo i propri appuntamenti

**Nessun cambiamento al request/response format.** Il filtro è applicato automaticamente in base al ruolo.

#### GET `/appointments-calendar` (Admin/Employee)
**Cambiamento:** Stesso filtro per filiali assegnate degli employee.

**Comportamento:** Identico a `/appointments-manage`.

---

### 2. Practices API - Changes

#### GET `/practices`
**Cambiamento:** Response include `branch_id` per ogni pratica. Gli employee vedono solo pratiche delle filiali a cui sono assegnati (più pratiche senza filiale).

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "client_profile_id": 1,
      "type": "730",
      "status": "nuova",
      "reference_year": 2025,
      "branch_id": 1,
      "notes": "Note pratica",
      "created_by": 2,
      "client": { ... },
      "assigned_users": [...]
    }
  ],
  "links": {},
  "meta": {}
}
```

**Query Params aggiuntivi:**

| Param | Type | Description |
|-------|------|-------------|
| `branch_id` | integer | Filtra pratiche per filiale |

**Esempio:**
```
GET /api/v1/practices?branch_id=1
```

#### GET `/practices/{id}`
**Cambiamento:** Response include ora `branch_id`.

**Response:**
```json
{
  "data": {
    "id": 1,
    "client_profile_id": 1,
    "type": "730",
    "status": "nuova",
    "reference_year": 2025,
    "branch_id": 1,
    "notes": "Note pratica",
    "created_by": 2,
    "client": { ... },
    "assigned_users": [...],
    "notes": [...],
    "documents": [...],
    "status_logs": [...]
  }
}
```

#### POST `/practices`
**Cambiamento:** Accetta ora `branch_id` opzionale.

**Request Body:**
```json
{
  "client_profile_id": 1,
  "type": "730",
  "status": "nuova",
  "reference_year": 2025,
  "branch_id": 1,
  "user_ids": [3],
  "notes": "Pratica 730 per cliente"
}
```

**Validation Rules aggiuntive:**

| Field | Rules |
|-------|-------|
| `branch_id` | nullable, exists:branches,id |

#### PUT/PATCH `/practices/{id}`
**Cambiamento:** Accetta ora `branch_id` opzionale.

**Request Body:**
```json
{
  "branch_id": 2,
  "status": "in_lavorazione"
}
```

---

### 3. Users API - Changes

#### GET `/users`
**Cambiamento:** Response include `branches` per gli employee.

**Response:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Mario Rossi",
        "email": "mario@caf.it",
        "is_active": true,
        "assigned_practices_count": 5,
        "open_practices_count": 3,
        "roles": [
          {
            "id": 1,
            "name": "employee",
            "guard_name": "web"
          }
        ]
      }
    ],
    "links": {},
    "meta": {}
  }
}
```

**Nota:** Il campo `branches` non è incluso nella list view per performance. Usa `GET /users/{id}` per vedere le filiali assegnate.

#### GET `/users/{id}`
**Cambiamento:** Response include `branches` per gli employee.

**Response (employee):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Mario Rossi",
      "email": "mario@caf.it",
      "is_active": true,
      "roles": [
        {
          "id": 1,
          "name": "employee",
          "guard_name": "web"
        }
      ],
      "practice_types": [
        {
          "id": 1,
          "name": "730 - Dichiarazione dei redditi"
        }
      ],
      "branches": [
        {
          "id": 1,
          "name": "Sede Milano",
          "address": "Via Roma 1",
          "city": "Milano",
          "province": "MI",
          "postal_code": "20100",
          "phone": "+39 02 1234567",
          "is_active": true
        },
        {
          "id": 2,
          "name": "Sede Roma",
          "address": "Via Napoli 10",
          "city": "Roma",
          "province": "RM",
          "postal_code": "00100",
          "phone": "+39 06 7654321",
          "is_active": true
        }
      ]
    },
    "activePractices": { ... },
    "closedPractices": { ... },
    "availableRoles": ["superadmin", "admin", "employee", "cliente"],
    "allPracticeTypes": [...],
    "practiceFilters": { ... }
  }
}
```

**Nota:** `branches` è presente solo per gli employee. Per admin/superadmin/cliente il campo non è incluso o è vuoto.

#### PUT/PATCH `/users/{id}`
**Cambiamento:** Accetta ora `branch_ids` opzionale.

**Request Body:**
```json
{
  "name": "Mario Rossi",
  "email": "mario@caf.it",
  "role": "employee",
  "practice_type_ids": [1, 2],
  "branch_ids": [1, 3]
}
```

**Validation Rules aggiuntive:**

| Field | Rules |
|-------|-------|
| `branch_ids` | nullable, array |
| `branch_ids.*` | integer, exists:branches,id |

**Comportamento:**
- Se `role` è `employee`, le `branch_ids` vengono sincronizzate (replace)
- Se `role` NON è `employee`, tutte le filiali vengono rimosse dall'utente automaticamente

---

### 4. Employee Filtering Behavior

**Importante per l'app React Native:**

Quando un employee fa request a `/appointments-manage` o `/practices`, il filtro per filiali è applicato automaticamente sul backend. L'app non deve implementare questo filtro lato client.

**Logica del filtro:**
```
Se employee:
  branchIds = filiali assegnate all'employee
  dove branch_id IS NULL OR branch_id IN (branchIds)
Altrimenti:
  Nessun filtro per filiale
```

Questo significa che:
- Un employee vede **sempre** appuntamenti/pratiche senza filiale (`branch_id` = NULL)
- Un employee vede appuntamenti/pratiche **solo** delle filiali a cui è assegnato
- Se un employee non ha filiali assegnate, vede solo record senza filiale

---

### Migration Note for React Native App

**Nessun action required** per la maggior parte delle funzionalità. I cambiamenti sono:

1. **Nuovi campi nelle response** (`branch_id`) - ignorali se non ti servono, non rompono nulla
2. **Nuovi campi nei request** (`branch_id`, `branch_ids`) - opzionali, non devi inviarli se non ti servono
3. **Filtro employee per filiali** - applicato automaticamente sul backend, nessun cambiamento lato app

**Se vuoi sfruttare le filiali nell'app:**
1. Usa `GET /branches/active` per ottenere la lista delle filiali
2. Invia `branch_id` quando crei/appunti appuntamenti o pratiche
3. Mostra il nome della filiale negli appuntamenti/pratiche usando i dati del campo `branch_id`

---

## Errori comuni

| Status Code | Significato |
|-------------|-------------|
| 401 | Non autenticato - manca o è invalido il Bearer Token |
| 403 | Permesso negato - l'utente non ha i permessi necessari |
| 404 | Filiale non trovata |
| 422 | Validazione fallita - controlla il campo `errors` nella response |

---

## Esempio React Native (axios)

### Branch API Helper

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://your-domain.com/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  },
});

// ===================
// BRANCH ENDPOINTS
// ===================

// Get active branches (for dropdowns/selects)
const fetchActiveBranches = async () => {
  const { data } = await api.get('/branches/active');
  return data.data; // Array of branches
};

// Get all branches with employees (admin only)
const fetchAllBranches = async () => {
  const { data } = await api.get('/branches');
  return data.data;
};

// Get single branch detail (admin only)
const fetchBranch = async (id) => {
  const { data } = await api.get(`/branches/${id}`);
  return data.data;
};

// Create branch (admin only)
const createBranch = async (branchData) => {
  const { data } = await api.post('/branches', branchData);
  return data.data;
};

// Update branch (admin only)
const updateBranch = async (id, branchData) => {
  const { data } = await api.put(`/branches/${id}`, branchData);
  return data.data;
};

// Delete branch (admin only, min 1 branch required)
const deleteBranch = async (id) => {
  const { data } = await api.delete(`/branches/${id}`);
  return data.message;
};

// Sync employees to branch (admin only)
const syncEmployees = async (branchId, userIds) => {
  const { data } = await api.post(`/branches/${branchId}/sync-employees`, {
    user_ids: userIds,
  });
  return data.data;
};

// ===================
// APPOINTMENT ENDPOINTS (with branch support)
// ===================

// Create appointment with branch_id (optional)
const createAppointment = async (appointmentData) => {
  const { data } = await api.post('/appointments', {
    ...appointmentData,
    branch_id: appointmentData.branch_id || null, // optional
  });
  return data.data;
};

// Update appointment with branch_id (optional)
const updateAppointment = async (id, appointmentData) => {
  const { data } = await api.put(`/appointments/${id}`, {
    ...appointmentData,
    branch_id: appointmentData.branch_id || null, // optional
  });
  return data.data;
};

// Get appointment detail (includes branch_id in response)
const fetchAppointment = async (id) => {
  const { data } = await api.get(`/appointments/${id}`);
  return data.data; // includes branch_id field
};

// ===================
// PRACTICE ENDPOINTS (with branch support)
// ===================

// Create practice with branch_id (optional)
const createPractice = async (practiceData) => {
  const { data } = await api.post('/practices', {
    ...practiceData,
    branch_id: practiceData.branch_id || null, // optional
  });
  return data.data;
};

// Update practice with branch_id (optional)
const updatePractice = async (id, practiceData) => {
  const { data } = await api.put(`/practices/${id}`, {
    ...practiceData,
    branch_id: practiceData.branch_id || null, // optional
  });
  return data.data;
};

// Get practices filtered by branch
const fetchPracticesByBranch = async (branchId, page = 1) => {
  const { data } = await api.get('/practices', {
    params: { branch_id: branchId, page },
  });
  return data;
};

// ===================
// USER ENDPOINTS (with branch support)
// ===================

// Update user with branch_ids (admin only, employee role)
const updateUserWithBranches = async (id, userData) => {
  const { data } = await api.put(`/users/${id}`, {
    ...userData,
    branch_ids: userData.branch_ids || [], // optional, only for employees
  });
  return data.data;
};

// Get user detail (includes branches for employees)
const fetchUser = async (id) => {
  const { data } = await api.get(`/users/${id}`);
  return data.data; // user.branches available for employees
};

// ===================
// UTILITY FUNCTIONS
// ===================

// Format branch name for display
const formatBranchName = (branch) => {
  if (!branch) return 'Nessuna filiale';
  return `${branch.name} - ${branch.city} (${branch.province})`;
};

// Get branch by ID from a list
const getBranchById = (branches, id) => {
  return branches.find(b => b.id === id) || null;
};
```

### Esempio di uso in un Component React Native

```javascript
import { useState, useEffect } from 'react';
import { View, Text, FlatList, ActivityIndicator } from 'react-native';

function AppointmentListScreen() {
  const [appointments, setAppointments] = useState([]);
  const [branches, setBranches] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      // Carica filiali e appuntamenti in parallelo
      const [branchesRes, appointmentsRes] = await Promise.all([
        fetchActiveBranches(),
        api.get('/appointments'),
      ]);

      setBranches(branchesRes.data);
      setAppointments(appointmentsRes.data.data);
    } catch (error) {
      console.error('Error loading data:', error);
    } finally {
      setLoading(false);
    }
  };

  const getBranchName = (branchId) => {
    const branch = branches.find(b => b.id === branchId);
    return branch ? formatBranchName(branch) : 'Nessuna filiale';
  };

  if (loading) {
    return <ActivityIndicator size="large" />;
  }

  return (
    <FlatList
      data={appointments}
      keyExtractor={(item) => item.id.toString()}
      renderItem={({ item }) => (
        <View style={styles.card}>
          <Text>{item.client?.first_name} {item.client?.last_name}</Text>
          <Text>{new Date(item.scheduled_at).toLocaleString('it-IT')}</Text>
          <Text style={styles.branch}>{getBranchName(item.branch_id)}</Text>
        </View>
      )}
    />
  );
}
```
