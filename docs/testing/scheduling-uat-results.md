# Hasil UAT Scheduling

Database: clone lokal anonim `wmvaa_akademia_sched_uat_20260802`. Seluruh user dinonaktifkan dan identitas guru/siswa diubah sebelum UAT.

- Requirement sync: 38 synced, 0 created, 38 updated.
- Generate strategi 0 dua kali: proyeksi entry identik.
- Kandidat parsial: ditolak.
- Kandidat complete: 38/38 requirement, 109 entry, diterapkan eksplisit.
- Stale revision: ditolak sebelum apply.
- Audit akhir: 5 finding pedagogis, 0 CRITICAL, 0 UNMET_HOURS.
- Workflow tetap DRAFT; tidak ada auto-apply atau finalisasi.
- Waktu run end-to-end 14.894 detik; peak memory 8 MB.

Apply pertama menemukan FK conflict history yang memblokir replacement. Perbaikan mempertahankan history sambil melepas referensi entry lama; focused regression dan UAT ulang lulus.
