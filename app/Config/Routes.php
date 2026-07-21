<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('dashboard', 'Home::index');
$routes->get('health', 'Health::index');
$routes->get('system/runtime', 'SystemController::runtime');

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
$routes->post('academic-periods/(:segment)/transition/(:segment)', 'AcademicPeriodController::transition/$1/$2');
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

