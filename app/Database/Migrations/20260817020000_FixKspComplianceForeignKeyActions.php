<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixKspComplianceForeignKeyActions extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE ksp_compliance_results DROP FOREIGN KEY ksp_compliance_results_compliance_run_id_foreign');
        $this->db->query('ALTER TABLE ksp_compliance_results DROP FOREIGN KEY ksp_compliance_results_regulation_rule_id_foreign');
        $this->db->query('ALTER TABLE ksp_compliance_results ADD CONSTRAINT ksp_compliance_results_compliance_run_id_foreign FOREIGN KEY (compliance_run_id) REFERENCES ksp_compliance_runs(id) ON UPDATE RESTRICT ON DELETE CASCADE');
        $this->db->query('ALTER TABLE ksp_compliance_results ADD CONSTRAINT ksp_compliance_results_regulation_rule_id_foreign FOREIGN KEY (regulation_rule_id) REFERENCES regulation_rules(id) ON UPDATE RESTRICT ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE ksp_compliance_results DROP FOREIGN KEY ksp_compliance_results_compliance_run_id_foreign');
        $this->db->query('ALTER TABLE ksp_compliance_results DROP FOREIGN KEY ksp_compliance_results_regulation_rule_id_foreign');
        $this->db->query('ALTER TABLE ksp_compliance_results ADD CONSTRAINT ksp_compliance_results_compliance_run_id_foreign FOREIGN KEY (compliance_run_id) REFERENCES ksp_compliance_runs(id) ON UPDATE CASCADE ON DELETE RESTRICT');
        $this->db->query('ALTER TABLE ksp_compliance_results ADD CONSTRAINT ksp_compliance_results_regulation_rule_id_foreign FOREIGN KEY (regulation_rule_id) REFERENCES regulation_rules(id) ON UPDATE SET NULL ON DELETE RESTRICT');
    }
}
