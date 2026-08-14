<?php

namespace App\Services;

class ElectiveConflictMatrixService
{
    /**
     * Counts how often two primary offerings are selected by the same student.
     * The symmetric matrix is the input contract for later schedule-block generation.
     */
    public function build(array $offeringIds, array $choiceRows): array
    {
        $ids = array_values(array_unique(array_map('intval', $offeringIds)));
        $matrix = [];
        foreach ($ids as $left) {
            foreach ($ids as $right) {
                $matrix[$left][$right] = 0;
            }
        }
        $bySubmission = [];
        foreach ($choiceRows as $row) {
            $offeringId = (int) ($row['offering_id'] ?? 0);
            if (($row['choice_type'] ?? '') === 'PRIMARY' && in_array($offeringId, $ids, true)) {
                $bySubmission[(int) $row['submission_id']][] = $offeringId;
            }
        }
        foreach ($bySubmission as $selected) {
            $selected = array_values(array_unique($selected));
            $count = count($selected);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $matrix[$selected[$i]][$selected[$j]]++;
                    $matrix[$selected[$j]][$selected[$i]]++;
                }
            }
        }
        return $matrix;
    }
}
