<?php

namespace App\Controllers;

use App\Models\AssignmentVersionModel;
use App\Services\AssignmentDocumentService;
use App\Services\UnitScopeService;

class AssignmentDocumentsController extends BaseController
{
    public function collective(string $uuid)
    {
        if (! has_permission('assignments.export')) {
            return redirect()->to('/assignments')->with('error', 'Anda tidak memiliki hak akses untuk mencetak dokumen.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            $document = AssignmentDocumentService::build($uuid, $unitId);
            return view('assignments/documents/collective', ['document' => $document]);
        } catch (\Throwable $e) {
            return redirect()->to('/assignments/' . $uuid)->with('error', $e->getMessage());
        }
    }

    public function teacher(string $uuid, int $teacherId)
    {
        if (! has_permission('assignments.export')) {
            return redirect()->to('/assignments')->with('error', 'Anda tidak memiliki hak akses untuk mencetak dokumen.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            UnitScopeService::assertTeacherInUnit($teacherId, $unitId);
            $document = AssignmentDocumentService::build($uuid, $unitId, $teacherId);
            if ($document['teachers'] === []) {
                throw new \RuntimeException('Guru belum memiliki penugasan pada versi dan unit ini.');
            }
            return view('assignments/documents/teacher', ['document' => $document]);
        } catch (\Throwable $e) {
            return redirect()->to('/assignments/' . $uuid)->with('error', $e->getMessage());
        }
    }
}
