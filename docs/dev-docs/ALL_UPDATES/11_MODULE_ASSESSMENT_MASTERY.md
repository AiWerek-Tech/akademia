# WMVAA Akademia — Assessment & Mastery Module

## 1. Overview

Modul penilaian mengelola asesmen, gradebook, mastery tracking, intervensi belajar, pengolahan sumatif, dan draf narasi rapor.

---

## 2. Assessment Management (`/assessment`)

### 2.1 Concept

Asesmen = instrumen penilaian yang terhubung dengan tujuan pembelajaran (TP).

### 2.2 Workflow

```
draft → validated → reviewed → approved → locked
```

### 2.3 Fields

| Field | Deskripsi |
|---|---|
| `title` | Judul asesmen |
| `type` | Tipe (formatif/sumatif) |
| `subject_id` | Mapel terkait |
| `classroom_id` | Rombel |
| `workflow_status` | Status workflow |

### 2.4 Features

| Aksi | Deskripsi |
|---|---|
| **Create** | Buat asesmen baru |
| **View** | Detail asesmen |
| **Edit** | Edit asesmen |
| **Transition** | Workflow transitions |
| **Delete** | Hapus asesmen |
| **Gradebook** | Input/Edit nilai siswa |
| **Add Evidence** | Upload bukti asesmen |
| **Add Feedback** | Berikan feedback siswa |

### 2.5 Gradebook

Gradebook memungkinkan guru menginput nilai untuk setiap siswa:

| Feature | Deskripsi |
|---|---|
| **Input Grades** | Input nilai per siswa per kriteria |
| **Bulk Save** | Simpan semua nilai sekaligus |
| **Evidence** | Lampirkan bukti penilaian |
| **Feedback** | Berikan feedback individual |

---

## 3. Mastery Tracking (`/mastery`)

### 3.1 Concept

Mastery = pencapaian siswa terhadap tujuan pembelajaran (TP). Sistem tracking otomatis menghitung level mastery berdasarkan asesmen.

### 3.2 Mastery Levels

| Level | Deskripsi |
|---|---|
| **Belum Mencapai** | TP belum tercapai |
| **Mulai Berkembang** | Mulai menunjukkan kemajuan |
| **Berkembang** | Berkembang sesuai harapan |
| **Mencapai** | Mencapai target |
| **Mencapai dengan Baik** | Melebihi target |

### 3.3 Features

| Aksi | Deskripsi |
|---|---|
| **View Mastery** | Lihat mastery per siswa per TP |
| **Set Mastery** | Set manual mastery level |
| **Recommend Interventions** | Rekomendasikan intervensi berdasarkan mastery |
| **Heatmap** | Visualisasi heatmap mastery |

### 3.4 Mastery Heatmap (`/smart/mastery-heatmap`)

Visualisasi heatmap yang menampilkan:
- **X-axis**: Tujuan Pembelajaran (TP)
- **Y-axis**: Siswa
- **Color**: Level mastery (merah → hijau)

---

## 4. Interventions (`/interventions`)

### 4.1 Concept

Intervensi belajar = langkah remedial untuk siswa yang belum mencapai mastery.

### 4.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar intervensi |
| **Create** | Buat intervensi baru |
| **Update** | Update status intervensi |
| **Bulk Status** | Update status massal |

### 4.3 Fields

| Field | Deskripsi |
|---|---|
| `student_id` | Siswa |
| `target_objective_id` | TP target |
| `intervention_type` | Tipe intervensi |
| `description` | Deskripsi |
| `status` | Status (active/completed/cancelled) |

---

## 5. Reporting Policies (`/reporting-policies`)

### 5.1 Concept

Kebijakan pelaporan mengatur bagaimana mastery dikonversi menjadi nilai rapor.

### 5.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Kebijakan aktif |
| **Save** | Simpan kebijakan baru |

---

## 6. Summative Processing (`/summative`)

### 6.1 Concept

Pengolahan sumatif = proses menghitung nilai akhir dari beberapa asesmen formatif/sumatif.

### 6.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /summative` | Daftar hasil sumatif |
| **Detail** | `GET /summative/:id` | Detail hasil |
| **Process** | `POST /summative/process` | Jalankan pengolahan |
| **Validate** | `POST /summative/:id/validate` | Validasi hasil |
| **Reopen** | `POST /summative/:id/reopen` | Buka kembali |

---

## 7. Narrative Drafter (`/smart/narrative-drafter`)

### 7.1 Concept

Draf Narasi Rapor = pembuatan draf narasi otomatis berdasarkan data asesmen dan mastery siswa.

### 7.2 Features

| Aksi | Deskripsi |
|---|---|
| **View** | Daftar draf narasi |
| **Generate** | Generate narasi otomatis |
| **Save** | Simpan draf |

---

## 8. Tables

| Table | Fungsi |
|---|---|
| `assessments` | Asesmen utama |
| `assessment_items` | Item asesmen |
| `assessment_criteria` | Kriteria penilaian (rubric levels) |
| `assessment_attempts` | Percobaan asesmen siswa |
| `assessment_evidence` | Bukti asesmen (file/link) |
| `assessment_feedback` | Feedback asesmen per siswa |
| `assessment_objectives` | Hubungan asesmen ↔ TP |
| `criterion_results` | Hasil per kriteria per siswa |
| `mastery_records` | Record mastery TP per siswa |
| `interventions` | Intervensi belajar (REMEDIAL/REINFORCEMENT/ENRICHMENT) |
| `reporting_policies` | Kebijakan pelaporan (Phase 6) |
| `summative_results` | Hasil pengolahan sumatif (raw_score, grade_label, status) |

---

## 9. Services

| Service | Fungsi |
|---|---|
| `AssessmentService` | CRUD asesmen, gradebook |
| `MasteryService` | Mastery tracking |
| `MasteryHeatmapService` | Heatmap generation |
| `SummativeProcessingService` | Pengolahan sumatif |
| `NarrativeService` | Narasi generation |
| `RemediationPackagerService` | Remedial package |
