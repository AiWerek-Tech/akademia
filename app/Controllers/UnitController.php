<?php

namespace App\Controllers;

use App\Models\SchoolUnitModel;
use App\Services\AuditService;
use Config\Database;

class UnitController extends BaseController
{
    public function index()
    {
        if (!has_permission('units.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $userId = session()->get('user_id');
        $db = Database::connect();

        // Get units user has access to
        $units = $db->table('user_unit_access uua')
            ->select('su.*, uua.access_level')
            ->join('school_units su', 'su.id = uua.unit_id')
            ->where('uua.user_id', $userId)
            ->where('su.deleted_at', null)
            ->get()
            ->getResultArray();

        return view('units/index', [
            'title'             => 'Daftar Unit Sekolah',
            'breadcrumb_active' => 'Unit Sekolah',
            'units'             => $units
        ]);
    }

    public function edit(string $uuid)
    {
        if (!has_permission('units.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $unitModel = new SchoolUnitModel();
        $unit = $unitModel->where('uuid', $uuid)->first();
        
        if (!$unit) {
            return redirect()->to('/settings/units')->with('error', 'Unit tidak ditemukan.');
        }

        return view('units/edit', [
            'title'             => 'Edit Profil Unit',
            'breadcrumb_active' => 'Edit Unit',
            'unit'              => $unit,
            'timezones'         => \DateTimeZone::listIdentifiers()
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('units.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $unitModel = new SchoolUnitModel();
        $unit = $unitModel->where('uuid', $uuid)->first();

        if (!$unit) {
            return redirect()->to('/settings/units')->with('error', 'Unit tidak ditemukan.');
        }

        $rules = [
            'name'       => 'required|min_length[3]|max_length[150]',
            'short_name' => 'required|min_length[2]|max_length[50]',
            'email'      => 'permit_empty|valid_email|max_length[150]',
            'phone'      => 'permit_empty|max_length[30]',
            'timezone'   => 'required|in_list[' . implode(',', \DateTimeZone::listIdentifiers()) . ']',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $before = $unit;
        $after = [
            'name'       => $this->request->getPost('name'),
            'short_name' => $this->request->getPost('short_name'),
            'npsn'       => $this->request->getPost('npsn') ?: null,
            'address'    => $this->request->getPost('address') ?: null,
            'phone'      => $this->request->getPost('phone') ?: null,
            'email'      => $this->request->getPost('email') ?: null,
            'timezone'   => $this->request->getPost('timezone'),
            'updated_by' => session()->get('user_id')
        ];

        $unitModel->update($unit['id'], $after);

        // Audit log
        AuditService::log(
            'units',
            'update',
            'SchoolUnit',
            $unit['id'],
            $before,
            $after,
            'Profil unit diperbarui oleh pengguna'
        );

        return redirect()->to('/settings/units')->with('success', "Profil unit {$unit['code']} berhasil diperbarui.");
    }
}
