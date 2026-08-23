<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\UniversalSyncService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 11 — Universal Sync Engine & Mobile Offline-First API Gateway Test Suite.
 *
 * Verifies:
 *   - Mobile Authentication & Session Token Lifecycle (§4).
 *   - Version-based Delta Sync with Bandwidth Optimization (§8 & §9).
 *   - Universal Full Sync Manifest for TinyDB (§7).
 *   - Central Action-Based API Router (`?action=*`) & Strict JSON Responses (§3).
 *   - File Metadata Registry Gateway (§10).
 *   - Web Admin Sync Dashboard.
 *
 * @internal
 */
final class UniversalSyncEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private UniversalSyncService $service;
    protected string $testUsername = 'guru_sync_test';
    protected string $testPassword = 'SyncPassword123!';
    protected int $syncUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new UniversalSyncService($this->db);

        // Ensure Phase 11 permissions exist and are mapped to superadmin role
        $perms = [
            'sync.view'         => 'View Sync Registry and Mobile Logs',
            'sync.manage'       => 'Manage Sync Versions and Gateways',
            'api.mobile_access' => 'Access Mobile Sync API',
        ];

        foreach ($perms as $code => $name) {
            $p = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if (! $p) {
                $this->db->table('permissions')->insert([
                    'code'        => $code,
                    'module'      => 'system',
                    'name'        => $name,
                    'description' => $name,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                $permId = (int) $this->db->insertID();
            } else {
                $permId = (int) $p['id'];
            }

            // Map to superadmin / role 1
            $rolePerm = $this->db->table('role_permissions')->where(['role_id' => 1, 'permission_id' => $permId])->get()->getRowArray();
            if (! $rolePerm) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => 1,
                    'permission_id' => $permId,
                ]);
            }
        }

        // Ensure user 1 has role 1
        if ($this->db->table('user_roles')->where(['user_id' => 1, 'role_id' => 1])->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id' => 1, 'role_id' => 1]);
        }

        // Ensure a dedicated test user exists with known password
        $user = $this->db->table('users')->where('username', $this->testUsername)->get()->getRowArray();
        if (! $user) {
            $this->db->table('users')->insert([
                'uuid'                 => '99000000-0000-4000-8000-000000000001',
                'username'             => $this->testUsername,
                'email'                => 'guru.sync@example.test',
                'full_name'            => 'Guru Sync Percontohan, S.Kom',
                'password_hash'        => password_hash($this->testPassword, PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
            $this->syncUserId = (int) $this->db->insertID();
        } else {
            $this->syncUserId = (int) $user['id'];
        }

        // Grant unit access
        if ($this->db->table('user_unit_access')->where(['user_id' => $this->syncUserId, 'unit_id' => $this->unitId])->countAllResults() === 0) {
            $this->db->table('user_unit_access')->insert([
                'user_id'    => $this->syncUserId,
                'unit_id'    => $this->unitId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function testMobileAuthenticationAndTokenGeneration(): void
    {
        // 1. Authenticate with valid credentials
        $auth = $this->service->authenticate(
            $this->testUsername,
            $this->testPassword,
            'device-android-test-01',
            '127.0.0.1',
            'Kodular-Android-App/1.0'
        );

        $this->assertNotNull($auth);
        $this->assertArrayHasKey('token', $auth);
        $this->assertArrayHasKey('user', $auth);
        $this->assertArrayHasKey('versions', $auth);
        $this->assertSame($this->testUsername, $auth['user']['username']);

        $token = $auth['token'];

        // 2. Validate token
        $session = $this->service->validateToken($token);
        $this->assertNotNull($session);
        $this->assertSame($this->syncUserId, (int) $session['user_id']);
        $this->assertSame('device-android-test-01', $session['device_id']);

        // 3. Revoke token
        $revoked = $this->service->revokeToken($token);
        $this->assertTrue($revoked);

        // 4. Validate after revocation must return null
        $sessionAfter = $this->service->validateToken($token);
        $this->assertNull($sessionAfter);
    }

    public function testVersionRegistryAndIncrementEngine(): void
    {
        // 1. Initial versions should be default (1)
        $versions = $this->service->getLatestVersions($this->unitId);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_JADWAL, $versions);
        $this->assertSame(1, $versions[UniversalSyncService::TABLE_JADWAL]);

        // 2. Increment version for 'jadwal'
        $newVer = $this->service->incrementVersion(UniversalSyncService::TABLE_JADWAL, $this->unitId);
        $this->assertSame(2, $newVer);

        // 3. Increment again
        $newVer3 = $this->service->incrementVersion(UniversalSyncService::TABLE_JADWAL, $this->unitId);
        $this->assertSame(3, $newVer3);

        // 4. Check latest versions map
        $updatedVersions = $this->service->getLatestVersions($this->unitId);
        $this->assertSame(3, $updatedVersions[UniversalSyncService::TABLE_JADWAL]);
        $this->assertSame(1, $updatedVersions[UniversalSyncService::TABLE_PENGUMUMAN]);
    }

    public function testUniversalSyncAllPayloadStructure(): void
    {
        $payload = $this->service->syncAll($this->syncUserId, $this->unitId, UniversalSyncService::ROLE_GURU);

        $this->assertArrayHasKey('versions', $payload);
        $this->assertArrayHasKey('tables', $payload);

        $tables = $payload['tables'];
        $this->assertArrayHasKey(UniversalSyncService::TABLE_USERS, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_GURU, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_JADWAL, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_PENGUMUMAN, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_REFLEKSI, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_MASTERY, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_EKSTRA, $tables);
        $this->assertArrayHasKey(UniversalSyncService::TABLE_PROFIL_SEKOLAH, $tables);
    }

    public function testVersionBasedDeltaSyncReturnsOnlyModifiedTables(): void
    {
        // Increment 'jadwal' and 'pengumuman' on server
        $this->service->setVersion(UniversalSyncService::TABLE_JADWAL, $this->unitId, 5);
        $this->service->setVersion(UniversalSyncService::TABLE_PENGUMUMAN, $this->unitId, 3);
        $this->service->setVersion(UniversalSyncService::TABLE_GURU, $this->unitId, 1);

        // Client has: jadwal v4 (stale), pengumuman v3 (up-to-date), guru v1 (up-to-date)
        $clientVersions = [
            UniversalSyncService::TABLE_JADWAL     => 4,
            UniversalSyncService::TABLE_PENGUMUMAN => 3,
            UniversalSyncService::TABLE_GURU       => 1,
            UniversalSyncService::TABLE_USERS      => 1,
        ];

        $delta = $this->service->syncDelta($this->syncUserId, $this->unitId, UniversalSyncService::ROLE_GURU, $clientVersions);

        $this->assertArrayHasKey('versions', $delta);
        $this->assertArrayHasKey('tables', $delta);

        // Only 'jadwal' has server_version > client_version
        $this->assertArrayHasKey(UniversalSyncService::TABLE_JADWAL, $delta['tables']);

        // Unchanged tables MUST NOT be in delta response (Rule §9)
        $this->assertArrayNotHasKey(UniversalSyncService::TABLE_PENGUMUMAN, $delta['tables']);
        $this->assertArrayNotHasKey(UniversalSyncService::TABLE_GURU, $delta['tables']);
        $this->assertArrayNotHasKey(UniversalSyncService::TABLE_USERS, $delta['tables']);
    }

    public function testFileStorageMetadataRegistry(): void
    {
        $fileRecord = $this->service->registerFileMetadata([
            'file_name'     => 'student_artwork_01.png',
            'original_name' => 'Lukisan_Pemandangan.png',
            'file_type'     => 'IMAGE',
            'category'      => 'STUDENT_MEDIA',
            'storage_path'  => 'uploads/202608/student_artwork_01.png',
            'public_url'    => 'http://example.test/uploads/202608/student_artwork_01.png',
            'file_size_kb'  => 512,
            'mime_type'     => 'image/png',
        ], $this->syncUserId, $this->unitId);

        $this->assertNotNull($fileRecord);
        $this->assertNotEmpty($fileRecord['uuid']);
        $this->assertSame('STUDENT_MEDIA', $fileRecord['category']);

        $list = $this->service->listFiles($this->unitId, 'STUDENT_MEDIA');
        $this->assertNotEmpty($list);
        $this->assertSame('Lukisan_Pemandangan.png', $list[0]['original_name']);
    }

    public function testCentralApiActionRouterAndRestEndpoints(): void
    {
        // 1. Test Health Action
        $healthRes = $this->get('api/v1/sync?action=health');
        $healthRes->assertOK();
        $healthJson = json_decode($healthRes->getJSON(), true);
        $this->assertSame('success', $healthJson['status']);
        $this->assertSame('HEALTHY', $healthJson['data']['status']);

        // 2. Test Login Action via POST
        $loginRes = $this->post('api/v1/sync?action=login', [
            'username' => $this->testUsername,
            'password' => $this->testPassword,
        ]);
        $loginRes->assertOK();
        $loginJson = json_decode($loginRes->getJSON(), true);
        $this->assertSame('success', $loginJson['status']);

        $token = $loginJson['data']['token'];
        $this->assertNotEmpty($token);

        // 3. Test Sync All with Token
        $syncAllRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('api/v1/sync?action=sync_all');
        $syncAllRes->assertOK();
        $syncAllJson = json_decode($syncAllRes->getJSON(), true);
        $this->assertSame('success', $syncAllJson['status']);
        $this->assertArrayHasKey('tables', $syncAllJson['data']);

        // 4. Test Sync Delta with Token
        $syncDeltaRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('api/v1/sync?action=sync_delta', [
            'versions' => json_encode(['jadwal' => 0]),
        ]);
        $syncDeltaRes->assertOK();
        $syncDeltaJson = json_decode($syncDeltaRes->getJSON(), true);
        $this->assertSame('success', $syncDeltaJson['status']);

        // 5. Test Invalid Action
        $invalidRes = $this->get('api/v1/sync?action=unsupported_action');
        $invalidRes->assertStatus(400);
        $invalidJson = json_decode($invalidRes->getJSON(), true);
        $this->assertSame('error', $invalidJson['status']);
    }

    public function testWebAdminSyncDashboardController(): void
    {
        $sessionData = [
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
            'user_roles'       => ['superadmin'],
        ];

        // 1. Dashboard View
        $res = $this->withSession($sessionData)->get('system/sync');
        $res->assertOK();
        $res->assertSee('Gateway Integrasi');
        $res->assertSee('Registry Versi Tabel');

        // 2. Version Bump Action
        $bumpRes = $this->withSession($sessionData)->post('system/sync/bump/' . UniversalSyncService::TABLE_JADWAL);
        $bumpRes->assertRedirect();

        // Verify version bumped in DB
        $versions = $this->service->getLatestVersions($this->unitId);
        $this->assertGreaterThanOrEqual(2, $versions[UniversalSyncService::TABLE_JADWAL]);
    }
}
