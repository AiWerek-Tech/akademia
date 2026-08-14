<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PasswordChangeRequiredFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if ($session->get('logged_in')) {
            $mustChange = (bool)$session->get('must_change_password');
            $mustChangeUsername = (bool)$session->get('must_change_username');
            
            if ($mustChange || $mustChangeUsername) {
                $uri = $request->getUri()->getPath();
                
                // Allow /change-password and /logout routes only
                if (!str_contains($uri, 'change-password') && !str_contains($uri, 'logout')) {
                    return redirect()->to('/change-password')->with('warning', 'Anda wajib memperbarui username dan keamanan akun terlebih dahulu.');
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
