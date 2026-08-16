<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>> [filter_name => classname]
     *                                                     or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'                    => CSRF::class,
        'toolbar'                 => DebugToolbar::class,
        'honeypot'                => Honeypot::class,
        'invalidchars'            => InvalidChars::class,
        'secureheaders'           => SecureHeaders::class,
        'auth'                    => \App\Filters\AuthFilter::class,
        'guest'                   => \App\Filters\GuestFilter::class,
        'permission'              => \App\Filters\PermissionFilter::class,
        'unit_access'             => \App\Filters\UnitAccessFilter::class,
        'password_change_required'=> \App\Filters\PasswordChangeRequiredFilter::class,
        'dev_only'                => \App\Filters\DevelopmentOnlyFilter::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            'csrf',
            // 'invalidchars',
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            'secureheaders',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        if (ENVIRONMENT === 'production') {
            if (($key = array_search('toolbar', $this->globals['after'], true)) !== false) {
                unset($this->globals['after'][$key]);
                $this->globals['after'] = array_values($this->globals['after']);
            }
        }
        if (ENVIRONMENT === 'testing' && empty($GLOBALS['enable_csrf_testing'])) {
            if (($key = array_search('csrf', $this->globals['before'], true)) !== false) {
                unset($this->globals['before'][$key]);
                $this->globals['before'] = array_values($this->globals['before']);
            }
        }
    }

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'auth' => [
            'before' => [
                '/',
                'dashboard',
                'dashboard/*',
                'settings',
                'settings/*',
                'academic-years',
                'academic-years/*',
                'academic-periods',
                'academic-periods/*',
                'academic-calendar',
                'academic-calendar/*',
                'attendances',
                'attendances/*',
                'users',
                'users/*',
                'roles',
                'roles/*',
                'audit',
                'audit/*',
                'context/*',
                'teachers',
                'teachers/*',
                'subjects',
                'subjects/*',
                'grade-levels',
                'grade-levels/*',
                'classrooms',
                'classrooms/*',
                'students',
                'students/*',
                'rooms',
                'rooms/*',
                'imports/master',
                'imports/master/*',
                'duplicates',
                'duplicates/*',
                'references',
                'references/*',
                'curriculum',
                'curriculum/*',
                'assignments',
                'assignments/*',
                'workloads',
                'workloads/*',
                'duties',
                'duties/*',
                'routine-activities',
                'routine-activities/*',
                'duty-schedules',
                'duty-schedules/*',
                'schedules',
                'schedules/*',
                'electives',
                'electives/*',
                'my-electives',
                'my-electives/*',
                'portal',
                'portal/*',
            ]
        ],
        'unit_access' => [
            'before' => [
                '/',
                'dashboard',
                'dashboard/*',
                'settings',
                'settings/*',
                'academic-years',
                'academic-years/*',
                'academic-periods',
                'academic-periods/*',
                'academic-calendar',
                'academic-calendar/*',
                'attendances',
                'attendances/*',
                'users',
                'users/*',
                'roles',
                'roles/*',
                'audit',
                'audit/*',
                'context/*',
                'teachers',
                'teachers/*',
                'subjects',
                'subjects/*',
                'grade-levels',
                'grade-levels/*',
                'classrooms',
                'classrooms/*',
                'students',
                'students/*',
                'rooms',
                'rooms/*',
                'imports/master',
                'imports/master/*',
                'duplicates',
                'duplicates/*',
                'references',
                'references/*',
                'curriculum',
                'curriculum/*',
                'assignments',
                'assignments/*',
                'workloads',
                'workloads/*',
                'duties',
                'duties/*',
                'routine-activities',
                'routine-activities/*',
                'duty-schedules',
                'duty-schedules/*',
                'schedules',
                'schedules/*',
                'electives',
                'electives/*',
                'my-electives',
                'my-electives/*',
                'portal',
                'portal/*',
            ]
        ],
        'password_change_required' => [
            'before' => [
                '/',
                'dashboard',
                'dashboard/*',
                'settings',
                'settings/*',
                'academic-years',
                'academic-years/*',
                'academic-periods',
                'academic-periods/*',
                'academic-calendar',
                'academic-calendar/*',
                'attendances',
                'attendances/*',
                'users',
                'users/*',
                'roles',
                'roles/*',
                'audit',
                'audit/*',
                'context/*',
                'teachers',
                'teachers/*',
                'subjects',
                'subjects/*',
                'grade-levels',
                'grade-levels/*',
                'classrooms',
                'classrooms/*',
                'students',
                'students/*',
                'rooms',
                'rooms/*',
                'imports/master',
                'imports/master/*',
                'duplicates',
                'duplicates/*',
                'references',
                'references/*',
                'curriculum',
                'curriculum/*',
                'assignments',
                'assignments/*',
                'workloads',
                'workloads/*',
                'duties',
                'duties/*',
                'routine-activities',
                'routine-activities/*',
                'duty-schedules',
                'duty-schedules/*',
                'schedules',
                'schedules/*',
                'electives',
                'electives/*',
                'my-electives',
                'my-electives/*',
                'portal',
                'portal/*',
            ]
        ]
    ];
}
