<?php

namespace App\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Services\DigitalKspService;
use App\Services\FeatureFlagService;
use App\Services\KspComplianceService;
use App\Services\KspDocumentGeneratorService;
use App\Services\KspEvidenceService;
use App\Services\UnitScopeService;
use Config\Database;

class DigitalKspController extends BaseController
{
    public function index()
    {
        if (!$this->enabled()) return $this->disabled();
        $db = Database::connect();
        return view('ksp/index', [
            'title' => 'Digital KSP | IALOS Education',
            'breadcrumb_active' => 'Digital KSP',
            'versions' => DigitalKspService::scoped(),
            'units' => UnitScopeService::accessibleUnits(),
            'periods' => $db->table('academic_periods')->orderBy('id', 'DESC')->get()->getResultArray(),
        ]);
    }

    public function dashboard(string $uuid) { return $this->page($uuid, 'dashboard'); }
    public function context(string $uuid) { return $this->page($uuid, 'context', ['rows' => DigitalKspService::context($uuid)]); }
    public function visionGoals(string $uuid) { return $this->page($uuid, 'vision_goals', ['rows' => DigitalKspService::goals($uuid)]); }
    public function organization(string $uuid) { return $this->page($uuid, 'organization', ['rows' => DigitalKspService::organizations($uuid)]); }
    public function evaluation(string $uuid) { return $this->page($uuid, 'evaluation', ['rows' => DigitalKspService::evaluations($uuid),'actions'=>DigitalKspService::improvementActions($uuid)]); }
    public function compliance(string $uuid) { return $this->page($uuid, 'compliance', ['preview'=>KspComplianceService::latest($uuid)]); }
    public function evidence(string $uuid) { return $this->page($uuid, 'evidence', ['rows'=>KspEvidenceService::all($uuid),'targets'=>$this->evidenceTargets($uuid),'evidenceTypes'=>KspEvidenceService::TYPES]); }
    public function documents(string $uuid) { return $this->page($uuid, 'documents', ['rows'=>KspDocumentGeneratorService::all($uuid)]); }

    public function store() { return $this->run(fn () => DigitalKspService::createVersion($this->request->getPost()), '/education/ksp', 'Draft KSP berhasil dibuat.'); }
    public function storeContext(string $uuid) { return $this->run(fn () => DigitalKspService::addContext($uuid, $this->request->getPost()), "/education/ksp/{$uuid}/context", 'Konteks sekolah berhasil disimpan.'); }
    public function storeGoal(string $uuid) { return $this->run(fn () => DigitalKspService::addGoal($uuid, $this->request->getPost()), "/education/ksp/{$uuid}/vision-goals", 'Pernyataan visi, misi, atau tujuan berhasil disimpan.'); }
    public function storeOrganization(string $uuid) { return $this->run(fn () => DigitalKspService::addOrganization($uuid, $this->request->getPost()), "/education/ksp/{$uuid}/organization", 'Organisasi pembelajaran berhasil disimpan.'); }
    public function storeEvaluation(string $uuid) { return $this->run(fn () => DigitalKspService::addEvaluation($uuid, $this->request->getPost()), "/education/ksp/{$uuid}/evaluation", 'Evaluasi KSP berhasil disimpan.'); }
    public function storeImprovementAction(string $uuid,string $evaluationUuid) { return $this->run(fn () => DigitalKspService::addImprovementAction($uuid,$evaluationUuid,$this->request->getPost()), "/education/ksp/{$uuid}/evaluation", 'Tindak lanjut perbaikan berhasil disimpan.'); }
    public function updateImprovementAction(string $uuid,string $actionUuid) { return $this->run(fn () => DigitalKspService::updateImprovementAction($uuid,$actionUuid,$this->request->getPost()), "/education/ksp/{$uuid}/evaluation", 'Status tindak lanjut berhasil diperbarui.'); }
    public function updateSection(string $uuid, string $section) { return $this->run(fn () => DigitalKspService::updateSection($uuid, $section, $this->request->getPost()), "/education/ksp/{$uuid}", 'Kesiapan bagian KSP berhasil diperbarui.'); }
    public function runCompliance(string $uuid) { return $this->run(fn()=>KspComplianceService::preview($uuid), "/education/ksp/{$uuid}/compliance", 'Preview kepatuhan berhasil dibuat.'); }
    public function storeEvidence(string $uuid) { return $this->run(fn()=>KspEvidenceService::attach($uuid,$this->request->getPost(),$this->request->getFile('evidence_file')), "/education/ksp/{$uuid}/evidence", 'Evidence dan provenance berhasil disimpan.'); }
    public function generateDocument(string $uuid) { return $this->run(fn()=>KspDocumentGeneratorService::generate($uuid,(string)$this->request->getPost('format')), "/education/ksp/{$uuid}/documents", 'Dokumen KSP berhasil dibuat tanpa menimpa riwayat.'); }

