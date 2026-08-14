<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicCalendarTables extends Migration
{
    private const PERMISSION_DEFINITIONS = [
        [
            'code'        => 'academic_calendar.view',
            'module'      => 'academic_calendar',
            'name'        => 'View Academic Calendar',
            'description' => 'Melihat kalender pendidikan dan data hari efektif',
        ],
        [
            'code'        => 'academic_calendar.manage',
            'module'      => 'academic_calendar',
            'name'        => 'Manage Academic Calendar',
            'description' => 'Membuat, mengedit, dan mengaktifkan kalender pendidikan',
        ],
    ];

    public function up()
    {
        // ──────────────────────────────────────────────
        // 1. academic_calendar_event_types (lookup / legend)
        // ──────────────────────────────────────────────
        $this->forge->addField([
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'bg_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#ffffff',
            ],
            'text_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#000000',
            ],
            'is_school_effective' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'is_learning_effective' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'UMUM',
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('code', true);
        $this->forge->createTable('academic_calendar_event_types', true);

        // Seed default event types
        $this->seedEventTypes();

        // ──────────────────────────────────────────────
        // 2. academic_calendars (header per year × unit)
        // ──────────────────────────────────────────────
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
            'academic_year_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'dinas_reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'dinas_reference_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'DRAFT',
            ],
            'total_hes_sem1' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'total_hes_sem2' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'total_heb_sem1' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'total_heb_sem2' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'total_effective_weeks_sem1' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'total_effective_weeks_sem2' => [
                'type'    => 'INT',
                'default' => 0,
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['academic_year_id', 'unit_id']);
        $this->forge->createTable('academic_calendars', true);

        // ──────────────────────────────────────────────
        // 3. academic_calendar_days (one row per date)
        // ──────────────────────────────────────────────
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'calendar_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'comment'    => '1=Mon … 7=Sun',
            ],
            'day_type_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'HEB',
            ],
            'is_school_effective' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_learning_effective' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'event_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'custom_bg_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
            'custom_text_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['calendar_id', 'date']);
        $this->forge->addKey('day_type_code');
        $this->forge->createTable('academic_calendar_days', true);

        // ──────────────────────────────────────────────
        // 4. academic_calendar_events (programs / events list)
        // ──────────────────────────────────────────────
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'calendar_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'PROGRAM_SEKOLAH',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('calendar_id');
        $this->forge->createTable('academic_calendar_events', true);

        // ──────────────────────────────────────────────
        // 5. Register permissions & role grants
        // ──────────────────────────────────────────────
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
            'super_admin'       => ['academic_calendar.view', 'academic_calendar.manage'],
            'superadmin'        => ['academic_calendar.view', 'academic_calendar.manage'],
            'kepala_sekolah'    => ['academic_calendar.view', 'academic_calendar.manage'],
            'wakasek_kurikulum' => ['academic_calendar.view', 'academic_calendar.manage'],
            'admin_smp'         => ['academic_calendar.view', 'academic_calendar.manage'],
            'admin_sma'         => ['academic_calendar.view', 'academic_calendar.manage'],
            'tata_usaha'        => ['academic_calendar.view'],
            'viewer_yayasan'    => ['academic_calendar.view'],
            'guru'              => ['academic_calendar.view'],
            'wali_kelas'        => ['academic_calendar.view'],
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
        $this->forge->dropTable('academic_calendar_events', true);
        $this->forge->dropTable('academic_calendar_days', true);
        $this->forge->dropTable('academic_calendars', true);
        $this->forge->dropTable('academic_calendar_event_types', true);
    }

    // ──────────────────────────────────────────────────
    // Seed the standard event-type lookup rows
    // ──────────────────────────────────────────────────
    private function seedEventTypes(): void
    {
        $types = [
            ['code' => 'HEB',   'name' => 'Hari Efektif Belajar',          'bg_color' => '#ffffff', 'text_color' => '#000000', 'is_school_effective' => 1, 'is_learning_effective' => 1, 'category' => 'BELAJAR',           'sort_order' => 1],
            ['code' => 'SABAT', 'name' => 'Hari Sabtu (Sabat)',            'bg_color' => '#1e293b', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'LIBUR',             'sort_order' => 2],
            ['code' => 'MINGGU','name' => 'Hari Minggu',                   'bg_color' => '#94a3b8', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'LIBUR',             'sort_order' => 3],
            ['code' => 'LU',    'name' => 'Libur Umum / Hari Besar',       'bg_color' => '#ef4444', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'HARI_LIBUR',        'sort_order' => 4],
            ['code' => 'CB',    'name' => 'Cuti Bersama',                  'bg_color' => '#f97316', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'HARI_LIBUR',        'sort_order' => 5],
            ['code' => 'LS1',   'name' => 'Libur Semester 1',              'bg_color' => '#a855f7', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'LIBUR',             'sort_order' => 6],
            ['code' => 'LS2',   'name' => 'Libur Semester 2',              'bg_color' => '#8b5cf6', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'LIBUR',             'sort_order' => 7],
            ['code' => 'MPLS',  'name' => 'Masa Pengenalan Lingk. Sekolah','bg_color' => '#06b6d4', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 8],
            ['code' => 'PTS',   'name' => 'Penilaian Tengah Semester',     'bg_color' => '#3b82f6', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'UJIAN',             'sort_order' => 9],
            ['code' => 'PAS',   'name' => 'Penilaian Akhir Semester',      'bg_color' => '#10b981', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'UJIAN',             'sort_order' => 10],
            ['code' => 'US',    'name' => 'Ujian Akhir Sekolah',           'bg_color' => '#14b8a6', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'UJIAN',             'sort_order' => 11],
            ['code' => 'AN',    'name' => 'Asesmen Nasional (ANBK)',       'bg_color' => '#0ea5e9', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'UJIAN',             'sort_order' => 12],
            ['code' => 'R1',    'name' => 'Penerimaan Rapor Semester 1',   'bg_color' => '#eab308', 'text_color' => '#000000', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 13],
            ['code' => 'R2',    'name' => 'Penerimaan Rapor Semester 2',   'bg_color' => '#eab308', 'text_color' => '#000000', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 14],
            ['code' => 'PT',    'name' => 'Teacher\'s Prime Time',         'bg_color' => '#f59e0b', 'text_color' => '#000000', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_ADVENT',   'sort_order' => 15],
            ['code' => 'DOA10', 'name' => '10 Hari Berdoa',                'bg_color' => '#d946ef', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 1, 'category' => 'KEGIATAN_ADVENT',   'sort_order' => 16],
            ['code' => 'SABPD', 'name' => 'Sabat Pendidikan',              'bg_color' => '#6366f1', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_ADVENT',   'sort_order' => 17],
            ['code' => 'SABOS', 'name' => 'Sabat OSIS',                    'bg_color' => '#818cf8', 'text_color' => '#ffffff', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_ADVENT',   'sort_order' => 18],
            ['code' => 'MPLY',  'name' => 'Minggu Pelayanan',              'bg_color' => '#22d3ee', 'text_color' => '#000000', 'is_school_effective' => 0, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_ADVENT',   'sort_order' => 19],
            ['code' => 'SUPV',  'name' => 'Supervisi Guru',                'bg_color' => '#fb923c', 'text_color' => '#000000', 'is_school_effective' => 1, 'is_learning_effective' => 1, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 20],
            ['code' => 'P',     'name' => 'Penamatan (Graduation)',        'bg_color' => '#92400e', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 21],
            ['code' => 'PDSG',  'name' => 'Penyusunan Dokumen Sekolah/Guru','bg_color' => '#64748b', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH', 'sort_order' => 22],
            ['code' => 'PLENO', 'name' => 'Pleno / Rapat Penilaian',      'bg_color' => '#78716c', 'text_color' => '#ffffff', 'is_school_effective' => 1, 'is_learning_effective' => 0, 'category' => 'KEGIATAN_SEKOLAH',  'sort_order' => 23],
        ];

        foreach ($types as $type) {
            $this->db->table('academic_calendar_event_types')->insert($type);
        }
    }
}
