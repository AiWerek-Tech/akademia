# Subject Learning Pack Engine (Phase 3) - Implementation Guide & Specification

Dokumen ini menjelaskan arsitektur, skema data, alur kerja, validasi graf, Optimistic Concurrency Control (OCC), modul import staging, kepatuhan Pembelajaran Mendalam (*Deep Learning*), serta antarmuka integrasi untuk **Phase 3 (Subject Learning Pack Engine)**.

---

## 1. Ikhtisar Arsitektur

Subject Learning Pack Engine berfungsi sebagai jembatan rancangan pembelajaran terstruktur antara fondasi kurikulum nasional/sekolah (Phase 1 CP/TP/ATP) dan perencanaan modul ajar harian guru (Phase 4 Lesson Planner).

```
+-------------------------------------------------------------+
| Phase 1: Education Foundation (CP, TP, ATP, Sources, Roles) |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
| Phase 3: Subject Learning Pack Engine                       |
| - Aggregate Root: subject_learning_packs                    |
| - Units & Bab: learning_units (OCC)                         |
| - Konsep & Graf Prasyarat: learning_concepts (DAG)          |
| - Materi Esensial: learning_material_topics (OCC)           |
| - Miskonsepsi & Deteksi: learning_misconceptions (OCC)      |
| - Apersepsi/Pemantik: learning_activations                  |
| - Aktivitas Belajar: learning_activities (OCC)              |
| - Sumber Daya/Perangkat: learning_resources (OCC)           |
| - Alternatif Moda (Plugged/Unplugged): alternatives         |
| - Dimensi Pembelajaran Mendalam (Understand/Apply/Reflect)  |
| - Panduan Guru & Respon Murid: guidance & responses         |
| - Referensi Asesmen & Tindak Lanjut: assessment & followup  |
| - Staging Import Engine (21 Entitas Relasional)             |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
| Phase 4: Lesson Plan Engine (RPP / Modul Ajar Harian)       |
| Consumes via SubjectLearningPackEngineService::             |
| getPackStructureForPlanning($packUuid)                      |
+-------------------------------------------------------------+
```

---

## 2. Entitas & Relasi Data (Database Schema)

Phase 3 mengimplementasikan 20+ tabel relasional pada migrasi `20260818000000_CreatePhase3SubjectLearningPackTables.php`:

