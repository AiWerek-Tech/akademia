# WMVAA Akademia — Teaching Assignments & Scheduling

## 1. Teaching Assignments (`/assignments`)

### 1.1 Concept

Penugasan mengajar mendistribusikan beban mengajar guru ke mapel, rombel, dan unit. Hasilnya berupa SK Pembagian Tugas.

### 1.2 Workflow

```
draft → validated → reviewed → approved → locked
```

### 1.3 Key Features

| Fitur | Deskripsi |
|---|---|
| **Create Version** | Buat versi penugasan baru |
| **Store Assignment** | Tambah item penugasan (guru → mapel → rombel) |
| **Store Duty** | Tugas tambahan (piket, bimbingan) |
| **Auto Assign** | Auto-distribusi penugasan berdasarkan kebijakan |
| **Matrix View** | Tampilan matrix guru × mapel × rombel |
| **Workflow** | Validate → Review → Approve → Lock |
| **Import** | Import penugasan dari Excel |
| **Export** | Export matrix penugasan |
| **SK Documents** | Generate SK Pembagian Tugas (集体 & individual) |
| **Clone Version** | Salin versi sebelumnya |

### 1.4 SK Documents

| Type | Route | Deskripsi |
|---|---|---|
| **Collective SK** | `GET /assignments/:id/documents/sk` | SK pembagian tugas seluruh guru |
| **Teacher SK** | `GET /assignments/:id/documents/teacher/:tid` | SK individual per guru |

### 1.5 Import Pipeline

```
Download Template → Fill Excel → Upload → Validation → Preview → Apply → Rollback (opsional)
```

### 1.6 Tables

| Table | Fungsi |
|---|---|
| `assignment_versions` | Versi penugasan |
| `teaching_assignments` | Item penugasan |
| `teaching_assignment_groups` | Grup penugasan |
| `assignment_import_batches` | Batch import |
| `assignment_import_rows` | Baris import |
| `assignment_revision_history` | Riwayat revisi |
| `assignment_validation_results` | Hasil validasi |

---

## 2. Workloads (`/workloads`)

### 2.1 Concept

Beban kerja guru dihitung berdasarkan penugasan, JP mingguan, dan kebijakan workload.

### 2.2 Dashboard

Menampilkan:
- Total JP per guru
- Perbandingan dengan minimum/maximum
- Status beban (underload/overload/optimal)

### 2.3 Workload Policies (`/workloads/policies`)

| Field | Deskripsi |
|---|---|
| `academic_period_id` | Periode akademik |
| `unit_id` | Unit |
| `minimum_teaching_hours` | JP minimum mengajar |
| `target_total_hours` | JP target |
| `maximum_total_hours` | JP maksimum |

### 2.4 Features

| Aksi | Deskripsi |
|---|---|
| **View Dashboard** | Lihat ringkasan beban kerja |
| **Create Policy** | Buat kebijakan workload |
| **Recalculate** | Hitung ulang workload |
| **Export** | Export laporan workload |

---

## 3. Scheduling System (`/schedules`)

### 3.1 Concept

Sistem penjadwalan otomatis menggunakan **Deterministic Greedy Algorithm** dengan constraint satisfaction.

### 3.2 Workflow

```
draft → validated → reviewed → approved → published
```

### 3.3 Schedule Components

#### 3.3.1 Schedule Version

| Field | Deskripsi |
|---|---|
| `academic_period_id` | Periode akademik |
| `unit_id` | Unit |
| `name` | Nama versi jadwal |
| `workflow_status` | Status workflow |

#### 3.3.2 Schedule Entries

Setiap entry = satu blok jadwal:

| Field | Deskripsi |
|---|---|
| `schedule_version_id` | Versi jadwal |
| `day_code` | Hari (MON-SUN) |
| `slot_number` | Nomor slot JP |
| `subject_id` | Mapel |
| `teacher_id` | Guru |
| `classroom_id` | Rombel |
| `room_id` | Ruang |

#### 3.3.3 Fixed Activities

Kegiatan tetap yang terjadwal otomatis (upacara, chapel, SID, dll).

### 3.4 Automatic Schedule Generator

**Deterministic Greedy Schedule Generator** (`DeterministicGreedyScheduleGenerator`):

1. **Input**: Curriculum structures, teacher assignments, constraints
2. **Processing**: Greedy algorithm dengan scoring
3. **Output**: Multiple candidate schedules

#### Algorithm Steps:

1. Sort assignments by constraint difficulty (most constrained first)
2. For each assignment:
   - Find all available slots (day × period)
   - Score each slot based on:
     - Teacher availability
     - Room availability
     - Classroom capacity
     - Constraint weights
   - Select highest-scoring slot
3. Generate candidate schedules
4. Present for review

### 3.5 Conflict Detection

**ScheduleConflictDetectionService** memeriksa:

| Conflict Type | Deskripsi |
|---|---|
| **Teacher Double-Booking** | Guru dijadwalkan 2 kelas bersamaan |
| **Classroom Double-Booking** | Rombel punya 2 mapel bersamaan |
| **Room Double-Booking** | Ruang dipakai 2 kelas bersamaan |
| **Teacher Unavailable** | Guru tidak tersedia di slot tersebut |

