<?php

namespace App\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Services\CurriculumCoverageService;
use App\Services\EducationFoundationImportService;
use App\Services\EducationFoundationService;
use App\Services\EducationControlCenterService;
use App\Services\FeatureFlagService;
use App\Services\LearningObjectiveService;
use App\Services\LearningOutcomeService;
use App\Services\LearningPackService;
use App\Services\LearningSequenceService;
use App\Services\RegulationRegistryService;
use App\Services\UnitScopeService;
use Config\Database;

class EducationFoundationController extends BaseController
{
    public function dashboard()
    {
        if (!$this->enabled()) return $this->disabledResponse();
        return view('education_foundation/dashboard', [
            'title' => 'IALOS Education',
            'breadcrumb_active' => 'Control Center',
            'control' => EducationControlCenterService::build(),
        ]);
    }

    public function regulations() { return $this->page('regulations', RegulationRegistryService::regulations()); }
    public function sources() { return $this->page('sources', Database::connect()->table('curriculum_sources')->orderBy('code')->get()->getResultArray()); }
    public function profile() { return $this->page('profile', Database::connect()->table('graduate_profile_dimensions')->orderBy('sort_order')->get()->getResultArray()); }
    public function outcomes() { return $this->page('outcomes', LearningOutcomeService::hierarchy($this->request->getGet())); }
    public function objectives() { return $this->page('objectives', LearningObjectiveService::scoped($this->request->getGet())); }
    public function sequences() { return $this->page('sequences', LearningSequenceService::scoped()); }
    public function packs() { return $this->page('packs', LearningPackService::scoped()); }

    public function coverage()
    {
        $result=null;
        if ($this->request->getGet('curriculum_version_id') && $this->request->getGet('subject_id') && $this->request->getGet('grade_level_id')) {
            try {
                $result=CurriculumCoverageService::analyze((int)$this->request->getGet('curriculum_version_id'),UnitScopeService::resolveUnit($this->request->getGet('unit_id')),(int)$this->request->getGet('subject_id'),(int)$this->request->getGet('grade_level_id'));
            } catch (\Throwable $e) { return $this->failure($e,'/curriculum/coverage'); }
        }
        return $this->page('coverage',$result ?? []);
    }

    public function imports() { return $this->page('imports', Database::connect()->table('education_foundation_import_batches')->whereIn('unit_id',UnitScopeService::accessibleUnitIds() ?: [0])->orderBy('id','DESC')->get()->getResultArray()); }

