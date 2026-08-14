# MODUL 05: FINALISASI JADWAL & PENERAPAN OTOMATIS (FOKUS UTAMA)
## Auto-Apply Threshold, Safe FK Cascade Cleaning, Matriks Konflik & Workflow Audit

---

## 📌 1. OVERVIEW PROSES FINALISASI OTOMATIS

Modul Finalisasi dan Penerapan Otomatis (*Auto-Finalization & Apply Engine*) bertanggung jawab untuk mempublikasikan kandidat hasil generasi ke dalam tabel utama `schedule_entries` secara aman, tanpa menimbulkan kesalahan relasi basis data (*Foreign Key Violations*) maupun menimpa jadwal dengan data konflik kritis.

```
[ User Klik "Susun Otomatis" ]
              │
              ▼
[ ScheduleGeneratorController::run ]
              │
              ▼
[ Generator Memproses 24 Strategi ]
              │
              ▼
[ usort Memilih Kandidat Terbaik (Gap Minimum & Slot Maksimum) ]
              │
              ▼
[ Evaluasi Auto-Apply Threshold: unplaced_requirements <= 1 ]
              │
              ▼
[ DeterministicGreedyScheduleGenerator::applyCandidate ]
              │
              ├──► 1. Start DB Transaction (transStart)
              ├──► 2. Safe Foreign Key Cascade Clean (conflicts -> entries)
              ├──► 3. Bulk Insert Candidate Entries ke schedule_entries
              ├──► 4. Run Audit (ScheduleConflictDetectionService)
              │       ├──► Ada CRITICAL / UNMET_HOURS? ──► Rollback (transRollback) & Throw Exception
              │       └──► 0 Critical Conflicts ─────────► Commit (transComplete) & Set Applied Status
              ▼
[ Return JSON Response: applied = true, applied_entries = 107 ]
              │
              ▼
[ Front-End SweetAlert2: "Jadwal Berhasil Disusun & Diterapkan" -> Reload ]
```

---

## 🛡️ 2. SAFE FOREIGN KEY CASCADE CLEANING

Saat kandidat baru akan diterapkan menggantikan jadwal draf lama yang tidak terkunci (`is_locked = 0`), penghapusan baris pada `schedule_entries` secara langsung akan memicu kesalahan *Foreign Key Constraint Failure* dari MySQL:

```
Cannot delete or update a parent row: a foreign key constraint fails
(`wmvaa_akademia`.`schedule_conflicts`, CONSTRAINT `schedule_conflicts_primary_entry_id_foreign`
FOREIGN KEY (`primary_entry_id`) REFERENCES `schedule_entries` (`id`))
```

### 2.1 Mekanisme Safe Cascade Cleanup (`applyCandidate` Code):
Untuk mengeliminasi kesalahan ini secara 100%, sistem menjalankan alur pembersihan 3-tahap dalam transaksi database:

```php
$this->db->transStart();

// TAHAP 1: Clear seluruh konflik yang tercatat pada versi jadwal ini
$this->db->table('schedule_conflicts')->where('schedule_version_id', $scheduleVersionId)->delete();

// TAHAP 2: Ambil daftar ID slot jadwal unlocked yang akan digantikan
$existingEntries = $this->db->table('schedule_entries')
    ->select('id')
    ->where('schedule_version_id', $scheduleVersionId)
    ->where('is_locked', 0)
    ->get()->getResultArray();

$existingIds = array_map('intval', array_column($existingEntries, 'id'));
if (!empty($existingIds)) {
    // TAHAP 3A: Hapus relasi konflik spesifik yang merujuk ke primary_entry_id atau conflicting_entry_id
    $this->db->table('schedule_conflicts')
        ->groupStart()
            ->whereIn('primary_entry_id', $existingIds)
            ->orWhereIn('conflicting_entry_id', $existingIds)
        ->groupEnd()
        ->delete();

    // TAHAP 3B: Hapus baris schedule_entries secara aman
    $this->db->table('schedule_entries')
        ->whereIn('id', $existingIds)
        ->delete();
}
```

---

## 🎯 3. AMBANG BATAS PENERAPAN OTOMATIS (*AUTO-APPLY THRESHOLD*)

Proses tombol **"Buat / Susun Otomatis"** didesain sebagai aksi *end-to-end* tanpa memerlukan langkah persetujuan manual tambahan dari user apabila hasil penjadwalan sudah memenuhi kelayakan tinggi.

### 3.1 Logika Seleksi Controller (`ScheduleGeneratorController.php`):
```php
usort($candidates, static function (array $a, array $b): int {
    $aGap = (int) ($a['unplaced_requirements'] ?? PHP_INT_MAX);
    $bGap = (int) ($b['unplaced_requirements'] ?? PHP_INT_MAX);
    if ($aGap !== $bGap) return $aGap <=> $bGap;
    return (int) ($b['total_placed_slots'] ?? 0) <=> (int) ($a['total_placed_slots'] ?? 0);
});

$result = $candidates[0];

// Kriteria Auto-Apply: kandidat terbaik memiliki sisa jam <= 1 (keterisian >= 99%)
if ((int) ($result['unplaced_requirements'] ?? 0) <= 1) {
    try {
        $applied = $this->generator->applyCandidate((int) $result['candidate_id'], $userId);
        $result['applied'] = true;
        $result['applied_entries'] = $applied['applied_entries'] ?? 0;
    } catch (\Throwable $applyError) {
        $result['applied'] = false;
        $result['apply_message'] = $applyError->getMessage();
    }
}
```

