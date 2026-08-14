<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'username', 'email', 'full_name', 'password_hash', 
        'is_active', 'must_change_password', 'must_change_username', 'failed_login_count',
        'locked_until', 'last_login_at', 'last_login_ip', 'password_changed_at', 'username_changed_at',
        'created_by', 'updated_by', 'teacher_id', 'classroom_id'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        return $data;
    }

    /**
     * Gets all permissions for a user across roles
     */
    public function getPermissions(int $userId, ?int $unitId = null): array
    {
        $db = $this->db;
        $builder = $db->table('user_roles ur')
            ->distinct()
            ->select('p.code')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->groupStart()
                ->where('ur.valid_from IS NULL')
                ->orWhere('ur.valid_from <=', date('Y-m-d'))
            ->groupEnd()
            ->groupStart()
                ->where('ur.valid_until IS NULL')
                ->orWhere('ur.valid_until >=', date('Y-m-d'))
            ->groupEnd();

        if ($unitId !== null) {
            $builder->groupStart()
                ->where('ur.unit_id', $unitId)
                ->orWhere('ur.unit_id IS NULL')
                ->groupEnd();
        }

        $rows = $builder->get()->getResultArray();
        return array_column($rows, 'code');
    }

    /**
     * Resolves active roles for a unit and chooses a deterministic display role.
     *
     * @return array{primary: array{code:string,name:string}, codes:list<string>}
     */
    public function getRoleContext(int $userId, ?int $unitId = null): array
    {
        $builder = $this->db->table('user_roles ur')
            ->distinct()
            ->select('r.code, r.name')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->groupStart()
                ->where('ur.valid_from IS NULL')
                ->orWhere('ur.valid_from <=', date('Y-m-d'))
            ->groupEnd()
            ->groupStart()
                ->where('ur.valid_until IS NULL')
                ->orWhere('ur.valid_until >=', date('Y-m-d'))
            ->groupEnd();

        if ($unitId !== null) {
            $builder->groupStart()
                ->where('ur.unit_id', $unitId)
                ->orWhere('ur.unit_id IS NULL')
                ->groupEnd();
        }

        $roles = $builder->get()->getResultArray();
        $priority = [
            'super_admin', 'superadmin', 'admin_smp', 'admin_sma',
            'kepala_sekolah', 'wakasek_kurikulum', 'tata_usaha',
            'wali_kelas', 'guru', 'viewer_yayasan',
        ];

        usort($roles, static function (array $left, array $right) use ($priority): int {
            $leftRank = array_search($left['code'], $priority, true);
            $rightRank = array_search($right['code'], $priority, true);
            return ($leftRank === false ? PHP_INT_MAX : $leftRank)
                <=> ($rightRank === false ? PHP_INT_MAX : $rightRank);
        });

        $primary = $roles[0] ?? ['code' => 'visitor', 'name' => 'Visitor'];

        return [
            'primary' => ['code' => $primary['code'], 'name' => $primary['name']],
            'codes'   => array_values(array_unique(array_column($roles, 'code'))),
        ];
    }
}
