<?php

namespace App\Controllers;

use App\Models\AcademicCalendarModel;
use App\Models\AcademicCalendarDayModel;
use App\Models\AcademicCalendarEventModel;
use App\Models\AcademicCalendarRuleModel;
use App\Models\AcademicYearModel;
use App\Models\SchoolUnitModel;
use App\Services\AcademicCalendarGeneratorService;
use App\Services\UnitScopeService;
use App\Exceptions\AuthorizationException;
use Config\Database;

class AcademicCalendarController extends BaseController
{
    private AcademicCalendarGeneratorService $generatorService;
    private AcademicCalendarModel $calendarModel;

    public function __construct()
    {
        $this->generatorService = new AcademicCalendarGeneratorService();
        $this->calendarModel    = new AcademicCalendarModel();
    }

    private function assertCalendarScope(array $calendar, bool $manage = false): void
    {
        $unitId = (int) ($calendar['unit_id'] ?? 0);
        if ($unitId > 0) {
            UnitScopeService::assertUnit($unitId);
            return;
        }

        if ($manage && !is_super_admin() && !has_role('kepala_sekolah', 'wakasek_kurikulum')) {
            throw new AuthorizationException('Kalender lintas unit hanya dapat dikelola oleh pimpinan akademik.');
        }
    }

