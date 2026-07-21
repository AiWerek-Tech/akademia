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
