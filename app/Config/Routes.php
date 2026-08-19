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

// School Units & Application Settings
$routes->get('settings/units', 'UnitController::index');
$routes->get('settings/units/(:segment)/edit', 'UnitController::edit/$1');
$routes->post('settings/units/(:segment)', 'UnitController::update/$1');
$routes->get('settings/application', 'UnitController::index');
$routes->get('settings/academic-operations', 'AcademicOperatingSettingsController::index', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('settings/academic-operations', 'AcademicOperatingSettingsController::save', ['filter' => 'permission:academic_calendar.manage']);
$routes->get('settings/attendance', 'AttendanceSettingsController::index', ['filter' => 'permission:attendances.admin']);
$routes->post('settings/attendance', 'AttendanceSettingsController::save', ['filter' => 'permission:attendances.admin']);

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

// Academic Calendar (Kalender Pendidikan)
$routes->get('academic-calendar', 'AcademicCalendarController::index', ['filter' => 'permission:academic_calendar.view']);
$routes->post('academic-calendar/generate', 'AcademicCalendarController::generate', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/preview', 'AcademicCalendarController::preview', ['filter' => 'permission:academic_calendar.manage']);
$routes->get('academic-calendar/(:num)/editor', 'AcademicCalendarController::editor/$1', ['filter' => 'permission:academic_calendar.view']);
$routes->post('academic-calendar/(:num)/update-day', 'AcademicCalendarController::updateDay/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/reset-day', 'AcademicCalendarController::resetDay/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/events/store', 'AcademicCalendarController::storeEvent/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/events/(:num)/delete', 'AcademicCalendarController::deleteEvent/$1/$2', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/events/(:num)/update', 'AcademicCalendarController::updateEvent/$1/$2', ['filter' => 'permission:academic_calendar.manage']);
$routes->get('academic-calendar/(:num)/print', 'AcademicCalendarController::printCalendar/$1', ['filter' => 'permission:academic_calendar.view']);
$routes->post('academic-calendar/(:num)/activate', 'AcademicCalendarController::activate/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/rebuild', 'AcademicCalendarController::rebuild/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/rules/store', 'AcademicCalendarController::storeRule/$1', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/rules/(:num)/delete', 'AcademicCalendarController::deleteRule/$1/$2', ['filter' => 'permission:academic_calendar.manage']);
$routes->post('academic-calendar/(:num)/rules/(:num)/update', 'AcademicCalendarController::updateRule/$1/$2', ['filter' => 'permission:academic_calendar.manage']);

// User Management
$routes->get('users', 'UserController::index');
$routes->get('users/create', 'UserController::create');
$routes->post('users', 'UserController::store');
$routes->post('users/provision-teachers', 'UserController::provisionTeachers');
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
$routes->post('classrooms/store', 'ClassroomsController::store');
$routes->get('classrooms/export', 'ClassroomsController::export');
$routes->get('classrooms/copy-period', 'ClassroomsController::copyPeriodView');
$routes->post('classrooms/copy-period', 'ClassroomsController::applyCopyPeriod');
$routes->get('classrooms/promote', 'ClassroomsController::promoteView');
$routes->post('classrooms/promote', 'ClassroomsController::applyPromote');
$routes->get('classrooms/(:segment)/students', 'ClassroomsController::students/$1');
$routes->post('classrooms/(:segment)/students/assign', 'ClassroomsController::assignStudent/$1');
$routes->post('classrooms/(:segment)/students/(:num)/remove', 'ClassroomsController::removeStudent/$1/$2');
$routes->get('classrooms/(:segment)/edit', 'ClassroomsController::edit/$1');
$routes->post('classrooms/(:segment)', 'ClassroomsController::update/$1');

// Master Ruang Sekolah
$routes->get('rooms', 'RoomsController::index');
$routes->get('rooms/create', 'RoomsController::create');
$routes->post('rooms', 'RoomsController::store');
$routes->post('rooms/store', 'RoomsController::store');
$routes->get('rooms/export', 'RoomsController::export');
$routes->get('rooms/(:segment)/edit', 'RoomsController::edit/$1');
$routes->post('rooms/(:segment)', 'RoomsController::update/$1');

// Master Peserta Didik (Students)
$routes->get('students', 'StudentsController::index', ['filter' => 'permission:students.view']);
$routes->post('students', 'StudentsController::store', ['filter' => 'permission:students.manage']);
$routes->post('students/(:num)/update', 'StudentsController::update/$1', ['filter' => 'permission:students.manage']);
$routes->post('students/(:num)/delete', 'StudentsController::delete/$1', ['filter' => 'permission:students.manage']);

// Import Staging Pipeline
$routes->get('imports/master', 'MasterImportController::index');
$routes->get('imports/master/template/(:segment)', 'MasterImportController::downloadTemplate/$1');
$routes->post('imports/master/upload', 'MasterImportController::upload');
$routes->get('imports/master/(:segment)', 'MasterImportController::showBatch/$1');
$routes->post('imports/master/(:segment)/apply', 'MasterImportController::apply/$1');

// Curriculum Versions & Structures (Milestone 3)
$routes->get('education', 'EducationFoundationController::dashboard', ['filter' => 'permission:regulations.view,curriculum_sources.view,graduate_profile.view,learning_outcomes.view,learning_objectives.view,learning_sequences.view,learning_packs.view,ksp.view']);
$routes->get('education/ksp', 'DigitalKspController::index', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp', 'DigitalKspController::store', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)', 'DigitalKspController::dashboard/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/transition', 'DigitalKspController::transition/$1', ['filter' => 'permission:ksp.review,ksp.approve,ksp.lock']);
$routes->post('education/ksp/(:segment)/sections/(:segment)', 'DigitalKspController::updateSection/$1/$2', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/context', 'DigitalKspController::context/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/context', 'DigitalKspController::storeContext/$1', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/vision-goals', 'DigitalKspController::visionGoals/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/vision-goals', 'DigitalKspController::storeGoal/$1', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/organization', 'DigitalKspController::organization/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/organization', 'DigitalKspController::storeOrganization/$1', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/evaluation', 'DigitalKspController::evaluation/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/evaluation', 'DigitalKspController::storeEvaluation/$1', ['filter' => 'permission:ksp.manage']);
$routes->post('education/ksp/(:segment)/evaluation/(:segment)/actions', 'DigitalKspController::storeImprovementAction/$1/$2', ['filter' => 'permission:ksp.manage']);
$routes->post('education/ksp/(:segment)/improvement-actions/(:segment)', 'DigitalKspController::updateImprovementAction/$1/$2', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/compliance', 'DigitalKspController::compliance/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/compliance', 'DigitalKspController::runCompliance/$1', ['filter' => 'permission:ksp.review']);
$routes->get('education/ksp/(:segment)/evidence', 'DigitalKspController::evidence/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/evidence', 'DigitalKspController::storeEvidence/$1', ['filter' => 'permission:ksp.manage']);
$routes->get('education/ksp/(:segment)/evidence/(:segment)/download', 'DigitalKspController::downloadEvidence/$1/$2', ['filter' => 'permission:ksp.view']);
$routes->get('education/ksp/(:segment)/documents', 'DigitalKspController::documents/$1', ['filter' => 'permission:ksp.view']);
$routes->post('education/ksp/(:segment)/documents', 'DigitalKspController::generateDocument/$1', ['filter' => 'permission:ksp.export']);
$routes->get('education/ksp/(:segment)/documents/(:segment)/download', 'DigitalKspController::downloadDocument/$1/$2', ['filter' => 'permission:ksp.export']);
$routes->get('references/regulations', 'EducationFoundationController::regulations', ['filter' => 'permission:regulations.view']);
$routes->post('references/regulations', 'EducationFoundationController::storeRegulation', ['filter' => 'permission:regulations.manage']);
$routes->get('references/curriculum-sources', 'EducationFoundationController::sources', ['filter' => 'permission:curriculum_sources.view']);
$routes->post('references/curriculum-sources', 'EducationFoundationController::storeSource', ['filter' => 'permission:curriculum_sources.manage']);
$routes->get('references/graduate-profile', 'EducationFoundationController::profile', ['filter' => 'permission:graduate_profile.view']);

$routes->get('curriculum/outcomes', 'EducationFoundationController::outcomes', ['filter' => 'permission:learning_outcomes.view']);
$routes->post('curriculum/outcomes', 'EducationFoundationController::storeOutcome', ['filter' => 'permission:learning_outcomes.manage']);
$routes->post('curriculum/outcomes/(:segment)', 'EducationFoundationController::updateOutcome/$1', ['filter' => 'permission:learning_outcomes.manage']);
$routes->post('curriculum/outcomes/(:segment)/elements', 'EducationFoundationController::addOutcomeElement/$1', ['filter' => 'permission:learning_outcomes.manage']);
$routes->get('curriculum/objectives', 'EducationFoundationController::objectives', ['filter' => 'permission:learning_objectives.view']);
$routes->post('curriculum/objectives', 'EducationFoundationController::storeObjective', ['filter' => 'permission:learning_objectives.manage']);
$routes->post('curriculum/objectives/(:segment)', 'EducationFoundationController::updateObjective/$1', ['filter' => 'permission:learning_objectives.manage']);
$routes->post('curriculum/objectives/(:segment)/adapt', 'EducationFoundationController::adaptObjective/$1', ['filter' => 'permission:learning_objectives.manage']);
$routes->post('curriculum/objectives/(:segment)/criteria', 'EducationFoundationController::addObjectiveCriterion/$1', ['filter' => 'permission:learning_objectives.manage']);
$routes->get('curriculum/sequences', 'EducationFoundationController::sequences', ['filter' => 'permission:learning_sequences.view']);
$routes->post('curriculum/sequences', 'EducationFoundationController::storeSequence', ['filter' => 'permission:learning_sequences.manage']);
$routes->post('curriculum/sequences/(:segment)/items', 'EducationFoundationController::addSequenceItem/$1', ['filter' => 'permission:learning_sequences.manage']);
$routes->post('curriculum/sequences/(:segment)/transition', 'EducationFoundationController::transitionSequence/$1', ['filter' => 'permission:learning_sequences.validate,learning_sequences.review,learning_sequences.approve,learning_sequences.lock']);
$routes->post('curriculum/sequences/(:segment)/clone', 'EducationFoundationController::cloneSequence/$1', ['filter' => 'permission:learning_sequences.manage']);
$routes->get('curriculum/coverage', 'EducationFoundationController::coverage', ['filter' => 'permission:learning_sequences.view']);
$routes->get('curriculum/learning-packs', 'EducationFoundationController::packs', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs', 'EducationFoundationController::storePack', ['filter' => 'permission:learning_packs.manage']);
$routes->post('curriculum/learning-packs/(:segment)/objectives', 'EducationFoundationController::attachPackObjective/$1', ['filter' => 'permission:learning_packs.manage']);
$routes->post('curriculum/learning-packs/(:segment)/sequences', 'EducationFoundationController::attachPackSequence/$1', ['filter' => 'permission:learning_packs.manage']);
$routes->get('curriculum/learning-packs/(:segment)', 'SubjectLearningPackController::detail/$1', ['filter' => 'permission:learning_packs.view']);
$routes->get('curriculum/learning-packs/(:segment)/overview', 'SubjectLearningPackController::detail/$1', ['filter' => 'permission:learning_packs.view']);
$routes->get('curriculum/learning-packs/(:segment)/structure', 'SubjectLearningPackController::structure/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/clone', 'SubjectLearningPackController::clonePack/$1', ['filter' => 'permission:learning_packs.clone']);
$routes->get('curriculum/learning-packs/(:segment)/units', 'SubjectLearningPackController::units/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/units', 'SubjectLearningPackController::storeUnit/$1', ['filter' => 'permission:learning_units.manage']);
$routes->post('curriculum/learning-packs/(:segment)/units/(:segment)/update', 'SubjectLearningPackController::updateUnit/$1/$2', ['filter' => 'permission:learning_units.manage']);
$routes->post('curriculum/learning-packs/(:segment)/units/(:segment)/delete', 'SubjectLearningPackController::deleteUnit/$1/$2', ['filter' => 'permission:learning_units.manage']);
$routes->get('curriculum/learning-packs/(:segment)/concepts', 'SubjectLearningPackController::concepts/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/concepts', 'SubjectLearningPackController::storeConcept/$1', ['filter' => 'permission:learning_units.manage']);
$routes->post('curriculum/learning-packs/(:segment)/concepts/(:segment)/update', 'SubjectLearningPackController::updateConcept/$1/$2', ['filter' => 'permission:learning_units.manage']);
$routes->post('curriculum/learning-packs/(:segment)/concepts/(:segment)/delete', 'SubjectLearningPackController::deleteConcept/$1/$2', ['filter' => 'permission:learning_units.manage']);
$routes->get('curriculum/learning-packs/(:segment)/activities', 'SubjectLearningPackController::activities/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/activities', 'SubjectLearningPackController::storeActivity/$1', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/update', 'SubjectLearningPackController::updateActivity/$1/$2', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/delete', 'SubjectLearningPackController::deleteActivity/$1/$2', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/resources', 'SubjectLearningPackController::attachActivityResource/$1/$2', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/resources/(:segment)/delete', 'SubjectLearningPackController::detachActivityResource/$1/$2/$3', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/alternatives', 'SubjectLearningPackController::addActivityAlternative/$1/$2', ['filter' => 'permission:learning_activities.manage']);
$routes->post('curriculum/learning-packs/(:segment)/activities/(:segment)/experiences', 'SubjectLearningPackController::addActivityExperience/$1/$2', ['filter' => 'permission:learning_activities.manage']);
$routes->get('curriculum/learning-packs/(:segment)/resources', 'SubjectLearningPackController::resources/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/resources', 'SubjectLearningPackController::storeResource/$1', ['filter' => 'permission:learning_resources.manage']);
$routes->post('curriculum/learning-packs/(:segment)/resources/(:segment)/update', 'SubjectLearningPackController::updateResource/$1/$2', ['filter' => 'permission:learning_resources.manage']);
$routes->post('curriculum/learning-packs/(:segment)/resources/(:segment)/delete', 'SubjectLearningPackController::deleteResource/$1/$2', ['filter' => 'permission:learning_resources.manage']);
$routes->get('curriculum/learning-packs/(:segment)/assessment', 'SubjectLearningPackController::assessment/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/assessment', 'SubjectLearningPackController::storeAssessment/$1', ['filter' => 'permission:learning_guidance.manage']);
$routes->post('curriculum/learning-packs/(:segment)/assessment/(:segment)/delete', 'SubjectLearningPackController::deleteAssessment/$1/$2', ['filter' => 'permission:learning_guidance.manage']);
$routes->get('curriculum/learning-packs/(:segment)/followup', 'SubjectLearningPackController::followup/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/followup', 'SubjectLearningPackController::storeFollowup/$1', ['filter' => 'permission:learning_guidance.manage']);
$routes->post('curriculum/learning-packs/(:segment)/followup/(:segment)/delete', 'SubjectLearningPackController::deleteFollowup/$1/$2', ['filter' => 'permission:learning_guidance.manage']);
$routes->get('curriculum/learning-packs/(:segment)/coverage', 'SubjectLearningPackController::coverage/$1', ['filter' => 'permission:learning_packs.view']);
$routes->get('curriculum/learning-packs/(:segment)/lineage', 'SubjectLearningPackController::lineage/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/lineage/imports', 'SubjectLearningPackController::stageImport/$1', ['filter' => 'permission:learning_packs.manage']);
$routes->post('curriculum/learning-packs/(:segment)/lineage/imports/(:segment)/apply', 'SubjectLearningPackController::applyImport/$1/$2', ['filter' => 'permission:learning_packs.manage']);
$routes->get('curriculum/learning-packs/(:segment)/history', 'SubjectLearningPackController::history/$1', ['filter' => 'permission:learning_packs.view']);
$routes->post('curriculum/learning-packs/(:segment)/transition', 'SubjectLearningPackController::transition/$1', ['filter' => 'permission:learning_packs.validate,learning_packs.review,learning_packs.approve,learning_packs.lock']);
$routes->get('curriculum/education-imports', 'EducationFoundationController::imports', ['filter' => 'permission:learning_outcomes.manage']);
$routes->post('curriculum/education-imports', 'EducationFoundationController::stageImport', ['filter' => 'permission:learning_outcomes.manage']);
$routes->post('curriculum/education-imports/(:segment)/apply', 'EducationFoundationController::applyImport/$1', ['filter' => 'permission:learning_outcomes.manage']);

// ====================================================================
// PHASE 4: LESSON PLAN ENGINE
// ====================================================================
$routes->get('lesson-plans', 'LessonPlanController::index', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/create', 'LessonPlanController::create', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans', 'LessonPlanController::store', ['filter' => 'permission:lesson_plans.manage']);
$routes->get('lesson-plans/(:segment)', 'LessonPlanController::detail/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/overview', 'LessonPlanController::detail/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/design', 'LessonPlanController::design/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/stages', 'LessonPlanController::stages/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/activities', 'LessonPlanController::activities/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/assessments', 'LessonPlanController::assessments/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->post('lesson-plans/(:segment)/design', 'LessonPlanController::updateDesign/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/objectives', 'LessonPlanController::addObjective/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/stages', 'LessonPlanController::addStage/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/activities', 'LessonPlanController::addActivity/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/assessments', 'LessonPlanController::addAssessment/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/transition', 'LessonPlanController::transition/$1', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/clone', 'LessonPlanController::clonePlan/$1', ['filter' => 'permission:lesson_plans.clone']);
$routes->post('lesson-plans/(:segment)/assessments/(:segment)/rubrics', 'LessonPlanController::addRubric/$1/$2', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/activities/(:segment)/resources', 'LessonPlanController::linkActivityResource/$1/$2', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/activities/(:segment)', 'LessonPlanController::updateActivity/$1/$2', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/rubrics/(:segment)/delete', 'LessonPlanController::deleteRubric/$1/$2', ['filter' => 'permission:lesson_plans.manage']);
$routes->post('lesson-plans/(:segment)/resources/(:segment)/delete', 'LessonPlanController::deleteActivityResource/$1/$2', ['filter' => 'permission:lesson_plans.manage']);
$routes->get('lesson-plans/(:segment)/validate', 'LessonPlanController::validatePlan/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/print', 'LessonPlanController::printPlan/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/export-docx', 'LessonPlanController::exportDocx/$1', ['filter' => 'permission:lesson_plans.view']);
$routes->get('lesson-plans/(:segment)/export-pdf', 'LessonPlanController::exportPdf/$1', ['filter' => 'permission:lesson_plans.view']);

// ====================================================================
// PHASE 5: DAILY TEACHING WORKSPACE & EXECUTION ENGINE
// ====================================================================
$routes->get('teaching', 'TeachingWorkspaceController::today', ['filter' => 'permission:teaching.workspace']);
$routes->get('teaching/today', 'TeachingWorkspaceController::today', ['filter' => 'permission:teaching.workspace']);
$routes->post('teaching/session/init', 'TeachingWorkspaceController::initSession', ['filter' => 'permission:teaching.teach']);
$routes->get('teaching/session/(:segment)', 'TeachingWorkspaceController::session/$1', ['filter' => 'permission:teaching.workspace']);
$routes->post('teaching/session/(:segment)/start', 'TeachingWorkspaceController::startSession/$1', ['filter' => 'permission:teaching.teach']);
$routes->post('teaching/session/(:segment)/complete', 'TeachingWorkspaceController::completeSession/$1', ['filter' => 'permission:teaching.teach']);
$routes->get('teaching/session/(:segment)/reflect', 'TeachingWorkspaceController::reflectView/$1', ['filter' => 'permission:teaching.reflect']);
$routes->post('teaching/session/(:segment)/reflect', 'TeachingWorkspaceController::saveReflection/$1', ['filter' => 'permission:teaching.reflect']);
$routes->post('teaching/session/(:segment)/activities/toggle', 'TeachingWorkspaceController::toggleActivity/$1', ['filter' => 'permission:teaching.teach']);
$routes->post('teaching/session/(:segment)/observations', 'TeachingWorkspaceController::addObservation/$1', ['filter' => 'permission:teaching.teach']);
$routes->post('teaching/session/(:segment)/observations/(:segment)/delete', 'TeachingWorkspaceController::deleteObservation/$1/$2', ['filter' => 'permission:teaching.teach']);
$routes->post('teaching/session/(:segment)/attendance/quick', 'TeachingWorkspaceController::quickAttendance/$1', ['filter' => 'permission:teaching.teach']);
$routes->post('teaching/session/(:segment)/link-plan', 'TeachingWorkspaceController::linkPlan/$1', ['filter' => 'permission:teaching.teach']);

// Assessment & Mastery Module (Phase 6)
$routes->get('assessment', 'AssessmentController::index', ['filter' => 'permission:assessment.view']);
$routes->get('assessment/create', 'AssessmentController::create', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment', 'AssessmentController::store', ['filter' => 'permission:assessment.manage']);
$routes->get('assessment/(:num)/edit', 'AssessmentController::edit/$1', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment/(:num)', 'AssessmentController::update/$1', ['filter' => 'permission:assessment.manage']);
$routes->get('assessment/(:num)', 'AssessmentController::detail/$1', ['filter' => 'permission:assessment.view']);
$routes->post('assessment/(:num)/transition', 'AssessmentController::transition/$1', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment/(:num)/delete', 'AssessmentController::destroy/$1', ['filter' => 'permission:assessment.manage']);
$routes->get('assessment/(:num)/gradebook', 'AssessmentController::gradebook/$1', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment/(:num)/gradebook', 'AssessmentController::saveGradebook/$1', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment/(:num)/evidence', 'AssessmentController::addEvidence/$1', ['filter' => 'permission:assessment.manage']);
$routes->post('assessment/(:num)/feedback', 'AssessmentController::addFeedback/$1', ['filter' => 'permission:assessment.manage']);
$routes->get('mastery', 'AssessmentController::mastery', ['filter' => 'permission:assessment.mastery']);
$routes->post('mastery/set', 'AssessmentController::setMastery', ['filter' => 'permission:assessment.mastery']);
$routes->post('mastery/recommend', 'AssessmentController::recommendInterventions', ['filter' => 'permission:assessment.mastery']);
$routes->get('interventions', 'AssessmentController::interventions', ['filter' => 'permission:assessment.mastery']);
$routes->post('interventions/(:num)', 'AssessmentController::updateIntervention/$1', ['filter' => 'permission:assessment.mastery']);
$routes->get('reporting-policies', 'AssessmentController::policies', ['filter' => 'permission:assessment.mastery']);
$routes->post('reporting-policies', 'AssessmentController::savePolicies', ['filter' => 'permission:assessment.mastery']);

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
$routes->post('curriculum/(:segment)/matrix/auto-trim', 'CurriculumMatrixController::autoTrim/$1');

$routes->get('curriculum/(:segment)', 'CurriculumController::show/$1');
$routes->post('curriculum/(:segment)/structures', 'CurriculumController::storeStructure/$1');
$routes->post('curriculum/(:segment)/planning-settings', 'CurriculumController::savePlanningSettings/$1');
$routes->post('curriculum/(:segment)/structures/(:segment)/delete', 'CurriculumController::deleteStructure/$1/$2');
$routes->get('curriculum/(:segment)/reconciliation', 'CurriculumController::reconciliation/$1');
$routes->get('curriculum/(:segment)/export', 'CurriculumController::export/$1');
$routes->post('curriculum/(:segment)/update', 'CurriculumController::updateVersion/$1');
$routes->post('curriculum/(:segment)/activate', 'CurriculumController::activate/$1');

// Elective Subjects & Selection Module
$routes->get('electives', 'ElectivesController::index');
$routes->get('electives/create', 'ElectivesController::create');
$routes->post('electives', 'ElectivesController::store');
$routes->get('electives/(:num)', 'ElectivesController::show/$1');
$routes->post('electives/(:num)/offerings', 'ElectivesController::addOffering/$1');
$routes->post('electives/(:num)/offerings/(:num)/delete', 'ElectivesController::removeOffering/$1/$2');
$routes->post('electives/(:num)/offerings/(:num)/update', 'ElectivesController::updateOffering/$1/$2');
$routes->post('electives/(:num)/offerings/(:num)/toggle-approval', 'ElectivesController::toggleOfferingApproval/$1/$2');
$routes->post('electives/(:num)/approve-all-eligible', 'ElectivesController::approveAllEligibleOfferings/$1');
$routes->post('electives/(:num)/update', 'ElectivesController::update/$1');
$routes->post('electives/(:num)/publish', 'ElectivesController::publish/$1');
$routes->get('electives/(:num)/selections/export', 'ElectivesController::exportSelections/$1');
$routes->post('electives/(:num)/enroll', 'ElectiveSelectionsController::enroll/$1');
$routes->post('electives/(:num)/students/sync-rombel', 'ElectiveSelectionsController::syncRombel/$1');
$routes->post('electives/(:num)/students/import', 'ElectiveSelectionsController::importStudents/$1');
$routes->get('electives/(:num)/students/template', 'ElectiveSelectionsController::downloadStudentsTemplate/$1');
$routes->post('electives/(:num)/students/(:num)/delete', 'ElectiveSelectionsController::deleteStudent/$1/$2');

$routes->get('my-electives', 'ElectiveSelectionsController::mySelection');
$routes->post('my-electives/save', 'ElectiveSelectionsController::saveMine');
$routes->post('my-electives/submit', 'ElectiveSelectionsController::submitMine');
$routes->post('my-electives/change-request', 'ElectiveSelectionsController::requestChangeMine');
$routes->get('electives/(:num)/select/(:num)', 'ElectiveSelectionsController::form/$1/$2');
$routes->post('electives/(:num)/select/(:num)', 'ElectiveSelectionsController::save/$1/$2');
$routes->post('electives/(:num)/select/(:num)/submit', 'ElectiveSelectionsController::submit/$1/$2');
$routes->get('electives/(:num)/students/(:num)/selection', 'ElectiveSelectionsController::form/$1/$2');
$routes->post('electives/(:num)/students/(:num)/selection/save', 'ElectiveSelectionsController::save/$1/$2');
$routes->post('electives/(:num)/students/(:num)/selection/submit', 'ElectiveSelectionsController::submit/$1/$2');
$routes->post('electives/(:num)/submissions/(:num)/review', 'ElectiveSelectionsController::review/$1/$2');
$routes->post('electives/(:num)/change-requests/(:num)/review', 'ElectiveSelectionsController::reviewChange/$1/$2');

// Read-only elective roster owned by the authenticated teacher.
$routes->get('portal/electives', 'TeacherElectivesController::index', ['filter' => 'permission:teacher_electives.view']);
$routes->get('portal/electives/export', 'TeacherElectivesController::export', ['filter' => 'permission:teacher_electives.view']);

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
$routes->get('assignments/(:segment)/documents/sk', 'AssignmentDocumentsController::collective/$1');
$routes->get('assignments/(:segment)/documents/teacher/(:num)', 'AssignmentDocumentsController::teacher/$1/$2');
$routes->get('assignments/(:segment)', 'AssignmentsController::show/$1');
$routes->post('assignments/(:segment)/store-assignment', 'AssignmentsController::storeAssignment/$1');
$routes->post('assignments/(:segment)/update-assignment/(:num)', 'AssignmentsController::updateAssignment/$1/$2');
$routes->post('assignments/(:segment)/delete-assignment/(:num)', 'AssignmentsController::deleteAssignment/$1/$2');
$routes->post('assignments/(:segment)/store-duty', 'AssignmentsController::storeDuty/$1');
$routes->post('assignments/(:segment)/update-duty/(:num)', 'AssignmentsController::updateDuty/$1/$2');
$routes->post('assignments/(:segment)/delete-duty/(:num)', 'AssignmentsController::deleteDuty/$1/$2');
$routes->post('assignments/(:segment)/auto-assign', 'AssignmentsController::autoAssign/$1');
$routes->get('assignments/(:segment)/matrix', 'AssignmentsController::matrix/$1');
$routes->get('assignments/(:segment)/export', 'AssignmentsController::export/$1');
$routes->post('assignments/(:segment)/workflow/(:segment)', 'AssignmentsController::workflowAction/$1/$2');
$routes->post('assignments/(:segment)/update', 'AssignmentsController::updateVersion/$1');
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

// School Routine Activities Module (Solution 2)
$routes->get('routine-activities', 'RoutineActivitiesController::index');
$routes->post('routine-activities', 'RoutineActivitiesController::store');
$routes->post('routine-activities/(:num)', 'RoutineActivitiesController::update/$1');
$routes->post('routine-activities/(:num)/delete', 'RoutineActivitiesController::delete/$1');

// Modul Jadwal Piket Guru (Sekolah Satu Atap SMP & SMA)
$routes->get('duty-schedules', 'DutySchedulesController::index');
$routes->post('duty-schedules/generate', 'DutySchedulesController::generate');
$routes->post('duty-schedules/store', 'DutySchedulesController::store');
$routes->post('duty-schedules/(:num)/delete', 'DutySchedulesController::delete/$1');
$routes->post('duty-schedules/clear', 'DutySchedulesController::clear');
$routes->get('duty-schedules/print', 'DutySchedulesController::print');

// Teacher Portal Routes
$routes->get('portal/schedule', 'TeacherPortalController::schedule', ['filter' => 'permission:teacher_schedule.view']);
$routes->get('portal/workload', 'TeacherPortalController::workload', ['filter' => 'permission:teacher_workload.view']);
$routes->get('portal/assignment-document', 'TeacherPortalController::assignmentDocument', ['filter' => 'permission:teacher_assignment_document.view']);
$routes->get('portal/duty-schedule', 'TeacherPortalController::dutySchedule', ['filter' => 'permission:teacher_duty_schedule.view']);
$routes->get('portal/classroom', 'HomeroomPortalController::classroom', ['filter' => 'permission:class_students.view']);

// Teacher Subject Attendance Portal Routes
$routes->get('portal/attendance', 'TeacherAttendanceController::index', ['filter' => 'permission:teacher_attendance.view,attendances.record']);
$routes->get('portal/attendance/record', 'TeacherAttendanceController::form', ['filter' => 'permission:attendances.record,teacher_attendance.view']);
$routes->get('portal/attendance/session/(:num)', 'TeacherAttendanceController::form/$1', ['filter' => 'permission:attendances.record,teacher_attendance.view']);
$routes->post('portal/attendance/save', 'TeacherAttendanceController::save', ['filter' => 'permission:attendances.record,teacher_attendance.view']);
$routes->post('portal/attendance/session/(:num)/delete', 'TeacherAttendanceController::delete/$1', ['filter' => 'permission:attendances.record,attendances.admin']);
$routes->get('portal/attendance/session/(:num)/print', 'TeacherAttendanceController::printJournal/$1', ['filter' => 'permission:teacher_attendance.view,attendances.view']);
$routes->get('portal/attendance/recap/print', 'TeacherAttendanceController::printRecap', ['filter' => 'permission:teacher_attendance.view,attendances.view']);

// Executive Superadmin Attendance Monitoring Routes
$routes->get('attendances', 'AttendancesController::index', ['filter' => 'permission:attendances.view']);
$routes->get('attendances/export', 'AttendancesController::export', ['filter' => 'permission:attendances.admin']);
$routes->get('attendances/print-unit-report', 'AttendancesController::printUnitReport', ['filter' => 'permission:attendances.view']);
$routes->get('attendances/print-blank-sheet', 'AttendancesController::printBlankSheet', ['filter' => 'permission:attendances.view']);
$routes->get('attendances/(:num)', 'AttendancesController::show/$1', ['filter' => 'permission:attendances.view']);
$routes->post('attendances/(:num)/verify', 'AttendancesController::verify/$1', ['filter' => 'permission:attendances.admin']);
$routes->post('attendances/(:num)/reopen', 'AttendancesController::reopen/$1', ['filter' => 'permission:attendances.admin']);
$routes->post('attendances/bulk-verify', 'AttendancesController::bulkVerify', ['filter' => 'permission:attendances.admin']);

// Scheduling System (Milestone 5)
$routes->group('schedules', ['filter' => 'permission:schedules.view,class_schedule.view'], static function ($routes) {
    $routes->get('/', 'SchedulesController::index');
    $routes->get('substitutions', 'TeacherScheduleSubstitutionsController::index', ['filter' => 'permission:schedules.view']);
    $routes->post('substitutions', 'TeacherScheduleSubstitutionsController::store', ['filter' => 'permission:schedules.manage']);
    $routes->post('substitutions/(:num)', 'TeacherScheduleSubstitutionsController::update/$1', ['filter' => 'permission:schedules.manage']);
    $routes->post('substitutions/(:num)/toggle', 'TeacherScheduleSubstitutionsController::toggle/$1', ['filter' => 'permission:schedules.manage']);
    $routes->post('substitutions/(:num)/repair/analyze', 'TeacherScheduleSubstitutionsController::analyzeRepair/$1', ['filter' => 'permission:schedules.manage']);
    $routes->post('substitutions/(:num)/repair/(:num)/apply', 'TeacherScheduleSubstitutionsController::applyRepair/$1/$2', ['filter' => 'permission:schedules.manage']);
    $routes->post('create', 'SchedulesController::create', ['filter' => 'permission:schedules.manage']);
    $routes->post('(:num)/update', 'SchedulesController::update/$1', ['filter' => 'permission:schedules.manage']);
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
    $routes->get('(:num)/reports/classroom/(:num)', 'ScheduleReportsController::classroomReport/$1/$2', ['filter' => 'permission:schedules.export,class_schedule.view']);
    $routes->get('(:num)/reports/teacher/(:num)', 'ScheduleReportsController::teacherReport/$1/$2', ['filter' => 'permission:schedules.export']);
    $routes->get('(:num)/reports/unit/(:num)', 'ScheduleReportsController::unitReport/$1/$2', ['filter' => 'permission:schedules.export']);
    $routes->get('(:num)/reports/multi-unit', 'ScheduleReportsController::multiUnitReport/$1', ['filter' => 'permission:schedules.export']);
});
