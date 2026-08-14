<?php

namespace App\Services;

use App\Models\TeacherSubstitutionRepairCandidateModel;
use Config\Database;

/**
 * Produces and atomically applies minimal local timetable repairs.
 * Display ownership never changes: only day_slot_id may move.
 */
class TeacherSubstitutionScheduleRepairService
{
    private $db;
    private TeacherSubstitutionRepairCandidateModel $candidateModel;
    private TeacherScheduleSubstitutionService $substitutions;
    private TeacherAvailabilityService $teacherAvailability;
    private ClassroomAvailabilityService $classroomAvailability;
    private RoomAvailabilityService $roomAvailability;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->candidateModel = new TeacherSubstitutionRepairCandidateModel();
        $this->substitutions = new TeacherScheduleSubstitutionService();
        $this->teacherAvailability = new TeacherAvailabilityService();
        $this->classroomAvailability = new ClassroomAvailabilityService();
        $this->roomAvailability = new RoomAvailabilityService();
    }

    /** @return array{candidate: array, changes: array, diagnostics: array} */
    public function generate(int $substitutionId, int $userId): array
    {
        $rule = $this->db->table('teacher_schedule_substitutions')->where('id', $substitutionId)->get()->getRowArray();
        if (! $rule) throw new \InvalidArgumentException('Aturan substitusi tidak ditemukan.');
        if ((string) $rule['status'] !== 'ACTIVE') throw new \RuntimeException('Auto Repair hanya tersedia untuk substitusi berstatus aktif.');
        if ((string) $rule['effective_to'] < date('Y-m-d')) throw new \RuntimeException('Masa substitusi sudah berakhir. Perbarui tanggal sebelum menjalankan Auto Repair.');
        $evaluationDate = max(date('Y-m-d'), (string) $rule['effective_from']);

        $periodId = (int) $rule['academic_period_id'];
        $versionRows = $this->latestVersions($periodId);
        if ($versionRows === []) throw new \RuntimeException('Tidak ada versi jadwal yang dapat dianalisis pada periode ini.');
        $versionIds = array_map('intval', array_column($versionRows, 'id'));
        $revisions = [];
        foreach ($versionRows as $v) $revisions[(int) $v['id']] = (int) $v['revision_number'];

        [$entries, $slots, $fixed, $requirements] = $this->loadState($versionIds);
        $initialState = [];
        foreach ($entries as $id => $entry) $initialState[$id] = (int) $entry['day_slot_id'];
        $baseline = $this->evaluate($initialState, $entries, $slots, $fixed, $requirements, $periodId, $evaluationDate);
        $targetResource = $this->substitutions->resolveResourceTeacherId((int) $rule['absent_teacher_id'], $periodId, $evaluationDate);

        $bestStates = [];
        if ($baseline['critical_count'] === 0) {
            $bestStates[] = ['state' => $initialState, 'evaluation' => $baseline];
        } else {
            $beam = [['state' => $initialState, 'evaluation' => $baseline]];
            $seen = [$this->stateKey($initialState, $initialState) => true];
            for ($depth = 1; $depth <= 5 && $bestStates === []; $depth++) {
                $next = [];
                foreach ($beam as $node) {
                    $problemIds = $node['evaluation']['problem_entry_ids'];
                    usort($problemIds, fn(int $a, int $b): int => $this->targetPriority($entries[$a], $targetResource, $periodId, $evaluationDate) <=> $this->targetPriority($entries[$b], $targetResource, $periodId, $evaluationDate));
                    foreach (array_slice($problemIds, 0, 8) as $problemId) {
                        if (!isset($entries[$problemId]) || (int) $entries[$problemId]['is_locked'] === 1) continue;
                        foreach ($this->neighbours($problemId, $node['state'], $entries, $slots, $fixed) as $candidateState) {
                            $key = $this->stateKey($candidateState, $initialState);
                            if (isset($seen[$key])) continue;
                            $seen[$key] = true;
                            $evaluation = $this->evaluate($candidateState, $entries, $slots, $fixed, $requirements, $periodId, $evaluationDate);
                            $item = ['state' => $candidateState, 'evaluation' => $evaluation];
                            if ($evaluation['critical_count'] === 0) $bestStates[] = $item;
                            else $next[] = $item;
                        }
                    }
                }
                usort($next, fn(array $a, array $b): int => $a['evaluation']['score'] <=> $b['evaluation']['score']);
                $beam = array_slice($next, 0, 240);
            }
        }

        if ($bestStates === []) {
            $details = implode(' | ', array_slice($baseline['messages'], 0, 5));
            throw new \RuntimeException('Belum ditemukan perbaikan aman dalam batas pencarian lokal. Tidak ada perubahan yang diterapkan; sesuaikan slot/ketersediaan lalu coba lagi.' . ($details !== '' ? ' Temuan awal: ' . $details : ''));
        }
        usort($bestStates, fn(array $a, array $b): int => $a['evaluation']['score'] <=> $b['evaluation']['score']);
        $best = $bestStates[0];
        $changes = $this->describeChanges($initialState, $best['state'], $entries, $slots);
        $now = date('Y-m-d H:i:s');
        $this->candidateModel->insert([
            'uuid' => UuidService::v4(), 'substitution_id' => $substitutionId,
            'status' => $changes === [] ? 'SAFE' : 'READY', 'score' => $best['evaluation']['score'],
            'moved_entry_count' => count($changes), 'critical_before' => $baseline['critical_count'],
            'critical_after' => $best['evaluation']['critical_count'],
            'changes_json' => json_encode($changes, JSON_THROW_ON_ERROR),
            'base_revisions_json' => json_encode($revisions, JSON_THROW_ON_ERROR),
            'diagnostics_json' => json_encode(['before' => $baseline['messages'], 'after' => $best['evaluation']['messages']], JSON_THROW_ON_ERROR),
            'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $candidate = $this->candidateModel->find((int) $this->candidateModel->getInsertID());
        $this->db->table('teacher_substitution_repair_candidates')
            ->where('substitution_id', $substitutionId)
            ->where('id !=', (int) $candidate['id'])
            ->whereIn('status', ['READY', 'SAFE'])
            ->update(['status' => 'STALE', 'updated_at' => $now]);
        return ['candidate' => $candidate, 'changes' => $changes, 'diagnostics' => ['before' => $baseline, 'after' => $best['evaluation']]];
    }

    public function apply(int $candidateId, int $substitutionId, int $userId): array
    {
        $candidate = $this->candidateModel->find($candidateId);
        if (!$candidate || (int)$candidate['substitution_id'] !== $substitutionId) throw new \InvalidArgumentException('Kandidat Auto Repair tidak ditemukan.');
        if ((string)$candidate['status'] !== 'READY') throw new \RuntimeException('Kandidat ini tidak dapat diterapkan atau sudah pernah diproses.');
        $rule = $this->db->table('teacher_schedule_substitutions')->where('id', $substitutionId)->get()->getRowArray();
        if (!$rule || (string)$rule['status'] !== 'ACTIVE' || (string)$rule['effective_to'] < date('Y-m-d')) throw new \RuntimeException('Aturan substitusi tidak lagi aktif pada saat kandidat diterapkan.');
        $evaluationDate = max(date('Y-m-d'), (string)$rule['effective_from']);
        $changes = json_decode((string)$candidate['changes_json'], true, 512, JSON_THROW_ON_ERROR);
        $revisions = json_decode((string)$candidate['base_revisions_json'], true, 512, JSON_THROW_ON_ERROR);
        if ($changes === []) throw new \RuntimeException('Jadwal sudah aman; tidak ada perubahan yang perlu diterapkan.');

        $versionIds = array_map('intval', array_keys($revisions)); sort($versionIds);
        $entryIds = array_map('intval', array_column($changes, 'entry_id')); sort($entryIds);
        $this->db->transException(true)->transBegin();
        try {
            foreach ($versionIds as $versionId) {
                $locked = $this->db->query('SELECT id, revision_number, workflow_status FROM schedule_versions WHERE id = ? FOR UPDATE', [$versionId])->getRowArray();
                if (!$locked || (int)$locked['revision_number'] !== (int)$revisions[$versionId]) throw new \RuntimeException('Kandidat kedaluwarsa karena jadwal telah berubah. Jalankan Analisis & Auto Repair kembali.', 409);
                if ((string)$locked['workflow_status'] !== 'DRAFT') throw new \RuntimeException('Auto Repair hanya dapat diterapkan pada jadwal DRAFT.');
            }
            $rows = $this->db->table('schedule_entries')->whereIn('id', $entryIds)->get()->getResultArray();
            if (count($rows) !== count($entryIds)) throw new \RuntimeException('Sebagian entri kandidat sudah tidak tersedia.');
            $rowMap = [];
            foreach ($rows as $row) $rowMap[(int)$row['id']] = $row;
            foreach ($changes as $change) {
                $row = $rowMap[(int)$change['entry_id']];
                if ((int)$row['is_locked'] === 1 || (int)$row['day_slot_id'] !== (int)$change['from_slot_id']) throw new \RuntimeException('Kandidat kedaluwarsa atau mencoba mengubah entri terkunci.');
            }

            $now = date('Y-m-d H:i:s');
            // Delete/reinsert the same IDs so arbitrary swaps cannot violate the
            // classroom unique key midway through an otherwise valid rotation.
            $conflictReferences = $this->db->table('schedule_conflicts')->select('id,primary_entry_id,conflicting_entry_id')->groupStart()->whereIn('primary_entry_id', $entryIds)->orWhereIn('conflicting_entry_id', $entryIds)->groupEnd()->get()->getResultArray();
            $exceptionReferences = $this->db->table('schedule_exceptions')->select('id,schedule_entry_id')->whereIn('schedule_entry_id', $entryIds)->get()->getResultArray();
            $resolved = ['is_resolved' => 1, 'status' => 'RESOLVED', 'updated_at' => $now];
            $this->db->table('schedule_conflicts')->whereIn('primary_entry_id', $entryIds)->update($resolved + ['primary_entry_id' => null]);
            $this->db->table('schedule_conflicts')->whereIn('conflicting_entry_id', $entryIds)->update($resolved + ['conflicting_entry_id' => null]);
            $this->db->table('schedule_entries')->whereIn('id', $entryIds)->delete();
            $changeMap = [];
            foreach ($changes as $change) $changeMap[(int)$change['entry_id']] = (int)$change['to_slot_id'];
            foreach ($rows as $row) {
                $row['day_slot_id'] = $changeMap[(int)$row['id']];
                $row['updated_at'] = $now; $row['updated_by'] = $userId;
                if (!$this->db->table('schedule_entries')->insert($row)) throw new \RuntimeException('Gagal memindahkan entri jadwal.');
            }
            foreach ($conflictReferences as $reference) {
                $this->db->table('schedule_conflicts')->where('id', $reference['id'])->update(['primary_entry_id' => $reference['primary_entry_id'], 'conflicting_entry_id' => $reference['conflicting_entry_id']]);
            }
            foreach ($exceptionReferences as $reference) {
                $this->db->table('schedule_exceptions')->where('id', $reference['id'])->update(['schedule_entry_id' => $reference['schedule_entry_id']]);
            }

            [$finalEntries, $finalSlots, $finalFixed, $finalRequirements] = $this->loadState($versionIds);
            $finalState = []; foreach ($finalEntries as $entryId => $entry) $finalState[$entryId] = (int)$entry['day_slot_id'];
            $finalEvaluation = $this->evaluate($finalState, $finalEntries, $finalSlots, $finalFixed, $finalRequirements, (int)$rule['academic_period_id'], $evaluationDate);
            if ($finalEvaluation['critical_count'] > 0) throw new \RuntimeException('Kandidat ditolak oleh validasi waktu efektif karena masih mengandung konflik. Seluruh perubahan dibatalkan.');

            foreach ($versionIds as $versionId) {
                $audit = (new ScheduleConflictDetectionService())->detectConflicts($versionId);
                $blocking = array_filter($audit['conflicts'] ?? [], static fn(array $c): bool => ($c['severity'] ?? '') === 'CRITICAL' || ($c['conflict_type'] ?? '') === 'UNMET_HOURS');
                if ($blocking !== []) throw new \RuntimeException('Kandidat ditolak oleh audit final karena masih mengandung ' . count($blocking) . ' konflik/gap. Seluruh perubahan dibatalkan.');
            }

            foreach ($versionIds as $versionId) {
                $newRevision = (int)$revisions[$versionId] + 1;
                $versionChanges = array_values(array_filter($changes, fn(array $c): bool => (int)$c['schedule_version_id'] === $versionId));
                if ($versionChanges === []) continue;
                $this->db->table('schedule_versions')->where('id', $versionId)->update(['revision_number' => $newRevision, 'change_summary' => 'Auto Repair substitusi guru #' . $substitutionId, 'updated_at' => $now, 'updated_by' => $userId]);
                $this->db->table('schedule_revision_history')->insert(['uuid' => UuidService::v4(), 'schedule_version_id' => $versionId, 'revision_number' => $newRevision, 'action' => 'APPLY_SUBSTITUTION_REPAIR', 'changes_json' => json_encode(['candidate_id' => $candidateId, 'substitution_id' => $substitutionId, 'moves' => $versionChanges], JSON_THROW_ON_ERROR), 'performed_by' => $userId, 'created_at' => $now]);
            }
            $this->db->table('teacher_substitution_repair_candidates')->where('id', $candidateId)->update(['status' => 'APPLIED', 'applied_by' => $userId, 'applied_at' => $now, 'updated_at' => $now]);
            $this->db->table('teacher_substitution_repair_candidates')->where('substitution_id', $substitutionId)->where('id !=', $candidateId)->where('status', 'READY')->update(['status' => 'STALE', 'updated_at' => $now]);
            $this->db->transCommit();
            return ['moved_entries' => count($changes), 'version_ids' => $versionIds];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function latestVersions(int $periodId): array
    {
        $rows = $this->db->table('schedule_versions')->select('id, unit_id, revision_number, workflow_status, is_active')->where('academic_period_id', $periodId)->where('workflow_status !=', 'ARCHIVED')->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        $result = []; $units = [];
        foreach ($rows as $row) if (!isset($units[(int)$row['unit_id']])) { $units[(int)$row['unit_id']] = true; $result[] = $row; }
        return $result;
    }

    private function loadState(array $versionIds): array
    {
        $entryRows = $this->db->table('schedule_entries se')->select('se.*, sv.unit_id, c.name class_name, s.name subject_name, t.full_name teacher_name')->join('schedule_versions sv','sv.id=se.schedule_version_id')->join('classrooms c','c.id=se.classroom_id')->join('subjects s','s.id=se.subject_id','left')->join('teachers t','t.id=se.teacher_id','left')->whereIn('se.schedule_version_id',$versionIds)->get()->getResultArray();
        $entries=[]; foreach($entryRows as $r) $entries[(int)$r['id']]=$r;
        $slotRows=$this->db->table('schedule_day_slots sds')->select('sds.id,sds.schedule_version_id,sds.slot_number,sds.slot_type,sds.start_time,sds.end_time,sd.day_of_week,sd.day_name')->join('schedule_days sd','sd.id=sds.day_id')->whereIn('sds.schedule_version_id',$versionIds)->get()->getResultArray();
        $slots=[]; foreach($slotRows as $r) $slots[(int)$r['id']]=$r;
        $fixedRows=$this->db->table('schedule_fixed_activities')->select('schedule_version_id,classroom_id,day_slot_id')->whereIn('schedule_version_id',$versionIds)->get()->getResultArray();
        $fixed=[]; foreach($fixedRows as $r) if((int)$r['classroom_id']>0) $fixed[(int)$r['schedule_version_id'].':'.(int)$r['classroom_id'].':'.(int)$r['day_slot_id']]=true;
        $requirementRows=$this->db->table('schedule_requirements')->select('id,required_weekly_hours')->whereIn('schedule_version_id',$versionIds)->get()->getResultArray();
        $requirements=[];foreach($requirementRows as $r)$requirements[(int)$r['id']]=(int)ceil((float)$r['required_weekly_hours']);
        return [$entries,$slots,$fixed,$requirements];
    }

    private function evaluate(array $state,array $entries,array $slots,array $fixed,array $requirements,int $periodId,string $evaluationDate): array
    {
        $critical=0; $messages=[]; $problems=[]; $teacherUse=[]; $roomUse=[]; $reqSlots=[];
        foreach($entries as $id=>$entry){
            $slot=$slots[$state[$id]]; $resources=[$this->substitutions->resolveResourceTeacherId((int)$entry['teacher_id'],$periodId,$evaluationDate)];
            $fixedKey=(int)$entry['schedule_version_id'].':'.(int)$entry['classroom_id'].':'.(int)$state[$id];
            if((int)($entry['subject_id']??0)>0&&isset($fixed[$fixedKey])){$critical++;$problems[$id]=true;$messages[]='Pelajaran bertabrakan dengan kegiatan tetap pada '.$slot['day_name'].' JP '.$slot['slot_number'].'.';}
            if(!empty($entry['second_teacher_id'])) $resources[]=$this->substitutions->resolveResourceTeacherId((int)$entry['second_teacher_id'],$periodId,$evaluationDate);
            foreach(array_unique($resources) as $resource){
                foreach($teacherUse[$resource][(int)$slot['day_of_week']]??[] as $used){ if($this->overlaps($slot,$used['slot'])){$critical++;$problems[$id]=true;$problems[$used['id']]=true;$messages[]='Guru fisik bentrok pada '.$slot['day_name'].' '.$slot['start_time'].'–'.$slot['end_time'];}}
                $teacherUse[$resource][(int)$slot['day_of_week']][]=['id'=>$id,'slot'=>$slot];
                if(!$this->teacherAvailability->isTeacherAvailable((int)$resource,$periodId,(int)$slot['day_of_week'],(int)$slot['slot_number'])){$critical++;$problems[$id]=true;$messages[]='Guru tidak tersedia pada '.$slot['day_name'].' JP '.$slot['slot_number'];}
            }
            if(!$this->classroomAvailability->isClassroomAvailable((int)$entry['classroom_id'],$periodId,(int)$slot['day_of_week'],(int)$slot['slot_number'])){$critical++;$problems[$id]=true;$messages[]='Kelas tidak tersedia.';}
            if(!empty($entry['room_id'])){
                $room=(int)$entry['room_id']; foreach($roomUse[$room][(int)$slot['day_of_week']]??[] as $used){if($this->overlaps($slot,$used['slot'])){$critical++;$problems[$id]=true;$problems[$used['id']]=true;$messages[]='Ruang dipakai bersamaan.';}}
                $roomUse[$room][(int)$slot['day_of_week']][]=['id'=>$id,'slot'=>$slot];
                if(!$this->roomAvailability->isRoomAvailable($room,$periodId,(int)$slot['day_of_week'],(int)$slot['slot_number'])){$critical++;$problems[$id]=true;$messages[]='Ruang tidak tersedia.';}
            }
            $req=(int)$entry['schedule_requirement_id']; if($req>0)$reqSlots[$req][]=['id'=>$id,'day'=>(int)$slot['day_of_week'],'number'=>(int)$slot['slot_number']];
        }
        $versionDays = [];
        foreach ($slots as $slot) {
            if (($slot['slot_type'] ?? '') === 'LESSON') {
                $versionDays[(int)$slot['schedule_version_id']][(int)$slot['day_of_week']] = true;
            }
        }
        foreach ($requirements as $req => $hours) {
            $items = $reqSlots[$req] ?? [];
            if (count($items) !== $hours) {
                $critical++;
                foreach ($items as $item) $problems[$item['id']] = true;
                $messages[] = 'Jumlah JP tidak sesuai untuk kebutuhan #' . $req;
                continue;
            }

            $needed = intdiv($hours, 2);
            if ($needed < 1) continue;
            $byDay = [];
            foreach ($items as $item) $byDay[$item['day']][] = $item;
            $pairs = 0;
            foreach ($byDay as &$dayItems) {
                usort($dayItems, fn($a, $b) => $a['number'] <=> $b['number']);
                $run = 1;
                for ($i = 1; $i < count($dayItems); $i++) {
                    if ($dayItems[$i]['number'] === $dayItems[$i - 1]['number'] + 1) $run++;
                    else { $pairs += intdiv($run, 2); $run = 1; }
                }
                $pairs += intdiv($run, 2);
            }
            unset($dayItems);
            if ($pairs < $needed) {
                $critical++;
                foreach ($items as $item) $problems[$item['id']] = true;
                $messages[] = 'Blok 2 JP terpecah untuk kebutuhan #' . $req;
            }

            // Four JP is specifically two 2-JP meetings on different days.
            // Day spacing remains a scored pedagogical preference, while Auto
            // Repair must never collapse it into one 4-JP session.
            $sampleEntryId = (int)($items[0]['id'] ?? 0);
            $versionId = (int)($entries[$sampleEntryId]['schedule_version_id'] ?? 0);
            if ($hours === 4 && count($versionDays[$versionId] ?? []) >= 2) {
                $days = array_map('intval', array_keys($byDay));
                sort($days);
                $validShape = count($days) === 2;
                foreach ($byDay as $dayItems) {
                    $validShape = $validShape
                        && count($dayItems) === 2
                        && $dayItems[1]['number'] === $dayItems[0]['number'] + 1;
                }
                if (!$validShape) {
                    $critical++;
                    foreach ($items as $item) $problems[$item['id']] = true;
                    $messages[] = 'Kebutuhan 4 JP #' . $req . ' wajib dibagi 2+2 pada hari berbeda.';
                }
            }
        }
        $moved=0;foreach($entries as $id=>$e)if($state[$id]!=(int)$e['day_slot_id'])$moved++;
        return ['critical_count'=>$critical,'problem_entry_ids'=>array_map('intval',array_keys($problems)),'messages'=>array_values(array_unique($messages)),'score'=>$critical*10000+$moved*100];
    }

    private function neighbours(int $problemId,array $state,array $entries,array $slots,array $fixed): array
    {
        $source=$entries[$problemId];$result=[];$occupied=[];
        foreach($entries as $id=>$e)if((int)$e['schedule_version_id']===(int)$source['schedule_version_id']&&(int)$e['classroom_id']===(int)$source['classroom_id'])$occupied[$state[$id]]=$id;
        foreach($occupied as $slotId=>$otherId){if($otherId===$problemId||(int)$entries[$otherId]['is_locked']===1)continue;$copy=$state;$copy[$problemId]=$slotId;$copy[$otherId]=$state[$problemId];$result[]=$copy;}
        foreach($slots as $slotId=>$slot){if((int)$slot['schedule_version_id']!==(int)$source['schedule_version_id']||$slot['slot_type']!=='LESSON'||isset($occupied[$slotId])||isset($fixed[(int)$source['schedule_version_id'].':'.(int)$source['classroom_id'].':'.$slotId]))continue;$copy=$state;$copy[$problemId]=$slotId;$result[]=$copy;}
        return $result;
    }

    private function describeChanges(array $initial,array $state,array $entries,array $slots): array
    {
        $result=[];foreach($state as $id=>$slotId){if($slotId===$initial[$id])continue;$e=$entries[$id];$from=$slots[$initial[$id]];$to=$slots[$slotId];$result[]=['entry_id'=>$id,'schedule_version_id'=>(int)$e['schedule_version_id'],'from_slot_id'=>$initial[$id],'to_slot_id'=>$slotId,'class_name'=>$e['class_name'],'subject_name'=>$e['subject_name'],'teacher_name'=>$e['teacher_name'],'from_label'=>$from['day_name'].' JP '.$from['slot_number'],'to_label'=>$to['day_name'].' JP '.$to['slot_number']];}return $result;
    }
    private function overlaps(array $a,array $b):bool{return (string)$a['start_time']<(string)$b['end_time']&&(string)$a['end_time']>(string)$b['start_time'];}
    private function targetPriority(array $entry,int $resource,int $periodId,string $evaluationDate):int{$ids=[$this->substitutions->resolveResourceTeacherId((int)$entry['teacher_id'],$periodId,$evaluationDate)];if(!empty($entry['second_teacher_id']))$ids[]=$this->substitutions->resolveResourceTeacherId((int)$entry['second_teacher_id'],$periodId,$evaluationDate);return in_array($resource,$ids,true)?0:1;}
    private function stateKey(array $state,array $initial):string{$changed=[];foreach($state as $id=>$slot)if($slot!==$initial[$id])$changed[$id]=$slot;ksort($changed);return json_encode($changed);}
}
