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
            foreach (preg_split('/[,|]/', (string) $permissionCode) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '' && in_array($candidate, $userPerms, true)) {
                    $hasAccess = true;
                    break 2;
                }
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

            return Services::response()->setStatusCode(403)->setBody(
                view('errors/html/error_403', ['message' => 'Anda tidak memiliki izin untuk halaman tersebut.'])
            );
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
