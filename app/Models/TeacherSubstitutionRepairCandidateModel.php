<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherSubstitutionRepairCandidateModel extends Model
{
    protected $table = 'teacher_substitution_repair_candidates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid', 'substitution_id', 'status', 'score', 'moved_entry_count',
        'critical_before', 'critical_after', 'changes_json', 'base_revisions_json',
        'diagnostics_json', 'created_by', 'applied_by', 'applied_at',
    ];
}
