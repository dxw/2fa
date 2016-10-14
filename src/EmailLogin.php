<?php

namespace Dxw\TwoFa;

class EmailLogin
{
    public function sendLoginTokens($userId)
    {
        $returnEarly = true;
        foreach (twofa_user_devices($userId) as $device) {
            if ($device['mode'] === 'email') {
                $returnEarly = false;
            }
        }

        if ($returnEarly) {
            return;
        }

        $token = twofa_generate_token();

        update_user_meta($userId, '2fa_email_temporary_token', $token);
        update_user_meta($userId, '2fa_email_temporary_token_time', time());

        $user = get_user_by('ID', $userId);
        if ($user === false) {
            return;
        }

        wp_mail($user->data->user_email, '2FA', 'Verification code: '.$token);
    }
}
