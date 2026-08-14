<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\TeacherAccountProvisioningService;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

final class TeacherAccountProvisioningTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testProvisionCreatesSecureLinkedAccountAndIsIdempotent(): void
    {
        $db = Database::connect($this->DBGroup);
        $unit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $db->table('teachers')->insert([
            'uuid' => UuidService::v4(),
            'full_name' => 'Guru Uji Otomatis',
            'normalized_name' => 'guru uji otomatis',
            'employment_status' => 'ACTIVE',
            'primary_unit_id' => (int) $unit['id'],
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $teacherId = (int) $db->insertID();
        $db->table('teacher_unit_assignments')->insert([
            'teacher_id' => $teacherId,
            'unit_id' => (int) $unit['id'],
            'assignment_type' => 'HOME_UNIT',
            'is_primary' => 1,
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $first = TeacherAccountProvisioningService::provisionAll();
        $credential = null;
        foreach ($first['credentials'] as $row) {
            if ((int) $row['teacher_id'] === $teacherId) {
                $credential = $row;
                break;
            }
        }
        $this->assertNotNull($credential);
        $this->assertMatchesRegularExpression('/^[0-9]{8}$/', $credential['temporary_password']);

        $user = $db->table('users')->where('teacher_id', $teacherId)->get()->getRowArray();
        $this->assertNotNull($user);
        $this->assertSame(1, (int) $user['must_change_username']);
        $this->assertSame(1, (int) $user['must_change_password']);
        $this->assertTrue(password_verify($credential['temporary_password'], $user['password_hash']));
        $this->assertSame(1, $db->table('user_unit_access')->where('user_id', $user['id'])->where('unit_id', $unit['id'])->where('is_default', 1)->countAllResults());

        $guruRole = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $this->assertSame(1, $db->table('user_roles')->where('user_id', $user['id'])->where('role_id', $guruRole['id'])->countAllResults());

        $second = TeacherAccountProvisioningService::provisionAll();
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $db->table('users')->where('teacher_id', $teacherId)->countAllResults());
    }
}
