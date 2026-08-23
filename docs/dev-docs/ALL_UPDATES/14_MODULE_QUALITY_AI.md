# WMVAA Akademia — Quality & AI Copilot Module

## 1. Overview

Modul kualitas mengelola refleksi guru, supervisi, evaluasi KSP, dan AI Copilot untuk asisten penulisan.

---

## 2. Quality Dashboard (`/quality`)

### 2.1 Concept

Dashboard terpadu untuk monitoring kualitas pengajaran dan pendidikan.

### 2.2 Access

```
permission: teacher_reflection.view | supervision.view | ksp_evaluation.view
```

### 2.3 Widgets

| Widget | Deskripsi |
|---|---|
| **Reflection Summary** | Ringkasan refleksi guru |
| **Supervision Summary** | Ringkasan supervisi |
| **KSP Status** | Status evaluasi KSP |
| **Quality Score** | Skor kualitas keseluruhan |

---

## 3. Teacher Reflections (`/quality/reflections`)

### 3.1 Concept

Refleksi guru = catatan reflektif tentang proses pembelajaran yang telah dilakukan.

### 3.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /quality/reflections` | Daftar refleksi |
| **Create Form** | `GET /quality/reflections/create` | Form buat baru |
| **Store** | `POST /quality/reflection/create` | Simpan refleksi baru |
| **Detail** | `GET /quality/reflection/:id` | Detail refleksi |
| **Edit** | `GET /quality/reflection/:id/edit` | Form edit |
| **Update** | `POST /quality/reflection/:id/edit` | Simpan perubahan |
| **Publish** | `POST /quality/reflection/:id/publish` | Publikasikan refleksi |

### 3.3 Views

| View | Deskripsi |
|---|---|
| `reflections.php` | Daftar refleksi |
| `reflection_form.php` | Form create/edit |
| `reflection_detail.php` | Detail refleksi |

---

## 4. Supervisions (`/quality/supervisions`)

### 4.1 Concept

Supervisi = catatan pengawasan dan asesmen kinerja mengajar oleh atasan/kepala sekolah.

### 4.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /quality/supervisions` | Daftar supervisi |
| **Create Form** | `GET /quality/supervisions/create` | Form buat baru |
| **Store** | `POST /quality/supervisions` | Simpan supervisi baru |
| **Detail** | `GET /quality/supervision/:id` | Detail supervisi |
| **Edit** | `POST /quality/supervision/:id/edit` | Edit supervisi |
| **Print** | `GET /quality/supervision/:id/print` | Cetak supervisi |

### 4.3 Views

| View | Deskripsi |
|---|---|
| `supervisions.php` | Daftar supervisi |
| `supervision_form.php` | Form create/edit |
| `supervision_detail.php` | Detail supervisi |
| `supervision_print.php` | Print layout |

---

## 5. KSP Evaluation (`/quality/ksp`)

### 5.1 Concept

Evaluasi KSP = monitoring dan evaluasi terhadap kinerja Komite Sekolah Pendidikan.

### 5.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Dashboard** | `GET /quality/ksp` | Dashboard evaluasi KSP |

---

## 6. AI Copilot (`/quality/copilot`)

### 6.1 Concept

AI Copilot = asisten berbasis AI yang membantu guru membuat draf refleksi dan narasi.

### 6.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Copilot** | `GET /quality/copilot` | Interface AI Copilot |
| **Generate Draft** | `POST /quality/copilot/generate` | Generate draf dengan AI |
| **Accept Draft** | `POST /quality/copilot/:id/accept` | Terima draf AI |
| **Reject Draft** | `POST /quality/copilot/:id/reject` | Tolak draf AI |
| **Feedback** | `POST /quality/copilot/:id/feedback` | Berikan feedback |

### 6.3 Workflow

```
User input → AI Generate → Review → Accept/Reject → Save to Reflection
```

---

## 7. Quality Report (`/quality/report`)

### 7.1 Concept

Laporan mutu = laporan komprehensif tentang kualitas pengajaran dan pendidikan.

### 7.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Report** | `GET /quality/report` | Lihat laporan mutu |

---

## 8. Tables

| Table | Fungsi |
|---|---|
| `teacher_reflections` | Refleksi guru |
| `supervision_records` | Catatan supervisi |
| `ksp_evaluation_records` | Evaluasi KSP |
| `ai_copilot_drafts` | Draf AI |
| `quality_report_records` | Laporan mutu |

---

## 9. Services

| Service | Fungsi |
|---|---|
| `QualityService` | CRUD refleksi, supervisi, evaluasi |
| `ReflectionTrendsService` | Analisis tren refleksi |
| `RubricGeneratorService` | Generate rubric |
| `DifferentiationService` | Asisten diferensiasi |
| `AdaptiveModeService` | Mode adaptif |
