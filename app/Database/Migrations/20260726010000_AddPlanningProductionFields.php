<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlanningProductionFields extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('unit_id', 'schedule_versions')) {
            $this->forge->addColumn('schedule_versions', [
                'unit_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'academic_period_id',
                ],
            ]);
            $this->db->query(
                'ALTER TABLE `schedule_versions` ADD CONSTRAINT `schedule_versions_unit_id_foreign` '
                . 'FOREIGN KEY (`unit_id`) REFERENCES `school_units` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT'
            );
        }

        $unitFields = [
            'head_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'email',
            ],
            'head_identifier' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'head_name',
            ],
            'document_city' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'head_identifier',
            ],
            'decree_prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'document_city',
            ],
        ];

        foreach ($unitFields as $name => $definition) {
            if (! $this->db->fieldExists($name, 'school_units')) {
                $this->forge->addColumn('school_units', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (['decree_prefix', 'document_city', 'head_identifier', 'head_name'] as $column) {
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['school_units', $column]
            )->getRowArray();
            if ($exists) {
                $this->db->query(sprintf('ALTER TABLE `school_units` DROP COLUMN `%s`', $column));
            }
        }

        $unitColumn = $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['schedule_versions', 'unit_id']
        )->getRowArray();
        if ($unitColumn) {
            $foreignKey = $this->db->query(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() '
                . 'AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
                ['schedule_versions', 'schedule_versions_unit_id_foreign', 'FOREIGN KEY']
            )->getRowArray();
            if ($foreignKey) {
                $this->db->query('ALTER TABLE `schedule_versions` DROP FOREIGN KEY `schedule_versions_unit_id_foreign`');
            }
            $this->db->query('ALTER TABLE `schedule_versions` DROP COLUMN `unit_id`');
        }
    }
}
