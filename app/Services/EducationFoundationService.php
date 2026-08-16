<?php

namespace App\Services;

use App\Exceptions\ConcurrencyException;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class EducationFoundationService
{
    public static function actorId(): ?int
    {
        $id = is_cli() ? 0 : (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    public static function requireFields(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new InvalidArgumentException("Field {$field} wajib diisi.");
            }
        }
    }

    public static function atomicUpdate(string $table, array $current, array $changes): array
    {
        if (!isset($current['revision_number'])) {
            throw new RuntimeException("Entitas {$table} tidak mendukung revision number.");
        }
        $expected = isset($changes['revision_number']) ? (int) $changes['revision_number'] : (int) $current['revision_number'];
        if ($expected !== (int) $current['revision_number']) {
            throw new ConcurrencyException('Data telah diubah oleh pengguna lain. Muat ulang halaman sebelum menyimpan.');
        }
        unset($changes['revision_number']);
        $changes['revision_number'] = $expected + 1;
        $changes['updated_at'] = date('Y-m-d H:i:s');
        $changes['updated_by'] = self::actorId();
        $db = Database::connect();
        $db->table($table)->where('id', (int) $current['id'])->where('revision_number', $expected)->update($changes);
        if ($db->affectedRows() !== 1) {
            throw new ConcurrencyException('Konflik versi terdeteksi. Perubahan tidak disimpan.');
        }
        return $db->table($table)->where('id', (int) $current['id'])->get()->getRowArray();
    }

    public static function byUuid(string $table, string $uuid): array
    {
        $row = Database::connect()->table($table)->where('uuid', $uuid)->get()->getRowArray();
        if (!$row) {
            throw new RuntimeException('Data tidak ditemukan.');
        }
        return $row;
    }
}

