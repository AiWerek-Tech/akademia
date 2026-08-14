# Audit Kesiapan Produksi Penjadwalan — 2 Agustus 2026

## Keputusan

**BLOCKED untuk merge/deployment produksi.** Perilaku scheduling telah memenuhi gate teknis pilot pada clone UAT, tetapi worktree berisi perubahan pengguna yang bercampur dan belum bersih/terkomit terpisah. CSP juga masih memakai `unsafe-inline` sebagai compatibility bridge. Tidak ada merge, deploy, auto-apply, atau pekerjaan Milestone 6.

## Gate yang Lulus

- Root PHPUnit langsung final: 219 test, 733 assertion, 0 skipped/incomplete/warning/error/failure, 06:32.662, 110 MB.
- Focused scheduling/fingerprint/team policy: 8 test, 39 assertion; generator/history regression: 2 test, 36 assertion.
- Template konfigurasi staging: seluruh mandatory check `akademia:scheduling-check` PASS, exit 0. Environment lokal dipulihkan ke development dan sengaja tetap gagal gate produksi.
- Team teaching aman dalam posisi feature flag OFF; input tim/guru kedua ditolak sebelum sinkronisasi, generate, edit, atau import.
- Dua audit konflik bersamaan menghasilkan 16 finding aktif dengan fingerprint unik tanpa duplikasi.
- Dua apply kandidat bersamaan: satu sukses, satu ditolak 409; satu revision history dan satu status applied.
- UAT anonim: sync 38 requirement, output deterministik, kandidat parsial dan stale ditolak, kandidat lengkap diterapkan eksplisit, status tetap DRAFT, unmet 0, blocker 0.
- Browser: 162 skenario, 18 login berhasil, 93 authorized 2xx, 51 unauthorized/cross-unit 403, 0 status tak terduga, 0 CSP violation, 0 network failure.
- Jadwal nyata tetap DRAFT: SMP 16 konflik pedagogis non-CRITICAL, SMA 11 konflik non-CRITICAL, keduanya 0 blocker dan 0 unmet.

## Laporan 16 Konflik Jadwal SMP

Data guru tidak disalin ke repository. Identitas sumber direferensikan dengan assignment ID.

| No | Kode / severity | Kelas dan mapel | Waktu terkait | Assignment | Akar masalah | Koreksi yang disarankan |
|---:|---|---|---|---:|---|---|
| 1 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VII / Pendidikan Pancasila | Senin–Selasa | 83 | spacing | pindahkan salah satu pertemuan ke Kamis/Jumat |
| 2 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VII / Seni, Budaya, dan Prakarya | Senin–Selasa | 64 | spacing | regenerasi dari source assignment dan review hari |
| 3 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VII / Bahasa Inggris | Kamis–Jumat | 89 | spacing | pindahkan satu blok ke Senin/Selasa |
| 4 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VIII / Matematika | Senin–Selasa | 78 | spacing | pertahankan blok 2 JP, beri satu hari jeda |
| 5 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VIII / Pathfinder | Selasa–Rabu | 66 | spacing | pindahkan pertemuan kedua ke Kamis/Jumat |
| 6 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | VIII / Pendidikan Pancasila | Rabu–Kamis | 84 | spacing | pindahkan satu pertemuan ke Senin |
| 7 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | IX / Informatika | Senin–Selasa | 57 | block pattern | jadikan satu blok 2 JP atau beri jeda |
| 8 | CLASS_SUBJECT_MEETINGS_TOO_CLOSE / MEDIUM | IX / Koding dan AI | Selasa–Rabu | 92 | block pattern | jadikan satu blok 2 JP atau pindahkan ke Kamis |
| 9 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VIII / Matematika (req 251) | Senin–Selasa | 78 | block pattern | dua blok 2 JP pada hari tidak berurutan |
| 10 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | IX / Informatika (req 230) | Senin–Selasa | 57 | block pattern | satu blok 2 JP berurutan |
| 11 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VII / Pendidikan Pancasila (req 256) | Senin–Selasa | 83 | block pattern | satu blok 2 JP atau hari berjeda |
| 12 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VII / Seni, Budaya, dan Prakarya (req 237) | Senin–Selasa | 64 | block pattern | satu blok 2 JP atau hari berjeda |
| 13 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VIII / Pathfinder (req 239) | Selasa–Rabu | 66 | block pattern | satu blok 2 JP atau hari berjeda |
| 14 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | IX / Koding dan AI (req 265) | Selasa–Rabu | 92 | block pattern | satu blok 2 JP atau hari berjeda |
| 15 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VIII / Pendidikan Pancasila (req 257) | Rabu–Kamis | 84 | block pattern | satu blok 2 JP atau hari berjeda |
| 16 | SUBJECT_MEETINGS_TOO_CLOSE / HIGH | VII / Bahasa Inggris (req 262) | Kamis–Jumat | 89 | block pattern | susun dua blok 2 JP pada hari berjeda |

## Dua Gap Kandidat Lama

Kandidat lama #465 meninggalkan requirement 230 sebanyak 2 JP dan requirement 229 sebanyak 1 JP. Keduanya berasal dari assignment 57/56, kapasitas mingguan masih tersedia, tidak memiliki aturan unavailable, kekurangan ruang, atau blocker lintas unit. Akar masalahnya adalah urutan heuristik kandidat lama. Generator bounded 24 strategi pada clone terbaru menemukan kandidat lengkap 38/38 requirement dan 109 entry; kandidat lama tetap tidak boleh diterapkan.

## Blocker Tersisa

1. Worktree belum bersih dan perubahan closure tidak aman untuk dicampur dengan perubahan pengguna dalam lima commit yang diminta.
2. CSP kompatibel tetapi `script-src` dan `style-src` masih mengizinkan `unsafe-inline`; gate mengeluarkan WARNING.
3. UAT memakai clone anonim lokal, bukan staging HTTPS/server produksi dan belum merupakan pilot pengguna nyata.

Produksi hanya dapat dibuka setelah patch direview/staged secara terpisah, worktree bersih, CSP nonce/event-listener selesai atau risiko disetujui, gate dijalankan ulang pada server HTTPS target, dan pilot sekolah dipantau.
