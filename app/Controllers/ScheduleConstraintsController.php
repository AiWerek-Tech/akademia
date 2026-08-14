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

        if ($weight < 0 || $weight > 10000 || ! in_array($isEnabled, [0, 1], true)
            || ! $this->constraintModel->find($id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Constraint, bobot, atau status tidak valid.',
            ])->setStatusCode(422);
        }

        if (! $this->constraintModel->update($id, [
            'weight'     => $weight,
            'is_enabled' => $isEnabled,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Constraint gagal diperbarui.'])->setStatusCode(500);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['id' => $id, 'weight' => $weight, 'is_enabled' => $isEnabled],
        ]);
    }
}
