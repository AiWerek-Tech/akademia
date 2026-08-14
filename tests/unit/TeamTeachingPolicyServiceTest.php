<?php

namespace Tests\Unit;

use App\Services\TeamTeachingPolicyService;
use CodeIgniter\Test\CIUnitTestCase;

/** @internal */
final class TeamTeachingPolicyServiceTest extends CIUnitTestCase
{
    public function testSinglePrimaryTeacherIsAcceptedWhileFeatureIsOff(): void
    {
        $policy = new TeamTeachingPolicyService();

        $policy->assertAssignmentsSupported([[
            'team_group_uuid' => null,
            'teaching_assignment_group_id' => null,
            'is_primary_teacher' => 1,
        ]]);
        $policy->assertRequirementSupported(['second_teacher_id' => null]);
        $policy->assertSecondTeacherSupported(null);

        $this->assertFalse($policy->isEnabled());
    }

    /** @dataProvider unsupportedAssignmentProvider */
    public function testTeamAssignmentIsRejectedWithoutDroppingMembers(array $assignment): void
    {
        $this->expectException(\RuntimeException::class);
        (new TeamTeachingPolicyService())->assertAssignmentsSupported([$assignment]);
    }

    public static function unsupportedAssignmentProvider(): iterable
    {
        yield 'group uuid' => [['team_group_uuid' => 'team-1', 'is_primary_teacher' => 1]];
        yield 'normalized group' => [['teaching_assignment_group_id' => 7, 'is_primary_teacher' => 1]];
        yield 'secondary member' => [['is_primary_teacher' => 0]];
    }

    public function testSecondTeacherRequirementIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        (new TeamTeachingPolicyService())->assertRequirementSupported(['second_teacher_id' => 42]);
    }

    public function testEditorCannotSilentlyPersistSecondTeacher(): void
    {
        $this->expectException(\RuntimeException::class);
        (new TeamTeachingPolicyService())->assertSecondTeacherSupported(42);
    }
}