    public function storeRegulation() { return $this->run(fn()=>RegulationRegistryService::createRegulation($this->request->getPost()),'/references/regulations','Regulasi berhasil ditambahkan.'); }
    public function storeSource() { return $this->run(fn()=>RegulationRegistryService::createSource($this->request->getPost()),'/references/curriculum-sources','Sumber kurikulum berhasil ditambahkan.'); }
    public function storeOutcome() { return $this->run(fn()=>LearningOutcomeService::create($this->request->getPost()),'/curriculum/outcomes','CP berhasil ditambahkan.'); }
    public function updateOutcome(string $uuid) { return $this->run(fn()=>LearningOutcomeService::update($uuid,$this->request->getPost()),'/curriculum/outcomes','CP berhasil diperbarui.'); }
    public function addOutcomeElement(string $uuid) { return $this->run(fn()=>LearningOutcomeService::addElement($uuid,$this->request->getPost()),'/curriculum/outcomes','Elemen CP berhasil ditambahkan.'); }
    public function storeObjective()
    {
        if (strtoupper((string)$this->request->getPost('source_level'))!=='NATIONAL') return $this->response->setStatusCode(422)->setJSON(['ok'=>false,'error'=>'Adaptasi TP harus dibuat melalui workflow adaptasi.']);
        if (!has_permission('regulations.manage')) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'Hanya pengelola referensi nasional yang dapat membuat TP nasional.']);
        return $this->run(fn()=>LearningObjectiveService::create($this->request->getPost()),'/curriculum/objectives','TP berhasil ditambahkan.');
    }
    public function adaptObjective(string $uuid) { return $this->run(fn()=>LearningObjectiveService::adapt($uuid,$this->request->getPost()),'/curriculum/objectives','Adaptasi TP berhasil dibuat.'); }
    public function updateObjective(string $uuid) { if (!$this->mayMutateObjective($uuid)) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'TP nasional hanya dapat diubah pengelola referensi nasional.']); return $this->run(fn()=>LearningObjectiveService::update($uuid,$this->request->getPost()),'/curriculum/objectives','TP berhasil diperbarui.'); }
    public function addObjectiveCriterion(string $uuid) { if (!$this->mayMutateObjective($uuid)) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'Kriteria TP nasional hanya dapat diubah pengelola referensi nasional.']); return $this->run(fn()=>LearningObjectiveService::addCriterion($uuid,$this->request->getPost()),'/curriculum/objectives','Kriteria TP berhasil ditambahkan.'); }
    public function storeSequence() { return $this->run(fn()=>LearningSequenceService::create($this->request->getPost()),'/curriculum/sequences','ATP berhasil ditambahkan.'); }
    public function addSequenceItem(string $uuid) { return $this->run(fn()=>LearningSequenceService::addItem($uuid,(string)$this->request->getPost('objective_uuid'),$this->request->getPost()),'/curriculum/sequences','TP berhasil ditambahkan ke ATP.'); }
    public function transitionSequence(string $uuid)
    {
        $target=strtoupper((string)$this->request->getPost('target_status'));
        $permission=['VALIDATED'=>'learning_sequences.validate','REVIEWED'=>'learning_sequences.review','APPROVED'=>'learning_sequences.approve','LOCKED'=>'learning_sequences.lock','ARCHIVED'=>'learning_sequences.lock'][$target] ?? '';
        if ($permission==='' || !has_permission($permission)) return $this->response->setStatusCode(403)->setJSON(['ok'=>false,'error'=>'Hak akses transisi ATP ditolak.']);
        return $this->run(fn()=>LearningSequenceService::transition($uuid,$target,(int)$this->request->getPost('revision_number')),'/curriculum/sequences','Status ATP berhasil diperbarui.');
    }
    public function cloneSequence(string $uuid) { return $this->run(fn()=>LearningSequenceService::clone($uuid,$this->request->getPost()),'/curriculum/sequences','ATP berhasil diklon.'); }
    public function storePack() { return $this->run(fn()=>LearningPackService::create($this->request->getPost()),'/curriculum/learning-packs','Paket pembelajaran berhasil dibuat.'); }
    public function attachPackObjective(string $uuid) { return $this->run(fn()=>LearningPackService::attachObjective($uuid,(string)$this->request->getPost('objective_uuid')),'/curriculum/learning-packs','TP berhasil ditautkan ke paket.'); }
    public function attachPackSequence(string $uuid) { return $this->run(fn()=>LearningPackService::attachSequence($uuid,(string)$this->request->getPost('sequence_uuid')),'/curriculum/learning-packs','ATP berhasil ditautkan ke paket.'); }

    public function stageImport()
    {
        return $this->run(function () {
            $rows=json_decode((string)$this->request->getPost('rows_json'),true,512,JSON_THROW_ON_ERROR);
            return EducationFoundationImportService::stage(UnitScopeService::resolveUnit($this->request->getPost('unit_id')),(int)$this->request->getPost('curriculum_version_id'),(string)$this->request->getPost('source_filename'),$rows);
        },'/curriculum/education-imports','Import berhasil divalidasi dan masuk staging.');
    }
    public function applyImport(string $uuid) { return $this->run(fn()=>EducationFoundationImportService::apply($uuid),'/curriculum/education-imports','Import berhasil diaplikasikan.'); }

    private function page(string $section,array $rows)
    {
        if (!$this->enabled()) return $this->disabledResponse();
        $db=Database::connect();
        $labels=['regulations'=>'Regulasi Pendidikan','sources'=>'Sumber Kurikulum','profile'=>'Profil Lulusan','outcomes'=>'Capaian Pembelajaran','objectives'=>'Tujuan Pembelajaran','sequences'=>'Alur Tujuan Pembelajaran','coverage'=>'Coverage Kurikulum','packs'=>'Paket Pembelajaran','imports'=>'Import Data Pendidikan'];
        return view('education_foundation/'.$section,[
            'title'=>$labels[$section].' | IALOS Education','breadcrumb_active'=>$labels[$section],'section'=>$section,'rows'=>$rows,
            'units'=>UnitScopeService::accessibleUnits(),
            'subjects'=>$db->table('subjects')->where('is_active',1)->orderBy('name')->get()->getResultArray(),
            'gradeLevels'=>$db->table('grade_levels')->where('is_active',1)->orderBy('sort_order')->get()->getResultArray(),
            'versions'=>$db->table('curriculum_versions')->orderBy('id','DESC')->get()->getResultArray(),
            'outcomeOptions'=>$db->table('learning_outcomes_cp')->select('id,uuid,code,statement')->orderBy('code')->get()->getResultArray(),
            'objectiveOptions'=>LearningObjectiveService::scoped(),
            'sequenceOptions'=>LearningSequenceService::scoped(),
        ]);
    }

    private function run(callable $operation,string $fallback,string $message)
    {
        if (!$this->enabled()) return $this->disabledResponse(true);
        try { $result=$operation(); if ($this->request->isAJAX()) return $this->response->setJSON(['ok'=>true,'data'=>$result]); return redirect()->to($fallback)->with('success',$message); }
        catch (\Throwable $e) { return $this->failure($e,$fallback); }
    }

    private function failure(\Throwable $e,string $fallback)
    {
        if ($e instanceof ConcurrencyException) return $this->response->setStatusCode(409)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
        if ($this->request->isAJAX()) return $this->response->setStatusCode($e instanceof \App\Exceptions\AuthorizationException ? 403 : 422)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
        return redirect()->to($fallback)->withInput()->with('error',$e->getMessage());
    }

    private function mayMutateObjective(string $uuid): bool
    {
        try { $objective=EducationFoundationService::byUuid('learning_objectives_tp',$uuid); }
        catch (\Throwable $e) { return false; }
        return $objective['source_level']!=='NATIONAL' || has_permission('regulations.manage');
    }

    private function enabled(): bool
    {
        return FeatureFlagService::isEnabled('ialos_education_foundation');
    }

    private function disabledResponse(bool $json = false)
    {
        if ($json || $this->request->isAJAX()) return $this->response->setStatusCode(404)->setJSON(['ok'=>false,'error'=>'IALOS Education belum diaktifkan.']);
        return $this->response->setStatusCode(404)->setBody('IALOS Education belum diaktifkan.');
    }
}
