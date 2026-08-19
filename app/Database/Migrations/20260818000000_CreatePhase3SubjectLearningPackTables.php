<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhase3SubjectLearningPackTables extends Migration
{
    private array $permissions = [
        'learning_packs.validate' => ['module' => 'learning_packs', 'name' => 'Validate Learning Packs'],
        'learning_packs.review' => ['module' => 'learning_packs', 'name' => 'Review Learning Packs'],
        'learning_packs.approve' => ['module' => 'learning_packs', 'name' => 'Approve Learning Packs'],
        'learning_packs.lock' => ['module' => 'learning_packs', 'name' => 'Lock Learning Packs'],
        'learning_packs.clone' => ['module' => 'learning_packs', 'name' => 'Clone Learning Packs'],
        'learning_units.manage' => ['module' => 'learning_packs', 'name' => 'Manage Learning Units'],
        'learning_activities.manage' => ['module' => 'learning_packs', 'name' => 'Manage Learning Activities'],
        'learning_resources.manage' => ['module' => 'learning_packs', 'name' => 'Manage Learning Resources'],
        'learning_guidance.manage' => ['module' => 'learning_packs', 'name' => 'Manage Learning Guidance'],
    ];

    public function up(): void
    {
        $this->extendLearningPacks();
        $this->createLearningUnits();
        $this->createLearningUnitObjectives();
        $this->createLearningConcepts();
        $this->createLearningConceptRelations();
        $this->createLearningUnitPrerequisites();
        $this->createLearningMaterialTopics();
        $this->createLearningMisconceptions();
        $this->createLearningActivations();
        $this->createLearningResources();
        $this->createLearningActivities();
        $this->createLearningActivityResources();
        $this->createLearningActivityAlternatives();
        $this->createLearningTeacherGuidance();
        $this->createLearningExpectedResponses();
        $this->createLearningActivityExperiences();
        $this->createPedagogicalPractices();
        $this->createPracticeMaps();
        $this->createGraduateProfileAlignments();
        $this->createInterdisciplinaryLinks();
        $this->createAssessmentReferences();
        $this->createFollowupGuidance();
        $this->createReflectionPrompts();
        $this->createImportStaging();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();

        foreach ([
            'learning_pack_import_rows',
            'learning_pack_import_batches',
            'learning_reflection_prompts',
            'learning_followup_guidance',
            'learning_assessment_references',
            'learning_interdisciplinary_links',
            'learning_graduate_profile_alignments',
            'learning_activity_practices',
            'learning_unit_practices',
            'pedagogical_practices',
            'learning_activity_experiences',
            'learning_expected_responses',
            'learning_teacher_guidance',
            'learning_activity_alternatives',
            'learning_activity_resources',
            'learning_activities',
            'learning_resources',
            'learning_activations',
            'learning_misconceptions',
            'learning_material_topics',
            'learning_unit_prerequisites',
            'learning_concept_relations',
            'learning_concepts',
            'learning_unit_objectives',
            'learning_units',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->dropColumnsIfPresent('subject_learning_packs', [
            'phase',
            'source_type',
            'source_id',
            'source_locator',
            'parent_pack_id',
            'validated_by',
            'validated_at',
            'reviewed_by',
            'reviewed_at',
            'approved_by',
            'approved_at',
            'locked_by',
            'locked_at',
        ]);
    }

    private function extendLearningPacks(): void
    {
        $columns = [
            'phase' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'grade_level_id'],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'CUSTOM', 'after' => 'description'],
            'source_id' => $this->bigInt(true, 'source_type'),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'source_id'],
            'parent_pack_id' => $this->bigInt(true, 'source_locator'),
            'validated_by' => $this->int(true, 'revision_number'),
            'validated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'validated_by'],
            'reviewed_by' => $this->int(true, 'validated_at'),
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'reviewed_by'],
            'approved_by' => $this->int(true, 'reviewed_at'),
            'approved_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'approved_by'],
            'locked_by' => $this->int(true, 'approved_at'),
            'locked_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'locked_by'],
        ];

        foreach ($columns as $name => $definition) {
            if (! $this->hasColumn('subject_learning_packs', $name)) {
                $this->forge->addColumn('subject_learning_packs', [$name => $definition]);
            }
        }
    }

    private function createLearningUnits(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_pack_id' => $this->bigInt(),
            'parent_unit_id' => $this->bigInt(true),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'unit_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'UNIT'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true],
            'estimated_hours' => ['type' => 'DECIMAL', 'constraint' => '7,2', 'null' => true],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'copyright_notes' => ['type' => 'TEXT', 'null' => true],
            'license_notes' => ['type' => 'TEXT', 'null' => true],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['learning_pack_id', 'code'], 'uq_learning_unit_code');
        $this->forge->addKey(['learning_pack_id', 'sequence_order']);
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('parent_unit_id', 'learning_units', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('learning_units', true);
    }

    private function createLearningUnitObjectives(): void
    {
        $this->forge->addField([
            'learning_unit_id' => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
            'role' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PRIMARY'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'estimated_hours' => ['type' => 'DECIMAL', 'constraint' => '7,2', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['learning_unit_id', 'learning_objective_id', 'role'], true);
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('learning_unit_objectives', true);
    }

    private function createLearningConcepts(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_pack_id' => $this->bigInt(),
            'learning_unit_id' => $this->bigInt(true),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'concept_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'CORE'],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['learning_pack_id', 'code'], 'uq_learning_concept_code');
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('learning_concepts', true);
    }

    private function createLearningConceptRelations(): void
    {
        $this->forge->addField([
            'from_concept_id' => $this->bigInt(),
            'to_concept_id' => $this->bigInt(),
            'relation_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['from_concept_id', 'to_concept_id', 'relation_type'], true);
        $this->forge->addForeignKey('from_concept_id', 'learning_concepts', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('to_concept_id', 'learning_concepts', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_concept_relations', true);
    }

    private function createLearningUnitPrerequisites(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_unit_id' => $this->bigInt(),
            'prerequisite_unit_id' => $this->bigInt(true),
            'prerequisite_objective_id' => $this->bigInt(true),
            'prerequisite_concept_id' => $this->bigInt(true),
            'description' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('prerequisite_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('prerequisite_objective_id', 'learning_objectives_tp', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('prerequisite_concept_id', 'learning_concepts', 'id', 'RESTRICT', 'RESTRICT');
        $this->keys('learning_unit_prerequisites', [['learning_unit_id', 'prerequisite_unit_id'], ['learning_unit_id', 'prerequisite_objective_id'], ['learning_unit_id', 'prerequisite_concept_id']]);
    }

    private function createLearningMaterialTopics(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_unit_id' => $this->bigInt(),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'material_level' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'ESSENTIAL'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'teacher_notes' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_material_topics', [['learning_unit_id', 'code']]);
    }

    private function createLearningMisconceptions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_unit_id' => $this->bigInt(true),
            'concept_id' => $this->bigInt(true),
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT'],
            'detection_hint' => ['type' => 'TEXT', 'null' => true],
            'teacher_response_suggestion' => ['type' => 'TEXT', 'null' => true],
            'severity' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'INFO'],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'ACTIVE'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('concept_id', 'learning_concepts', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_misconceptions', [['learning_unit_id', 'title'], ['concept_id', 'title']]);
    }

    private function createLearningActivations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_unit_id' => $this->bigInt(),
            'activation_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'instructions' => ['type' => 'TEXT'],
            'estimated_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resource_id' => $this->bigInt(true),
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_activations', [['learning_unit_id', 'activation_type']]);
    }

    private function createLearningResources(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_pack_id' => $this->bigInt(),
            'resource_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'file_reference' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'device_count' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'internet_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'copyright_notes' => ['type' => 'TEXT', 'null' => true],
            'license_notes' => ['type' => 'TEXT', 'null' => true],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'ACTIVE'],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_resources', [['learning_pack_id', 'title']]);
    }

    private function createLearningActivities(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_pack_id' => $this->bigInt(),
            'learning_unit_id' => $this->bigInt(),
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'activity_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'PRACTICE'],
            'delivery_mode' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'OTHER'],
            'grouping_mode' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'FLEXIBLE'],
            'estimated_minutes' => ['type' => 'INT', 'unsigned' => true],
            'minimum_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'maximum_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'difficulty_level' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'internet_requirement' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'NOT_REQUIRED'],
            'device_requirement' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'room_requirement' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'teacher_guidance' => ['type' => 'TEXT', 'null' => true],
            'student_instructions' => ['type' => 'TEXT', 'null' => true],
            'expected_output' => ['type' => 'TEXT', 'null' => true],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'copyright_notes' => ['type' => 'TEXT', 'null' => true],
            'license_notes' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_activities', [['learning_pack_id', 'code'], ['learning_unit_id', 'code']]);
    }

    private function createLearningActivityResources(): void
    {
        $this->forge->addField([
            'activity_id' => $this->bigInt(),
            'resource_id' => $this->bigInt(),
            'requirement_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'REQUIRED'],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '9,2', 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['activity_id', 'resource_id', 'requirement_type'], true);
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('resource_id', 'learning_resources', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_activity_resources', true);
    }

    private function createLearningActivityAlternatives(): void
    {
        $this->forge->addField([
            'group_uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'activity_id' => $this->bigInt(),
            'priority' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'condition_json' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['group_uuid', 'activity_id'], true);
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_activity_alternatives', true);
    }

    private function createLearningTeacherGuidance(): void
    {
        $this->forge->addField($this->baseFields() + [
            'guidance_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'learning_unit_id' => $this->bigInt(true),
            'activity_id' => $this->bigInt(true),
            'concept_id' => $this->bigInt(true),
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'guidance' => ['type' => 'TEXT'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('concept_id', 'learning_concepts', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_teacher_guidance', [['guidance_type', 'learning_unit_id'], ['guidance_type', 'activity_id'], ['guidance_type', 'concept_id']]);
    }

    private function createLearningExpectedResponses(): void
    {
        $this->forge->addField($this->baseFields() + [
            'activity_id' => $this->bigInt(),
            'response_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'description' => ['type' => 'TEXT'],
            'teacher_response' => ['type' => 'TEXT', 'null' => true],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('learning_expected_responses', [['activity_id', 'response_type', 'sequence_order']]);
    }

    private function createLearningActivityExperiences(): void
    {
        $this->forge->addField([
            'activity_id' => $this->bigInt(),
            'experience_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['activity_id', 'experience_type'], true);
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_activity_experiences', true);
    }

    private function createPedagogicalPractices(): void
    {
        $this->forge->addField($this->baseFields() + [
            'code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->auditFields());
        $this->keys('pedagogical_practices', [['code']]);

        $now = date('Y-m-d H:i:s');
        foreach ([
            'DIRECT_INSTRUCTION' => 'Direct Instruction',
            'DISCOVERY_LEARNING' => 'Discovery Learning',
            'INQUIRY' => 'Inquiry',
            'PROBLEM_BASED' => 'Problem Based',
            'PROJECT_BASED' => 'Project Based',
            'CONTEXTUAL' => 'Contextual',
            'COLLABORATIVE' => 'Collaborative',
            'GAME_BASED' => 'Game Based',
            'EXPERIENTIAL' => 'Experiential',
            'OTHER' => 'Other',
        ] as $code => $name) {
            if ($this->db->table('pedagogical_practices')->where('code', $code)->countAllResults() === 0) {
                $this->db->table('pedagogical_practices')->insert([
                    'uuid' => $this->uuid(),
                    'code' => $code,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function createPracticeMaps(): void
    {
        foreach (['learning_unit_practices' => 'learning_unit_id', 'learning_activity_practices' => 'activity_id'] as $table => $ownerField) {
            $this->forge->addField([
                $ownerField => $this->bigInt(),
                'practice_id' => $this->bigInt(),
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey([$ownerField, 'practice_id'], true);
            $this->forge->addForeignKey($ownerField, $ownerField === 'learning_unit_id' ? 'learning_units' : 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->addForeignKey('practice_id', 'pedagogical_practices', 'id', 'RESTRICT', 'RESTRICT');
            $this->forge->createTable($table, true);
        }
    }

    private function createGraduateProfileAlignments(): void
    {
        $this->forge->addField($this->baseFields() + [
            'owner_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'owner_id' => $this->bigInt(),
            'dimension_id' => $this->bigInt(),
            'notes' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('dimension_id', 'graduate_profile_dimensions', 'id', 'RESTRICT', 'RESTRICT');
        $this->keys('learning_graduate_profile_alignments', [['owner_type', 'owner_id', 'dimension_id']]);
    }

    private function createInterdisciplinaryLinks(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_pack_id' => $this->bigInt(),
            'source_subject_id' => $this->bigInt(),
            'related_subject_id' => $this->bigInt(),
            'related_objective_id' => $this->bigInt(true),
            'description' => ['type' => 'TEXT'],
            'link_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'RELATED'],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('related_subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('related_objective_id', 'learning_objectives_tp', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_interdisciplinary_links', [['learning_pack_id', 'source_subject_id', 'related_subject_id']]);
    }

    private function createAssessmentReferences(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_unit_id' => $this->bigInt(true),
            'activity_id' => $this->bigInt(true),
            'assessment_purpose' => ['type' => 'VARCHAR', 'constraint' => 30],
            'recommended_method' => ['type' => 'VARCHAR', 'constraint' => 120],
            'criteria_reference' => ['type' => 'TEXT', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'source_id' => $this->bigInt(true),
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('source_id', 'curriculum_sources', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_assessment_references', [['learning_unit_id', 'assessment_purpose'], ['activity_id', 'assessment_purpose']]);
    }

    private function createFollowupGuidance(): void
    {
        $this->forge->addField($this->baseFields() + [
            'guidance_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'learning_unit_id' => $this->bigInt(true),
            'learning_objective_id' => $this->bigInt(true),
            'activity_id' => $this->bigInt(true),
            'trigger_description' => ['type' => 'TEXT'],
            'guidance' => ['type' => 'TEXT'],
            'recommended_activity_id' => $this->bigInt(true),
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('recommended_activity_id', 'learning_activities', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_followup_guidance', [['guidance_type', 'learning_unit_id'], ['guidance_type', 'activity_id']]);
    }

    private function createReflectionPrompts(): void
    {
        $this->forge->addField($this->baseFields() + [
            'audience' => ['type' => 'VARCHAR', 'constraint' => 30],
            'learning_unit_id' => $this->bigInt(true),
            'activity_id' => $this->bigInt(true),
            'prompt' => ['type' => 'TEXT'],
            'prompt_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'OPEN'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addForeignKey('learning_unit_id', 'learning_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('activity_id', 'learning_activities', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('learning_reflection_prompts', [['audience', 'learning_unit_id', 'sequence_order'], ['audience', 'activity_id', 'sequence_order']]);
    }

    private function createImportStaging(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id' => $this->bigInt(),
            'learning_pack_id' => $this->bigInt(true),
            'source_filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'UPLOADED'],
            'total_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'valid_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'applied_rows' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'applied_by' => $this->int(true),
            'applied_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_pack_id', 'subject_learning_packs', 'id', 'SET NULL', 'RESTRICT');
        $this->keys('learning_pack_import_batches', [['source_hash']]);

        $this->forge->addField($this->baseFields() + [
            'batch_id' => $this->bigInt(),
            'row_number' => ['type' => 'INT', 'unsigned' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 60],
            'payload_json' => ['type' => 'MEDIUMTEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'STAGED'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'applied_entity_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'applied_entity_id' => $this->bigInt(true),
        ] + $this->auditFields());
        $this->forge->addForeignKey('batch_id', 'learning_pack_import_batches', 'id', 'CASCADE', 'RESTRICT');
        $this->keys('learning_pack_import_rows', [['batch_id', 'row_number']]);
    }

    private function seedPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->permissions as $code => $definition) {
            if ($this->db->table('permissions')->where('code', $code)->countAllResults() === 0) {
                $this->db->table('permissions')->insert($definition + [
                    'code' => $code,
                    'description' => $definition['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $roleMap = [
            'super_admin' => array_keys($this->permissions),
            'kepala_sekolah' => ['learning_packs.view', 'learning_packs.review', 'learning_packs.approve', 'learning_packs.lock'],
            'wakasek_kurikulum' => array_keys($this->permissions),
            'admin_smp' => ['learning_packs.view', 'learning_packs.manage', 'learning_packs.clone', 'learning_units.manage', 'learning_activities.manage', 'learning_resources.manage', 'learning_guidance.manage'],
            'admin_sma' => ['learning_packs.view', 'learning_packs.manage', 'learning_packs.clone', 'learning_units.manage', 'learning_activities.manage', 'learning_resources.manage', 'learning_guidance.manage'],
            'guru' => ['learning_packs.view', 'learning_packs.manage', 'learning_packs.clone', 'learning_units.manage', 'learning_activities.manage', 'learning_resources.manage', 'learning_guidance.manage'],
            'viewer_yayasan' => ['learning_packs.view'],
        ];

        foreach ($roleMap as $roleCode => $codes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }

            foreach ($codes as $code) {
                $permission = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
                if (! $permission) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permission['id'])
                    ->countAllResults() > 0;
                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => (int) $role['id'],
                        'permission_id' => (int) $permission['id'],
                        'created_at' => $now,
                    ]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $rows = $this->db->table('permissions')->select('id')->whereIn('code', array_keys($this->permissions))->get()->getResultArray();
        $ids = array_map('intval', array_column($rows, 'id'));
        if ($ids === []) {
            return;
        }
        if ($this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
        }
        $this->db->table('permissions')->whereIn('id', $ids)->delete();
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
            'created_by' => $this->int(true),
            'updated_by' => $this->int(true),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function bigInt(bool $nullable = false, ?string $after = null): array
    {
        $field = ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
        if ($after !== null) {
            $field['after'] = $after;
        }
        return $field;
    }

    private function int(bool $nullable = false, ?string $after = null): array
    {
        $field = ['type' => 'INT', 'unsigned' => true, 'null' => $nullable];
        if ($after !== null) {
            $field['after'] = $after;
        }
        return $field;
    }

    private function keys(string $table, array $uniqueKeys = []): void
    {
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        foreach ($uniqueKeys as $key) {
            $this->forge->addUniqueKey($key);
        }
        $this->forge->createTable($table, true);
    }

    private function dropColumnsIfPresent(string $table, array $columns): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                $this->forge->dropColumn($table, $column);
                $this->db->resetDataCache();
            }
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        )->getRowArray() !== null;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
