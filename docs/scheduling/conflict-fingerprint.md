# Fingerprint Konflik Jadwal

Setiap finding memperoleh SHA-256 dari payload canonical berurutan: `schedule_version_id`, `conflict_code`, entry ID yang disortir, jenis/ID resource, identitas hari, start time, dan end time.

Kolom lifecycle: `fingerprint`, `generation_run_id`, `conflict_code`, resource IDs, `day_identity`, `start_time`, `end_time`, `status`, `detected_at`, dan `active_generation_scope`. Unique key `(schedule_version_id, fingerprint, active_generation_scope)` serta upsert atomik mencegah duplikasi.

Audit mengunci baris `schedule_versions FOR UPDATE`, menandai finding lama RESOLVED, lalu membuka kembali fingerprint yang ditemukan. History tidak dihapus. Saat apply mengganti entry unlocked, referensi FK entry pada history dilepas dan status di-resolve, sementara fingerprint/description tetap tersimpan.

Uji dua proses pada versi nyata menghasilkan finding identik, tanpa fingerprint duplikat. Reopen mempertahankan ID/fingerprint yang sama.
