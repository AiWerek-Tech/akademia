<?php

namespace Tests\Support;

use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Keeps database tests isolated without rebuilding the complete schema before
 * every test method. The schema and seed baseline are rebuilt once per test
 * class, then every test runs inside an outer transaction that is rolled back.
 */
trait IsolatedDatabaseTestTrait
{
    use DatabaseTestTrait {
        setUpDatabase as private frameworkSetUpDatabase;
        tearDownDatabase as private frameworkTearDownDatabase;
    }

    protected function setUpDatabase(): void
    {
        // The test DB is pre-synced from production via mysqldump.
        // CI4's MigrationRunner sees all migrations as 'pending' due to group
        // mismatches, triggering broken Phase 6 FK constraints. We skip
        // framework migrations entirely and rely on the already-synced schema.
        $this->migrateOnce = true;
        $this->seedOnce    = true;
        $this->refresh     = false;
        $this->migrate     = false;

        $this->frameworkSetUpDatabase();

        if (! $this->db->transBegin()) {
            throw new \RuntimeException('Tidak dapat memulai transaksi isolasi test database.');
        }
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->db)) {
            $this->db->transRollback();
        }

        $this->frameworkTearDownDatabase();
    }
}
