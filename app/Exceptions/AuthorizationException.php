<?php

namespace App\Exceptions;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\RuntimeException;

final class AuthorizationException extends RuntimeException implements HTTPExceptionInterface
{
    protected $code = 403;
}
