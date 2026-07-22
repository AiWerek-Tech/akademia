# Akademia Platform Roles & Permissions Matrix

---

## 1. Permission Naming Conventions

All permissions follow standard `<module>.<action>` dot notation:

- **`assignments.*`**: Teaching assignment versioning, matrix, allocation, workflow & staging import.
- **`workloads.*`**: Teacher workload calculation, policies, dashboards & exports.
- **`duties.*`**: Additional duty type definition & teacher duty assignments.

---

## 2. Core Milestone 4 Permissions

| Permission Code | Module | Name | Description |
| :--- | :---: | :--- | :--- |
| `assignments.view` | `assignments` | View Assignments | Viewing assignment versions and matrix |
| `assignments.manage` | `assignments` | Manage Assignments | Creating & editing teaching assignments |
| `assignments.validate` | `assignments` | Validate Assignments | Executing business rule validation |
| `assignments.review` | `assignments` | Review Assignments | Reviewing validated assignment versions |
| `assignments.approve` | `assignments` | Approve Assignments | Approving assignment versions |
| `assignments.lock` | `assignments` | Lock Assignments | Locking approved assignment versions |
| `assignments.import` | `assignments` | Import Assignments | Staging & applying Excel assignment imports |
| `assignments.export` | `assignments` | Export Assignments | Exporting assignment matrices & reports |
| `assignments.revise` | `assignments` | Revise Assignments | Creating revisions of locked versions |
| `workloads.view` | `workloads` | View Workloads | Viewing workload dashboard and snapshots |
| `workloads.manage` | `workloads` | Manage Workload Policies | Creating & editing workload policies |
| `workloads.recalculate` | `workloads` | Recalculate Workloads | Triggering recalculation of workload snapshots |
| `workloads.export` | `workloads` | Export Workloads | Exporting workload calculation reports |
| `duties.view` | `duties` | View Additional Duties | Viewing additional duty assignments |
| `duties.manage` | `duties` | Manage Additional Duties | Assigning additional duties to teachers |
