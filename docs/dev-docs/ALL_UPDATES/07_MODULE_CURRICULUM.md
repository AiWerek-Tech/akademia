# WMVAA Akademia — Curriculum Planning Module

## 1. Overview

Modul kurikulum mengelola struktur kurikulum, planning settings, matrix interaktif, dan import/export data kurikulum.

---

## 2. Curriculum Versions (`/curriculum`)

### 2.1 Concept

Kurikulum dikelola dalam **versi** yang terikat ke periode akademik. Setiap versi memiliki workflow status.

### 2.2 Workflow

```
draft → validated → reviewed → approved → locked
                                      ↓
                              (revisi) → draft (new version)
```

### 2.3 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `uuid` | CHAR(36) | UUID v4 |
| `academic_period_id` | BIGINT FK | Periode akademik |
| `code` | VARCHAR(50) | Kode versi |
| `name` | VARCHAR(100) | Nama versi |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `is_active` | TINYINT(1) | Status aktif |

### 2.4 Fitur

| Aksi | Deskripsi |
|---|---|
| **Create** | Buat versi kurikulum baru |
| **View** | Lihat detail versi |
| **Export** | Export struktur ke Excel |
| **Reconciliation** | Rekonstruksi jam efektif |
| **Activate** | Aktifkan versi |
| **Update** | Update metadata |

---

## 3. Curriculum Structures

### 3.1 Concept

Struktur kurikulum = mapping antara **unit × grade_level × subject** dengan jam mengajar per minggu.

### 3.2 Dual JP Model

Sistem mendukung model JP ganda:

| Field | Deskripsi |
|---|---|
| `official_weekly_hours` | JP resmi dari kurikulum nasional |
| `custom_weekly_hours` | JP custom dari sekolah |
| `effective_source` | Sumber jam efektif: `official` atau `custom` |
| `effective_weekly_hours` | Jam efektif yang digunakan untuk perhitungan |
| `adjustment_reason` | Alasan penyesuaian jika custom |

### 3.3 CRUD

| Aksi | Deskripsi |
|---|---|
| **Add Structure** | Tambah mapping unit-grade-subject |
| **Delete Structure** | Hapus mapping |
| **Edit Hours** | Ubah JP mingguan |
| **Toggle Source** | Switch antara official/custom |

---

## 4. Curriculum Planning Settings

### 4.1 Concept

Pengaturan perencanaan kurikulum per versi per unit.

### 4.2 Fields

| Field | Default | Deskripsi |
|---|---|---|
| `teaching_days_per_week` | 5 | Hari mengajar per minggu |
| `selected_day_codes_json` | NULL | JSON hari yang dipilih (MON-FRI) |
| `daily_jp_capacity` | 9.00 | Kapasitas JP per hari |
| `allow_custom_hours` | 1 | Izinkan jam custom |
| `minutes_per_jp` | NULL | Menit per JP |
| `start_time_jp1` | NULL | Jam mulai JP1 |
| `daily_jp_capacities` | NULL | Kapasitas JP per hari (detail) |
| `workload_policy_id` | NULL | Kebijakan beban kerja terkait |
| `notes` | NULL | Catatan |
| `revision_number` | 1 | Nomor revisi |

### 4.3 Unique Constraint

```sql
UNIQUE KEY uq_curriculum_planning_scope (curriculum_version_id, unit_id)
```

---

## 5. Curriculum Matrix (`/curriculum/:id/matrix`)

### 5.1 Concept

Matrix interaktif untuk melihat dan mengedit distribusi JP per minggu:

```
        │ Mapel A │ Mapel B │ Mapel C │ ...
Grade 1 │   4     │   3     │   2     │
Grade 2 │   4     │   3     │   2     │
Grade 3 │   4     │   3     │   2     │
```

### 5.2 Fitur

| Aksi | Deskripsi |
|---|---|
| **View Matrix** | Tampilan grid grade × mapel |
| **Update Cell** | Update JP untuk cell tertentu |
| **Bulk Store** | Update beberapa cell sekaligus |
| **Clone Previous** | Salin dari versi kurikulum sebelumnya |
| **Apply Preset** | Terapkan preset distribusi |
| **Auto Trim** | Otomatis sesuaikan JP agar sesuai kapasitas |

### 5.3 Routes

