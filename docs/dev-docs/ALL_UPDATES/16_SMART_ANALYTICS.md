# WMVAA Akademia — Smart Analytics Features

## 1. Overview

Modul analytics cerdas menyediakan visualisasi data dan insight untuk membantu pengambilan keputusan.

---

## 2. Lineage Graph (`/smart/lineage-graph`)

### 2.1 Concept

Peta Lineage Kurikulum = visualisasi hubungan antara:
```
Regulasi → CP → Elemen → TP → ATP → Paket Pembelajaran → RPP → Asesmen
```

### 2.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Graph** | `GET /smart/lineage-graph` | Tampilan graph lineage |
| **Data API** | `GET /smart/lineage-graph/data` | JSON data untuk graph |

### 2.3 Implementation

Graph di-render menggunakan library JavaScript interaktif (kemungkinan D3.js atau library graph lainnya).

---

## 3. Mastery Heatmap (`/smart/mastery-heatmap`)

### 3.1 Concept

Heatmap visualisasi mastery siswa terhadap TP.

### 3.2 Layout

```
              │ TP-1 │ TP-2 │ TP-3 │ TP-4 │ ...
Siswa A       │  🟢  │  🟡  │  🔴  │  🟢  │
Siswa B       │  🟡  │  🟢  │  🟢  │  🟡  │
Siswa C       │  🔴  │  🔴  │  🟡  │  🟢  │
...
```

### 3.3 Color Legend

| Warna | Mastery Level |
|---|---|
| 🟢 Hijau | Mencapai / Mencapai dengan Baik |
| 🟡 Kuning | Berkembang |
| 🔴 Merah | Belum Mencapai / Mulai Berkembang |

---

## 4. Reflection Trends (`/smart/reflection-trends`)

### 4.1 Concept

Tren refleksi mengajar = analisis pola dan tren dari refleksi guru dari waktu ke waktu.

### 4.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Trends** | `GET /smart/reflection-trends` | Tampilan tren |

### 4.3 Metrics

- Jumlah refleksi per periode
- Tema-tema yang paling sering muncul
- Korelasi refleksi dengan mastery siswa
- Tren kualitas pengajaran

---

## 5. Remedial Package (`/smart/remedial-package`)

### 5.1 Concept

Paket Remedial Terarah = paket belajar remedial yang di-generate otomatis berdasarkan data mastery siswa.

### 5.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Package** | `GET /smart/remedial-package` | Lihat paket remedial |
| **Complete** | `POST /smart/remedial/complete` | Tandai selesai |

### 5.3 Package Contents

- Identifikasi TP yang belum tercapai
- Materi remedial terkait
- Aktivitas remedial
- Asesmen remedial

---

## 6. Narrative Drafter (`/smart/narrative-drafter`)

### 6.1 Concept

Draf Narasi Rapor = generate draf narasi otomatis berdasarkan data asesmen, mastery, dan aktivitas siswa.

### 6.2 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **View** | `GET /smart/narrative-drafter` | Interface drafter |
| **Save** | `POST /smart/narrative-drafter/save` | Simpan draf |

### 6.3 Generation Process

1. Kumpulkan data asesmen siswa
2. Analisis mastery TP
3. Generate narasi berdasarkan template
4. Presentasikan untuk review
5. Simpan atau edit manual

---

## 7. AI-Powered Tools

### 7.1 Rubric Generator (`POST /smart/ajax/rubric-generator`)

Generate rubric penilaian otomatis berdasarkan:
- Tujuan pembelajaran
- Tipe asesmen
- Level kemampuan siswa

### 7.2 Differentiation Assistant (`POST /smart/ajax/differentiation-assistant`)

Asisten diferensiasi pembelajaran:
- Saran strategi diferensiasi
- Rekomendasi materi per level
- Aktivitas differentiated

### 7.3 Adaptive Mode (`POST /smart/ajax/adaptive-mode`)

Mode adaptif pembelajaran:
- Analisis level siswa
- Rekomendasi jalur belajar
- Penyesuaian konten otomatis

---

## 8. Services

| Service | Fungsi |
|---|---|
| `CurriculumLineageService` | Lineage graph generation |
| `MasteryHeatmapService` | Heatmap generation |
| `ReflectionTrendsService` | Tren refleksi analysis |
| `RemediationPackagerService` | Remedial package generation |
| `NarrativeService` | Narasi generation |
| `RubricGeneratorService` | Rubric generation |
| `DifferentiationService` | Diferensiasi assistant |
| `AdaptiveModeService` | Mode adaptif |

---

## 9. Routes Summary

| Route | Method | Deskripsi | Permission |
|---|---|---|---|
| `GET /smart/lineage-graph` | GET | Peta lineage | `learning_outcomes.view` |
| `GET /smart/lineage-graph/data` | GET | Data lineage JSON | `learning_outcomes.view` |
| `GET /smart/mastery-heatmap` | GET | Mastery heatmap | `assessment.view` |
| `GET /smart/reflection-trends` | GET | Tren refleksi | `teaching.workspace` |
| `GET /smart/remedial-package` | GET | Paket remedial | `assessment.view` |
| `GET /smart/narrative-drafter` | GET | Narasi drafter | `assessment.view` |
| `POST /smart/narrative-drafter/save` | POST | Simpan draf narasi | `assessment.mastery` |
| `POST /smart/remedial/complete` | POST | Selesaikan remedial | `assessment.mastery` |
| `POST /smart/ajax/rubric-generator` | POST | Generate rubric | `lesson_plans.view` |
| `POST /smart/ajax/differentiation-assistant` | POST | Differentiation assistant | `lesson_plans.view` |
| `POST /smart/ajax/adaptive-mode` | POST | Adaptive mode | `lesson_plans.view` |
