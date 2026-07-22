# Walkthrough — Milestone 4: Penugasan Mengajar & Beban Kerja Guru

Milestone 4 (Penugasan Mengajar dan Beban Kerja Guru terpadu SMP–SMA) telah selesai dibangun, terintegrasi, dan diverifikasi secara komprehensif.

---

## 1. Ringkasan Implementasi

### A. Skema Database (11 Tabel Baru)
Telah dibuat dan divalidasi migrasi `20260722100000_CreateMilestone4Tables.php`:
1. `assignment_versions`: Mengelola versi penugasan per unit & periode akademik.
2. `teaching_assignment_groups`: Pengelompokan rombel / kelompok mengajar.
3. `teaching_assignments`: Penugasan spesifik guru, rombel, mapel, JP mengajar, dan status team teaching.
4. `additional_duty_types`: Master tipe tugas tambahan (Wali Kelas, Pembina OSIS, Kepala Laboratorium, dll).
5. `teacher_additional_duties`: Penugasan tugas tambahan spesifik guru per unit & periode.
6. `workload_policies`: Kebijakan batas JP mengajar & tugas tambahan per unit & status kepegawaian.
7. `teacher_workload_snapshots`: Snapshot kalkulasi total JP mengajar, JP tugas tambahan, total beban kerja, dan status beban (UNDERLOAD, BALANCED, OVERLOAD).
8. `assignment_validation_results`: Log hasil validasi aturan bisnis penugasan.
9. `assignment_revision_history`: Histori revisi versi penugasan dengan *optimistic locking*.
10. `assignment_import_batches`: Batch pementasan (*staging*) impor data penugasan dari Excel/CSV.
11. `assignment_import_rows`: Baris detail pementasan impor data penugasan.

### B. Seeder
- `Milestone4Seeder.php`: Mendaftarkan permission `assignments.view`, `assignments.manage`, `assignments.validate`, `assignments.review`, `assignments.approve`, `assignments.lock`, `assignments.import`, `assignments.export`, `duties.manage`, `workload.view`, `workload.manage` serta data awal master `additional_duty_types`.

### C. Services & Logika Bisnis
- **`TeacherWorkloadCalculationService`**: Menghitung beban mengajar & tugas tambahan, memetakan ke kebijakan unit/kepegawaian, serta menghasilkan snapshot status beban (Underload, Balanced, Overload).
- **`AssignmentValidationService`**: Validasi aturan penugasan (`MISSING_TEACHER`, `OVER_ALLOCATED_STRUCTURE`, penugasan ganda, batas tugas tambahan, underload/overload).
- **`AssignmentWorkflowService`**: Manajemen transisi status versi (DRAFT → VALIDATED → REVIEWED → APPROVED → LOCKED) dengan *optimistic locking* dan penguncian immutability.
- **`AssignmentMatrixService`**: Rekonsiliasi matriks kebutuhan JP vs penugasan aktual per rombel dan mapel (menangani *grade default* dan *classroom override*).
- **`AssignmentImportService`**: Pipeline pementasan (*staging*) impor data penugasan (upload, validasi, mapping, apply, rollback).

### D. Kontroller & Routing
- `AssignmentsController` (`/assignments`): Penugasan mengajar, versi, matriks, validasi, & persetujuan workflow.
- `AssignmentsImportController` (`/assignments/imports`): Staging impor penugasan.
- `WorkloadsController` (`/workloads`): Dashboard beban kerja guru, pemetaan kebijakan, & ekspor report.
- `DutiesController` (`/duties`): Pengelolaan tugas tambahan guru.

Semua route administratif dilindungi oleh server-side filter:
- `auth`
- `password_change_required`
- `unit_access`

---

## 2. Pengujian & Verifikasi

### Test Suite Integration (`Milestone4AcceptanceTest.php`)
Menjalankan scenario end-to-end penugasan mengajar, tugas tambahan, validasi, workflow transition, import staging, dan kalkulasi snapshot beban kerja:
```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit tests/database/Milestone4AcceptanceTest.php
```
**Hasil:** `OK (1 test, 20 assertions)`

### Focused Integration Test Suite (M3 + M4 + Workflow)
```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit tests/database/Milestone3AcceptanceTest.php tests/database/Milestone4AcceptanceTest.php tests/database/WorkflowTest.php
```
**Hasil:** `OK (22 tests, 79 assertions)`

---

## 3. Status Akhir System

| Modul | Status |
| :--- | :--- |
| Milestone 0 — Auth, Security & RBAC Gate | PASSED |
| Milestone 1 — Fondasi Master Data | PASSED |
| Milestone 2 — Master Data Sekolah, Rombel, & Guru | PASSED |
| Milestone 3 — Struktur Kurikulum SMP–SMA | PASSED |
| **Milestone 4 — Penugasan Mengajar & Beban Kerja Guru** | **PASSED** |
| Milestone 5 — Jadwal Pelajaran (Generator & Slot) | NOT STARTED |
