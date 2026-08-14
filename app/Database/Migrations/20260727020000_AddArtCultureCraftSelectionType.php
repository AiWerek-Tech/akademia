<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddArtCultureCraftSelectionType extends Migration
{
    public function up()
    {
        if (! $this->hasColumn('elective_periods', 'selection_type')) {
            $this->db->query("ALTER TABLE `elective_periods` ADD COLUMN `selection_type` VARCHAR(30) NOT NULL DEFAULT 'FASE_F_ELECTIVE' AFTER `title`");
        }

        // Drop old unique key if present and create new composite unique key including selection_type
        $keys = $this->db->getIndexData('elective_periods');
        foreach ($keys as $key) {
            if ($key->name === 'uq_elective_period_scope') {
                $this->db->query('ALTER TABLE `elective_periods` DROP INDEX `uq_elective_period_scope`');
                break;
            }
        }

        $hasNewKey = false;
        foreach ($this->db->getIndexData('elective_periods') as $key) {
            if ($key->name === 'uq_elective_period_scope_type') {
                $hasNewKey = true;
                break;
            }
        }

        if (! $hasNewKey && $this->hasColumn('elective_periods', 'selection_type')) {
            $this->db->query('ALTER TABLE `elective_periods` ADD UNIQUE INDEX `uq_elective_period_scope_type` (`unit_id`, `academic_year_id`, `target_grade`, `selection_type`)');
        }
    }

    public function down()
    {
        $keys = $this->db->getIndexData('elective_periods');
        foreach ($keys as $key) {
            if ($key->name === 'uq_elective_period_scope_type') {
                $this->db->query('ALTER TABLE `elective_periods` DROP INDEX `uq_elective_period_scope_type`');
                break;
            }
        }

        if ($this->hasColumn('elective_periods', 'selection_type')) {
            $this->forge->dropColumn('elective_periods', 'selection_type');
        }

        $this->db->query('ALTER TABLE `elective_periods` ADD UNIQUE INDEX `uq_elective_period_scope` (`unit_id`, `academic_year_id`, `target_grade`)');
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column))->getNumRows() > 0;
    }
}
