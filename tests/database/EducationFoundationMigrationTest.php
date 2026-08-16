<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\IsolatedDatabaseTestTrait;
final class EducationFoundationMigrationTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class;
public function testAllSemanticTablesExist():void { foreach(['regulations','regulation_versions','curriculum_sources','graduate_profile_dimensions','learning_outcomes_cp','curriculum_elements','learning_objectives_tp','objective_criteria','learning_sequences_atp','learning_sequence_items','subject_learning_packs','subject_learning_pack_objectives','subject_learning_pack_sequences','education_foundation_import_batches','education_foundation_import_rows'] as $table) $this->assertTrue($this->db->tableExists($table),$table); }
public function testLineageAndRevisionColumnsExist():void { foreach(['unit_id','parent_objective_id','source_level','revision_number'] as $field) $this->assertTrue($this->db->fieldExists($field,'learning_objectives_tp')); foreach(['workflow_status','revision_number','parent_sequence_id'] as $field) $this->assertTrue($this->db->fieldExists($field,'learning_sequences_atp')); }
}

