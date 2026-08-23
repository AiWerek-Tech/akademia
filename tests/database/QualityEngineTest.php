<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\QualityService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 10 — Quality & AI Copilot Engine Test Suite.
 *
 * Verifies Teacher Reflection lifecycle, Academic Supervision lifecycle,
 * AI Copilot governance (Explainability, Safety States, Feedback loop),
 * KSP Evaluation integration, and Official A4 Observation Print view.
 *
 * @internal
 */
final class QualityEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private QualityService $service;
    protected int $teacherId;
    protected int $supervisorId;
    protected int $classroomId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new QualityService($this->db);

        // Teacher
        $teacher = $this->db->table('teachers')->where('primary_unit_id', $this->unitId)->get()->getRowArray();
        if (! $teacher) {
            $this->db->table('teachers')->insert([
                'uuid'             => '94000000-0000-4000-8000-000000000001',
                'full_name'        => 'Guru Mutu Percontohan, S.Pd.',
                'normalized_name'  => 'GURU MUTU PERCONTOHAN',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id'  => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        // Supervisor
        $supervisor = $this->db->table('teachers')->where('id !=', $this->teacherId)->where('primary_unit_id', $this->unitId)->get()->getRowArray();
        if (! $supervisor) {
            $this->db->table('teachers')->insert([
                'uuid'             => '94000000-0000-4000-8000-000000000002',
                'full_name'        => 'Dra. Supervisor Akademik, M.Pd.',
                'normalized_name'  => 'SUPERVISOR AKADEMIK',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id'  => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->supervisorId = (int) $this->db->insertID();
        } else {
            $this->supervisorId = (int) $supervisor['id'];
        }

        // Classroom
        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'                => '94000000-0000-4000-8000-000000000003',
                'unit_id'             => $this->unitId,
                'academic_period_id'  => $this->periodId,
                'grade_level_id'      => $this->gradeId,
                'name'                => 'Kelas X-MIPA-1',
                'homeroom_teacher_id' => $this->teacherId,
                'is_active'           => 1,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);
            $this->classroomId = (int) $this->db->insertID();
        } else {
            $this->classroomId = (int) $classroom['id'];
        }
    }

    public function testReflectionLifecycle(): void
    {
        $userId = 1;

        // 1. Create Reflection
        $reflectionId = $this->service->createReflection([
            'teacher_id'         => $this->teacherId,
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'subject_id'         => $this->subjectId,
            'classroom_id'       => $this->classroomId,
            'reflection_type'    => QualityService::REFLECTION_POST_LESSON,
            'what_went_well'     => 'Murid sangat antusias saat praktik coding langsung.',
            'what_to_improve'    => 'Perlu penambahan contoh kasus kontekstual.',
            'next_steps'         => 'Menyiapkan modul diferensiasi kelompok.',
        ], $userId);

        $this->assertGreaterThan(0, $reflectionId);

        // 2. Detail Reflection
        $detail = $this->service->detailReflection($reflectionId);
        $this->assertNotNull($detail);
        $this->assertSame('DRAFT', $detail['status']);
        $this->assertSame(QualityService::REFLECTION_POST_LESSON, $detail['reflection_type']);
        $this->assertNotEmpty($detail['teacher_name']);
        $this->assertNotEmpty($detail['subject_name']);

        // 3. List Reflections
        $list = $this->service->listReflections($this->teacherId, $this->periodId);
        $this->assertCount(1, $list);

        // 4. Update and Publish Reflection
        $this->service->updateReflection($reflectionId, [
            'what_went_well' => 'Murid sangat aktif dan kolaboratif.',
            'ai_draft'       => 'Draft refleksi rekomendasi AI',
            'ai_status'      => QualityService::AI_ACCEPTED,
            'status'         => 'PUBLISHED',
        ], $userId);

        $updated = $this->service->detailReflection($reflectionId);
        $this->assertSame('PUBLISHED', $updated['status']);
        $this->assertSame(QualityService::AI_ACCEPTED, $updated['ai_status']);

        // 5. Reflection Stats & Type Breakdown
        $stats = $this->service->reflectionStats($this->unitId, $this->periodId);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['published']);
        $this->assertSame(1, $stats['with_ai']);
        $this->assertSame(0, $stats['draft']);

        $types = $this->service->reflectionTypeBreakdown($this->unitId, $this->periodId);
        $this->assertSame(1, $types[QualityService::REFLECTION_POST_LESSON]);
        $this->assertSame(0, $types[QualityService::REFLECTION_PERIODIC]);
    }

    public function testSupervisionLifecycleAndRatingBreakdown(): void
    {
        $userId = 1;

        // 1. Create Supervision Record
        $supId = $this->service->createSupervision([
            'teacher_id'         => $this->teacherId,
            'supervisor_id'      => $this->supervisorId,
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'observation_date'   => date('Y-m-d'),
            'subject_id'         => $this->subjectId,
            'classroom_id'       => $this->classroomId,
            'observation_type'   => QualityService::OBS_CLASSROOM,
            'strengths'          => 'Manajemen kelas sangat baik dan interaktif.',
            'areas_for_growth'   => 'Diferensiasi lembar kerja perlu diperluas.',
            'recommendations'    => 'Gunakan pendekatan inquiry berbasis proyek.',
            'overall_rating'     => QualityService::RATING_EXCELLENT,
            'follow_up_needed'   => 1,
            'follow_up_notes'    => 'Supervisi klinis follow-up bulan depan.',
        ], $userId);

        $this->assertGreaterThan(0, $supId);

        // 2. Detail Supervision
        $detail = $this->service->detailSupervision($supId);
        $this->assertNotNull($detail);
        $this->assertSame(QualityService::RATING_EXCELLENT, $detail['overall_rating']);
        $this->assertSame(1, (int) $detail['follow_up_needed']);
        $this->assertNotEmpty($detail['teacher_name']);
        $this->assertNotEmpty($detail['supervisor_name']);

        // 3. List Supervisions with Filters
        $list = $this->service->listSupervisions($this->unitId, $this->periodId, ['follow_up' => '1']);
        $this->assertCount(1, $list);

        // 4. Update Supervision to Completed
        $this->service->updateSupervision($supId, [
            'status' => 'COMPLETED',
        ], $userId);

        $completed = $this->service->detailSupervision($supId);
        $this->assertSame('COMPLETED', $completed['status']);

        // 5. Supervision Stats & Rating Breakdown
        $stats = $this->service->supervisionStats($this->unitId, $this->periodId);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(1, $stats['follow_up']);

        $ratings = $this->service->supervisionRatingBreakdown($this->unitId, $this->periodId);
        $this->assertSame(1, $ratings[QualityService::RATING_EXCELLENT]);
        $this->assertSame(0, $ratings[QualityService::RATING_GOOD]);
    }

    public function testAiCopilotGovernanceAndSafetyStates(): void
    {
        $userId = 1;

        // 1. Generate Reflection Draft (Rule-Based with Explainability)
        $res1 = $this->service->generateAiDraft(QualityService::PROMPT_REFLECTION_DRAFT, [
            'subject'        => 'Informatika & Pemrograman',
            'mastery_pct'    => 88,
            'attendance_pct' => 95,
        ], $userId);

        $this->assertArrayHasKey('id', $res1);
        $this->assertArrayHasKey('output', $res1);
        $this->assertStringContainsString('Refleksi Pembelajaran', $res1['output']);
        $this->assertStringContainsString('Dasar Analisis AI', $res1['output']); // Explainability §10.7

        // 2. Generate Supervision Summary
        $res2 = $this->service->generateAiDraft(QualityService::PROMPT_SUPERVISION_SUMMARY, [
            'teacher_name' => 'Budi Setiawan',
            'rating'       => QualityService::RATING_EXCELLENT,
            'subject'      => 'Matematika',
        ], $userId);
        $this->assertStringContainsString('Rangkuman Observasi Akademik', $res2['output']);

        // 3. Generate Improvement Idea & Learning Tips
        $res3 = $this->service->generateAiDraft(QualityService::PROMPT_IMPROVEMENT_IDEA, ['area' => 'Asesmen Formatif'], $userId);
        $this->assertStringContainsString('Strategi Peningkatan Kualitas', $res3['output']);

        $res4 = $this->service->generateAiDraft(QualityService::PROMPT_LEARNING_TIPS, ['subject' => 'Fisika'], $userId);
        $this->assertStringContainsString('Tips Pedagogik', $res4['output']);

        $res5 = $this->service->generateAiDraft(QualityService::PROMPT_CLASS_SUMMARY, [
            'class_name'     => 'X-IPA-2',
            'avg_score'      => 82.5,
            'total_students' => 30,
        ], $userId);
        $this->assertStringContainsString('Ringkasan Mutu Akademik', $res5['output']);

        // 4. Accept AI Output (Safety State: DRAFT -> ACCEPTED)
        $aiId = $res1['id'];
        $this->service->acceptAiOutput($aiId, 'Diedit sedikit oleh guru', $userId);

        $row = $this->db->table('ai_copilot_outputs')->where('id', $aiId)->get()->getRowArray();
        $this->assertSame('ACCEPTED', $row['approval_status']);
        $this->assertSame('Diedit sedikit oleh guru', $row['human_edit']);

        // 5. Reject AI Output
        $rejectId = $res2['id'];
        $this->service->rejectAiOutput($rejectId, 'Kurang sesuai konteks kelas', $userId);
        $rowReject = $this->db->table('ai_copilot_outputs')->where('id', $rejectId)->get()->getRowArray();
        $this->assertSame('REJECTED', $rowReject['approval_status']);

        // 6. Teacher Feedback Loop (USEFUL, WRONG_ALIGNMENT)
        $this->service->submitFeedback($aiId, QualityService::FEEDBACK_USEFUL, 'Sangat membantu menyusun refleksi', $userId);
        $rowFb = $this->db->table('ai_copilot_outputs')->where('id', $aiId)->get()->getRowArray();
        $this->assertSame(QualityService::FEEDBACK_USEFUL, $rowFb['feedback_rating']);

        // 7. AI Stats and Adoption Breakdown
        $stats = $this->service->aiStats($userId);
        $this->assertSame(5, $stats['total']);
        $this->assertSame(1, $stats['accepted']);
        $this->assertSame(1, $stats['rejected']);
        $this->assertSame(3, $stats['pending']);

        $adoption = $this->service->aiAdoptionBreakdown($userId);
        $this->assertSame(5, $adoption['total']);
        $this->assertSame(1, $adoption['feedback_useful']);
    }

    public function testQualityControllerDashboardAndReports(): void
    {
        $sessionData = [
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
            'user_roles'       => ['superadmin'],
        ];

        // 1. Dashboard Mutu
        $resIndex = $this->withSession($sessionData)->get('quality');
        $resIndex->assertOK();
        $resIndex->assertSee('Pusat Penjaminan Mutu');

        // 2. Refleksi Index
        $resRef = $this->withSession($sessionData)->get('quality/reflections');
        $resRef->assertOK();
        $resRef->assertSee('Refleksi Guru');

        // 3. Supervisi Index
        $resSup = $this->withSession($sessionData)->get('quality/supervisions');
        $resSup->assertOK();
        $resSup->assertSee('Catatan Supervisi');

        // 4. AI Copilot Index
        $resCopilot = $this->withSession($sessionData)->get('quality/copilot');
        $resCopilot->assertOK();
        $resCopilot->assertSee('AI Copilot');

        // 5. Laporan Mutu
        $resRep = $this->withSession($sessionData)->get('quality/report');
        $resRep->assertOK();
        $resRep->assertSee('Laporan Mutu Akademik');
    }

    public function testSupervisionPrintViewRendering(): void
    {
        $userId = 1;

        $supId = $this->service->createSupervision([
            'teacher_id'         => $this->teacherId,
            'supervisor_id'      => $this->supervisorId,
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'observation_date'   => '2026-08-20',
            'subject_id'         => $this->subjectId,
            'classroom_id'       => $this->classroomId,
            'observation_type'   => QualityService::OBS_CLASSROOM,
            'strengths'          => 'Metode ceramah interaktif sangat memikat.',
            'areas_for_growth'   => 'Scaffolding lembar kerja perlu diperkuat.',
            'recommendations'    => 'Lanjutkan praktik baik ini pada bab berikutnya.',
            'overall_rating'     => QualityService::RATING_GOOD,
            'status'             => 'COMPLETED',
        ], $userId);

        $sessionData = [
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
            'user_roles'       => ['superadmin'],
        ];

        $res = $this->withSession($sessionData)->get("quality/supervision/{$supId}/print");
        $res->assertOK();
        $res->assertSee('Lembar Hasil Supervisi');
        $res->assertSee('Guru Mutu Percontohan');
        $res->assertSee('BAIK (GOOD)');
        $res->assertSee('Mengetahui');
    }
}
