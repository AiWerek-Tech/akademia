<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Controllers\Home;

/**
 * @internal
 */
final class HomeControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';

    public function testIndexRendersDashboard(): void
    {
        session()->set([
            'logged_in' => true,
            'full_name' => 'Super Admin',
            'role_name' => 'Administrator'
        ]);

        $result = $this->controller(Home::class)
                       ->execute('index');

        $this->assertTrue(
            $result->isOK(),
            'Dashboard response status: ' . $result->response()->getStatusCode()
                . "\n" . $result->response()->getBody()
        );
        $this->assertStringContainsString('WMVAA Akademia', $result->response()->getBody());
        $this->assertStringContainsString('Ringkasan Akademik', $result->response()->getBody());
    }
}
