# Permission Matrix

| Area | View | Manage / workflow |
|---|---|---|
| Regulasi | `regulations.view` | `regulations.manage` |
| Sumber | `curriculum_sources.view` | `curriculum_sources.manage` |
| Profil lulusan | `graduate_profile.view` | `graduate_profile.manage` |
| CP | `learning_outcomes.view` | `learning_outcomes.manage` |
| TP | `learning_objectives.view` | `learning_objectives.manage` |
| ATP | `learning_sequences.view` | `learning_sequences.manage`, `.validate`, `.review`, `.approve`, `.lock` |
| Paket | `learning_packs.view` | `learning_packs.manage` |

Super admin menerima seluruh permission dan menjadi satu-satunya role bawaan yang dapat mengubah referensi/CP nasional. Kepala sekolah menerima view dan tahap review/approve/lock. Wakasek kurikulum serta admin unit menerima pengelolaan operasional tanpa approve/lock atau mutasi referensi nasional. Guru menerima view serta pengelolaan TP adaptasi, ATP, dan paket dalam scope unit; viewer hanya menerima view. Semua grant dibuat idempotent dan controller tidak memeriksa nama role.

Permission tidak menggantikan data scope. Setiap operasi adaptasi, ATP, paket, coverage, dan import tetap harus melewati `UnitScopeService`.
