<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
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
    use IsolatedDatabaseTestTrait;

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
            $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $this->assertSame(['Data Import', 'Contoh', 'Petunjuk Pengisian', 'Referensi'], $workbook->getSheetNames());
            $headers = MasterImportService::headers($type);
            $actualHeaders = $workbook->getSheetByName('Data Import')
                ->rangeToArray('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')[0];
            $this->assertSame($headers, $actualHeaders);
            $this->assertNotEmpty($workbook->getSheetByName('Data Import')->getAutoFilter()->getRange());
            $this->assertCount(1, $workbook->getSheetByName('Data Import')->getTableCollection());
            $validationCell = match ($type) {
                'TEACHERS' => 'O2',
                'SUBJECTS' => 'D2',
                'GRADE_LEVELS' => 'A2',
                'CLASSROOMS' => 'A2',
                'ROOMS' => 'C2',
                'STUDENTS' => 'C2',
            };
            $this->assertSame(
                \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST,
                $workbook->getSheetByName('Data Import')->getCell($validationCell)->getDataValidation()->getType()
            );
            $workbook->disconnectWorksheets();

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
            'category' => 'WAJIB',
            'units' => 'SMP',
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
            'category' => 'WAJIB',
            'units' => 'SMP',
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
            'room_type' => $roomType['code'],
            'unit' => 'SMP',
            'shared_between_units' => 0,
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
            'normalized_data_json'     => json_encode(['full_name' => 'Import Guru Alpha', 'employment_status' => 'GURU_TETAP', 'primary_unit_id' => $this->smpId, 'unit_ids' => [$this->smpId]]),
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
            'normalized_data_json'     => json_encode(['full_name' => 'Import Guru Beta', 'employment_status' => 'GURU_HONORER', 'primary_unit_id' => $this->smpId, 'unit_ids' => [$this->smpId]]),
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
            'normalized_data_json'     => json_encode(['full_name' => 'Skip Test Valid', 'employment_status' => 'GURU_TETAP', 'primary_unit_id' => $this->smpId, 'unit_ids' => [$this->smpId]]),
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

    /** K.15 Grade-level rows are validated with unit scope and normalized values. */
    public function testK15_ValidateGradeLevelRow(): void
    {
        $method = new \ReflectionMethod(MasterImportService::class, 'validateRow');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'GRADE_LEVELS', [
            'unit' => 'SMP',
            'grade_number' => '6',
            'code' => 'VI',
            'name' => 'Kelas VI',
            'phase' => 'C',
            'active' => '1',
        ]);

        $this->assertSame('VALID', $result['status']);
        $this->assertSame($this->smpId, $result['normalized_data']['unit_id']);
        $this->assertSame(6, $result['normalized_data']['grade_number']);
    }

    /** K.16 Applying a grade-level batch creates the new scoped master row. */
    public function testK16_ApplyGradeLevelBatch(): void
    {
        $batchModel = new MasterImportBatchModel();
        $rowModel = new MasterImportRowModel();
        $batchId = $batchModel->insert([
            'import_type' => 'GRADE_LEVELS',
            'source_filename' => 'grade_levels.xlsx',
            'source_hash' => hash('sha256', 'grade_levels'),
            'source_mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'source_size' => 1024,
            'status' => 'VALIDATED',
            'total_rows' => 1,
            'valid_rows' => 1,
            'created_by' => 1,
        ]);
        $rowModel->insert([
            'batch_id' => $batchId,
            'row_number' => 2,
            'entity_type' => 'GRADE_LEVELS',
            'raw_data_json' => json_encode(['unit' => 'SMP', 'grade_number' => '6', 'code' => 'VI', 'name' => 'Kelas VI']),
            'normalized_data_json' => json_encode(['unit_id' => $this->smpId, 'grade_number' => 6, 'code' => 'VI', 'name' => 'Kelas VI', 'phase' => 'C', 'sort_order' => 6, 'is_active' => 1]),
            'proposed_action' => 'INSERT',
            'validation_status' => 'VALID',
            'validation_messages_json' => json_encode([]),
            'admin_decision' => 'INSERT',
        ]);

        $result = MasterImportService::applyBatch($batchModel->find($batchId)['uuid']);
        $this->assertSame(1, $result['applied_rows']);
        $this->seeInDatabase('grade_levels', ['unit_id' => $this->smpId, 'code' => 'VI', 'grade_number' => 6]);
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
        $this->assertWorkbookUsesImportContract($path, 'TEACHERS');

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
        $this->assertWorkbookUsesImportContract($path, 'SUBJECTS');

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
        $this->assertWorkbookUsesImportContract($path, 'CLASSROOMS');

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
        $this->assertWorkbookUsesImportContract($path, 'ROOMS');

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

    /** L.6 Every page export uses the same official workbook contract as Import Master. */
    public function testL06_AllMasterExportsUseOfficialImportContract(): void
    {
        foreach (MasterImportService::SUPPORTED_TYPES as $type) {
            $path = MasterExportService::exportExcel($type);
            $this->assertFileExists($path);
            $this->assertWorkbookUsesImportContract($path, $type);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function assertWorkbookUsesImportContract(string $path, string $type): void
    {
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $this->assertSame(['Data Import', 'Contoh', 'Petunjuk Pengisian', 'Referensi'], $workbook->getSheetNames());
        $headers = MasterImportService::headers($type);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $actualHeaders = $workbook->getSheetByName('Data Import')->rangeToArray("A1:{$lastColumn}1")[0];
        $this->assertSame($headers, $actualHeaders);
        $workbook->disconnectWorksheets();
    }
}
