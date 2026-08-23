# WMVAA Akademia — Security Framework & RBAC

## 1. Security Architecture Overview

Aplikasi menerapkan keamanan berlapis (defense-in-depth):

```
┌─────────────────────────────────────────┐
│  Layer 1: HTTPS / CSP Headers          │
├─────────────────────────────────────────┤
│  Layer 2: Session-based Authentication │
├─────────────────────────────────────────┤
│  Layer 3: CSRF Token Verification      │
├─────────────────────────────────────────┤
│  Layer 4: Role-Based Access Control    │
├─────────────────────────────────────────┤
│  Layer 5: Unit Scope Isolation         │
├─────────────────────────────────────────┤
│  Layer 6: Input Validation & Escaping  │
├─────────────────────────────────────────┤
│  Layer 7: SQL Injection Prevention     │
├─────────────────────────────────────────┤
│  Layer 8: Audit Trail Logging          │
└─────────────────────────────────────────┘
```

---

## 2. Authentication System

### 2.1 Login Flow

```
User submits username + password
    ↓
AuthController::attemptLogin()
    ↓
Password verification (password_verify() / bcrypt)
    ↓
Login attempt logging (login_attempts table)
    ↓
Rate limiting (max attempts per IP/user)
    ↓
Session creation with rotation
    ↓
Session data populated:
  - user_id, username, full_name
  - role_code, role_name, all_role_codes
  - active_unit_id, active_period_id
  - permissions (array)
  - auth_timestamp
    ↓
Redirect to dashboard
```

### 2.2 Session Security

| Aspek | Implementasi |
|---|---|
| **Session Driver** | Database-backed CI4 sessions |
| **Session Rotation** | Otomatis pada login |
| **Password Hash** | bcrypt via `password_hash()` |
| **Password Rotation Detection** | `AuthFilter` membandingkan `auth_timestamp` dengan `password_changed_at` |
| **Account Lockout** | Rate limiting pada `login_attempts` |
| **Inactive Session** | Destroyed jika user tidak aktif |
| **Session Timeout** | Dikontrol oleh CI4 session expiration |

### 2.3 AuthFilter (`app/Filters/AuthFilter.php`)

Filter ini berjalan pada setiap request terproteksi:

1. Memeriksa `session('logged_in')` — redirect ke `/login` jika false
2. Query `users` table untuk memverifikasi:
   - `is_active = 1`
   - `deleted_at IS NULL`
3. Mendeteksi **password rotation**: membandingkan `auth_timestamp` dengan `password_changed_at`
4. Mengatur flag `must_change_password` dan `must_change_username` di session

### 2.4 Password Change

```
GET /change-password → Form ganti password
POST /change-password → AuthController::attemptChangePassword()
    ↓
Validasi password lama
    ↓
Hash password baru (bcrypt)
    ↓
Update password_changed_at timestamp
    ↓
Invalidasi semua session lain
    ↓
Redirect ke dashboard
```

---

## 3. Role-Based Access Control (RBAC)

### 3.1 Role System

Aplikasi menggunakan **multi-role** system — satu user dapat memiliki beberapa role.

#### Default Roles

| Role Code | Nama | Deskripsi |
|---|---|---|
| `super_admin` / `superadmin` | Super Administrator | Akses penuh ke semua fitur |
| `admin_smp` | Admin SMP | Administrator unit SMP |
| `admin_sma` | Admin SMA | Administrator unit SMA |
| `wakasek_kurikulum` | Wakasek Kurikulum | Pengelola kurikulum |
| `kepala_sekolah` | Kepala Sekolah | Akses monitoring eksekutif |
| `viewer_yayasan` | Viewer Yayasan | Akses baca untuk yayasan |
| `tata_usaha` | Tata Usaha | Administrasi operasional |
| `guru` | Guru | Portal personal guru |
| `wali_kelas` | Wali Kelas | Guru + akses kelas binaan |
| `siswa` | Siswa | Portal peserta didik |

### 3.2 Permission System

Permissions menggunakan pola dot-notation: `module.action`

#### Permission Groups

