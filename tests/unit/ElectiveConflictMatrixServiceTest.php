<?php

use App\Services\ElectiveConflictMatrixService;
use CodeIgniter\Test\CIUnitTestCase;

final class ElectiveConflictMatrixServiceTest extends CIUnitTestCase
{
    public function testMatrixIsSymmetricAndCountsSharedPrimarySelections(): void
    {
        $rows = [
            ['submission_id' => 1, 'offering_id' => 10, 'choice_type' => 'PRIMARY'],
            ['submission_id' => 1, 'offering_id' => 20, 'choice_type' => 'PRIMARY'],
            ['submission_id' => 1, 'offering_id' => 30, 'choice_type' => 'BACKUP'],
            ['submission_id' => 2, 'offering_id' => 10, 'choice_type' => 'PRIMARY'],
            ['submission_id' => 2, 'offering_id' => 20, 'choice_type' => 'PRIMARY'],
            ['submission_id' => 2, 'offering_id' => 30, 'choice_type' => 'PRIMARY'],
        ];

        $matrix = (new ElectiveConflictMatrixService())->build([10, 20, 30], $rows);

        $this->assertSame(2, $matrix[10][20]);
        $this->assertSame($matrix[10][20], $matrix[20][10]);
        $this->assertSame(1, $matrix[10][30]);
        $this->assertSame(0, $matrix[10][10]);
    }
}
