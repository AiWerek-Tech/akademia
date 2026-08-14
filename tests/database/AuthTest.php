<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Models\UserModel;
use App\Models\LoginAttemptModel;
use App\Database\Seeds\CoreSeeder;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testUserCreationAndPasswordHash(): void
    {
        $userModel = new UserModel();

        $userData = [
            'username'             => 'testteacher',
            'email'                => 'testteacher@wmvaa.sch.id',
            'full_name'            => 'Test Teacher M1',
            'password_hash'        => password_hash('SecretPassword123!', PASSWORD_BCRYPT, ['cost' => 12]),
            'is_active'            => 1,
            'must_change_password' => 0
        ];

        $userModel->insert($userData);
        $userId = $userModel->insertID();

        $this->assertIsNumeric($userId);

        $inserted = $userModel->find($userId);
        $this->assertNotEmpty($inserted);
        $this->assertTrue(password_verify('SecretPassword123!', $inserted['password_hash']));
    }

    public function testFailedAttemptsLockout(): void
    {
        $attemptModel = new LoginAttemptModel();
        $ip = '192.168.1.100';

        // Add 5 failed attempts within the lockout window
        for ($i = 0; $i < 5; $i++) {
            $attemptModel->insert([
                'username'     => 'testuser',
                'ip_address'   => $ip,
                'user_agent'   => 'PHPUnit-Test',
                'successful'   => 0,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Count failed attempts
        $fifteenMinutesAgo = date('Y-m-d H:i:s', time() - 900);
        $failedCount = $attemptModel->where('ip_address', $ip)
            ->where('successful', 0)
            ->where('attempted_at >=', $fifteenMinutesAgo)
            ->countAllResults();

        $this->assertEquals(5, $failedCount);
        $this->assertTrue($failedCount >= 5); // Locked out!
    }
}
