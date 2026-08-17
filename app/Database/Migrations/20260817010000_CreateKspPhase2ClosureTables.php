<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKspPhase2ClosureTables extends Migration
{
    public function up()
    {
        $this->createRegulationRules();
        $this->createComplianceRuns();
        $this->createComplianceResults();
        $this->createEvidence();
        $this->createGeneratedDocuments();
    }

    public function down()
    {
        foreach (['ksp_generated_documents', 'ksp_evidence', 'ksp_compliance_results', 'ksp_compliance_runs', 'regulation_rules'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createRegulationRules(): void
    {
        $this->forge->addField($this->base() + [
            'regulation_version_id' => $this->bigInt(),
            'rule_code' => ['type'=>'VARCHAR','constraint'=>120],
            'rule_class' => ['type'=>'VARCHAR','constraint'=>30],
            'category' => ['type'=>'VARCHAR','constraint'=>60],
            'severity' => ['type'=>'VARCHAR','constraint'=>20],
            'evaluator_key' => ['type'=>'VARCHAR','constraint'=>80],
            'expected_json' => ['type'=>'LONGTEXT'],
            'parameters_json' => ['type'=>'LONGTEXT','null'=>true],
            'suggested_action' => ['type'=>'TEXT'],
            'effective_from' => ['type'=>'DATE','null'=>true],
            'effective_until' => ['type'=>'DATE','null'=>true],
            'is_active' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
        ] + $this->audit());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['regulation_version_id','rule_code']);
        $this->forge->addForeignKey('regulation_version_id','regulation_versions','id','CASCADE','RESTRICT');
        $this->forge->createTable('regulation_rules', true);
    }

    private function createComplianceRuns(): void
    {
        $this->forge->addField($this->base() + [
            'ksp_version_id'=>$this->bigInt(),
            'source_revision'=>['type'=>'VARCHAR','constraint'=>120],
            'overall_status'=>['type'=>'VARCHAR','constraint'=>20],
            'total_count'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'pass_count'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'warning_count'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'fail_count'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'not_applicable_count'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'generated_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'generated_at'=>['type'=>'DATETIME'],
            'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('ksp_version_id','ksp_versions','id','CASCADE','RESTRICT');
        $this->forge->createTable('ksp_compliance_runs', true);
    }

    private function createComplianceResults(): void
    {
        $this->forge->addField($this->base() + [
            'compliance_run_id'=>$this->bigInt(),
            'regulation_rule_id'=>$this->bigInt(true),
            'rule_code'=>['type'=>'VARCHAR','constraint'=>120],
            'rule_class'=>['type'=>'VARCHAR','constraint'=>30],
            'regulation_version'=>['type'=>'VARCHAR','constraint'=>120],
            'result_status'=>['type'=>'VARCHAR','constraint'=>20],
            'expected'=>['type'=>'LONGTEXT'],
            'actual'=>['type'=>'LONGTEXT'],
            'severity'=>['type'=>'VARCHAR','constraint'=>20],
            'source_reference'=>['type'=>'TEXT'],
            'provenance_json'=>['type'=>'LONGTEXT'],
            'suggested_action'=>['type'=>'TEXT'],
            'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('compliance_run_id','ksp_compliance_runs','id','RESTRICT','CASCADE');
        $this->forge->addForeignKey('regulation_rule_id','regulation_rules','id','RESTRICT','SET NULL');
        $this->forge->createTable('ksp_compliance_results', true);
    }

    private function createEvidence(): void
    {
        $this->forge->addField($this->base() + [
            'ksp_version_id'=>$this->bigInt(),
            'target_type'=>['type'=>'VARCHAR','constraint'=>40],
            'target_uuid'=>['type'=>'CHAR','constraint'=>36],
            'evidence_type'=>['type'=>'VARCHAR','constraint'=>40],
            'source_name'=>['type'=>'VARCHAR','constraint'=>255],
            'evidence_date'=>['type'=>'DATE'],
            'description'=>['type'=>'TEXT'],
            'reference_uri'=>['type'=>'VARCHAR','constraint'=>1000,'null'=>true],
            'storage_reference'=>['type'=>'VARCHAR','constraint'=>1000,'null'=>true],
            'original_filename'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'mime_type'=>['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'file_size'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'file_hash'=>['type'=>'CHAR','constraint'=>64,'null'=>true],
            'created_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['ksp_version_id','target_type','target_uuid']);
        $this->forge->addForeignKey('ksp_version_id','ksp_versions','id','CASCADE','RESTRICT');
        $this->forge->createTable('ksp_evidence', true);
    }

    private function createGeneratedDocuments(): void
    {
        $this->forge->addField($this->base() + [
            'ksp_version_id'=>$this->bigInt(),
            'format'=>['type'=>'VARCHAR','constraint'=>10],
            'source_revision'=>['type'=>'VARCHAR','constraint'=>120],
            'source_snapshot_hash'=>['type'=>'CHAR','constraint'=>64],
            'generated_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'generated_at'=>['type'=>'DATETIME'],
            'document_hash'=>['type'=>'CHAR','constraint'=>64],
            'storage_reference'=>['type'=>'VARCHAR','constraint'=>1000],
            'file_size'=>['type'=>'BIGINT','unsigned'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['ksp_version_id','format']);
        $this->forge->addForeignKey('ksp_version_id','ksp_versions','id','CASCADE','RESTRICT');
        $this->forge->createTable('ksp_generated_documents', true);
    }

    private function base(): array { return ['id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],'uuid'=>['type'=>'CHAR','constraint'=>36]]; }
    private function bigInt(bool $null=false): array { return ['type'=>'BIGINT','unsigned'=>true,'null'=>$null]; }
    private function audit(): array { return ['created_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],'updated_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true]]; }
}
