# Schema Database Phase 1

| Kelompok | Tabel |
|---|---|
| Referensi | `regulations`, `regulation_versions`, `curriculum_sources`, `graduate_profile_dimensions` |
| Capaian | `learning_outcomes_cp`, `curriculum_elements` |
| Tujuan | `learning_objectives_tp`, `objective_criteria` |
| Alur | `learning_sequences_atp`, `learning_sequence_items` |
| Paket | `subject_learning_packs`, `subject_learning_pack_objectives`, `subject_learning_pack_sequences` |
| Import staging | `education_foundation_import_batches`, `education_foundation_import_rows` |

Seluruh entitas publik memiliki UUID. Foreign key internal menggunakan tipe yang sama dengan master: BIGINT unsigned untuk unit/mapel/tingkat/versi dan INT unsigned untuk user/periode. Unique key mencegah kode ganda dalam scope serta item/urutan ganda. Migration bersifat add-only dan rollback menjatuhkan tabel dalam urutan dependensi terbalik.

Kolom `revision_number` pada CP, TP, ATP, dan paket mendukung optimistic concurrency control. ATP menyimpan aktor/waktu setiap tahap workflow.
