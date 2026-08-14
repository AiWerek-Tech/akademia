<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherSubstitutionRepairCandidates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'substitution_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'READY'],
            'score' => ['type' => 'INT', 'default' => 0],
            'moved_entry_count' => ['type' => 'INT', 'default' => 0],
            'critical_before' => ['type' => 'INT', 'default' => 0],
            'critical_after' => ['type' => 'INT', 'default' => 0],
            'changes_json' => ['type' => 'JSON'],
            'base_revisions_json' => ['type' => 'JSON'],
            'diagnostics_json' => ['type' => 'JSON', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true],
            'applied_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'applied_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['substitution_id', 'status']);
        $this->forge->addForeignKey('substitution_id', 'teacher_schedule_substitutions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('applied_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('teacher_substitution_repair_candidates', true);
    }

    public function down()
    {
        $this->forge->dropTable('teacher_substitution_repair_candidates', true);
    }
}