    public function downloadEvidence(string $uuid,string $evidenceUuid)
    {
        $file=KspEvidenceService::file($uuid,$evidenceUuid);
        return $this->response->download($file['path'],null)->setFileName($file['row']['original_filename'] ?: basename($file['path']));
    }

    public function downloadDocument(string $uuid,string $documentUuid)
    {
        $file=KspDocumentGeneratorService::file($uuid,$documentUuid);
        return $this->response->download($file['path'],null)->setFileName($file['row']['uuid'].'.'.strtolower($file['row']['format']));
    }

    public function transition(string $uuid)
    {
        $target = strtoupper((string) $this->request->getPost('target_status'));
        $permission = ['REVIEW'=>'ksp.review','DRAFT'=>'ksp.review','APPROVED'=>'ksp.approve','LOCKED'=>'ksp.lock','SUPERSEDED'=>'ksp.lock'][$target] ?? '';
        if ($permission === '' || !has_permission($permission)) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'Hak akses transisi KSP ditolak.']);
        return $this->run(fn () => DigitalKspService::transition($uuid, $target, (int) $this->request->getPost('revision_number')), "/education/ksp/{$uuid}", 'Status KSP berhasil diperbarui.');
    }

    private function page(string $uuid, string $view, array $extra = [])
    {
        if (!$this->enabled()) return $this->disabled();
        $detail = DigitalKspService::detail($uuid);
        return view('ksp/'.$view, $extra + $detail + [
            'title' => $detail['version']['title'].' | Digital KSP',
            'breadcrumb_active' => 'Digital KSP',
            'canManage' => has_permission('ksp.manage') && $detail['version']['status'] === 'DRAFT',
        ]);
    }

    private function evidenceTargets(string $uuid): array
    {
        $detail=DigitalKspService::detail($uuid); $targets=[];
        foreach(DigitalKspService::context($uuid) as $r)$targets[]=['type'=>'SCHOOL_CONTEXT','uuid'=>$r['uuid'],'label'=>'Konteks · '.$r['title']];
        foreach(DigitalKspService::goals($uuid) as $r)$targets[]=['type'=>'VISION_MISSION_GOAL','uuid'=>$r['uuid'],'label'=>$r['statement_type'].' · '.mb_strimwidth($r['statement'],0,70,'…')];
        foreach(DigitalKspService::evaluations($uuid) as $r)$targets[]=['type'=>'EVALUATION','uuid'=>$r['uuid'],'label'=>'Evaluasi · '.mb_strimwidth($r['objective'],0,70,'…')];
        foreach(DigitalKspService::improvementActions($uuid) as $r)$targets[]=['type'=>'IMPROVEMENT_ACTION','uuid'=>$r['uuid'],'label'=>'Perbaikan · '.$r['title']];
        foreach($detail['sections'] as $r)$targets[]=['type'=>'KSP_SECTION','uuid'=>$r['uuid'],'label'=>'Bagian · '.$r['section_code']];
        return $targets;
    }

    private function run(callable $operation, string $fallback, string $message)
    {
        if (!$this->enabled()) return $this->disabled();
        try {
            $result = $operation();
            if ($this->request->isAJAX()) return $this->response->setJSON(['ok'=>true,'data'=>$result]);
            return redirect()->to($fallback)->with('success', $message);
        } catch (\Throwable $e) {
            $status = $e instanceof ConcurrencyException ? 409 : 422;
            if ($this->request->isAJAX()) return $this->response->setStatusCode($status)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
            return redirect()->to($fallback)->withInput()->with('error', $e->getMessage());
        }
    }

    private function enabled(): bool { return FeatureFlagService::isEnabled('ialos_education_foundation'); }
    private function disabled() { return $this->response->setStatusCode(404)->setBody('IALOS Education belum diaktifkan.'); }
}
