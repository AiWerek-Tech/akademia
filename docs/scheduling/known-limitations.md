# Known Limitations — Milestone 5 Penjadwalan Pelajaran

## Generator Engine Limitations
1. **Best-Effort Greedy Heuristic**:
   - The `DeterministicGreedyScheduleGenerator` is a **deterministic, reproducible, best-effort, constraint-aware** generator.
   - It **may return partial results** when slots cannot be placed without violating hard constraints.
   - It is **not guaranteed to find a globally optimal** schedule (NP-hard constraint optimization).
2. **Synchronous Run Limits**:
   - Maximum 200 requirements per synchronous generator run to prevent HTTP timeout.
   - Maximum 3 candidate solutions generated per run.
   - Execution limit of 60 seconds.
3. **Manual Candidate Application**:
   - Generated candidate schedules are staged in preview tables (`schedule_candidate_entries`) and **never auto-applied** to active schedules.
   - Admin/Wakasek must review soft scores and explicitly click "Apply Candidate".

## Scope & Architectural Boundaries (Prohibitions Kept)
- No teacher duty rosters / piket.
- No automatic substitution teacher assignment (*absensi / pengganti guru*).
- No SK / Surat Tugas generation.
- No WhatsApp or SMS notification integration.
- No external Google Calendar or iCal synchronization.
- No direct Kurikulum Merdeka API sync.
