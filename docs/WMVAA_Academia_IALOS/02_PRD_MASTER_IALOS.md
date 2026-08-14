# 02 — Master Product Requirements Document (PRD)

## 1. Product Name

**WMVAA Academia — Integrated Academic & Learning Operating System (IALOS)**

## 2. Product Objective

Menyediakan satu sistem digital terpadu bagi sekolah untuk:
1. menerjemahkan regulasi nasional menjadi konfigurasi yang dapat divalidasi;
2. membangun dan menjalankan Kurikulum Satuan Pendidikan;
3. mengelola struktur kurikulum, CP, TP, ATP dan subject learning pack;
4. menghubungkan penugasan guru dan jadwal dengan pelaksanaan pembelajaran nyata;
5. menyediakan workspace harian guru;
6. mendukung pembelajaran mendalam secara operasional;
7. mengelola asesmen, evidence, mastery, remedial dan enrichment;
8. menjalankan kokurikuler dan ekstrakurikuler secara terukur;
9. menghasilkan pelaporan dan portofolio dari data yang sama;
10. menutup siklus melalui refleksi, supervisi, evaluasi dan perbaikan KSP.

---

## 3. Primary Users

### Super Admin
Mengelola konfigurasi global, registry regulasi, keamanan dan master system.

### Kepala Sekolah
Menetapkan KSP, melihat academic health, melakukan approval dan evaluasi.

### Wakasek Kurikulum
Mengelola struktur kurikulum, ATP governance, assignment, jadwal, compliance, monitoring.

### Guru
Merencanakan, menjalankan dan merefleksikan pembelajaran; melakukan asesmen dan tindak lanjut.

### Wali Kelas
Memantau perkembangan kelas, kehadiran, intervensi dan proses rapor.

### Pembina Kokurikuler/Ekstrakurikuler
Mengelola program, aktivitas, evidence dan evaluasi.

### Murid
Menerima aktivitas, mengirim evidence, melakukan refleksi/assessment bila portal murid diaktifkan.

### Orang Tua/Wali
Melihat informasi yang telah disetujui sekolah dan memberi umpan balik pada kanal yang ditentukan.

### Pengawas/Reviewer
Akses read-only atau review terkontrol terhadap KSP, evaluasi dan bukti implementasi.

---

## 4. Core User Journeys

### Journey A — Tahun Ajaran Baru
`Regulation Baseline → KSP Version → Curriculum Version → Assignments → Workload Validation → Schedule → Teaching Plan Readiness`.

### Journey B — Guru Mengajar Hari Ini
`Dashboard Hari Ini → Scheduled Session → Lesson Plan → Teaching Mode → Attendance → Activity → Formative Assessment → Reflection → Follow-up`.

### Journey C — Asesmen ke Rapor
`Assessment Evidence → TP Attainment → Summative Processing → Teacher Validation → Report Description → Rapor`.

### Journey D — Kokurikuler
`Need Analysis → Profile Dimension → Theme → Related Subjects → Program Plan → Execution → Evidence → Assessment → Report → Evaluation`.

### Journey E — School Improvement
`Learning Data → Student Outcome → Teacher Reflection → Parent/Student Feedback → Program Evaluation → Root Cause → Improvement Action → KSP Revision`.

---

## 5. Functional Requirements

### FR-REG — Regulation & Compliance
- registry regulasi;
- tanggal berlaku;
- scope jenjang/kelas/fase;
- aturan kurikulum berversi;
- citation/reference metadata;
- rule severity;
- compliance checker;
- override/waiver dengan alasan dan approval;
- impact analysis saat regulasi berubah.

### FR-KSP — Digital KSP
- analisis karakteristik sekolah;
- sumber data dan evidence;
- visi, misi, tujuan;
- pengorganisasian pembelajaran;
- intra/kokurikuler/ekstrakurikuler;
- perencanaan tingkat sekolah;
- evaluasi dan tindak lanjut;
- versioning dan approval;
- generate dokumen KSP.

### FR-CUR — Curriculum & Subject Learning Pack
- CP;
- elemen;
- TP;
- ATP;
- prerequisite;
- indikator/kriteria;
- materi esensial;
- konsep dan miskonsepsi;
- learning activity;
- alokasi JP;
- resource;
- reference/source;
- official vs school/teacher adaptation;
- coverage checker.

### FR-WRK/SCH — Workforce & Scheduling
Memanfaatkan sistem yang sudah dibangun:
- teaching assignment;
- workload;
- team teaching policy;
- schedule version;
- conflict detection;
- candidate generation;
- explicit apply;
- print/export.

