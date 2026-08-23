# WMVAA Akademia — Settings & System Management

## 1. Overview

Modul pengaturan mengelola profil sekolah, tampilan, database, pengaturan aplikasi, dan manajemen pengguna.

---

## 2. School Profile (`/settings/school-profile`)

### 2.1 Fields

| Field | Deskripsi |
|---|---|
| `app_name` | Nama aplikasi (default: IALOS Education) |
| `app_tagline` | Tagline (default: ACADEMIC SUITE) |
| `school_address` | Alamat sekolah |
| `school_phone` | Telepon sekolah |
| `school_email` | Email sekolah |
| `school_website` | Website sekolah |
| `school_logo` | Logo sekolah |

### 2.2 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/school-profile` | Form profil | `settings.view` |
| **Save** | `POST /settings/school-profile` | Simpan profil | `settings.manage` |

---

## 3. System Appearance (`/settings/appearance`)

### 3.1 Settings Groups

#### Appearance Settings

| Setting | Default | Deskripsi |
|---|---|---|
| `primary_color` | `#6366f1` | Warna tema utama |
| `font_family` | `Inter` | Font family |
| `font_size` | `14` | Font size (px) |
| `theme` | `light` | Theme mode (light/dark) |

#### Email Settings

Konfigurasi email untuk notifikasi.

#### Security Settings

Pengaturan keamanan (CSP, HSTS, cookie).

### 3.2 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/appearance` | Form tampilan | `settings.view` |
| **Save Appearance** | `POST /settings/appearance/save` | Simpan tampilan | `settings.manage` |
| **Save Email** | `POST /settings/appearance/save-email` | Simpan email | `settings.manage` |
| **Save Security** | `POST /settings/appearance/save-security` | Simpan security | `settings.manage` |

---

## 4. Application Settings (`/settings/application`)

### 4.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/application` | Form pengaturan | `settings.view` |
| **Save** | `POST /settings/application/save` | Simpan pengaturan | `settings.manage` |
| **Save Maintenance** | `POST /settings/application/save-maintenance` | Mode maintenance | `settings.manage` |
| **Save Registration** | `POST /settings/application/save-registration` | Pengaturan registrasi | `settings.manage` |

### 4.2 Features

- Maintenance mode toggle
- Registration settings
- Application-level configuration

---

## 5. Academic Operations Settings (`/settings/academic-operations`)

### 5.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/academic-operations` | Form operasional | `academic_calendar.manage` |
| **Save** | `POST /settings/academic-operations` | Simpan pengaturan | `academic_calendar.manage` |

### 5.2 Related

- Mengontrol behavior kalender pendidikan
- Mengatur aturan operasional akademik

---

## 6. Attendance Settings (`/settings/attendance`)

### 6.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/attendance` | Form absensi | `attendances.admin` |
| **Save** | `POST /settings/attendance` | Simpan pengaturan | `attendances.admin` |

---

## 7. Database Manager (`/settings/database`)

### 7.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /settings/database` | Dashboard database | `settings.manage` |
| **Export** | `GET /settings/database/:table/export` | Export tabel | `settings.manage` |
| **Truncate** | `POST /settings/database/:table/truncate` | Kosongkan tabel | `settings.manage` |
| **Detail** | `GET /settings/database/:table/detail` | Detail tabel | `settings.manage` |
| **Backup** | `POST /settings/database/save-backup` | Backup database | `settings.manage` |

### 7.2 Database Tables Overview

Menampilkan:
- Daftar semua tabel
- Jumlah record per tabel
- Ukuran tabel
- Status (active/inactive)

### 7.3 Export

Export tabel ke format SQL atau CSV.

### 7.4 Truncate

Mengosongkan isi tabel (destructive action, memerlukan konfirmasi).

---

## 8. User Management (`/users`)

### 8.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **Index** | `GET /users` | Daftar pengguna | `users.view` |
| **Create** | `GET /users/create` | Form tambah user | `users.view` |
| **Store** | `POST /users` | Simpan user baru | `users.view` |
| **Provision Teachers** | `POST /users/provision-teachers` | Buat akun dari data guru | `users.view` |
| **Edit** | `GET /users/:id/edit` | Form edit | `users.view` |
| **Update** | `POST /users/:id` | Simpan perubahan | `users.view` |
| **Reset Password** | `POST /users/:id/reset-password` | Reset password | `users.view` |

### 8.2 Provision Teachers

Fitur untuk membuat akun user secara massal dari data guru yang sudah ada.

---

## 9. Role & Permission Management (`/roles`)

### 9.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **Index** | `GET /roles` | Daftar role | `roles.view` |
| **Permissions** | `GET /roles/:id/permissions` | Lihat permission | `roles.view` |
| **Update Permissions** | `POST /roles/:id/permissions` | Update permission | `roles.view` |

### 9.2 Permission Matrix

Permission dikelola per role. Setiap role memiliki daftar permission yang diizinkan.

---

## 10. Academic Calendar Settings

### 10.1 Kalender Pendidikan (`/academic-calendar`)

#### Concept

Kalender pendidikan = jadwal hari efektif, hari libur, dan event sekolah per tahun akademik.

#### Rule Engine

Sistem rule engine untuk generate kalender otomatis:

| Rule Type | Deskripsi |
|---|---|
| **Weekly Pattern** | Pola hari aktif per minggu |
| **Holiday Rules** | Aturan hari libur |
| **Special Events** | Event khusus |
| **Custom Overrides** | Override manual |

#### Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /academic-calendar` | Daftar kalender |
| **Generate** | `POST /academic-calendar/generate` | Generate kalender |
| **Preview** | `POST /academic-calendar/preview` | Preview sebelum generate |
| **Editor** | `GET /academic-calendar/:id/editor` | Editor kalender |
| **Update Day** | `POST /academic-calendar/:id/update-day` | Update hari |
| **Reset Day** | `POST /academic-calendar/:id/reset-day` | Reset hari |
| **Events Store** | `POST /academic-calendar/:id/events/store` | Tambah event |
| **Events Update** | `POST /academic-calendar/:id/events/:eid/update` | Update event |
| **Events Delete** | `POST /academic-calendar/:id/events/:eid/delete` | Hapus event |
| **Rules Store** | `POST /academic-calendar/:id/rules/store` | Tambah rule |
| **Rules Update** | `POST /academic-calendar/:id/rules/:rid/update` | Update rule |
| **Rules Delete** | `POST /academic-calendar/:id/rules/:rid/delete` | Hapus rule |
| **Print** | `GET /academic-calendar/:id/print` | Cetak kalender |
| **Activate** | `POST /academic-calendar/:id/activate` | Aktifkan kalender |
| **Rebuild** | `POST /academic-calendar/:id/rebuild` | Rebuild kalender |

---

## 11. System Settings Storage

Semua pengaturan disimpan di tabel `system_settings`:

| Column | Deskripsi |
|---|---|
| `group_key` | Grup pengaturan (appearance, email, security, app) |
| `setting_key` | Kunci pengaturan |
| `setting_value` | Nilai |

Pengaturan di-cache di session untuk performa.

---

## 12. Services

| Service | Fungsi |
|---|---|
| `SettingsService` | CRUD settings |
| `AuditService` | Audit trail |
| `FeatureFlagService` | Feature flag management |
| `AcademicCalendarGeneratorService` | Generate kalender |
| `AcademicOperatingSettingsService` | Operasional settings |
| `AttendanceService` | Attendance settings |
| `SystemDiagnosticsService` | Diagnostik |
