# Kontrak Team Teaching Penjadwalan

## Keputusan Go-live

Team teaching penjadwalan **OFF** melalui `Config\Scheduling::$teamTeachingEnabled`. Data single teacher tetap didukung. Assignment dengan `team_group_uuid`, `teaching_assignment_group_id`, anggota non-primary, requirement team, atau `second_teacher_id` ditolak eksplisit sebelum ada write yang dapat meratakan anggota tim.

## Source of Truth Target

`teaching_assignment_groups` → anggota `teaching_assignments` → `schedule_requirement_group` → junction `schedule_entry_teachers`.

Junction target memuat `schedule_entry_id`, `teacher_id`, `assignment_id`, `role`, `workload_share`, `is_primary`, dan timestamp. `second_teacher_id` tidak boleh menjadi kontrak final karena tidak mendukung tiga guru.

Saat fitur kelak diaktifkan, seluruh anggota wajib ikut overlap/availability, apply, drag/drop, import/export, print, workload, dan audit. Mode workload default membagi share dan tidak menggandakan beban; penggandaan hanya pada mode eksplisit `FULL_FOR_EACH`.

## Batas Aman Saat Ini

- Single primary teacher: diterima.
- Dua/tiga guru, split hours, secondary unavailable, dan cross-unit member: diblokir sebelum scheduling.
- Tidak ada anggota kedua yang dikosongkan diam-diam.
- Production gate gagal bila flag dinyalakan sebelum junction/contract diimplementasikan.
