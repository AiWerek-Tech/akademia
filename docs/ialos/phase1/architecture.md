# Arsitektur IALOS Education

IALOS Education menyatu di modular monolith CodeIgniter dan memakai identitas, unit, periode, kurikulum, RBAC, audit, serta layout WMVAA Academia yang sudah ada. Boundary teknis Phase 1 tetap bernama `education_foundation` dan terdiri dari controller tipis, layanan domain transaksional, model/tabel semantik, serta UI server-rendered.

Alur dependensi:

`Regulasi → Versi Regulasi → Sumber Kurikulum → CP → Elemen → TP → Item ATP → ATP → Paket Pembelajaran`

Master `school_units`, `subjects`, `grade_levels`, `academic_periods`, dan `curriculum_versions` tetap menjadi authority yang dipakai bersama. Authorization dibagi dua: permission menentukan tindakan, `UnitScopeService` menentukan data unit yang boleh disentuh. Semua mutasi penting menulis `audit_logs`.

Data resmi tidak dicampur dengan adaptasi. CP dan TP nasional bersifat global. Adaptasi TP menyimpan `unit_id`, `source_level`, dan `parent_objective_id`. ATP dan paket selalu unit-scoped.
