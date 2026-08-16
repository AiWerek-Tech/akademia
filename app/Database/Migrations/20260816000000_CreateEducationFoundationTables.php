<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEducationFoundationTables extends Migration
{
    public function up()
    {
        $this->createRegulations();
        $this->createRegulationVersions();
        $this->createCurriculumSources();
        $this->createGraduateProfileDimensions();
        $this->createLearningOutcomes();
        $this->createCurriculumElements();
        $this->createLearningObjectives();
        $this->createObjectiveCriteria();
        $this->createLearningSequences();
        $this->createLearningSequenceItems();
        $this->createLearningPacks();
        $this->createLearningPackObjectives();
        $this->createLearningPackSequences();
        $this->createImportStaging();
    }

    public function down()
    {
        foreach ([
            'education_foundation_import_rows', 'education_foundation_import_batches',
            'subject_learning_pack_sequences', 'subject_learning_pack_objectives',
            'subject_learning_packs', 'learning_sequence_items', 'learning_sequences_atp',
            'objective_criteria', 'learning_objectives_tp', 'curriculum_elements',
            'learning_outcomes_cp', 'graduate_profile_dimensions', 'curriculum_sources',
            'regulation_versions', 'regulations',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createRegulations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'authority' => ['type' => 'VARCHAR', 'constraint' => 150],
            'regulation_type' => ['type' => 'VARCHAR', 'constraint' => 60],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'published_at' => ['type' => 'DATE', 'null' => true],
            'effective_from' => ['type' => 'DATE', 'null' => true],
            'effective_until' => ['type' => 'DATE', 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->keys('regulations', [['code']]);
    }

    private function createRegulationVersions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'regulation_id' => $this->bigInt(),
            'version_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'document_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'document_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('regulation_id', 'regulations', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('regulation_versions', [['regulation_id', 'version_number']]);
    }

    private function createCurriculumSources(): void
    {
        $this->forge->addField($this->baseFields() + [
            'regulation_version_id' => $this->bigInt(true),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'issuer' => ['type' => 'VARCHAR', 'constraint' => 150],
            'source_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'source_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'published_at' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'metadata_json' => ['type' => 'LONGTEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('regulation_version_id', 'regulation_versions', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('curriculum_sources', [['code']]);
    }

    private function createGraduateProfileDimensions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'unsigned' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->auditFields());
        $this->keys('graduate_profile_dimensions', [['code'], ['sort_order']]);
    }

    private function createLearningOutcomes(): void
    {
        $this->forge->addField($this->baseFields() + [
            'curriculum_version_id' => $this->bigInt(),
            'curriculum_source_id' => $this->bigInt(true),
            'subject_id' => $this->bigInt(),
            'grade_level_id' => $this->bigInt(),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'phase' => ['type' => 'VARCHAR', 'constraint' => 20],
            'statement' => ['type' => 'LONGTEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('grade_level_id', 'grade_levels', 'id', 'RESTRICT', 'RESTRICT');
        $this->keys('learning_outcomes_cp', [['curriculum_version_id', 'subject_id', 'grade_level_id', 'code']]);
    }

    private function createCurriculumElements(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_outcome_id' => $this->bigInt(),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'description' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'unsigned' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_outcome_id', 'learning_outcomes_cp', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('curriculum_elements', [['learning_outcome_id', 'code'], ['learning_outcome_id', 'sort_order']]);
    }

    private function createLearningObjectives(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_outcome_id' => $this->bigInt(),
            'curriculum_element_id' => $this->bigInt(true),
            'unit_id' => $this->bigInt(true),
            'parent_objective_id' => $this->bigInt(true),
            'source_level' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'NATIONAL'],
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'statement' => ['type' => 'LONGTEXT'],
            'rationale' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_outcome_id', 'learning_outcomes_cp', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_element_id', 'curriculum_elements', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('parent_objective_id', 'learning_objectives_tp', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_objectives_tp', [['learning_outcome_id', 'unit_id', 'code']]);
    }

    private function createObjectiveCriteria(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_objective_id' => $this->bigInt(),
            'description' => ['type' => 'TEXT'],
            'sort_order' => ['type' => 'INT', 'unsigned' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('objective_criteria', [['learning_objective_id', 'sort_order']]);
    }

    private function createLearningSequences(): void
    {
        $this->forge->addField($this->baseFields() + [
            'curriculum_version_id' => $this->bigInt(),
            'unit_id' => $this->bigInt(),
            'subject_id' => $this->bigInt(),
            'grade_level_id' => $this->bigInt(),
            'parent_sequence_id' => $this->bigInt(true),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'phase' => ['type' => 'VARCHAR', 'constraint' => 20],
            'description' => ['type' => 'TEXT', 'null' => true],
            'workflow_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'validated_by' => $this->int(true), 'validated_at' => ['type' => 'DATETIME', 'null' => true],
            'reviewed_by' => $this->int(true), 'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => $this->int(true), 'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'locked_by' => $this->int(true), 'locked_at' => ['type' => 'DATETIME', 'null' => true],
            'archived_by' => $this->int(true), 'archived_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('grade_level_id', 'grade_levels', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('parent_sequence_id', 'learning_sequences_atp', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_sequences_atp', [['curriculum_version_id', 'unit_id', 'subject_id', 'grade_level_id', 'code']]);
    }

    private function createLearningSequenceItems(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_sequence_id' => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
            'sort_order' => ['type' => 'INT', 'unsigned' => true],
            'estimated_hours' => ['type' => 'DECIMAL', 'constraint' => '7,2', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_sequence_id', 'learning_sequences_atp', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'RESTRICT', 'RESTRICT');
        $this->keys('learning_sequence_items', [['learning_sequence_id', 'learning_objective_id'], ['learning_sequence_id', 'sort_order']]);
    }

    private function createLearningPacks(): void
    {
        $this->forge->addField($this->baseFields() + [
            'curriculum_version_id' => $this->bigInt(), 'unit_id' => $this->bigInt(),
            'subject_id' => $this->bigInt(), 'grade_level_id' => $this->bigInt(),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('grade_level_id', 'grade_levels', 'id', 'RESTRICT', 'RESTRICT');
        $this->keys('subject_learning_packs', [['curriculum_version_id', 'unit_id', 'subject_id', 'grade_level_id', 'code']]);
    }

    private function createLearningPackObjectives(): void
    {
        $this->forge->addField([
            'subject_learning_pack_id' => $this->bigInt(), 'learning_objective_id' => $this->bigInt(),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['subject_learning_pack_id', 'learning_objective_id'], true);
        $this->forge->addForeignKey('subject_learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT', 'fk_pack_objective_pack');
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'RESTRICT', 'RESTRICT', 'fk_pack_objective_tp');
        $this->forge->createTable('subject_learning_pack_objectives', true);
    }

    private function createLearningPackSequences(): void
    {
        $this->forge->addField([
            'subject_learning_pack_id' => $this->bigInt(), 'learning_sequence_id' => $this->bigInt(),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['subject_learning_pack_id', 'learning_sequence_id'], true);
        $this->forge->addForeignKey('subject_learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT', 'fk_pack_sequence_pack');
        $this->forge->addForeignKey('learning_sequence_id', 'learning_sequences_atp', 'id', 'RESTRICT', 'RESTRICT', 'fk_pack_sequence_atp');
        $this->forge->createTable('subject_learning_pack_sequences', true);
    }

    private function createImportStaging(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id' => $this->bigInt(), 'curriculum_version_id' => $this->bigInt(),
            'source_filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'UPLOADED'],
            'total_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'valid_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'applied_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'applied_by' => $this->int(true), 'applied_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT', 'fk_ef_import_batch_unit');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'RESTRICT', 'RESTRICT', 'fk_ef_import_batch_version');
        $this->keys('education_foundation_import_batches', [['unit_id', 'source_hash']]);

        $this->forge->addField($this->baseFields() + [
            'import_batch_id' => $this->bigInt(),
            'row_number' => ['type' => 'INT', 'unsigned' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'payload_json' => ['type' => 'LONGTEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PENDING'],
            'errors_json' => ['type' => 'LONGTEXT', 'null' => true],
            'warnings_json' => ['type' => 'LONGTEXT', 'null' => true],
            'target_entity_id' => $this->bigInt(true),
        ] + $this->auditFields());
        $this->forge->addForeignKey('import_batch_id', 'education_foundation_import_batches', 'id', 'CASCADE', 'RESTRICT', 'fk_ef_import_row_batch');
        $this->keys('education_foundation_import_rows', [['import_batch_id', 'row_number']]);
    }

    private function baseFields(): array
    {
        return [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
        ];
    }

    private function auditFields(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => $this->int(true),
            'updated_by' => $this->int(true),
        ];
    }

    private function bigInt(bool $null = false): array
    {
        return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $null];
    }

    private function int(bool $null = false): array
    {
        return ['type' => 'INT', 'unsigned' => true, 'null' => $null];
    }

    private function keys(string $table, array $uniqueKeys): void
    {
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        foreach ($uniqueKeys as $key) {
            $this->forge->addUniqueKey($key);
        }
        $this->forge->createTable($table, true);
    }
}
