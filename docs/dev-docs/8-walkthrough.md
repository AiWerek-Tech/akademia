# Walkthrough — Milestone 5: Penjadwalan Pelajaran, Slot Waktu, dan Generator Jadwal

Milestone 5 (Sistem Penjadwalan Pelajaran Terpadu SMP–SMA, Slot Waktu, Ketersediaan Guru/Ruang, dan Generator Jadwal Otomatis) telah selesai dibangun, terintegrasi, dan diverifikasi secara komprehensif.

---

## 1. Ringkasan Implementasi

### A. Skema Database (21 Tabel Baru)
Telah dibuat dan divalidasi migrasi `20260722300000_CreateMilestone5Tables.php`:
1. `schedule_versions`: Mengelola lifecycle versi jadwal (`DRAFT`, `VALIDATED`, `REVIEWED`, `APPROVED`, `LOCKED`, `ARCHIVED`).
2. `schedule_days`: Konfigurasi hari sekolah per unit.
3. `schedule_slot_templates`: Template slot waktu pelajaran & istirahat.
4. `schedule_slot_template_items`: Item detail template slot waktu.
5. `schedule_day_slots`: Slot waktu aktual ter-materialisasi per hari sekolah.
6. `schedule_requirements`: Kebutuhan jam mengajar hasil sinkronisasi dari Penugasan M4 & Kurikulum M3.
7. `schedule_entries`: Grid entri jadwal aktual (slot x rombel x teacher x room).
8. `schedule_fixed_activities`: Jam kegiatan tetap (upacara, chapel, kebaktian, istirahat).
9. `teacher_availability_rules`: Aturan ketersediaan guru (blokir/preferred jam).
10. `classroom_availability_rules`: Aturan ketersediaan rombel.
11. `room_availability_rules`: Aturan ketersediaan ruang kelas/lab.
12. `scheduling_constraints`: Aturan batasan *hard* & *soft constraint* beserta bobot penalti.
13. `schedule_conflicts`: Audit log bentrok jadwal (teacher/classroom/room overlap).
14. `schedule_generation_runs`: Log eksekusi generator jadwal.
15. `schedule_generation_candidates`: Solusi kandidat jadwal hasil generator.
16. `schedule_candidate_entries`: Detail entri kandidat jadwal ter-generate.
17. `schedule_locks`: Penguncian spesifik (rombel/guru/ruang/slot/versi).
18. `schedule_exceptions`: Pengecualian audit manual (dispensasi bentrok).
19. `schedule_revision_history`: Log revisi versi jadwal (*optimistic concurrency control*).
20. `schedule_import_batches`: Batch pementasan (*staging*) impor jadwal dari Excel/CSV.
21. `schedule_import_rows`: Detail baris pementasan impor jadwal.

### B. Seeder
- `Milestone5Seeder.php`: Mendaftarkan 14 permission (`schedules.view`, `schedules.manage`, `schedules.validate`, `schedules.review`, `schedules.approve`, `schedules.lock`, `schedules.generate`, `schedules.import`, `schedules.export`, `schedules.revise`, `availability.view`, `availability.manage`, `constraints.view`, `constraints.manage`) dan 11 *default scheduling constraints*.

### C. Services & Logika Bisnis
- **`ScheduleRequirementSyncService`**: Idempotent sync dari penugasan mengajar M4 dan struktur kurikulum M3 ke `schedule_requirements`.
- **`TeacherAvailabilityService`**, **`ClassroomAvailabilityService`**, **`RoomAvailabilityService`**: Evaluasi ketersediaan lintas unit secara global (mencegah guru mengajar bersamaan di SMP dan SMA).
- **`ScheduleConflictDetectionService`**: Deteksi bentrok *hard* & *soft constraints* secara menyeluruh (teacher overlap, classroom overlap, room overlap, cross-unit teacher overlap, unassigned slots).
- **`ScheduleScoringService`**: Kalkulasi skor penalti *soft constraints* (keseimbangan beban harian, minimisasi *gap* jam kosong, penggunaan ruang prioritas).
- **`DeterministicGreedyScheduleGenerator`**: Pure deterministic greedy generator yang menghasilkan kandidat jadwal optimal tanpa keacakan nondeterministik.
- **`ScheduleWorkflowService`**: Manajemen transisi status versi dengan OCC (`revision_number`) dan immutability lock.
- **`ScheduleImportService`**: Pipeline staging impor Excel/CSV.
- **`ScheduleExportService`**: Ekspor grid jadwal per rombel, guru, ruang, dan unit sekolah.

### D. Kontroller & Routing
- `SchedulesController` (`/schedules`)
- `ScheduleEditorController` (`/schedules/editor`)
- `ScheduleGeneratorController` (`/schedules/generator`)
- `ScheduleConflictsController` (`/schedules/conflicts`)
- `ScheduleAvailabilityController` (`/schedules/availability`)
- `ScheduleConstraintsController` (`/schedules/constraints`)
- `ScheduleImportsController` (`/schedules/imports`)
- `ScheduleReportsController` (`/schedules/reports`)

---

## 2. Pengujian & Verifikasi

Seluruh unit test dan integration test untuk Milestone 5 telah dijalankan dan **100% LULUS**:

```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit --no-coverage tests/database/
```

```text
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.20
Configuration: E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\phpunit.xml.dist

...............................................................  63 / 152 ( 41%)
............................................................... 126 / 152 ( 82%)
..........................                                      152 / 152 (100%)

Time: 17:22.045, Memory: 74.00 MB

OK (152 tests, 523 assertions)
```

### Rincian Suite Test Milestone 5
1. `tests/database/Milestone5MigrationTest.php` — **PASSED** (21 assertions)
2. `tests/database/Milestone5SeederTest.php` — **PASSED** (25 assertions)
3. `tests/database/ScheduleVersionTest.php` — **PASSED** (8 assertions)
4. `tests/database/ScheduleGeneratorTest.php` — **PASSED** (7 assertions)
5. `tests/database/ScheduleConflictDetectionTest.php` — **PASSED** (2 assertions)
6. `tests/database/ScheduleImportExportTest.php` — **PASSED** (6 assertions)
7. `tests/database/ScheduleSecurityTest.php` — **PASSED** (6 assertions)

---

## 3. Status Resmi Sistem

| Modul | Status |
| :--- | :--- |
| Milestone 0 — Auth, Security & RBAC Gate | **FINAL PASSED** |
| Milestone 1 — Fondasi Master Data | **FINAL PASSED** |
| Milestone 2 — Master Data Sekolah, Rombel, & Guru | **FINAL PASSED** |
| Milestone 3 — Struktur Kurikulum SMP–SMA | **FINAL PASSED** |
| Milestone 4 — Penugasan Mengajar & Beban Kerja Guru | **FINAL PASSED** |
| **Milestone 5 — Penjadwalan Pelajaran & Generator Jadwal** | **FINAL PASSED** |
