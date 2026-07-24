# Official vs Custom Hours Allocation Model

## 1. Concept & Requirements
The curriculum structure supports dual weekly hour allocations:
1. `official_weekly_hours`: Standard hours established by Kemendikdasmen.
2. `custom_weekly_hours`: Local school overrides to adapt to specific unit requirements.

## 2. Effective Hours Calculation Logic
$$\text{effective\_weekly\_hours} = \begin{cases} \text{official\_weekly\_hours} & \text{if } \text{effective\_source} = \text{'OFFICIAL'} \\ \text{custom\_weekly\_hours} & \text{if } \text{effective\_source} = \text{'CUSTOM'} \\ \text{manual\_weekly\_hours} & \text{if } \text{effective\_source} = \text{'MANUAL'} \end{cases}$$

## 3. Business Rules & Guardrails
- **Override behavior**: `CUSTOM` hours replace `OFFICIAL` hours completely during calculations.
- **Mandatory Reasons**: Changing `effective_source` to `CUSTOM` requires a non-empty `adjustment_reason`.
- **Validation**: Zero or negative custom hours are rejected.
- **Optimistic Concurrency**: Updates validate `revision_number` to prevent stale data overwrites.
- **Immutability**: Locked curriculum versions cannot be modified.