| Route | Method | Aksi |
|---|---|---|
| `GET /curriculum/:id/matrix` | GET | View matrix |
| `POST /curriculum/:id/matrix/update-cell` | POST | Update satu cell |
| `POST /curriculum/:id/matrix/bulk-store` | POST | Bulk update |
| `POST /curriculum/:id/matrix/clone-previous` | POST | Clone dari sebelumnya |
| `POST /curriculum/:id/matrix/apply-preset` | POST | Apply preset |
| `POST /curriculum/:id/matrix/auto-trim` | POST | Auto trim |

---

## 6. Curriculum Import (`/curriculum/imports`)

### 6.1 Staging Pipeline

```
Download Template → Fill Excel → Upload → Validation → Preview → Apply
```

### 6.2 Routes

| Route | Method | Deskripsi |
|---|---|---|
| `GET /curriculum/imports` | GET | List import batches |
| `GET /curriculum/imports/template` | GET | Download template |
| `POST /curriculum/imports/upload` | POST | Upload file |
| `GET /curriculum/imports/:id` | GET | Show batch detail |
| `POST /curriculum/imports/:id/apply` | POST | Apply batch |

### 6.3 Tables

| Table | Fungsi |
|---|---|
| `curriculum_import_batches` | Batch import |
| `curriculum_import_rows` | Baris data per batch |
| `curriculum_validation_results` | Hasil validasi |

---

## 7. Reconciliation (`/curriculum/:id/reconciliation`)

Fitur untuk merekonstruksi dan memvalidasi distribusi JP agar sesuai dengan kapasitas yang tersedia.

---

## 8. Curriculum Export (`/curriculum/:id/export`)

Export struktur kurikulum ke format Excel untuk dokumentasi atau sharing.

---

## 9. Services

| Service | Fungsi |
|---|---|
| `CurriculumVersionService` | CRUD versi, workflow management |
| `CurriculumStructureService` | CRUD struktur, JP management |
| `CurriculumPlanningService` | Planning settings, day codes |
| `CurriculumImportService` | Import staging pipeline |
| `CurriculumExportService` | Export ke Excel |
| `CurriculumValidationService` | Validasi kurikulum |
| `CurriculumWorkflowService` | Workflow state transitions |
| `CurriculumCoverageService` | Coverage analysis |
| `CurriculumLineageService` | Lineage graph generation |
| `CurriculumEffectiveHoursService` | Hitung jam efektif |
| `CurriculumReconciliationService` | Rekonstruksi distribusi |
| `CurriculumResolutionService` | Resolution of conflicts |
| `CurriculumCapacityReconciliationService` | Kapasitas reconciliation |

---

## 10. Routes Summary

| Route | Method | Controller::Method | Permission |
|---|---|---|---|
| `GET /curriculum` | GET | `CurriculumController::index` | - |
| `GET /curriculum/create` | GET | `CurriculumController::create` | - |
| `POST /curriculum` | POST | `CurriculumController::store` | - |
| `GET /curriculum/:id` | GET | `CurriculumController::show` | - |
| `POST /curriculum/:id/structures` | POST | `CurriculumController::storeStructure` | - |
| `POST /curriculum/:id/planning-settings` | POST | `CurriculumController::savePlanningSettings` | - |
| `POST /curriculum/:id/structures/:sid/delete` | POST | `CurriculumController::deleteStructure` | - |
| `GET /curriculum/:id/reconciliation` | GET | `CurriculumController::reconciliation` | - |
| `GET /curriculum/:id/export` | GET | `CurriculumController::export` | - |
| `POST /curriculum/:id/update` | POST | `CurriculumController::updateVersion` | - |
| `POST /curriculum/:id/activate` | POST | `CurriculumController::activate` | - |
| `GET /curriculum/imports` | GET | `CurriculumImportController::index` | `curriculum.import` |
| `GET /curriculum/imports/template` | GET | `CurriculumImportController::template` | `curriculum.import` |
| `POST /curriculum/imports/upload` | POST | `CurriculumImportController::upload` | `curriculum.import` |
| `GET /curriculum/imports/:id` | GET | `CurriculumImportController::showBatch` | `curriculum.import` |
| `POST /curriculum/imports/:id/apply` | POST | `CurriculumImportController::apply` | `curriculum.import` |
