# MODUL 01: KURIKULUM & MATRIKS JAM PELAJARAN (JP)
## Single Source of Truth Total JP, Mapel Wajib & Pilihan, serta Sinkronisasi Requirement

---

## 📌 1. OVERVIEW & PRINSIP SINGLE SOURCE OF TRUTH

Sistem Penjadwalan WMVAA Akademia mewajibkan **Struktur Kurikulum (`curriculum_structures`)** dan **Verifikasi Mata Pelajaran Pilihan (`elective_offerings`)** sebagai *Single Source of Truth* untuk seluruh perhitungan beban Jam Pelajaran (JP).

```
[ curriculum_versions ]
        │
        ├──► [ curriculum_structures ] (Mapel Wajib, Intrakurikuler, P5, Effective Hours)
        │
        └──► [ elective_offerings ] (Mapel Pilihan Fase F SMA: status is_approved = 1)
                │
                ▼
      [ teaching_assignments ] ──► (assigned_weekly_hours disinkronkan otomatis)
                │
                ▼
    [ ScheduleRequirementSyncService ]
                │
                ▼
     [ schedule_requirements ] (Tabel Kebutuhan Jam Pelajaran untuk Generator)
```

---

## 📊 2. KATEGORI MATA PELAJARAN & PERHITUNGAN JP

### 2.1 Kategori Mata Pelajaran (`subjects.category`)
1. **`WAJIB`**: Mata pelajaran pokok kurikulum nasional (misal: Bahasa Indonesia, Matematika, Agama, PKN).
2. **`P5`**: Projek Penguatan Profil Pelajar Pancasila.
3. **`PILIHAN`**: Mata pelajaran kelompok pilihan Kurikulum Merdeka Fase F (Kelas XI & XII SMA).
4. **`RUTIN`**: Kegiatan rutin sekolah (Upacara, Chapel, SID, Senam Pagi, Kunci Jam Akhir).

### 2.2 Rumus Jam Efektif Mingguan (`effective_weekly_hours`)
Total JP mingguan untuk setiap entri kurikulum dihitung menggunakan rumus:

$$\text{Effective Weekly Hours} = \text{Intracurricular Hours} + \text{P5 Hours}$$

Di mana:
- **Intracurricular Hours**: Alokasi jam tatap muka kelas mingguan.
- **P5 Hours**: Alokasi jam projek mingguan.
- Jam efektif ini dicatat secara resmi di `curriculum_structures.effective_weekly_hours`.

---

## 🔍 3. ALGORITMA SINKRONISASI KEBUTUHAN (`ScheduleRequirementSyncService`)

Service `ScheduleRequirementSyncService::syncFromAssignments($scheduleVersionId)` bertanggung jawab mengubah penugasan guru (`teaching_assignments`) menjadi kebutuhan jadwal (`schedule_requirements`).

### 3.1 Langkah-Langkah Sinkronisasi:
1. **Identifikasi Versi Jadwal & Lingkup Unit**:
   ```php
   $version = $this->versionModel->find($scheduleVersionId);
   $assignmentVersionId = $version['assignment_version_id'] ?? null;
   $unitId = $version['unit_id'] ?? null;
   $periodId = (int)$version['academic_period_id'];
   ```
2. **Query Penugasan Mengajar Aktif**:
   Menarik `teaching_assignments` di mana `status = 'ACTIVE'`, `deleted_at IS NULL`, dan `unit_id` sesuai dengan unit sekolah jadwal.
3. **Verifikasi Mata Pelajaran Pilihan (SMA Fase F - Kelas XI & XII)**:
   Untuk mapel kategori `PILIHAN` di SMA (Grade $\ge 11$), sistem melakukan verifikasi ke tabel `elective_offerings`:
   ```php
   if (($subject['category'] ?? '') === 'PILIHAN' && ($unitLevel === 'SMA' || $gradeNumber >= 11)) {
       $approvedOffering = $this->db->table('elective_offerings eo')
           ->join('elective_periods ep', 'ep.id = eo.elective_period_id')
           ->join('academic_periods ap', 'ap.academic_year_id = ep.academic_year_id')
           ->where('ap.id', $periodId)
           ->where('ep.target_grade', $gradeNumber)
           ->where('eo.subject_id', $subjectId)
           ->where('eo.is_approved', 1)
           ->get()->getRowArray();

       if (! $approvedOffering) {
           // Jika tidak disetujui, hapus requirement dan abaikan penugasan
           $this->deleteRequirementWithCandidates((int) $existing['id']);
           continue;
       }
   }
   ```
4. **Pembaruan / Buat Entri Requirement (`schedule_requirements`)**:
   Sistem menyinkronkan data `required_weekly_hours`, `preferred_room_id`, dan `required_room_type`.
5. **Pembersihan Entri Usang (*Stale Requirements Clean*)**:
   Requirements yang penugasannya sudah dihapus atau tidak aktif secara otomatis dibersihkan secara cascading (`deleteRequirementWithCandidates`).

---

## 🗄️ 4. SKEMA TABEL TERKAIT KURIKULUM & REQUIREMENT

### 4.1 Tabel `curriculum_structures`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Identifier utama struktur kurikulum |
| `curriculum_version_id` | BIGINT | Foreign key ke `curriculum_versions` |
| `grade_level_id` | INT | Tingkat kelas (7, 8, 9, 10, 11, 12) |
| `subject_id` | INT | Foreign key ke `subjects` |
| `effective_weekly_hours` | DECIMAL(4,2) | Total JP efektif per minggu |
| `preferred_room_id` | INT (NULLable) | Ruang laboratorium / khusus pilihan |
| `status` | VARCHAR(20) | `ACTIVE` / `INACTIVE` |
| `deleted_at` | DATETIME (NULLable) | Soft delete timestamp |

### 4.2 Tabel `schedule_requirements`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Identifier utama kebutuhan jadwal |
| `schedule_version_id` | BIGINT | Foreign key ke `schedule_versions` |
| `teaching_assignment_id` | BIGINT | Foreign key ke `teaching_assignments` |
| `classroom_id` | INT | Kelas / Rombel target |
| `subject_id` | INT | Mata pelajaran |
| `teacher_id` | INT | Guru pengampu utama |
| `second_teacher_id` | INT (NULLable) | Guru pendamping (Team Teaching) |
| `required_weekly_hours` | DECIMAL(4,2) | Total jam yang wajib disolusikan generator |
