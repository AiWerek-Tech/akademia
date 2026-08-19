<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhase4Iteration2Tables extends Migration
{
    public function up(): void
    {
        $this->createAssessmentRubrics();
        $this->createActivityResources();
        $this->extendLessonPlanActivities();
    }

    public function down(): void
    {
        // Reverse extend
        if ($this->db->fieldExists('graduate_profile_alignment', 'lesson_plan_activities')) {
            $this->forge->dropColumn('lesson_plan_activities', 'graduate_profile_alignment');
        }

        $this->forge->dropTable('lesson_plan_activity_resources', true);
        $this->forge->dropTable('lesson_plan_assessment_rubrics', true);
    }

    private function createAssessmentRubrics(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'lesson_plan_assessment_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'criterion_description' => ['type' => 'VARCHAR', 'constraint' => 500],
            'rubric_levels' => ['type' => 'TEXT', 'null' => true],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['lesson_plan_assessment_id', 'sequence_order']);
        $this->forge->addForeignKey('lesson_plan_assessment_id', 'lesson_plan_assessments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('lesson_plan_assessment_rubrics', true);
    }

    private function createActivityResources(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'lesson_plan_activity_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'learning_resource_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'custom_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'quantity' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'is_required' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('lesson_plan_activity_id');
        $this->forge->addForeignKey('lesson_plan_activity_id', 'lesson_plan_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_resource_id', 'learning_resources', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('lesson_plan_activity_resources', true);
    }

    private function extendLessonPlanActivities(): void
    {
        if (! $this->db->fieldExists('graduate_profile_alignment', 'lesson_plan_activities')) {
            $this->forge->addColumn('lesson_plan_activities', [
                'graduate_profile_alignment' => ['type' => 'TEXT', 'null' => true, 'after' => 'teacher_notes'],
            ]);
        }
    }
}
