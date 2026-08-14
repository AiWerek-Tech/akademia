<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $userId = (int) session()->get('user_id');
        $user = Database::connect()->table('users')
            ->select('id, is_active, deleted_at, must_change_password, must_change_username, password_changed_at')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user || (int) $user['is_active'] !== 1 || $user['deleted_at'] !== null) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi Anda tidak lagi valid. Silakan masuk kembali.');
        }

        $authenticatedAt = (int) session()->get('auth_timestamp');
        $passwordChangedAt = !empty($user['password_changed_at']) ? strtotime($user['password_changed_at']) : 0;
        if ($passwordChangedAt > 0 && $authenticatedAt > 0 && $authenticatedAt < $passwordChangedAt) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Password akun telah berubah. Silakan masuk kembali.');
        }

        session()->set('must_change_password', (int) $user['must_change_password'] === 1);
        session()->set('must_change_username', (int) $user['must_change_username'] === 1);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
