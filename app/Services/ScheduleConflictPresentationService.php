<?php
namespace App\Services;
class ScheduleConflictPresentationService
{
    private const ADVISORY_CODES=['SUBJECT_MEETINGS_TOO_CLOSE','CLASS_SUBJECT_MEETINGS_TOO_CLOSE','TEACHER_CLASS_SUBJECT_REPEAT'];
    public static function split(array $conflicts):array
    {
        $blocking=[];$advisories=[];
        foreach($conflicts as $conflict){
            $code=strtoupper((string)($conflict['conflict_code']??$conflict['conflict_type']??''));
            if(in_array($code,self::ADVISORY_CODES,true))$advisories[]=$conflict;else$blocking[]=$conflict;
        }
        return ['blocking'=>$blocking,'advisories'=>$advisories];
    }
}
