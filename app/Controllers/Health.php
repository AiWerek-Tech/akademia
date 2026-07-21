<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use Config\Database;
use Exception;

class Health extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $health = [
            'status'     => 'OK',
            'timestamp'  => date('Y-m-d H:i:s'),
            'php_version'=> PHP_VERSION,
            'checks'     => [
                'database' => 'UNKNOWN',
                'writable' => 'UNKNOWN',
            ]
        ];

        // 1. Database Connection Check
        try {
            $db = Database::connect();
            $db->initialize();
            $health['checks']['database'] = 'OK';
        } catch (Exception $e) {
            $health['status'] = 'ERROR';
            $health['checks']['database'] = 'FAIL';
            log_message('error', 'Health check database failure: {message}', ['message' => $e->getMessage()]);
        }

        // 2. Writable Folder Write Permission Check
        $writablePath = WRITEPATH;
        if (is_writable($writablePath)) {
            $health['checks']['writable'] = 'OK';
        } else {
            $health['status'] = 'ERROR';
            $health['checks']['writable'] = 'FAIL (Directory not writable)';
        }

        if ($health['status'] === 'ERROR') {
            return $this->respond($health, 500);
        }

        return $this->respond($health, 200);
    }
}
