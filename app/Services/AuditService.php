<?php

namespace App\Services;

use Config\Database;
use Config\Services;

class AuditService
{
    private static array $sensitiveKeys = [
        'password',
        'password_hash',
        'password_confirm',
        'password_confirmation',
        'token',
        'session',
        'csrf',
        'csrf_test_name',
        'credential',
        'secret'
    ];

    /**
     * Write an audit log entry to the database
     */
    public static function log(
        string $module,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?string $batchUuid = null
    ): void {
        $db = Database::connect();
        
        // Use services if SAPI is not CLI or safe fallback
        $session = is_cli() ? null : Services::session();
        $request = Services::request();

        $userId   = $session ? $session->get('user_id') : null;
        $unitId   = $session ? $session->get('active_unit_id') : null;
        $periodId = $session ? $session->get('active_period_id') : null;

        // Sanitize data
        $beforeSanitized = self::sanitize($before);
        $afterSanitized  = self::sanitize($after);

        $data = [
            'uuid'               => UuidService::v4(),
            'user_id'            => $userId,
            'unit_id'            => $unitId,
            'academic_period_id' => $periodId,
            'module'             => $module,
            'action'             => $action,
            'entity_type'        => $entityType,
            'entity_id'          => $entityId,
            'before_json'        => $beforeSanitized ? json_encode($beforeSanitized) : null,
            'after_json'         => $afterSanitized ? json_encode($afterSanitized) : null,
            'reason'             => $reason,
            'ip_address'         => method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '127.0.0.1',
            'user_agent'         => method_exists($request, 'getUserAgent') ? substr($request->getUserAgent()->getAgentString(), 0, 255) : 'CLI/Console',
            'batch_uuid'         => $batchUuid,
            'created_at'         => date('Y-m-d H:i:s')
        ];

        $db->table('audit_logs')->insert($data);
    }

    /**
     * Recursively sanitizes sensitive parameters to protect user privacy
     */
    private static function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}
