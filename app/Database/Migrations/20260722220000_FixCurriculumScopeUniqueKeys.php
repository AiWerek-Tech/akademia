<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixCurriculumScopeUniqueKeys extends Migration
{
    public function up()
    {
        $this->dropKeyIfPresent('curriculum_structures', 'uq_classroom_override');
        $this->dropKeyIfPresent('curriculum_structures', 'uq_grade_default');
        $this->db->query("ALTER TABLE `curriculum_structures` MODIFY `grade_default_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NULL AND `deleted_at` IS NULL THEN CONCAT(`curriculum_version_id`, '-', `unit_id`, '-', `grade_level_id`, '-', `subject_id`) ELSE NULL END) STORED");
        $this->db->query("ALTER TABLE `curriculum_structures` MODIFY `classroom_override_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NOT NULL AND `deleted_at` IS NULL THEN CONCAT(`curriculum_version_id`, '-', `classroom_id`, '-', `subject_id`) ELSE NULL END) STORED");
        $this->db->query('ALTER TABLE `curriculum_structures` ADD UNIQUE KEY `uq_grade_default` (`grade_default_key`), ADD UNIQUE KEY `uq_classroom_override` (`classroom_override_key`)');
    }

    public function down()
    {
        $this->dropKeyIfPresent('curriculum_structures', 'uq_classroom_override');
        $this->dropKeyIfPresent('curriculum_structures', 'uq_grade_default');
        $this->db->query("ALTER TABLE `curriculum_structures` MODIFY `grade_default_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NULL THEN CONCAT(`curriculum_version_id`, '-', `unit_id`, '-', `grade_level_id`, '-', `subject_id`) ELSE NULL END) STORED");
        $this->db->query("ALTER TABLE `curriculum_structures` MODIFY `classroom_override_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NOT NULL THEN CONCAT(`curriculum_version_id`, '-', `classroom_id`, '-', `subject_id`) ELSE NULL END) STORED");
        $this->db->query('ALTER TABLE `curriculum_structures` ADD UNIQUE KEY `uq_grade_default` (`grade_default_key`), ADD UNIQUE KEY `uq_classroom_override` (`classroom_override_key`)');
    }

    private function dropKeyIfPresent(string $table, string $keyName): void
    {
        $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$keyName}'")->getNumRows() > 0;
        if ($exists) {
            $this->db->query("ALTER TABLE `{$table}` DROP KEY `{$keyName}`");
        }
    }
}