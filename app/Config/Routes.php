<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('dashboard', 'Home::index');
$routes->get('health', 'Health::index');
$routes->get('system/runtime', 'SystemController::runtime', ['filter' => 'dev_only']);

// Auth Routes
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->post('logout', 'AuthController::logout');
$routes->get('change-password', 'AuthController::changePassword');
$routes->post('change-password', 'AuthController::attemptChangePassword');

// Context Switcher
$routes->post('context/unit', 'ContextController::changeUnit');
$routes->post('context/period', 'ContextController::changePeriod');

// School Units Profile Management
$routes->get('settings/units', 'UnitController::index');
$routes->get('settings/units/(:segment)/edit', 'UnitController::edit/$1');
$routes->post('settings/units/(:segment)', 'UnitController::update/$1');

// Academic Years
$routes->get('academic-years', 'AcademicYearController::index');
$routes->get('academic-years/create', 'AcademicYearController::create');
$routes->post('academic-years', 'AcademicYearController::store');
$routes->get('academic-years/(:segment)/edit', 'AcademicYearController::edit/$1');
$routes->post('academic-years/(:segment)', 'AcademicYearController::update/$1');
$routes->post('academic-years/(:segment)/activate', 'AcademicYearController::activate/$1');

// Academic Periods
$routes->get('academic-periods', 'AcademicPeriodController::index');
$routes->get('academic-periods/create', 'AcademicPeriodController::create');
$routes->post('academic-periods', 'AcademicPeriodController::store');
$routes->get('academic-periods/(:segment)', 'AcademicPeriodController::show/$1');
$routes->get('academic-periods/(:segment)/edit', 'AcademicPeriodController::edit/$1');
$routes->post('academic-periods/(:segment)', 'AcademicPeriodController::update/$1');
$routes->post('academic-periods/(:segment)/activate', 'AcademicPeriodController::activate/$1');

// User Management
$routes->get('users', 'UserController::index');
$routes->get('users/create', 'UserController::create');
$routes->post('users', 'UserController::store');
$routes->get('users/(:segment)/edit', 'UserController::edit/$1');
$routes->post('users/(:segment)', 'UserController::update/$1');
$routes->post('users/(:segment)/reset-password', 'UserController::resetPassword/$1');

// Role & Permission Management
$routes->get('roles', 'RoleController::index');
$routes->get('roles/(:num)/permissions', 'RoleController::permissions/$1');
$routes->post('roles/(:num)/permissions', 'RoleController::updatePermissions/$1');

// ====================================================================
// MILESTONE 2: MASTER DATA AKADEMIK TERPADU
// ====================================================================

// Master Guru Global
$routes->get('teachers', 'TeachersController::index');
$routes->get('teachers/create', 'TeachersController::create');
$routes->post('teachers', 'TeachersController::store');
$routes->get('teachers/export', 'TeachersController::export');
$routes->get('teachers/(:segment)', 'TeachersController::show/$1');
$routes->get('teachers/(:segment)/edit', 'TeachersController::edit/$1');
$routes->post('teachers/(:segment)', 'TeachersController::update/$1');
$routes->post('teachers/(:segment)/verify', 'TeachersController::verify/$1');

// Duplicate Review Guru
$routes->get('duplicates', 'DuplicateReviewController::index');
$routes->get('duplicates/(:segment)', 'DuplicateReviewController::show/$1');
$routes->post('duplicates/(:segment)/resolve', 'DuplicateReviewController::resolve/$1');

// Master Mata Pelajaran Global
$routes->get('subjects', 'SubjectsController::index');
$routes->get('subjects/create', 'SubjectsController::create');
$routes->post('subjects', 'SubjectsController::store');
$routes->get('subjects/export', 'SubjectsController::export');
$routes->get('subjects/(:segment)/edit', 'SubjectsController::edit/$1');
$routes->post('subjects/(:segment)', 'SubjectsController::update/$1');

