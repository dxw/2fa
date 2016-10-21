<?php

namespace Dxw\TwoFa;

class Tokens
{
    public function isValid(string $namespace, int $userId, string $token) : bool
    {
        $currentTime = time();
        $storedTime = get_user_meta($userId, '2fa_'.$namespace.'_temporary_token_time', true);
        $expiry = 2*60;

        if ($currentTime > ($storedTime + $expiry)) {
            return false;
        }

        $storedToken = get_user_meta($userId, '2fa_'.$namespace.'_temporary_token', true);

        return $token === $storedToken;
    }
}
