# WMVAA Akademia — IALOS Education Control Center

## 1. Overview

IALOS Education (Integrated Academic Learning Operating System) adalah **bounded context** di dalam WMVAA Akademia yang mengelola aspek kurikulum tingkat lanjut: Capaian Pembelajaran (CP), Elemen, Tujuan Pembelajaran (TP), Alur Tujuan Pembelajaran (ATP), Coverage, Paket Pembelajaran, dan KSP Digital.

### 1.1 Access Point

- **Route**: `GET /education`
- **Controller**: `EducationFoundationController::dashboard()`
- **Sidebar**: Menu "IALOS Education" dengan sub-menu terpisah

---

## 2. Control Center Dashboard (`/education`)

Dashboard terpadu untuk semua fitur IALOS Education:

| Widget | Deskripsi |
|---|---|
| **Overview Cards** | Ringkasan regulasi, sumber kurikulum, profil lulusan |
| **Quick Navigation** | Link ke setiap sub-modul |
| **Status Indicators** | Status setiap komponen |

---

## 3. Digital KSP (`/education/ksp`)

### 3.1 Concept

KSP (Komite Sekolah Pendidikan) Digital mengelola dokumen-dokumen terkait evaluasi dan penjaminan mutu pendidikan.

### 3.2 Workflow

```
draft → in_review → approved → locked
```

### 3.3 Sections

| Section | Route | Deskripsi |
|---|---|---|
| **Context** | `/education/ksp/:id/context` | Konteks sekolah |
| **Vision & Goals** | `/education/ksp/:id/vision-goals` | Visi dan tujuan |
| **Organization** | `/education/ksp/:id/organization` | Struktur organisasi |
| **Evaluation** | `/education/ksp/:id/evaluation` | Evaluasi |
| **Compliance** | `/education/ksp/:id/compliance` | Kepatuhan regulasi |
| **Evidence** | `/education/ksp/:id/evidence` | Bukti-bukti |
| **Documents** | `/education/ksp/:id/documents` | Generate & download dokumen |

### 3.4 Features

| Aksi | Deskripsi |
|---|---|
| **Create KSP** | Buat dokumen KSP baru |
| **Update Section** | Edit per bagian |
| **Workflow** | Validate → Review → Approve → Lock |
| **Run Compliance** | Jalankan pengecekan kepatuhan |
| **Store Evidence** | Upload bukti pendukung |
| **Download Evidence** | Download file bukti |
| **Generate Document** | Generate dokumen PDF/Word |
| **Download Document** | Download dokumen yang sudah di-generate |

### 3.5 Improvement Actions

- Tambah action per evaluasi
- Update status action
- Track progress improvement

### 3.6 Tables

| Table | Fungsi |
|---|---|
| `digital_ksp_documents` | Dokumen KSP |
| `ksp_sections` | Bagian dokumen |
| `ksp_context` | Konteks sekolah |
| `ksp_vision_goals` | Visi dan tujuan |
| `ksp_organization` | Struktur organisasi |
| `ksp_evaluations` | Evaluasi |
| `ksp_improvement_actions` | Tindakan perbaikan |
| `ksp_compliance_results` | Hasil kepatuhan |
| `ksp_evidences` | Bukti pendukung |
| `ksp_documents` | Dokumen yang di-generate |

---

## 4. Regulations (`/references/regulations`)

### 4.1 Deskripsi

Regulasi pendidikan yang menjadi dasar kurikulum dan operasional sekolah.

### 4.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar regulasi |
| **Create** | Tambah regulasi baru |
| **Store** | Simpan regulasi |

### 4.3 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `title` | VARCHAR(255) | Judul regulasi |
| `document_number` | VARCHAR(100) | Nomor dokumen |
| `description` | TEXT | Deskripsi |
| `category` | VARCHAR(50) | Kategori |

---

## 5. Curriculum Sources (`/references/curriculum-sources`)

### 5.1 Deskripsi

Sumber-sumber kurikulum yang digunakan sekolah (buku, platform, dll).

### 5.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar sumber |
| **Create** | Tambah sumber baru |
| **Store** | Simpan sumber |

---

## 6. Graduate Profile (`/references/graduate-profile`)

### 6.1 Deskripsi

Profil lulusan yang menjadi target pencapaian pendidikan.

### 6.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Dimensi profil lulusan |
| **Dimensions** | Kelola dimensi |

---

## 7. Learning Outcomes (`/curriculum/outcomes`)

### 7.1 Concept

Capaian Pembelajaran (CP) = target kompetensi siswa di akhir jenjang per mapel.

### 7.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `uuid` | CHAR(36) | UUID |
| `code` | VARCHAR(50) | Kode CP |
| `description` | TEXT | Deskripsi CP |
| `grade_level_id` | BIGINT FK | Tingkat |
| `subject_id` | BIGINT FK | Mapel |

### 7.3 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar CP per mapel |
| **Create** | Tambah CP baru |
| **Update** | Edit CP |
| **Add Element** | Tambah elemen ke CP |

### 7.4 Related: Curriculum Elements

