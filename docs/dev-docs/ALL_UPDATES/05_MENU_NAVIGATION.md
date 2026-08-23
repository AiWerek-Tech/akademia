# WMVAA Akademia — Complete Menu & Navigation Reference

## 1. Navigation Structure

Menu sidebar dinamis berdasarkan role pengguna. Setiap role melihat menu yang berbeda.

---

## 2. Platform Admin (super_admin, superadmin, admin_smp, admin_sma)

### 2.1 Menu Utama

| Menu | Icon | Route |
|---|---|---|
| Dashboard | `layout-dashboard` | `/dashboard` |

### 2.2 Master Data

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Tahun Pelajaran | `calendar-clock` | `/academic-periods` | `academic_years.view` OR `academic_periods.view` |
| Master Guru | `users-round` | `/teachers` | `teachers.view` |
| Review Duplikat | `copy-check` | `/duplicates` | `duplicates.view` |
| Mata Pelajaran | `book-open` | `/subjects` | `subjects.view` |
| Kegiatan Rutin Sekolah | `clock` | `/routine-activities` | `curriculum.view` |
| Tingkat Kelas | `layers` | `/grade-levels` | `grade_levels.view` |
| Kelas / Rombel | `layout-grid` | `/classrooms` | `classrooms.view` |
| Peserta Didik | `user-round` | `/students` | `students.view` |
| Ruang Sekolah | `door-closed` | `/rooms` | `rooms.view` |
| Regulasi Pendidikan | `landmark` | `/references/regulations` | `regulations.view` |
| Sumber Kurikulum | `library` | `/references/curriculum-sources` | `curriculum_sources.view` |
| Profil Lulusan | `badge-check` | `/references/graduate-profile` | `graduate_profile.view` |
| Import Master | `upload` | `/imports/master` | `teachers.import` OR `subjects.import` |

### 2.3 Perencanaan

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Struktur Kurikulum | `layout-list` | `/curriculum` | `curriculum.view` |
| Import Kurikulum | `table-2` | `/curriculum/imports` | `curriculum.import` |
| IALOS Education | `network` | `/curriculum/outcomes` | `learning_outcomes.view` |
| Adaptasi TP | `target` | `/curriculum/objectives` | `learning_objectives.view` |
| ATP & Coverage | `route` | `/curriculum/sequences` | `learning_sequences.view` |
| Paket Pembelajaran | `package-open` | `/curriculum/learning-packs` | `learning_packs.view` |
| Pemilihan Mapel | `list-checks` | `/electives` | `electives.view` |
| Kalender Pendidikan | `calendar-range` | `/academic-calendar` | `academic_calendar.view` |

### 2.4 Penugasan & Jadwal

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Penugasan Mengajar | `clipboard-pen` | `/assignments` | `assignments.view` |
| Beban Kerja Guru | `bar-chart` | `/workloads` | `workloads.view` |
| Jadwal Pelajaran | `calendar-days` | `/schedules` | `schedules.view` |
| Substitusi Guru | `user-round-cog` | `/schedules/substitutions` | `schedules.view` |
| Jadwal Piket | `shield-check` | `/duty-schedules` | `duty_schedules.view` OR `schedules.view` |
| Absensi & Jurnal Kelas | `clipboard-check` | `/attendances` | `attendances.view` |

### 2.5 Portal Guru (Supervisi)

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Jadwal Mengajar Guru | `calendar-days` | `/portal/schedule` | `teacher_schedule.view` |
| Penugasan & Beban Guru | `gauge` | `/portal/workload` | `teacher_workload.view` |
| SK Tugas Guru | `file-signature` | `/portal/assignment-document` | `teacher_assignment_document.view` |
| Piket Guru | `shield-check` | `/portal/duty-schedule` | `teacher_duty_schedule.view` |

### 2.6 IALOS Education

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Control Center | `layout-dashboard` | `/education` | Any IALOS permission |
| Digital KSP | `book-open-check` | `/education/ksp` | `ksp.view` |
| Regulasi Pendidikan | `landmark` | `/references/regulations` | `regulations.view` |
| Sumber Kurikulum | `library` | `/references/curriculum-sources` | `curriculum_sources.view` |
| Profil Lulusan | `badge-check` | `/references/graduate-profile` | `graduate_profile.view` |
| CP & Elemen | `milestone` | `/curriculum/outcomes` | `learning_outcomes.view` |
| Tujuan Pembelajaran | `target` | `/curriculum/objectives` | `learning_objectives.view` |
| ATP | `route` | `/curriculum/sequences` | `learning_sequences.view` |
| Coverage Kurikulum | `scan-search` | `/curriculum/coverage` | `learning_sequences.view` |
| Paket Pembelajaran | `package-open` | `/curriculum/learning-packs` | `learning_packs.view` |
| Rencana Belajar (RPP) | `book-open` | `/lesson-plans` | `lesson_plans.view` |
| Tren Refleksi Mengajar | `trending-up` | `/smart/reflection-trends` | `teaching.workspace` |
| Peta Lineage Kurikulum | `git-branch` | `/smart/lineage-graph` | `learning_outcomes.view` |
| Import Data Pendidikan | `file-up` | `/curriculum/education-imports` | `learning_outcomes.manage` |