| Module | Permissions |
|---|---|
| **Dashboard** | `dashboard.view` |
| **Academic Calendar** | `academic_calendar.view`, `academic_calendar.manage` |
| **Assignments** | `assignments.view`, `assignments.manage`, `assignments.validate`, `assignments.review`, `assignments.approve`, `assignments.lock`, `assignments.import`, `assignments.export`, `assignments.revise` |
| **Workloads** | `workloads.view`, `workloads.manage`, `workloads.recalculate`, `workloads.export` |
| **Duties** | `duties.view`, `duties.manage` |
| **Schedules** | `schedules.view`, `schedules.manage`, `schedules.generate`, `schedules.validate`, `schedules.export`, `schedules.import` |
| **Students** | `students.view`, `students.manage` |
| **Classrooms** | `classrooms.view`, `classrooms.manage` |
| **Electives** | `electives.view`, `electives.manage`, `class_electives.manage`, `electives.selection.submit` |
| **Attendances** | `attendances.view`, `attendances.record`, `attendances.admin` |
| **Curriculum** | `curriculum.view`, `curriculum.import` |
| **Learning Outcomes** | `learning_outcomes.view`, `learning_outcomes.manage` |
| **Learning Objectives** | `learning_objectives.view`, `learning_objectives.manage` |
| **Learning Sequences** | `learning_sequences.view`, `learning_sequences.validate`, `learning_sequences.review`, `learning_sequences.approve`, `learning_sequences.lock`, `learning_sequences.manage` |
| **Learning Packs** | `learning_packs.view`, `learning_packs.manage`, `learning_packs.clone` |
| **Learning Units** | `learning_units.manage` |
| **Learning Activities** | `learning_activities.manage` |
| **Learning Resources** | `learning_resources.manage` |
| **Learning Guidance** | `learning_guidance.manage` |
| **Lesson Plans** | `lesson_plans.view`, `lesson_plans.manage`, `lesson_plans.clone` |
| **Teaching** | `teaching.workspace`, `teaching.teach`, `teaching.reflect` |
| **Assessment** | `assessment.view`, `assessment.manage`, `assessment.mastery` |
| **Cocurricular** | `cocurricular.view`, `cocurricular.manage` |
| **Extracurricular** | `extracurricular.view`, `extracurricular.manage` |
| **Reporting** | `reporting.view`, `reporting.manage` |
| **Teacher Reflection** | `teacher_reflection.view`, `teacher_reflection.manage` |
| **Supervision** | `supervision.view`, `supervision.manage` |
| **KSP Evaluation** | `ksp_evaluation.view` |
| **KSP** | `ksp.view`, `ksp.manage`, `ksp.review`, `ksp.approve`, `ksp.lock`, `ksp.export` |
| **Regulations** | `regulations.view`, `regulations.manage` |
| **Curriculum Sources** | `curriculum_sources.view`, `curriculum_sources.manage` |
| **Graduate Profile** | `graduate_profile.view` |
| **Settings** | `settings.view`, `settings.manage` |
| **Users** | `users.view` |
| **Roles** | `roles.view` |
| **Audit** | `audit.view` |
| **Sync** | `sync.view`, `sync.manage` |
| **Availability** | `availability.view`, `availability.manage` |
| **Constraints** | `constraints.manage` |
| **Class Schedule** | `class_schedule.view` |
| **Class Students** | `class_students.view` |
| **Teacher Portal** | `teacher_portal.view` |
| **Teacher Schedule** | `teacher_schedule.view` |
| **Teacher Workload** | `teacher_workload.view` |
| **Teacher Assignment Document** | `teacher_assignment_document.view` |
| **Teacher Duty Schedule** | `teacher_duty_schedule.view` |
| **Teacher Electives** | `teacher_electives.view` |
| **Teacher Attendance** | `teacher_attendance.view` |
| **Mobile Access** | `api.mobile_access` |

### 3.3 Predefined Role Permission Sets

#### Guru Role (`RolePermissions::GURU`)

