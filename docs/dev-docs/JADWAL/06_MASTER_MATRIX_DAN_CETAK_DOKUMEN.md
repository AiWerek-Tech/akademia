# MODUL 06: MASTER MATRIX, RENDERING UI & CETAK DOKUMEN
## Matriks Lintas Jenjang, Interactive Drag & Drop, & Modul Cetak Dokumen PDF / Cetak Resmi

---

## 📌 1. OVERVIEW RENDERING MASTER MATRIX

Modul UI Master Matrix (`app/Views/schedules/master_matrix.php` dan `app/Views/schedules/editor.php`) menyajikan kisi-kisi jadwal pelajaran mingguan secara visual interaktif untuk seluruh kelas di bawah jenjang SMP maupun SMA.

```
+-----------------------------------------------------------------------------------+
| MASTER JADWAL MULTI-JENJANG (SMP & SMA)                                           |
+----------+-----------------------+-----------------------+------------------------+
| WAKTU/JP |       KELAS X         |       KELAS XI        |       KELAS XII        |
+----------+-----------------------+-----------------------+------------------------+
| SENIN JP1| UPACARA [LOCKED]      | UPACARA [LOCKED]      | UPACARA [LOCKED]       |
| SENIN JP2| BAHASA INGGRIS (MS)   | KESEHATAN (LW)        | MATEMATIKA (KG)        |
| SENIN JP3| PATHFINDER (KL)       | KESEHATAN (LW)        | MATEMATIKA (KG)        |
| SENIN JP4| IPA (AA)              | SENI (KL)             | K-AI (FL)              |
| ...      | ...                   | ...                   | ...                    |
+----------+-----------------------+-----------------------+------------------------+
```

---

## 🖱️ 2. INTERACTIVE DRAG & DROP MODIFICATION

Selain penyusunan otomatis oleh generator, pengguna (Waka Kurikulum / Admin) dapat melakukan penyesuaian manual slot pelajaran menggunakan fitur **Drag & Drop Active**:

### 2.1 Alur Kerja Drag & Drop:
1. **Penggeseran Kartu Pelajaran**: User menggeser kartu pelajaran dari Slot A ke Slot B pada kolom kelas yang sama atau antar-kelas.
2. **Validasi Slot Terkunci**: Kartu pelajaran pada slot `is_locked = 1` atau `FIXED` (seperti Upacara, Chapel, SID, Senam Pagi, WORKED) dikunci dan tidak dapat digeser.
3. **Pemberitahuan Konflik Real-Time**: Ketika kartu ditaruh pada slot baru, AJAX request memanggil endpoint `POST /schedules/entries/move` yang mengeksekusi `ScheduleConflictDetectionService::detectConflicts`.
4. **Respon Visual Interaktif**: Jika penempatan baru menimbulkan `CRITICAL` (misal Guru Double Booking), kartu menampilkan indikator bentrok merah dan menyajikan konfirmasi opsional.

---

## 🖨️ 3. MODUL CETAK DOKUMEN JADWAL (*PRINT ENGINE*)

Sistem WMVAA Akademia menyediakan fitur cetak resmi (*Official Document Printing*) melalui controller `App\Controllers\SchedulesController::printDoc`.

### 3.1 Opsi & Output Cetak:
1. **Cetak Master Multi-Unit**: Mencetak kisi-kisi lengkap seluruh kelas SMP dan SMA dalam format matriks bersatu untuk papan pengumuman sekolah / kantor guru.
2. **Cetak Per Kelas / Rombel**: Mencetak jadwal mingguan individual untuk ditempel di ruang kelas siswa (Format 5 Hari Kerja: Senin s.d. Jumat).
3. **Cetak Per Guru**: Mencetak jadwal mengajar pribadi per guru (menampilkan seluruh kelas dan JP yang diampu guru tersebut sepanjang minggu).

```php
public function printDoc(int $versionId): string
{
    $version = $this->versionModel->find($versionId);
    if (! $version) {
        throw new \RuntimeException('Versi jadwal tidak ditemukan.');
    }

    $db = Database::connect();
    $entries = $db->table('schedule_entries se')
        ->select('se.*, c.name as class_name, s.code as subject_code, s.name as subject_name, t.full_name as teacher_name, r.name as room_name, sd.day_name, sd.day_of_week, sds.slot_number, sds.start_time, sds.end_time')
        ->join('classrooms c', 'c.id = se.classroom_id')
        ->join('subjects s', 's.id = se.subject_id')
        ->join('teachers t', 't.id = se.teacher_id')
        ->join('rooms r', 'r.id = se.room_id', 'left')
        ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
        ->join('schedule_days sd', 'sd.id = sds.day_id')
        ->where('se.schedule_version_id', $versionId)
        ->orderBy('sd.day_of_week', 'ASC')
        ->orderBy('sds.slot_number', 'ASC')
        ->get()->getResultArray();

    return view('schedules/print_doc', [
        'version' => $version,
        'entries' => $entries,
    ]);
}
```

---

## 📱 4. SYNC TO MOBILE APP (KODULAR OFFLINE-FIRST)

Sesuai dengan **Rule System WMVAA HUB (`<RULE[user_global]>`)**:
1. Setelah jadwal dipublikasikan (`workflow_status = 'APPROVED'`), versi jadwal dicatat di sheet/tabel `CONFIG` (`jadwal_version`).
2. Aplikasi mobile Kodular (Guru & Siswa) memanggil API endpoint central router:
   ```
   ?action=sync_delta
   ```
3. Mobile app memperbarui local cache `TinyDB` sehingga jadwal mengajar guru dan jadwal belajar siswa dapat diakses **secara offline tanpa jaringan internet**.

---
*Dokumentasi Modul 01 - 06 Selesai secara Lengkap & Transparan.*
