<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Models\CurriculumStructureModel;
use App\Models\CurriculumValidationResultModel;
use App\Models\SubjectModel;
use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use App\Models\RoomTypeModel;
use Config\Database;

class CurriculumValidationService
{
    /**
     * Run complete validation engine on a curriculum version and store results
     */
    public static function validateVersion(int $versionId): array
    {
        $db = Database::connect();
        $versionModel = new CurriculumVersionModel();
        $structureModel = new CurriculumStructureModel();
        $valResultModel = new CurriculumValidationResultModel();

        $version = $versionModel->find($versionId);
        if (!$version) {
            throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
        }

        // Clear existing validation results for this version
        $valResultModel->where('curriculum_version_id', $versionId)->delete();

        $structures = $structureModel->where('curriculum_version_id', $versionId)
            ->where('status', 'ACTIVE')
            ->findAll();

        $results = [];

        if (empty($structures)) {
            $results[] = self::addResult($versionId, null, 'MISSING_SUBJECT', 'WARNING', 'Versi kurikulum belum memiliki struktur mata pelajaran.');
        }

        // Cache for lookup optimization
        $subjectModel   = new SubjectModel();
        $gradeModel     = new GradeLevelModel();
        $classroomModel = new ClassroomModel();
        $roomTypeModel  = new RoomTypeModel();

        $seenDefaults = []; // "unitId_gradeId_subjectId"
        $seenOverrides = []; // "classroomId_subjectId"

        foreach ($structures as $s) {
            $sId = (int)$s['id'];
            $unitId = (int)$s['unit_id'];
            $gradeId = (int)$s['grade_level_id'];
            $classroomId = !empty($s['classroom_id']) ? (int)$s['classroom_id'] : null;
            $subjectId = (int)$s['subject_id'];

            // 1. Check duplicate subjects in same scope
            if ($classroomId === null) {
                $key = "{$unitId}_{$gradeId}_{$subjectId}";
                if (isset($seenDefaults[$key])) {
                    $results[] = self::addResult($versionId, $sId, 'SUBJECT_DUPLICATE', 'ERROR', "Mata pelajaran ID {$subjectId} terdaftar ganda pada tingkat ID {$gradeId}.");
                }
                $seenDefaults[$key] = true;
            } else {
                $key = "{$classroomId}_{$subjectId}";
                if (isset($seenOverrides[$key])) {
                    $results[] = self::addResult($versionId, $sId, 'SUBJECT_DUPLICATE', 'ERROR', "Mata pelajaran ID {$subjectId} terdaftar ganda pada kelas ID {$classroomId}.");
                }
                $seenOverrides[$key] = true;
            }

            // 2. Inactive Subject Check
            $subject = $subjectModel->find($subjectId);
            if (!$subject || (int)$subject['is_active'] !== 1 || !empty($subject['deleted_at'])) {
                $results[] = self::addResult($versionId, $sId, 'INACTIVE_SUBJECT', 'ERROR', "Mata pelajaran ID {$subjectId} tidak aktif atau sudah dihapus.");
            } else {
                // 3. Subject Unit Availability Check
                $avail = $db->table('subject_unit_availability')
                    ->where('subject_id', $subjectId)
                    ->where('unit_id', $unitId)
                    ->get()
                    ->getRowArray();
                if (!$avail) {
                    $results[] = self::addResult($versionId, $sId, 'SUBJECT_UNAVAILABLE_UNIT', 'ERROR', "Mata pelajaran '{$subject['name']}' tidak tersedia untuk unit ID {$unitId}.");
                }
            }

            // 4. Grade Level Check & Unit Mismatch
            $grade = $gradeModel->find($gradeId);
            if (!$grade || (int)$grade['is_active'] !== 1) {
                $results[] = self::addResult($versionId, $sId, 'INACTIVE_GRADE', 'ERROR', "Tingkat kelas ID {$gradeId} tidak aktif.");
            } elseif ((int)$grade['unit_id'] !== $unitId) {
                $results[] = self::addResult($versionId, $sId, 'GRADE_UNIT_MISMATCH', 'ERROR', "Tingkat kelas ID {$gradeId} tidak sesuai dengan unit ID {$unitId}.");
            }

            // 5. Classroom Check & Period Mismatch
            if ($classroomId !== null) {
                $classroom = $classroomModel->find($classroomId);
                if (!$classroom || (int)$classroom['is_active'] !== 1 || !empty($classroom['deleted_at'])) {
                    $results[] = self::addResult($versionId, $sId, 'INACTIVE_CLASSROOM', 'ERROR', "Kelas ID {$classroomId} tidak aktif atau tidak ditemukan.");
                } else {
                    if ((int)$classroom['academic_period_id'] !== (int)$version['academic_period_id']) {
                        $results[] = self::addResult($versionId, $sId, 'CLASSROOM_PERIOD_MISMATCH', 'ERROR', "Kelas '{$classroom['name']}' berasal dari periode akademik yang berbeda.");
                    }
                    if ((int)$classroom['unit_id'] !== $unitId || (int)$classroom['grade_level_id'] !== $gradeId) {
                        $results[] = self::addResult($versionId, $sId, 'GRADE_UNIT_MISMATCH', 'ERROR', "Kelas '{$classroom['name']}' tidak sesuai dengan unit atau tingkat yang ditentukan.");
                    }
                }
            }

            // 6. Effective JP Validation
            $effJP = (float)$s['effective_weekly_hours'];
            if ($effJP < 0) {
                $results[] = self::addResult($versionId, $sId, 'INVALID_JP', 'ERROR', "Jam efektif mingguan ({$effJP} JP) tidak boleh negatif.");
            }

            // 7. Effective Source & Reason Validation
            $effSource = strtoupper(trim($s['effective_source'] ?? ''));
            if (!in_array($effSource, ['OFFICIAL', 'CUSTOM', 'MANUAL'], true)) {
                $results[] = self::addResult($versionId, $sId, 'MISSING_EFFECTIVE_SOURCE', 'ERROR', "Sumber jam efektif ('{$effSource}') tidak valid.");
            } elseif (in_array($effSource, ['CUSTOM', 'MANUAL'], true) && empty(trim($s['adjustment_reason'] ?? ''))) {
                $results[] = self::addResult($versionId, $sId, 'MISSING_ADJUSTMENT_REASON', 'ERROR', "Sumber '{$effSource}' wajib mencantumkan alasan penyesuaian (adjustment_reason).");
            }

            // 8. Block Pattern Validation
            if (!empty($s['block_pattern_json'])) {
                $bpCheck = BlockPatternService::validateBlockPattern(
                    $s['block_pattern_json'],
                    $effJP,
                    !empty($s['minimum_days']) ? (int)$s['minimum_days'] : null,
                    !empty($s['maximum_daily_hours']) ? (float)$s['maximum_daily_hours'] : null
                );
                if (!$bpCheck['valid']) {
                    foreach ($bpCheck['errors'] as $errMsg) {
                        if (strpos($errMsg, 'Total jam dalam blok') !== false) {
                            $results[] = self::addResult($versionId, $sId, 'INVALID_BLOCK_TOTAL', 'ERROR', $errMsg);
                        } elseif (strpos($errMsg, 'melebihi batas maksimum') !== false) {
                            $results[] = self::addResult($versionId, $sId, 'MAXIMUM_DAILY_VIOLATION', 'ERROR', $errMsg);
                        } elseif (strpos($errMsg, 'lebih kecil dari minimum hari') !== false) {
                            $results[] = self::addResult($versionId, $sId, 'MINIMUM_DAY_VIOLATION', 'ERROR', $errMsg);
                        } else {
                            $results[] = self::addResult($versionId, $sId, 'INVALID_BLOCK_TOTAL', 'ERROR', $errMsg);
                        }
                    }
                }
            }

            // 9. Report & Teaching Load Flags Warning
            if (!isset($s['counts_in_report']) || $s['counts_in_report'] === null) {
                $results[] = self::addResult($versionId, $sId, 'MISSING_REPORT_FLAG', 'WARNING', "Flag masuk rapor (counts_in_report) belum ditentukan.");
            }
            if (!isset($s['counts_as_teaching_load']) || $s['counts_as_teaching_load'] === null) {
                $results[] = self::addResult($versionId, $sId, 'MISSING_LOAD_FLAG', 'WARNING', "Flag beban mengajar (counts_as_teaching_load) belum ditentukan.");
            }

            // 10. Room Type Active Check
            if (!empty($s['required_room_type_id'])) {
                $roomType = $roomTypeModel->find($s['required_room_type_id']);
                if (!$roomType || (int)$roomType['is_active'] !== 1) {
                    $results[] = self::addResult($versionId, $sId, 'INACTIVE_ROOM_TYPE', 'WARNING', "Jenis ruangan ID {$s['required_room_type_id']} tidak aktif.");
                }
            }
        }

        // Summary counts
        $errorCount   = 0;
        $blockerCount = 0;
        $warningCount = 0;
        $infoCount    = 0;

        foreach ($results as $r) {
            if ($r['severity'] === 'BLOCKER') $blockerCount++;
            elseif ($r['severity'] === 'ERROR') $errorCount++;
            elseif ($r['severity'] === 'WARNING') $warningCount++;
            elseif ($r['severity'] === 'INFO') $infoCount++;
        }

        return [
            'version_id'    => $versionId,
            'total_results' => count($results),
            'blockers'      => $blockerCount,
            'errors'        => $errorCount,
            'warnings'      => $warningCount,
            'infos'         => $infoCount,
            'has_blockers'  => ($blockerCount > 0 || $errorCount > 0),
            'results'       => $results,
        ];
    }

    private static function addResult(int $versionId, ?int $structureId, string $code, string $severity, string $message, ?array $details = null): array
    {
        $valResultModel = new CurriculumValidationResultModel();
        $record = [
            'curriculum_version_id'   => $versionId,
            'curriculum_structure_id' => $structureId,
            'validation_code'         => $code,
            'severity'                => $severity,
            'message'                 => $message,
            'details_json'            => $details ? json_encode($details) : null,
            'is_resolved'             => 0,
            'created_at'              => date('Y-m-d H:i:s'),
        ];
        $id = $valResultModel->insert($record);
        $record['id'] = $id;
        return $record;
    }
}
