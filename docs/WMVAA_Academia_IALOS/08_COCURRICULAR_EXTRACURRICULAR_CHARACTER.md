# 08 — Cocurricular, Extracurricular & Character System

## 1. Cocurricular Position

Kokurikuler adalah bagian integral pembelajaran yang menguatkan, memperdalam dan/atau memperkaya intrakurikuler serta mengembangkan kompetensi dan karakter.

Engine harus mendukung tiga bentuk utama:
- kolaboratif lintas disiplin;
- Gerakan 7 Kebiasaan Anak Indonesia Hebat bila digunakan;
- cara lainnya sesuai konteks/nilai satuan pendidikan.

---

## 2. Cocurricular Program Model

```text
cocurricular_programs
- ksp_version_id
- academic_period_id
- unit_id
- title
- type
- theme
- rationale
- annual_minutes/jp
- status
```

Junction:
- profile dimensions;
- subjects;
- objectives/TP;
- teachers;
- classes;
- partners;
- resources.

---

## 3. Cocurricular Workflow

```text
Need/Context Analysis
→ Choose Profile Dimension
→ Choose Theme
→ Map Related Subjects/Objectives
→ Design
→ Schedule/Allocate Time
→ Execute
→ Formative Monitoring
→ Summative Evidence
→ Report
→ Evaluate
→ Follow-up
```

---

## 4. Interdisciplinary Example

```text
Program: Peduli Lingkungan

Profile:
- Penalaran Kritis
- Komunikasi

Subjects:
IPA
Bahasa Indonesia

Evidence:
- observation notes
- data
- solution proposal
- presentation

Assessment:
- IPA criteria
- Bahasa criteria
- profile dimension rubric
```

Satu evidence dapat menyumbang ke beberapa alignment melalui junction, tanpa copy-paste.

---

## 5. School-Specific Activities

WMVAA dapat memetakan:
- Chapel;
- Follow The Bible;
- Family Educare;
- School in Discipleship;
- Doa 777;
- Work Education;
- Service Learning.

Classification harus didasarkan pada tujuan:
- COCURRICULAR;
- EXTRACURRICULAR;
- FIXED_SCHOOL_ACTIVITY;
- FORMATION;
- SERVICE.

Jangan memaksakan semuanya menjadi mapel.

---

## 6. 7KAIH Support

Jika sekolah memilih mengimplementasikan:
- habit definition;
- diary/journal;
- weekly challenge;
- teacher monitoring;
- parent/partner participation;
- reflection;
- long-running formative evidence.

Feature harus optional dan configurable.

---

## 7. Cocurricular Assessment

### Formative
- journal;
- observation;
- peer feedback;
- self assessment;
- reflection.

### Summative
- performance;
- project;
- action;
- product;
- presentation;
- final reflection.

Pelaporan fokus pada pencapaian dimensi profil lulusan dan pengalaman kegiatan, bukan angka semata.

---

## 8. Evaluation Model

Implementasikan:
`INPUT → PROCESS → OUTPUT → OUTCOME`.

Contoh:
- input: waktu, guru, alat;
- process: partisipasi, fidelity to plan;
- output: karya/aksi;
- outcome: perkembangan kompetensi/karakter.

---

## 9. Extracurricular Module

Data:
- rationale;
- objective;
- program description;
- management;
- funding metadata;
- coach;
- membership;
- schedule;
- resources;
- attendance;
- competency;
- achievement;
- qualitative assessment;
- annual evaluation.

Pathfinder dapat menggunakan engine ini dengan extension competency/honor.

---

## 10. Schedule Integration

Extracurricular schedule harus divalidasi agar tidak bentrok dengan intra/kokurikuler sesuai school policy.

Cocurricular annual allocation dapat dieksekusi:
- block days;
- weekly sessions;
- project weeks;
- mixed pattern.

Scheduling engine perlu mengetahui kategori aktivitas agar kapasitas tidak dihitung keliru.
