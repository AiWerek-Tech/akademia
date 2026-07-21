<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class ExtendedUserManagementTest extends CIUnitTestCase
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
            'permissions'    => ['users.view', 'users.manage']
        ];
    }

    public function testUserCreationDuplicateUsernameAndEmail(): void
    {
        $db = Database::connect($this->DBGroup);
        $role = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $unit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();

        // 1. First valid creation
        $result = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => 'newguru',
                'email'                => 'newguru@wmvaa.id',
                'full_name'            => 'New Teacher',
                'password'             => 'GuruNewPass12345!',
                'roles'                => [$role['id']],
                'units'                => [$unit['id']],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);

        $result->assertRedirectTo('users');

        // Assert DB entry
        $user = $db->table('users')->where('username', 'newguru')->get()->getRowArray();
        $this->assertNotEmpty($user);
        $this->assertEquals('newguru@wmvaa.id', $user['email']);

        // 2. Duplicate Username should be rejected
        $resultDupUser = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => 'newguru',
                'email'                => 'newguru_diff@wmvaa.id', // Different email, same username
                'full_name'            => 'New Teacher',
                'password'             => 'GuruNewPass12345!',
                'roles'                => [$role['id']],
                'units'                => [$unit['id']],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);
        
        $resultDupUser->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('username', $errors);

        // 3. Duplicate Email should be rejected
        $resultDupEmail = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => 'newguru_diff', // Different username, same email
                'email'                => 'newguru@wmvaa.id',
                'full_name'            => 'New Teacher',
                'password'             => 'GuruNewPass12345!',
                'roles'                => [$role['id']],
                'units'                => [$unit['id']],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);
        
        $resultDupEmail->assertRedirect();
        $errors2 = session()->getFlashdata('errors');
        $this->assertNotEmpty($errors2);
        $this->assertArrayHasKey('email', $errors2);
    }

    public function testUserCreationPayloadValidationAndXssDefense(): void
    {
        $db = Database::connect($this->DBGroup);
        $role = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $unit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();

        // 1. Weak password validation test
        $resultWeak = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => 'gurutest2',
                'email'                => 'gurutest2@wmvaa.id',
                'full_name'            => 'Teacher Two',
                'password'             => '123456', // Too weak/short
                'roles'                => [$role['id']],
                'units'                => [$unit['id']],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);
        
        $resultWeak->assertRedirect();
        $this->assertArrayHasKey('password', session()->getFlashdata('errors'));

        // 2. Test XSS payloads (HTML/Script tag sanitization)
        $resultXss = $this->withSession($this->getSuperAdminSession())
            ->post('users', [
                'username'             => 'xssguru',
                'email'                => 'xssguru@wmvaa.id',
                'full_name'            => '<script>alert("XSS")</script> Guru', // XSS input
                'password'             => 'GuruPass12345!',
                'roles'                => [$role['id']],
                'units'                => [$unit['id']],
                'is_active'            => 1,
                'must_change_password' => 0
            ]);
        
        $resultXss->assertRedirectTo('users');
        
        // Assert entry exists and is output-safe
        $user = $db->table('users')->where('username', 'xssguru')->get()->getRowArray();
        $this->assertNotEmpty($user);
        $this->assertEquals('<script>alert("XSS")</script> Guru', $user['full_name']); // Stored raw but outputs must be escaped
    }

    public function testUserResetPasswordForcesChange(): void
    {
        $db = Database::connect($this->DBGroup);
        
        // Create user
        $db->table('users')->insert([
            'uuid'                 => \App\Services\UuidService::v4(),
            'username'             => 'resetee',
            'email'                => 'resetee@wmvaa.id',
            'full_name'            => 'Resetee User',
            'password_hash'        => password_hash('PassTemp12345!', PASSWORD_BCRYPT),
            'is_active'            => 1,
            'must_change_password' => 0,
            'created_at'           => date('Y-m-d H:i:s')
        ]);
        $userId = $db->insertID();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

        $result = $this->withSession($this->getSuperAdminSession())
            ->post('users/' . $user['uuid'] . '/reset-password');
        
        $result->assertRedirectTo('users');

        // Check password changed status and must_change_password
        $updatedUser = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertEquals(1, (int)$updatedUser['must_change_password']);
        $this->assertNotEquals($user['password_hash'], $updatedUser['password_hash']);
        $this->assertStringContainsString('Kata sandi untuk resetee berhasil direset', session()->getFlashdata('success'));
    }
}
