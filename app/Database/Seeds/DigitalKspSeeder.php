<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DigitalKspSeeder extends Seeder
{
    private const PERMISSIONS = [
        'ksp.view' => 'Melihat Digital KSP',
        'ksp.manage' => 'Menyusun dan mengubah draft Digital KSP',
        'ksp.review' => 'Melakukan review Digital KSP',
        'ksp.approve' => 'Menyetujui Digital KSP',
        'ksp.lock' => 'Mengunci dan supersede Digital KSP',
        'ksp.export' => 'Mengekspor dokumen Digital KSP',
    ];

    public function run()
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::PERMISSIONS as $code => $description) {
            $row = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            $data = ['module'=>'ksp','name'=>ucwords(str_replace(['.','_'],' ',$code)),'description'=>$description,'updated_at'=>$now];
            if ($row) $this->db->table('permissions')->where('id',$row['id'])->update($data);
            else $this->db->table('permissions')->insert($data + ['code'=>$code,'created_at'=>$now]);
        }

        $permissionIds = array_column($this->db->table('permissions')->where('module','ksp')->get()->getResultArray(),'id','code');
        $roleMap = [
            'super_admin'=>array_keys(self::PERMISSIONS),
            'kepala_sekolah'=>['ksp.view','ksp.review','ksp.approve','ksp.lock','ksp.export'],
            'wakasek_kurikulum'=>['ksp.view','ksp.manage','ksp.review','ksp.export'],
            'admin_smp'=>['ksp.view','ksp.manage','ksp.export'],
            'admin_sma'=>['ksp.view','ksp.manage','ksp.export'],
            'guru'=>['ksp.view'],
            'viewer_yayasan'=>['ksp.view','ksp.export'],
        ];
        $roles = array_column($this->db->table('roles')->get()->getResultArray(),'id','code');
        foreach ($roleMap as $roleCode=>$codes) {
            if (!isset($roles[$roleCode])) continue;
            foreach ($codes as $code) {
                if (!isset($permissionIds[$code])) continue;
                $key=['role_id'=>$roles[$roleCode],'permission_id'=>$permissionIds[$code]];
                if ($this->db->table('role_permissions')->where($key)->countAllResults()===0) $this->db->table('role_permissions')->insert($key + ['created_at'=>$now]);
            }
        }
    }
}
