<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class ElectiveRouteSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestCannotAccessElectiveAdministration(): void
    {
        $this->withSession([])->get('electives')->assertRedirectTo(base_url('login'));
    }

    public function testGuestCannotAccessStudentSelfService(): void
    {
        $this->withSession([])->get('my-electives')->assertRedirectTo(base_url('login'));
    }
}