// Tingkat Kelas & Fase
$routes->get('grade-levels', 'GradeLevelsController::index');
$routes->get('grade-levels/create', 'GradeLevelsController::create');
$routes->post('grade-levels/store', 'GradeLevelsController::store');
$routes->get('grade-levels/(:segment)/edit', 'GradeLevelsController::edit/$1');
$routes->post('grade-levels/(:segment)', 'GradeLevelsController::update/$1');

// Kelas / Rombel per Periode
$routes->get('classrooms', 'ClassroomsController::index');
$routes->get('classrooms/create', 'ClassroomsController::create');
$routes->post('classrooms', 'ClassroomsController::store');
$routes->get('classrooms/export', 'ClassroomsController::export');
$routes->get('classrooms/copy-period', 'ClassroomsController::copyPeriodView');
$routes->post('classrooms/copy-period', 'ClassroomsController::applyCopyPeriod');
$routes->get('classrooms/(:segment)/edit', 'ClassroomsController::edit/$1');
$routes->post('classrooms/(:segment)', 'ClassroomsController::update/$1');

// Master Ruang Sekolah
$routes->get('rooms', 'RoomsController::index');
$routes->get('rooms/create', 'RoomsController::create');
$routes->post('rooms', 'RoomsController::store');
$routes->get('rooms/export', 'RoomsController::export');
$routes->get('rooms/(:segment)/edit', 'RoomsController::edit/$1');
$routes->post('rooms/(:segment)', 'RoomsController::update/$1');

// Import Staging Pipeline
$routes->get('imports/master', 'MasterImportController::index');
$routes->get('imports/master/template/(:segment)', 'MasterImportController::downloadTemplate/$1');
$routes->post('imports/master/upload', 'MasterImportController::upload');
$routes->get('imports/master/(:segment)', 'MasterImportController::showBatch/$1');
$routes->post('imports/master/(:segment)/apply', 'MasterImportController::apply/$1');

// Curriculum Versions & Structures (Milestone 3)
$routes->get('curriculum', 'CurriculumController::index');
$routes->get('curriculum/create', 'CurriculumController::create');
$routes->post('curriculum', 'CurriculumController::store');
$routes->get('curriculum/imports', 'CurriculumImportController::index');
$routes->get('curriculum/imports/template', 'CurriculumImportController::template');
$routes->post('curriculum/imports/upload', 'CurriculumImportController::upload');
$routes->get('curriculum/imports/(:segment)', 'CurriculumImportController::showBatch/$1');
$routes->post('curriculum/imports/(:segment)/apply', 'CurriculumImportController::apply/$1');

// Interactive Curriculum Matrix Routes
$routes->get('curriculum/(:segment)/matrix', 'CurriculumMatrixController::index/$1');
$routes->post('curriculum/(:segment)/matrix/update-cell', 'CurriculumMatrixController::updateCell/$1');
$routes->post('curriculum/(:segment)/matrix/bulk-store', 'CurriculumMatrixController::bulkStore/$1');
$routes->post('curriculum/(:segment)/matrix/clone-previous', 'CurriculumMatrixController::cloneFromPrevious/$1');
$routes->post('curriculum/(:segment)/matrix/apply-preset', 'CurriculumMatrixController::applyPreset/$1');

$routes->get('curriculum/(:segment)', 'CurriculumController::show/$1');
$routes->post('curriculum/(:segment)/structures', 'CurriculumController::storeStructure/$1');
$routes->post('curriculum/(:segment)/planning-settings', 'CurriculumController::savePlanningSettings/$1');
$routes->post('curriculum/(:segment)/structures/(:segment)/delete', 'CurriculumController::deleteStructure/$1/$2');
$routes->get('curriculum/(:segment)/reconciliation', 'CurriculumController::reconciliation/$1');
$routes->get('curriculum/(:segment)/export', 'CurriculumController::export/$1');
$routes->post('curriculum/(:segment)/activate', 'CurriculumController::activate/$1');

