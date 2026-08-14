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
            'head_name'  => 'permit_empty|max_length[150]',
            'head_identifier' => 'permit_empty|max_length[80]',
            'document_city'   => 'permit_empty|max_length[80]',
            'decree_prefix'   => 'permit_empty|max_length[50]',
            'timezone'        => 'required|in_list[' . implode(',', \DateTimeZone::listIdentifiers()) . ']',
        ];

        $logoFiles = ['logo' => $this->request->getFile('logo'), 'logo_right' => $this->request->getFile('logo_right')];
        foreach ($logoFiles as $field => $file) {
            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $rules[$field] = "uploaded[{$field}]|max_size[{$field},2048]|is_image[{$field}]|mime_in[{$field},image/png,image/jpeg,image/webp]|ext_in[{$field},png,jpg,jpeg,webp]";
            }
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $before = $unit;
        $newLogos = [];
        foreach ($logoFiles as $field => $logoFile) {
            if ($logoFile === null || ! $logoFile->isValid() || $logoFile->hasMoved()) continue;
            $uploadDirectory = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'unit-logos';
            if (! is_dir($uploadDirectory) && ! mkdir($uploadDirectory, 0755, true) && ! is_dir($uploadDirectory)) {
                return redirect()->back()->withInput()->with('error', 'Direktori penyimpanan logo tidak dapat dibuat.');
            }
            $safeName = $unit['uuid'] . '-' . ($field === 'logo' ? 'left' : 'right') . '-' . bin2hex(random_bytes(8)) . '.' . strtolower($logoFile->getExtension());
            $logoFile->move($uploadDirectory, $safeName);
            $newLogos[$field] = ['absolute' => $uploadDirectory . DIRECTORY_SEPARATOR . $safeName, 'relative' => 'uploads/unit-logos/' . $safeName];
        }

        $after = [
            'name'            => $this->request->getPost('name'),
            'short_name'      => $this->request->getPost('short_name'),
            'npsn'            => $this->request->getPost('npsn') ?: null,
            'address'         => $this->request->getPost('address') ?: null,
            'phone'           => $this->request->getPost('phone') ?: null,
            'email'           => $this->request->getPost('email') ?: null,
            'head_name'       => $this->request->getPost('head_name') ?: null,
            'head_identifier' => $this->request->getPost('head_identifier') ?: null,
            'document_city'   => $this->request->getPost('document_city') ?: null,
            'decree_prefix'   => $this->request->getPost('decree_prefix') ?: null,
            'header_line_1'   => $this->request->getPost('header_line_1') ?: null,
            'header_line_2'   => $this->request->getPost('header_line_2') ?: null,
            'header_line_3'   => $this->request->getPost('header_line_3') ?: null,
            'header_line_4'   => $this->request->getPost('header_line_4') ?: null,
            'timezone'        => $this->request->getPost('timezone'),
            'updated_by'      => session()->get('user_id')
        ];
        if (isset($newLogos['logo'])) $after['logo_path'] = $newLogos['logo']['relative'];
        if (isset($newLogos['logo_right'])) $after['logo_right_path'] = $newLogos['logo_right']['relative'];

        if (!$unitModel->update($unit['id'], $after)) {
            foreach ($newLogos as $newLogo) if (is_file($newLogo['absolute'])) unlink($newLogo['absolute']);
            return redirect()->back()->withInput()->with('errors', $unitModel->errors());
        }

        foreach (['logo' => 'logo_path', 'logo_right' => 'logo_right_path'] as $field => $column) {
            if (! isset($newLogos[$field]) || empty($before[$column])) continue;
            $oldRelativePath = ltrim(str_replace('\\', '/', (string) $before[$column]), '/');
            if (str_starts_with($oldRelativePath, 'uploads/unit-logos/')) {
                $oldAbsolutePath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $oldRelativePath);
                if (is_file($oldAbsolutePath) && realpath(dirname($oldAbsolutePath)) === realpath(FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'unit-logos')) {
                    unlink($oldAbsolutePath);
                }
            }
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
