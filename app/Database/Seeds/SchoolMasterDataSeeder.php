<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\UuidService;

class SchoolMasterDataSeeder extends Seeder
{
    private function generateUuid(): string
    {
        return UuidService::v4();
    }

    public function run()
    {
        $db = $this->db;

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        if (!$smp || !$sma) {
            echo "Unit SMP atau SMA tidak ditemukan. Jalankan CoreSeeder terlebih dahulu.\n";
            return;
        }

        // 1. Seed Academic Years
        $years = [
            ['name' => '2024/2025', 'start_date' => '2024-07-15', 'end_date' => '2025-06-21', 'is_active' => 0],
            ['name' => '2025/2026', 'start_date' => '2025-07-14', 'end_date' => '2026-06-20', 'is_active' => 1],
            ['name' => '2026/2027', 'start_date' => '2026-07-13', 'end_date' => '2027-06-19', 'is_active' => 0],
        ];

        $yearIds = [];
        foreach ($years as $y) {
            $existing = $db->table('academic_years')->where('name', $y['name'])->get()->getRowArray();
            if ($existing) {
                $db->table('academic_years')->where('id', $existing['id'])->update([
                    'start_date' => $y['start_date'],
                    'end_date'   => $y['end_date'],
                    'is_active'  => $y['is_active'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $yearIds[$y['name']] = (int)$existing['id'];
            } else {
                $y['uuid']       = $this->generateUuid();
                $y['created_at'] = date('Y-m-d H:i:s');
                $db->table('academic_years')->insert($y);
                $yearIds[$y['name']] = (int)$db->insertID();
            }
        }

        // 2. Seed Academic Periods
        $activePeriodId = null;
        if (isset($yearIds['2025/2026'])) {
            $periods = [
                ['academic_year_id' => $yearIds['2025/2026'], 'semester_number' => 1, 'name' => 'Semester Ganjil 2025/2026', 'start_date' => '2025-07-14', 'end_date' => '2025-12-20', 'is_active' => 1],
                ['academic_year_id' => $yearIds['2025/2026'], 'semester_number' => 2, 'name' => 'Semester Genap 2025/2026',  'start_date' => '2026-01-05', 'end_date' => '2026-06-20', 'is_active' => 0],
            ];

            foreach ($periods as $p) {
                $existing = $db->table('academic_periods')
                    ->where('academic_year_id', $p['academic_year_id'])
                    ->where('semester_number', $p['semester_number'])
                    ->get()->getRowArray();

                if ($existing) {
                    $db->table('academic_periods')->where('id', $existing['id'])->update([
                        'is_active'  => $p['is_active'],
                        'start_date' => $p['start_date'],
                        'end_date'   => $p['end_date'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    if ($p['is_active'] === 1) {
                        $activePeriodId = (int)$existing['id'];
                    }
                } else {
                    $p['uuid']       = $this->generateUuid();
                    $p['created_at'] = date('Y-m-d H:i:s');
                    $db->table('academic_periods')->insert($p);
                    $newId = (int)$db->insertID();
                    if ($p['is_active'] === 1) {
                        $activePeriodId = $newId;
                    }
                }
            }
        }

        // 3. Seed Teachers
        $teachersData = [
            ['nip' => '198001012005011001', 'full_name' => 'Drs. Yohanes Wibowo', 'email' => 'yohanes@sogokmo.sch.id', 'gender' => 'L', 'phone' => '081234567891'],
            ['nip' => '198502152008022002', 'full_name' => 'Dra. Maria Saragih', 'email' => 'maria@sogokmo.sch.id', 'gender' => 'P', 'phone' => '081234567892'],
            ['nip' => '198803202010031003', 'full_name' => 'Paulina Rumbewas, S.Pd.', 'email' => 'paulina@sogokmo.sch.id', 'gender' => 'P', 'phone' => '081234567893'],
            ['nip' => '199004252014041004', 'full_name' => 'Daniel Kogoya, S.Pd.', 'email' => 'daniel@sogokmo.sch.id', 'gender' => 'L', 'phone' => '081234567894'],
            ['nip' => '199205102018052005', 'full_name' => 'Esther Simanjuntak, S.Pd.', 'email' => 'esther@sogokmo.sch.id', 'gender' => 'P', 'phone' => '081234567895'],
            ['nip' => '199306122019061006', 'full_name' => 'Ruben Wenda, S.Pd.', 'email' => 'ruben@sogokmo.sch.id', 'gender' => 'L', 'phone' => '081234567896'],
            ['nip' => '199407182020072007', 'full_name' => 'Sarah Latuconsina, S.Pd.', 'email' => 'sarah@sogokmo.sch.id', 'gender' => 'P', 'phone' => '081234567897'],
            ['nip' => '199508222021081008', 'full_name' => 'Markus Tabuni, S.Pd.', 'email' => 'markus@sogokmo.sch.id', 'gender' => 'L', 'phone' => '081234567898'],
            ['nip' => '199609302022092009', 'full_name' => 'Grace Patty, S.Pd.', 'email' => 'grace@sogokmo.sch.id', 'gender' => 'P', 'phone' => '081234567899'],
            ['nip' => '199710052023101010', 'full_name' => 'Alexander Lumentut, S.Kom.', 'email' => 'alex@sogokmo.sch.id', 'gender' => 'L', 'phone' => '081234567800'],
        ];

        foreach ($teachersData as $t) {
            $existing = $db->table('teachers')->where('nip', $t['nip'])->get()->getRowArray();
            if (!$existing) {
                $t['uuid']              = $this->generateUuid();
                $t['employment_status'] = 'PNS';
                $t['employment_type']   = 'TETAP';
                $t['is_active']         = 1;
                $t['created_at']        = date('Y-m-d H:i:s');
                $db->table('teachers')->insert($t);
            }
        }

        // 4. Seed Subjects for SMP & SMA
        $subjectsData = [
            ['code' => 'PAI-SMP', 'name' => 'Pendidikan Agama & Budi Pekerti', 'short_name' => 'PAI', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 1],
            ['code' => 'PPN-SMP', 'name' => 'Pendidikan Pancasila', 'short_name' => 'PPN', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 2],
            ['code' => 'IND-SMP', 'name' => 'Bahasa Indonesia', 'short_name' => 'IND', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 3],
            ['code' => 'ING-SMP', 'name' => 'Bahasa Inggris', 'short_name' => 'ING', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 4],
            ['code' => 'MAT-SMP', 'name' => 'Matematika', 'short_name' => 'MAT', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 5],
            ['code' => 'IPA-SMP', 'name' => 'Ilmu Pengetahuan Alam (IPA)', 'short_name' => 'IPA', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 6],
            ['code' => 'IPS-SMP', 'name' => 'Ilmu Pengetahuan Sosial (IPS)', 'short_name' => 'IPS', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 7],
            ['code' => 'PJK-SMP', 'name' => 'Pendidikan Jasmani, Olahraga & Kesehatan', 'short_name' => 'PJK', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 8],
            ['code' => 'INF-SMP', 'name' => 'Informatika', 'short_name' => 'INF', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 9],
            ['code' => 'SNB-SMP', 'name' => 'Seni & Budaya', 'short_name' => 'SNB', 'category' => 'WAJIB', 'unit_id' => $smp['id'], 'sort_order' => 10],

            ['code' => 'PAI-SMA', 'name' => 'Pendidikan Agama & Budi Pekerti', 'short_name' => 'PAI', 'category' => 'WAJIB', 'unit_id' => $sma['id'], 'sort_order' => 1],
            ['code' => 'PPN-SMA', 'name' => 'Pendidikan Pancasila', 'short_name' => 'PPN', 'category' => 'WAJIB', 'unit_id' => $sma['id'], 'sort_order' => 2],
            ['code' => 'IND-SMA', 'name' => 'Bahasa Indonesia', 'short_name' => 'IND', 'category' => 'WAJIB', 'unit_id' => $sma['id'], 'sort_order' => 3],
            ['code' => 'ING-SMA', 'name' => 'Bahasa Inggris', 'short_name' => 'ING', 'category' => 'WAJIB', 'unit_id' => $sma['id'], 'sort_order' => 4],
            ['code' => 'MAT-SMA', 'name' => 'Matematika Umum', 'short_name' => 'MAT', 'category' => 'WAJIB', 'unit_id' => $sma['id'], 'sort_order' => 5],
            ['code' => 'FIS-SMA', 'name' => 'Fisika', 'short_name' => 'FIS', 'category' => 'PILIHAN', 'unit_id' => $sma['id'], 'sort_order' => 6],
            ['code' => 'KIM-SMA', 'name' => 'Kimia', 'short_name' => 'KIM', 'category' => 'PILIHAN', 'unit_id' => $sma['id'], 'sort_order' => 7],
            ['code' => 'BIO-SMA', 'name' => 'Biologi', 'short_name' => 'BIO', 'category' => 'PILIHAN', 'unit_id' => $sma['id'], 'sort_order' => 8],
            ['code' => 'EKO-SMA', 'name' => 'Ekonomi', 'short_name' => 'EKO', 'category' => 'PILIHAN', 'unit_id' => $sma['id'], 'sort_order' => 9],
            ['code' => 'GEO-SMA', 'name' => 'Geografi', 'short_name' => 'GEO', 'category' => 'PILIHAN', 'unit_id' => $sma['id'], 'sort_order' => 10],
        ];

        foreach ($subjectsData as $sub) {
            $existing = $db->table('subjects')->where('code', $sub['code'])->get()->getRowArray();
            $subjectId = null;

            if ($existing) {
                $subjectId = $existing['id'];
            } else {
                $subjectData = [
                    'uuid'            => $this->generateUuid(),
                    'code'            => $sub['code'],
                    'name'            => $sub['name'],
                    'normalized_name' => strtolower($sub['name']),
                    'short_name'      => $sub['short_name'],
                    'category'        => $sub['category'],
                    'is_active'       => 1,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                $db->table('subjects')->insert($subjectData);
                $subjectId = $db->insertID();
            }

            if ($subjectId) {
                $avail = $db->table('subject_unit_availability')
                    ->where('subject_id', $subjectId)
                    ->where('unit_id', $sub['unit_id'])
                    ->get()->getRowArray();

                if (!$avail) {
                    $db->table('subject_unit_availability')->insert([
                        'subject_id'       => $subjectId,
                        'unit_id'          => $sub['unit_id'],
                        'is_available'     => 1,
                        'default_category' => $sub['category'],
                        'sort_order'       => $sub['sort_order'],
                        'created_at'       => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        // 5. Seed Rooms
        $roomTypeClass = $db->table('room_types')->where('code', 'CLASSROOM')->get()->getRowArray();
        $roomTypeId = $roomTypeClass ? $roomTypeClass['id'] : 1;

        $roomsData = [
            ['code' => 'R-VII-A', 'name' => 'Ruang Kelas VII A', 'unit_id' => $smp['id'], 'capacity' => 32],
            ['code' => 'R-VII-B', 'name' => 'Ruang Kelas VII B', 'unit_id' => $smp['id'], 'capacity' => 32],
            ['code' => 'R-VIII-A', 'name' => 'Ruang Kelas VIII A', 'unit_id' => $smp['id'], 'capacity' => 32],
            ['code' => 'R-VIII-B', 'name' => 'Ruang Kelas VIII B', 'unit_id' => $smp['id'], 'capacity' => 32],
            ['code' => 'R-IX-A', 'name' => 'Ruang Kelas IX A', 'unit_id' => $smp['id'], 'capacity' => 32],
            ['code' => 'R-IX-B', 'name' => 'Ruang Kelas IX B', 'unit_id' => $smp['id'], 'capacity' => 32],

            ['code' => 'R-X-IPA', 'name' => 'Ruang Kelas X IPA', 'unit_id' => $sma['id'], 'capacity' => 36],
            ['code' => 'R-X-IPS', 'name' => 'Ruang Kelas X IPS', 'unit_id' => $sma['id'], 'capacity' => 36],
            ['code' => 'R-XI-IPA', 'name' => 'Ruang Kelas XI IPA', 'unit_id' => $sma['id'], 'capacity' => 36],
            ['code' => 'R-XI-IPS', 'name' => 'Ruang Kelas XI IPS', 'unit_id' => $sma['id'], 'capacity' => 36],
            ['code' => 'R-XII-IPA', 'name' => 'Ruang Kelas XII IPA', 'unit_id' => $sma['id'], 'capacity' => 36],
            ['code' => 'R-XII-IPS', 'name' => 'Ruang Kelas XII IPS', 'unit_id' => $sma['id'], 'capacity' => 36],
        ];

        $roomIds = [];
        foreach ($roomsData as $rm) {
            $existing = $db->table('rooms')->where('code', $rm['code'])->get()->getRowArray();
            if ($existing) {
                $roomIds[$rm['code']] = $existing['id'];
            } else {
                $rm['uuid']         = $this->generateUuid();
                $rm['room_type_id'] = $roomTypeId;
                $rm['is_active']    = 1;
                $rm['created_at']   = date('Y-m-d H:i:s');
                $db->table('rooms')->insert($rm);
                $roomIds[$rm['code']] = $db->insertID();
            }
        }

        // 6. Seed Classrooms (Rombel)
        if ($activePeriodId) {
            $grades = $db->table('grade_levels')->get()->getResultArray();
            $gradeMap = [];
            foreach ($grades as $g) {
                $gradeMap[$g['code']] = $g['id'];
            }

            $classroomsData = [
                ['code' => 'VII-A', 'name' => 'Kelas VII A', 'unit_id' => $smp['id'], 'grade_code' => 'VII', 'room_code' => 'R-VII-A'],
                ['code' => 'VII-B', 'name' => 'Kelas VII B', 'unit_id' => $smp['id'], 'grade_code' => 'VII', 'room_code' => 'R-VII-B'],
                ['code' => 'VIII-A', 'name' => 'Kelas VIII A', 'unit_id' => $smp['id'], 'grade_code' => 'VIII', 'room_code' => 'R-VIII-A'],
                ['code' => 'VIII-B', 'name' => 'Kelas VIII B', 'unit_id' => $smp['id'], 'grade_code' => 'VIII', 'room_code' => 'R-VIII-B'],
                ['code' => 'IX-A', 'name' => 'Kelas IX A', 'unit_id' => $smp['id'], 'grade_code' => 'IX', 'room_code' => 'R-IX-A'],
                ['code' => 'IX-B', 'name' => 'Kelas IX B', 'unit_id' => $smp['id'], 'grade_code' => 'IX', 'room_code' => 'R-IX-B'],

                ['code' => 'X-IPA', 'name' => 'Kelas X IPA', 'unit_id' => $sma['id'], 'grade_code' => 'X', 'room_code' => 'R-X-IPA'],
                ['code' => 'X-IPS', 'name' => 'Kelas X IPS', 'unit_id' => $sma['id'], 'grade_code' => 'X', 'room_code' => 'R-X-IPS'],
                ['code' => 'XI-IPA', 'name' => 'Kelas XI IPA', 'unit_id' => $sma['id'], 'grade_code' => 'XI', 'room_code' => 'R-XI-IPA'],
                ['code' => 'XI-IPS', 'name' => 'Kelas XI IPS', 'unit_id' => $sma['id'], 'grade_code' => 'XI', 'room_code' => 'R-XI-IPS'],
                ['code' => 'XII-IPA', 'name' => 'Kelas XII IPA', 'unit_id' => $sma['id'], 'grade_code' => 'XII', 'room_code' => 'R-XII-IPA'],
                ['code' => 'XII-IPS', 'name' => 'Kelas XII IPS', 'unit_id' => $sma['id'], 'grade_code' => 'XII', 'room_code' => 'R-XII-IPS'],
            ];

            foreach ($classroomsData as $cr) {
                $gradeId = $gradeMap[$cr['grade_code']] ?? null;
                $roomId  = $roomIds[$cr['room_code']] ?? null;

                if ($gradeId) {
                    $existing = $db->table('classrooms')
                        ->where('unit_id', $cr['unit_id'])
                        ->where('academic_period_id', $activePeriodId)
                        ->where('code', $cr['code'])
                        ->get()->getRowArray();

                    if (!$existing) {
                        $db->table('classrooms')->insert([
                            'uuid'               => $this->generateUuid(),
                            'unit_id'            => $cr['unit_id'],
                            'academic_period_id' => $activePeriodId,
                            'grade_level_id'     => $gradeId,
                            'default_room_id'    => $roomId,
                            'code'               => $cr['code'],
                            'name'               => $cr['name'],
                            'capacity'           => 32,
                            'is_active'          => 1,
                            'created_at'         => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        echo "School Master Data Seeded Successfully!\n";
    }
}
