# 10 — AI Academic Copilot & Governance

## 1. AI Position

AI adalah **copilot**, bukan authority.

AI membantu:
- merangkum;
- memberi alternatif;
- membuat draft;
- mengidentifikasi pola;
- menyarankan tindak lanjut.

AI tidak boleh melakukan final academic decision secara otomatis.

---

## 2. Allowed Use Cases

### Teacher
- draft apersepsi;
- alternative activity;
- plugged/unplugged adaptation;
- group strategy;
- formative questions;
- rubric draft;
- remedial/enrichment idea;
- summarize reflection.

### Curriculum Team
- check draft TP wording;
- map potential alignment;
- identify uncovered objectives;
- summarize compliance finding;
- compare KSP revision.

### Reporting
- draft narrative based on evidence;
- summarize portfolio;
- highlight progress.

### Leadership
- summarize trends;
- suggest root-cause hypotheses;
- prepare discussion questions.

---

## 3. Prohibited Autonomous Actions

AI must not:
- alter national reference CP/TP;
- approve KSP;
- publish schedule;
- finalise grade;
- decide promotion/graduation;
- issue discipline sanction;
- send parent-facing message without workflow approval if sensitive;
- delete evidence;
- change attendance without authorized human action.

---

## 4. Context Contract

AI request gets minimal structured context:

```json
{
  "role": "teacher",
  "unit": "SMA",
  "subject": "Informatika",
  "phase": "E",
  "tp": "...",
  "class_profile_summary": "...",
  "available_resources": "...",
  "time_minutes": 120,
  "previous_mastery_summary": "...",
  "requested_task": "..."
}
```

Avoid exposing unrelated student data.

---

## 5. Provenance

Every AI output stores:
- model/provider;
- created_at;
- requesting user;
- context hash;
- source records;
- prompt type;
- output;
- approval status;
- human edits.

---

## 6. AI Safety States

```text
DRAFT
→ REVIEWED
→ ACCEPTED / REJECTED
```

AI output cannot directly enter locked curriculum/rapor without human transition.

---

## 7. Explainability

UI should show:
“Rekomendasi ini dibuat berdasarkan TP X, hasil asesmen formatif terakhir, alokasi 2 JP, dan resource profile kelas.”

Not:
“AI says so.”

---

## 8. Teacher Feedback Loop

Buttons:
- Useful;
- Needs correction;
- Inappropriate;
- Wrong curriculum alignment.

Feedback used to improve prompt/configuration, not to silently train on private student content unless policy permits.

---

## 9. Cost and Quota Governance

- cache non-sensitive static reference summaries;
- structured prompts;
- no repeated whole-document context;
- use deterministic non-AI rule engine for compliance;
- use AI only where generative reasoning adds value.

---

## 10. AI Readiness Gate

AI feature baru tidak boleh enabled sebelum:
- source data quality adequate;
- permission scoped;
- audit log works;
- human approval path exists;
- hallucination/failure behavior documented;
- fallback manual workflow exists.
