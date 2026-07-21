<?php

namespace App\Services;

use Config\Database;

class SettingsService
{
    private static array $cache = [];

    /**
     * Parse dot-notated key into group and key
     */
    private static function parseKey(string $key, ?string $group): array
    {
        if ($group === null && strpos($key, '.') !== false) {
            list($group, $key) = explode('.', $key, 2);
        }
        return [$group ?? 'general', $key];
    }

    /**
     * Gets a configuration value, parsing dot-notated keys automatically
     */
    public static function get(string $key, ?string $group = null, $default = null)
    {
        list($group, $key) = self::parseKey($key, $group);
        $cacheKey = "{$group}.{$key}";

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $db = Database::connect();
        $row = $db->table('application_settings')
            ->where('setting_group', $group)
            ->where('setting_key', $key)
            ->get()
            ->getRowArray();

        if (!$row) {
            return $default;
        }

        $value = self::castValue($row['setting_value'], $row['value_type']);
        self::$cache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Updates a setting value, casts correctly, logs to audit
     */
    public static function set(string $key, $value, ?string $group = null, ?int $userId = null): bool
    {
        list($group, $key) = self::parseKey($key, $group);
        $cacheKey = "{$group}.{$key}";

        $db = Database::connect();
        $row = $db->table('application_settings')
            ->where('setting_group', $group)
            ->where('setting_key', $key)
            ->get()
            ->getRowArray();

        if (!$row) {
            return false;
        }

        $before = ['value' => $row['setting_value']];
        $after  = ['value' => (string)$value];

        $db->table('application_settings')->where('id', $row['id'])->update([
            'setting_value' => (string)$value,
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => $userId
        ]);

        self::$cache[$cacheKey] = self::castValue((string)$value, $row['value_type']);

        // Log audit trail
        AuditService::log(
            'settings',
            'update_setting',
            'ApplicationSetting',
            $row['id'],
            $before,
            $after,
            "Application setting {$group}.{$key} updated"
        );

        return true;
    }

    /**
     * Casts raw string values into target datatypes
     */
    private static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            default:
                return $value;
        }
    }

    /**
     * Fetches all settings belonging to a specific group
     */
    public static function getGroup(string $group): array
    {
        $db = Database::connect();
        $rows = $db->table('application_settings')->where('setting_group', $group)->get()->getResultArray();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = self::castValue($row['setting_value'], $row['value_type']);
        }
        return $settings;
    }
}
