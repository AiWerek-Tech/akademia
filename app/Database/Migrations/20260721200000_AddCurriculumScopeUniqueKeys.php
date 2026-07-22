<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurriculumScopeUniqueKeys extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('curriculum_structures')) {
            if (!$this->db->fieldExists('grade_default_key', 'curriculum_structures')) {
                $this->db->query("ALTER TABLE `curriculum_structures` ADD `grade_default_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NULL THEN CONCAT(`curriculum_version_id`, '-', `unit_id`, '-', `grade_level_id`, '-', `subject_id`) ELSE NULL END) STORED");
                $this->db->query("ALTER TABLE `curriculum_structures` ADD UNIQUE KEY `uq_grade_default` (`grade_default_key`)");
            }
            if (!$this->db->fieldExists('classroom_override_key', 'curriculum_structures')) {
                $this->db->query("ALTER TABLE `curriculum_structures` ADD `classroom_override_key` VARCHAR(150) GENERATED ALWAYS AS (CASE WHEN `classroom_id` IS NOT NULL THEN CONCAT(`curriculum_version_id`, '-', `classroom_id`, '-', `subject_id`) ELSE NULL END) STORED");
                $this->db->query("ALTER TABLE `curriculum_structures` ADD UNIQUE KEY `uq_classroom_override` (`classroom_override_key`)");
            }
        }
    }

    public function down()
    {
        // Drop unique keys and generated columns
        if ($this->db->tableExists('curriculum_structures')) {
            try {
                $this->db->query("ALTER TABLE `curriculum_structures` DROP KEY `uq_classroom_override`");
            } catch (\Throwable $e) {}
            try {
                $this->db->query("ALTER TABLE `curriculum_structures` DROP KEY `uq_grade_default`");
            } catch (\Throwable $e) {}
            try {
                $this->db->query("ALTER TABLE `curriculum_structures` DROP COLUMN `classroom_override_key`");
            } catch (\Throwable $e) {}
            try {
                $this->db->query("ALTER TABLE `curriculum_structures` DROP COLUMN `grade_default_key`");
            } catch (\Throwable $e) {}
        }
    }
}
