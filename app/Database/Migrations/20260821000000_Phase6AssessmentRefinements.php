<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 6 refinements:
 *  - assessment_evidence gains alignment to graduate profile dimensions and
 *    (future) cocurricular objectives, per blueprint §7.
 *  - interventions gain criterion-level targeting so recommendations point at
 *    the specific failing criterion ("targeted remediation"), per blueprint §5.
 */
class Phase6AssessmentRefinements extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('assessment_evidence')) {
            $evidenceColumns = [];
            if (! $this->db->fieldExists('profile_dimension_id', 'assessment_evidence')) {
                $evidenceColumns['profile_dimension_id'] = $this->bigInt(true);
            }
            if (! $this->db->fieldExists('cocurricular_objective_id', 'assessment_evidence')) {
                $evidenceColumns['cocurricular_objective_id'] = $this->bigInt(true);
            }
            if ($evidenceColumns !== []) {
                $this->forge->addColumn('assessment_evidence', $evidenceColumns);
            }
            if ($this->db->tableExists('graduate_profile_dimensions')) {
                $this->addForeign('profile_dimension_id', 'graduate_profile_dimensions', 'fk_evidence_profile_dim');
            }
            $this->forge->processIndexes('assessment_evidence');
        }

        if ($this->db->tableExists('interventions')) {
            if (! $this->db->fieldExists('criterion_id', 'interventions')) {
                $this->forge->addColumn('interventions', ['criterion_id' => $this->bigInt(true)]);
            }
            if ($this->db->tableExists('assessment_criteria')) {
                $this->addForeign('criterion_id', 'assessment_criteria', 'fk_intervention_criterion');
            }
            $this->forge->processIndexes('interventions');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('assessment_evidence')) {
            $this->dropForeign('assessment_evidence', 'fk_evidence_profile_dim');
            $this->forge->dropColumn('assessment_evidence', ['profile_dimension_id', 'cocurricular_objective_id']);
        }

        if ($this->db->tableExists('interventions')) {
            $this->dropForeign('interventions', 'fk_intervention_criterion');
            $this->forge->dropColumn('interventions', ['criterion_id']);
        }
    }

    /**
     * SQLite rejects explicitly named foreign keys; let the driver generate one.
     */
    private function addForeign(string $column, string $references, string $name): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $this->forge->addForeignKey($column, $references, 'id', 'SET NULL', 'RESTRICT', $name);
        } else {
            $this->forge->addForeignKey($column, $references, 'id', 'SET NULL', 'RESTRICT');
        }
    }

    private function dropForeign(string $table, string $name): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $this->forge->dropForeignKey($table, $name);
        }
    }

    private function bigInt(bool $nullable = false): array
    {
        return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
    }
}