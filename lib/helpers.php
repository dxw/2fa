<?php

// Get the 2fa_override option (yes/no/default)
function twofa_user_override($user_id) {
  $enabled = get_user_meta($user_id, '2fa_override', true);

  if ($enabled === 'yes') {
    return 'yes';
  } else if ($enabled === 'no') {
    return 'no';
  }

  return 'default';
}

// Get whether a blog has 2fa enabled (bool)
function twofa_blog_enabled($blawg_id) {
    return get_blog_option($blawg_id, '2fa_enabled') === 'yes';
}

// Get whether a user has 2fa enabled (bool - based on values of twofa_user_override() and twofa_blog_enabled())
function twofa_user_enabled($user_id) {
  $override = twofa_user_override($user_id);

  if ($override === 'yes') {
    return true;
  } else if ($override === 'no') {
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
function twofa_user_activated($user_id) {
  if (!twofa_user_enabled($user_id)) {
    return 0;
  }

  return count(twofa_user_devices($user_id));
}

// Return the value of 2fa_devices minus the secrets (array of arrays)
function twofa_user_devices($user_id) {
  $_devices = get_user_meta($user_id, '2fa_devices', true);
  $devices = [];

  if (is_array($_devices)) {
    foreach ($_devices as $k => $dev) {
      $devices[] = [
        'id' => $k+1,
        'mode' => $dev['mode'],
      ];
    }
  }

  return $devices;
}

// Checks if a token is valid for a user (bool)
// Used during the login process, checks against all devices and the blacklist
function twofa_user_verify_token($user_id, $token) {
  if (twofa_token_blacklist($token)) {
    return false;
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
function twofa_json($data) {
  header('Content-Type: application/json');
  echo json_encode($data);
  exit(0);
}

// Checks if a token is valid for a base32-encoded secret (bool)
function twofa_verify_token($secret, $token) {
  $otp = new \Otp\Otp();
  return $otp->checkTotp(\Base32\Base32::decode($secret), $token, TWOFA_WINDOW);
}

// Stores a token in the blacklist and returns true if the token was already in the blacklist
function twofa_token_blacklist($token) {
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

function twofa_log_failure($mode, $user_id, $token) {
  $ip = $_SERVER['REMOTE_ADDR'];

  $user = get_user_by('id', $user_id);
  $user_login = '';
  if ($user !== false) {
    $user_login = $user->user_login;
  }

  trigger_error('IP address "'.$ip.'" attempted to log in as "'.$user_login.'" with a valid password but an invalid "'.$mode.'" token "'.$token.'"', E_USER_WARNING);
}
