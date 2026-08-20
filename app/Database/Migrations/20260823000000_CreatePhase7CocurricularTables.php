<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 7 — Cocurricular & Character (Blueprint §7–8, §5, §6).
 *
 * Kokurikuler adalah bagian integral pembelajaran yang menguatkan,
 * memperdalam dan/atau memperkaya intrakurikuler serta mengembangkan
 * kompetensi dan karakter. Modul ini mendukung:
 *
 *   1. Program kokurikuler dengan klasifikasi per tujuan:
 *      COCURRICULAR / EXTRACURRICULAR / FIXED_SCHOOL_ACTIVITY /
 *      FORMATION / SERVICE (sekolah tidak perlu memaksakan semuanya
 *      menjadi mata pelajaran).
 *   2. Alur kerja: Need Analysis → Pilih Dimensi Profil Lulusan →
 *      Pilih Tema → Petakan Mapel/TP → Desain → Jadwal/Alokasi → Eksekusi
 *      → Monitoring Formatif → Bukti Sumatif → Laporan → Evaluasi → Tindak
 *      lanjut.
 *   3. Junction lintas-disiplin: dimensi profil lulusan, mapel, TP, guru,
 *      kelas, mitra, dan sumber daya (satu evidence dapat menyumbang ke
 *      beberapa alignment tanpa copy-paste).
 *   4. Asesmen formatif (jurnal, observasi, peer feedback, self assessment,
 *      refleksi) dan sumatif (performance, project, action, product,
 *      presentation, final reflection) berfokus pada pencapaian dimensi
 *      profil lulusan.
 *   5. Evaluasi model INPUT → PROCESS → OUTPUT → OUTCOME.
 *   6. Dukungan opsional Gerakan 7 Kebiasaan Anak Indonesia Hebat (7KAIH):
 *      definisi kebiasaan, tantangan mingguan, diary/check-in, monitoring
 *      guru, dan partisipasi orang tua. Opsional & dapat dikonfigurasi
 *      (feature flag ialos_7kahi).
 */
class CreatePhase7CocurricularTables extends Migration
{
    private array $permissions = [
        'cocurricular.view'   => ['module' => 'cocurricular', 'name' => 'View Cocurricular Programs'],
        'cocurricular.manage' => ['module' => 'cocurricular', 'name' => 'Manage Cocurricular Programs'],
    ];

    public function up(): void
    {
        $this->createPrograms();
        $this->createProgramDimensions();
        $this->createProgramSubjects();
        $this->createProgramObjectives();
        $this->createProgramTeachers();
        $this->createProgramClasses();
        $this->createProgramPartners();
        $this->createProgramResources();
        $this->createSessions();
        $this->createObservations();
        $this->createEvidences();
        $this->createStudentResults();
        $this->createEvaluations();
        $this->createHabits();
        $this->createHabitCheckins();
        $this->seedPermissions();
        $this->seedFeatureFlags();
    }

