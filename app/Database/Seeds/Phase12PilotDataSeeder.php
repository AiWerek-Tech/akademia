<?php

namespace App\Database\Seeds;

use App\Services\UuidService;
use CodeIgniter\Database\Seeder;

/**
 * Phase 12 — Real-World Pilot Data Seeder.
 *
 * Seeds comprehensive pilot data for:
 *   1. Informatika Fase E (Kelas X) curriculum package (CP -> TP -> Learning Pack).
 *   2. Deep Learning 3D Lesson Plan (RPP) in published state.
 *   3. Pathfinder Club extracurricular with competencies & attendance.
 *   4. Sample student mastery evidence & academic report snapshot.
 *   5. Teacher reflection, supervision record & mobile sync sessions.
 */
class Phase12PilotDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // 1. Ensure School Unit
        $unit = $this->db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        if (! $unit) {
            $this->db->table('school_units')->insert([
                'uuid'       => UuidService::v4(),
                'code'       => 'SMA',
                'name'       => 'SMA WMVAA Akademia',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $unitId = (int) $this->db->insertID();
        } else {
            $unitId = (int) $unit['id'];
        }

        // 2. Ensure Academic Year & Period
        $year = $this->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (! $year) {
            $this->db->table('academic_years')->insert([
                'uuid'       => UuidService::v4(),
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $yearId = (int) $this->db->insertID();
        } else {
            $yearId = (int) $year['id'];
        }

        $period = $this->db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (! $period) {
            $this->db->table('academic_periods')->insert([
                'uuid'             => UuidService::v4(),
                'academic_year_id' => $yearId,
                'semester_number'  => 1,
                'name'             => 'Ganjil 2026/2027',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $periodId = (int) $this->db->insertID();
        } else {
            $periodId = (int) $period['id'];
        }

        // 3. Ensure Grade Level
        $grade = $this->db->table('grade_levels')->where(['unit_id' => $unitId, 'code' => 'X'])->get()->getRowArray();
        if (! $grade) {
            $this->db->table('grade_levels')->insert([
                'uuid'        => UuidService::v4(),
                'unit_id'     => $unitId,
                'code'         => 'X',
                'name'         => 'Kelas 10',
                'grade_number' => 10,
                'sort_order'   => 10,
                'is_active'    => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $gradeId = (int) $this->db->insertID();
        } else {
            $gradeId = (int) $grade['id'];
        }

        // 4. Ensure Subject (Informatika)
        $subject = $this->db->table('subjects')->where('code', 'INF-X')->get()->getRowArray();
        if (! $subject) {
            $this->db->table('subjects')->insert([
                'uuid'            => UuidService::v4(),
                'code'            => 'INF-X',
                'name'            => 'Informatika Kelas X',
                'normalized_name' => 'INFORMATIKA KELAS X',
                'short_name'      => 'INF',
                'category'        => 'WAJIB',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $subjectId = (int) $this->db->insertID();
        } else {
            $subjectId = (int) $subject['id'];
        }

        // Ensure subject availability for unit
        if ($this->db->table('subject_unit_availability')->where(['subject_id' => $subjectId, 'unit_id' => $unitId])->countAllResults() === 0) {
            $this->db->table('subject_unit_availability')->insert([
                'subject_id'   => $subjectId,
                'unit_id'      => $unitId,
                'is_available' => 1,
            ]);
        }

        // 5. Ensure Teacher
        $teacher = $this->db->table('teachers')->where('email', 'guru.informatika@wmvaa.test')->get()->getRowArray();
        if (! $teacher) {
            $this->db->table('teachers')->insert([
                'uuid'               => UuidService::v4(),
                'primary_unit_id'    => $unitId,
                'full_name'          => 'Budi Pratama, M.Kom',
                'normalized_name'    => 'BUDI PRATAMA',
                'email'              => 'guru.informatika@wmvaa.test',
                'phone'              => '081234567890',
                'employment_status'  => 'TETAP',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $teacherId = (int) $this->db->insertID();
        } else {
            $teacherId = (int) $teacher['id'];
        }

        // 6. Ensure Classroom
        $classroom = $this->db->table('classrooms')->where(['unit_id' => $unitId, 'code' => 'X-A'])->get()->getRowArray();
        if (! $classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'                => UuidService::v4(),
                'academic_period_id'  => $periodId,
                'unit_id'             => $unitId,
                'grade_level_id'      => $gradeId,
                'homeroom_teacher_id' => $teacherId,
                'code'                => 'X-A',
                'name'                => 'Kelas X-A',
                'capacity'            => 32,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $classroomId = (int) $this->db->insertID();
        } else {
            $classroomId = (int) $classroom['id'];
        }

        // 7. Ensure Students
        $studentsData = [
            ['NIS26001', 'Ahmad Fauzi', 10],
            ['NIS26002', 'Siti Rahma Azzahra', 10],
            ['NIS26003', 'Daniel Wijaya', 10],
        ];

        $studentIds = [];
        foreach ($studentsData as [$nis, $name, $gradeNum]) {
            $std = $this->db->table('elective_students')->where(['unit_id' => $unitId, 'student_number' => $nis])->get()->getRowArray();
            if (! $std) {
                $this->db->table('elective_students')->insert([
                    'uuid'             => UuidService::v4(),
                    'unit_id'          => $unitId,
                    'academic_year_id' => $yearId,
                    'classroom_id'     => $classroomId,
                    'student_number'   => $nis,
                    'full_name'        => $name,
                    'current_grade'    => $gradeNum,
                    'is_active'        => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                $studentIds[] = (int) $this->db->insertID();
            } else {
                $studentIds[] = (int) $std['id'];
            }
        }

        // 8. Ensure Curriculum Version & CP
        $ver = $this->db->table('curriculum_versions')->where('code', 'IALOS-PILOT-X')->get()->getRowArray();
        if (! $ver) {
            $this->db->table('curriculum_versions')->insert([
                'uuid'               => UuidService::v4(),
                'academic_period_id' => $periodId,
                'code'               => 'IALOS-PILOT-X',
                'name'               => 'Kurikulum Merdeka Informatika Fase E',
                'revision_number'    => 1,
                'workflow_status'    => 'ACTIVE',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $versionId = (int) $this->db->insertID();
        } else {
            $versionId = (int) $ver['id'];
        }

        $cp = $this->db->table('learning_outcomes_cp')->where(['curriculum_version_id' => $versionId, 'code' => 'CP-INF-E-01'])->get()->getRowArray();
        if (! $cp) {
            $this->db->table('learning_outcomes_cp')->insert([
                'uuid'                  => UuidService::v4(),
                'curriculum_version_id' => $versionId,
                'subject_id'            => $subjectId,
                'grade_level_id'        => $gradeId,
                'code'                  => 'CP-INF-E-01',
                'phase'                 => 'E',
                'statement'             => 'Peserta didik mampu menerapkan strategi algoritmik standar untuk menghasilkan beberapa solusi persoalan.',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            $cpId = (int) $this->db->insertID();
        } else {
            $cpId = (int) $cp['id'];
        }

        // 9. Seed Learning Objective (TP)
        $tp = $this->db->table('learning_objectives_tp')->where(['learning_outcome_id' => $cpId, 'code' => 'TP-INF-X-01'])->get()->getRowArray();
        if (! $tp) {
            $this->db->table('learning_objectives_tp')->insert([
                'uuid'                => UuidService::v4(),
                'learning_outcome_id' => $cpId,
                'unit_id'             => $unitId,
                'source_level'        => 'SCHOOL',
                'code'                => 'TP-INF-X-01',
                'statement'           => 'Peserta didik mampu merancang dan menerapkan algoritma sekuensial dan percabangan dalam bahasa Python.',
                'rationale'           => 'Fondasi komputasi untuk pemecahan masalah praktis.',
                'status'              => 'APPROVED',
                'revision_number'     => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $tpId = (int) $this->db->insertID();
        } else {
            $tpId = (int) $tp['id'];
        }

        // 10. Seed Subject Learning Pack
        $pack = $this->db->table('subject_learning_packs')->where(['unit_id' => $unitId, 'subject_id' => $subjectId])->get()->getRowArray();
        if (! $pack) {
            $this->db->table('subject_learning_packs')->insert([
                'uuid'                  => UuidService::v4(),
                'curriculum_version_id' => $versionId,
                'unit_id'               => $unitId,
                'subject_id'            => $subjectId,
                'grade_level_id'        => $gradeId,
                'code'                  => 'LP-INF-X',
                'name'                  => 'Paket Belajar Informatika Kelas X — Berpikir Komputasional',
                'description'           => 'Paket kurikulum standar Informatika Fase E mencakup algoritma, pemrograman dasar, dan literasi data.',
                'status'                => 'PUBLISHED',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            $packId = (int) $this->db->insertID();
        } else {
            $packId = (int) $pack['id'];
        }

        // 11. Seed 3D Deep Learning Lesson Plan
        $lp = $this->db->table('lesson_plans')->where(['unit_id' => $unitId, 'session_label' => 'Modul Ajar: Algoritma Python Dasar'])->get()->getRowArray();
        if (! $lp) {
            $this->db->table('lesson_plans')->insert([
                'uuid'                 => UuidService::v4(),
                'unit_id'              => $unitId,
                'academic_period_id'   => $periodId,
                'subject_id'           => $subjectId,
                'grade_level_id'       => $gradeId,
                'class_id'             => $classroomId,
                'teacher_id'           => $teacherId,
                'learning_pack_id'     => $packId,
                'date'                 => date('Y-m-d'),
                'session_number'       => 1,
                'session_label'        => 'Modul Ajar: Algoritma Python Dasar',
                'source_type'          => 'CUSTOM',
                'identification_notes' => 'Pengenalan Algoritma & Flowchart dengan logika komputasi.',
                'pedagogical_practice' => 'Problem-Based Learning & Live Coding',
                'learning_environment' => 'Laboratorium Komputer & Google Colab',
                'digital_utilization'  => 'Google Colab Python 3, Visual Flowchart Simulator',
                'status'               => 'PUBLISHED',
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        // 12. Seed Student Mastery Records
        foreach ($studentIds as $idx => $sId) {
            $exists = $this->db->table('mastery_records')->where(['student_id' => $sId, 'learning_objective_id' => $tpId])->get()->getRowArray();
            if (! $exists) {
                $results = ['MASTERY', 'DEVELOPING', 'BEGINNING'];
                $this->db->table('mastery_records')->insert([
                    'uuid'                  => UuidService::v4(),
                    'student_id'            => $sId,
                    'learning_objective_id' => $tpId,
                    'result'                => $results[$idx % count($results)],
                    'source'                => 'ASSESSMENT',
                    'confidence'            => 90,
                    'notes'                 => 'Evaluasi formatif modul berpikir komputasional.',
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }
        }

        // 13. Seed Pathfinder Club Extracurricular
        $ekstra = $this->db->table('extracurricular_programs')->where(['unit_id' => $unitId, 'code' => 'PATHFINDER-01'])->get()->getRowArray();
        if (! $ekstra) {
            $this->db->table('extracurricular_programs')->insert([
                'uuid'               => UuidService::v4(),
                'unit_id'            => $unitId,
                'academic_period_id' => $periodId,
                'coach_teacher_id'   => $teacherId,
                'code'               => 'PATHFINDER-01',
                'title'              => 'Klub Kepanduan Pathfinder Club',
                'category'           => 'LEADERSHIP',
                'rationale'          => 'Pembinaan karakter kepemimpinan, kemandirian, dan ketangkasan alam terbuka.',
                'objective'          => 'Membentuk generasi muda berintegritas dan terampil dalam kepanduan.',
                'meeting_day'        => 'JUMAT',
                'meeting_time'       => '15:00 - 17:00',
                'location'           => 'Lapangan & Ruang Serbaguna',
                'max_members'        => 40,
                'status'             => 'ACTIVE',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $ekstraId = (int) $this->db->insertID();
        } else {
            $ekstraId = (int) $ekstra['id'];
        }

        // Seed members
        foreach ($studentIds as $sId) {
            $hasMember = $this->db->table('extracurricular_members')->where(['program_id' => $ekstraId, 'student_id' => $sId])->get()->getRowArray();
            if (! $hasMember) {
                $this->db->table('extracurricular_members')->insert([
                    'uuid'            => UuidService::v4(),
                    'program_id'      => $ekstraId,
                    'student_id'      => $sId,
                    'role'            => 'MEMBER',
                    'join_date'       => date('Y-m-d'),
                    'status'          => 'ACTIVE',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // 14. Seed Teacher Reflection
        $ref = $this->db->table('teacher_reflections')->where(['unit_id' => $unitId, 'teacher_id' => $teacherId])->get()->getRowArray();
        if (! $ref) {
            $this->db->table('teacher_reflections')->insert([
                'uuid'               => UuidService::v4(),
                'unit_id'            => $unitId,
                'academic_period_id' => $periodId,
                'teacher_id'         => $teacherId,
                'subject_id'         => $subjectId,
                'reflection_type'    => 'POST_LESSON',
                'what_went_well'     => 'Peserta didik sangat antusias saat mencoba live coding Python di Google Colab.',
                'what_to_improve'    => 'Perlu penambahan waktu latihan untuk siswa yang baru mengenal logika pemrograman.',
                'next_steps'         => 'Menyediakan modul remedial terpandu dan tantangan kode bertingkat.',
                'status'             => 'PUBLISHED',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }

        // 15. Seed Sync Version Registry (Phase 11)
        foreach (['users', 'guru', 'jadwal', 'pengumuman', 'refleksi', 'mastery', 'ekstrakurikuler', 'profil_sekolah'] as $tbl) {
            $v = $this->db->table('sync_version_registry')->where(['unit_id' => $unitId, 'table_name' => $tbl])->get()->getRowArray();
            if (! $v) {
                $this->db->table('sync_version_registry')->insert([
                    'uuid'            => UuidService::v4(),
                    'unit_id'         => $unitId,
                    'table_name'      => $tbl,
                    'current_version' => 1,
                    'last_updated_at' => $now,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }
}
