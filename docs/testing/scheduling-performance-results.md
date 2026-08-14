# Hasil Performance Scheduling

Dataset clone nyata, PHP 8.2.20, MariaDB lokal.

| Operasi | Durasi | Query | Slowest query | Peak memory |
|---|---:|---:|---:|---:|
| Requirement sync | 244.99 ms | 193 | 25.51 ms | 8 MB |
| Generator satu strategi | 719.79 ms | 666 | 11.96 ms | 8 MB |
| Conflict detection | 344.12 ms | 507 | 110.09 ms | 8 MB |
| Laporan kelas | 5.06 ms | 6 | 1.29 ms | 8 MB |
| Laporan guru | 0.74 ms | 1 | 0.55 ms | 8 MB |
| Laporan unit | 4.29 ms | 8 | 0.67 ms | 8 MB |
| Full root suite final | 06:32.662 | n/a | n/a | 110 MB |

Batas pilot: sync <2 s, satu strategi <2 s, 24 strategi <30 s, audit <2 s, report server <1 s, peak operasi <128 MB. Query generator/audit harus dipantau; target optimasi berikutnya <400 dan <300 query tanpa mengubah determinisme.
