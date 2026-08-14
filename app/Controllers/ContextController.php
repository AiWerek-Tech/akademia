<?php

namespace App\Controllers;

use Config\Database;
use App\Services\AuditService;

class ContextController extends BaseController
{
    public function changeUnit()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userId = session()->get('user_id');
        $unitId = (int)$this->request->getPost('unit_id');

        if ($unitId <= 0) {
            return redirect()->back()->with('error', 'Pilihan unit sekolah tidak valid.');
        }

        $db = Database::connect();

        // Validate unit access
        $accessibleUnitIds = \App\Services\UnitScopeService::accessibleUnitIds($userId);
        if (!in_array($unitId, $accessibleUnitIds, true)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke unit sekolah tersebut.');
        }

        $beforeUnitId = session()->get('active_unit_id');

        $userModel = new \App\Models\UserModel();
        $roleContext = $userModel->getRoleContext($userId, $unitId);
        $roleCode = $roleContext['primary']['code'];
        $roleName = $roleContext['primary']['name'];

        // Recalculate permissions
        $permissions = $userModel->getPermissions($userId, $unitId);

        // Update session
        session()->set([
            'active_unit_id' => $unitId,
            'role_code'      => $roleCode,
            'role_name'      => $roleName,
            'all_role_codes' => $roleContext['codes'],
            'permissions'    => $permissions
        ]);

        // Log audit
        AuditService::log(
            'auth',
            'change_unit_context',
            'SchoolUnit',
            $unitId,
            ['active_unit_id' => $beforeUnitId],
            ['active_unit_id' => $unitId],
            'User switched active school unit context'
        );

        return redirect()->back()->with('success', 'Unit aktif berhasil diubah.');
    }

    public function changePeriod()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $periodId = (int)$this->request->getPost('period_id');

        $db = Database::connect();

        // Validate period exists
        $period = $db->table('academic_periods')->where('id', $periodId)->get()->getRowArray();
        if (!$period) {
            return redirect()->back()->with('error', 'Periode akademik tidak valid.');
        }

        $beforePeriodId = session()->get('active_period_id');

        // Update session
        session()->set('active_period_id', $periodId);

        // Log audit
        AuditService::log(
            'auth',
            'change_period_context',
            'AcademicPeriod',
            $periodId,
            ['active_period_id' => $beforePeriodId],
            ['active_period_id' => $periodId],
            'User switched active academic period context'
        );

        return redirect()->back()->with('success', 'Periode akademik aktif berhasil diubah.');
    }
}
