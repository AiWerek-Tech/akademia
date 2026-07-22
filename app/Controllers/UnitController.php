<?php

namespace App\Controllers;

use App\Models\SchoolUnitModel;
use App\Services\AuditService;
use App\Services\UnitScopeService;
use Config\Database;

class UnitController extends BaseController
{
    public function index()
    {
        if (!has_permission('units.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = Database::connect();
        $accessibleIds = UnitScopeService::accessibleUnitIds();
        $units = $accessibleIds === [] ? [] : $db->table('school_units su')
            ->select('su.*, uua.access_level')
            ->join('user_unit_access uua', 'uua.unit_id = su.id AND uua.user_id = ' . (int) session()->get('user_id'))
            ->whereIn('su.id', $accessibleIds)
            ->where('su.deleted_at IS NULL')
            ->orderBy('su.name', 'ASC')
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
        try {
            UnitScopeService::assertUnit((int) $unit['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/settings/units')->with('error', $e->getMessage());
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
        try {
            UnitScopeService::assertUnit((int) $unit['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/settings/units')->with('error', $e->getMessage());
        }

        $rules = [
            'name'       => 'required|min_length[3]|max_length[150]',
            'short_name' => 'required|min_length[2]|max_length[50]',
            'email'      => 'permit_empty|valid_email|max_length[150]',
            'phone'      => 'permit_empty|max_length[30]',
            'npsn'       => 'permit_empty|numeric|min_length[8]|max_length[12]',
            'address'    => 'permit_empty|max_length[500]',
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

        if (!$unitModel->update($unit['id'], $after)) {
            return redirect()->back()->withInput()->with('errors', $unitModel->errors());
        }

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
