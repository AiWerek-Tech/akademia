<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAcademicPeriodWorkflowPermissions extends Migration
{
    private array $codes = [
        'academic_periods.validate',
        'academic_periods.review',
        'academic_periods.approve',
        'academic_periods.lock',
    ];

    public function up()
    {
        $rows = $this->db->table('permissions')->select('id')->whereIn('code', $this->codes)->get()->getResultArray();
        $ids = array_map('intval', array_column($rows, 'id'));
        if ($ids !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
            $this->db->table('permissions')->whereIn('id', $ids)->delete();
        }
    }

    public function down()
    {
        $definitions = [
            ['code' => 'academic_periods.validate', 'name' => 'Validate Academic Periods'],
            ['code' => 'academic_periods.review', 'name' => 'Review Academic Periods'],
            ['code' => 'academic_periods.approve', 'name' => 'Approve Academic Periods'],
            ['code' => 'academic_periods.lock', 'name' => 'Lock Academic Periods'],
        ];
        foreach ($definitions as $definition) {
            if (!$this->db->table('permissions')->where('code', $definition['code'])->get()->getRowArray()) {
                $this->db->table('permissions')->insert([
                    'code' => $definition['code'], 'module' => 'academic_periods',
                    'name' => $definition['name'], 'description' => 'Legacy academic period workflow permission',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