### FR-TCH — Teaching & Learning
- lesson sequence;
- session planner;
- daily teacher workspace;
- teaching mode;
- `Understand → Apply → Reflect`;
- learning activity;
- resource recommendation;
- device/resource context;
- linked attendance;
- session completion;
- session deviation/notes.

### FR-ASM — Assessment
- diagnostic/initial;
- formative;
- summative;
- rubric;
- qualitative/quantitative;
- criterion based;
- evidence links;
- feedback;
- mastery;
- intervention;
- enrichment.

### FR-COC — Cocurricular
- dimension;
- theme;
- learning objectives;
- interdisciplinary subjects;
- annual time allocation;
- partner;
- activity;
- evidence;
- formative/summative assessment;
- evaluation input/process/output/outcome;
- report description.

### FR-EXT — Extracurricular
- program;
- rational/objectives;
- management;
- instructor;
- member;
- schedule;
- funding metadata;
- attendance;
- competency/achievement;
- qualitative assessment;
- annual evaluation.

### FR-RPT — Reporting
- subject progress;
- TP coverage;
- mastery;
- rapor values;
- narrative description;
- cocurricular description;
- extracurricular qualitative report;
- class/teacher dashboards;
- portfolio;
- printable/exportable views.

### FR-QI — Quality Improvement
- teacher reflection;
- peer/head observation;
- supervision;
- program evaluation;
- KSP evaluation;
- improvement actions;
- professional development recommendation.

### FR-AI — Academic Copilot
- context-aware assistance;
- draft lesson idea;
- activity alternatives;
- assessment item generation;
- rubric draft;
- reflection summary;
- follow-up recommendation;
- narrative report suggestion;
- no autonomous final academic decision.

---

## 6. Critical Business Rules

1. Formative results **must not** automatically become final grade components.
2. Summative assessment is used to determine attainment for reporting according to configured school policy.
3. A student mastery status must be traceable to criteria and evidence.
4. A lesson session cannot be silently marked complete merely because its scheduled time passed.
5. A teacher may modify a reference plan, but system must preserve source lineage.
6. Official curriculum data and school adaptation must be separable.
7. KSP version approved for a year cannot be silently overwritten.
8. Cocurricular evidence may contribute to subject learning only when alignment and policy explicitly allow it.
9. AI-generated content remains draft until human approval.
10. Historical reports must continue using the rule/configuration version applicable to that period.

---

## 7. UX Principles

### Teacher-first
Primary screen answers:
**“Apa yang perlu saya lakukan sekarang?”**

### Progressive disclosure
Guru tidak melihat seluruh kompleksitas database. Detail muncul sesuai kebutuhan.

### Defaults from context
Unit, class, subject, phase, schedule, ATP position and resource profile should prefill automatically.

### No duplicate entry
Data yang sudah tersedia dari jadwal, kelas, teacher assignment dan curriculum tidak diketik ulang.

### Explainable automation
Setiap rekomendasi sistem menjelaskan sumber:
- regulation;
- school policy;
- ATP;
- last assessment;
- schedule;
- resource constraint.

---

## 8. Non-Functional Requirements

### Security
- RBAC;
- unit scope;
- least privilege;
- CSRF;
- secure session;
- audit;
- sensitive data protection;
- server-side validation.

### Reliability
- transaction boundaries;
- optimistic concurrency;
- revision history;
- idempotent migration/seeder;
- retry-safe jobs.

### Performance
Teacher daily workspace should render rapidly and avoid loading full-year datasets unnecessarily.

### Auditability
Every major academic decision stores:
- actor;
- timestamp;
- source;
- version;
- before/after when relevant;
- reason/approval.

### Accessibility
Keyboard navigation, readable contrast, responsive design, semantic labels, print accessibility where practical.

### Maintainability
Bounded modules with services, policies, repositories/models and tests; avoid fat controllers.

---

## 9. Definition of Product Success

IALOS dianggap berhasil ketika:
- guru dapat mengajar satu pertemuan dari jadwal tanpa menyalin data;
- setiap TP dapat dilacak ke lesson/evidence/assessment;
- laporan semester berasal dari proses yang sama;
- KSP dapat dievaluasi berdasarkan data aktual;
- compliance terhadap aturan kurikulum dapat diperiksa otomatis;
- kegiatan khas sekolah dapat masuk tanpa merusak model nasional;
- perubahan regulasi dapat diterapkan sebagai versi baru tanpa merusak histori.
