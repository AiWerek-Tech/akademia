<?php

namespace App\Controllers;

use App\Models\SchedulingConstraintModel;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleConstraintsController extends BaseController
{
    private SchedulingConstraintModel $constraintModel;

    public function __construct()
    {
        $this->constraintModel = new SchedulingConstraintModel();
    }

    public function updateWeight(int $id): ResponseInterface
    {
        $weight    = (int)$this->request->getPost('weight');
        $isEnabled = (int)$this->request->getPost('is_enabled');

        $this->constraintModel->update($id, [
            'weight'     => $weight,
            'is_enabled' => $isEnabled,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['id' => $id, 'weight' => $weight, 'is_enabled' => $isEnabled],
        ]);
    }
}
