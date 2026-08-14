# 06 — Teaching & Learning Engine

## 1. Goal

Mengubah jadwal menjadi **pelaksanaan pembelajaran digital yang benar-benar membantu guru**.

---

## 2. Core Concept

```text
ScheduleEntry
    ↓
LearningSession
    ↓
LessonPlan
    ↓
LearningActivities
    ↓
Evidence + Assessment
    ↓
Reflection + Follow-up
```

Schedule adalah *orchestrator*, bukan lesson plan itu sendiri.

---

## 3. Subject Learning Pack

Setiap mata pelajaran memiliki struktur generik:

```text
Subject
Phase
Grade
CP
Element
TP
ATP
Learning Unit / Chapter
Concept
Prerequisite
Essential Material
Misconception
Aperception
Activity
Teaching Strategy
Teacher Role
Student Role
Resource
Assessment
Rubric
Remedial
Enrichment
Reflection
Reference
```

Informatika Kelas X menjadi **reference implementation pertama**, bukan special-case database.

---

## 4. Lesson Planning Model

### Identification
- class/phase;
- TP;
- learner readiness;
- material characteristics;
- relevant graduate-profile dimensions.

### Learning Design
- learning objective;
- pedagogical practice;
- learning partnership;
- learning environment;
- digital utilization;
- interdisciplinary alignment.

### Learning Experience
- Memahami;
- Mengaplikasi;
- Merefleksi.

### Assessment
- initial/diagnostic;
- formative during process;
- summative/closing where appropriate;
- criteria;
- follow-up.

---

## 5. Daily Teacher Workspace

Primary screen:

```text
KAMIS, 13 AGUSTUS 2026

08:00  Matematika VIII      ✓ selesai
10:10  Informatika X        [MULAI PEMBELAJARAN]
13:00  Koding & KA IX       belum dimulai

Perhatian:
• 6 siswa perlu tindak lanjut
• 1 refleksi sesi belum selesai
• 2 assessment belum direview
```

---

## 6. Teaching Mode

Layar sederhana untuk kelas:

```text
INFORMATIKA X
Pertemuan #13 | 3 JP

TP: ...
Tujuan hari ini: ...

MEMAHAMI
✓ Apersepsi
✓ Pertanyaan pemantik

MENGAPLIKASI
○ Aktivitas kelompok
○ Praktik

MEREFLEKSI
○ Exit ticket

ASESMEN FORMATIF
○ Belum selesai
```

Fitur:
- timer optional;
- attendance shortcut;
- activity checklist;
- quick observation;
- capture evidence;
- “deviation from plan” note;
- offline/degraded cache jika memungkinkan.

---

## 7. Adaptive Activity Selection

Activity metadata:
- plugged/unplugged/both;
- device requirements;
- internet requirements;
- group size;
- room requirement;
- estimated duration;
- prerequisite;
- accessibility need.

System recommendation menggunakan context sekolah.

Contoh:
`Internet unavailable + 15 computers + 31 students → suggest unplugged or paired activity`.

---

## 8. Common Misconception Engine

Setiap konsep dapat memiliki daftar miskonsepsi dan teacher response suggestions.

Guru dapat:
- melihat warning sebelum mengajar;
- tandai miskonsepsi ditemukan;
- catat berapa siswa terdampak;
- link ke follow-up.

Analytics dapat menunjukkan miskonsepsi yang berulang.

---

## 9. Session Completion

Session tidak ditutup hanya karena waktu habis.

Guru menyelesaikan:
- actual duration;
- TP covered;
- activities actually used;
- attendance;
- evidence captured;
- formative assessment state;
- issues;
- next-step recommendation;
- reflection status.

Status:
`PLANNED → IN_PROGRESS → COMPLETED → REFLECTED`.

---

## 10. Teacher Autonomy

Reference plan dapat:
- USE AS IS;
- ADAPT;
- CLONE;
- CREATE CUSTOM.

System tetap menghitung:
- TP coverage;
- missing prerequisites;
- unassessed objectives;
- time risk.

---

## 11. Intellectual Property Model

Simpan struktur, metadata, kode aktivitas, referensi dan adaptasi yang diperbolehkan. Jangan otomatis menyalin keseluruhan buku panduan berhak cipta ke database tanpa landasan izin/lisensi.
