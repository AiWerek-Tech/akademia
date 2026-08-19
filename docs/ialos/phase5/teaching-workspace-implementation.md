# Dokumentasi Teknis Implementasi Phase 5: Daily Teaching Workspace & Execution Engine

## 1. Ringkasan Eksekutif

Phase 5 (**Daily Teaching Workspace & Execution Engine**) menjembatani persiapan pembelajaran di **Phase 4 (Lesson Plan Engine)** dan jadwal pelajaran sekolah (**ScheduleEntry**) ke dalam **pelaksanaan kegiatan belajar mengajar nyata di kelas**.

Alur terpadu yang diimplementasikan:
$$\text{ScheduleEntry} \longrightarrow \text{LearningSession} \longrightarrow \text{LessonPlan} \longrightarrow \text{Teaching Mode (Checklist 3D)} \longrightarrow \text{Presensi \& Formatif} \longrightarrow \text{Refleksi Sesi}$$

---

## 2. Database & Data Models

### A. Tabel Utama
1. **`learning_sessions`**: Entitas inti sesi pembelajaran yang menghubungkan jadwal (`schedule_entry_id`), RPP (`lesson_plan_id`), dan presensi operasional (`attendance_session_id`).
   - Fields: `uuid`, `academic_period_id`, `unit_id`, `teacher_id`, `classroom_id`, `subject_id`, `grade_level_id`, `schedule_entry_id`, `lesson_plan_id`, `attendance_session_id`, `session_date`, `meeting_number`, `jp_count`, `start_time`, `end_time`, `actual_start_time`, `actual_end_time`, `topic`, `learning_objective_summary`, `misconception_warnings`, `deviation_notes`, `status`, `started_at`, `completed_at`, `reflected_at`.
2. **`learning_session_activities`**: Ceklis alur aktivitas belajar 3 Dimensi di kelas.
   - Fields: `learning_session_id`, `lesson_plan_activity_id`, `stage_type` (`MEMAHAMI`, `MENGAPLIKASI`, `MEREFLEKSI`), `title`, `description`, `sequence_order`, `is_completed`, `completed_at`, `actual_minutes`, `notes`.
3. **`learning_session_observations`**: Pencatatan formatif cepat dan radar miskonsepsi siswa.
   - Fields: `learning_session_id`, `student_id`, `observation_type`, `rating`, `notes`, `misconception_found`, `misconception_detail`, `follow_up_needed`.
4. **`learning_session_reflections`**: Jurnal refleksi pasca mengajar guru.
   - Fields: `learning_session_id`, `what_went_well`, `challenges`, `student_engagement`, `objective_achievement`, `tp_coverage_notes`, `follow_up_plan`, `next_session_notes`, `self_rating`.

### B. State Machine Siklus Sesi
$$\text{PLANNED} \longrightarrow \text{IN\_PROGRESS} \longrightarrow \text{COMPLETED} \longrightarrow \text{REFLECTED}$$

---

## 3. Komponen Antarmuka Pengguna (UI/UX)

### A. Today Dashboard (`/teaching/today`)
- **Header:** Pemilih tanggal dinamis dan filter guru untuk pemantauan pimpinan/kurikulum.
- **Attention Center:** Notifikasi murid yang butuh intervensi remedial dan sesi yang belum direfleksi.
- **Timeline Mengajar:** Kartu kelas dengan badge status, indikator JP, tombol aksi *[Mulai Kelas]*, *[Masuk Teaching Mode]*, *[Isi Refleksi]*.
- **Radar Miskonsepsi Siswa:** Feed ringkas miskonsepsi terkini yang terdeteksi di kelas.

### B. In-Class Teaching Mode (`/teaching/session/{uuid}`)
- **Header Distraction-Free:** Live timer stopwatch digital, badge status kelas, dan tombol penuntasan kelas.
- **Panel Kiri:** Ringkasan TP hari ini, warning miskonsepsi, dan rekomendasi adaptif (*Plugged vs Unplugged*).
- **Panel Tengah:** Checklist interaktif alur 3D (*1. Memahami*, *2. Mengaplikasi*, *3. Merefleksi*) yang tersinkronisasi via AJAX secara instan.
- **Panel Kanan:** 
  - Presensi Cepat: Roster kelas dengan tombol H/T/I/S/A sekali sentuh.
  - Observasi Formatif: Formulir kilat untuk menandai pemahaman, miskonsepsi, dan kebutuhan tindak lanjut siswa.

### C. Jurnal Refleksi Pembelajaran (`/teaching/session/{uuid}/reflect`)
- Evaluasi hal yang berjalan baik (*what went well*).
- Pencatatan kendala / tantangan di kelas.
- Evaluasi ketercapaian TP dan keterlibatan siswa.
- Rencana tindak lanjut dan self-rating bintang 1–5.

---

## 4. Keamanan & Role-Based Access Control (RBAC)

Izin hak akses yang didaftarkan:
- `teaching.workspace`: Melihat timeline dan dashboard ruang mengajar.
- `teaching.teach`: Memulai sesi mengajar, mengoperasikan Teaching Mode, ceklis aktivitas, dan observasi.
- `teaching.reflect`: Mengisi dan memperbarui jurnal refleksi pembelajaran.

Roles yang diberi hak akses: `super_admin`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `guru`.

---

## 5. Status Verifikasi

- Database Migration: `20260819100000_CreatePhase5TeachingWorkspaceTables.php` (Tuntas dieksekusi).
- Automated Test Suite: `tests/database/TeachingWorkspaceEngineTest.php`.
