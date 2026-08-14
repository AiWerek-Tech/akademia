<?php

namespace App\Controllers;

use App\Services\WaliKelasAccessService;
use App\Services\PortalUnitScopeService;
use Config\Database;

class HomeroomPortalController extends BaseController
{
    public function classroom()
    {
        if (!has_permission('class_students.view') || !is_wali_kelas()) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke kelas binaan.');
        }

        $teacherId = (int) (get_teacher_id() ?? 0);
        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId ?: null);
        $classroomId = WaliKelasAccessService::classroomId();
        if (!$classroomId) {
            return view('homeroom_portal/classroom', [
                'title'             => 'Kelas Binaan',
                'breadcrumb_active' => 'Portal Wali Kelas',
                'classroom'         => null,
                'students'          => [],
                'unitScope'         => $unitScope,
                'classroomFilteredOut' => false,
            ]);
        }

        $db = Database::connect();
        $classroom = $db->table('classrooms c')
            ->select('c.*, gl.grade_number, gl.name AS grade_name, su.name AS unit_name')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->join('school_units su', 'su.id = c.unit_id')
            ->where('c.id', $classroomId)
            ->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL')
            ->get()
            ->getRowArray();

        $students = [];
        $classroomFilteredOut = $classroom && !in_array((int) $classroom['unit_id'], $unitScope['unitIds'], true);
        if ($classroomFilteredOut) {
            $classroom = null;
        }
        if ($classroom) {
            $students = $db->table('elective_students')
                ->where('classroom_id', $classroomId)
                ->where('is_active', 1)
                ->orderBy('full_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('homeroom_portal/classroom', [
            'title'             => 'Kelas Binaan',
            'breadcrumb_active' => 'Portal Wali Kelas',
            'classroom'         => $classroom,
            'students'          => $students,
            'unitScope'         => $unitScope,
            'classroomFilteredOut' => $classroomFilteredOut,
        ]);
    }
}
