<?php

namespace App\Database\Seeds;

use App\Services\UuidService;
use CodeIgniter\Database\Seeder;

class EducationFoundationSeeder extends Seeder
{
    private const DIMENSIONS = [
        ['FAITH', 'Keimanan dan Ketakwaan terhadap Tuhan YME', 1],
        ['CITIZENSHIP', 'Kewargaan', 2],
        ['CRITICAL_REASONING', 'Penalaran Kritis', 3],
        ['CREATIVITY', 'Kreativitas', 4],
        ['COLLABORATION', 'Kolaborasi', 5],
        ['INDEPENDENCE', 'Kemandirian', 6],
        ['HEALTH', 'Kesehatan', 7],
        ['COMMUNICATION', 'Komunikasi', 8],
    ];

    private const PERMISSIONS = [
        'regulations.view' => 'Melihat registri regulasi', 'regulations.manage' => 'Mengelola registri regulasi',
        'curriculum_sources.view' => 'Melihat sumber kurikulum', 'curriculum_sources.manage' => 'Mengelola sumber kurikulum',
        'graduate_profile.view' => 'Melihat dimensi profil lulusan', 'graduate_profile.manage' => 'Mengelola dimensi profil lulusan',
        'learning_outcomes.view' => 'Melihat capaian pembelajaran', 'learning_outcomes.manage' => 'Mengelola capaian pembelajaran',
        'learning_objectives.view' => 'Melihat tujuan pembelajaran', 'learning_objectives.manage' => 'Mengelola tujuan pembelajaran',
        'learning_sequences.view' => 'Melihat alur tujuan pembelajaran', 'learning_sequences.manage' => 'Mengelola alur tujuan pembelajaran',
        'learning_sequences.validate' => 'Memvalidasi alur tujuan pembelajaran', 'learning_sequences.review' => 'Mereview alur tujuan pembelajaran',
        'learning_sequences.approve' => 'Menyetujui alur tujuan pembelajaran', 'learning_sequences.lock' => 'Mengunci alur tujuan pembelajaran',
        'learning_packs.view' => 'Melihat paket pembelajaran', 'learning_packs.manage' => 'Mengelola paket pembelajaran',
    ];

    public function run()
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::DIMENSIONS as [$code, $name, $sortOrder]) {
            $existing = $this->db->table('graduate_profile_dimensions')->where('code', $code)->get()->getRowArray();
            $data = ['name' => $name, 'sort_order' => $sortOrder, 'is_active' => 1, 'updated_at' => $now];
            if ($existing) {
                $this->db->table('graduate_profile_dimensions')->where('id', $existing['id'])->update($data);
            } else {
                $this->db->table('graduate_profile_dimensions')->insert($data + [
                    'uuid' => UuidService::v4(), 'code' => $code, 'created_at' => $now,
                ]);
            }
        }

        foreach (self::PERMISSIONS as $code => $description) {
            $data = ['module' => 'education_foundation', 'name' => ucwords(str_replace(['.', '_'], ' ', $code)), 'description' => $description, 'updated_at' => $now];
            $existing = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if ($existing) {
                $this->db->table('permissions')->where('id', $existing['id'])->update($data);
            } else {
                $this->db->table('permissions')->insert($data + ['code' => $code, 'created_at' => $now]);
            }
        }

        $all = array_keys(self::PERMISSIONS);
        $view = array_values(array_filter($all, static fn (string $code): bool => str_ends_with($code, '.view')));
        $manage = array_values(array_filter($all, static fn (string $code): bool => !in_array($code, ['learning_sequences.approve', 'learning_sequences.lock'], true)));
        $roleMap = [
            'super_admin' => $all,
            'kepala_sekolah' => array_merge($view, ['learning_sequences.review', 'learning_sequences.approve', 'learning_sequences.lock']),
            'wakasek_kurikulum' => $manage,
            'admin_smp' => $manage,
            'admin_sma' => $manage,
            'guru' => $view,
            'viewer_yayasan' => $view,
        ];
        $roles = array_column($this->db->table('roles')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->whereIn('code', $all)->get()->getResultArray(), 'id', 'code');
        foreach ($roleMap as $roleCode => $codes) {
            if (!isset($roles[$roleCode])) {
                continue;
            }
            foreach (array_unique($codes) as $code) {
                if (!isset($permissions[$code])) {
                    continue;
                }
                $key = ['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$code]];
                if ($this->db->table('role_permissions')->where($key)->countAllResults() === 0) {
                    $this->db->table('role_permissions')->insert($key + ['created_at' => $now]);
                }
            }
        }

        $flag = $this->db->table('feature_flags')->where('code', 'ialos_education_foundation')->get()->getRowArray();
        $flagData = ['name' => 'IALOS Education Foundation', 'description' => 'Fondasi regulasi, CP, TP, ATP, coverage, dan paket pembelajaran', 'enabled' => 1, 'updated_at' => $now];
        if ($flag) {
            $this->db->table('feature_flags')->where('id', $flag['id'])->update($flagData);
        } else {
            $this->db->table('feature_flags')->insert($flagData + ['code' => 'ialos_education_foundation', 'created_at' => $now]);
        }
    }
}
