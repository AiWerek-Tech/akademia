<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillPersonalRoleEntityLinks extends Migration
{
    public function up()
    {
        $waliUsers = $this->db->table('users u')
            ->distinct()->select('u.id, u.classroom_id, u.email')
            ->join('user_roles ur', 'ur.user_id = u.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('r.code', 'wali_kelas')->where('u.teacher_id IS NULL')
            ->where('u.deleted_at IS NULL')->get()->getResultArray();

        foreach ($waliUsers as $user) {
            $teacherId = 0;
            if (!empty($user['classroom_id'])) {
                $classroom = $this->db->table('classrooms')->select('homeroom_teacher_id')
                    ->where('id', $user['classroom_id'])->where('deleted_at IS NULL')->get()->getRowArray();
                $teacherId = (int) ($classroom['homeroom_teacher_id'] ?? 0);
            }
            if ($teacherId <= 0 && !empty($user['email'])) {
                $matches = $this->db->table('teachers')->select('id')->where('email', $user['email'])
                    ->where('deleted_at IS NULL')->limit(2)->get()->getResultArray();
                if (count($matches) === 1) {
                    $teacherId = (int) $matches[0]['id'];
                }
            }
            if ($teacherId > 0) {
                $this->db->table('users')->where('id', $user['id'])->update(['teacher_id' => $teacherId]);
            }
        }
    }

    public function down()
    {
        // Data linkage is intentionally retained; removing a verified relation
        // during rollback would make existing user accounts incomplete again.
    }
}
