<?php

// Get the 2fa_override option (yes/no/default)
function twofa_user_override($user_id)
{
    $enabled = get_user_meta($user_id, '2fa_override', true);

    if ($enabled !== 'yes' && $enabled !== 'no') {
        return 'default';
    }

    return $enabled;
}

// Get whether a blog has 2fa enabled (bool)
function twofa_blog_enabled($blawg_id)
{
    return get_blog_option($blawg_id, '2fa_enabled') === 'yes';
}

// Get whether a user has 2fa enabled (bool - based on values of twofa_user_override() and twofa_blog_enabled())
function twofa_user_enabled($user_id)
{
    $override = twofa_user_override($user_id);

    if ($override === 'yes') {
        return true;
    } elseif ($override === 'no') {
        return false;
    }

    foreach (get_blogs_of_user($user_id, true) as $blawg) {
        if (twofa_blog_enabled($blawg->userblog_id)) {
            return true;
        }
    }

    return false;
}

// How many devices a user has 2fa enabled on (int)
function twofa_user_activated($user_id)
{
    if (!twofa_user_enabled($user_id)) {
        return 0;
    }

    return count(twofa_user_devices($user_id));
}

// Return the value of 2fa_devices (array of arrays)
function twofa_user_devices($user_id)
{
    $_devices = get_user_meta($user_id, '2fa_devices', true);
    $devices = [];

    if (is_array($_devices)) {
        foreach ($_devices as $k => $dev) {
            $devices[] = [
                'id' => $k+1,
                'name' => isset($dev['name']) ? $dev['name'] : '[unnamed]',
                'mode' => $dev['mode'],
                'secret' => isset($dev['secret']) ? $dev['secret'] : '',
                'number' => isset($dev['number']) ? $dev['number'] : '',
            ];
        }
    }

    return $devices;
}

// Checks if a token is valid for a user (bool)
// Used during the login process, checks against all devices and the blacklist
function twofa_user_verify_token($user_id, $token)
{
    if (twofa_token_blacklist($token)) {
        return false;
    }

    if (twofa_sms_verify_token($user_id, $token)) {
        return true;
    }

    $tokens = new \Dxw\TwoFa\Tokens();
    if ($tokens->isValid('email', $user_id, $token)) {
        return true;
    }

    $_devices = get_user_meta($user_id, '2fa_devices', true);
    foreach ($_devices as $k => $dev) {
        if ($dev['mode'] === 'totp') {
            // Verify it
            if (twofa_verify_token($dev['secret'], $token)) {
                return true;
            }
        }
    }

    return false;
}

// Outputs the given value as JSON, exits afterwards
function twofa_json($data)
{
    if (isset($data['error']) && $data['error']) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_login = wp_get_current_user()->user_login;

        trigger_error('Unexpected error during 2FA setup. IP: "'.$ip.'" User: "'.$user_login.'" Error data: '.json_encode($data), E_USER_WARNING);
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit(0);
}

// Checks if a token is valid for a base32-encoded secret (bool)
function twofa_verify_token($secret, $token)
{
    $otp = new \Otp\Otp();
    return $otp->checkTotp(\Base32\Base32::decode($secret), $token, TWOFA_WINDOW);
}

// Stores a token in the blacklist and returns true if the token was already in the blacklist
function twofa_token_blacklist($token)
{
    // Retrieve blacklist
    $blacklist = get_site_option('2fa_token_blacklist');
    if (!is_array($blacklist)) {
        $blacklist = [];
    }

    // Purge the blacklist of old entries
    $blacklist = array_filter($blacklist, function ($item) {
        return $item['time'] > (time() - TWOFA_BLACKLIST_DURATION);
    });

    // Figure out whether the token was already on the blacklist
    $return = false;
    foreach ($blacklist as $item) {
        if ($item['token'] === $token) {
            $return = true;
            break;
        }
    }

    // Add the token to the blacklist
    $blacklist[] = [
        'time' => time(),
        'token' => $token,
    ];
    update_site_option('2fa_token_blacklist', $blacklist);

    return $return;
}

// Output a warning
function twofa_log_failure($user_id, $token)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    $user = get_user_by('id', $user_id);
    $user_login = '';
    if ($user !== false) {
        $user_login = $user->user_login;
    }

    trigger_error('IP address "'.$ip.'" attempted to log in as "'.$user_login.'" with a valid password but an invalid token "'.$token.'"', E_USER_WARNING);
}

// Generate shared secret (16 digit base32)
function twofa_generate_secret()
{
    $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $digits = 16;

    $secret = '';

    // We can't just generate the secret number and convert it to base32 because 32**16 > PHP_INT_MAX
    for ($i = 0; $i < $digits; $i++) {
        $secret .= substr($base32, wp_rand(0, 31), 1);
    }

    return $secret;
}

// This should be run when a user successfully logs in
// It resets the count of failed attempts and thus resets the captcha
function twofa_bruteforce_login_success($user_id)
{
    update_user_meta($user_id, '2fa_bruteforce_failed_attempts', 0);
}

// This should be run when a user unsuccessfully logs in
// It increments the count of failed attempts and thus eventually makes a captcha appear
function twofa_bruteforce_login_failure($user_id)
{
    update_user_meta($user_id, '2fa_bruteforce_failed_attempts', twofa_bruteforce_login_failures($user_id) + 1);
}

