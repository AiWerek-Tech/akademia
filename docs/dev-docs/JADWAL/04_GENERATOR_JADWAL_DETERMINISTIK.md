# MODUL 04: GENERATOR JADWAL OTOMATIS (DETERMINISTIC GREEDY ENGINE)
## Pra-syarat Kapasitas, 24 Strategi Variatif, Blok Pedagogis (2+2+1), & Fallback Slot 1-JP

---

## 📌 1. OVERVIEW ENGINE GENERATOR

Generator Jadwal Otomatis WMVAA Akademia diimplementasikan dalam class `App\Services\DeterministicGreedyScheduleGenerator`.

Engine ini menggunakan pendekatan **Deterministic Bounded Multi-Strategy Greedy Algorithm**. Karena algoritma greedy murni dapat "terjebak" (*stuck*) akibat urutan pemrosesan awal yang mengambil slot legal yang dibutuhkan mapel berikutnya, engine ini menjalankan **24 variasi strategi pemetaan** secara paralel dan memilih kandidat terbaik (*Best-Fit Selection*).

---

## ⚙️ 2. ALUR EKSEKUSI GENERATOR (`generate`)

```
[ Panggil generate(versionId, userId, strategy) ]
                        │
                        ▼
    [ 1. Memuat Day Slots & Filter Ruang Aktif Unit ]
                        │
                        ▼
    [ 2. Memuat Requirements & Fixed Routine Activities ]
                        │
                        ▼
    [ 3. Capacity Preflight Check (Gagal jika slot < beban) ]
                        │
                        ▼
    [ 4. Pengurutan Requirements Berdasarkan 24 Strategi ]
                        │
                        ▼
    [ 5. Loop Penempatan per Requirement ]
         ├──► Break ke Blok Pedagogis: buildJpBlocks(4 JP => [2, 2])
         ├──► Pencarian Pasangan Slot Berurutan (2 JP Kontigu)
         ├──► Validasi Bentrok Guru, Kelas, Ruang, & Lintas Unit
         └──► Fallback Penempatan Slot Tunggal (1-JP Fallback Loop)
                        │
                        ▼
    [ 6. Hitung Score & Simpan Kandidat ke Database ]
```

---

## 🔀 3. 24 STRATEGI VARIASI PENGURUTAN (*MULTI-STRATEGY SEARCH*)

Dalam metode `generate()`, variabel `$strategy` (0 hingga 23) mengubah urutan prioritas pemrosesan `requirements`. Hal ini menjamin eksplorasi ruang solusi yang luas:

### 3.1 Faktor Pengurutan Strategi:
1. **Slack Kapasitas Kelas ($S_{\text{class}}$)**:
   $$S_{\text{class}} = \text{Capacity}_{\text{effective}} - \text{Required}_{\text{class}}$$
   Kelas dengan sisa ruang paling ketat diproses terlebih dahulu.
2. **Prioritas Guru Beban Tinggi (*Busy Multi-Class Teachers*)**:
   Pada strategi `$strategy % 3 === 0`, guru yang mengajar di banyak kelas/jam ditarik ke urutan paling awal untuk mengamankan slot mengajar sebelum slot tersebut terisi oleh guru tunggal.
3. **Ukuran Jam Pelajaran (`required_weekly_hours`)**:
   Mata pelajaran dengan beban JP terbesar (misal: 4 JP) diproses sebelum mapel 2 JP / 1 JP.
4. **Variasi Arah Kelas & ID Requirement**:
   Strategi ganjil/genap membalik urutan ID kelas dan ID requirement ($a \Leftrightarrow b$).

