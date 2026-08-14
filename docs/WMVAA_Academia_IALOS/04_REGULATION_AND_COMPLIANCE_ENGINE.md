# 04 — Regulation & Compliance Engine

## 1. Objective

Mengubah aturan nasional dari PDF menjadi **reference registry + rule engine berversi** yang dapat:
- digunakan saat membuat kurikulum sekolah;
- memvalidasi struktur;
- memberi warning/error;
- menjelaskan dasar aturan;
- menyimpan histori perubahan.

---

## 2. Regulation Registry

Minimal metadata:

```text
regulations
- id
- code
- title
- authority
- regulation_type
- issued_date
- effective_from
- effective_to
- status
- source_url/reference
- source_document_hash
- notes
```

Contoh:
`Permendikdasmen No. 13 Tahun 2025`.

---

## 3. Regulation Rules

```text
regulation_rules
- id
- regulation_id
- rule_code
- scope_json
- category
- severity
- expression_type
- expression_json
- effective_from
- effective_to
- citation_label
- human_explanation
```

Kategori:
- curriculum structure;
- learning hours;
- elective;
- local content;
- cocurricular;
- extracurricular;
- graduate profile;
- process/assessment;
- reporting.

---

## 4. Compliance Result

```text
compliance_runs
compliance_results
```

Contoh hasil:

```text
RULE: SMA_ELECTIVE_MINIMUM_OFFERINGS
Status: FAIL
Expected: minimum 7 offerings
Actual: 5
Severity: BLOCKER
Source: Permendikdasmen 13/2025
Suggested action: Tambahkan sedikitnya 2 penawaran mapel pilihan.
```

---

## 5. Overrides / Waivers

Beberapa area bersifat fleksibel. Sistem perlu membedakan:
- **hard legal rule**;
- **recommended guidance**;
- **school policy**.

Override hanya boleh untuk aturan yang memang dapat dikustom:
- reason;
- actor;
- approval;
- effective period;
- evidence/reference.

Jangan pernah menampilkan “compliant” apabila override sebenarnya melanggar aturan mandatory.

---

## 6. Rule Versioning

Saat aturan berubah:
- rule lama tetap terikat ke tahun ajaran lama;
- new rule set berlaku ke periode baru;
- impact analysis menunjukkan objek sekolah yang terdampak.

---

## 7. Initial Rules to Encode

Dari dokumen nasional yang dianalisis, prioritas awal:
- struktur SMP;
- struktur SMA;
- intrakurikuler/kokurikuler;
- mapel pilihan;
- muatan lokal;
- Koding dan Kecerdasan Artifisial;
- delapan dimensi profil lulusan;
- kewajiban layanan ekstrakurikuler;
- aturan yang berkaitan dengan perencanaan, asesmen dan pelaporan.

---

## 8. Compliance UI

### School Compliance Center
```text
Kurikulum 2026/2027

Regulasi Aktif
✓ Permendikdasmen 13/2025
✓ Pedoman Pembelajaran Mendalam 126/P/2025

Curriculum Structure       PASS
Cocurricular Plan          WARNING
Extracurricular Program    PASS
Assessment Policy          PASS
KSP Completeness           92%
```

Klik satu item membuka:
- expectation;
- actual state;
- source reference;
- affected data;
- suggested correction.

---

## 9. Safety Requirement

Rule engine **tidak boleh** menjadi interpreter bebas yang mengeksekusi kode dari database. Gunakan expression DSL terbatas/typed evaluator agar aman dan dapat diuji.
