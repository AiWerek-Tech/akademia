<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for student_narrative_drafts table.
 */
class StudentNarrativeDraftModel extends Model
{
    protected $table            = 'student_narrative_drafts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'subject_id',
        'classroom_id',
        'academic_period_id',
        'narrative_text',
        'tone',
        'created_by',
        'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Upsert narrative draft for a student/subject/classroom/period combination.
     */
    public function upsertDraft(array $data): bool
    {
        $existing = $this->where([
            'student_id'         => $data['student_id'],
            'subject_id'         => $data['subject_id'],
            'classroom_id'       => $data['classroom_id'],
            'academic_period_id' => $data['academic_period_id'],
        ])->first();

        if ($existing) {
            return (bool) $this->update($existing['id'], [
                'narrative_text' => $data['narrative_text'],
                'tone'           => $data['tone'] ?? $existing['tone'] ?? 'STANDARD',
                'updated_by'     => $data['updated_by'] ?? null,
            ]);
        }

        return (bool) $this->insert($data);
    }
}