---

## ⚖️ 4. MATRIKS KONFLIK & SEVERITAS (`ScheduleConflictDetectionService`)

Setiap kandidat yang dimasukkan ke `schedule_entries` diverifikasi ulang oleh `ScheduleConflictDetectionService`. Konflik diklasifikasikan ke dalam 4 tingkat keparahan:

| Tipe Konflik (`conflict_type`) | Severitas | Efek Terhadap Penerapan Otomatis | Deskripsi & Penanganan |
| :--- | :--- | :--- | :--- |
| **`CROSS_UNIT_TEACHER_DOUBLE_BOOKING`** | **`CRITICAL`** | ❌ **MEMBLOKIR (Rollback)** | Guru mengajar di dua kelas (SMP & SMA) pada slot jam yang sama. |
| **`TEACHER_DOUBLE_BOOKING`** | **`CRITICAL`** | ❌ **MEMBLOKIR (Rollback)** | Guru mengajar di dua kelas berbeda pada unit yang sama di JP yang sama. |
| **`CLASSROOM_DOUBLE_BOOKING`** | **`CRITICAL`** | ❌ **MEMBLOKIR (Rollback)** | Kelas/Rombel mendapat dua mata pelajaran bersamaan di JP yang sama. |
| **`ROOM_DOUBLE_BOOKING`** | **`CRITICAL`** | ❌ **MEMBLOKIR (Rollback)** | Ruangan khusus/lab digunakan oleh dua kelas sekaligus pada JP yang sama. |
| **`UNMET_HOURS`** | **`HIGH`** | ❌ **MEMBLOKIR (Rollback)** | Terdapat kebutuhan jam pelajaran yang gagal ditempatkan oleh generator. |
| **`SUBJECT_MEETINGS_TOO_CLOSE`** | **`HIGH`** | ℹ️ Warning (Tidak Memblokir) | Pertemuan mata pelajaran 4 JP ditempatkan di hari berurutan tanpa jeda. |
| **`TEACHER_CLASS_SUBJECT_REPEAT`** | **`MEDIUM`** | ℹ️ Warning (Tidak Memblokir) | Guru mengajar 2 mapel berbeda di kelas yang sama pada hari yang sama. |
| **`CLASS_SUBJECT_MEETINGS_TOO_CLOSE`** | **`MEDIUM`** | ℹ️ Warning (Tidak Memblokir) | Saran jeda pedagogis antar-pertemuan mapel pada rombel. |
| **`TEACHER_DAY_LOAD_HEAVY`** | **`LOW`** | ℹ️ Information | Beban mengajar harian guru melebihi 6 JP dalam sehari. |

### 4.1 Logika Gate Audit Akhir (`applyCandidate`):
```php
$audit = (new ScheduleConflictDetectionService())->detectConflicts($scheduleVersionId);
$blockingConflicts = array_filter($audit['conflicts'] ?? [], static fn(array $conflict): bool =>
    ($conflict['severity'] ?? '') === 'CRITICAL' || ($conflict['conflict_type'] ?? '') === 'UNMET_HOURS'
);

if ($blockingConflicts !== []) {
    $this->db->transRollback();
    throw new \RuntimeException('Kandidat ditolak karena masih memiliki ' . count($blockingConflicts) . ' konflik/gap jadwal. Jalankan generate ulang setelah memperbaikinya.');
}

$this->candidateModel->update($candidateId, [
    'is_applied' => 1,
    'applied_at' => $now,
    'applied_by' => $userId,
]);

$this->db->transComplete();
```

---

## 🔄 5. WORKFLOW STATUS VERSI JADWAL (`schedule_versions`)

Status alur kerja (*Workflow Status*) jadwal dikelola melalui `ScheduleWorkflowService`:

```
[ DRAFT ]  ──► (Penyusunan & Auto-Apply Generator)
    │
    ▼
[ REVIEWED ] ──► (Ditinjau oleh Waka Kurikulum / Kepala Sekolah)
    │
    ▼
[ APPROVED / PUBLISHED ] ──► (Jadwal Resmi Aktif & Dipublikasikan ke Mobile App)
```

1. **`DRAFT`**: Status awal saat jadwal dibuat dan disajikan di Editor/Master Matrix.
2. **`REVIEWED`**: Draf yang sudah lulus audit konflik tanpa `CRITICAL` dapat diajukan ke peninjau.
3. **`APPROVED`**: Jadwal yang telah disetujui dipublikasikan ke API Mobile App (Kodular) dan TinyDB lokal pengguna (Guru/Siswa).