Elemen-elemen yang membentuk satu CP.

---

## 8. Learning Objectives (`/curriculum/objectives`)

### 8.1 Concept

Tujuan Pembelajaran (TP) = kompetensi spesifik yang harus dicapai siswa.

### 8.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `uuid` | CHAR(36) | UUID |
| `code` | VARCHAR(50) | Kode TP |
| `description` | TEXT | Deskripsi TP |
| `learning_outcome_id` | BIGINT FK | CP induk |
| `subject_id` | BIGINT FK | Mapel |

### 8.3 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar TP |
| **Create** | Tambah TP baru |
| **Update** | Edit TP |
| **Adapt** | Adaptasi TP ke konteks sekolah |
| **Add Criterion** | Tambah kriteria penilaian |

---

## 9. Learning Sequences / ATP (`/curriculum/sequences`)

### 9.1 Concept

Alur Tujuan Pembelajaran (ATP) = urutan belajar yang menghubungkan TP dalam konteks mapel dan kelas.

### 9.2 Workflow

```
draft → validated → reviewed → approved → locked
```

### 9.3 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar ATP |
| **Create** | Tambah ATP baru |
| **Add Item** | Tambah item ke ATP |
| **Transition** | Perubahan status workflow |
| **Clone** | Salin ATP |

### 9.4 Items

Setiap ATP memiliki item-item urutan belajar yang terhubung ke TP.

---

## 10. Coverage (`/curriculum/coverage`)

### 10.1 Concept

Coverage kurikulum = analisis seberapa lengkap ATP sudah mencakup semua TP.

### 10.2 Features

| Aksi | Deskripsi |
|---|---|
| **View Coverage** | Lihat matriks ATP × TP |
| **Gap Analysis** | Identifikasi TP yang belum tercakup |

---

## 11. Subject Learning Packs (`/curriculum/learning-packs`)

### 11.1 Concept

Paket Pembelajaran = unit terstruktur yang menggabungkan materi, aktivitas, resources, dan asesmen untuk satu mapel di satu kelas.

### 11.2 Workflow

```
draft → validated → reviewed → approved → locked
```

### 11.3 Components

| Component | Deskripsi |
|---|---|
| **Units** | Unit-unit pembelajaran |
| **Concepts** | Konsep-konsep yang diajarkan |
| **Activities** | Aktivitas pembelajaran |
| **Resources** | Materi dan sumber belajar |
| **Assessment** | Asesmen dalam paket |
| **Follow-up** | Tindak lanjut |
| **Coverage** | Coverage terhadap ATP |
| **Lineage** | Hubungan lineage dengan CP/TP |
| **History** | Riwayat perubahan |

### 11.4 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Detail paket pembelajaran |
| **Clone** | Salin paket |
| **Units CRUD** | Kelola unit pembelajaran |
| **Concepts CRUD** | Kelola konsep |
| **Activities CRUD** | Kelola aktivitas (dengan resources, alternatives, experiences) |
| **Resources CRUD** | Kelola resources |
| **Assessment CRUD** | Kelola asesmen |
| **Followup CRUD** | Kelola tindak lanjut |
| **Lineage** | Lihat hubungan lineage |
| **Import** | Import dari paket lain |
| **History** | Riwayat perubahan |
| **Transition** | Workflow transitions |

### 11.5 Activities Detail

Setiap aktivitas memiliki:
- **Resources**: File/materi pendukung
- **Alternatives**: Aktivitas alternatif
- **Experiences**: Pengalaman belajar

### 11.6 Tables

| Table | Fungsi |
|---|---|
| `subject_learning_packs` | Paket pembelajaran |
| `learning_session` (units) | Unit pembelajaran |
| `learning_session_activity` | Aktivitas |
| `activity_resource` | Resources aktivitas |
| `assessment_item` | Asesmen |
| `learning_session_observation` | Observasi |
| `learning_session_reflection` | Refleksi |

---

## 12. Education Imports (`/curriculum/education-imports`)

Import data pendidikan (CP, TP, ATP) dari Excel.

### 12.1 Pipeline

```
Upload → Validation → Preview → Apply
```

---

## 13. Services

| Service | Fungsi |
|---|---|
| `EducationFoundationService` | Core IALOS service |
| `EducationFoundationImportService` | Import pipeline |
| `EducationControlCenterService` | Dashboard aggregation |
| `RegulationRegistryService` | Regulasi management |
| `DigitalKspService` | KSP CRUD |
| `KspComplianceService` | Compliance checking |
| `KspEvidenceService` | Evidence management |
| `KspDocumentGeneratorService` | Document generation |
| `LearningOutcomeService` | CP CRUD |
| `LearningObjectiveService` | TP CRUD, adaptation |
| `LearningSequenceService` | ATP CRUD |
| `LearningPackService` | Paket pembelajaran CRUD |
| `SubjectLearningPackEngineService` | Pack engine |
| `LearningPackImportService` | Import packs |
| `CurriculumCoverageService` | Coverage analysis |
| `CurriculumLineageService` | Lineage graph |