### 2.7 Penilaian & Mastery

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Assessment & Nilai | `clipboard-check` | `/assessment` | `assessment.view` |
| Mastery TP | `bar-chart-3` | `/mastery` | `assessment.mastery` |
| Mastery Heatmap | `grid-3x3` | `/smart/mastery-heatmap` | `assessment.view` |
| Intervensi Belajar | `heart-handshake` | `/interventions` | `assessment.mastery` |
| Kebijakan Pelaporan | `settings` | `/reporting-policies` | `assessment.mastery` |
| Pengolahan Sumatif | `sigma` | `/summative` | `assessment.mastery` |
| Draf Narasi Rapor | `pen-line` | `/smart/narrative-drafter` | `assessment.view` |

### 2.8 Kokurikuler & Ekstra

| Submenu | Icon | Route | Permission | Notes |
|---|---|---|---|---|
| Program Kokurikuler | `star` | `/cocurricular` | `cocurricular.view` | Feature flag: `ialos_phase7_cocurricular` |
| Program Ekstrakurikuler | `trophy` | `/extracurricular` | `extracurricular.view` | |
| Kebiasaan (7KAIH) | `smile` | `/cocurricular/habits` | `cocurricular.view` | Feature flag: `ialos_7kahi` |
| Check-in Kebiasaan | `calendar-check-2` | `/cocurricular/checkins` | `cocurricular.view` | Feature flag: `ialos_7kahi` |

### 2.9 Rapor & Portofolio

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Laporan Semester | `file-check` | `/reporting` | `reporting.view` |
| Dashboard Kelas | `line-chart` | `/reporting/class-dashboard` | `reporting.view` |
| Kesiapan Kenaikan Kelas | `trending-up` | `/reporting/promotion` | `reporting.view` |

### 2.10 Integrasi & Sistem

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Sync Mobile (Kodular) | `smartphone` | `/system/sync` | `sync.view` |
| Diagnostik Sistem | `activity` | `/system/diagnostics` | `sync.view` |

### 2.11 Sistem

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Profil Sekolah | `building` | `/settings/school-profile` | `settings.view` |
| Tampilan & Tema | `palette` | `/settings/appearance` | `settings.view` |
| Manajemen Database | `server` | `/settings/database` | `settings.manage` |
| Pengaturan Aplikasi | `cog` | `/settings/application` | `settings.view` |
| Operasional Akademik | `calendar-check` | `/settings/academic-operations` | `academic_calendar.manage` |
| Pengaturan Absensi | `clipboard-list` | `/settings/attendance` | `attendances.admin` |
| User Management | `users` | `/users` | `users.view` |
| Role & Permission | `key-round` | `/roles` | `roles.view` |
| Audit Log | `scroll-text` | `/audit` | `audit.view` |

---

## 3. Wakasek Kurikulum

Menu sama dengan admin tetapi **tidak termasuk**: User Management, Role & Permission, Audit Log, Database Manager, dan beberapa menu sistem lainnya. Fokus pada:

- Struktur Kurikulum & Import
- Penugasan Mengajar
- Jadwal Pelajaran
- Assessment & Nilai
- Portal Guru (Supervisi)
- IALOS Education

---

## 4. Kepala Sekolah / Viewer Yayasan (Executive)

### Monitoring Akademik

| Submenu | Icon | Route |
|---|---|---|
| SK Pembagian Tugas | `file-check-2` | `/assignments` |
| Beban Kerja Guru | `bar-chart-3` | `/workloads` |
| Jadwal Resmi | `calendar-check` | `/schedules` |
| Jadwal Piket | `shield-check` | `/duty-schedules` |
| IALOS Education | `network` | `/curriculum/sequences` |
| Paket Pembelajaran | `package-open` | `/curriculum/learning-packs` |

### Referensi Sekolah

| Submenu | Icon | Route |
|---|---|---|
| Daftar Guru | `users` | `/teachers` |
| Daftar Rombel | `school` | `/classrooms` |
| Peserta Didik | `graduation-cap` | `/students` |
| Regulasi Pendidikan | `landmark` | `/references/regulations` |
| Profil Lulusan | `badge-check` | `/references/graduate-profile` |

---

## 5. Tata Usaha (Operations)

### Administrasi Sekolah

| Submenu | Icon | Route | Permission |
|---|---|---|---|
| Data Guru | `contact` | `/teachers` | `teachers.view` |
| Peserta Didik | `users-round` | `/students` | `students.view` |
| Kelas / Rombel | `school` | `/classrooms` | `classrooms.view` |
| Periode Akademik | `calendar-range` | `/academic-periods` | `academic_periods.view` |
| Akun Pengguna | `user-cog` | `/users` | `users.view` |

---

## 6. Wali Kelas

### Kelas Binaan