// Teaching Assignments & Workload (Milestone 4)
$routes->get('assignments', 'AssignmentsController::index');
$routes->get('assignments/create', 'AssignmentsController::create');
$routes->post('assignments', 'AssignmentsController::store');
$routes->get('assignments/imports', 'AssignmentsImportController::index');
$routes->get('assignments/imports/template', 'AssignmentsImportController::template');
$routes->post('assignments/imports/upload', 'AssignmentsImportController::upload');
$routes->get('assignments/imports/(:segment)', 'AssignmentsImportController::showBatch/$1');
$routes->post('assignments/imports/(:segment)/apply', 'AssignmentsImportController::apply/$1');
$routes->post('assignments/imports/(:segment)/rollback', 'AssignmentsImportController::rollback/$1');
$routes->get('assignments/(:segment)', 'AssignmentsController::show/$1');
$routes->post('assignments/(:segment)/store-assignment', 'AssignmentsController::storeAssignment/$1');
$routes->get('assignments/(:segment)/matrix', 'AssignmentsController::matrix/$1');
$routes->get('assignments/(:segment)/export', 'AssignmentsController::export/$1');
$routes->post('assignments/(:segment)/workflow/(:segment)', 'AssignmentsController::workflowAction/$1/$2');
$routes->post('assignments/(:segment)/clone', 'AssignmentsController::cloneVersion/$1');

$routes->get('workloads', 'WorkloadsController::index');
$routes->get('workloads/policies', 'WorkloadsController::policies');
$routes->get('workloads/policies/create', 'WorkloadsController::createPolicy');
$routes->post('workloads/policies', 'WorkloadsController::storePolicy');
$routes->post('workloads/recalculate', 'WorkloadsController::recalculate');
$routes->get('workloads/export', 'WorkloadsController::export');

$routes->get('duties', 'DutiesController::index');
$routes->get('duties/create', 'DutiesController::create');
$routes->post('duties', 'DutiesController::store');

// Scheduling System (Milestone 5)
$routes->group('schedules', ['filter' => 'permission:schedules.view'], static function ($routes) {
    $routes->get('/', 'SchedulesController::index');
    $routes->post('create', 'SchedulesController::create', ['filter' => 'permission:schedules.manage']);
    $routes->post('(:num)/transition', 'SchedulesController::transition/$1', ['filter' => 'permission:schedules.manage']);
    $routes->get('(:num)/editor', 'ScheduleEditorController::view/$1', ['filter' => 'permission:schedules.view']);
    $routes->post('(:num)/save-entry', 'ScheduleEditorController::saveEntry/$1', ['filter' => 'permission:schedules.manage']);
    $routes->post('entries/(:num)/delete', 'ScheduleEditorController::deleteEntry/$1', ['filter' => 'permission:schedules.manage']);
    $routes->post('(:num)/generate', 'ScheduleGeneratorController::run/$1', ['filter' => 'permission:schedules.generate']);
    $routes->post('candidates/(:num)/apply', 'ScheduleGeneratorController::applyCandidate/$1', ['filter' => 'permission:schedules.generate']);
    $routes->get('(:num)/audit', 'ScheduleConflictsController::audit/$1', ['filter' => 'permission:schedules.validate']);
    $routes->post('availability/teacher', 'ScheduleAvailabilityController::saveTeacherRule', ['filter' => 'permission:availability.manage']);
    $routes->post('constraints/(:num)/weight', 'ScheduleConstraintsController::updateWeight/$1', ['filter' => 'permission:constraints.manage']);
    $routes->post('(:num)/import/stage', 'ScheduleImportsController::stage/$1', ['filter' => 'permission:schedules.import']);
    $routes->post('import/batches/(:num)/apply', 'ScheduleImportsController::apply/$1', ['filter' => 'permission:schedules.import']);
    $routes->get('(:num)/reports/classroom/(:num)', 'ScheduleReportsController::classroomReport/$1/$2', ['filter' => 'permission:schedules.export']);
    $routes->get('(:num)/reports/teacher/(:num)', 'ScheduleReportsController::teacherReport/$1/$2', ['filter' => 'permission:schedules.export']);
});
