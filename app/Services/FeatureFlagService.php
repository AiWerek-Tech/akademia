<?php

namespace App\Services;

use Config\Database;

class FeatureFlagService
{
    private static array $cache = [];

    /**
     * Checks if a specific feature flag is enabled
     */
    public static function isEnabled(string $code): bool
    {
        if (isset(self::$cache[$code])) {
            return self::$cache[$code];
        }

        $db = Database::connect();
        $row = $db->table('feature_flags')->where('code', $code)->get()->getRowArray();
        
        $enabled = $row ? (bool)$row['enabled'] : false;
        self::$cache[$code] = $enabled;
        
        return $enabled;
    }

    /**
     * Updates the status of a feature flag and records an audit log entry
     */
    public static function set(string $code, bool $enabled, ?int $userId = null): bool
    {
        $db = Database::connect();
        $row = $db->table('feature_flags')->where('code', $code)->get()->getRowArray();
        
        if (!$row) {
            return false;
        }

        $before = ['enabled' => (bool)$row['enabled']];
        $after  = ['enabled' => $enabled];

        $db->table('feature_flags')->where('id', $row['id'])->update([
            'enabled'    => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId
        ]);

        self::$cache[$code] = $enabled;

        // Log audit trail
        AuditService::log(
            'settings',
            'update_feature_flag',
            'FeatureFlag',
            $row['id'],
            $before,
            $after,
            "Feature flag {$code} changed to " . ($enabled ? 'enabled' : 'disabled')
        );

        return true;
    }

    /**
     * Fetches all registered feature flags
     */
    public static function getAll(): array
    {
        $db = Database::connect();
        return $db->table('feature_flags')->get()->getResultArray();
    }
}
