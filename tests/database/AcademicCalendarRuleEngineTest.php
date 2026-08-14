<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\AcademicCalendarGeneratorService;
use App\Services\AcademicOperatingSettingsService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/** @internal */
final class AcademicCalendarRuleEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testFiveDayProfileAndEditableRulesProduceDeterministicCalendar(): void
    {
        $db = Database::connect($this->DBGroup);
        $db->table('academic_years')->insert([
            'uuid' => '78000000-0000-4000-8000-000000000001',
            'name' => '2028/2029',
            'start_date' => '2028-07-10',
            'end_date' => '2029-06-22',
            'status' => 'DRAFT',
            'is_active' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $yearId = (int) $db->insertID();
        foreach ([
            [1, '2028-07-10', '2028-12-22'],
            [2, '2029-01-08', '2029-06-22'],
        ] as [$semester, $start, $end]) {
            $db->table('academic_periods')->insert([
                'uuid' => sprintf('78000000-0000-4000-8000-%012d', $semester + 1),
                'academic_year_id' => $yearId,
                'semester_number' => $semester,
                'name' => "Semester {$semester}",
                'start_date' => $start,
                'end_date' => $end,
                'workflow_status' => 'DRAFT',
                'is_active' => $semester === 1 ? 1 : 0,
                'revision_number' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $service = new AcademicCalendarGeneratorService();
        $result = $service->generate($yearId, null, 'Kalender Uji 2028/2029');
        $this->assertTrue($result['success']);
        $calendarId = (int) $result['calendar_id'];

        $saturday = $db->table('academic_calendar_days')->where('calendar_id', $calendarId)->where('date', '2028-07-15')->get()->getRowArray();
        $monday = $db->table('academic_calendar_days')->where('calendar_id', $calendarId)->where('date', '2028-07-10')->get()->getRowArray();
        $this->assertSame('SABAT', $saturday['day_type_code']);
        $this->assertSame(0, (int) $saturday['is_learning_effective']);
        $this->assertSame('HEB', $monday['day_type_code']);

        $db->table('academic_calendar_rules')->insert([
            'calendar_id' => $calendarId,
            'title' => 'Libur Lokal Uji',
            'day_type_code' => 'LU',
            'start_date' => '2028-07-10',
            'end_date' => '2028-07-10',
            'source_layer' => 'SCHOOL',
            'priority' => 60,
            'is_school_effective' => 0,
            'is_learning_effective' => 0,
            'is_enabled' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $rebuilt = $service->rebuild($calendarId);
        $this->assertTrue($rebuilt['success']);
        $holiday = $db->table('academic_calendar_days')->where('calendar_id', $calendarId)->where('date', '2028-07-10')->get()->getRowArray();
        $this->assertSame('LU', $holiday['day_type_code']);
        $this->assertSame('Libur Lokal Uji', $holiday['event_title']);

        $this->assertTrue($service->updateDay($calendarId, '2028-07-11', 'P', 'Program Khusus'));
        $service->rebuild($calendarId, true);
        $after = $db->table('academic_calendar_days')->where('calendar_id', $calendarId)->where('date', '2028-07-11')->get()->getRowArray();
        $this->assertSame('P', $after['day_type_code']);
        $this->assertSame(1, (int) $after['is_manual_override']);

        $calendar = $db->table('academic_calendars')->where('id', $calendarId)->get()->getRowArray();
        helper('auth');
        $html = view('academic_calendar/editor', [
            'calendar' => $calendar, 'year' => $db->table('academic_years')->where('id', $yearId)->get()->getRowArray(),
            'unit' => null, 'grid' => $service->getCalendarGrid($calendarId), 'eventTypes' => $service->getEventTypes(),
            'events' => [], 'holidays' => [], 'rules' => $service->getRules($calendarId),
            'validation' => $service->validate($calendarId), 'canManage' => true,
        ]);
        $this->assertStringContainsString('Ringkasan', $html);
        $this->assertStringContainsString('Konfigurasi terpadu', $html);
    }

    public function testGlobalCustomPolicyOverridesFallbackAndIsUnitScoped(): void
    {
        $db = Database::connect($this->DBGroup);
        $unit = $db->table('school_units')->where('is_active', 1)->orderBy('id')->get()->getRowArray();
        $this->assertNotNull($unit);
        $db->table('academic_years')->insert([
            'uuid' => '78000000-0000-4000-8000-000000000090', 'name' => '2030/2031',
            'start_date' => '2030-07-08', 'end_date' => '2031-06-20', 'status' => 'DRAFT',
            'is_active' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $yearId = (int) $db->insertID();
        $service = new AcademicOperatingSettingsService();
        $saved = $service->save($yearId, (int) $unit['id'], [
            'source_mode' => 'CUSTOM', 'working_days' => [1,2,3,4],
            'effective_week_min_days' => 2, 'compare_official_targets' => 0,
        ], 1);
        $this->assertSame('GLOBAL_CUSTOM', $saved['source']);
        $this->assertSame([1,2,3,4], $saved['working_days']);
        $this->assertSame(['MON','TUE','WED','THU'], $saved['working_day_codes']);
        $this->assertSame(2, $saved['effective_week_min_days']);
    }
}
