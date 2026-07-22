<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleImportRowModel extends Model
{
    protected $table            = 'schedule_import_rows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'batch_id',
        'row_number',
        'raw_data_json',
        'parsed_day_code',
        'parsed_slot_number',
        'parsed_class_code',
        'parsed_teacher_code',
        'parsed_subject_code',
        'parsed_room_code',
        'validation_status',
        'validation_errors_json',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
