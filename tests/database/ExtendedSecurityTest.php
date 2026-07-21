<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UserModel;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class ExtendedSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSuperAdminExists();
    }

    private function ensureSuperAdminExists(): void
    {
        $db = Database::connect($this->DBGroup);
        $user = $db->table('users')->where('id', 1)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => \App\Services\UuidService::v4(),
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }
        
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        if ($smp) {
            $access = $db->table('user_unit_access')
                ->where('user_id', 1)
                ->where('unit_id', $smp['id'])
                ->get()
                ->getRowArray();
            if (!$access) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => 1,
                    'unit_id'      => $smp['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function getSuperAdminSession(): array
    {
        return [
            'logged_in'      => true,
            'user_id'        => 1,
            'username'       => 'admin',
            'role_code'      => 'super_admin',
            'active_unit_id' => 1,
            'permissions'    => ['users.manage']
        ];
    }

    public function testSqlInjectionSecurityOnQueries(): void
    {
        $db = Database::connect($this->DBGroup);
        $userModel = new UserModel();

        // SQLi payload in search/where query
        $sqliPayload = "admin' OR '1'='1";
        
        // CI4 Query Builder must escape this automatically so it searches literally
        $user = $userModel->where('username', $sqliPayload)->first();
        $this->assertNull($user);

        // Check SQL compilation contains escaped string
        $sql = $userModel->builder()->where('username', $sqliPayload)->getCompiledSelect();
        $this->assertStringContainsString("`username` = 'admin\' OR \'1\'=\'1'", $sql);
    }

    public function testOverlongInputsRejectedByValidation(): void
    {
        // Exceeding 100 character limits
        $overlongUsername = str_repeat('a', 101);
        $overlongEmail = str_repeat('b', 140) . '@example.com'; // Exceeds 150 limit

        $result = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => $overlongUsername,
                'email'                => $overlongEmail,
                'full_name'            => 'Overlong Name Test',
                'password'             => 'StrongPass12345!',
                'roles'                => [1],
                'units'                => [1],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);

        $result->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testInvalidUuidUrlHandling(): void
    {
        $invalidUuid = 'invalid-uuid-1234567';

        $result = $this->withSession($this->getSuperAdminSession())
            ->get("users/{$invalidUuid}/edit");

        $result->assertRedirectTo('users');
        $this->assertEquals('Pengguna tidak ditemukan.', session()->getFlashdata('error'));
    }
}
