# Phase 4 — Deep Learning Lesson Plan Engine

**Sub-sistem:** IALOS Education · Deep Learning Lesson Planner  
**Tanggal:** 19 Agustus 2026  
**Status:** ✅ **SELESAI & TERVERIFIKASI**

---

## 1. Ikhtisar Arsitektur

Phase 4 (Deep Learning Lesson Plan Engine) mengubah alur perencanaan pembelajaran konvensional (berupa dokumen statis Word/PDF terpisah) menjadi **model perencanaan digital hidup (*Living Lesson Plan*)** yang:
1. Mengagregasikan desain kurikulum dari **Phase 3 (Subject Learning Pack)** secara otomatis (Tujuan Pembelajaran, Tahapan Belajar, Aktivitas, dan Asesmen).
2. Menerapkan kerangka kerja **Pembelajaran Mendalam (Deep Learning)** sesuai Kepmendikdasmen 126/P/2025 dengan tiga tahapan esensial: **Memahami**, **Mengaplikasi**, dan **Merefleksi**.
3. Menyediakan kontrol alur kerja persetujuan berjenjang (*State Machine*):  
   `DRAFT` $\rightarrow$ `READY` $\rightarrow$ `IN_PROGRESS` $\rightarrow$ `COMPLETED` $\rightarrow$ `REFLECTED`.
4. Mengamankan integritas data dengan *Optimistic Concurrency Control* (OCC), pembekuan data saat status selesai (*Immutability Guard*), dan isolasi unit sekolah (`UnitScopeService`).

---

## 2. Struktur Database & Model Data

### 2.1 Tabel Utama & Relasi

| Nama Tabel | Deskripsi | Kunci & Constraints |
|---|---|---|
| `lesson_plans` | Entitas utama RPP/Modul Ajar harian | `uuid`, `academic_period_id`, `unit_id`, `subject_id`, `grade_level_id`, `class_id`, `teacher_id`, `schedule_entry_id`, `learning_pack_id`, `learning_unit_id`, `date`, `session_number`, `status`, `revision_number` |
| `lesson_plan_objectives` | Relasi TP ke RPP | `uuid`, `lesson_plan_id`, `learning_objective_id`, `role` (PRIMARY, SECONDARY), `sequence_order` |
| `lesson_plan_stages` | 3 Dimensi Tahapan Belajar | `uuid`, `lesson_plan_id`, `stage_type` (MEMAHAMI, MENGAPLIKASI, MEREFLEKSI), `title`, `description`, `estimated_minutes`, `sequence_order` |
| `lesson_plan_activities` | Aktivitas kelas & moda pelaksanaan | `uuid`, `lesson_plan_id`, `lesson_plan_stage_id`, `learning_activity_id`, `custom_title`, `delivery_mode` (PLUGGED, UNPLUGGED, DISCUSSION, HYBRID, PRACTICE, PROJECT, OTHER), `grouping_mode` (INDIVIDUAL, PAIR, SMALL_GROUP, WHOLE_CLASS, FLEXIBLE), `graduate_profile_alignment` |
| `lesson_plan_activity_resources` | Sarana/alat per aktivitas | `uuid`, `lesson_plan_activity_id`, `learning_resource_id`, `custom_description`, `quantity`, `is_required` |
| `lesson_plan_assessments` | Rencana asesmen | `uuid`, `lesson_plan_id`, `assessment_purpose` (INITIAL, FORMATIVE, SUMMATIVE), `recommended_method`, `criteria_reference`, `notes` |
| `lesson_plan_assessment_rubrics` | Kriteria rubrik penilaian | `uuid`, `lesson_plan_assessment_id`, `criterion_description`, `rubric_levels`, `sequence_order` |

---

## 3. Fitur Utama & Service Layer

File Service: [`App\Services\LessonPlanService`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/LessonPlanService.php)

- **`create(array $data)`**: Membuat rencana pembelajaran baru dengan validasi unit dan hak akses.
- **`populateFromPack(string $planUuid, ?int $learningUnitId)`**: Mengisi otomatis seluruh TP, 3 tahapan (Memahami, Mengaplikasi, Merefleksi), aktivitas, kebutuhan sarana, dan asesmen dari Paket Pembelajaran Phase 3.
- **`updateDesign(string $planUuid, array $data)`**: Memperbarui identifikasi kesiapan belajar, karakteristik materi, kemitraan belajar, pemanfaatan digital, dan dimensi Profil Lulusan.
- **`addStage`, `addActivity`, `addAssessment`**: Menambah komponen tahapan, aktivitas, dan asesmen.
- **`linkActivityResource`, `addRubric`**: Menghubungkan kebutuhan alat/sarana dan mendefinisikan rubrik penilaian bertingkat.
- **`validatePlan(string $planUuid)`**: Memvalidasi kelengkapan RPP sebelum diajukan ke status `READY` (memastikan minimal 1 TP, tahapan 3D lengkap, aktivitas dan asesmen tersedia).
- **`transition(string $uuid, string $target, int $revisionNumber)`**: Memproses perpindahan status workflow dengan OCC.
- **`clone(string $sourceUuid, array $data)`**: Menduplikasi RPP beserta seluruh struktur tahapan, aktivitas, rubrik, dan sarana secara utuh.

---

## 4. Antarmuka Pengguna (UI/UX)

1. **Dashboard & Index ([`lesson_plans/index.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/lesson_plans/index.php)):**
   - Kartu metrik statistik real-time (Total, Draft, Siap Diajarkan, Selesai & Refleksi).
   - Live search per mapel, pertemuan, dan guru pengampu.
2. **Formulir Pembuatan ([`lesson_plans/create.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/lesson_plans/create.php)):**
   - Dynamic Learning Pack $\rightarrow$ Unit loader yang mempermudah auto-populasi desain belajar.
3. **Detail & Studio RPP ([`lesson_plans/detail.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/lesson_plans/detail.php)):**
   - Segmented navigation tabs: *Ringkasan & Validasi*, *Desain Pembelajaran*, *Tahapan Belajar (3D)*, *Aktivitas & Sumber Daya*, *Asesmen & Rubrik*.
   - Workflow stepper dan tombol aksi transisi.
   - Modal dialog terstruktur untuk penambahan aktivitas, sarana, dan kriteria rubrik.

---

## 5. Verifikasi Pengujian

Pengujian otomatis dijalankan menggunakan PHPUnit 10.5 pada PHP 8.2:
- File pengujian: [`tests/database/LessonPlanEngineTest.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/LessonPlanEngineTest.php)
- **Hasil:** 25 test methods, 78 assertions — **100% PASSED**.