| Tabel | Fungsi | Kolom Utama | OCC (`revision_number`) |
|---|---|---|:---:|
| `subject_learning_packs` | Root agregat paket belajar | `uuid`, `curriculum_version_id`, `unit_id`, `subject_id`, `grade_level_id`, `phase`, `code`, `name`, `source_type`, `parent_pack_id`, `status` | Ya |
| `learning_units` | Unit / Bab / Topik besar | `uuid`, `learning_pack_id`, `parent_unit_id`, `code`, `title`, `unit_type`, `sequence_order`, `estimated_hours` | Ya |
| `learning_unit_objectives` | Penautan Unit ke TP | `learning_unit_id`, `learning_objective_id`, `role`, `sequence_order`, `estimated_hours` | - |
| `learning_unit_prerequisites` | Prasyarat antar unit (DAG) | `uuid`, `learning_unit_id`, `prerequisite_unit_id`, `prerequisite_objective_id`, `prerequisite_concept_id` | - |
| `learning_concepts` | Konsep materi (graf) | `uuid`, `learning_pack_id`, `learning_unit_id`, `code`, `title`, `concept_type` | Ya |
| `learning_concept_relations` | Hubungan antar konsep | `from_concept_id`, `to_concept_id`, `relation_type` | - |
| `learning_material_topics` | Rincian topik materi | `uuid`, `learning_unit_id`, `code`, `title`, `material_level`, `sequence_order` | Ya |
| `learning_misconceptions` | Deteksi & remediasi miskonsepsi | `uuid`, `learning_unit_id`, `concept_id`, `title`, `description`, `detection_hint`, `teacher_response_suggestion`, `severity` | Ya |
| `learning_activations` | Apersepsi & pertanyaan pemantik | `uuid`, `learning_unit_id`, `activation_type`, `title`, `instructions`, `estimated_minutes` | - |
| `learning_activities` | Aktivitas belajar terstruktur | `uuid`, `learning_pack_id`, `learning_unit_id`, `code`, `title`, `delivery_mode`, `grouping_mode`, `estimated_minutes` | Ya |
| `learning_resources` | Sumber daya & perangkat | `uuid`, `learning_pack_id`, `resource_type`, `title`, `url`, `device_count`, `internet_required` | Ya |
| `learning_activity_resources` | Penautan resource ke aktivitas | `activity_id`, `resource_id`, `requirement_type`, `quantity`, `is_required` | - |
| `learning_activity_alternatives` | Alternatif moda (Plugged/Unplugged) | `group_uuid`, `activity_id`, `priority`, `condition_json` | - |
| `learning_activity_experiences` | 3 Dimensi Pembelajaran Mendalam | `activity_id`, `experience_type` (`UNDERSTAND`, `APPLY`, `REFLECT`), `sequence_order` | - |
| `learning_unit_practices` & `learning_activity_practices` | Penautan praktik pedagogis | `learning_unit_id`/`activity_id`, `practice_id` | - |
| `learning_teacher_guidance` | Panduan guru per unit/aktivitas | `uuid`, `guidance_type`, `learning_unit_id`, `activity_id`, `concept_id`, `title`, `guidance` | - |
| `learning_expected_responses` | Ekspektasi respon & kesulitan | `uuid`, `activity_id`, `response_type`, `description`, `teacher_response` | - |
| `learning_assessment_references` | Referensi asesmen awal/formatif/sumatif | `uuid`, `learning_unit_id`, `activity_id`, `assessment_purpose`, `recommended_method`, `criteria_reference` | - |
| `learning_followup_guidance` | Arahan remedial & pengayaan | `uuid`, `guidance_type`, `learning_unit_id`, `trigger_description`, `guidance`, `recommended_activity_id` | - |
| `learning_reflection_prompts` | Pemantik refleksi murid & guru | `uuid`, `audience`, `learning_unit_id`, `activity_id`, `prompt`, `prompt_type` | - |
| `learning_pack_import_batches` & `_rows` | Modul staging import batch | `uuid`, `unit_id`, `source_filename`, `source_hash`, `status`, `valid_rows`, `applied_rows` | - |

---

## 3. Aturan Validasi & Keamanan

### 3.1 Optimistic Concurrency Control (OCC)
Mutasi entitas (Pack, Unit, Concept, Activity, Resource, Material Topic, Misconception) menerapkan pola atomis `atomicUpdate`:
```sql
UPDATE table_name 
SET ..., revision_number = revision_number + 1, updated_at = NOW() 
WHERE id = ? AND revision_number = ?
```
Jika `affectedRows === 0`, sistem melempar `App\Exceptions\ConcurrencyException` untuk mencegah *race condition* atau *lost update*.

### 3.2 Immutability Pack Terkunci / Diarsipkan
Setiap mutasi anak memanggil `assertMutable($pack)`. Jika status pack adalah `LOCKED` atau `ARCHIVED`, mutasi ditolak dengan `RuntimeException`.

### 3.3 Validasi Siklus Prasyarat Konsep & Unit (DAG)
Sistem mencegah siklus referensi melingkar (*directed acyclic graph cycle*):
- `relateConcepts`: Memeriksa jalur prasyarat menggunakan traversal `conceptPathExists($toId, $fromId)`.
- `addUnitPrerequisite`: Memeriksa jalur prasyarat lintas paket / vertikal menggunakan `unitPathExists($prereqUnitId, $currentUnitId)`.

### 3.4 Isolasi Multi-Unit Sekolah (Multi-Tenancy)
Setiap pemanggilan API / Controller divalidasi dengan `UnitScopeService::assertUnit((int) $pack['unit_id'])`. Akses lintas unit sekolah langsung menghasilkan `AuthorizationException` (HTTP 403).

---

