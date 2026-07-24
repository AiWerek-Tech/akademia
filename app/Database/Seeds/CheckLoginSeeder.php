<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\CurriculumStructureService;

class CheckLoginSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        $version = $db->table('curriculum_versions')->get()->getRowArray();
        if ($version) {
            $matrix = CurriculumStructureService::getMatrixView($version['id'], 1);
            echo "Matrix loaded successfully! Found " . count($matrix['grades']) . " grades and " . count($matrix['subjects']) . " subjects.\n";
        }
    }
}
