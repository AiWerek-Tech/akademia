<?php

namespace App\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Services\CurriculumCoverageService;
use App\Services\EducationFoundationImportService;
use App\Services\LearningObjectiveService;
use App\Services\LearningOutcomeService;
use App\Services\LearningPackService;
use App\Services\LearningSequenceService;
use App\Services\RegulationRegistryService;
use App\Services\UnitScopeService;
use Config\Database;

class EducationFoundationController extends BaseController
{
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
    public function storeObjective() { return $this->run(fn()=>LearningObjectiveService::create($this->request->getPost()),'/curriculum/objectives','TP berhasil ditambahkan.'); }
    public function adaptObjective(string $uuid) { return $this->run(fn()=>LearningObjectiveService::adapt($uuid,$this->request->getPost()),'/curriculum/objectives','Adaptasi TP berhasil dibuat.'); }
    public function updateObjective(string $uuid) { return $this->run(fn()=>LearningObjectiveService::update($uuid,$this->request->getPost()),'/curriculum/objectives','TP berhasil diperbarui.'); }
    public function storeSequence() { return $this->run(fn()=>LearningSequenceService::create($this->request->getPost()),'/curriculum/sequences','ATP berhasil ditambahkan.'); }
    public function addSequenceItem(string $uuid) { return $this->run(fn()=>LearningSequenceService::addItem($uuid,(string)$this->request->getPost('objective_uuid'),$this->request->getPost()),'/curriculum/sequences','TP berhasil ditambahkan ke ATP.'); }
    public function transitionSequence(string $uuid) { return $this->run(fn()=>LearningSequenceService::transition($uuid,(string)$this->request->getPost('target_status'),(int)$this->request->getPost('revision_number')),'/curriculum/sequences','Status ATP berhasil diperbarui.'); }
    public function cloneSequence(string $uuid) { return $this->run(fn()=>LearningSequenceService::clone($uuid,$this->request->getPost()),'/curriculum/sequences','ATP berhasil diklon.'); }
    public function storePack() { return $this->run(fn()=>LearningPackService::create($this->request->getPost()),'/curriculum/learning-packs','Paket pembelajaran berhasil dibuat.'); }

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
        $db=Database::connect();
        return view('education_foundation/index',[
            'title'=>'IALOS Education Foundation','breadcrumb_active'=>'IALOS','section'=>$section,'rows'=>$rows,
            'units'=>UnitScopeService::accessibleUnits(),
            'subjects'=>$db->table('subjects')->where('is_active',1)->orderBy('name')->get()->getResultArray(),
            'gradeLevels'=>$db->table('grade_levels')->where('is_active',1)->orderBy('sort_order')->get()->getResultArray(),
            'versions'=>$db->table('curriculum_versions')->orderBy('id','DESC')->get()->getResultArray(),
        ]);
    }

    private function run(callable $operation,string $fallback,string $message)
    {
        try { $result=$operation(); if ($this->request->isAJAX()) return $this->response->setJSON(['ok'=>true,'data'=>$result]); return redirect()->to($fallback)->with('success',$message); }
        catch (\Throwable $e) { return $this->failure($e,$fallback); }
    }

    private function failure(\Throwable $e,string $fallback)
    {
        if ($e instanceof ConcurrencyException) return $this->response->setStatusCode(409)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
        if ($this->request->isAJAX()) return $this->response->setStatusCode($e instanceof \App\Exceptions\AuthorizationException ? 403 : 422)->setJSON(['ok'=>false,'error'=>$e->getMessage()]);
        return redirect()->to($fallback)->withInput()->with('error',$e->getMessage());
    }
}
