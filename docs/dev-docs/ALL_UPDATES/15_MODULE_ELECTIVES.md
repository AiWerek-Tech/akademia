# WMVAA Akademia — Elective Subjects & Selection Module

## 1. Overview

Modul Pemilihan Mata Pelajaran Elektif mendukung sistem pemilihan mapel pilihan untuk jenjang Fase F (SMP/SMA) sesuai Permendikdasmen Nomor 13 Tahun 2025.

---

## 2. Admin Side (`/electives`)

### 2.1 Concept

Admin mengelola periode pemilihan, penawaran mapel, dan persetujuan pilihan siswa.

### 2.2 Features

| Aksi | Deskripsi |
|---|---|
| **Create Period** | Buat periode pemilihan |
| **View Period** | Detail periode |
| **Update Period** | Edit periode |
| **Publish** | Publikasikan periode ke siswa |
| **Add Offering** | Tambah penawaran mapel |
| **Remove Offering** | Hapus penawaran |
| **Update Offering** | Edit penawaran |
| **Toggle Approval** | Setujui/tolak penawaran |
| **Approve All Eligible** | Setujui semua penawaran eligible |
| **Export Selections** | Export data pemilihan |
| **Sync Rombel** | Sinkronkan data ke rombel |
| **Import Students** | Import daftar siswa |
| **Download Template** | Download template import |
| **Delete Student** | Hapus siswa dari periode |

### 2.3 Offering Management

Setiap penawaran (offering) terdiri dari:

| Field | Deskripsi |
|---|---|
| `subject_id` | Mapel yang ditawarkan |
| `teacher_id` | Guru pengampu |
| `weekly_hours` | JP per minggu |
| `min_students` | Jumlah minimum peminat |
| `capacity` | Kapasitas maksimal |
| `study_career_info` | Info studi/karier |

### 2.4 Workflow Approval

```
offering created → pending → approved (by kurikulum) → published
```

---

## 3. Student Selection (`/my-electives`)

### 3.1 Concept

Siswa memilih mapel elektif berdasarkan urutan preferensi.

### 3.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Selection** | `GET /my-electives` | Lihat pilihan saya |
| **Save Draft** | `POST /my-electives/save` | Simpan draft pilihan |
| **Submit** | `POST /my-electives/submit` | Submit pilihan |
| **Request Change** | `POST /my-electives/change-request` | Minta perubahan |

### 3.3 Rules

- Siswa memilih **4-5** pilihan (utama + cadangan)
- Setiap pilihan memiliki urutan preferensi
- Batas waktu perubahan dikonfigurasi di periode

---

## 4. Wali Kelas Management

### 4.1 Concept

Wali kelas mengelola pilihan siswa di kelas binaannya.

### 4.2 Features

| Aksi | Deskripsi |
|---|---|
| **Review Submissions** | Review pilihan siswa |
| **Approve/Reject** | Setujui/tolak pilihan |
| **Change Requests** | Kelola permintaan perubahan |

---

## 5. Elective Period Settings

### 5.1 Fields

| Field | Deskripsi |
|---|---|
| `academic_period_id` | Periode akademik |
| `unit_id` | Unit |
| `curriculum_version_id` | Versi kurikulum |
| `grade_from` | Tingkat asal |
| `grade_to` | Tingkat tujuan |
| `selection_window_start` | Mulai jendela pemilihan |
| `selection_window_end` | Akhir jendela pemilihan |
| `change_deadline` | Batas waktu perubahan |
| `min_selections` | Minimum pilihan |
| `max_selections` | Maksimum pilihan |
| `selection_types` | Tipe pemilihan (JSON) |
| `status` | Status publikasi |

### 5.2 Selection Types

| Type | Deskripsi |
|---|---|
| `academic` | Mapel akademik |
| `art_culture_craft` | Seni, budaya, keterampilan |

---

## 6. Tables

| Table | Fungsi |
|---|---|
| `elective_periods` | Periode pemilihan |
| `elective_offerings` | Penawaran mapel |
| `elective_students` | Siswa per periode |
| `student_elective_submissions` | Submission pilihan |
| `student_elective_choices` | Urutan pilihan |
| `student_elective_reviews` | Review BK & kurikulum |
| `student_elective_change_requests` | Permintaan perubahan |

---

## 7. Routes Summary

| Route | Method | Deskripsi |
|---|---|---|
| `GET /electives` | GET | List periode |
| `GET /electives/create` | GET | Form buat periode |
| `POST /electives` | POST | Simpan periode |
| `GET /electives/:id` | GET | Detail periode |
| `POST /electives/:id/update` | POST | Update periode |
| `POST /electives/:id/publish` | POST | Publikasikan |
| `POST /electives/:id/offerings` | POST | Tambah offering |
| `POST /electives/:id/offerings/:oid/delete` | POST | Hapus offering |
| `POST /electives/:id/offerings/:oid/update` | POST | Update offering |
| `POST /electives/:id/offerings/:oid/toggle-approval` | POST | Toggle approval |
| `POST /electives/:id/approve-all-eligible` | POST | Approve all |
| `GET /electives/:id/selections/export` | GET | Export selections |
| `POST /electives/:id/enroll` | POST | Enroll siswa |
| `POST /electives/:id/students/sync-rombel` | POST | Sync rombel |
| `POST /electives/:id/students/import` | POST | Import siswa |
| `GET /electives/:id/students/template` | GET | Download template |
| `POST /electives/:id/students/:sid/delete` | POST | Hapus siswa |
| `GET /my-electives` | GET | Pilihan saya |
| `POST /my-electives/save` | POST | Simpan draft |
| `POST /my-electives/submit` | POST | Submit |
| `POST /my-electives/change-request` | POST | Request change |
| `POST /electives/:id/submissions/:sid/review` | POST | Review submission |
| `POST /electives/:id/change-requests/:rid/review` | POST | Review change |

---

## 8. Services

| Service | Fungsi |
|---|---|
| `ElectiveSelectionService` | Core selection logic |
| `ElectiveConflictMatrixService` | Conflict detection |
| `ElectiveComplianceService` | Compliance checking |
| `ElectiveExportService` | Export data |
| `ElectivePromotionCatalog` | Promotion catalog |
| `TeacherElectivePortalService` | Portal guru |
