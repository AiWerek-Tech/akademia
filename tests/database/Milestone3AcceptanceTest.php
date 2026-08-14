<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumEffectiveHoursService;
use App\Services\BlockPatternService;
use App\Services\CurriculumResolutionService;
use App\Services\CurriculumValidationService;
use App\Services\CurriculumWorkflowService;
use App\Services\CurriculumReconciliationService;
use App\Services\CurriculumImportService;
use App\Services\CurriculumExportService;
use App\Models\CurriculumVersionModel;
use App\Models\CurriculumStructureModel;
use App\Models\CurriculumValidationResultModel;
use App\Models\CurriculumImportBatchModel;
use App\Models\CurriculumImportRowModel;
use App\Models\SubjectModel;
use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use App\Models\RoomTypeModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use Config\Database;

/**
 * Milestone 3 Curriculum Acceptance Tests
 *
 * @internal
 */
final class Milestone3AcceptanceTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private ?int $smpId = null;
    private ?int $smaId = null;
    private ?int $periodId = null;
    private ?int $grade7Id = null;
    private ?int $grade10Id = null;
    private ?int $subjectMatId = null;
    private ?int $subjectIpaId = null;
    private ?int $class7AId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $seeder2 = new Milestone2MasterSeeder(new Database());
        $seeder2->run();

        $seeder3 = new Milestone3CurriculumSeeder(new Database());
        $seeder3->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;
        $this->smaId = $sma ? (int)$sma['id'] : 2;

        // Ensure academic year exists
        $year = $db->table('academic_years')->where('name', '2026/2027')->get()->getRowArray();
        if (!$year) {
            $db->table('academic_years')->insert([
                'uuid'       => '00000000-0000-0000-0000-000000000000',
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'status'     => 'APPROVED',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $yearId = $db->insertID();
        } else {
            $yearId = (int)$year['id'];
        }

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
            $db->table('academic_periods')->insert([
                'uuid'             => '00000000-0000-0000-0000-000000000001',
                'academic_year_id' => $yearId,
                'semester_number'  => 1,
                'name'             => 'Ganjil 2026/2027',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->periodId = $db->insertID();
        } else {
            $this->periodId = (int)$period['id'];
        }

        // Grades
        $g7 = $db->table('grade_levels')->where('unit_id', $this->smpId)->where('code', 'VII')->get()->getRowArray();
        $this->grade7Id = $g7 ? (int)$g7['id'] : null;

        $g10 = $db->table('grade_levels')->where('unit_id', $this->smaId)->where('code', 'X')->get()->getRowArray();
        $this->grade10Id = $g10 ? (int)$g10['id'] : null;

        // Subjects
        $subMat = $db->table('subjects')->where('code', 'MAT-SMP')->get()->getRowArray();
        if (!$subMat) {
            $db->table('subjects')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000010',
                'code' => 'MAT-SMP',
                'name' => 'Matematika SMP',
                'short_name' => 'MAT',
                'category' => 'WAJIB',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->subjectMatId = (int) $db->insertID();
        } else {
            $this->subjectMatId = (int)$subMat['id'];
        }
        $db->table('subject_unit_availability')
            ->where('subject_id', $this->subjectMatId)
            ->where('unit_id', $this->smpId)
            ->delete();
        $db->table('subject_unit_availability')->insert([
            'subject_id' => $this->subjectMatId,
            'unit_id' => $this->smpId
        ]);

        $subIpa = $db->table('subjects')->where('code', 'IPA-SMP')->get()->getRowArray();
        if (!$subIpa) {
            $db->table('subjects')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000011',
                'code' => 'IPA-SMP',
                'name' => 'IPA Terpadu',
                'short_name' => 'IPA',
                'category' => 'WAJIB',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->subjectIpaId = (int) $db->insertID();
        } else {
            $this->subjectIpaId = (int)$subIpa['id'];
        }
        $db->table('subject_unit_availability')
            ->where('subject_id', $this->subjectIpaId)
            ->where('unit_id', $this->smpId)
            ->delete();
        $db->table('subject_unit_availability')->insert([
            'subject_id' => $this->subjectIpaId,
            'unit_id' => $this->smpId
        ]);

        // Classroom
        $c7a = $db->table('classrooms')->where('unit_id', $this->smpId)->where('code', '7A')->get()->getRowArray();
        if (!$c7a) {
            $db->table('classrooms')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000020',
                'academic_period_id' => $this->periodId,
                'unit_id' => $this->smpId,
                'grade_level_id' => $this->grade7Id,
                'code' => '7A',
                'name' => 'Kelas VII A',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->class7AId = (int) $db->insertID();
        } else {
            $this->class7AId = (int)$c7a['id'];
        }

        // Ensure admin user exists
        $user = $db->table('users')->where('id', 1)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => '00000000-0000-0000-0000-000000000099',
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('TestPass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // Ensure super_admin role assignment
        $superAdminRole = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if ($superAdminRole) {
            $roleAssignment = $db->table('user_roles')->where('user_id', 1)->where('role_id', $superAdminRole['id'])->get()->getRowArray();
            if (!$roleAssignment) {
                $db->table('user_roles')->insert([
                    'user_id' => 1,
                    'role_id' => $superAdminRole['id'],
                ]);
            }
        }

        // Ensure unit access exists
        $access = $db->table('user_unit_access')->where('user_id', 1)->where('unit_id', $this->smpId)->get()->getRowArray();
        if (!$access) {
            $db->table('user_unit_access')->insert([
                'user_id' => 1,
                'unit_id' => $this->smpId,
                'is_default' => 1
            ]);
        }
        $accessSma = $db->table('user_unit_access')->where('user_id', 1)->where('unit_id', $this->smaId)->get()->getRowArray();
        if (!$accessSma) {
            $db->table('user_unit_access')->insert([
                'user_id' => 1,
                'unit_id' => $this->smaId,
                'is_default' => 0
            ]);
        }

        // Session admin
        session()->set([
            'user_id' => 1,
            'logged_in' => true,
            'active_role' => 'super_admin',
            'active_unit_id' => $this->smpId,
            'permissions' => ['curriculum.view', 'curriculum.manage', 'curriculum.validate', 'curriculum.review', 'curriculum.approve', 'curriculum.lock', 'curriculum.import', 'curriculum.export', 'curriculum.revise'],
        ]);
    }

    // ==================================================
    // C. VERSION TESTS
    // ==================================================

    public function testC01_CreateVersionSuccess(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-TEST-01',
            'name'               => 'Kurikulum Test 1',
            'description'        => 'Test version',
        ]);

        $this->assertNotNull($version);
        $this->assertEquals('KUR-TEST-01', $version['code']);
        $this->assertEquals('DRAFT', $version['workflow_status']);
        $this->assertEquals(1, $version['revision_number']);
        $this->assertEquals(0, $version['is_active']);
    }

    public function testC02_CreateVersionDuplicateCodeRejected(): void
    {
        CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-DUP-01',
            'name'               => 'Kurikulum Original',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah ada');

        CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-DUP-01',
            'name'               => 'Kurikulum Duplicate',
        ]);
    }

    public function testC03_WorkflowTransitionsAndActiveVersion(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-WF-01',
            'name'               => 'Kurikulum Workflow',
        ]);

        // Add a valid structure first
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        // 1. DRAFT -> VALIDATED
        $vValidated = CurriculumWorkflowService::transition($version['uuid'], 'VALIDATED');
        $this->assertEquals('VALIDATED', $vValidated['workflow_status']);

        // 2. VALIDATED -> REVIEWED
        $vReviewed = CurriculumWorkflowService::transition($version['uuid'], 'REVIEWED');
        $this->assertEquals('REVIEWED', $vReviewed['workflow_status']);

        // 3. REVIEWED -> APPROVED
        $vApproved = CurriculumWorkflowService::transition($version['uuid'], 'APPROVED');
        $this->assertEquals('APPROVED', $vApproved['workflow_status']);

        // 4. Set Active
        $vActive = CurriculumWorkflowService::setActiveVersion($version['uuid']);
        $this->assertEquals(1, $vActive['is_active']);

        // 5. APPROVED -> LOCKED
        $vLocked = CurriculumWorkflowService::transition($version['uuid'], 'LOCKED');
        $this->assertEquals('LOCKED', $vLocked['workflow_status']);
    }

    public function testC04_LockedVersionIsImmutable(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-LOCK-01',
            'name'               => 'Kurikulum Locked',
        ]);

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        CurriculumWorkflowService::transition($version['uuid'], 'VALIDATED');
        CurriculumWorkflowService::transition($version['uuid'], 'REVIEWED');
        CurriculumWorkflowService::transition($version['uuid'], 'APPROVED');
        CurriculumWorkflowService::transition($version['uuid'], 'LOCKED');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectIpaId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 3,
        ]);
    }

    public function testC05_CloneVersionCreatesNewVersionWithStructures(): void
    {
        $source = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-SRC-01',
            'name'               => 'Kurikulum Source',
        ]);

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $source['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $cloned = CurriculumVersionService::cloneVersion($source['uuid'], 'KUR-CLONE-01', 'Kurikulum Cloned');

        $this->assertNotNull($cloned);
        $this->assertEquals('KUR-CLONE-01', $cloned['code']);
        $this->assertEquals('DRAFT', $cloned['workflow_status']);

        $structures = CurriculumStructureService::getStructures($cloned['id'])['data'];
        $this->assertCount(1, $structures);
        $this->assertEquals($this->subjectMatId, $structures[0]['subject_id']);
    }

    // ==================================================
    // D. STRUCTURE & EFFECTIVE HOURS TESTS
    // ==================================================

    public function testD01_EffectiveHoursOfficial(): void
    {
        $res = CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4.0,
        ]);

        $this->assertEquals(4.0, $res['effective_weekly_hours']);
        $this->assertEquals('OFFICIAL', $res['effective_source']);
    }

    public function testD02_EffectiveHoursCustomRequiresReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('adjustment_reason');

        CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'    => 'CUSTOM',
            'custom_weekly_hours' => 3.0,
            'adjustment_reason'   => '',
        ]);
    }

    public function testD03_EffectiveHoursCustomSuccess(): void
    {
        $res = CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'    => 'CUSTOM',
            'custom_weekly_hours' => 3.0,
            'adjustment_reason'   => 'Penyesuaian jam lokal',
        ]);

        $this->assertEquals(3.0, $res['effective_weekly_hours']);
        $this->assertEquals('CUSTOM', $res['effective_source']);
        $this->assertEquals('Penyesuaian jam lokal', $res['adjustment_reason']);
    }

    public function testD05_EffectiveHoursSourceEffectiveRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak valid');

        CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'      => 'EFFECTIVE',
            'official_weekly_hours' => 4.0,
        ]);
    }

    public function testD06_EffectiveHoursSourceEmptyRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak valid');

        CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'      => '',
            'official_weekly_hours' => 4.0,
        ]);
    }

    public function testD07_EffectiveHoursSourceInvalidRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak valid');

        CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'      => 'INVALID_SOURCE',
            'official_weekly_hours' => 4.0,
        ]);
    }

    public function testD08_EffectiveHoursClientSuppliedEffectiveHoursIgnored(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-IGNORE-EFF',
            'name'               => 'Kurikulum Ignore Client Effective Hours',
        ]);

        // Create structure passing effective_weekly_hours = 99.0 explicitly
        $structure = CurriculumStructureService::createStructure([
            'curriculum_version_id'  => $version['id'],
            'unit_id'                => $this->smpId,
            'grade_level_id'         => $this->grade7Id,
            'subject_id'             => $this->subjectMatId,
            'effective_source'       => 'OFFICIAL',
            'official_weekly_hours'  => 4.0,
            'effective_weekly_hours' => 99.0, // Should be ignored and calculated as 4.0
        ]);

        $this->assertEquals(4.0, (float)$structure['effective_weekly_hours']);
    }

    public function testD09_EffectiveHoursManualSuccess(): void
    {
        $res = CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'    => 'MANUAL',
            'manual_weekly_hours' => 5.0,
            'adjustment_reason'   => 'Manual adjustment override',
        ]);

        $this->assertEquals(5.0, $res['effective_weekly_hours']);
        $this->assertEquals('MANUAL', $res['effective_source']);
        $this->assertEquals('Manual adjustment override', $res['adjustment_reason']);
    }

    public function testD04_DuplicateGradeDefaultStructureRejected(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-DUP-STRUCT',
            'name'               => 'Kurikulum Duplicate Structure',
        ]);

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah ada');

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);
    }

    // ==================================================
    // E. BLOCK PATTERN TESTS
    // ==================================================

    public function testE01_ValidBlockPattern(): void
    {
        $res = BlockPatternService::validateBlockPattern('{"blocks":[2,2],"preferred":true}', 4.0, 2, 2.0);
        $this->assertTrue($res['valid']);
        $this->assertEmpty($res['errors']);
    }

    public function testE02_InvalidBlockPatternSumMismatch(): void
    {
        $res = BlockPatternService::validateBlockPattern('{"blocks":[2,1],"preferred":true}', 4.0);
        $this->assertFalse($res['valid']);
        $this->assertNotEmpty($res['errors']);
    }

    public function testE03_InvalidBlockPatternMaxDailyViolation(): void
    {
        $res = BlockPatternService::validateBlockPattern('{"blocks":[3,1],"preferred":true}', 4.0, 2, 2.0);
        $this->assertFalse($res['valid']);
        $this->assertNotEmpty($res['errors']);
    }

    // ==================================================
    // F. RESOLUTION & RECONCILIATION TESTS
    // ==================================================

    public function testF01_ClassroomOverrideWinsOverGradeDefault(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-RES-01',
            'name'               => 'Kurikulum Resolution',
        ]);

        // Grade default: 4 JP MAT
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        // Classroom override for 7A: 5 JP MAT
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'classroom_id'          => $this->class7AId,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'CUSTOM',
            'custom_weekly_hours'   => 5,
            'adjustment_reason'     => 'Jam tambahan 7A',
        ]);

        $resolved = CurriculumResolutionService::resolveClassroomStructure($version['id'], $this->class7AId);

        $this->assertCount(1, $resolved['structures']);
        $this->assertEquals(5.0, (float)$resolved['structures'][0]['effective_weekly_hours']);
        $this->assertEquals('CLASSROOM_OVERRIDE', $resolved['structures'][0]['resolution_source']);
        $this->assertTrue($resolved['structures'][0]['is_override']);
    }

    public function testF02_ReconciliationSummary(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-RECON-01',
            'name'               => 'Kurikulum Reconciliation',
        ]);

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $recon = CurriculumReconciliationService::reconcileVersion($version['id']);

        $this->assertNotNull($recon);
        $this->assertGreaterThan(0, count($recon['reconciliation']));
        $this->assertEquals(4.0, (float)$recon['summary']['grand_official']);
        $this->assertEquals(4.0, (float)$recon['summary']['grand_effective']);
    }

    // ==================================================
    // G. EXPORT TEST
    // ==================================================

    public function testG01_ExportExcelGeneratesFile(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-EXP-01',
            'name'               => 'Kurikulum Export',
        ]);

        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $this->grade7Id,
            'subject_id'            => $this->subjectMatId,
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $path = CurriculumExportService::exportExcel($version['uuid']);
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.xlsx', $path);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testH01_DirectActivationNeedsNoWorkflowSteps(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code' => 'KUR-DIRECT-ACTIVE',
            'name' => 'Kurikulum Aktivasi Langsung',
        ]);
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id' => $this->smpId,
            'grade_level_id' => $this->grade7Id,
            'subject_id' => $this->subjectMatId,
            'effective_source' => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $active = CurriculumWorkflowService::setActiveVersion($version['uuid']);

        $this->assertSame('APPROVED', $active['workflow_status']);
        $this->assertSame(1, (int) $active['is_active']);
    }

    public function testH02_ImportDetectsExistingScopeAsUpdate(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code' => 'KUR-IMPORT-UPDATE',
            'name' => 'Kurikulum Import Update',
        ]);
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'],
            'unit_id' => $this->smpId,
            'grade_level_id' => $this->grade7Id,
            'subject_id' => $this->subjectMatId,
            'effective_source' => 'OFFICIAL',
            'official_weekly_hours' => 4,
        ]);

        $method = new \ReflectionMethod(CurriculumImportService::class, 'validateRow');
        $result = $method->invoke(null, $version['id'], [
            'unit' => 'SMP', 'grade' => 'VII', 'subject_code' => 'MAT-SMP',
            'category' => 'INTRAKURIKULER', 'official_weekly_hours' => '5',
            'effective_source' => 'OFFICIAL',
        ]);

        $this->assertSame('WARNING', $result['status']);
        $this->assertSame('UPDATE', $result['proposed_action']);
    }

    public function testH03_ImportAppliesAsUpdateWithoutDuplicate(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code' => 'KUR-IMPORT-APPLY',
            'name' => 'Kurikulum Import Apply',
        ]);
        CurriculumStructureService::createStructure([
            'curriculum_version_id' => $version['id'], 'unit_id' => $this->smpId,
            'grade_level_id' => $this->grade7Id, 'subject_id' => $this->subjectMatId,
            'effective_source' => 'OFFICIAL', 'official_weekly_hours' => 4,
        ]);
        $batchModel = new CurriculumImportBatchModel();
        $batchId = $batchModel->insert([
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'curriculum_version_id' => $version['id'], 'source_filename' => 'update.xlsx',
            'source_hash' => str_repeat('a', 64), 'source_mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size' => 100, 'status' => 'VALIDATED', 'total_rows' => 1, 'warning_rows' => 1,
            'created_by' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        (new CurriculumImportRowModel())->insert([
            'batch_id' => $batchId, 'row_number' => 2, 'raw_data_json' => '{}',
            'normalized_data_json' => json_encode(['effective_weekly_hours' => 5, 'counts_in_report' => 1, 'counts_as_teaching_load' => 1]),
            'source_unit' => 'SMP', 'source_grade' => 'VII', 'source_subject' => 'MAT-SMP',
            'mapped_unit_id' => $this->smpId, 'mapped_grade_level_id' => $this->grade7Id,
            'mapped_subject_id' => $this->subjectMatId, 'official_hours' => 5,
            'effective_source' => 'OFFICIAL', 'category' => 'INTRAKURIKULER',
            'proposed_action' => 'UPDATE', 'validation_status' => 'WARNING', 'admin_decision' => 'UPDATE',
            'validation_messages_json' => '[]', 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $result = CurriculumImportService::applyBatch('10000000-0000-4000-8000-000000000001');

        $this->assertSame(1, $result['updated_rows']);
        $this->assertSame(0, $result['inserted_rows']);
        $rows = CurriculumStructureService::getStructures($version['id'])['data'];
        $this->assertCount(1, $rows);
        $this->assertSame(5.0, (float) $rows[0]['effective_weekly_hours']);
    }
}
