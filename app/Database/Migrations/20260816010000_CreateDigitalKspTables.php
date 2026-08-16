<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDigitalKspTables extends Migration
{
    public function up()
    {
        $this->createKspVersions();
        $this->createSectionStatuses();
        $this->createContextSnapshots();
        $this->createContextEvidence();
        $this->createVisionMissionGoals();
        $this->createLearningOrganizations();
        $this->createEvaluations();
        $this->createImprovementActions();
    }

    public function down()
    {
        foreach (['improvement_actions','ksp_evaluations','ksp_learning_organizations','ksp_vision_mission_goals','school_context_evidence','school_context_snapshots','ksp_section_statuses','ksp_versions'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function baseFields(): array
    {
        return [
            'id' => ['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'uuid' => ['type'=>'CHAR','constraint'=>36],
        ];
    }

    private function auditFields(): array
    {
        return [
            'revision_number' => ['type'=>'INT','unsigned'=>true,'default'=>1],
            'created_by' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'updated_by' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ];
    }

    private function finish(string $table, array $unique = [], array $foreign = []): void
    {
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        foreach ($unique as $key) $this->forge->addUniqueKey($key);
        foreach ($foreign as [$field,$target,$targetField,$delete]) $this->forge->addForeignKey($field,$target,$targetField,'CASCADE',$delete);
        $this->forge->createTable($table, true);
    }

    private function createKspVersions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'=>['type'=>'BIGINT','unsigned'=>true],
            'academic_period_id'=>['type'=>'INT','unsigned'=>true],
            'code'=>['type'=>'VARCHAR','constraint'=>80],
            'title'=>['type'=>'VARCHAR','constraint'=>255],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'DRAFT'],
            'effective_from'=>['type'=>'DATE','null'=>true],
            'effective_to'=>['type'=>'DATE','null'=>true],
            'supersedes_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'reviewed_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'reviewed_at'=>['type'=>'DATETIME','null'=>true],
            'approved_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'approved_at'=>['type'=>'DATETIME','null'=>true],
            'locked_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'locked_at'=>['type'=>'DATETIME','null'=>true],
        ] + $this->auditFields());
        $this->finish('ksp_versions', [['unit_id','academic_period_id','code']], [
            ['unit_id','school_units','id','RESTRICT'],['academic_period_id','academic_periods','id','RESTRICT'],['supersedes_id','ksp_versions','id','SET NULL'],
        ]);
    }

    private function createSectionStatuses(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_version_id'=>['type'=>'BIGINT','unsigned'=>true],
            'section_code'=>['type'=>'VARCHAR','constraint'=>50],
            'completion_percent'=>['type'=>'TINYINT','unsigned'=>true,'default'=>0],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'EMPTY'],
            'notes'=>['type'=>'TEXT','null'=>true],
        ] + $this->auditFields());
        $this->finish('ksp_section_statuses', [['ksp_version_id','section_code']], [['ksp_version_id','ksp_versions','id','RESTRICT']]);
    }

    private function createContextSnapshots(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_version_id'=>['type'=>'BIGINT','unsigned'=>true],
            'context_type'=>['type'=>'VARCHAR','constraint'=>50],
            'title'=>['type'=>'VARCHAR','constraint'=>255],
            'summary'=>['type'=>'TEXT'],
            'finding'=>['type'=>'TEXT','null'=>true],
            'source_type'=>['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'source_reference'=>['type'=>'VARCHAR','constraint'=>500,'null'=>true],
        ] + $this->auditFields());
        $this->finish('school_context_snapshots', [], [['ksp_version_id','ksp_versions','id','RESTRICT']]);
    }

    private function createContextEvidence(): void
    {
        $this->forge->addField($this->baseFields() + [
            'context_snapshot_id'=>['type'=>'BIGINT','unsigned'=>true],
            'evidence_type'=>['type'=>'VARCHAR','constraint'=>50],
            'title'=>['type'=>'VARCHAR','constraint'=>255],
            'source_uri'=>['type'=>'VARCHAR','constraint'=>1000,'null'=>true],
            'source_hash'=>['type'=>'CHAR','constraint'=>64,'null'=>true],
            'notes'=>['type'=>'TEXT','null'=>true],
        ] + $this->auditFields());
        $this->finish('school_context_evidence', [], [['context_snapshot_id','school_context_snapshots','id','RESTRICT']]);
    }

    private function createVisionMissionGoals(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_version_id'=>['type'=>'BIGINT','unsigned'=>true],
            'statement_type'=>['type'=>'VARCHAR','constraint'=>20],
            'statement'=>['type'=>'TEXT'],
            'measure'=>['type'=>'VARCHAR','constraint'=>500,'null'=>true],
            'target_value'=>['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'graduate_profile_dimension_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'sort_order'=>['type'=>'INT','unsigned'=>true,'default'=>1],
        ] + $this->auditFields());
        $this->finish('ksp_vision_mission_goals', [], [
            ['ksp_version_id','ksp_versions','id','RESTRICT'],['graduate_profile_dimension_id','graduate_profile_dimensions','id','SET NULL'],
        ]);
    }

    private function createLearningOrganizations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_version_id'=>['type'=>'BIGINT','unsigned'=>true],
            'category'=>['type'=>'VARCHAR','constraint'=>30],
            'delivery_model'=>['type'=>'VARCHAR','constraint'=>30],
            'title'=>['type'=>'VARCHAR','constraint'=>255],
            'description'=>['type'=>'TEXT'],
            'annual_minutes'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'source_record_type'=>['type'=>'VARCHAR','constraint'=>80,'null'=>true],
            'source_record_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
        ] + $this->auditFields());
        $this->finish('ksp_learning_organizations', [], [['ksp_version_id','ksp_versions','id','RESTRICT']]);
    }

    private function createEvaluations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_version_id'=>['type'=>'BIGINT','unsigned'=>true],
            'evaluation_period'=>['type'=>'VARCHAR','constraint'=>100],
            'objective'=>['type'=>'TEXT'],
            'target_value'=>['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'actual_value'=>['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'finding'=>['type'=>'TEXT','null'=>true],
            'root_cause'=>['type'=>'TEXT','null'=>true],
            'decision'=>['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'DRAFT'],
        ] + $this->auditFields());
        $this->finish('ksp_evaluations', [], [['ksp_version_id','ksp_versions','id','RESTRICT']]);
    }

    private function createImprovementActions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'ksp_evaluation_id'=>['type'=>'BIGINT','unsigned'=>true],
            'unit_id'=>['type'=>'BIGINT','unsigned'=>true],
            'title'=>['type'=>'VARCHAR','constraint'=>255],
            'description'=>['type'=>'TEXT','null'=>true],
            'owner_user_id'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'due_date'=>['type'=>'DATE','null'=>true],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'OPEN'],
            'outcome'=>['type'=>'TEXT','null'=>true],
        ] + $this->auditFields());
        $this->finish('improvement_actions', [], [
            ['ksp_evaluation_id','ksp_evaluations','id','RESTRICT'],['unit_id','school_units','id','RESTRICT'],['owner_user_id','users','id','SET NULL'],
        ]);
    }
}
