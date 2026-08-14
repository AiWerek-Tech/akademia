# Modernisasi UI/UX — 26 Juli 2026

## Cakupan

Modernisasi dilakukan pada halaman autentikasi, identitas brand, layout admin, navigasi, konteks unit/periode, dashboard, form, kartu, tabel, dropdown, modal, notifikasi, empty state, dark mode, print style, dan tampilan mobile.

## Perubahan

- Logo monogram buku/akademik menggantikan ikon generik dan dipakai konsisten pada login, sidebar, favicon, serta empty state.
- Login dan ganti password memakai layout responsif, label form eksplisit, show/hide password, status submit, pesan error inline, serta indikator kekuatan password.
- Font utama disatukan ke Plus Jakarta Sans.
- Avatar eksternal dihapus dan diganti inisial pengguna agar cepat, privat, dan tidak bergantung layanan pihak ketiga.
- Sidebar memiliki pencarian menu, shortcut `/`, penyimpanan status collapsed, dan drawer mobile yang dapat ditutup melalui backdrop atau pemilihan menu.
- Pemilih unit dan periode tersedia di desktop maupun mobile.
- Title dan breadcrumb modul perencanaan diturunkan otomatis dari route bila controller tidak memasok judul.
- Komponen Bootstrap distandardisasi melalui `academia-ui.css`: tombol, input, select, kartu, tabel, badge, pagination, dropdown, modal, SweetAlert, dark mode, serta print.
- Tabel dapat difokuskan dengan keyboard dan memiliki label region untuk pembaca layar.
- Empty state tabel dikenali otomatis dan tetap terbaca pada layar kecil.
- Loading state submit diterapkan secara global pada form tanpa dialog konfirmasi.
- Bootstrap Icons dimuat pada layout admin; sebelumnya sejumlah ikon tampil kosong.

## Bug yang Ditemukan Saat Uji Visual

1. Drawer mobile memakai class JavaScript `active`, tetapi stylesheet lama hanya mengenali `show`.
2. Dropdown konteks mobile keluar dari viewport.
3. Halaman Jadwal gagal total karena `UnitScopeService::getCurrentUnit()` belum tersedia.
4. Modul Kurikulum, Penugasan, dan Beban Kerja memakai judul/breadcrumb default `Dashboard`.
5. Beberapa nama ikon Lucide tidak tersedia pada versi library yang digunakan.

Semua masalah tersebut telah diperbaiki.

## Verifikasi

- Login diuji pada viewport 1440×900, 721×856, dan 390×844.
- Dashboard, master guru, kurikulum, import master, serta navigasi/konteks mobile diperiksa secara visual.
- Lima belas halaman utama diuji dalam sesi terautentikasi dan tidak menghasilkan halaman exception.
- Console sesi browser baru: tidak ada error atau warning pada halaman dashboard, kurikulum, jadwal, dan import master.
- Pemeriksaan aksesibilitas elemen terlihat pada import master: tidak ada gambar tanpa `alt`, tombol tanpa nama, atau input tanpa label. Satu tautan tanpa nama berasal dari toolbar debug CodeIgniter, bukan UI aplikasi.
- PHP lint lulus untuk seluruh file PHP yang disentuh pada modernisasi ini.
- JavaScript syntax check lulus untuk `academia-ui.js` dan `auth-ui.js`.
- SVG logo valid sebagai XML.
- Regression test keamanan route, scope jadwal, dan health: 10 test, 34 assertions, seluruhnya lulus.

## Audit Dark Mode

Audit tambahan dilakukan setelah ditemukan chevron `<select>` putih berukuran besar dan bertumpuk dengan teks pada navbar.

Perbaikan:

- Semua `form-select` memakai satu SVG chevron 14×9 px, `no-repeat`, posisi kanan 0,85 rem, dan padding kanan 2,5 rem.
- Warna chevron dark mode diubah menjadi slate agar jelas tanpa menyilaukan.
- Kontras label form, placeholder, option, card, tabel, empty state, modal, dropdown, pagination, tombol sekunder, breadcrumb, dan user menu diselaraskan.
- Menu aktif memperoleh latar indigo gelap, bukan gradient putih light mode.
- Hero dashboard mempertahankan aksen brand di dark mode.
- Ikon toggle diperbaiki agar benar-benar berubah antara bulan dan matahari setelah Lucide mengganti node ikon.
- Dropdown konteks mobile dan empty state tabel diverifikasi kembali pada dark mode.

Bukti:

- Select navbar: satu background image, `no-repeat`, ukuran 14×9 px, padding kanan 40 px.
- Kontras label konteks: 11,95:1.
- Kontras teks select: 15,55:1.
- Kontras teks pendukung: 6,92:1.
- Console browser: tidak ada error atau warning.

## Gate Produksi

UI aplikasi sudah siap untuk input data dan UAT. Sebelum publikasi internet:

1. Set `CI_ENVIRONMENT=production` agar toolbar debug tidak tampil.
2. Gunakan HTTPS dan secure cookie.
3. Vendor-kan Bootstrap, Bootstrap Icons, SweetAlert, Lucide, dan font atau siapkan kebijakan CDN/SRI yang disetujui.
4. Lakukan UAT dengan data nyata karena tabel padat dan matriks jadwal belum dapat diuji penuh pada database yang masih kosong.
5. Jalankan audit otomatis WCAG/axe serta uji browser target sekolah.

Klaim “tanpa bug 100%” tidak dapat dibuktikan secara absolut. Status yang dapat dibuktikan adalah seluruh skenario UI yang disebutkan di atas lulus, dengan gate publik yang tersisa terdokumentasi.