    public function down(): void
    {
        $this->removeFeatureFlags();
        $this->removePermissions();

        foreach ([
            'cocurricular_habit_checkins',
            'cocurricular_habits',
            'cocurricular_evaluations',
            'cocurricular_student_results',
            'cocurricular_evidences',
            'cocurricular_observations',
            'cocurricular_sessions',
            'cocurricular_program_resources',
            'cocurricular_program_partners',
            'cocurricular_program_classes',
            'cocurricular_program_teachers',
            'cocurricular_program_objectives',
            'cocurricular_program_subjects',
            'cocurricular_program_dimensions',
            'cocurricular_programs',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    // ----------------------------------------------------------------
    // Table Definitions
    // ----------------------------------------------------------------

    private function createPrograms(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'            => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'ksp_version_id'     => $this->bigInt(true),
            'code'               => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'program_type'       => ['type' => 'VARCHAR', 'constraint' => 25, 'default' => 'COCURRICULAR'],
            'theme'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'rationale'          => ['type' => 'TEXT', 'null' => true],
            'objective'          => ['type' => 'TEXT', 'null' => true],
            'annual_minutes'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'delivery_model'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PROJECT'],
            'start_date'         => ['type' => 'DATE', 'null' => true],
            'end_date'           => ['type' => 'DATE', 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'description'        => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['unit_id', 'academic_period_id', 'program_type']);
        $this->forge->addKey(['unit_id', 'status']);
        $this->forge->addKey('ksp_version_id');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('ksp_version_id', 'ksp_versions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_programs', true);
    }

    private function createProgramDimensions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'  => $this->bigInt(),
            'dimension_id'=> $this->bigInt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'dimension_id'], 'uq_cocurricular_dimension');
        $this->forge->addKey('dimension_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('dimension_id', 'graduate_profile_dimensions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_dimensions', true);
    }

    private function createProgramSubjects(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id' => $this->bigInt(),
            'subject_id' => $this->bigInt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'subject_id'], 'uq_cocurricular_subject');
        $this->forge->addKey('subject_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_subjects', true);
    }

    private function createProgramObjectives(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'            => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'learning_objective_id'], 'uq_cocurricular_objective');
        $this->forge->addKey('learning_objective_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('learning_objective_id', 'learning_objectives_tp', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_objectives', true);
    }

    private function createProgramTeachers(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id' => $this->bigInt(),
            'teacher_id' => $this->bigInt(),
            'role'       => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'FACILITATOR'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'teacher_id', 'role'], 'uq_cocurricular_teacher_role');
        $this->forge->addKey('teacher_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_teachers', true);
    }

    private function createProgramClasses(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'   => $this->bigInt(),
            'classroom_id' => $this->bigInt(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'classroom_id'], 'uq_cocurricular_class');
        $this->forge->addKey('classroom_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_classes', true);
    }

    private function createProgramPartners(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'    => $this->bigInt(),
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'partner_type'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'role'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'contact'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'name']);
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_partners', true);
    }

    private function createProgramResources(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'    => $this->bigInt(),
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'resource_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'quantity'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'name']);
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_program_resources', true);
    }

    private function createSessions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'    => $this->bigInt(),
            'classroom_id'  => $this->bigInt(true),
            'teacher_id'    => $this->bigInt(true),
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'session_date'  => ['type' => 'DATE'],
            'start_time'    => ['type' => 'TIME', 'null' => true],
            'end_time'      => ['type' => 'TIME', 'null' => true],
            'mode'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'WEEKLY'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PLAN'],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'executed_at'   => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'session_date']);
        $this->forge->addKey(['classroom_id', 'status']);
        $this->forge->addKey('teacher_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_sessions', true);
    }

    private function createObservations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'       => $this->bigInt(),
            'session_id'       => $this->bigInt(true),
            'student_id'       => $this->bigInt(),
            'teacher_id'       => $this->bigInt(),
            'observation_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'OBSERVATION'],
            'dimension_id'     => $this->bigInt(true),
            'notes'            => ['type' => 'TEXT'],
            'rating'           => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'observed_on'      => ['type' => 'DATE'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'observation_type']);
        $this->forge->addKey(['student_id', 'observed_on']);
        $this->forge->addKey('session_id');
        $this->forge->addKey('dimension_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('session_id', 'cocurricular_sessions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('dimension_id', 'graduate_profile_dimensions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_observations', true);
    }

    private function createEvidences(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'     => $this->bigInt(),
            'student_id'     => $this->bigInt(),
            'dimension_id'   => $this->bigInt(true),
            'title'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'evidence_type'  => ['type' => 'VARCHAR', 'constraint' => 25, 'default' => 'PROJECT'],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'file_path'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'meta_json'      => ['type' => 'TEXT', 'null' => true],
            'captured_at'    => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'evidence_type']);
        $this->forge->addKey(['student_id', 'captured_at']);
        $this->forge->addKey('dimension_id');
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('dimension_id', 'graduate_profile_dimensions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_evidences', true);
    }

    private function createStudentResults(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'   => $this->bigInt(),
            'student_id'   => $this->bigInt(),
            'dimension_id' => $this->bigInt(),
            'level'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DEVELOPING'],
            'note'         => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'student_id', 'dimension_id'], 'uq_cocurricular_student_dimension');
        $this->forge->addKey(['student_id', 'dimension_id']);
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('dimension_id', 'graduate_profile_dimensions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_student_results', true);
    }

    private function createEvaluations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id' => $this->bigInt(),
            'aspect'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'INPUT'],
            'indicator'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'finding'    => ['type' => 'TEXT', 'null' => true],
            'rating'     => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'aspect']);
        $this->forge->addForeignKey('program_id', 'cocurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cocurricular_evaluations', true);
    }

    private function createHabits(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'          => $this->bigInt(true),
            'code'             => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'icon'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'weekly_challenge' => ['type' => 'TEXT', 'null' => true],
            'sort_order'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'enabled'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['code', 'unit_id'], 'uq_cocurricular_habit_code');
        $this->forge->addKey(['unit_id', 'enabled', 'sort_order']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_habits', true);
    }

    private function createHabitCheckins(): void
    {
        $this->forge->addField($this->baseFields() + [
            'habit_id'      => $this->bigInt(),
            'student_id'    => $this->bigInt(),
            'checkin_week'  => ['type' => 'DATE'],
            'teacher_id'    => $this->bigInt(true),
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DONE'],
            'note'          => ['type' => 'TEXT', 'null' => true],
            'parent_note'   => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['habit_id', 'student_id', 'checkin_week'], 'uq_cocurricular_habit_checkin');
        $this->forge->addKey(['student_id', 'checkin_week']);
        $this->forge->addKey('teacher_id');
        $this->forge->addForeignKey('habit_id', 'cocurricular_habits', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('cocurricular_habit_checkins', true);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function baseFields(): array
    {
        return [
            'id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
        ];
    }

    private function auditFields(): array
    {
        return [
            'created_by'  => $this->int(true),
            'updated_by'  => $this->int(true),
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function bigInt(bool $nullable = false): array
    {
        return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
    }

    private function int(bool $nullable = false): array
    {
        return ['type' => 'INT', 'unsigned' => true, 'null' => $nullable];
    }

    // ----------------------------------------------------------------
    // Permission & Feature Flag Seeding
    // ----------------------------------------------------------------

    private function seedPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->permissions as $code => $definition) {
            if ($this->db->table('permissions')->where('code', $code)->countAllResults() === 0) {
                $this->db->table('permissions')->insert($definition + [
                    'code'        => $code,
                    'description' => $definition['name'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $allPerms   = array_keys($this->permissions);
        $roleMap    = [
            'super_admin'       => $allPerms,
            'wakasek_kurikulum' => $allPerms,
            'admin_smp'         => $allPerms,
            'admin_sma'         => $allPerms,
            'kepala_sekolah'    => ['cocurricular.view'],
        ];

        foreach ($roleMap as $roleCode => $codes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }

            foreach ($codes as $code) {
                $permission = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
                if (! $permission) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permission['id'])
                    ->countAllResults() > 0;
                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $role['id'],
                        'permission_id' => $permission['id'],
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        foreach (array_keys($this->permissions) as $code) {
            $perm = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if ($perm) {
                $this->db->table('role_permissions')->where('permission_id', $perm['id'])->delete();
                $this->db->table('permissions')->where('id', $perm['id'])->delete();
            }
        }
    }

    private function seedFeatureFlags(): void
    {
        if (! $this->db->tableExists('feature_flags')) {
            return;
        }

        $flags = [
            [
                'code'        => 'ialos_phase7_cocurricular',
                'name'        => 'Cocurricular & Character',
                'description' => 'Phase 7: program kokurikuler, asesmen dimensi profil lulusan, dan evaluasi.',
                'enabled'     => 1,
            ],
            [
                'code'        => 'ialos_7kahi',
                'name'        => 'Gerakan 7 Kebiasaan (7KAIH)',
                'description' => 'Dukungan opsional kebiasaan anak Indonesia hebat: definisi, tantangan mingguan, diary, monitoring guru, partisipasi orang tua.',
                'enabled'     => 0,
            ],
        ];

        foreach ($flags as $flag) {
            $exists = $this->db->table('feature_flags')->where('code', $flag['code'])->countAllResults() > 0;
            if (! $exists) {
                $flag['created_at'] = date('Y-m-d H:i:s');
                $flag['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('feature_flags')->insert($flag);
            }
        }
    }

    private function removeFeatureFlags(): void
    {
        if (! $this->db->tableExists('feature_flags')) {
            return;
        }

        $this->db->table('feature_flags')->whereIn('code', ['ialos_phase7_cocurricular', 'ialos_7kahi'])->delete();
    }
}