# 07 — Assessment, Mastery & Reporting

## 1. Fundamental Principle

Pembelajaran dan asesmen adalah satu siklus. Assessment module tidak boleh hanya menjadi “input nilai”.

---

## 2. Assessment Types

```text
DIAGNOSTIC / INITIAL
FORMATIVE
SUMMATIVE
```

### Diagnostic
Tujuan:
- mengetahui titik awal;
- readiness;
- prerequisite;
- misconception.

Tidak menjadi nilai akhir.

### Formative
Tujuan:
- feedback;
- memperbaiki strategi;
- mengetahui perkembangan;
- menentukan intervensi.

**Tidak otomatis digabung ke nilai akhir.**

### Summative
Tujuan:
- menilai pencapaian setelah satu/lebih TP;
- menjadi input pengolahan hasil akhir sesuai kebijakan sekolah.

---

## 3. Assessment Model

```text
assessment
assessment_objectives
assessment_criteria
rubric
rubric_levels
assessment_items
student_attempts
assessment_evidence
criterion_results
feedback
```

Mendukung:
- angka;
- rubrik;
- observasi;
- essay;
- performance;
- product;
- project;
- oral;
- portfolio.

---

## 4. Mastery Model

Jangan hanya menyimpan:
`score=72`.

Simpan:
```text
Student
TP
Criterion
Evidence
Result
Status
Confidence/quality metadata (optional)
Updated by
Updated at
```

Status configurable, contoh:
- NEEDS_SUPPORT;
- DEVELOPING;
- ACHIEVED;
- ADVANCED.

Label harus positif dan dapat dipahami guru.

---

## 5. Criterion-Based Attainment

Contoh:

```text
TP X-LD-03

Criterion 1 ✓
Criterion 2 ✓
Criterion 3 ○
Criterion 4 ✓

Overall:
NEARLY ACHIEVED

Recommended:
Targeted remediation for criterion 3.
```

---

## 6. Intervention & Enrichment

System menghasilkan rekomendasi, bukan keputusan final:

```text
interventions
- student_id
- objective_id
- trigger_evidence
- type: REMEDIAL / REINFORCEMENT / ENRICHMENT
- planned_activity
- scheduled_at
- outcome
```

Guru approve/modify.

---

## 7. Evidence of Learning

Jenis:
- text;
- file;
- image;
- video;
- spreadsheet;
- code;
- observation;
- checklist;
- quiz response;
- performance record;
- presentation;
- reflection.

Evidence dapat align ke:
- TP;
- criterion;
- profile dimension;
- cocurricular objective.

---

## 8. Rapor as Output

Ideal flow:

```text
Teaching
→ Evidence
→ Assessment
→ TP Attainment
→ Summative Processing
→ Teacher Validation
→ Narrative/Grade
→ Rapor
```

Rapor tidak menjadi tempat utama mengetik data mentah.

---

## 9. Reporting Calculation Policies

Karena panduan memberi fleksibilitas pengolahan, system harus menyimpan policy:
- average;
- weighted;
- proportional;
- criterion-based conversion;
- qualitative.

Policy versioned per unit/year/subject scope bila diperlukan.

---

## 10. Narrative Generator

AI/system boleh membuat **draft deskripsi** berdasarkan:
- top achieved competencies;
- area for improvement;
- evidence;
- school language policy.

Guru wajib review sebelum publish.

---

## 11. Portfolio

Portfolio otomatis menarik evidence terpilih:

```text
Student Portfolio
- Best work
- Growth evidence
- Projects
- Cocurricular evidence
- Extracurricular achievement
- Reflection
```

Portfolio tidak perlu menduplikasi file; gunakan references.

---

## 12. Promotion/Graduation Support

System dapat menyediakan **decision support**, misalnya:
- completion;
- attendance alerts;
- competency progress;
- unresolved intervention.

Keputusan kenaikan/kelulusan tetap melalui mekanisme dan pihak berwenang sekolah.
