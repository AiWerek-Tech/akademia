<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeScheduleRequirementIdNullable extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE schedule_entries MODIFY COLUMN schedule_requirement_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE schedule_entries MODIFY COLUMN subject_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE schedule_entries MODIFY COLUMN teacher_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");

        $this->db->query("ALTER TABLE schedule_candidate_entries MODIFY COLUMN schedule_requirement_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE schedule_candidate_entries MODIFY COLUMN subject_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE schedule_candidate_entries MODIFY COLUMN teacher_id BIGINT(20) UNSIGNED NULL DEFAULT NULL;");
    }

    public function down()
    {
        // No down needed
    }
}
