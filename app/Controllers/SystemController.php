<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class SystemController extends BaseController
{
    public function runtime()
    {
        // Only active in development environment
        if (ENVIRONMENT !== 'development') {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();

        return $this->response->setJSON([
            'PHP_VERSION' => PHP_VERSION,
            'PHP_SAPI' => PHP_SAPI,
            'CI_VERSION' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'environment' => ENVIRONMENT,
            'database_driver' => $db->getPlatform(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4'
        ]);
    }
}
