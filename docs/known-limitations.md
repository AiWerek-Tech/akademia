# Known Limitations

This document lists the deliberate engineering limitations and constraints of the current curriculum versioning and scheduling platform.

## 1. Export Formats
- **PDF Export**:
  - **Status**: Known Limitation.
  - **Description**: The system currently does not include standard PHP libraries (such as Dompdf) installed via Composer in the local environment. A placeholder download returning clean, structured HTML format is served instead. Full PDF rendering remains a known limitation until Dompdf is installed.

## 2. Kurikulum Merdeka (Kumer)
- **Status**: Out of Scope.
  - **Description**: This system strictly designs structure availability for regular SMP (Junior High) and SMA (Senior High) classes based on default grade levels and classroom overrides. Advanced Kurikulum Merdeka specific options (e.g. customized elective subjects mapping and dynamic projects) are not implemented.

## 3. Teaching Assignments & Schedules (Milestone 4+)
- **Status**: Not Started (Out of Scope for Milestone 3).
  - **Description**: Features related to teaching assignments, workloads, scheduling entries, roster generation, and official letters of assignment (SK/Surat Tugas) are strictly reserved for Milestone 4 and must not be created or simulated.
