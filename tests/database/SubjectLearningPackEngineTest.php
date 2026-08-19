<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ConcurrencyException;
use App\Services\LearningPackService;
use App\Services\LearningPackImportService;
use App\Services\SubjectLearningPackEngineService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

final class SubjectLearningPackEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $role = $this->db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if ($role && $this->db->table('user_roles')->where(['user_id' => 1, 'role_id' => $role['id']])->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id' => 1, 'role_id' => $role['id'], 'unit_id' => $this->unitId, 'created_at' => date('Y-m-d H:i:s')]);
        }
        session()->set([
            'logged_in' => true,
            'role_code' => 'super_admin',
            'all_role_codes' => ['super_admin'],
            'username' => 'ialos_test',
            'full_name' => 'IALOS Test',
            'must_change_password' => 0,
            'user_id' => 1,
            'active_unit_id' => $this->unitId,
            'active_period_id' => $this->periodId,
        ]);
    }

    public function testPhase3SchemaIsGenericAndAvailable(): void
    {
        foreach ([
            'learning_units',
            'learning_unit_objectives',
            'learning_concepts',
            'learning_concept_relations',
            'learning_material_topics',
            'learning_misconceptions',
            'learning_activities',
            'learning_resources',
            'learning_teacher_guidance',
            'learning_expected_responses',
            'learning_assessment_references',
            'learning_followup_guidance',
            'learning_reflection_prompts',
            'pedagogical_practices',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), $table);
        }

        $this->assertTrue($this->db->fieldExists('source_type', 'subject_learning_packs'));
        $this->assertTrue($this->db->fieldExists('parent_pack_id', 'subject_learning_packs'));
    }

    public function testInformatikaReferencePackCanRepresentStructuredLearningDesign(): void
    {
        $objective = $this->createTestObjective();
        $pack = $this->createPack('INF-X-P3', 'Informatika X Reference Pack');
        LearningPackService::attachObjective($pack['uuid'], $objective['uuid']);

        $unit = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'BK-01',
            'title' => 'Berpikir Komputasional',
            'unit_type' => 'CHAPTER',
            'sequence_order' => 1,
            'estimated_hours' => 8,
            'source_locator' => 'Panduan Guru Informatika X, Bab BK',
        ]);
        SubjectLearningPackEngineService::mapUnitObjective($unit['uuid'], $objective['uuid'], ['role' => 'PRIMARY']);
        $concept = SubjectLearningPackEngineService::createConcept($pack['uuid'], [
            'learning_unit_uuid' => $unit['uuid'],
            'code' => 'DEKOMPOSISI',
            'title' => 'Dekomposisi',
            'concept_type' => 'CORE',
            'source_locator' => 'BK concept reference',
        ]);
        SubjectLearningPackEngineService::addMaterialTopic($unit['uuid'], [
            'code' => 'BK-M1',
            'title' => 'Strategi dekomposisi masalah',
            'material_level' => 'ESSENTIAL',
        ]);
        SubjectLearningPackEngineService::addMisconception($unit['uuid'], [
            'concept_uuid' => $concept['uuid'],
            'title' => 'Dekomposisi dianggap menebak langkah',
            'description' => 'Peserta didik dapat mengira dekomposisi adalah memilih langkah acak.',
            'severity' => 'ATTENTION',
        ]);
        SubjectLearningPackEngineService::addActivation($unit['uuid'], [
            'activation_type' => 'APERCEPTION',
            'title' => 'Masalah sehari-hari',
            'instructions' => 'Ajak peserta didik memecah proses sederhana menjadi langkah kecil.',
            'estimated_minutes' => 10,
        ]);
        $resource = SubjectLearningPackEngineService::createResource($pack['uuid'], [
            'resource_type' => 'DEVICE',
            'title' => 'Komputer/laptop kelompok',
            'device_count' => 8,
            'internet_required' => false,
        ]);
        $plugged = SubjectLearningPackEngineService::createActivity($unit['uuid'], [
            'code' => 'BK-A1',
            'title' => 'Analisis Masalah dengan Spreadsheet',
            'delivery_mode' => 'PLUGGED',
            'grouping_mode' => 'SMALL_GROUP',
            'estimated_minutes' => 45,
            'internet_requirement' => 'OPTIONAL',
            'device_requirement' => '1 perangkat per kelompok',
        ]);
        $unplugged = SubjectLearningPackEngineService::createActivity($unit['uuid'], [
            'code' => 'BK-A1U',
            'title' => 'Analisis Manual di Kertas',
            'delivery_mode' => 'UNPLUGGED',
            'grouping_mode' => 'SMALL_GROUP',
            'estimated_minutes' => 45,
        ]);
        SubjectLearningPackEngineService::attachActivityResource($plugged['uuid'], $resource['uuid']);
        SubjectLearningPackEngineService::addAlternative('10000000-0000-4000-8000-0000000000aa', $plugged['uuid'], ['priority' => 1]);
        SubjectLearningPackEngineService::addAlternative('10000000-0000-4000-8000-0000000000aa', $unplugged['uuid'], ['priority' => 2, 'condition_json' => '{"internet":"unavailable"}']);
        SubjectLearningPackEngineService::addTeacherGuidance([
            'activity_uuid' => $plugged['uuid'],
            'guidance_type' => 'SCAFFOLDING',
            'title' => 'Pertanyaan bantu',
            'guidance' => 'Tanyakan bagian masalah mana yang dapat dipisahkan terlebih dahulu.',
        ]);
        SubjectLearningPackEngineService::addExpectedResponse($plugged['uuid'], [
            'response_type' => 'COMMON_DIFFICULTY',
            'description' => 'Peserta didik mencampur langkah utama dan rincian kecil.',
            'teacher_response' => 'Minta mereka menandai langkah utama dahulu.',
        ]);
        SubjectLearningPackEngineService::addExperience($plugged['uuid'], 'UNDERSTAND');
        SubjectLearningPackEngineService::addExperience($plugged['uuid'], 'APPLY');
        SubjectLearningPackEngineService::addExperience($unplugged['uuid'], 'REFLECT');
        SubjectLearningPackEngineService::mapPracticeToUnit($unit['uuid'], 'PROBLEM_BASED');
        SubjectLearningPackEngineService::mapPracticeToActivity($plugged['uuid'], 'COLLABORATIVE');
        SubjectLearningPackEngineService::alignGraduateProfile('UNIT', $unit['uuid'], 'CRITICAL_REASONING');
        SubjectLearningPackEngineService::addAssessmentReference([
            'learning_unit_uuid' => $unit['uuid'],
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi proses kelompok',
        ]);
        SubjectLearningPackEngineService::addFollowupGuidance([
            'learning_unit_uuid' => $unit['uuid'],
            'guidance_type' => 'REMEDIAL',
            'trigger_description' => 'Belum dapat memisahkan masalah menjadi bagian kecil.',
            'guidance' => 'Gunakan contoh proses harian yang lebih dekat dengan murid.',
        ]);
        SubjectLearningPackEngineService::addReflectionPrompt([
            'activity_uuid' => $plugged['uuid'],
            'audience' => 'TEACHER',
            'prompt' => 'Bagian mana yang paling sulit difasilitasi?',
        ]);

        $coverage = SubjectLearningPackEngineService::coverage($pack['uuid']);
        $this->assertSame(1, $coverage['tp_total']);
        $this->assertSame(1, $coverage['tp_with_unit']);
        $this->assertSame(1, $coverage['tp_with_activity']);
        $this->assertSame(1, $coverage['tp_with_assessment_reference']);
        $this->assertSame(2, $coverage['activity_count']);
        $this->assertSame(1, $coverage['deep_learning_experiences']['UNDERSTAND']);
        $this->assertSame(1, $coverage['deep_learning_experiences']['APPLY']);
        $this->assertSame(1, $coverage['deep_learning_experiences']['REFLECT']);
        $this->assertGreaterThan(70, $coverage['readiness_score']);

        $planningTree = SubjectLearningPackEngineService::getPackStructureForPlanning($pack['uuid']);
        $this->assertSame($pack['uuid'], $planningTree['uuid']);
        $this->assertCount(1, $planningTree['units']);
        $this->assertCount(2, $planningTree['units'][0]['activities']);
        $this->assertCount(1, $planningTree['units'][0]['activities'][0]['resources']);
    }

    public function testChildEntitiesMutationsAndUpdateWithOcc(): void
    {
        $pack = $this->createPack('INF-X-MUT', 'Mutation Test Pack');
        $unit = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'MUT-U1',
            'title' => 'Original Unit Title',
            'sequence_order' => 1,
        ]);

        $updatedUnit = SubjectLearningPackEngineService::updateUnit($unit['uuid'], [
            'title' => 'Updated Unit Title',
            'revision_number' => 1,
        ]);
        $this->assertSame('Updated Unit Title', $updatedUnit['title']);
        $this->assertSame(2, (int) $updatedUnit['revision_number']);

        // OCC test on child unit
        $this->expectException(ConcurrencyException::class);
        SubjectLearningPackEngineService::updateUnit($unit['uuid'], [
            'title' => 'Stale Unit Edit',
            'revision_number' => 1,
        ]);
    }

    public function testChildEntitiesDeletion(): void
    {
        $pack = $this->createPack('INF-X-DEL', 'Deletion Test Pack');
        $unit = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'DEL-U1',
            'title' => 'Unit to Delete',
            'sequence_order' => 1,
        ]);
        $concept = SubjectLearningPackEngineService::createConcept($pack['uuid'], [
            'learning_unit_uuid' => $unit['uuid'],
            'code' => 'DEL-C1',
            'title' => 'Concept to Delete',
        ]);
        $resource = SubjectLearningPackEngineService::createResource($pack['uuid'], [
            'title' => 'Resource to Delete',
        ]);
        $activity = SubjectLearningPackEngineService::createActivity($unit['uuid'], [
            'code' => 'DEL-A1',
            'title' => 'Activity to Delete',
            'estimated_minutes' => 30,
        ]);

        SubjectLearningPackEngineService::deleteActivity($activity['uuid']);
        $this->assertSame(0, $this->db->table('learning_activities')->where('uuid', $activity['uuid'])->countAllResults());

        SubjectLearningPackEngineService::deleteResource($resource['uuid']);
        $this->assertSame(0, $this->db->table('learning_resources')->where('uuid', $resource['uuid'])->countAllResults());

        SubjectLearningPackEngineService::deleteConcept($concept['uuid']);
        $this->assertSame(0, $this->db->table('learning_concepts')->where('uuid', $concept['uuid'])->countAllResults());

        SubjectLearningPackEngineService::deleteUnit($unit['uuid']);
        $this->assertSame(0, $this->db->table('learning_units')->where('uuid', $unit['uuid'])->countAllResults());
    }

    public function testCrossPackPrerequisiteValidation(): void
    {
        $pack1 = $this->createPack('INF-X-P1', 'Pack 1');
        $pack2 = $this->createPack('INF-X-P2', 'Pack 2');

        $unit1 = SubjectLearningPackEngineService::createUnit($pack1['uuid'], ['code' => 'U1', 'title' => 'Unit 1', 'sequence_order' => 1]);
        $unit2 = SubjectLearningPackEngineService::createUnit($pack2['uuid'], ['code' => 'U2', 'title' => 'Unit 2', 'sequence_order' => 1]);

        // Unit 2 in Pack 2 depends on Unit 1 in Pack 1
        $prereq = SubjectLearningPackEngineService::addUnitPrerequisite($unit2['uuid'], [
            'prerequisite_unit_uuid' => $unit1['uuid'],
        ]);
        $this->assertNotEmpty($prereq['uuid']);

        // Reverse dependency should be rejected due to cycle
        $this->expectException(InvalidArgumentException::class);
        SubjectLearningPackEngineService::addUnitPrerequisite($unit1['uuid'], [
            'prerequisite_unit_uuid' => $unit2['uuid'],
        ]);
    }

    public function testComprehensiveImportBatchWithAllEntityTypes(): void
    {
        $objective = $this->createTestObjective();
        $pack = $this->createPack('INF-X-FULL-IMP', 'Full Import Pack');
        LearningPackService::attachObjective($pack['uuid'], $objective['uuid']);

        $batch = LearningPackImportService::stage($this->unitId, $pack['uuid'], 'full-learning-pack.json', [
            [
                'entity_type' => 'LEARNING_UNIT',
                'payload' => [
                    'code' => 'FIMP-U1',
                    'title' => 'Full Import Unit',
                    'sequence_order' => 1,
                ],
            ],
            [
                'entity_type' => 'CONCEPT',
                'payload' => [
                    'code' => 'FIMP-C1',
                    'title' => 'Full Import Concept',
                ],
            ],
            [
                'entity_type' => 'RESOURCE',
                'payload' => [
                    'resource_type' => 'DOCUMENT',
                    'title' => 'Full Import Resource',
                ],
            ],
            [
                'entity_type' => 'GUIDANCE',
                'payload' => [
                    'title' => 'Teacher Planning Note',
                    'guidance' => 'Ensure all students have access to offline materials.',
                ],
            ],
            [
                'entity_type' => 'REFLECTION_PROMPT',
                'payload' => [
                    'prompt' => 'What was the most engaging part of today lesson?',
                    'audience' => 'STUDENT',
                ],
            ],
        ]);

        $this->assertSame('VALIDATED', $batch['status']);
        $this->assertSame(5, (int) $batch['valid_rows']);

        $applied = LearningPackImportService::apply($batch['uuid']);
        $this->assertSame('APPLIED', $applied['status']);
        $this->assertSame(5, (int) $applied['applied_rows']);
        $this->assertSame(1, $this->db->table('learning_reflection_prompts')->countAllResults());
    }

    public function testPrerequisiteCycleIsRejected(): void
    {
        $pack = $this->createPack('INF-X-CYCLE', 'Cycle Pack');
        $first = SubjectLearningPackEngineService::createConcept($pack['uuid'], ['code' => 'A', 'title' => 'A']);
        $second = SubjectLearningPackEngineService::createConcept($pack['uuid'], ['code' => 'B', 'title' => 'B']);

        SubjectLearningPackEngineService::relateConcepts($first['uuid'], $second['uuid'], 'PREREQUISITE');

        $this->expectException(InvalidArgumentException::class);
        SubjectLearningPackEngineService::relateConcepts($second['uuid'], $first['uuid'], 'PREREQUISITE');
    }

    public function testWorkflowOccAndLockedMutationAreEnforced(): void
    {
        $pack = $this->createPack('INF-X-WF', 'Workflow Pack');

        $validated = SubjectLearningPackEngineService::transition($pack['uuid'], 'VALIDATED', 1);
        $this->assertSame('VALIDATED', $validated['status']);

        $this->expectException(ConcurrencyException::class);
        SubjectLearningPackEngineService::transition($pack['uuid'], 'REVIEWED', 1);
    }

    public function testLockedPackRejectsChildMutation(): void
    {
        $pack = $this->createPack('INF-X-LOCK', 'Locked Pack');
        $pack = SubjectLearningPackEngineService::transition($pack['uuid'], 'VALIDATED', 1);
        $pack = SubjectLearningPackEngineService::transition($pack['uuid'], 'REVIEWED', 2);
        $pack = SubjectLearningPackEngineService::transition($pack['uuid'], 'APPROVED', 3);
        SubjectLearningPackEngineService::transition($pack['uuid'], 'LOCKED', 4);

        $this->expectException(RuntimeException::class);
        SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'LOCKED',
            'title' => 'Should fail',
            'sequence_order' => 1,
        ]);
    }

    public function testNonInformatikaSubjectDoesNotRequirePluggedMetadata(): void
    {
        $mathSubjectId = $this->ensureMathSubject();
        $pack = SubjectLearningPackEngineService::createPack([
            'curriculum_version_id' => $this->versionId,
            'unit_id' => $this->unitId,
            'subject_id' => $mathSubjectId,
            'grade_level_id' => $this->gradeId,
            'phase' => 'E',
            'code' => 'MATH-X-P3',
            'name' => 'Mathematics Generic Pack',
            'source_type' => 'CUSTOM',
        ]);
        $unit = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'NUM-01',
            'title' => 'Numerasi Kontekstual',
            'unit_type' => 'UNIT',
            'sequence_order' => 1,
        ]);
        $activity = SubjectLearningPackEngineService::createActivity($unit['uuid'], [
            'code' => 'NUM-A1',
            'title' => 'Literasi finansial sederhana',
            'delivery_mode' => 'DISCUSSION',
            'grouping_mode' => 'PAIR',
            'estimated_minutes' => 40,
        ]);

        $this->assertSame('DISCUSSION', $activity['delivery_mode']);
        $this->assertNull($activity['device_requirement']);
    }

    public function testLearningPackPagesRenderAsSeparateDestinations(): void
    {
        $pack = $this->createPack('INF-X-PAGES', 'Pages Pack');
        SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'PAGES-01',
            'title' => 'Navigation Unit',
            'sequence_order' => 1,
        ]);

        foreach (['', '/overview', '/units', '/concepts', '/activities', '/resources', '/assessment', '/followup', '/coverage', '/lineage', '/history'] as $path) {
            $this->withSession(session()->get())->get('curriculum/learning-packs/'.$pack['uuid'].$path)->assertOK();
        }
    }

    public function testStructureEndpointReturnsJson(): void
    {
        $pack = $this->createPack('INF-X-STRUCT', 'Structure Pack');
        SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'STRUCT-01',
            'title' => 'Planning Structure Unit',
            'sequence_order' => 1,
        ]);

        $structure = SubjectLearningPackEngineService::getPackStructureForPlanning($pack['uuid']);
        $this->assertNotEmpty($structure['units']);
        $this->assertSame('STRUCT-01', $structure['units'][0]['code']);

        $res = $this->withSession(session()->get())->get('curriculum/learning-packs/'.$pack['uuid'].'/structure');
        $raw = $res->response()->getBody();
        $json = json_decode($raw, true);
        $this->assertTrue(isset($json['ok']) && $json['ok'] === true, 'Raw body was [' . $raw . '], Status was: ' . $res->response()->getStatusCode());
        $this->assertSame('STRUCT-01', $json['data']['units'][0]['code']);
    }

    public function testCrossUnitLearningPackUuidIsDenied(): void
    {
        $this->db->table('users')->ignore(true)->insert([
            'id' => 55,
            'uuid' => '10000000-0000-4000-8000-000000000055',
            'username' => 'phase3_unit_viewer',
            'email' => 'phase3-viewer@example.test',
            'full_name' => 'Phase 3 Unit Viewer',
            'password_hash' => password_hash('SomePass12345!', PASSWORD_BCRYPT),
            'is_active' => 1,
            'must_change_password' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $viewerRole = $this->db->table('roles')->where('code', 'viewer_yayasan')->get()->getRowArray();
        if ($viewerRole) {
            $this->db->table('user_roles')->ignore(true)->insert(['user_id' => 55, 'role_id' => $viewerRole['id'], 'created_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->table('user_unit_access')->ignore(true)->insert([
            'user_id' => 55,
            'unit_id' => $this->unitId,
            'access_level' => 'VIEW',
            'is_default' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('subject_learning_packs')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000088',
            'curriculum_version_id' => $this->versionId,
            'unit_id' => $this->otherUnitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'phase' => 'E',
            'code' => 'OTHER-UNIT',
            'name' => 'Other Unit Pack',
            'source_type' => 'CUSTOM',
            'status' => 'DRAFT',
            'revision_number' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(AuthorizationException::class);
        $this->withSession([
            'logged_in' => true,
            'user_id' => 55,
            'auth_timestamp' => time(),
            'username' => 'phase3_unit_viewer',
            'role_code' => 'viewer_yayasan',
            'active_role' => 'viewer_yayasan',
            'active_unit_id' => $this->unitId,
            'unit_access' => [$this->unitId],
        ])->get('curriculum/learning-packs/10000000-0000-4000-8000-000000000088');
    }

    private function createPack(string $code, string $name): array
    {
        return SubjectLearningPackEngineService::createPack([
            'curriculum_version_id' => $this->versionId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'phase' => 'E',
            'code' => $code,
            'name' => $name,
            'source_type' => 'CUSTOM',
        ]);
    }

    private function ensureMathSubject(): int
    {
        $subject = $this->db->table('subjects')->where('code', 'MATH-X-TEST')->get()->getRowArray();
        if (! $subject) {
            $this->db->table('subjects')->insert([
                'uuid' => '10000000-0000-4000-8000-000000000099',
                'code' => 'MATH-X-TEST',
                'name' => 'Mathematics X (Fixture)',
                'short_name' => 'MATH',
                'category' => 'WAJIB',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $subject = ['id' => $this->db->insertID()];
        }
        if ($this->db->table('subject_unit_availability')->where(['subject_id' => $subject['id'], 'unit_id' => $this->unitId])->countAllResults() === 0) {
            $this->db->table('subject_unit_availability')->insert(['subject_id' => $subject['id'], 'unit_id' => $this->unitId, 'is_available' => 1]);
        }
        return (int) $subject['id'];
    }
}