## 4. Evaluasi Pembelajaran Mendalam (*Deep Learning*) & Kesiapan Paket

Method `SubjectLearningPackEngineService::coverage(string $packUuid)` mengevaluasi kepatuhan desain pembelajaran terhadap kerangka Pembelajaran Mendalam (Kepmendikdasmen 126/P/2025):

1. **3 Dimensi Pengalaman Belajar:**
   - **Memahami (*Understand*):** Apersepsi, pengenalan konsep, pemantik rasa ingin tahu.
   - **Mengaplikasi (*Apply*):** Praktik langsung, kerja kelompok/kolaboratif, pembuatan proyek.
   - **Merefleksi (*Reflect*):** Refleksi diri, jurnal belajar, exit ticket.
2. **Perhitungan Readiness Score (0–100%):**
   - Penautan TP ke Paket (25%)
   - Distribusi TP ke Unit/Bab (25%)
   - Ketersediaan Aktivitas Belajar (20%)
   - Kelengkapan 3 Dimensi Pengalaman Mendalam (15%)
   - Referensi Asesmen Formatif/Sumatif (15%)

---

## 5. Integrasi Staging Import (21 Entitas Relasional)

Layanan `LearningPackImportService` memfasilitasi import data pembelajaran skala besar dari berkas JSON melalui dua tahap:
1. **`stage(int $unitId, ?string $packUuid, string $sourceFilename, array $rows)`:**
   - Menghitung SHA-256 hash payload.
   - Memvalidasi skema setiap entitas dan mencatat baris `VALID` atau `ERROR`.
   - Batch diberi status `VALIDATED` atau `NEEDS_FIX`.
2. **`apply(string $batchUuid)`:**
   - Menerapkan baris `VALIDATED` secara transaksional ke database relasional.
   - Mendukung fallback pack level untuk guidance, assessment, dan reflection prompt.

---

## 6. Antarmuka Konsumsi Phase 4 (Lesson Planner)

Untuk menghindari duplikasi kueri dan overhead jaringan pada Phase 4, Phase 3 menyediakan fungsi agregat struktur pohon:

### Service Method
```php
$structure = SubjectLearningPackEngineService::getPackStructureForPlanning(string $packUuid): array
```

### REST Endpoint
```http
GET /curriculum/learning-packs/{uuid}/structure
Authorization: Bearer <session_token> (Permission: learning_packs.view)
```

**Contoh Struktur Output JSON:**
```json
{
  "ok": true,
  "data": {
    "uuid": "...",
    "code": "INF-X-P3",
    "name": "Informatika X",
    "units": [
      {
        "uuid": "...",
        "code": "BK-01",
        "title": "Berpikir Komputasional",
        "objectives": [...],
        "materials": [...],
        "misconceptions": [...],
        "activations": [...],
        "activities": [
          {
            "code": "BK-A1",
            "title": "Analisis Masalah",
            "delivery_mode": "PLUGGED",
            "resources": [...],
            "alternatives": [...],
            "experiences": [
              {"experience_type": "UNDERSTAND"},
              {"experience_type": "APPLY"}
            ],
            "guidance": [...],
            "expected_responses": [...]
          }
        ],
        "assessment_references": [...],
        "followup_guidance": [...],
        "reflection_prompts": [...]
      }
    ],
    "concepts": [...],
    "resources": [...],
    "coverage": {
      "tp_total": 5,
      "readiness_score": 95,
      "deep_learning_experiences": {
        "UNDERSTAND": 4,
        "APPLY": 6,
        "REFLECT": 3
      }
    }
  }
}
```

---

## 7. Rangkuman Verifikasi Pengujian

Pengujian unit dan integrasi dijalankan menggunakan PHPUnit 10.5 pada PHP 8.2:

```bash
vendor/bin/phpunit tests/database/SubjectLearningPackEngineTest.php
```

**Status:** 13 tests, 65 assertions, **100% Passed**.  
**Combined Regression Suite (Phase 1, 2, 3):** 23 tests, 102 assertions, **100% Passed**.
