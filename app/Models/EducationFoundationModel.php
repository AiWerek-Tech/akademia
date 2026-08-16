<?php

namespace App\Models;

use CodeIgniter\Model;

abstract class EducationFoundationModel extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $protectFields = true;
}