```php
[
    'dashboard.view', 'teacher_portal.view', 'teacher_assignment_document.view',
    'teacher_attendance.view', 'teacher_duty_schedule.view', 'teacher_electives.view',
    'teacher_schedule.view', 'teacher_workload.view', 'attendances.record',
    'academic_calendar.view',
    // Phase 3
    'learning_packs.clone', 'learning_units.manage', 'learning_activities.manage',
    'learning_resources.manage', 'learning_guidance.manage',
    // Phase 5
    'teaching.workspace', 'teaching.teach', 'teaching.reflect',
    // Phase 6
    'assessment.view', 'assessment.manage',
    // Phase 7
    'cocurricular.view', 'cocurricular.manage',
    // Phase 8
    'extracurricular.view', 'extracurricular.manage',
    // Phase 9
    'reporting.view',
    // Phase 10
    'teacher_reflection.view', 'teacher_reflection.manage', 'supervision.view',
]
```

#### Wali Kelas Role (`RolePermissions::WALI_KELAS`)

Sama dengan Guru + ditambah:

```php
[
    'class_students.view', 'class_schedule.view', 'class_electives.manage',
    'reporting.manage', // Lebih dari guru
]
```

### 3.4 Administrative Permissions (Never Granted to Guru/Wali Kelas)

```php
[
    'curriculum.import', 'assignments.view', 'duties.view',
    'workloads.view', 'availability.view', 'schedules.view',
    'students.view', 'classrooms.view', 'electives.view',
    'attendances.view', 'attendances.admin',
    'sync.view', 'sync.manage', 'api.mobile_access',
]
```

### 3.5 Permission Filter Execution

```
Route defined with filter: ['filter' => 'permission:code1,code2']
    ↓
PermissionFilter::before()
    ↓
UserModel::getPermissions(userId, unitId)
    ↓
Returns flat array of all permission codes for user
(includes: direct role grants + personal role grants)
    ↓
Checks if user has ANY of the requested permissions (OR logic)
    ↓
403 Error if not authorized
```

---

## 4. Unit Scope Isolation

### 4.1 Multi-Unit Access Control

```
User ──┬── user_unit_access (unit A, unit B, unit C)
       │
       └── session.active_unit_id = unit A
              ↓
         All queries filtered by unit A
```

### 4.2 UnitScopeService

- `accessibleUnitIds(userId)` — returns array of accessible unit IDs
- Super admin: all active units
- Regular user: only units in `user_unit_access`

### 4.3 UnitAccessFilter

1. Verifikasi `active_unit_id` ada di session
2. Cek apakah unit tersebut di accessible list
3. Jika tidak valid: redirect ke dashboard atau destroy session

### 4.4 PortalUnitScopeService

- Digunakan di dashboard dan portal views
- Mendukung filter "Semua Unit" atau unit spesifik
- Query parameter `unit_scope=all|unit_id`

---

## 5. CSRF Protection

- Semua POST/PUT/DELETE requests memerlukan CSRF token
- Template: `<?= csrf_field() ?>` di setiap form
- AJAX requests: sertakan header `X-CSRF-TOKEN`

---

## 6. XSS Prevention

- Semua output di-escape menggunakan `esc()`:
  ```php
  <?= esc($variable) ?>                    // HTML escaping
  <?= esc($variable, 'attr') ?>            // Attribute escaping
  <?= esc($variable, 'js') ?>              // JavaScript escaping
  <?= esc($url, 'attr') ?>                 // URL attribute escaping
  ```
- Content Security Policy (CSP) dapat diaktifkan via `app.CSPEnabled = true`
- Meta tag: `<meta name="application-name">`, `<meta name="version">`

---

## 7. SQL Injection Prevention

- Semua database query menggunakan **CI4 Query Builder** atau **prepared statements**
- Tidak ada raw SQL concatenation di controllers atau views
- Parameter binding di Query Builder:
  ```php
  $db->table('users')->where('id', $userId)->get();
  ```

---

## 8. Input Validation

### 8.1 Route-level Validation

```php
$routes->get('(:num)', 'Controller::show/$1');  // Only numeric segments
$routes->get('(:segment)', 'Controller::show/$1');  // Only alphanumeric segments
```

### 8.2 Controller-level Validation

