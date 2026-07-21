<?php

namespace App\Controllers;

use App\Services\RoomService;
use App\Services\MasterExportService;
use App\Models\SchoolUnitModel;
use App\Models\RoomTypeModel;
use App\Models\RoomModel;

class RoomsController extends BaseController
{
    public function index()
    {
        if (!has_permission('rooms.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId = session()->get('active_unit_id');
        $filters = [
            'unit_id'      => $this->request->getGet('unit_id') ?? $activeUnitId,
            'room_type_id' => $this->request->getGet('room_type_id'),
            'is_active'    => $this->request->getGet('is_active'),
            'search'       => $this->request->getGet('search'),
        ];

        $result = RoomService::getRooms($filters);

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $typeModel = new RoomTypeModel();
        $roomTypes = $typeModel->where('is_active', 1)->findAll();

        return view('rooms/index', [
            'title'             => 'Master Ruang Sekolah',
            'breadcrumb_active' => 'Ruang Sekolah',
            'rooms'             => $result['data'],
            'pager'             => $result['pager'],
            'units'             => $units,
            'roomTypes'         => $roomTypes,
            'filters'           => $filters,
        ]);
    }

    public function create()
    {
        if (!has_permission('rooms.manage')) {
            return redirect()->to('/rooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $typeModel = new RoomTypeModel();
        $roomTypes = $typeModel->where('is_active', 1)->findAll();

        return view('rooms/create', [
            'title'             => 'Tambah Ruang Sekolah',
            'breadcrumb_active' => 'Tambah Ruang',
            'units'             => $units,
            'roomTypes'         => $roomTypes,
        ]);
    }

    public function store()
    {
        if (!has_permission('rooms.manage')) {
            return redirect()->to('/rooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'         => 'required|min_length[2]|max_length[30]',
            'name'         => 'required|min_length[3]|max_length[100]',
            'room_type_id' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            RoomService::createRoom($this->request->getPost());
            return redirect()->to('/rooms')->with('success', 'Ruang sekolah berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('rooms.manage')) {
            return redirect()->to('/rooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $model = new RoomModel();
        $room = $model->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$room) {
            return redirect()->to('/rooms')->with('error', 'Data ruang tidak ditemukan.');
        }

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $typeModel = new RoomTypeModel();
        $roomTypes = $typeModel->where('is_active', 1)->findAll();

        return view('rooms/edit', [
            'title'             => 'Edit Ruang Sekolah - ' . $room['name'],
            'breadcrumb_active' => 'Edit Ruang',
            'room'              => $room,
            'units'             => $units,
            'roomTypes'         => $roomTypes,
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('rooms.manage')) {
            return redirect()->to('/rooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'            => 'required|min_length[2]|max_length[30]',
            'name'            => 'required|min_length[3]|max_length[100]',
            'room_type_id'    => 'required|numeric',
            'revision_number' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            RoomService::updateRoom($uuid, $this->request->getPost());
            return redirect()->to('/rooms')->with('success', 'Ruang sekolah berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('rooms.export')) {
            return redirect()->to('/rooms')->with('error', 'Anda tidak memiliki hak akses export.');
        }

        try {
            $filePath = MasterExportService::exportExcel('ROOMS', [
                'unit_id' => $this->request->getGet('unit_id') ?? session()->get('active_unit_id'),
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
