# WMVAA Akademia — Universal Sync Engine & Mobile API

## 1. Overview

Phase 11 menyediakan Universal Sync Gateway untuk integrasi dengan aplikasi mobile (Kodular) dan sistem lain.

---

## 2. Architecture

```
Mobile App (Kodular) ──→ API Gateway ──→ MobileSyncController
                                              │
                                              ├── authenticate()
                                              ├── syncAll()
                                              ├── syncDelta()
                                              ├── uploadFile()
                                              └── health()
```

### 2.1 Design Principles

1. **Central Router Pattern**: Satu endpoint dengan action parameter
2. **JSON-Only Responses**: Semua response berformat JSON
3. **Session Token Auth**: Token-based authentication
4. **Backend RBAC**: Otorisasi di backend
5. **Universal Delta Sync**: Format payload standar

---

## 3. API Endpoints

### 3.1 Central Router

```
POST/GET /api/v1/sync?action=<action>
POST/GET /api/sync?action=<action>
```

Supported actions: `login`, `sync_all`, `sync_delta`, `upload_file`, `health`

### 3.2 REST Endpoints

| Endpoint | Method | Deskripsi |
|---|---|---|
| `POST /api/v1/auth/login` | POST | Login mobile |
| `GET /api/v1/sync/all` | GET | Full sync |
| `POST /api/v1/sync/delta` | POST | Delta sync |
| `POST /api/v1/storage/upload` | POST | File upload |
| `GET /api/v1/health` | GET | Health check |

---

## 4. Authentication

### 4.1 Login

**Request:**
```json
POST /api/v1/auth/login
{
    "username": "guru01",
    "password": "secret123",
    "device_id": "device-abc-123"
}
```

**Response (Success):**
```json
{
    "status": "success",
    "data": {
        "token": "eyJ...",
        "user_id": 42,
        "unit_id": 1,
        "role": "guru",
        "expires_at": "2026-08-30T23:59:59Z"
    }
}
```

**Response (Error):**
```json
{
    "status": "error",
    "message": "Autentikasi gagal."
}
```

### 4.2 Token Usage

Semua request berikutnya menggunakan Bearer token:

```
Authorization: Bearer eyJ...
```

Atau query parameter:
```
?token=eyJ...
```

---

## 5. Sync Endpoints

### 5.1 Full Sync

**Request:**
```
GET /api/v1/sync/all
Authorization: Bearer <token>
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "teachers": [...],
        "subjects": [...],
        "classrooms": [...],
        "schedules": [...],
        "students": [...],
        "versions": {
            "teachers": "v5",
            "subjects": "v3",
            ...
        }
    }
}
```

### 5.2 Delta Sync

**Request:**
```json
POST /api/v1/sync/delta
Authorization: Bearer <token>
{
    "versions": {
        "teachers": "v3",
        "subjects": "v2"
    }
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "changed": {
            "teachers": [...],
            "subjects": []
        },
        "deleted": {
            "teachers": [15]
        },
        "versions": {
            "teachers": "v5",
            "subjects": "v3"
        }
    }
}
```

---

## 6. File Upload

### 6.1 Upload

**Request:**
```
POST /api/v1/storage/upload
Authorization: Bearer <token>
Content-Type: multipart/form-data

file: <binary>
category: GENERAL
```

**Constraints:**
- Max file size: **15MB**
- Supported types: IMAGE, AUDIO, VIDEO, PDF, DOCUMENT

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "uuid": "abc-123-def",
        "file_name": "random_name.jpg",
        "original_name": "photo.jpg",
        "category": "GENERAL",
        "public_url": "http://app.wmvaa.id/uploads/202608/random_name.jpg",
        "created_at": "2026-08-23 10:30:00"
    }
}
```

---

## 7. Health Check

**Request:**
```
GET /api/v1/health
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "service": "WMVAA HUB Universal Sync Gateway",
        "status": "HEALTHY",
        "timestamp": "2026-08-23T10:30:00+00:00",
        "version": "11.0.0"
    }
}
```

---

## 8. Admin Dashboard (`/system/sync`)

### 8.1 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Dashboard** | `GET /system/sync` | Monitoring sync |
| **Bump Version** | `POST /system/sync/bump/:entity` | Force version bump |
| **Revoke Session** | `POST /system/sync/revoke/:id` | Revoke mobile session |

### 8.2 Sync Dashboard Shows

- Active mobile sessions
- Sync version per entity
- Recent sync activity
- Error logs

---

## 9. System Diagnostics (`/system/diagnostics`)

### 9.1 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /system/diagnostics` | Diagnostik dashboard |
| **Purge Sessions** | `POST /system/diagnostics/purge-sessions` | Hapus session expired |
| **API Health** | `GET /api/v1/system/diagnostics` | JSON health report |

### 9.2 Metrics

- PHP version & extensions
- Database connectivity
- Disk space
- Session count
- Error rate
- Response time

---

## 10. Tables

| Table | Fungsi |
|---|---|
| `mobile_sync_sessions` | Sesi mobile aktif |
| `mobile_sync_versions` | Versi sync per entity |
| `mobile_sync_files` | Metadata file upload |

---

## 11. Services

| Service | Fungsi |
|---|---|
| `UniversalSyncService` | Core sync logic |
| `SystemDiagnosticsService` | Diagnostik |
| `SystemIntegrationController` | Admin dashboard |
| `SystemDiagnosticsController` | Diagnostik controller |