function twofa_bruteforce_login_show_captcha($user_id)
{
    return twofa_bruteforce_login_failures($user_id) >= 5;
}

function twofa_bruteforce_login_failures($user_id)
{
    return absint(get_user_meta($user_id, '2fa_bruteforce_failed_attempts', true));
}

function twofa_user_status($user_id)
{
    $s = '';
    $s .= twofa_user_enabled($user_id) ? 'Enabled' : 'Disabled';
    $s .= ' - ';
    $s .= twofa_user_activated($user_id) ? 'Activated' : 'Not activated';
    return $s;
}

// Generate token (to be sent via SMS)
function twofa_generate_token()
{
    return sprintf('%06d', wp_rand(0, 999999));
}

function twofa_add_device($user_id, $device_spec)
{
    $devices = get_user_meta(get_current_user_id(), '2fa_devices', true);
    if (!is_array($devices)) {
        $devices = [];
    }
    if (count($devices) >= TWOFA_MAX_DEVICES) {
        twofa_json([
            'error' => true,
            'reason' => 'max devices exceeded',
        ]);
    }
    $devices[] = $device_spec;
    update_user_meta(get_current_user_id(), '2fa_devices', $devices);
}

// Sends $body to $number
// Returns null on success, the error which occurred on failure
function twofa_send_sms($number, $body)
{
    if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_NUMBER')) {
        return 'bad configuration';
    }

    try {
        $client = new Services_Twilio(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

        // To avoid leaking user phone numbers to the log, we mask all but the last two digits.
        $number_len = strlen($number);
        $masked_number = $number_len > 2 ? substr_replace($number, str_repeat('X', $number_len - 2), 0, $number_len - 2) : $number;
        trigger_error(sprintf('About to send SMS from %s to %s', TWILIO_NUMBER, $masked_number), E_USER_WARNING);

        $client->account->messages->create([
            'To' => $number,
            'From' => TWILIO_NUMBER,
            'Body' => $body,
        ]);
    } catch (Services_Twilio_RestException $e) {
        // Log the error
        trigger_error('Twilio SMS error: '.$e, E_USER_WARNING);

        // Report that this function failed
        return (string)$e;
    }

    return null;
}

function twofa_sms_send_token($user_id, $numbers)
{
    // Generate a token
    $token = twofa_generate_token();

    // Store it temporarily
    update_user_meta($user_id, '2fa_sms_temporary_token', $token);
    update_user_meta($user_id, '2fa_sms_temporary_token_time', time());

    // Send it to all numbers
    foreach ($numbers as $number) {
        $err = twofa_send_sms($number, 'Verification code: '.$token);

        if ($err !== null) {
            return $err;
        }
    }
}

// Sends an authentication token to $user on all SMS devices
function twofa_sms_send_login_tokens($user_id)
{
    // Get all numbers
    $numbers = [];
    foreach (twofa_user_devices($user_id) as $device) {
        if ($device['mode'] === 'sms') {
            $numbers[] = $device['number'];
        }
    }

    if (count($numbers) > 0) {
        $err = twofa_sms_send_token($user_id, $numbers);
        if ($err !== null) {
            return $err;
        }
    }

    return null;
}

function twofa_sms_verify_token($user_id, $token)
{
    // Check to see if the token has expired
    //TODO: this hardcoded value should probably be a constant
    if (time() > ((int)get_user_meta($user_id, '2fa_sms_temporary_token_time', true)) + 2*60) {
        return false;
    }

    //TODO: use a constant-time string comparison function
    return $token === get_user_meta($user_id, '2fa_sms_temporary_token', true);
}

function twofa_skip_days()
{
    $value = absint(get_site_option('2fa_skip_days'));
    if ($value > 0) {
        return $value;
    }
    return 30;
}

// Set a cookie containing:
// user_id
// expiration timestamp
// HMAC
function twofa_set_skip_cookie($user_id)
{
    // Get values
    $user_id = absint($user_id);
    $expiration = time() + (twofa_skip_days() * DAY_IN_SECONDS);

    // Calculate HMAC
    //TODO: I'm unsure about this code
    $key = wp_hash($user_id . '|' . $expiration, 'auth');
    $hmac = hash_hmac('sha256', $user_id . '|' . $expiration, $key);

    // Set cookie
    setcookie('skip_2fa_'.$user_id, $user_id . '|' . $expiration . '|' . $hmac, $expiration, '', '', is_ssl(), true);
}

// Check for the skip cookie
function twofa_verify_skip_cookie($user_id)
{
    if (empty($_COOKIE['skip_2fa_'.$user_id])) {
        return false;
    }

    $s = explode('|', $_COOKIE['skip_2fa_'.$user_id]);
    if (absint($s[0]) !== $user_id) {
        return false;
    }

    if (absint($s[1]) < time()) {
        return false;
    }

    $key = wp_hash($user_id . '|' . absint($s[1]), 'auth');
    $hmac = hash_hmac('sha256', $user_id . '|' . absint($s[1]), $key);

    return $s[2] === $hmac;
}
