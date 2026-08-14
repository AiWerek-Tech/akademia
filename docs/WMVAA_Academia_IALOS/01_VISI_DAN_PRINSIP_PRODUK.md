# 01 — Visi dan Prinsip Produk WMVAA Academia IALOS

## 1. Visi

**WMVAA Academia menjadi Integrated Academic & Learning Operating System yang regulation-aware, curriculum-driven, evidence-based, teacher-friendly, student-centered, auditable, dan mampu mendigitalisasi siklus pendidikan sekolah dari perencanaan sampai peningkatan mutu.**

Sistem harus membantu sekolah menjalankan kurikulum nasional secara benar tanpa menghilangkan karakter sekolah, konteks Papua Pegunungan, maupun nilai pendidikan Advent.

---

## 2. Masalah yang Diselesaikan

Sekolah umumnya memiliki data yang terfragmentasi:

- regulasi ada dalam PDF;
- KSP ada dalam Word;
- kurikulum dan JP di Excel;
- jadwal di spreadsheet;
- RPP/modul ajar dalam file terpisah;
- asesmen tersebar;
- nilai akhir diketik ulang;
- kokurikuler dan ekstrakurikuler memiliki administrasi terpisah;
- refleksi guru tidak terhubung ke data hasil belajar;
- rapor menjadi titik input, bukan hasil proses akademik;
- pimpinan sulit melihat apakah masalah berasal dari kurikulum, jadwal, proses mengajar, asesmen, atau intervensi.

IALOS mengubah semua itu menjadi **satu rantai data dan workflow yang dapat ditelusuri**.

---

## 3. Prinsip Produk

### 3.1 Regulation-aware, bukan hard-coded
Aturan nasional dimasukkan sebagai data berversi dan rule set. Custom sekolah menjadi layer adaptasi, bukan penggantian diam-diam.

### 3.2 Single source of truth
Satu konsep hanya memiliki satu sumber kebenaran. Contoh:
- beban guru → workload policy;
- JP mata pelajaran → curriculum structure/effective hours;
- jadwal → schedule version;
- capaian belajar → assessment evidence dan mastery records.

### 3.3 Workflow-first
Sistem tidak sekadar menyimpan dokumen. Sistem membimbing pekerjaan:
`Plan → Execute → Assess → Reflect → Improve`.

### 3.4 Evidence-based
Setiap keputusan akademik harus dapat ditelusuri ke bukti, data, aturan, atau approval.

### 3.5 Human-in-the-loop
Guru, wali kelas, waka kurikulum dan kepala sekolah tetap menjadi pengambil keputusan pada titik yang membutuhkan pertimbangan profesional.

### 3.6 Student-centered
Data dirancang untuk memahami perkembangan murid per tujuan pembelajaran dan pengalaman belajar, bukan sekadar menghasilkan angka.

### 3.7 Context-aware
Sistem memperhatikan:
- unit SMP/SMA;
- fase dan kelas;
- kalender akademik;
- fasilitas;
- akses internet;
- sumber daya;
- konteks lokal;
- kebijakan sekolah;
- kesiapan murid.

### 3.8 Flexible but compliant
Guru boleh menyesuaikan ATP dan rencana pembelajaran, namun sistem tetap memeriksa coverage CP/TP, beban waktu, dan aturan yang relevan.

### 3.9 Versioned and auditable
Regulasi, KSP, kurikulum, ATP, lesson plan, rubrik, jadwal dan hasil approval memiliki versi/revisi dan audit trail.

### 3.10 Offline/degraded-mode friendly
Karena kondisi jaringan dapat berubah, desain pengalaman guru harus tetap memungkinkan aktivitas utama berjalan dengan graceful degradation bila internet bermasalah.

---

## 4. Prinsip Pedagogis yang Diadopsi

Dari panduan nasional 2025, sistem harus mampu mendukung:

- pembelajaran berkesadaran, bermakna, dan menggembirakan;
- pengalaman belajar memahami, mengaplikasi, dan merefleksi;
- praktik pedagogis yang beragam;
- kemitraan pembelajaran;
- lingkungan pembelajaran fisik/virtual;
- pemanfaatan teknologi digital;
- asesmen awal, proses/formatif, dan akhir/sumatif;
- keterlibatan murid yang aktif;
- diferensiasi sebagai alat untuk keterlibatan dan kedalaman berpikir, bukan pelabelan tetap;
- refleksi dan tindak lanjut.

---

## 5. Eight-Dimension Graduate Profile Spine

Delapan dimensi profil lulusan dijadikan *cross-cutting competency spine*:

1. Keimanan dan ketakwaan kepada Tuhan Yang Maha Esa.
2. Kewargaan.
3. Penalaran kritis.
4. Kreativitas.
5. Kolaborasi.
6. Kemandirian.
7. Kesehatan.
8. Komunikasi.

Dimensi tersebut dapat dihubungkan dengan:
- intrakurikuler;
- kokurikuler;
- ekstrakurikuler;
- kegiatan karakter sekolah;
- proyek;
- asesmen/rubrik;
- portofolio;
- refleksi.

---

## 6. Posisi Kekhasan WMVAA

IALOS harus mendukung empat lapisan secara bersamaan:

```text
NATIONAL COMPLIANCE
+
ADVENTIST EDUCATION IDENTITY
+
LOCAL PAPUA PEGUNUNGAN CONTEXT
+
WMVAA SCHOOL PRIORITIES
```

Kegiatan seperti Chapel, School in Discipleship, Follow The Bible, Family Educare, Doa 777, Work Education dan Pathfinder tidak harus dipaksa menjadi mata pelajaran. Masing-masing diklasifikasikan sesuai fungsi akademik sebenarnya.

---

## 7. Non-Goals

IALOS tidak boleh:
- menggantikan profesionalisme guru dengan AI;
- mengubah seluruh aplikasi melalui big-bang rewrite;
- menyimpan seluruh isi buku berhak cipta tanpa dasar izin/lisensi;
- menjadikan kepatuhan administratif sebagai tujuan utama pembelajaran;
- menganggap angka nilai sebagai satu-satunya representasi kompetensi;
- membuat satu workflow kaku untuk semua mata pelajaran.

---

## 8. North Star Metrics

Keberhasilan tidak hanya diukur dari jumlah fitur, tetapi dari:

- persentase TP yang memiliki evidence;
- keterhubungan jadwal → lesson execution;
- kecepatan guru menyiapkan pertemuan;
- persentase tindak lanjut asesmen formatif;
- jumlah input ganda yang berhasil dihilangkan;
- ketepatan compliance struktur kurikulum;
- keterlacakan hasil rapor ke evidence;
- waktu administrasi guru yang dihemat;
- persentase KSP yang berbasis data aktual;
- kemampuan pimpinan melakukan perbaikan berbasis indikator.
