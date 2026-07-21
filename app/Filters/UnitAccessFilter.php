<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class UnitAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userId = session()->get('user_id');
        $activeUnitId = session()->get('active_unit_id');

        if (!$activeUnitId) {
            return redirect()->to('/login')->with('error', 'Silakan hubungi administrator untuk verifikasi akses unit sekolah.');
        }

        // Verify active unit access in DB
        $db = Database::connect();
        $access = $db->table('user_unit_access')
            ->where('user_id', $userId)
            ->where('unit_id', $activeUnitId)
            ->get()
            ->getRowArray();

        if (!$access) {
            $fallbackAccess = $db->table('user_unit_access')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($fallbackAccess) {
                session()->set('active_unit_id', (int)$fallbackAccess['unit_id']);
                return redirect()->to('/dashboard')->with('error', 'Akses unit Anda telah dialihkan.');
            }

            session()->destroy();
            return redirect()->to('/login')->with('error', 'Akses unit Anda tidak valid.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
