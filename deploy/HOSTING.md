# Deployment ke Hosting

Aplikasi menggunakan dua konfigurasi yang sengaja dipisahkan:

- `.env` di komputer pengembangan untuk XAMPP/local.
- `deploy/production.env.example` sebagai template hosting.

Pemisahan ini mencegah kredensial database hosting tertimpa ketika source code
diunggah kembali dan mencegah aplikasi salah mengaktifkan mode development di
internet.

## Instalasi pertama

1. Pilih PHP 8.2 atau lebih baru pada panel hosting.
2. Upload source code tanpa file `.env` lokal.
3. Salin `deploy/production.env.example` menjadi `.env` di root aplikasi.
4. Ganti seluruh nilai `CHANGE_ME` dengan database hosting yang sebenarnya.
5. Sesuaikan `app.baseURL`:
   - subfolder: `https://app.wmvaa.id/wmvaa-akademia/`
   - document root langsung ke aplikasi: `https://app.wmvaa.id/`
6. Pastikan folder `writable` dan seluruh subfoldernya dapat ditulis oleh proses
   PHP hosting. Jangan memberikan izin tulis publik pada `app`, `system`,
   `vendor`, atau file `.env`.
7. Jalankan migrasi dan pemeriksaan produksi:

   ```text
   php spark migrate --all
   php spark akademia:scheduling-check
   ```

Deployment hanya boleh dilanjutkan bila pemeriksaan berakhir dengan `PASSED`.
Peringatan CSP `unsafe-inline` adalah compatibility warning yang sudah diketahui;
setiap baris `FAIL` tetap harus diperbaiki.

## Upload pembaruan berikutnya

- Jangan upload/overwrite `.env` hosting.
- Jangan upload isi lokal `writable/session`, `writable/cache`, log, export, atau
  backup.
- Setelah upload, jalankan migrasi, bersihkan cache, lalu jalankan gate produksi:

  ```text
  php spark migrate --all
  php spark cache:clear
  php spark akademia:scheduling-check
  ```

`.htaccess` root mendukung aplikasi pada subfolder maupun ketika document root
hosting diarahkan langsung ke folder aplikasi. Handler PHP lokal XAMPP hanya
aktif untuk hostname lokal dan tidak diterapkan pada `app.wmvaa.id`.

## Reverse proxy atau Cloudflare

Biarkan `app.behindTrustedProxy=false` untuk shared hosting biasa. Jika provider
memastikan aplikasi berada di belakang reverse proxy, ubah menjadi `true` dan
isi `app.proxyIPsJSON` hanya dengan IP/CIDR proxy yang diberikan provider. Jangan
menggunakan `0.0.0.0/0` atau mempercayai semua alamat.
