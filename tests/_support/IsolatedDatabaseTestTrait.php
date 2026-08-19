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
        $this->migrateOnce = true;
        $this->seedOnce    = true;
        $this->refresh     = true;

        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        $this->frameworkSetUpDatabase();

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        if (! $this->db->transBegin()) {
            throw new \RuntimeException('Tidak dapat memulai transaksi isolasi test database.');
        }
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->db)) {
            $this->db->transRollback();
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        }

        $this->frameworkTearDownDatabase();

        if (isset($this->db)) {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
