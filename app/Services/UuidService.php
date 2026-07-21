<?php

namespace App\Services;

class UuidService
{
    /**
     * Generates a cryptographically secure UUID version 4 (RFC 4122)
     */
    public static function v4(): string
    {
        $data = random_bytes(16);
        
        // Set version to 4 (0100)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        
        // Set variant to RFC 4122 (10xx)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
