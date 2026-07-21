/**
 * SPMB WMVAA - Context-Aware Help Articles Dictionary
 * Contains user manuals mapped to current URL paths and roles.
 */
const HelpArticles = {
    // -------------------------------------------------------------
    // PENDAFTAR (CANDIDATES) GUIDES
    // -------------------------------------------------------------
    'pendaftar/dashboard': {
        title: 'Panduan Dashboard Pendaftar',
        description: 'Pusat kendali dan informasi kelulusan pendaftaran Anda.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="info" class="me-1 text-primary"></i> Status Pendaftaran</h6>
                <p>Status Anda ditampilkan di kartu utama bagian atas:</p>
                <ul>
                    <li><span class="badge bg-warning text-dark">Draft</span>: Anda masih mengisi formulir. Selesaikan semua langkah agar data masuk ke panitia.</li>
                    <li><span class="badge bg-primary">Berkas Direview</span>: Data Anda sudah dikirim dan sedang diverifikasi oleh Operator.</li>
                    <li><span class="badge bg-info text-dark">Lolos Verifikasi</span>: Berkas fisik/digital Anda sah dan disetujui.</li>
                    <li><span class="badge bg-success">Lulus</span>: Selamat! Anda diterima sebagai calon siswa baru.</li>
                </ul>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="credit-card" class="me-1 text-primary"></i> Panduan Pembayaran</h6>
                <p>Ada dua metode pembayaran yang didukung sistem:</p>
                <ol>
                    <li><strong>Bayar Online (Otomatis)</strong>: Klik tombol <em>Bayar Online</em>. Anda dapat membayar via QRIS, Virtual Account bank transfer, atau E-Wallet. Pembayaran akan terverifikasi secara otomatis oleh sistem dalam beberapa detik.</li>
                    <li><strong>Konfirmasi Manual</strong>: Lakukan transfer ke salah satu rekening bank resmi sekolah di tombol <em>Cara Bayar</em>, lalu klik <em>Konfirmasi Bayar</em> untuk mengunggah foto bukti transfer. Panitia akan memverifikasi berkas Anda secara manual.</li>
                </ol>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="printer" class="me-1 text-primary"></i> Cetak Kartu & Formulir</h6>
                <p>Setelah status Anda dinyatakan lulus atau berkas selesai diverifikasi, tombol cetak kartu bukti pendaftaran akan aktif di dashboard Anda.</p>
            </div>
        `
    },
    'pendaftar/daftar': {
        title: 'Panduan Pengisian Formulir Pendaftaran',
        description: 'Langkah pengisian data pendaftaran sesuai Dapodik.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="clipboard-list" class="me-1 text-primary"></i> 9 Langkah Pendaftaran</h6>
                <p>Formulir terbagi menjadi 9 langkah berurutan. Data Anda akan disimpan otomatis saat Anda mengklik tombol <em>"Lanjutkan ke Langkah Berikutnya"</em>.</p>
                <ul>
                    <li><strong>Langkah 1-3</strong>: Data Pribadi, Kontak, dan NIK Calon Siswa.</li>
                    <li><strong>Langkah 4-5</strong>: Data Ayah Kandung, Ibu Kandung, dan Wali (jika ada).</li>
                    <li><strong>Langkah 6</strong>: Data Periodik (Tinggi/Berat badan, jarak tempuh sekolah).</li>
                    <li><strong>Langkah 7</strong>: Pilihan Jurusan / Program Studi.</li>
                    <li><strong>Langkah 8</strong>: Unggah dan validasi kelengkapan dokumen.</li>
                    <li><strong>Langkah 9</strong>: Review, pernyataan kebenaran data, dan finalisasi pengiriman berkas.</li>
                </ul>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="lock" class="me-1 text-primary"></i> Pengeditan Data</h6>
                <p>Selama status pendaftaran masih dalam tahap <strong>Draft</strong>, Anda bebas kembali ke langkah sebelumnya untuk mengubah data. Namun, jika Anda sudah menekan tombol <strong>Finalisasi/Submit</strong> pada langkah 9, data Anda akan dikunci dan tidak dapat diubah kembali kecuali dibuka khusus oleh Admin/Operator.</p>
            </div>
        `
    },
    'pendaftar/dokumen': {
        title: 'Panduan Unggah Dokumen / Berkas',
        description: 'Memahami status berkas fisik dan unggahan digital.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="file-text" class="me-1 text-primary"></i> Dua Status Kelengkapan</h6>
                <p>Sistem sekarang memisahkan dua kondisi berkas agar sesuai proses lapangan:</p>
                <ul>
                    <li><strong>Tersedia fisik</strong>: panitia sudah melihat/menerima berkas yang dibawa ke sekolah.</li>
                    <li><strong>Sudah diunggah</strong>: file digital sudah diupload ke aplikasi.</li>
                </ul>
                <p>Jika Anda belum sempat upload, berkas fisik tetap bisa dicatat oleh panitia sebagai tersedia. Upload digital dapat menyusul sesuai arahan sekolah.</p>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="alert-triangle" class="me-1 text-primary"></i> Aturan Upload</h6>
                <p>Perhatikan ketentuan berkas berikut agar lolos verifikasi panitia:</p>
                <ol>
                    <li>Ukuran setiap file <strong>maksimal 2 MB</strong>.</li>
                    <li>Gambar harus terlihat jelas, tidak kabur, dan teks di dalamnya terbaca dengan baik.</li>
                    <li>Jika dokumen ditolak, Anda akan melihat tanda silang merah dengan keterangan penolakan dari panitia. Silakan unggah ulang dokumen perbaikan yang diminta.</li>
                </ol>
            </div>
        `
    },
    'pendaftar/account-card': {
        title: 'Panduan Cetak Kartu Akun',
        description: 'Kartu berisi username, password awal, dan cara akses aplikasi.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="badge" class="me-1 text-primary"></i> Fungsi Kartu Akun</h6>
                <p>Kartu akun dipakai sebagai pegangan pendaftar/wali untuk masuk kembali ke aplikasi SPMB. Simpan kartu ini dengan baik karena memuat username dan password awal.</p>
                <ul>
                    <li>Cetak kartu dari dashboard pendaftar atau dari data pendaftar oleh panitia.</li>
                    <li>Gunakan alamat aplikasi yang tercantum pada kartu.</li>
                    <li>Jika password lupa, minta bantuan panitia/admin untuk reset akun.</li>
                </ul>
            </div>
        `
    },

    // -------------------------------------------------------------
    // OPERATOR / PANITIA GUIDES
    // -------------------------------------------------------------
    'operator/registrants': {
        title: 'Panduan Data Pendaftar & Verifikasi Berkas',
        description: 'Monitoring pendaftar, dokumen fisik, dan unggahan digital.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="users" class="me-1 text-primary"></i> Data Pendaftar</h6>
                <p>Gunakan halaman ini untuk mencari pendaftar, membuka detail formulir, meninjau status, dan mencetak dokumen pendukung.</p>
                <ul>
                    <li><strong>Verifikasi ketersediaan fisik</strong>: checklist ketika berkas sudah dibawa ke sekolah dan diperiksa panitia.</li>
                    <li><strong>Status unggahan</strong>: menunjukkan apakah file digital sudah diupload dan lolos/tolak review.</li>
                    <li><strong>Checklist cetak</strong>: menampilkan dua kolom status agar panitia dan pendaftar tahu mana yang sudah tersedia dan mana yang belum diunggah.</li>
                </ul>
            </div>
        `
    },
    'operator/walk-in': {
        title: 'Panduan Pendaftaran Walk-in / Dibantu Panitia',
        description: 'Mendaftarkan calon siswa yang datang langsung ke sekolah.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="user-plus" class="me-1 text-primary"></i> Alur Walk-in</h6>
                <ol>
                    <li>Panitia membuka menu <strong>Pendaftaran Walk-in</strong>.</li>
                    <li>Isi data dasar calon siswa dan wali berdasarkan berkas lapangan.</li>
                    <li>Sistem membuat akun pendaftar dan nomor pendaftaran.</li>
                    <li>Cetak kartu akun lalu serahkan ke siswa/wali agar mereka bisa melengkapi data atau upload berkas dari rumah.</li>
                </ol>
                <p>Fitur ini tersedia untuk panitia/operator yang diberi izin membuat pendaftar, dan tidak tersedia untuk bendahara.</p>
            </div>
        `
    },
    'operator/dapodik': {
        title: 'Panduan Validasi Dapodik',
        description: 'Memantau kesiapan data siswa untuk kebutuhan Dapodik.',
        content: `
            <div class="help-section">
                <p>Menu ini digunakan untuk melihat kelengkapan data penting calon siswa, menandai kesiapan Dapodik, mencetak FPD, dan mengekspor data sesuai hak akses role.</p>
            </div>
        `
    },

    // -------------------------------------------------------------
    // BENDAHARA / OPERATOR GUIDES
    // -------------------------------------------------------------
    'bendahara/dashboard': {
        title: 'Panduan Dashboard Keuangan',
        description: 'Ringkasan penerimaan, piutang, kas, dan statistik pembayaran.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="bar-chart-3" class="me-1 text-primary"></i> Statistik Keuangan</h6>
                <p>Dashboard menampilkan penerimaan hari ini, total dana terkumpul, sisa piutang, pembayaran pending, dan ringkasan pos biaya. Untuk role pemantau seperti kepala sekolah, halaman ini bersifat read-only.</p>
                <p>Jika akun memiliki akses lebih dari satu jenjang, gunakan pilihan scope untuk melihat SMP, SMA, atau semua jenjang.</p>
            </div>
        `
    },
    'bendahara/invoices': {
        title: 'Panduan Manajemen Pembayaran & Tagihan',
        description: 'Modul tagihan, kwitansi, dan pemantauan pembayaran.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="search" class="me-1 text-primary"></i> Pencarian & Filter Tagihan</h6>
                <p>Gunakan bar pencarian di bagian atas untuk menemukan tagihan berdasarkan:</p>
                <ul>
                    <li>Nomor Invoice (misal: <code>INV-2026...</code>)</li>
                    <li>Nomor Pendaftaran Calon Siswa (misal: <code>SPMB-...</code>)</li>
                    <li>Nama Lengkap Calon Siswa</li>
                </ul>
                <p>Filter status membantu Anda memisahkan tagihan yang <em>Belum Bayar (Unpaid)</em>, <em>Menunggu Verifikasi (Pending)</em>, dan <em>Lunas (Paid)</em>.</p>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="receipt" class="me-1 text-primary"></i> Tagihan vs Kwitansi</h6>
                <p><strong>Tagihan</strong> dipakai untuk invoice yang belum lunas atau dibayar sebagian. <strong>Kwitansi</strong> hanya untuk pembayaran yang sudah tercatat/terverifikasi. Kwitansi tersedia dalam tiga jenis: semua pembayaran, khusus pendaftaran/MPLS, dan khusus perlengkapan sekolah.</p>
                <p>Tanggal pada kwitansi mengikuti <strong>tanggal pembayaran</strong> yang diinput bendahara, bukan tanggal saat dokumen dicetak.</p>
            </div>
        `
    },
    'bendahara/invoices/detail': {
        title: 'Panduan Detail Invoice & Penerimaan Tunai',
        description: 'Langkah menginput pembayaran kasir dan mencetak kuitansi.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="dollar-sign" class="me-1 text-primary"></i> Pembayaran Tunai (Cash di Tempat)</h6>
                <p>Jika pendaftar menyetor uang tunai langsung ke kasir sekolah:</p>
                <ol>
                    <li>Klik tombol <strong>Input Pembayaran</strong> di sebelah kanan atas detail invoice.</li>
                    <li>Masukkan nominal yang diterima (sistem mendukung pembayaran cicilan / parsial).</li>
                    <li>Pilih metode pembayaran: <strong>Tunai (Kasir Sekolah)</strong>.</li>
                    <li>Klik Simpan. Tagihan otomatis dipotong dan status diperbarui secara real-time.</li>
                </ol>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="printer" class="me-1 text-primary"></i> Kuitansi Pembayaran resmi</h6>
                <p>Setiap transaksi pembayaran yang berhasil dicatat dapat dicetak sebagai kwitansi resmi. Pastikan tanggal pembayaran diisi sesuai tanggal uang benar-benar diterima.</p>
            </div>
        `
    },
    'bendahara/quick-payment': {
        title: 'Panduan Catat Pembayaran Cepat',
        description: 'Input pembayaran loket untuk bendahara/petugas kasir.',
        content: `
            <div class="help-section">
                <p>Cari pendaftar berdasarkan invoice, nomor pendaftaran, nama, NISN, atau NIK. Isi nominal, alokasi item, metode, dan tanggal pembayaran. Tanggal tersebut akan menjadi tanggal resmi pada kwitansi.</p>
            </div>
        `
    },
    'bendahara/daily-recap': {
        title: 'Panduan Rekap Harian',
        description: 'Rekap penerimaan berdasarkan tanggal pembayaran.',
        content: `
            <div class="help-section">
                <p>Rekap harian memakai tanggal pembayaran, bukan tanggal cetak atau tanggal verifikasi. Gunakan filter tanggal untuk mencocokkan penerimaan fisik/kas dengan laporan aplikasi.</p>
            </div>
        `
    },
    'bendahara/reports': {
        title: 'Panduan Laporan Keuangan',
        description: 'Laporan read-only/ekspor sesuai hak akses.',
        content: `
            <div class="help-section">
                <p>Halaman laporan menampilkan data tagihan, penerimaan, status pembayaran, dan ekspor Excel. Kepala sekolah atau panitia pemantau dapat membaca laporan tanpa akses mengubah transaksi.</p>
            </div>
        `
    },
    'bendahara/cash-drawers': {
        title: 'Panduan Kas Harian',
        description: 'Kontrol laci kas bendahara dan riwayat transaksi tunai.',
        content: `
            <div class="help-section">
                <p>Bendahara dapat membuka, memantau, menambah transaksi kas, dan menutup laci kas harian. Role read-only hanya dapat melihat ringkasan dan riwayat kas sesuai scope jenjang.</p>
            </div>
        `
    },

    // -------------------------------------------------------------
    // ADMIN GUIDES
    // -------------------------------------------------------------
    'admin/dashboard': {
        title: 'Panduan Dashboard Kepala Sekolah/Admin',
        description: 'Command center untuk statistik global dan per jenjang.',
        content: `
            <div class="help-section">
                <p>Dashboard menampilkan statistik pendaftar, verifikasi, seleksi, kuota, Dapodik, dan aktivitas panitia. Jika akun diberi akses global/lebih dari satu jenjang, tombol scope dapat dipakai untuk melihat semua jenjang, SMP, atau SMA.</p>
                <p>Kepala sekolah difokuskan untuk pemantauan dan laporan read-only; aksi teknis tetap mengikuti permission role.</p>
            </div>
        `
    },
    'admin/access': {
        title: 'Panduan Mode & Hak Akses',
        description: 'Mengatur role, permission, dan menu yang tampil di dashboard.',
        content: `
            <div class="help-section">
                <p>Gunakan pencarian dan filter grup permission untuk memilih hak akses tanpa scroll panjang. Permission yang memiliki halaman aktif akan otomatis muncul sebagai menu sesuai role, sedangkan permission teknis tetap dibatasi untuk admin global.</p>
                <ul>
                    <li><strong>Pilih semua menu</strong>: mencentang permission menu yang tersedia.</li>
                    <li><strong>Pilih terlihat</strong>: mencentang permission sesuai hasil filter/pencarian.</li>
                    <li><strong>Kosongkan terlihat</strong>: menghapus centang permission yang sedang tampil.</li>
                </ul>
            </div>
        `
    },
    'admin/settings': {
        title: 'Panduan Konfigurasi Sistem Utama',
        description: 'Pusat pengaturan global, kop sekolah, payment gateway, dan rekening.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="settings" class="me-1 text-primary"></i> Pengaturan Tab Global</h6>
                <ul>
                    <li><strong>Profil & Tahun Ajaran</strong>: Ganti Nama Sekolah, slogan, dan logo sekolah yang tampil di portal publik/cetak kartu.</li>
                    <li><strong>Kontak & Peta</strong>: Isi nomor telepon resmi, email, alamat fisik sekolah, serta koordinat Google Maps untuk halaman beranda.</li>
                    <li><strong>Payment Gateway</strong>: Integrasikan Midtrans. Masukkan mode (Sandbox untuk testing/Production untuk live), Server Key, Client Key, serta biaya layanan online.</li>
                    <li><strong>Metode Bayar Manual</strong>: Atur nomor rekening bank sekolah (BSI/Bank Mandiri/DKI dll), status aktif/nonaktif, serta cara petunjuk transfer. Anda juga bisa mengunggah barcode QRIS statis sekolah di sini.</li>
                </ul>
                <p>Pengaturan yang bersifat kop sekolah/tanda tangan bendahara harus disimpan sesuai scope jenjang agar data SMP tidak muncul pada SMA dan sebaliknya.</p>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="info" class="me-1 text-primary"></i> Catatan Biaya Online</h6>
                <p>Midtrans mengenakan biaya layanan kepada sekolah per transaksi sukses (VA Rp4.000, GoPay 2%, QRIS 0.7%). Masukkan nilai **Biaya Layanan Online** (misalnya Rp4.000) di tab Payment Gateway untuk membebankan biaya tersebut kepada calon siswa.</p>
            </div>
        `
    },
    'admin/fee-types': {
        title: 'Panduan Pengaturan Jenis Biaya',
        description: 'Pengelolaan struktur komponen biaya pendaftaran.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="plus-circle" class="me-1 text-primary"></i> Menambah Komponen Biaya</h6>
                <p>Anda dapat membuat berbagai jenis tagihan (seperti Formulir Pendaftaran, Sumbangan Gedung, Seragam):</p>
                <ul>
                    <li><strong>Wajib Pembayaran Sebelum Formulir</strong>: Centang ini jika tagihan tersebut wajib dilunasi pendaftar sebelum mereka diizinkan mengisi data formulir pendaftaran.</li>
                    <li><strong>Kirim Otomatis (Auto Invoice)</strong>: Centang ini agar sistem otomatis membuatkan invoice tagihan saat akun siswa pertama kali dibuat.</li>
                </ul>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="refresh-cw" class="me-1 text-primary"></i> Penyesuaian Tarif Otomatis</h6>
                <p>Jika Anda mengedit/mengubah nominal suatu komponen biaya pendaftaran, sistem secara cerdas akan **memperbarui nominal tagihan belum dibayar** milik calon siswa secara otomatis. Cache token payment gateway lama juga akan di-reset agar nominal tagihan di layar pembayaran Midtrans ikut ter-update.</p>
            </div>
        `
    },
    'admin/academic-years': {
        title: 'Panduan Manajemen Tahun Pelajaran',
        description: 'Pengaturan gelombang pendaftaran dan tahun akademik aktif.',
        content: `
            <div class="help-section">
                <h6 class="fw-bold text-dark"><i data-lucide="calendar" class="me-1 text-primary"></i> Periode Tahun Pelajaran</h6>
                <p>Menu ini digunakan untuk mengatur tahun ajaran aktif sistem (misalnya: <code>2026/2027</code>). Data pendaftar, rekap tagihan, dan file dokumen diarsipkan berdasarkan tahun ajaran aktif ini.</p>
            </div>
            <hr>
            <div class="help-section mt-3">
                <h6 class="fw-bold text-dark"><i data-lucide="layers" class="me-1 text-primary"></i> Gelombang Pendaftaran</h6>
                <p>Buat gelombang pendaftaran (Gelombang 1, Gelombang 2, dst) beserta tanggal pembukaan dan penutupan masing-masing gelombang. Di luar tanggal aktif gelombang, portal pendaftaran siswa otomatis akan terkunci dan tidak dapat diakses calon siswa.</p>
            </div>
        `
    }
};

// Global fallback article if page is not mapped
const DefaultHelpArticle = {
    title: 'Panduan Penggunaan Sistem',
    description: 'Bantuan penggunaan modul sistem SPMB WMVAA.',
    content: `
        <div class="help-section">
            <h6 class="fw-bold text-dark"><i data-lucide="help-circle" class="me-1 text-primary"></i> Butuh Bantuan?</h6>
            <p>Selamat datang di Pusat Bantuan SPMB WMVAA. Laci bantuan ini menyajikan panduan kontekstual sesuai halaman yang sedang Anda buka saat ini.</p>
            <p>Silakan jelajahi menu-menu dashboard di sidebar sebelah kiri. Setiap halaman memiliki petunjuk penggunaan yang disesuaikan dengan peran Anda.</p>
        </div>
        <hr>
        <div class="help-section mt-3">
            <h6 class="fw-bold text-dark"><i data-lucide="phone-call" class="me-1 text-primary"></i> Kontak Panitia</h6>
            <p>Jika Anda mengalami kendala teknis atau masalah pembayaran, harap hubungi staf IT sekolah atau Administrator utama sistem melalui informasi kontak yang tertera di bagian dashboard utama.</p>
        </div>
    `
};
