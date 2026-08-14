# Hasil Full Root Suite Penjadwalan

- Discovery: 219 test setelah penambahan coverage closure.
- Perintah: `E:\xampp\php82\php.exe vendor/bin/phpunit --no-coverage`.
- Run acceptance awal: 219 executed, 720 assertions, 0 error, 0 failure, 0 warning, 0 incomplete, 3 skipped, 08:31.428, 110 MB.
- Tiga skip disebabkan fixture curriculum structure kosong; fixture dan pemanggilan API stale diperbaiki. Focused rerun: 10 test, 32 assertions, tanpa skip.
- Root cause suite >10 menit: `DatabaseTestTrait` memigrasi dan seed seluruh schema untuk setiap method. `IsolatedDatabaseTestTrait` melakukan rebuild sekali per class dan rollback transaksi per test.
- Full root final setelah seluruh quality changes: `OK (219 tests, 733 assertions)`, 0 skipped/incomplete/warning/error/failure, 06:32.662, 110 MB.