Controllers menggunakan CI4 Validation:
```php
$rules = [
    'full_name' => 'required|max_length[100]',
    'email' => 'required|valid_email|is_unique[users.email]',
];
if (!$this->validate($rules)) { /* handle errors */ }
```

---

## 9. Security Headers

### 9.1 Production Headers

| Header | Value |
|---|---|
| `Content-Security-Policy` | Configurable via `app.CSPEnabled` |
| `Strict-Transport-Security` | HSTS when `app.forceGlobalSecureRequests = true` |
| `X-Content-Type-Options` | nosniff (CI4 default) |
| `X-Frame-Options` | DENY (CI4 default) |

### 9.2 Cookie Security

```ini
cookie.secure = true          # HTTPS only
cookie.httponly = true         # No JavaScript access
cookie.samesite = Lax          # CSRF protection
```

---

## 10. Audit Trail System

### 10.1 AuditService

Semua perubahan signifikan dicatat:

```php
AuditService::log(
    'module',          // e.g., 'teachers'
    'action',          // e.g., 'update'
    'EntityType',      // e.g., 'Teacher'
    $entityId,         // ID entitas
    $beforeState,      // JSON state sebelum
    $afterState,       // JSON state sesudah
    'reason text'      // Alasan perubahan
);
```

### 10.2 Audit Log Fields

| Field | Keterangan |
|---|---|
| `user_id` | ID pengguna yang melakukan aksi |
| `username` | Username |
| `module` | Modul yang terpengaruh |
| `action` | Jenis aksi |
| `entity_type` | Tipe entitas |
| `entity_id` | ID entitas |
| `before_state` | State sebelum (JSON) |
| `after_state` | State sesudah (JSON) |
| `reason` | Alasan/catatan |
| `ip_address` | IP address client |
| `created_at` | Timestamp |

---

## 11. Rate Limiting

### 11.1 Login Rate Limiting

- Tracking di tabel `login_attempts`
- Max attempts per IP/user sebelum lockout
- Progressive delay

---

## 12. Mobile API Security

### 12.1 Token-based Authentication

```
Login → POST /api/v1/auth/login { username, password, device_id }
    ↓
Returns: { token, user_id, unit_id, role, expires_at }
    ↓
Subsequent requests: Header "Authorization: Bearer {token}"
    ↓
Token validation di MobileSyncController::authenticateRequest()
    ↓
Touch session timestamp on each valid request
```

### 12.2 API Security Features

| Feature | Implementasi |
|---|---|
| **Authentication** | Bearer token dari login |
| **Token Validation** | `UniversalSyncService::validateToken()` |
| **Session Touch** | Update timestamp setiap request valid |
| **Device Binding** | `device_id` disimpan di session |
| **IP Tracking** | IP address dicatat saat login |
| **JSON-only** | Semua response berformat JSON |
| **File Upload Limit** | Max 15MB per file |

---

## 13. Password Policies

| Policy | Nilai |
|---|---|
| **Hash Algorithm** | bcrypt |
| **Min Length** | Configurable |
| **Force Change** | Flag `must_change_password` |
| **Rotation Detection** | `password_changed_at` vs `auth_timestamp` |

---

## 14. Data Isolation

### 14.1 Per-Unit Data Isolation

Setiap tabel operasional memiliki kolom `unit_id` yang memastikan:

- Guru di SMP tidak dapat melihat data guru SMA (kecuali super admin)
- Jadwal SMP terpisah dari jadwal SMA
- Rombel, kelas, dan siswa terisolasi per unit

### 14.2 Cross-Unit Access

- Hanya **super_admin** dan **admin** yang dapat mengakses lintas unit
- Portal views menggunakan `unit_scope` parameter untuk filtering
- `PortalUnitScopeService` mengelola scope untuk dashboard dan portal

---

## 15. Known Security Considerations

1. **CSP Mode**: Saat ini `CSPEnabled = false`. Untuk production, harus diaktifkan dan diuji
2. **HTTPS Enforcement**: `forceGlobalSecureRequests` harus `true` di production
3. **Session Driver**: Database-backed (lebih aman dari file-based)
4. **Password Storage**: bcrypt (recommended)
5. **Upload Security**: File uploads dikontrol via `MasterImportService` dan MobileSyncController
