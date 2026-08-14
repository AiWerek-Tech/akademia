<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Services\ElectiveSelectionService;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use Config\Database;

final class ElectiveSelectionWorkflowTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = false;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testSubmissionAndTwoStageReviewPersistAtomically(): void
    {
        (new Milestone2MasterSeeder(new Database()))->run();
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $suffix = substr(str_replace('-', '', UuidService::v4()), 0, 8);

        $unit = $db->table('school_units')->where('level', 'SMA')->get()->getRowArray();
        $this->assertNotEmpty($unit);
        // A unique year makes this regression test repeatable even when the
        // shared test database already contains elective fixtures.
        $db->table('academic_years')->insert([
            'uuid' => UuidService::v4(), 'name' => 'EL-' . $suffix,
            'start_date' => date('Y-m-d', strtotime('-60 days')),
            'end_date' => date('Y-m-d', strtotime('+300 days')),
            'status' => 'DRAFT', 'is_active' => 1, 'created_at' => $now,
        ]);
        $year = $db->table('academic_years')->where('id', $db->insertID())->get()->getRowArray();
        $db->table('academic_periods')->insert([
            'uuid' => UuidService::v4(), 'academic_year_id' => $year['id'],
            'semester_number' => 1, 'name' => 'Semester 1',
            'start_date' => $year['start_date'], 'end_date' => date('Y-m-d', strtotime('+120 days')),
            'workflow_status' => 'APPROVED', 'is_active' => 1, 'created_at' => $now,
        ]);
        $academicPeriod = $db->table('academic_periods')->where('id', $db->insertID())->get()->getRowArray();

        $db->table('users')->insert([
            'uuid' => UuidService::v4(), 'username' => 'elective-reviewer-' . $suffix,
            'full_name' => 'Elective Reviewer', 'password_hash' => password_hash('Test123!', PASSWORD_BCRYPT),
            'is_active' => 1, 'created_at' => $now,
        ]);
        $reviewerId = (int) $db->insertID();

        $db->table('curriculum_versions')->insert([
            'uuid' => UuidService::v4(), 'academic_period_id' => $academicPeriod['id'],
            'code' => 'EL-CUR-' . $suffix, 'name' => 'Elective Curriculum',
            'workflow_status' => 'APPROVED', 'is_active' => 1, 'created_at' => $now,
        ]);
        $curriculumId = (int) $db->insertID();

        $db->table('elective_periods')->insert([
            'uuid' => UuidService::v4(), 'unit_id' => $unit['id'], 'academic_year_id' => $year['id'],
            'curriculum_version_id' => $curriculumId, 'title' => 'Elective Test',
            'source_grade' => 10, 'target_grade' => 11,
            'selection_start_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'selection_end_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'min_primary_choices' => 4, 'max_primary_choices' => 5, 'max_backup_choices' => 2,
            'minimum_subjects_offered' => 7, 'allow_changes' => 1,
            'change_deadline' => date('Y-m-d', strtotime('+30 days')), 'status' => 'PUBLISHED',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $periodId = (int) $db->insertID();

        $offeringIds = [];
        foreach (range(1, 7) as $index) {
            $db->table('subjects')->insert([
                'uuid' => UuidService::v4(), 'code' => 'EL' . $suffix . $index,
                'name' => 'Elective Subject ' . $index, 'normalized_name' => 'elective subject ' . $index,
                'short_name' => 'EL' . $index, 'category' => 'PILIHAN',
                'is_active' => 1, 'created_at' => $now,
            ]);
            $subjectId = (int) $db->insertID();
            $db->table('elective_offerings')->insert([
                'uuid' => UuidService::v4(), 'elective_period_id' => $periodId,
                'subject_id' => $subjectId, 'minimum_students' => 1, 'maximum_students' => 20,
                'weekly_hours' => 5, 'is_open' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $offeringIds[] = (int) $db->insertID();
        }

        $db->table('elective_students')->insert([
            'uuid' => UuidService::v4(), 'unit_id' => $unit['id'], 'academic_year_id' => $year['id'],
            'student_number' => 'STU-' . $suffix, 'full_name' => 'Test Student',
            'current_grade' => 10, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentId = (int) $db->insertID();

        $service = new ElectiveSelectionService($db);
        $db->table('elective_students')->where('id', $studentId)->update(['user_id' => $reviewerId]);
        $db->table('elective_periods')->where('id', $periodId)->update([
            'selection_start_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'selection_end_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $service->save(
            $periodId,
            $studentId,
            array_slice($offeringIds, 0, 2),
            [],
            [],
            $reviewerId,
            false
        );
        try {
            $service->save(
                $periodId,
                $studentId,
                array_slice($offeringIds, 0, 5),
                [],
                [],
                $reviewerId,
                true
            );
            $this->fail('Siswa tidak boleh mengirim pilihan setelah periode ditutup.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('di luar periode', $exception->getMessage());
        }
        $db->table('elective_periods')->where('id', $periodId)->update([
            'selection_start_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'selection_end_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        $submissionId = $service->save(
            $periodId,
            $studentId,
            array_slice($offeringIds, 0, 5),
            [],
            ['career_plan' => 'Kuliah', 'intended_major' => 'Teknik', 'selection_reason' => 'Sesuai minat'],
            $reviewerId,
            true
        );

        $submission = $db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray();
        $this->assertSame('SUBMITTED', $submission['status']);
        $this->assertSame(5, $db->table('student_elective_choices')->where('submission_id', $submissionId)->countAllResults());
        $this->assertSame(0, $db->table('student_elective_choices')->where('submission_id', $submissionId)
            ->where('choice_type', 'BACKUP')->countAllResults());

        $service->review($submissionId, 'BK', 'APPROVED', 'Sesuai minat dan kemampuan', $reviewerId);
        $this->assertSame('WAITING_CURRICULUM', $db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray()['status']);
        $service->review($submissionId, 'CURRICULUM', 'APPROVED', 'Sumber daya tersedia', $reviewerId);
        $this->assertSame('APPROVED', $db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray()['status']);
        $this->assertSame(2, $db->table('student_elective_reviews')->where('submission_id', $submissionId)->countAllResults());

        // Kelas XII is a fixed continuation choice: even a stale legacy
        // allow_changes flag must never reopen an approved submission.
        $db->table('elective_periods')->where('id', $periodId)->update([
            'target_grade' => 12,
            'allow_changes' => 1,
            'change_deadline' => date('Y-m-d', strtotime('+30 days')),
        ]);
        try {
            $service->requestChange(
                $submissionId,
                array_slice($offeringIds, 0, 4),
                [],
                'Permintaan perubahan yang seharusnya ditolak',
                $reviewerId
            );
            $this->fail('Perubahan pilihan kelas XII seharusnya selalu ditolak.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('kelas XII', $exception->getMessage());
        }
        $db->table('elective_periods')->where('id', $periodId)->update([
            'target_grade' => 11,
            'allow_changes' => 1,
        ]);

        $changeId = $service->requestChange(
            $submissionId,
            [$offeringIds[1], $offeringIds[2], $offeringIds[3], $offeringIds[4]],
            [$offeringIds[0], $offeringIds[5]],
            'Penilaian ulang minat bersama Guru BK',
            $reviewerId
        );
        $this->assertGreaterThan(0, $changeId);
        $this->assertSame('CHANGE_REQUESTED', $db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray()['status']);
        $service->reviewChange($changeId, 'APPROVED', 'Disetujui berdasarkan penilaian ulang', $reviewerId);
        $this->assertSame('CHANGED', $db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray()['status']);
        $firstChoice = $db->table('student_elective_choices')->where('submission_id', $submissionId)
            ->where('choice_type', 'PRIMARY')->where('priority_order', 1)->get()->getRowArray();
        $this->assertSame($offeringIds[1], (int) $firstChoice['offering_id']);
    }
}
