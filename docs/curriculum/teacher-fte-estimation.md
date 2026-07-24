# Teacher FTE Estimation & Demand Formula

## 1. Overview & Disclaimer
Teacher capacity planning metrics in Akademia are designated as **Estimasi Kebutuhan Guru (Full-Time Equivalent / FTE)**, reflecting theoretical workload demand derived from active curriculum structures and classroom counts. They do not represent absolute payroll or staffing obligations.

## 2. Mathematical Formulas

### Required Total Hours
$$\text{Required Total Hours} = \sum (\text{effective\_weekly\_hours} \times \text{classroom\_count})$$
*(only for subjects where `counts_as_teaching_load = 1`)*

### Available Teacher Capacity
$$\text{Available Capacity} = \text{assigned\_teacher\_count} \times \text{policy\_target\_hours (e.g. 40 JP)}$$

### Estimated Teacher FTE (Target Load Basis)
$$\text{Estimated Teacher FTE} = \frac{\text{Required Total Hours}}{\text{policy\_target\_hours (40.00 JP)}}$$

### Estimated Teacher FTE (Minimum Load Basis)
$$\text{Estimated FTE at Minimum Load} = \frac{\text{Required Total Hours}}{\text{policy\_minimum\_hours (24.00 JP)}}$$

## 3. Workload Policy Single Source of Truth
Policy thresholds (`minimum_teaching_hours`, `target_total_hours`, `maximum_total_hours`) are dynamically resolved from **Milestone 4 Workload Policies** (`workload_policies` table via `TeacherWorkloadCalculationService`), ensuring single-source-of-truth integrity.
