<?php

namespace App\Controllers;

use App\Services\UnitScopeService;
use App\Services\AuditService;
use Config\Database;

class AttendanceSettingsController extends BaseController
{
    public function index()
    {
        $db = Database::connect();
        $years = $db->table('academic_years')->orderBy('start_date', 'DESC')->get()->getResultArray();
        $yearId = (int) $this->request->getGet('academic_year_id') ?: (int) ($db->table('academic_years')->where('is_active', 1)->get()->getRowArray()['id'] ?? ($years[0]['id'] ?? 0));
        $units = UnitScopeService::accessibleUnits();
        $settings = [];
        if ($yearId && $units) {
            $rows = $db->table('attendance_operating_settings')->where('academic_year_id', $yearId)->whereIn('unit_id', array_column($units, 'id'))->get()->getResultArray();
            foreach ($rows as $row) $settings[(int) $row['unit_id']] = $row;
        }
        return view('attendances/settings', ['title'=>'Pengaturan Absensi','breadcrumb_active'=>'Pengaturan Absensi','years'=>$years,'yearId'=>$yearId,'units'=>$units,'settings'=>$settings]);
    }

    public function save()
    {
        $yearId = (int) $this->request->getPost('academic_year_id');
        $unitId = (int) $this->request->getPost('unit_id');
        UnitScopeService::assertUnit($unitId);
        $db = Database::connect();
        if (!$db->table('academic_years')->where('id', $yearId)->countAllResults()) return redirect()->back()->with('error','Tahun ajaran tidak valid.');
        $times = [];
        foreach (['morning_start_time','morning_end_time','afternoon_start_time','afternoon_end_time'] as $field) {
            $value = trim((string) $this->request->getPost($field));
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) return redirect()->back()->withInput()->with('error','Format waktu tidak valid.');
            $times[$field] = $value . ':00';
        }
        if ($times['morning_start_time'] >= $times['morning_end_time'] || $times['afternoon_start_time'] >= $times['afternoon_end_time']) return redirect()->back()->withInput()->with('error','Waktu mulai harus lebih awal dari waktu selesai.');
        $data = $times + [
            'academic_year_id'=>$yearId,'unit_id'=>$unitId,
            'morning_label'=>mb_substr(trim((string)$this->request->getPost('morning_label')),0,100) ?: 'Apel & Absensi Pagi',
            'afternoon_label'=>mb_substr(trim((string)$this->request->getPost('afternoon_label')),0,100) ?: 'Apel & Absensi Siang',
            'carry_forward_absence'=>$this->request->getPost('carry_forward_absence')?1:0,
            'allow_off_schedule_subject'=>$this->request->getPost('allow_off_schedule_subject')?1:0,
            'updated_by'=>(int)session()->get('user_id'),'updated_at'=>date('Y-m-d H:i:s'),
        ];
        $existing = $db->table('attendance_operating_settings')->where('academic_year_id',$yearId)->where('unit_id',$unitId)->get()->getRowArray();
        if ($existing) {
            $data['revision_number']=(int)$existing['revision_number']+1;
            $db->table('attendance_operating_settings')->where('id',(int)$existing['id'])->update($data);
        } else {
            $data['created_by']=(int)session()->get('user_id');$data['created_at']=date('Y-m-d H:i:s');
            $db->table('attendance_operating_settings')->insert($data);
        }
        AuditService::log('attendance','UPDATE_SETTINGS','AttendanceOperatingSetting',$existing ? (int)$existing['id'] : (int)$db->insertID(),$existing,$data,'Pengaturan operasional absensi diperbarui');
        return redirect()->to('settings/attendance?academic_year_id='.$yearId)->with('success','Pengaturan absensi berhasil diperbarui.');
    }
}