```php
usort($requirements, static function (array $a, array $b) use ($capacityByClassroom, $requiredByClassroom, $teacherLoadCount, $strategy): int {
    $aClass = (int) $a['classroom_id'];
    $bClass = (int) $b['classroom_id'];
    $aSlack = ($capacityByClassroom[$aClass] ?? 0) - ($requiredByClassroom[$aClass] ?? 0);
    $bSlack = ($capacityByClassroom[$bClass] ?? 0) - ($requiredByClassroom[$bClass] ?? 0);
    if ($aSlack !== $bSlack) return $aSlack <=> $bSlack;

    // Prioritaskan guru beban tinggi pada cabang strategi % 3 === 0
    if ($strategy % 3 === 0) {
        $aTLoad = $teacherLoadCount[(int) $a['teacher_id']] ?? 0;
        $bTLoad = $teacherLoadCount[(int) $b['teacher_id']] ?? 0;
        if ($aTLoad !== $bTLoad) return $bTLoad <=> $aTLoad;
    }

    if ($aClass !== $bClass) return $strategy % 2 === 0 ? ($bClass <=> $aClass) : ($aClass <=> $bClass);
    $aHours = (float) $a['required_weekly_hours'];
    $bHours = (float) $b['required_weekly_hours'];
    if ($aHours !== $bHours) return $bHours <=> $aHours;
    return $strategy % 4 < 2
        ? ((int) $a['id'] <=> (int) $b['id'])
        : ((int) $b['id'] <=> (int) $a['id']);
});
```

---

## 🧱 4. PEMBAGIAN BLOK PEDAGOGIS & FALLBACK SLOT 1-JP

### 4.1 Pembagian Blok (`buildJpBlocks`)
Untuk menjaga kualitas pembelajaran pedagogis (mencegah mapel 4 JP dihabiskan dalam 1 hari sekaligus), jam mingguan dipecah menjadi blok berukuran maksimal 2 JP:
- **1 JP** $\rightarrow$ `[1]`
- **2 JP** $\rightarrow$ `[2]`
- **3 JP** $\rightarrow$ `[2, 1]`
- **4 JP** $\rightarrow$ `[2, 2]`
- **5 JP** $\rightarrow$ `[2, 2, 1]`

```php
private function buildJpBlocks(int $hours): array
{
    $blocks = [];
    while ($hours >= 2) {
        $blocks[] = 2;
        $hours -= 2;
    }
    if ($hours === 1) $blocks[] = 1;
    return $blocks;
}
```

### 4.2 Graceful Fallback Single-Slot Search (Slot 1-JP)
Jika suatu blok 2-JP gagal menemukan 2 slot berurutan di hari manapun akibat terpecahnya sisa slot, algoritma **tidak membatalkan requirement tersebut**. Sebaliknya, algoritma mengaktifkan **Fallback 1-JP Placement**:

```php
if (!$placedBlock) {
    for ($sub = 0; $sub < $blockLength; $sub++) {
        $subPlaced = false;
        foreach ($allDayNumbers as $dayOfWeek) {
            $dayList = $slotsByDay[$dayOfWeek] ?? [];
            foreach ($dayList as $slot) {
                $slotId = (int) $slot['id'];
                $slotNum = (int) $slot['slot_number'];

                // 1. Cek okupansi kelas & guru
                if (isset($classOccupied["{$slotId}_{$classId}"]) || isset($teacherOccupied["{$slotId}_{$teacherId}"])) continue;

                // 2. Cek aturan ketersediaan guru & kesibukan lintas unit
                if (!$this->teacherAvailabilityService->isTeacherAvailable($teacherId, $academicPeriodId, $dayOfWeek, $slotNum)
                    || $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy($teacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId) !== []) {
                    continue;
                }

                // 3. Alokasikan ke slot tunggal ini
                $placedEntries[] = [ ... ];
                $classOccupied["{$slotId}_{$classId}"] = true;
                $teacherOccupied["{$slotId}_{$teacherId}"] = true;
                $placedForThisReq += 1;
                $subPlaced = true;
                break 2;
            }
        }
        if (!$subPlaced) break;
    }
}
```
Mekanisme ini menjamin keterisian slot hingga 100% (*0 Unplaced Requirements*).
