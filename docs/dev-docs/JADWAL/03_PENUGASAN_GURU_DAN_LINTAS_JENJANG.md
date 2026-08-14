# MODUL 03: PENUGASAN GURU & INTEGRITAS LINTAS JENJANG
## Dual Assignment, Teacher Availability, & Matriks Kesibukan Lintas Unit (SMP vs SMA)

---

## 📌 1. OVERVIEW PENUGASAN GURU LINTAS JENJANG

Dalam ekosistem sekolah WMVAA (SMP Advent Sogokmo dan SMA Advent Sogokmo), beberapa guru mengajar secara lintas jenjang (*Cross-Unit Shared Teachers*). Sebagai contoh:
- **Pak Yohanis Ondy**: Mengajar Kesehatan & Pathfinder di SMP sekaligus PKN di SMA.
- **Bu Sarlota Yansip**: Mengajar SBDP & Pathfinder di SMP sekaligus Bahasa Indonesia di SMA.
- **Bu Maria Sinaga**: Mengajar Bahasa Inggris & Pathfinder di SMP sekaligus Bahasa Inggris di SMA.
- **Pak Frengky Lokobal**: Mengajar K-AI di SMP sekaligus Informatika & K-AI di SMA.

Penyusunan jadwal otomatis wajib menjamin bahwa **seorang guru tidak pernah mengajar di dua kelas (baik di SMP maupun SMA) pada slot waktu yang sama** (*Zero Cross-Unit Double Booking*).

---

## 🏫 2. DETEKSI KESIBUKAN LINTAS UNIT (`getCrossUnitTeacherScheduleOccupancy`)

Service `TeacherAvailabilityService::getCrossUnitTeacherScheduleOccupancy` memindai jadwal guru di unit lawan secara real-time.

### 2.1 Alur Pemindaian Kesibukan Guru:
1. **Dapatkan Unit Lawan**: Jika jadwal yang sedang disusun adalah SMA (`unit_id = 2`), maka unit lawan yang diperiksa adalah SMP (`unit_id = 1`), dan sebaliknya.
2. **Query Slot Jadwal Terpasang di Unit Lawan**:
   ```php
   public function getCrossUnitTeacherScheduleOccupancy(
       int $teacherId,
       int $academicPeriodId,
       int $dayOfWeek,
       int $slotNumber,
       int $currentScheduleVersionId
   ): array {
       // Cari versi jadwal aktif di unit lawan pada periode akademik yang sama
       $otherVersion = $this->db->table('schedule_versions sv')
           ->select('sv.id')
           ->where('sv.academic_period_id', $academicPeriodId)
           ->where('sv.id !=', $currentScheduleVersionId)
           ->where('sv.unit_id !=', $currentUnitId)
           ->get()->getRowArray();

       if (!$otherVersion) return [];

       // Cari entri jadwal mengajar guru tersebut pada slot hari & JP yang sama
       return $this->db->table('schedule_entries se')
           ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
           ->join('schedule_days sd', 'sd.id = sds.day_id')
           ->where('se.schedule_version_id', $otherVersion['id'])
           ->groupStart()
               ->where('se.teacher_id', $teacherId)
               ->orWhere('se.second_teacher_id', $teacherId)
           ->groupEnd()
           ->where('sd.day_of_week', $dayOfWeek)
           ->where('sds.slot_number', $slotNumber)
           ->get()->getResultArray();
   }
   ```

3. **Pengintegrasian ke Generator Jadwal**:
   Saat Generator mencoba menempatkan slot pelajaran (baik pada blok 2-JP maupun *fallback* 1-JP), sistem mengecek ketersediaan lintas unit ini:
   ```php
   if ($this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy(
           $teacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId) !== []) {
       // Guru sedang mengajar di unit lain! Abaikan slot ini.
       continue;
   }
   ```

---

## 🚫 3. ATURAN KETERSEDIAAN GURU (`teacher_availability_rules`)

Selain bentrok jadwal mengajar, guru juga dapat memiliki konfigurasi ketersediaan pribadi (misal: Izin Khusus, Dinas Luar, atau Pengabdian Organisasi).

### 3.1 Aturan Ketersediaan (`availability_status`)
- **`AVAILABLE`**: Guru bersedia mengajar pada slot waktu tersebut.
- **`UNAVAILABLE`**: Guru berhalangan hadir pada hari/slot waktu tertentu. Generator akan secara otomatis menghindari slot tersebut.

### 3.2 Pemindaian di Service (`isTeacherAvailable`):
```php
public function isTeacherAvailable(int $teacherId, int $academicPeriodId, int $dayOfWeek, int $slotNumber): bool
{
    $rule = $this->ruleModel
        ->where('teacher_id', $teacherId)
        ->where('academic_period_id', $academicPeriodId)
        ->groupStart()
            ->where('day_of_week', $dayOfWeek)
            ->orWhere('day_of_week IS NULL')
        ->groupEnd()
        ->groupStart()
            ->where('slot_number', $slotNumber)
            ->orWhere('slot_number IS NULL')
        ->groupEnd()
        ->first();

    if ($rule && (string)$rule['availability_status'] === 'UNAVAILABLE') {
        return false;
    }

    return true;
}
```

---

## 🗄️ 4. SKEMA TABEL TERKAIT PENUGASAN GURU

### 4.1 Tabel `teaching_assignments`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Identifier penugasan mengajar |
| `assignment_version_id` | BIGINT | Foreign key ke `assignment_versions` |
| `unit_id` | INT | Unit sekolah penugasan (1=SMP, 2=SMA) |
| `teacher_id` | INT | Guru pengampu utama |
| `classroom_id` | INT | Kelas target |
| `subject_id` | INT | Mata pelajaran yang diampu |
| `curriculum_structure_id` | BIGINT | Foreign key ke `curriculum_structures` |
| `assigned_weekly_hours` | DECIMAL(4,2) | Beban jam mengajar mingguan |
| `status` | VARCHAR(20) | `ACTIVE` / `INACTIVE` |

### 4.2 Tabel `teacher_availability_rules`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Identifier aturan ketersediaan |
| `teacher_id` | INT | Guru yang diatur |
| `academic_period_id` | INT | Periode akademik |
| `day_of_week` | INT (NULLable) | Hari spesifik (1-7) atau NULL (semua hari) |
| `slot_number` | INT (NULLable) | Slot JP spesifik (1-9) atau NULL (semua JP) |
| `availability_status` | VARCHAR(20) | `AVAILABLE` / `UNAVAILABLE` |
