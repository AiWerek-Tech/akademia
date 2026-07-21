<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\MasterImportService;
use App\Services\MasterExportService;
use App\Services\TeacherService;
use App\Services\SubjectService;
use App\Services\RoomService;
use App\Models\MasterImportBatchModel;
use App\Models\MasterImportRowModel;
use App\Models\TeacherModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use Config\Database;

/**
 * Milestone 2 Import & Export Acceptance Tests
 *
 * Covers: K. Import Pipeline, L. Export Pipeline
 *
 * @internal
 */
final class ImportExportAcceptanceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private ?int $smpId = null;
    private ?int $smaId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $seeder = new Milestone2MasterSeeder(new \Config\Database());
        $seeder->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : null;
        $this->smaId = $sma ? (int)$sma['id'] : null;

        // Simulate logged-in super admin
        session()->set('user_id', 1);
    }

    // ==================================================
    // K. IMPORT PIPELINE TESTS
    // ==================================================

    /** K.1 Template generation for all supported types */
    public function testK01_TemplateGenerationAllTypes(): void
    {
        foreach (MasterImportService::SUPPORTED_TYPES as $type) {
            $path = MasterImportService::generateTemplate($type);
            $this->assertFileExists($path);
            $this->assertStringEndsWith('.xlsx', $path);

            // Clean up
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /** K.2 Template generation rejects invalid type */
    public function testK02_TemplateInvalidTypeRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak dikenal');

        MasterImportService::generateTemplate('INVALID_TYPE');
    }

    /** K.3 Validate row — TEACHERS valid data */
    public function testK03_ValidateTeacherRowValid(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'TEACHERS', [
            'full_name'         => 'Import Teacher A',
            'employment_status' => 'GURU_TETAP',
            'primary_unit'      => 'SMP',
        ]);

        $this->assertEquals('VALID', $result['status']);
        $this->assertEquals('INSERT', $result['proposed_action']);
    }

    /** K.4 Validate row — TEACHERS missing full_name */
    public function testK04_ValidateTeacherRowMissingName(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'TEACHERS', [
            'full_name'         => '',
            'employment_status' => 'GURU_TETAP',
        ]);

        $this->assertEquals('ERROR', $result['status']);
        $this->assertNotEmpty($result['messages']);
    }

    /** K.5 Validate row — SUBJECTS valid data */
    public function testK05_ValidateSubjectRowValid(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'SUBJECTS', [
            'code' => 'IMP-SUBJ-01',
            'name' => 'Import Subject',
        ]);

        $this->assertEquals('VALID', $result['status']);
        $this->assertEquals('INSERT', $result['proposed_action']);
    }

    /** K.6 Validate row — SUBJECTS duplicate code proposes UPDATE */
    public function testK06_ValidateSubjectDuplicateProposesUpdate(): void
    {
        // Create a subject first
        SubjectService::createSubject([
            'code'     => 'IMP-DUP-01',
            'name'     => 'Original Subject',
            'category' => 'WAJIB',
        ]);

        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'SUBJECTS', [
            'code' => 'IMP-DUP-01',
            'name' => 'Updated Subject',
        ]);

        $this->assertEquals('WARNING', $result['status']);
        $this->assertEquals('UPDATE', $result['proposed_action']);
        $this->assertNotNull($result['target_entity_id']);
    }

    /** K.7 Validate row — ROOMS missing code */
    public function testK07_ValidateRoomRowMissingCode(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'ROOMS', [
            'code' => '',
            'name' => 'Some Room',
        ]);

        $this->assertEquals('ERROR', $result['status']);
    }

    /** K.8 Validate row — ROOMS missing name */
    public function testK08_ValidateRoomRowMissingName(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'ROOMS', [
            'code' => 'R-IMP-01',
            'name' => '',
        ]);

        $this->assertEquals('ERROR', $result['status']);
    }

    /** K.9 Validate row — ROOMS duplicate code proposes UPDATE */
    public function testK09_ValidateRoomDuplicateProposesUpdate(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        RoomService::createRoom([
            'code'                 => 'IMP-DUP-RM',
            'name'                 => 'Original Room',
            'room_type_id'         => $roomType['id'],
            'unit_id'              => $this->smpId,
            'shared_between_units' => 0,
        ]);

        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'ROOMS', [
            'code' => 'IMP-DUP-RM',
            'name' => 'Updated Room',
        ]);

        $this->assertEquals('WARNING', $result['status']);
        $this->assertEquals('UPDATE', $result['proposed_action']);
    }

    /** K.10 Validate row — CLASSROOMS missing code */
    public function testK10_ValidateClassroomRowMissingCode(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'CLASSROOMS', [
            'code' => '',
            'name' => 'Some Class',
        ]);

        $this->assertEquals('ERROR', $result['status']);
    }

    /** K.11 Apply batch rejects already-applied batch */
    public function testK11_ApplyBatchRejectsAlreadyApplied(): void
    {
        $db = Database::connect($this->DBGroup);
        $batchModel = new MasterImportBatchModel();

        $batchId = $batchModel->insert([
            'import_type'     => 'TEACHERS',
            'source_filename' => 'test.xlsx',
            'source_hash'     => hash('sha256', 'test'),
            'source_mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size'     => 1024,
            'status'          => 'APPLIED',
            'total_rows'      => 1,
            'valid_rows'      => 1,
            'created_by'      => 1,
        ]);

        $batch = $batchModel->find($batchId);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak valid');

        MasterImportService::applyBatch($batch['uuid']);
    }

    /** K.12 Apply batch with valid staged rows creates teachers */
    public function testK12_ApplyBatchCreatesTeachers(): void
    {
        $db = Database::connect($this->DBGroup);
        $batchModel = new MasterImportBatchModel();
        $rowModel   = new MasterImportRowModel();

        $batchId = $batchModel->insert([
            'import_type'     => 'TEACHERS',
            'source_filename' => 'test_apply.xlsx',
            'source_hash'     => hash('sha256', 'test_apply'),
            'source_mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size'     => 2048,
            'status'          => 'VALIDATED',
            'total_rows'      => 2,
            'valid_rows'      => 2,
            'created_by'      => 1,
        ]);

        // Insert valid staged rows
        $rowModel->insert([
            'batch_id'                 => $batchId,
            'row_number'               => 2,
            'entity_type'              => 'TEACHERS',
            'raw_data_json'            => json_encode(['full_name' => 'Import Guru Alpha', 'employment_status' => 'GURU_TETAP']),
            'normalized_data_json'     => json_encode(['full_name' => 'Import Guru Alpha', 'employment_status' => 'GURU_TETAP']),
            'proposed_action'          => 'INSERT',
            'validation_status'        => 'VALID',
            'validation_messages_json' => json_encode([]),
            'admin_decision'           => 'INSERT',
        ]);

        $rowModel->insert([
            'batch_id'                 => $batchId,
            'row_number'               => 3,
            'entity_type'              => 'TEACHERS',
            'raw_data_json'            => json_encode(['full_name' => 'Import Guru Beta', 'employment_status' => 'GURU_HONORER']),
            'normalized_data_json'     => json_encode(['full_name' => 'Import Guru Beta', 'employment_status' => 'GURU_HONORER']),
            'proposed_action'          => 'INSERT',
            'validation_status'        => 'VALID',
            'validation_messages_json' => json_encode([]),
            'admin_decision'           => 'INSERT',
        ]);

        $batch = $batchModel->find($batchId);
        $result = MasterImportService::applyBatch($batch['uuid']);

        $this->assertEquals(2, $result['applied_rows']);

        // Verify teachers exist
        $teacherModel = new TeacherModel();
        $alpha = $teacherModel->where('full_name', 'Import Guru Alpha')->first();
        $beta  = $teacherModel->where('full_name', 'Import Guru Beta')->first();
        $this->assertNotNull($alpha);
        $this->assertNotNull($beta);

        // Verify batch status
        $updatedBatch = $batchModel->find($batchId);
        $this->assertEquals('APPLIED', $updatedBatch['status']);
    }

    /** K.13 Apply batch skips ERROR rows */
    public function testK13_ApplyBatchSkipsErrorRows(): void
    {
        $db = Database::connect($this->DBGroup);
        $batchModel = new MasterImportBatchModel();
        $rowModel   = new MasterImportRowModel();

        $batchId = $batchModel->insert([
            'import_type'     => 'TEACHERS',
            'source_filename' => 'test_skip.xlsx',
            'source_hash'     => hash('sha256', 'test_skip'),
            'source_mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size'     => 1024,
            'status'          => 'VALIDATED',
            'total_rows'      => 2,
            'valid_rows'      => 1,
            'error_rows'      => 1,
            'created_by'      => 1,
        ]);

        // Valid row
        $rowModel->insert([
            'batch_id'                 => $batchId,
            'row_number'               => 2,
            'entity_type'              => 'TEACHERS',
            'raw_data_json'            => json_encode(['full_name' => 'Skip Test Valid', 'employment_status' => 'GURU_TETAP']),
            'normalized_data_json'     => json_encode(['full_name' => 'Skip Test Valid', 'employment_status' => 'GURU_TETAP']),
            'proposed_action'          => 'INSERT',
            'validation_status'        => 'VALID',
            'validation_messages_json' => json_encode([]),
            'admin_decision'           => 'INSERT',
        ]);

        // Error row — should be skipped
        $rowModel->insert([
            'batch_id'                 => $batchId,
            'row_number'               => 3,
            'entity_type'              => 'TEACHERS',
            'raw_data_json'            => json_encode(['full_name' => '', 'employment_status' => '']),
            'normalized_data_json'     => json_encode(['full_name' => '', 'employment_status' => '']),
            'proposed_action'          => 'INSERT',
            'validation_status'        => 'ERROR',
            'validation_messages_json' => json_encode(['Nama wajib diisi']),
            'admin_decision'           => 'INSERT',
        ]);

        $batch = $batchModel->find($batchId);
        $result = MasterImportService::applyBatch($batch['uuid']);

        $this->assertEquals(1, $result['applied_rows']);
    }

    /** K.14 Apply batch skips SKIP decision rows */
    public function testK14_ApplyBatchSkipsSkipDecision(): void
    {
        $db = Database::connect($this->DBGroup);
        $batchModel = new MasterImportBatchModel();
        $rowModel   = new MasterImportRowModel();

        $batchId = $batchModel->insert([
            'import_type'     => 'TEACHERS',
            'source_filename' => 'test_admin_skip.xlsx',
            'source_hash'     => hash('sha256', 'test_admin_skip'),
            'source_mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size'     => 1024,
            'status'          => 'VALIDATED',
            'total_rows'      => 1,
            'valid_rows'      => 1,
            'created_by'      => 1,
        ]);

        $rowModel->insert([
            'batch_id'                 => $batchId,
            'row_number'               => 2,
            'entity_type'              => 'TEACHERS',
            'raw_data_json'            => json_encode(['full_name' => 'Admin Skipped', 'employment_status' => 'GURU_TETAP']),
            'normalized_data_json'     => json_encode(['full_name' => 'Admin Skipped', 'employment_status' => 'GURU_TETAP']),
            'proposed_action'          => 'INSERT',
            'validation_status'        => 'VALID',
            'validation_messages_json' => json_encode([]),
            'admin_decision'           => 'SKIP',
        ]);

        $batch = $batchModel->find($batchId);
        $result = MasterImportService::applyBatch($batch['uuid']);

        $this->assertEquals(0, $result['applied_rows']);
    }

    // ==================================================
    // L. EXPORT PIPELINE TESTS
    // ==================================================

    /** L.1 Export teachers to Excel generates valid file */
    public function testL01_ExportTeachersExcel(): void
    {
        // Create a teacher first
        TeacherService::createTeacher([
            'full_name'         => 'Export Teacher Test',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $path = MasterExportService::exportExcel('TEACHERS');
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.xlsx', $path);
        $this->assertGreaterThan(0, filesize($path));

        // Clean up
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** L.2 Export subjects to Excel */
    public function testL02_ExportSubjectsExcel(): void
    {
        SubjectService::createSubject([
            'code'     => 'EXP-SUBJ',
            'name'     => 'Export Subject Test',
            'category' => 'WAJIB',
        ]);

        $path = MasterExportService::exportExcel('SUBJECTS');
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** L.3 Export classrooms to Excel */
    public function testL03_ExportClassroomsExcel(): void
    {
        $path = MasterExportService::exportExcel('CLASSROOMS');
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** L.4 Export rooms to Excel */
    public function testL04_ExportRoomsExcel(): void
    {
        $path = MasterExportService::exportExcel('ROOMS');
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** L.5 Export invalid entity rejected */
    public function testL05_ExportInvalidEntityRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak dikenali');

        MasterExportService::exportExcel('INVALID');
    }
}
