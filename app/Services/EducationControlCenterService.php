<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Database;

class EducationControlCenterService
{
    public static function build(): array
    {
        $db = Database::connect();
        $unitIds = UnitScopeService::accessibleUnitIds();
        $scopedUnits = $unitIds ?: [0];

        $sequenceBuilder = $db->table('learning_sequences_atp')->whereIn('unit_id', $scopedUnits);
        $sequenceTotal = $sequenceBuilder->countAllResults();
        $packTotal = $db->table('subject_learning_packs')->whereIn('unit_id', $scopedUnits)->countAllResults();
        $localObjectives = $db->table('learning_objectives_tp')->whereIn('unit_id', $scopedUnits)->countAllResults();
        $nationalObjectives = $db->table('learning_objectives_tp')->where('unit_id IS NULL')->countAllResults();
        $pendingImports = $db->table('education_foundation_import_batches')
            ->whereIn('unit_id', $scopedUnits)->whereIn('status', ['STAGED', 'READY', 'INVALID'])->countAllResults();
        $kspTotal = $db->table('ksp_versions')->whereIn('unit_id', $scopedUnits)->countAllResults();
        $kspReview = $db->table('ksp_versions')->whereIn('unit_id', $scopedUnits)->where('status', 'REVIEW')->countAllResults();

        $statusRows = $db->table('learning_sequences_atp')
            ->select('workflow_status, COUNT(*) AS total', false)
            ->whereIn('unit_id', $scopedUnits)
            ->groupBy('workflow_status')->get()->getResultArray();
        $permissions = (new UserModel())->getPermissions(
            (int) session()->get('user_id'),
            session()->get('active_unit_id') ? (int) session()->get('active_unit_id') : null
        );

        return [
            'persona' => self::persona(),
            'metrics' => [
                'regulations' => $db->table('regulations')->countAllResults(),
                'sources' => $db->table('curriculum_sources')->countAllResults(),
                'dimensions' => $db->table('graduate_profile_dimensions')->countAllResults(),
                'outcomes' => $db->table('learning_outcomes_cp')->countAllResults(),
                'objectives' => $nationalObjectives + $localObjectives,
                'sequences' => $sequenceTotal,
                'packs' => $packTotal,
                'pending_imports' => $pendingImports,
                'ksp_versions' => $kspTotal,
            ],
            'sequence_statuses' => array_column($statusRows, 'total', 'workflow_status'),
            'work_queue' => self::workQueue($pendingImports, $kspReview, $statusRows, $permissions),
            'unit_count' => count($unitIds),
        ];
    }

    private static function persona(): string
    {
        $roles = (array) (session()->get('all_role_codes') ?? [session()->get('role_code')]);
        if (array_intersect($roles, ['super_admin', 'superadmin'])) return 'platform';
        if (array_intersect($roles, ['kepala_sekolah', 'viewer_yayasan'])) return 'executive';
        if (array_intersect($roles, ['wakasek_kurikulum', 'admin_smp', 'admin_sma'])) return 'curriculum';
        if (array_intersect($roles, ['guru', 'wali_kelas'])) return 'teacher';
        return 'viewer';
    }

    private static function workQueue(int $pendingImports, int $kspReview, array $statusRows, array $permissions): array
    {
        $statuses = array_map('intval', array_column($statusRows, 'total', 'workflow_status'));
        $queue = [];
        if (in_array('ksp.review', $permissions, true) || in_array('ksp.approve', $permissions, true)) $queue[] = ['label' => 'KSP menunggu keputusan', 'count' => $kspReview, 'href' => 'education/ksp'];
        if (in_array('learning_sequences.validate', $permissions, true)) $queue[] = ['label' => 'ATP menunggu validasi', 'count' => $statuses['DRAFT'] ?? 0, 'href' => 'curriculum/sequences'];
        if (in_array('learning_sequences.review', $permissions, true)) $queue[] = ['label' => 'ATP menunggu review', 'count' => $statuses['VALIDATED'] ?? 0, 'href' => 'curriculum/sequences'];
        if (in_array('learning_sequences.approve', $permissions, true)) $queue[] = ['label' => 'ATP menunggu persetujuan', 'count' => $statuses['REVIEWED'] ?? 0, 'href' => 'curriculum/sequences'];
        if (in_array('learning_outcomes.manage', $permissions, true)) $queue[] = ['label' => 'Batch import perlu ditindaklanjuti', 'count' => $pendingImports, 'href' => 'curriculum/education-imports'];
        if ($queue === []) $queue[] = ['label' => 'ATP yang sedang disusun', 'count' => $statuses['DRAFT'] ?? 0, 'href' => 'curriculum/sequences'];
        return $queue;
    }
}
