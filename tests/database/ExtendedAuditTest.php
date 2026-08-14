<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Services\AuditService;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class ExtendedAuditTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testAuditLogSanitization(): void
    {
        $db = Database::connect($this->DBGroup);

        $sensitiveDataBefore = [
            'username'              => 'admin',
            'password'              => 'MySecretPassword123!',
            'password_hash'         => '$2y$10$abcdefghijklmnopqrstuvw',
            'csrf_test_name'        => 'csrf_token_value',
            'token'                 => 'secret_api_token_abc'
        ];

        $sensitiveDataAfter = [
            'username'              => 'admin',
            'password'              => 'MyNewSecretPassword123!',
            'password_hash'         => '$2y$10$xyzzyxyzzyxyzzyxyzzyxyz',
            'csrf_test_name'        => 'new_csrf_token_value',
            'token'                 => 'new_secret_api_token_abc'
        ];

        // Trigger log
        AuditService::log(
            'users',
            'test_sanitize',
            'User',
            1,
            $sensitiveDataBefore,
            $sensitiveDataAfter,
            'Testing audit log sanitization behavior'
        );

        // Fetch latest audit record
        $latest = $db->table('audit_logs')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $this->assertNotEmpty($latest);
        $this->assertEquals('users', $latest['module']);
        $this->assertEquals('test_sanitize', $latest['action']);

        $beforeJson = json_decode($latest['before_json'], true);
        $afterJson = json_decode($latest['after_json'], true);

        // Verify that sensitive fields are redacted to '[REDACTED]'
        $this->assertArrayHasKey('username', $beforeJson);
        $this->assertEquals('[REDACTED]', $beforeJson['password']);
        $this->assertEquals('[REDACTED]', $beforeJson['password_hash']);
        $this->assertEquals('[REDACTED]', $beforeJson['csrf_test_name']);
        $this->assertEquals('[REDACTED]', $beforeJson['token']);

        $this->assertArrayHasKey('username', $afterJson);
        $this->assertEquals('[REDACTED]', $afterJson['password']);
        $this->assertEquals('[REDACTED]', $afterJson['password_hash']);
        $this->assertEquals('[REDACTED]', $afterJson['csrf_test_name']);
        $this->assertEquals('[REDACTED]', $afterJson['token']);
    }
}
