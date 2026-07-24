<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\UuidService;

class CurriculumDataSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $activePeriod = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();

        if (!$activePeriod) {
            return;
        }

        $existing = $db->table('curriculum_versions')
            ->where('academic_period_id', $activePeriod['id'])
            ->get()->getRowArray();

        if (!$existing) {
            $db->table('curriculum_versions')->insert([
                'uuid'               => UuidService::v4(),
                'academic_period_id' => $activePeriod['id'],
                'code'               => 'KM-SOGOKMO-2025',
                'name'               => 'Kurikulum Merdeka Advent Sogokmo T.A 2025/2026',
                'description'        => 'Struktur Kurikulum Merdeka SMP-SMA Advent Sogokmo T.A 2025/2026',
                'workflow_status'    => 'APPROVED',
                'is_active'          => 1,
                'created_at'         => date('Y-m-d H:i:s')
            ]);
            echo "Curriculum Version Seeded Successfully!\n";
        }
    }
}
