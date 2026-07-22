# Known Limitations

This document lists the deliberate engineering limitations and constraints of the current curriculum versioning and scheduling platform.

## 1. Export Formats
- **PDF Export**:
  - **Status**: Known Limitation.
  - **Description**: The system currently does not include standard PHP libraries (such as Dompdf) installed via Composer in the local environment. A placeholder download returning clean, structured HTML format is served instead. Full PDF rendering remains a known limitation until Dompdf is installed.

## 2. Kurikulum Merdeka (Kumer)
- **Status**: Out of Scope.
  - **Description**: This system strictly designs structure availability for regular SMP (Junior High) and SMA (Senior High) classes based on default grade levels and classroom overrides. Advanced Kurikulum Merdeka specific options (e.g. customized elective subjects mapping and dynamic projects) are not implemented.

## 3. Teaching Assignments & Workloads (Milestone 4)
- **Status**: Completed (Milestone 4).
  - **Description**: Teaching assignments, team teaching split ratios, additional duties, workload calculation, and assignment versioning workflows are fully supported.

## 4. Timetable Scheduling & Generator (Milestone 5+)
- **Status**: Not Started (Out of Scope for Milestone 4).
  - **Description**: Features related to timetable slots, time conflict resolution, schedule generators, piket assignments, and official decision letters (SK / Surat Tugas) are strictly reserved for Milestone 5 and are not implemented.
