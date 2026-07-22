<?php

namespace App\Services;

use App\Models\RoomModel;
use App\Models\RoomTypeModel;
use Config\Database;

class RoomService
{
    /**
     * Get list of rooms with unit / shared filters
     */
    public static function getRooms(array $filters = [], int $perPage = 20): array
    {
        $model = new RoomModel();
        $builder = $model->select('rooms.*, room_types.code as room_type_code, room_types.name as room_type_name, school_units.name as unit_name, school_units.code as unit_code')
            ->join('room_types', 'room_types.id = rooms.room_type_id')
            ->join('school_units', 'school_units.id = rooms.unit_id', 'left')
            ->where('rooms.deleted_at IS NULL');

        if (!empty($filters['unit_id'])) {
            $unitId = (int)$filters['unit_id'];
            $builder->groupStart()
                ->where('rooms.unit_id', $unitId)
                ->orWhere('rooms.shared_between_units', 1)
                ->groupEnd();
        } elseif (!empty($filters['unit_ids'])) {
            $builder->groupStart()
                ->whereIn('rooms.unit_id', $filters['unit_ids'])
                ->orWhere('rooms.shared_between_units', 1)
                ->groupEnd();
        }

        if (!empty($filters['room_type_id'])) {
            $builder->where('rooms.room_type_id', $filters['room_type_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('rooms.is_active', (int)$filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('rooms.code', $search)
                ->orLike('rooms.name', $search)
                ->orLike('rooms.location', $search)
                ->groupEnd();
        }

        $rooms = $builder->orderBy('rooms.code', 'ASC')->paginate($perPage);

        return [
            'data'  => $rooms,
            'pager' => $model->pager,
        ];
    }

    /**
     * Create room record
     */
    public static function createRoom(array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $model = new RoomModel();

            // Validate room_type_id
            $typeModel = new RoomTypeModel();
            $roomType = $typeModel->find($data['room_type_id']);
            if (!$roomType || (int)$roomType['is_active'] !== 1) {
                throw new \InvalidArgumentException('Jenis ruang tidak valid atau tidak aktif.');
            }

            // Shared validation: non-shared room must have unit_id
            $isShared = !empty($data['shared_between_units']) ? 1 : 0;
            if (!$isShared && empty($data['unit_id'])) {
                throw new \InvalidArgumentException('Ruang non-shared wajib dikaitkan dengan unit sekolah.');
            }

            // Code uniqueness check
            $code = strtoupper(trim($data['code']));
            $existing = $model->where('code', $code)->where('deleted_at IS NULL')->first();
            if ($existing) {
                throw new \InvalidArgumentException('Kode ruang ' . $code . ' sudah terdaftar.');
            }

            // Capacity non-negative check
            if (isset($data['capacity']) && (int)$data['capacity'] < 0) {
                throw new \InvalidArgumentException('Kapasitas ruang tidak boleh bernilai negatif.');
            }

            $data['code']                 = $code;
            $data['shared_between_units'] = $isShared;
            $data['unit_id']              = $isShared ? ($data['unit_id'] ?? null) : $data['unit_id'];
            $data['facilities_json']      = is_array($data['facilities'] ?? null) ? json_encode($data['facilities']) : ($data['facilities_json'] ?? null);
            unset($data['facilities']);
            $data['is_active']            = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['revision_number']      = 1;
            $data['created_by']           = session()->get('user_id');

            $id = $model->insert($data);
            $room = $model->find($id);

            AuditService::log('rooms', 'CREATE', 'Room', $id, null, $room, 'Create room master data');

            $db->transCommit();
            return $room;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Create room failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update room record with Optimistic Locking check
     */
    public static function updateRoom(string $uuid, array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $model = new RoomModel();
            $existing = $model->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

            if (!$existing) {
                throw new \RuntimeException('Data ruang sekolah tidak ditemukan.');
            }

            // Optimistic locking
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$existing['revision_number']) {
                throw new \RuntimeException('Stale Data Error: Ruang sekolah ini telah diperbarui oleh pengguna lain.');
            }

            if (!empty($data['code'])) {
                $code = strtoupper(trim($data['code']));
                if ($code !== $existing['code']) {
                    $dup = $model->where('code', $code)->where('id !=', $existing['id'])->where('deleted_at IS NULL')->first();
                    if ($dup) {
                        throw new \InvalidArgumentException('Kode ruang ' . $code . ' sudah terdaftar.');
                    }
                    $data['code'] = $code;
                }
            }

            $isShared = isset($data['shared_between_units']) ? ($data['shared_between_units'] ? 1 : 0) : $existing['shared_between_units'];
            if (!$isShared && empty($data['unit_id']) && empty($existing['unit_id'])) {
                throw new \InvalidArgumentException('Ruang non-shared wajib dikaitkan dengan unit sekolah.');
            }

            if (isset($data['facilities']) && is_array($data['facilities'])) {
                $data['facilities_json'] = json_encode($data['facilities']);
                unset($data['facilities']);
            }

            $data['shared_between_units'] = $isShared;
            $data['revision_number']      = ((int)$existing['revision_number']) + 1;
            $data['updated_by']           = session()->get('user_id');

            $db->table('rooms')
                ->where('id', $existing['id'])
                ->where('revision_number', $existing['revision_number'])
                ->update($data);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Data ruang telah diubah oleh pengguna lain. Silakan muat ulang halaman.');
            }
            $updated = $model->find($existing['id']);

            AuditService::log('rooms', 'UPDATE', 'Room', $existing['id'], $existing, $updated, 'Update room master data');

            $db->transCommit();
            return $updated;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Update room failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
