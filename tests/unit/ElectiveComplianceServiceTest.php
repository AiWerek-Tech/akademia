<?php

use App\Services\ElectiveComplianceService;
use CodeIgniter\Test\CIUnitTestCase;

final class ElectiveComplianceServiceTest extends CIUnitTestCase
{
    public function testPrakaryaTwoJpDoesNotCreateWarningCard(): void
    {
        $period = [
            'selection_type' => 'FASE_F_ELECTIVE',
            'min_primary_choices' => 4,
            'max_primary_choices' => 5,
            'minimum_subjects_offered' => 7,
            'selection_start_at' => '2026-07-01 08:00:00',
            'selection_end_at' => '2026-07-31 16:00:00',
            'curriculum_version_id' => 1,
        ];
        $offerings = array_map(static fn (int $id): array => [
            'subject_id' => $id,
            'subject_code' => $id === 7 ? 'PRAK' : 'MAPEL-' . $id,
            'subject_name' => $id === 7 ? 'Prakarya dan Kewirausahaan' : 'Mapel ' . $id,
            'is_open' => 1,
            'minimum_students' => 1,
            'maximum_students' => 20,
            'weekly_hours' => $id === 7 ? 2 : 5,
            'teacher_id' => $id,
        ], range(1, 7));

        $result = (new ElectiveComplianceService())->assessPeriod($period, $offerings);

        $this->assertSame([], $result['warnings']);
    }

    public function testCompliantSmaPeriodPasses(): void
    {
        $period = [
            'min_primary_choices' => 4, 'max_primary_choices' => 5,
            'minimum_subjects_offered' => 7, 'selection_start_at' => '2027-05-01 00:00:00',
            'selection_end_at' => '2027-05-15 23:59:59', 'allow_changes' => 1,
            'change_deadline' => '2028-01-31',
            'curriculum_version_id' => 1,
        ];
        $offerings = array_map(static fn (int $id): array => [
            'subject_id' => $id, 'subject_name' => "Mapel {$id}", 'is_open' => 1,
            'minimum_students' => 3, 'maximum_students' => 20, 'weekly_hours' => 5,
            'teacher_id' => $id,
        ], range(1, 7));

        $result = (new ElectiveComplianceService())->assessPeriod($period, $offerings);

        $this->assertTrue($result['compliant']);
        $this->assertSame([], $result['errors']);
    }

    public function testPublicationBlocksNonCompliantChoiceRangeAndOfferingCount(): void
    {
        $result = (new ElectiveComplianceService())->assessPeriod([
            'min_primary_choices' => 3, 'max_primary_choices' => 6,
            'minimum_subjects_offered' => 7, 'selection_start_at' => '2027-05-01',
            'selection_end_at' => '2027-05-15', 'allow_changes' => 0,
            'curriculum_version_id' => 1,
        ], []);

        $this->assertFalse($result['compliant']);
        $this->assertCount(2, $result['errors']);
    }

    public function testSelectionRejectsDuplicatesAndUnavailableOfferings(): void
    {
        $errors = (new ElectiveComplianceService())->validateSelection(
            [1, 2, 3, 4],
            [4, 99],
            ['min_primary_choices' => 4, 'max_primary_choices' => 5, 'max_backup_choices' => 2],
            [1, 2, 3, 4, 5, 6, 7]
        );

        $this->assertCount(2, $errors);
    }

    public function testPublicationBlocksMissingCurriculumTeacherAndStructureLink(): void
    {
        $period = [
            'min_primary_choices' => 4, 'max_primary_choices' => 5,
            'minimum_subjects_offered' => 7, 'selection_start_at' => '2027-05-01',
            'selection_end_at' => '2027-05-15', 'allow_changes' => 0,
            'curriculum_version_id' => null,
        ];
        $offerings = array_map(static fn (int $id): array => [
            'subject_id' => $id, 'subject_name' => "Mapel {$id}", 'is_open' => 1,
            'minimum_students' => 1, 'maximum_students' => 20, 'weekly_hours' => 5,
            'teacher_id' => null, 'in_curriculum' => 0,
        ], range(1, 7));

        $result = (new ElectiveComplianceService())->assessPeriod($period, $offerings);

        $this->assertFalse($result['compliant']);
        $this->assertCount(15, $result['errors']);
    }

    public function testIncompleteDraftCanBeSavedButCannotBeSubmitted(): void
    {
        $service = new ElectiveComplianceService();
        $period = ['min_primary_choices' => 4, 'max_primary_choices' => 5, 'max_backup_choices' => 2];

        $this->assertSame([], $service->validateSelection([1, 2], [], $period, [1, 2, 3, 4], false));
        $this->assertNotEmpty($service->validateSelection([1, 2], [], $period, [1, 2, 3, 4], true));
    }

    public function testSubmissionAllowsCustomWeeklyHoursWhenSubjectCountIsValid(): void
    {
        $service = new ElectiveComplianceService();
        $period = ['min_primary_choices' => 4, 'max_primary_choices' => 5, 'max_backup_choices' => 2];
        $hours = [1 => 5, 2 => 5, 3 => 5, 4 => 2, 5 => 5];

        // 17 JP is a UI warning only: curriculum structures may define custom
        // allocations. Submission is constrained by 4-5 subjects, not JP.
        $this->assertSame([], $service->validateSelection([1, 2, 3, 4], [], $period, array_keys($hours), true, $hours));
        $this->assertSame([], $service->validateSelection([1, 2, 3, 4, 5], [], $period, array_keys($hours), true, $hours));
    }

    public function testBackupChoicesAreOptionalWhenPrimarySelectionIsComplete(): void
    {
        $service = new ElectiveComplianceService();
        $period = [
            'selection_type' => 'FASE_F_ELECTIVE',
            'min_primary_choices' => 4,
            'max_primary_choices' => 5,
            'max_backup_choices' => 2,
        ];
        $hours = [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5];

        $this->assertSame(
            [],
            $service->validateSelection([1, 2, 3, 4, 5], [], $period, array_keys($hours), true, $hours)
        );
    }

    public function testArtCultureCraftPeriodPassesWithOneOfferingAndOneChoice(): void
    {
        $period = [
            'selection_type' => 'ART_CULTURE_CRAFT',
            'min_primary_choices' => 1, 'max_primary_choices' => 1,
            'minimum_subjects_offered' => 1, 'selection_start_at' => '2027-05-01 00:00:00',
            'selection_end_at' => '2027-05-15 23:59:59', 'allow_changes' => 1,
            'change_deadline' => '2028-01-31',
            'curriculum_version_id' => 1,
        ];
        $offerings = [
            [
                'subject_id' => 10, 'subject_name' => 'Seni Musik', 'is_open' => 1,
                'minimum_students' => 1, 'maximum_students' => 30, 'weekly_hours' => 2,
                'teacher_id' => 1,
            ],
        ];

        $result = (new ElectiveComplianceService())->assessPeriod($period, $offerings);

        $this->assertTrue($result['compliant']);
        $this->assertSame([], $result['errors']);
    }

    public function testArtCultureCraftSelectionValidatesSingleChoice(): void
    {
        $service = new ElectiveComplianceService();
        $period = [
            'selection_type' => 'ART_CULTURE_CRAFT',
            'min_primary_choices' => 1, 'max_primary_choices' => 1, 'max_backup_choices' => 1,
        ];

        // Valid single choice
        $this->assertSame([], $service->validateSelection([10], [], $period, [10, 11], true));

        // Invalid: 2 primary choices
        $this->assertNotEmpty($service->validateSelection([10, 11], [], $period, [10, 11], true));
    }
}