### 3.6 Schedule Editor

Grid-based editor untuk manual adjustment:

```
        │ JP 1 │ JP 2 │ JP 3 │ ... │ JP 9 │
Senin   │      │      │      │     │      │
Selasa  │      │      │      │     │      │
Rabu    │      │      │      │     │      │
Kamis   │      │      │      │     │      │
Jumat   │      │      │      │     │      │
```

### 3.7 Teacher Substitutions

| Fitur | Deskripsi |
|---|---|
| **Create Substitution** | Guru pengganti untuk slot tertentu |
| **Toggle Active** | Aktifkan/nonaktifkan substitusi |
| **Analyze Repair** | Cari opsi repair jika konflik |
| **Apply Repair** | Terapkan repair solution |

### 3.8 Schedule Reports

| Report | Route | Deskripsi |
|---|---|---|
| **Classroom Report** | `GET /schedules/:id/reports/classroom/:cid` | Jadwal per rombel |
| **Teacher Report** | `GET /schedules/:id/reports/teacher/:tid` | Jadwal per guru |
| **Unit Report** | `GET /schedules/:id/reports/unit/:uid` | Jadwal per unit |
| **Multi-Unit Report** | `GET /schedules/:id/reports/multi-unit` | Laporan lintas unit |

### 3.9 Schedule Import

Import jadwal dari Excel ke dalam schedule version.

### 3.10 Schedule Constraints

| Constraint | Deskripsi | Weight |
|---|---|---|
| **Teacher Availability** | Slot yang tersedia untuk guru | Configurable |
| **Classroom Availability** | Slot yang tersedia untuk rombel | Configurable |
| **Room Availability** | Slot yang tersedia untuk ruang | Configurable |
| **Subject Preference** | Preferensi penempatan mapel | Configurable |
| **Workload Balance** | Distribusi beban merata | Configurable |

---

## 4. Duty Schedules (`/duty-schedules`)

### 4.1 Deskripsi

Jadwal piket guru untuk tugas-tugas non-mengajar.

### 4.2 Features

| Aksi | Deskripsi |
|---|---|
| **Generate** | Generate jadwal piket otomatis |
| **Store** | Simpan jadwal manual |
| **Clear** | Hapus semua jadwal |
| **Print** | Cetak jadwal piket |
| **Delete** | Hapus item jadwal |

---

## 5. Attendances Monitoring (`/attendances`)

### 5.1 Deskripsi

Monitoring absensi kelas dari perspektif admin (superadmin).

### 5.2 Features

| Aksi | Deskripsi | Permission |
|---|---|---|
| **Index** | Daftar sesi absensi | `attendances.view` |
| **Show** | Detail sesi absensi | `attendances.view` |
| **Export** | Export data absensi | `attendances.admin` |
| **Verify** | Verifikasi sesi absensi | `attendances.admin` |
| **Reopen** | Buka kembali sesi absensi | `attendances.admin` |
| **Bulk Verify** | Verifikasi massal | `attendances.admin` |
| **Print Unit Report** | Cetak laporan unit | `attendances.view` |
| **Print Blank Sheet** | Cetak form kosong | `attendances.view` |

---

## 6. Services

| Service | Fungsi |
|---|---|
| `AssignmentWorkflowService` | Workflow penugasan |
| `AssignmentValidationService` | Validasi bisnis penugasan |
| `AssignmentMatrixService` | Matrix penugasan |
| `AssignmentImportService` | Import penugasan |
| `AssignmentDocumentService` | Generate SK documents |
| `TeacherWorkloadCalculationService` | Hitung beban kerja |
| `TeamTeachingPolicyService` | Kebijakan team teaching |
| `DeterministicGreedyScheduleGenerator` | Generate jadwal otomatis |
| `ScheduleConflictDetectionService` | Deteksi konflik |
| `ScheduleConflictPresentationService` | Presentasi konflik |
| `ScheduleScoringService` | Scoring slot jadwal |
| `ScheduleCapacityService` | Kapasitas penjadwalan |
| `ScheduleWorkflowService` | Workflow jadwal |
| `ScheduleImportService` | Import jadwal |
| `ScheduleExportService` | Export jadwal |
| `ScheduleSetupService` | Setup awal jadwal |
| `ScheduleSourceAuditService` | Audit sumber jadwal |
| `ScheduleRequirementSyncService` | Sync requirement |
| `RoomAvailabilityService` | Ketersediaan ruang |
| `ClassroomAvailabilityService` | Ketersediaan rombel |
| `TeacherAvailabilityService` | Ketersediaan guru |
| `TeacherScheduleReadService` | Read jadwal guru |
| `TeacherScheduleSubstitutionService` | Substitusi guru |
| `TeacherScheduleSubstitutionManagementService` | Kelola substitusi |
| `TeacherSubstitutionScheduleRepairService` | Repair substitusi |
| `TeacherDutyScheduleService` | Jadwal piket |
| `AttendanceService` | Absensi |
| `AuditService` | Audit trail |
