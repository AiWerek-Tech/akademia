<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Home;

/**
 * @internal
 */
final class HomeControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

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

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('WMVAA Akademia', $result->response()->getBody());
        $this->assertStringContainsString('Selamat Datang', $result->response()->getBody());
    }
}
