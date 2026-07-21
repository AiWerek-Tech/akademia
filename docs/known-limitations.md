# Known Limitations — Milestone 2

Berikut adalah daftar batasan yang diketahui pada Milestone 2 (Master Data Akademik Terpadu):

1. **PDF Export Tidak Didukung**:
   - Ekspor saat ini hanya mendukung format Excel `.xlsx`.
   - Ekspor PDF ditandai sebagai keterbatasan sistem (tidak diimplementasikan karena dependensi mPDF/dompdf tidak terpasang di server).
   
2. **Cakupan Pengujian Browser**:
   - Pengujian browser otomatis (`browser_subagent`) hanya memverifikasi login admin dan pemuatan dashboard utama.
   - Pengujian fungsional halaman lainnya dan peran pengguna lainnya (guru, wakasek) diverifikasi sepenuhnya lewat unit/feature tests di PHPUnit.

3. **Routing Filter Default**:
   - Beberapa route Milestone 2 (`/teachers`, `/subjects`, dll.) tidak secara eksplisit didaftarkan di filter `auth` dalam file `Config/Filters.php`. Keamanan dipastikan lewat pengecekan `has_permission()` di setiap method controller.
