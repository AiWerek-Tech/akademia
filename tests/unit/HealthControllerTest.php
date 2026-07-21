<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Health;

/**
 * @internal
 */
final class HealthControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testHealthIndexReturnsSuccess(): void
    {
        $result = $this->controller(Health::class)
                       ->execute('index');

        $statusCode = $result->response()->getStatusCode();
        $bodyText = $result->response()->getBody();
        $this->assertEquals(200, $statusCode, "Response body was: " . $bodyText);
        
        $body = json_decode($bodyText, true);
        
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertArrayHasKey('php_version', $body);
        $this->assertArrayHasKey('checks', $body);
        
        $this->assertEquals('OK', $body['status']);
        $this->assertEquals('OK', $body['checks']['database']);
        $this->assertEquals('OK', $body['checks']['writable']);
    }
}