| Submenu | Icon | Route |
|---|---|---|
| Siswa Kelas Saya | `users-round` | `/portal/classroom` |
| Pemilihan Mapel Kelas | `list-checks` | `/electives` |
| Jadwal Kelas Saya | `calendar-range` | `/portal/schedule?scope=classroom` |

### Tugas Mengajar Saya

| Submenu | Icon | Route |
|---|---|---|
| Ruang Mengajar Harian | `sparkles` | `/teaching/today` |
| Jadwal Mengajar | `calendar-days` | `/portal/schedule` |
| Mapel Pilihan Saya | `users-round` | `/portal/electives` |
| Beban Mengajar | `bar-chart` | `/portal/workload` |
| SK Pembagian Tugas | `file-signature` | `/portal/assignment-document` |
| Jadwal Piket | `shield-check` | `/portal/duty-schedule` |

### Absensi

| Submenu | Icon | Route |
|---|---|---|
| Input Absensi Baru | `edit-3` | `/teaching/attendance/offline` |
| Riwayat Absensi | `history` | `/teaching/attendance/history` |
| Rekapan Kehadiran | `bar-chart-3` | `/teaching/attendance/recap` |

---

## 7. Guru

### Tugas Mengajar Saya

| Submenu | Icon | Route |
|---|---|---|
| Ruang Mengajar Harian | `sparkles` | `/teaching/today` |
| Jadwal Mengajar | `calendar-days` | `/portal/schedule` |
| Mapel Pilihan Saya | `users-round` | `/portal/electives` |
| Beban Mengajar | `bar-chart` | `/portal/workload` |
| SK Pembagian Tugas | `file-signature` | `/portal/assignment-document` |

### Absensi

| Submenu | Icon | Route |
|---|---|---|
| Input Absensi Baru | `edit-3` | `/teaching/attendance/offline` |
| Riwayat Absensi | `history` | `/teaching/attendance/history` |
| Rekapan Kehadiran | `bar-chart-3` | `/teaching/attendance/recap` |

---

## 8. Siswa

### Akademik Saya

| Submenu | Icon | Route |
|---|---|---|
| Pilihan Mata Pelajaran | `list-checks` | `/my-electives` |
| Kalender Pendidikan | `calendar-range` | `/academic-calendar` |

---

## 9. Dashboard Variants

### 9.1 Mode Detection

Dashboard mode ditentukan oleh role:

| Mode | Roles | Hero Copy |
|---|---|---|
| `administration` | super_admin, admin_smp, admin_sma | "Kontrol Akademik" |
| `academic` | wakasek_kurikulum | "Ruang Kerja Kurikulum" |
| `executive` | kepala_sekolah, viewer_yayasan | "Ringkasan Eksekutif" |
| `teacher` | guru, wali_kelas (tanpa admin role) | "Ruang Kerja Personal" |
| `student` | siswa | "Portal Peserta Didik" |
| `operations` | tata_usaha | "Ruang Kerja Operasional" |
| `general` | Lainnya | "Dashboard Akademik" |

### 9.2 Admin Dashboard Features

| Widget | Deskripsi |
|---|---|
| **Hero Card** | Greeting, unit scope, active period |
| **IALOS Control Center** | Quick link ke IALOS Education |
| **KPI Cards** | Unit aktif, Pengguna aktif, Guru aktif, Rombel aktif |
| **Kesiapan Data Master** | Progress bar + checklist items |
| **Akses Cepat** | Quick action buttons |
| **Aktivitas Terbaru** | Audit log table |

### 9.3 Teacher Dashboard Features

| Widget | Deskripsi |
|---|---|
| **Hero Card** | Greeting dengan role badge |
| **KPI Cards** | JP Mengajar, Mata Pelajaran, Rombel, Hari Piket |
| **Agenda Hari Ini** | Today's schedule entries |
| **Dokumen & Tugas Saya** | Quick links ke portal features |

### 9.4 Executive Dashboard Features

| Widget | Deskripsi |
|---|---|
| **KPI Cards** | Versi tugas resmi, Jadwal diterbitkan, Konflik terbuka |
| **Laporan & Dokumen** | Quick links ke SK, Beban, Jadwal |

### 9.5 Student Dashboard Features

| Widget | Deskripsi |
|---|---|
| **Profile Card** | Nama, rombel, quick link ke Pilihan Mapel |

---

## 10. Context Switching

### 10.1 Unit Selector

- Dropdown di navbar untuk switch unit sekolah
- POST ke `/context/unit`
- Super admin: dapat memilih semua unit
- Regular user: hanya unit yang diakses

### 10.2 Period Selector

- Dropdown di navbar untuk switch periode akademik
- POST ke `/context/period`
- Menampilkan format: `T.A 2026/2027 - Ganjil — Aktif`

### 10.3 Portal Unit Scope

- Query parameter `unit_scope=all|unit_id`
- Digunakan di dashboard dan portal views
- Form auto-submit via `data-auto-submit` attribute

---

## 11. Breadcrumb

Setiap halaman memiliki breadcrumb navigation:

```html
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item active">Page Title</li>
</ol>
```

Breadcrumb title di-infer otomatis dari route prefix menggunakan map `$routeTitles`.
