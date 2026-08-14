# MODUL 02: KEGIATAN RUTIN SEKOLAH & TEMPLATE SLOT WAKTU
## Inisialisasi Slot Hari, Penguncian Otomatis, & Kalkulasi Kapasitas Efektif

---

## 📌 1. OVERVIEW KEGIATAN RUTIN & TEMPLATE SLOT

Setiap unit sekolah (SMP dan SMA) memiliki struktur hari dan jam belajar mingguan yang dikonfigurasi melalui **Profil Perencanaan Kurikulum (`curriculum_planning_settings`)**.

Slot waktu terbagi menjadi dua tipe utama (`slot_type`):
1. **`LESSON`**: Slot jam pelajaran efektif yang dapat diisi oleh generator untuk mata pelajaran intrakurikuler / pilihan.
2. **`FIXED` / `ROUTINE`**: Slot waktu yang terkunci otomatis untuk Kegiatan Rutin Sekolah (Upacara, Chapel, Discipleship/SID, Senam Pagi, Istirahat, dan Kunci Jam Akhir).

---

## 🕒 2. STRUKTUR HARI & WAKTU BELAJAR (`schedule_days` & `schedule_day_slots`)

Service `ScheduleSetupService::initialize($scheduleVersionId, $unitId)` membuat susunan slot hari dan jam belajar berdasarkan konfigurasi unit:

```
[ Senin ]  ──► JP1 (Upacara / FIXED)  ──► JP2..JP9 (LESSON)
[ Selasa ] ──► JP1 (SID / FIXED)       ──► JP2..JP9 (LESSON)
[ Rabu ]   ──► JP1 (Chapel / FIXED)    ──► JP2..JP9 (LESSON)
[ Kamis ]  ──► JP1..JP9 (LESSON)
[ Jumat ]  ──► JP1 (Senam / FIXED)     ──► JP2..JP5 (LESSON) ──► JP6..JP7 (WORKED / Kunci Jam Akhir)
```

### 2.1 Kegiatan Rutin Khusus Sekolah:
- **Upacara Bendera**: Setiap Senin JP 1 (`07.30 - 08.10`).
- **School in Discipleship (SID)**: Setiap Selasa JP 1 (`07.30 - 08.10`).
- **Chapel Worship**: Setiap Rabu JP 1 (`07.30 - 08.10`).
- **Senam Pagi Kebugaran**: Setiap Jumat JP 1 (`07.30 - 08.10`).
- **WORKED (Kunci Jam Akhir Jumat)**: Jumat JP 6 & JP 7 (`11.05 - 12.25`) dikunci secara otomatis untuk seluruh kelas SMA/SMP guna kegiatan kebersihan/ekstrakurikuler dan kepulangan lebih awal.

---

## 🧮 3. KALKULASI KAPASITAS EFEKTIF BELAJAR (*LESSON CAPACITY*)

Sebelum Generator Jadwal mencoba menyusun slot pelajaran, generator melakukan pemeriksaan kapasitas matematika pra-syarat (*Capacity Preflight Check*) untuk memastikan bahwa slot efektif yang tersedia cukup menampung total beban JP kelas.

### 3.1 Rumus Kapasitas Efektif per Kelas ($C_{\text{effective}}$)

$$C_{\text{base}} = \sum \text{Slot LESSON Mingguan}$$

$$C_{\text{effective}} = C_{\text{base}} - N_{\text{fixed}}$$

Di mana:
- $C_{\text{base}}$: Total slot waktu pelajaran mingguan yang dibuat untuk versi jadwal (misal: 43 slot).
- $N_{\text{fixed}}$: Jumlah slot kegiatan rutin yang mengunci kelas tersebut (misal: Upacara, Chapel, SID, Senam, WORKED = 6 slot).
- $C_{\text{effective}}$: Jumlah slot efektif yang benar-benar dapat diisi pelajaran (misal: $43 - 6 = 37 \text{ JP}$).

### 3.2 Preflight Gate Code (`DeterministicGreedyScheduleGenerator.php`):
```php
$baseLessonCapacity = array_sum(array_map('count', $slotsByDay));
$requiredByClassroom = [];
foreach ($requirements as $req) {
    $classId = (int) $req['classroom_id'];
    $hours = (int) ceil((float) $req['required_weekly_hours']);
    $requiredByClassroom[$classId] = ($requiredByClassroom[$classId] ?? 0) + $hours;
}

foreach ($requiredByClassroom as $classId => $requiredHours) {
    $availableHours = $baseLessonCapacity - ($fixedCountByClassroom[$classId] ?? 0);
    if ($requiredHours > $availableHours) {
        $classLabel = $classNames[$classId] ?? ('Kelas #' . $classId);
        $capacityIssues[] = "{$classLabel}: kebutuhan {$requiredHours} JP, slot efektif {$availableHours} JP, kurang " . ($requiredHours - $availableHours) . ' JP';
    }
}

if ($capacityIssues !== []) {
    throw new \RuntimeException('Susun otomatis dihentikan karena kapasitas tidak cukup: ' . implode('; ', $capacityIssues));
}
```

---

## 🗄️ 4. SKEMA TABEL KEGIATAN RUTIN & FIXED ACTIVITIES

### 4.1 Tabel `schedule_fixed_activities`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Identifier utama kegiatan tetap |
| `schedule_version_id` | BIGINT | Foreign key ke `schedule_versions` |
| `classroom_id` | INT | Kelas target yang dikunci |
| `day_slot_id` | BIGINT | Slot waktu hari dan JP yang dikunci |
| `activity_name` | VARCHAR(150) | Nama kegiatan (Upacara, Chapel, WORKED, dll) |
| `created_at` | DATETIME | Timestamp pembuatan |
