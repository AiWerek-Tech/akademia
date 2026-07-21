<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use App\Models\UserModel;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if (empty($arguments)) {
            return;
        }

        $userId = session()->get('user_id');
        $unitId = session()->get('active_unit_id');

        $userModel = new UserModel();
        $userPerms = $userModel->getPermissions((int)$userId, $unitId ? (int)$unitId : null);

        $hasAccess = false;
        foreach ($arguments as $permissionCode) {
            if (in_array($permissionCode, $userPerms, true)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                $response = Services::response();
                return $response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.'
                ]);
            }

            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki izin (Permission denied) untuk halaman tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