    private function validDateRange(string $startDate, string $endDate): bool
    {
        foreach ([$startDate, $endDate] as $date) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $errors = \DateTimeImmutable::getLastErrors();
            if (!$parsed || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                return false;
            }
        }
        return $startDate <= $endDate;
    }

    // ──────────────────────────────────────────────
    // INDEX: List all calendars
    // ──────────────────────────────────────────────
    public function index()
    {
        if (!has_permission('academic_calendar.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke Kalender Pendidikan.');
        }

        $db = Database::connect();
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $calendars = $db->table('academic_calendars ac')
            ->select('ac.*, ay.name as year_name, su.name as unit_name, su.code as unit_code')
            ->join('academic_years ay', 'ay.id = ac.academic_year_id')
            ->join('school_units su', 'su.id = ac.unit_id', 'left')
            ->groupStart()
                ->where('ac.unit_id IS NULL')
                ->orWhereIn('ac.unit_id', $allowedUnitIds)
            ->groupEnd()
            ->orderBy('ay.start_date', 'DESC')
            ->orderBy('ac.created_at', 'DESC')
            ->get()->getResultArray();

        $academicYears = (new AcademicYearModel())->orderBy('start_date', 'DESC')->findAll();
        $units = UnitScopeService::accessibleUnits();

        return view('academic_calendar/index', [
            'title'             => 'Kalender Pendidikan',
            'breadcrumb_active' => 'Kalender Pendidikan',
            'calendars'         => $calendars,
            'academicYears'     => $academicYears,
            'units'             => $units,
        ]);
    }

    // ──────────────────────────────────────────────
    // GENERATE: Create new calendar via auto-generation
    // ──────────────────────────────────────────────
    public function generate()
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->to('academic-calendar')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $academicYearId   = (int) $this->request->getPost('academic_year_id');
        $unitId           = $this->request->getPost('unit_id') ? (int) $this->request->getPost('unit_id') : null;
        $calendarName     = trim((string) $this->request->getPost('name'));
        $dinasRefNumber   = trim((string) $this->request->getPost('dinas_reference_number'));
        $dinasRefDate     = $this->request->getPost('dinas_reference_date') ?: null;

        if (!$academicYearId || !$calendarName) {
            return redirect()->back()->with('error', 'Tahun ajaran dan nama kalender wajib diisi.');
        }
        if (mb_strlen($calendarName) > 150 || mb_strlen($dinasRefNumber) > 100) {
            return redirect()->back()->withInput()->with('error', 'Nama kalender atau nomor referensi terlalu panjang.');
        }
        if (!(new AcademicYearModel())->find($academicYearId)) {
            return redirect()->back()->withInput()->with('error', 'Tahun ajaran tidak valid.');
        }
        if ($unitId !== null) {
            UnitScopeService::assertUnit($unitId);
        } elseif (!is_super_admin() && !has_role('kepala_sekolah', 'wakasek_kurikulum')) {
            throw new AuthorizationException('Kalender lintas unit hanya dapat dibuat oleh pimpinan akademik.');
        }

        $result = $this->generatorService->generate(
            $academicYearId,
            $unitId,
            $calendarName,
            $dinasRefNumber,
            $dinasRefDate,
            null,
            [
                'hes_sem1' => $this->request->getPost('target_hes_sem1'),
                'hes_sem2' => $this->request->getPost('target_hes_sem2'),
                'heb_sem1' => $this->request->getPost('target_heb_sem1'),
                'heb_sem2' => $this->request->getPost('target_heb_sem2'),
            ]
        );

        if ($result['success']) {
            return redirect()->to("academic-calendar/{$result['calendar_id']}/editor")->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message']);
    }

    // ──────────────────────────────────────────────
    // EDITOR: Interactive calendar grid editor
    // ──────────────────────────────────────────────
    public function editor(int $id)
    {
        if (!has_permission('academic_calendar.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses.');
        }

        $calendar = $this->calendarModel->find($id);
        if (!$calendar) {
            return redirect()->to('academic-calendar')->with('error', 'Kalender tidak ditemukan.');
        }
        $this->assertCalendarScope($calendar);

        $db   = Database::connect();
        $year = (new AcademicYearModel())->find($calendar['academic_year_id']);
        $unit = $calendar['unit_id']
            ? $db->table('school_units')->where('id', $calendar['unit_id'])->get()->getRowArray()
            : null;

        $grid       = $this->generatorService->getCalendarGrid($id);
        $eventTypes = $this->generatorService->getEventTypes();
        $events     = (new AcademicCalendarEventModel())
            ->where('calendar_id', $id)
            ->groupStart()->where('notes !=', 'GENERATED_FROM_RULE')->orWhere('notes IS NULL')->groupEnd()
            ->orderBy('start_date', 'ASC')
            ->findAll();
        $rules = $this->generatorService->getRules($id);
        $validation = json_decode((string) ($calendar['validation_summary_json'] ?? ''), true);
        if (!is_array($validation)) {
            $validation = $this->generatorService->validate($id);
        }

        // Calculate holidays list for the legend
        $holidays = (new AcademicCalendarDayModel())
            ->where('calendar_id', $id)
            ->whereIn('day_type_code', ['LU', 'CB'])
            ->where('event_title IS NOT NULL')
            ->orderBy('date', 'ASC')
            ->findAll();

        return view('academic_calendar/editor', [
            'title'             => 'Editor Kalender Pendidikan',
            'breadcrumb_active' => 'Kalender Pendidikan',
            'calendar'          => $calendar,
            'year'              => $year,
            'unit'              => $unit,
            'grid'              => $grid,
            'eventTypes'        => $eventTypes,
            'events'            => $events,
            'holidays'          => $holidays,
            'canManage'         => has_permission('academic_calendar.manage') && $calendar['status'] === 'DRAFT',
            'rules'             => $rules,
            'validation'        => $validation,
        ]);
    }

    // ──────────────────────────────────────────────
    // AJAX: Update single day cell
    // ──────────────────────────────────────────────
    public function updateDay(int $calendarId)
    {
        if (!has_permission('academic_calendar.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $calendarScope = $this->calendarModel->find($calendarId);
        if (!$calendarScope) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Kalender tidak ditemukan.']);
        }
        $this->assertCalendarScope($calendarScope, true);

        $date       = $this->request->getPost('date');
        $typeCode   = $this->request->getPost('day_type_code');
        $eventTitle = $this->request->getPost('event_title');

        if (!$this->validDateRange((string) $date, (string) $date)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Tanggal kalender tidak valid.']);
        }
        if (mb_strlen((string) $eventTitle) > 255) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Keterangan tanggal maksimal 255 karakter.']);
        }

        if (!$this->generatorService->updateDay($calendarId, $date, $typeCode, $eventTitle ?: null)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Hari tidak dapat diubah. Pastikan kalender masih draft dan tipe hari valid.']);
        }

        // Return updated metrics
        $calendar = $this->calendarModel->find($calendarId);

        return $this->response->setJSON([
            'success' => true,
            'metrics' => [
                'hes_sem1'  => (int) $calendar['total_hes_sem1'],
                'hes_sem2'  => (int) $calendar['total_hes_sem2'],
                'heb_sem1'  => (int) $calendar['total_heb_sem1'],
                'heb_sem2'  => (int) $calendar['total_heb_sem2'],
                'eff_weeks1'=> (int) $calendar['total_effective_weeks_sem1'],
                'eff_weeks2'=> (int) $calendar['total_effective_weeks_sem2'],
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // EVENTS: Store new school event
    // ──────────────────────────────────────────────
    public function storeEvent(int $calendarId)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar || $calendar['status'] !== 'DRAFT') return redirect()->to('academic-calendar')->with('error', 'Program hanya dapat diubah pada kalender draft.');
        $this->assertCalendarScope($calendar, true);

        $title = trim((string) $this->request->getPost('title'));
        $startDate = trim((string) $this->request->getPost('start_date'));
        $endDate = trim((string) $this->request->getPost('end_date'));
        if ($title === '' || mb_strlen($title) > 150 || !$this->validDateRange($startDate, $endDate)) {
            return redirect()->back()->withInput()->with('error', 'Judul dan rentang tanggal kegiatan tidak valid.');
        }

        $category = strtoupper((string) ($this->request->getPost('category') ?: 'SCHOOL_PROGRAM'));
        if (!in_array($category, ['SCHOOL_PROGRAM', 'EXAM', 'HOLIDAY', 'OTHER'], true) || mb_strlen((string) $this->request->getPost('notes')) > 5000) {
            return redirect()->back()->withInput()->with('error', 'Kategori atau catatan program tidak valid.');
        }
        (new AcademicCalendarEventModel())->insert([
            'calendar_id' => $calendarId,
            'title'       => $title,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'category'    => $category,
            'notes'       => $this->request->getPost('notes'),
        ]);

        return redirect()->to("academic-calendar/{$calendarId}/editor")->with('success', 'Program sekolah berhasil ditambahkan.');
    }

    // ──────────────────────────────────────────────
    // EVENTS: Delete school event
    // ──────────────────────────────────────────────
    public function deleteEvent(int $calendarId, int $eventId)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar || $calendar['status'] !== 'DRAFT') return redirect()->to('academic-calendar')->with('error', 'Program hanya dapat diubah pada kalender draft.');
        $this->assertCalendarScope($calendar, true);

        $eventModel = new AcademicCalendarEventModel();
        $event = $eventModel->find($eventId);
        if (!$event || (int) $event['calendar_id'] !== $calendarId) {
            return redirect()->to("academic-calendar/{$calendarId}/editor")->with('error', 'Kegiatan kalender tidak ditemukan.');
        }

        $eventModel->delete($eventId);

        return redirect()->to("academic-calendar/{$calendarId}/editor")->with('success', 'Program sekolah dihapus.');
    }

    // ──────────────────────────────────────────────
    // ACTIVATE: Set calendar as active
    // ──────────────────────────────────────────────
    public function activate(int $id)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $calendar = $this->calendarModel->find($id);
        if (!$calendar) {
            return redirect()->to('academic-calendar')->with('error', 'Kalender tidak ditemukan.');
        }
        $this->assertCalendarScope($calendar, true);

        $validation = $this->generatorService->validate($id);
        if ($validation['status'] === 'ERROR') {
            return redirect()->to("academic-calendar/{$id}/editor")
                ->with('error', 'Kalender belum dapat diaktifkan: ' . implode(' ', $validation['errors']));
        }

        // Deactivate others for the same year
        $this->calendarModel->where('academic_year_id', $calendar['academic_year_id'])
            ->where('unit_id', $calendar['unit_id'])
            ->where('id !=', $id)
            ->set('status', 'ARCHIVED')
            ->update();

        $this->calendarModel->update($id, ['status' => 'ACTIVE']);

        return redirect()->to('academic-calendar')->with('success', 'Kalender berhasil diaktifkan.');
    }

    public function preview()
    {
        if (!has_permission('academic_calendar.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }
        $unitId = $this->request->getPost('unit_id') ? (int) $this->request->getPost('unit_id') : null;
        if ($unitId !== null) {
            UnitScopeService::assertUnit($unitId);
        }
        return $this->response->setJSON($this->generatorService->preview(
            (int) $this->request->getPost('academic_year_id'),
            $unitId,
            null
        ));
    }

    public function rebuild(int $id)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }
        $calendar = $this->calendarModel->find($id);
        if (!$calendar) {
            return redirect()->to('academic-calendar')->with('error', 'Kalender tidak ditemukan.');
        }
        $this->assertCalendarScope($calendar, true);
        $result = $this->generatorService->rebuild($id, true);
        return redirect()->to("academic-calendar/{$id}/editor")
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function storeRule(int $calendarId)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }
        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar || $calendar['status'] !== 'DRAFT') {
            return redirect()->back()->with('error', 'Aturan hanya dapat diubah pada kalender draft.');
        }
        $this->assertCalendarScope($calendar, true);
        $title = trim((string) $this->request->getPost('title'));
        $start = trim((string) $this->request->getPost('start_date'));
        $end = trim((string) $this->request->getPost('end_date'));
        $type = trim((string) $this->request->getPost('day_type_code'));
        if ($title === '' || mb_strlen($title) > 200 || !$this->validDateRange($start, $end)) {
            return redirect()->back()->withInput()->with('error', 'Data aturan kalender tidak valid.');
        }
        $year = (new AcademicYearModel())->find((int) $calendar['academic_year_id']);
        $eventType = Database::connect()->table('academic_calendar_event_types')->where('code', $type)->get()->getRowArray();
        if (!$year || !$eventType || $start < $year['start_date'] || $end > $year['end_date']) {
            return redirect()->back()->withInput()->with('error', 'Tipe atau tanggal aturan berada di luar tahun ajaran.');
        }
        $sourceLayer = strtoupper((string) ($this->request->getPost('source_layer') ?: 'SCHOOL'));
        if (!in_array($sourceLayer, ['SCHOOL', 'DINAS', 'RELIGIOUS', 'SCHOOL_ADJUSTMENT'], true) || mb_strlen((string) $this->request->getPost('notes')) > 2000) {
            return redirect()->back()->withInput()->with('error', 'Sumber atau catatan aturan tidak valid.');
        }
        (new AcademicCalendarRuleModel())->insert([
            'calendar_id' => $calendarId,
            'title' => $title,
            'day_type_code' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'source_layer' => $sourceLayer,
            'priority' => max(1, min(100, (int) ($this->request->getPost('priority') ?: 60))),
            'is_school_effective' => (int) $eventType['is_school_effective'],
            'is_learning_effective' => (int) $eventType['is_learning_effective'],
            'is_enabled' => 1,
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'created_by' => session()->get('user_id'),
        ]);
        $this->generatorService->rebuild($calendarId, true);
        return redirect()->to("academic-calendar/{$calendarId}/editor")->with('success', 'Aturan ditambahkan dan kalender dihitung ulang.');
    }

    public function deleteRule(int $calendarId, int $ruleId)
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }
        $calendar = $this->calendarModel->find($calendarId);
        $rule = (new AcademicCalendarRuleModel())->find($ruleId);
        if (!$calendar || $calendar['status'] !== 'DRAFT' || !$rule || (int) $rule['calendar_id'] !== $calendarId) {
            return redirect()->back()->with('error', 'Aturan kalender tidak ditemukan atau tidak dapat diubah.');
        }
        $this->assertCalendarScope($calendar, true);
        (new AcademicCalendarRuleModel())->delete($ruleId);
        $this->generatorService->rebuild($calendarId, true);
        return redirect()->to("academic-calendar/{$calendarId}/editor")->with('success', 'Aturan dihapus dan kalender dihitung ulang.');
    }

    public function updateRule(int $calendarId, int $ruleId)
    {
        if (!has_permission('academic_calendar.manage')) return redirect()->back()->with('error', 'Akses ditolak.');
        $calendar = $this->calendarModel->find($calendarId);
        $model = new AcademicCalendarRuleModel();
        $rule = $model->find($ruleId);
        if (!$calendar || $calendar['status'] !== 'DRAFT' || !$rule || (int)$rule['calendar_id'] !== $calendarId) return redirect()->back()->with('error', 'Aturan tidak dapat diubah.');
        $this->assertCalendarScope($calendar, true);
        $title=trim((string)$this->request->getPost('title')); $start=trim((string)$this->request->getPost('start_date')); $end=trim((string)$this->request->getPost('end_date')); $type=trim((string)$this->request->getPost('day_type_code'));
        $year=(new AcademicYearModel())->find((int)$calendar['academic_year_id']);
        $eventType=Database::connect()->table('academic_calendar_event_types')->where('code',$type)->get()->getRowArray();
        if ($title==='' || mb_strlen($title)>200 || !$this->validDateRange($start,$end) || !$eventType || $start<$year['start_date'] || $end>$year['end_date']) return redirect()->back()->withInput()->with('error','Perubahan aturan tidak valid.');
        $sourceLayer=strtoupper((string)($this->request->getPost('source_layer') ?: 'SCHOOL'));
        if(!in_array($sourceLayer,['SCHOOL','DINAS','RELIGIOUS','SCHOOL_ADJUSTMENT'],true) || mb_strlen((string)$this->request->getPost('notes'))>2000) return redirect()->back()->withInput()->with('error','Sumber atau catatan aturan tidak valid.');
        $model->update($ruleId,[
            'title'=>$title,'start_date'=>$start,'end_date'=>$end,'day_type_code'=>$type,
            'source_layer'=>$sourceLayer,
            'priority'=>max(1,min(100,(int)($this->request->getPost('priority') ?: 60))),
            'is_school_effective'=>(int)$eventType['is_school_effective'],'is_learning_effective'=>(int)$eventType['is_learning_effective'],
            'notes'=>trim((string)$this->request->getPost('notes')) ?: null,
        ]);
        $this->generatorService->rebuild($calendarId,true);
        return redirect()->to("academic-calendar/{$calendarId}/editor?tab=rules")->with('success','Aturan diperbarui dan kalender dihitung ulang.');
    }

    public function updateEvent(int $calendarId, int $eventId)
    {
        if (!has_permission('academic_calendar.manage')) return redirect()->back()->with('error','Akses ditolak.');
        $calendar=$this->calendarModel->find($calendarId); $model=new AcademicCalendarEventModel(); $event=$model->find($eventId);
        if(!$calendar || $calendar['status']!=='DRAFT' || !$event || (int)$event['calendar_id']!==$calendarId) return redirect()->back()->with('error','Program tidak dapat diubah.');
        $this->assertCalendarScope($calendar,true);
        $title=trim((string)$this->request->getPost('title')); $start=trim((string)$this->request->getPost('start_date')); $end=trim((string)$this->request->getPost('end_date'));
        $year=(new AcademicYearModel())->find((int)$calendar['academic_year_id']);
        if($title==='' || mb_strlen($title)>255 || !$this->validDateRange($start,$end) || $start<$year['start_date'] || $end>$year['end_date']) return redirect()->back()->withInput()->with('error','Perubahan program tidak valid.');
        $category=strtoupper((string)($this->request->getPost('category') ?: 'SCHOOL_PROGRAM'));
        if(!in_array($category,['SCHOOL_PROGRAM','EXAM','HOLIDAY','OTHER'],true) || mb_strlen((string)$this->request->getPost('notes'))>5000) return redirect()->back()->withInput()->with('error','Kategori atau catatan program tidak valid.');
        $model->update($eventId,['title'=>$title,'start_date'=>$start,'end_date'=>$end,'category'=>$category,'notes'=>trim((string)$this->request->getPost('notes')) ?: null]);
        return redirect()->to("academic-calendar/{$calendarId}/editor?tab=programs")->with('success','Program sekolah diperbarui.');
    }

    public function resetDay(int $calendarId)
    {
        if(!has_permission('academic_calendar.manage')) return $this->response->setStatusCode(403)->setJSON(['success'=>false,'message'=>'Akses ditolak.']);
        $calendar=$this->calendarModel->find($calendarId); if(!$calendar) return $this->response->setStatusCode(404)->setJSON(['success'=>false,'message'=>'Kalender tidak ditemukan.']);
        $this->assertCalendarScope($calendar,true); $date=trim((string)$this->request->getPost('date'));
        if(!$this->validDateRange($date,$date) || !$this->generatorService->resetDayOverride($calendarId,$date)) return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>'Tanggal bukan perubahan manual atau tidak dapat direset.']);
        return $this->response->setJSON(['success'=>true,'message'=>'Tanggal dikembalikan ke hasil generator.']);
    }

    // ──────────────────────────────────────────────
    // PRINT: PDF-ready print view
    // ──────────────────────────────────────────────
    public function printCalendar(int $id)
    {
        if (!has_permission('academic_calendar.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $calendar = $this->calendarModel->find($id);
        if (!$calendar) {
            return redirect()->to('academic-calendar')->with('error', 'Kalender tidak ditemukan.');
        }
        $this->assertCalendarScope($calendar);

        $db   = Database::connect();
        $year = (new AcademicYearModel())->find($calendar['academic_year_id']);
        $unit = $calendar['unit_id']
            ? $db->table('school_units')->where('id', $calendar['unit_id'])->get()->getRowArray()
            : null;

        $grid       = $this->generatorService->getCalendarGrid($id);
        $eventTypes = $this->generatorService->getEventTypes();
        $events     = (new AcademicCalendarEventModel())
            ->where('calendar_id', $id)
            ->orderBy('start_date', 'ASC')
            ->findAll();
        $holidays   = (new AcademicCalendarDayModel())
            ->where('calendar_id', $id)
            ->whereIn('day_type_code', ['LU', 'CB'])
            ->where('event_title IS NOT NULL')
            ->orderBy('date', 'ASC')
            ->findAll();

        return view('academic_calendar/print', [
            'calendar'   => $calendar,
            'year'       => $year,
            'unit'       => $unit,
            'grid'       => $grid,
            'eventTypes' => $eventTypes,
            'events'     => $events,
            'holidays'   => $holidays,
        ]);
    }
}
