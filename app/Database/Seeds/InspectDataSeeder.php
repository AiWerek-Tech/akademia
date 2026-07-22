<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InspectDataSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $tables = [
            'school_units', 'academic_years', 'academic_periods',
            'teachers', 'subjects', 'grade_levels', 'classrooms', 'rooms',
            'curriculum_versions', 'curriculum_structures', 'roles', 'permissions', 'role_permissions'
        ];

        echo "=== REKAP DATA DATABASE WMVAA AKADEMIA ===\n";
        foreach ($tables as $table) {
            $count = $db->table($table)->countAllResults();
            echo "Tabel {$table}: {$count} baris\n";
        }
    }
}
