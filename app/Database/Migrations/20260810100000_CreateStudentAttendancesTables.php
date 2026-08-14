<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\RolePermissions;

class CreateStudentAttendancesTables extends Migration
{
    private const PERMISSION_DEFINITIONS = [
        [
            'code' => 'attendances.view',
            'module' => 'attendance',
            'name' => 'View Student Attendances',
            'description' => 'Melihat data presensi siswa dan statistik kehadiran',
        ],
        [
            'code' => 'attendances.record',
            'module' => 'attendance',
            'name' => 'Record Student Attendances',
            'description' => 'Menginput dan mengubah presensi siswa serta jurnal mengajar kelas',
        ],
        [
            'code' => 'attendances.admin',
            'module' => 'attendance',
            'name' => 'Manage Attendance Administration',
            'description' => 'Akses eksekutif superadmin untuk rekapitulasi, pengesahan jurnal, dan ekspor presensi',
        ],
        [
            'code' => 'teacher_attendance.view',
            'module' => 'teacher_portal',
            'name' => 'View Teacher Attendance Portal',
            'description' => 'Melihat dan mengelola absensi mata pelajaran pada portal guru',
        ],
    ];

    public function up()
    {
        // 1. Create attendance_sessions table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'subject_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'schedule_entry_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'attendance_date' => [
                'type' => 'DATE',
            ],
            'meeting_number' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 1,
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'topic' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'teaching_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'SUBMITTED',
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['unit_id', 'academic_period_id', 'classroom_id', 'attendance_date']);
        $this->forge->addKey('teacher_id');
        $this->forge->addKey('subject_id');
        $this->forge->createTable('attendance_sessions', true);

        // 2. Create student_attendances table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'session_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'student_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'HADIR',
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('session_id');
        $this->forge->addKey('student_id');
        $this->forge->addUniqueKey(['session_id', 'student_id']);
        $this->forge->createTable('student_attendances', true);

        // 3. Register Permissions & Assign Roles
        $now = date('Y-m-d H:i:s');
        foreach (self::PERMISSION_DEFINITIONS as $def) {
            if ($this->db->table('permissions')->where('code', $def['code'])->countAllResults() === 0) {
                $this->db->table('permissions')->insert($def + ['created_at' => $now]);
            }
        }

        $permissionRows = $this->db->table('permissions')
            ->select('id, code')
            ->whereIn('code', array_column(self::PERMISSION_DEFINITIONS, 'code'))
            ->get()->getResultArray();
        $permissionMap = array_column($permissionRows, 'id', 'code');

        $roleGrants = [
            'super_admin'       => ['attendances.view', 'attendances.record', 'attendances.admin', 'teacher_attendance.view'],
            'superadmin'        => ['attendances.view', 'attendances.record', 'attendances.admin', 'teacher_attendance.view'],
            'kepala_sekolah'    => ['attendances.view', 'attendances.admin'],
            'wakasek_kurikulum' => ['attendances.view', 'attendances.record', 'attendances.admin'],
            'admin_smp'         => ['attendances.view', 'attendances.record', 'attendances.admin'],
            'admin_sma'         => ['attendances.view', 'attendances.record', 'attendances.admin'],
            'tata_usaha'        => ['attendances.view', 'attendances.admin'],
            'viewer_yayasan'    => ['attendances.view'],
            'guru'              => ['attendances.view', 'attendances.record', 'teacher_attendance.view'],
            'wali_kelas'        => ['attendances.view', 'attendances.record', 'teacher_attendance.view'],
        ];

        foreach ($roleGrants as $roleCode => $codes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role) {
                continue;
            }
            foreach ($codes as $code) {
                if (!isset($permissionMap[$code])) {
                    continue;
                }
                $permId = (int) $permissionMap[$code];
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permId)
                    ->countAllResults() > 0;
                if (!$exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => (int) $role['id'],
                        'permission_id' => $permId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('student_attendances', true);
        $this->forge->dropTable('attendance_sessions', true);
    }
}
