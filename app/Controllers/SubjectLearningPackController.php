<?php

namespace App\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Services\LearningPackImportService;
use App\Services\SubjectLearningPackEngineService;
use App\Services\UnitScopeService;
use Config\Database;

class SubjectLearningPackController extends BaseController
{
    public function detail(string $uuid) { return $this->page($uuid, 'overview'); }
    public function units(string $uuid) { return $this->page($uuid, 'units'); }
    public function concepts(string $uuid) { return $this->page($uuid, 'concepts'); }
    public function activities(string $uuid) { return $this->page($uuid, 'activities'); }
    public function resources(string $uuid) { return $this->page($uuid, 'resources'); }
    public function assessment(string $uuid) { return $this->page($uuid, 'assessment'); }
    public function followup(string $uuid) { return $this->page($uuid, 'followup'); }
    public function coverage(string $uuid) { return $this->page($uuid, 'coverage'); }
    public function lineage(string $uuid) { return $this->page($uuid, 'lineage'); }
    public function history(string $uuid) { return $this->page($uuid, 'history'); }

    public function storeUnit(string $uuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::createUnit($uuid, $this->request->getPost()), $uuid, 'units', 'Unit berhasil ditambahkan.');
    }

    public function updateUnit(string $uuid, string $unitUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::updateUnit($unitUuid, $this->request->getPost()), $uuid, 'units', 'Unit berhasil diperbarui.');
    }

    public function deleteUnit(string $uuid, string $unitUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteUnit($unitUuid), $uuid, 'units', 'Unit berhasil dihapus.');
    }

    public function storeConcept(string $uuid)
    {
        $data = $this->request->getPost();
        return $this->run(fn () => SubjectLearningPackEngineService::createConcept($uuid, $data), $uuid, 'concepts', 'Konsep berhasil ditambahkan.');
    }

    public function updateConcept(string $uuid, string $conceptUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::updateConcept($conceptUuid, $this->request->getPost()), $uuid, 'concepts', 'Konsep berhasil diperbarui.');
    }

    public function deleteConcept(string $uuid, string $conceptUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteConcept($conceptUuid), $uuid, 'concepts', 'Konsep berhasil dihapus.');
    }

    public function storeResource(string $uuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::createResource($uuid, $this->request->getPost()), $uuid, 'resources', 'Resource berhasil ditambahkan.');
    }

    public function updateResource(string $uuid, string $resourceUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::updateResource($resourceUuid, $this->request->getPost()), $uuid, 'resources', 'Resource berhasil diperbarui.');
    }

    public function deleteResource(string $uuid, string $resourceUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteResource($resourceUuid), $uuid, 'resources', 'Resource berhasil dihapus.');
    }

    public function storeActivity(string $uuid)
    {
        $unitUuid = (string) $this->request->getPost('learning_unit_uuid');
        return $this->run(fn () => SubjectLearningPackEngineService::createActivity($unitUuid, $this->request->getPost()), $uuid, 'activities', 'Aktivitas berhasil ditambahkan.');
    }

    public function updateActivity(string $uuid, string $activityUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::updateActivity($activityUuid, $this->request->getPost()), $uuid, 'activities', 'Aktivitas berhasil diperbarui.');
    }

    public function deleteActivity(string $uuid, string $activityUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteActivity($activityUuid), $uuid, 'activities', 'Aktivitas berhasil dihapus.');
    }

    public function attachActivityResource(string $uuid, string $activityUuid)
    {
        $resourceUuid = (string) $this->request->getPost('resource_uuid');
        return $this->run(fn () => SubjectLearningPackEngineService::attachActivityResource($activityUuid, $resourceUuid, $this->request->getPost()), $uuid, 'activities', 'Resource berhasil ditautkan ke aktivitas.');
    }

    public function detachActivityResource(string $uuid, string $activityUuid, string $resourceUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::detachActivityResource($activityUuid, $resourceUuid), $uuid, 'activities', 'Resource berhasil dilepas dari aktivitas.');
    }

    public function addActivityAlternative(string $uuid, string $activityUuid)
    {
        $groupUuid = (string) ($this->request->getPost('group_uuid') ?: \App\Services\UuidService::v4());
        return $this->run(fn () => SubjectLearningPackEngineService::addAlternative($groupUuid, $activityUuid, $this->request->getPost()), $uuid, 'activities', 'Alternatif aktivitas berhasil ditambahkan.');
    }

    public function addActivityExperience(string $uuid, string $activityUuid)
    {
        $experienceType = (string) $this->request->getPost('experience_type');
        $seq = (int) ($this->request->getPost('sequence_order') ?? 1);
        return $this->run(fn () => SubjectLearningPackEngineService::addExperience($activityUuid, $experienceType, $seq), $uuid, 'activities', 'Pengalaman belajar (Understand/Apply/Reflect) berhasil ditambahkan.');
    }

    public function storeAssessment(string $uuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::addAssessmentReference($this->request->getPost()), $uuid, 'assessment', 'Assessment guidance berhasil ditambahkan.');
    }

    public function deleteAssessment(string $uuid, string $refUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteAssessmentReference($refUuid), $uuid, 'assessment', 'Assessment guidance berhasil dihapus.');
    }

    public function storeFollowup(string $uuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::addFollowupGuidance($this->request->getPost()), $uuid, 'followup', 'Follow-up guidance berhasil ditambahkan.');
    }

    public function deleteFollowup(string $uuid, string $guideUuid)
    {
        return $this->run(fn () => SubjectLearningPackEngineService::deleteFollowupGuidance($guideUuid), $uuid, 'followup', 'Follow-up guidance berhasil dihapus.');
    }

    public function clonePack(string $uuid)
    {
        try {
            $clone = SubjectLearningPackEngineService::clonePack($uuid, $this->request->getPost());
            return redirect()->to('curriculum/learning-packs/' . $clone['uuid'])->with('success', 'Learning pack berhasil dikloning.');
        } catch (\Throwable $e) {
            return redirect()->to('curriculum/learning-packs/' . $uuid . '/overview')->withInput()->with('error', $e->getMessage());
        }
    }

    public function structure(string $uuid)
    {
        try {
            $data = SubjectLearningPackEngineService::getPackStructureForPlanning($uuid);
            $json = json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return $this->response->setHeader('Content-Type', 'application/json')->setBody($json);
        } catch (\Throwable $e) {
            $errJson = json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return $this->response->setStatusCode(400)->setHeader('Content-Type', 'application/json')->setBody($errJson);
        }
    }

    public function transition(string $uuid)
    {
        $target = strtoupper((string) $this->request->getPost('target_status'));
        $permission = ['VALIDATED'=>'learning_packs.validate','REVIEWED'=>'learning_packs.review','APPROVED'=>'learning_packs.approve','LOCKED'=>'learning_packs.lock','ARCHIVED'=>'learning_packs.lock'][$target] ?? '';
        if ($permission === '' || ! has_permission($permission)) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'Hak akses workflow learning pack ditolak.']);
        return $this->run(fn () => SubjectLearningPackEngineService::transition($uuid, $target, (int) $this->request->getPost('revision_number')), $uuid, 'overview', 'Status learning pack berhasil diperbarui.');
    }

    public function stageImport(string $uuid)
    {
        return $this->run(function () use ($uuid) {
            $rows = json_decode((string) $this->request->getPost('rows_json'), true, 512, JSON_THROW_ON_ERROR);
            $pack = $this->pack($uuid);
            return LearningPackImportService::stage((int) $pack['unit_id'], $uuid, (string) $this->request->getPost('source_filename'), $rows);
        }, $uuid, 'lineage', 'Import Phase 3 berhasil masuk staging.');
    }

    public function applyImport(string $uuid, string $batchUuid)
    {
        return $this->run(fn () => LearningPackImportService::apply($batchUuid), $uuid, 'lineage', 'Import Phase 3 berhasil diaplikasikan.');
    }

    private function page(string $uuid, string $section)
    {
        $pack = $this->pack($uuid);
        $db = Database::connect();
        $units = $db->table('learning_units')->where('learning_pack_id', (int) $pack['id'])->orderBy('sequence_order')->get()->getResultArray();
        $unitIds = array_map('intval', array_column($units, 'id')) ?: [0];
        $data = [
            'title' => $pack['name'] . ' | Subject Learning Pack',
            'breadcrumb_active' => 'Subject Learning Pack',
            'section' => $section,
            'pack' => $pack,
            'coverage' => SubjectLearningPackEngineService::coverage($uuid),
            'units' => $units,
            'concepts' => $db->table('learning_concepts')->where('learning_pack_id', (int) $pack['id'])->orderBy('code')->get()->getResultArray(),
            'activities' => $db->table('learning_activities')->where('learning_pack_id', (int) $pack['id'])->orderBy('code')->get()->getResultArray(),
            'resources' => $db->table('learning_resources')->where('learning_pack_id', (int) $pack['id'])->orderBy('title')->get()->getResultArray(),
            'assessments' => $db->table('learning_assessment_references')->whereIn('learning_unit_id', $unitIds)->orderBy('id', 'DESC')->get()->getResultArray(),
            'followups' => $db->table('learning_followup_guidance')->whereIn('learning_unit_id', $unitIds)->orderBy('id', 'DESC')->get()->getResultArray(),
            'imports' => $db->table('learning_pack_import_batches')->where('learning_pack_id', (int) $pack['id'])->orderBy('id', 'DESC')->get()->getResultArray(),
            'audits' => $db->table('audit_logs')->where('module', 'learning_packs')->orderBy('id', 'DESC')->limit(50)->get()->getResultArray(),
            'canManage' => has_permission('learning_packs.manage') && ! in_array($pack['status'], ['LOCKED', 'ARCHIVED'], true),
        ];
        return view('learning_packs/detail', $data);
    }

    private function pack(string $uuid): array
    {
        $pack = Database::connect()->table('subject_learning_packs p')
            ->select('p.*, s.name subject_name, gl.name grade_name, cv.code curriculum_code, su.name unit_name')
            ->join('subjects s', 's.id=p.subject_id')
            ->join('grade_levels gl', 'gl.id=p.grade_level_id')
            ->join('curriculum_versions cv', 'cv.id=p.curriculum_version_id')
            ->join('school_units su', 'su.id=p.unit_id')
            ->where('p.uuid', $uuid)->get()->getRowArray();
        if (! $pack) throw new \RuntimeException('Learning pack tidak ditemukan.');
        UnitScopeService::assertUnit((int) $pack['unit_id']);
        return $pack;
    }

    private function run(callable $operation, string $uuid, string $section, string $message)
    {
        try { $operation(); return redirect()->to('curriculum/learning-packs/'.$uuid.'/'.$section)->with('success', $message); }
        catch (\Throwable $e) {
            if ($e instanceof ConcurrencyException) return $this->response->setStatusCode(409)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
            return redirect()->to('curriculum/learning-packs/'.$uuid.'/'.$section)->withInput()->with('error', $e->getMessage());
        }
    }
}
