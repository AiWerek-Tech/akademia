<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;

final class OperationalRoleSecurityTest extends CIUnitTestCase
{
    public function testStudentMutationsRequireDedicatedPermission(): void
    {
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("permission:students.manage", $routes);
        $this->assertStringNotContainsString("StudentsController::store', ['filter' => 'permission:students.view,classrooms.manage", $routes);
        $this->assertStringNotContainsString("StudentsController::delete/\$1', ['filter' => 'permission:students.view,classrooms.manage", $routes);
    }

    public function testDelegatedUserManagementHasExplicitRoleAllowLists(): void
    {
        $controller = (string) file_get_contents(APPPATH . 'Controllers/UserController.php');
        $this->assertStringContainsString('allowedManagedRoleCodes()', $controller);
        $this->assertStringContainsString("return ['guru', 'wali_kelas', 'siswa', 'tata_usaha'];", $controller);
        $this->assertStringContainsString("return ['guru', 'wali_kelas', 'siswa'];", $controller);
        $this->assertStringContainsString('Anda tidak dapat mengelola akun dengan peran', $controller);

        $this->assertStringContainsString("has_permission('users.reset_password')", $controller);
    }

    public function testStudentAndElectiveQueriesKeepUnitOwnershipBoundary(): void
    {
        $students = (string) file_get_contents(APPPATH . 'Controllers/StudentsController.php');
        $electives = (string) file_get_contents(APPPATH . 'Controllers/ElectiveSelectionsController.php');
        $this->assertStringContainsString("whereIn('es.unit_id', \$allowedUnitIds", $students);
        $this->assertStringContainsString('assertStudentReferences(', $students);
        $this->assertStringContainsString("where('r.code', 'siswa')", $electives);
        $this->assertStringContainsString("where('uua.unit_id', \$period['unit_id'])", $electives);
        $this->assertStringNotContainsString("where('r.name', 'siswa')", $electives);
    }

    public function testProductionTemplateContainsNoPopulatedDatabaseSecret(): void
    {
        $template = (string) file_get_contents(ROOTPATH . 'deploy/production.env.example');
        $this->assertStringContainsString("database.default.database = 'CHANGE_ME_DATABASE'", $template);
        $this->assertStringContainsString("database.default.username = 'CHANGE_ME_USERNAME'", $template);
        $this->assertStringContainsString("database.default.password = 'CHANGE_ME_PASSWORD'", $template);
    }
}
