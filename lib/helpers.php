<?php

function twofa_user_override($user_id) {
  $enabled = get_user_meta($user_id, '2fa_override', true);

  if ($enabled === 'yes') {
    return 'yes';
  } else if ($enabled === 'no') {
    return 'no';
  }

  return 'default';
}

function twofa_blog_enabled($blawg_id) {
    return get_blog_option($blawg_id, '2fa_enabled') === 'yes';
}

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

function twofa_user_activated($user_id) {
  if (!twofa_user_enabled($user_id)) {
    return 0;
  }

  return count(twofa_user_devices($user_id));
}

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

function twofa_user_verify_token($user_id, $token) {
  $_devices = get_user_meta($user_id, '2fa_devices', true);
  foreach ($_devices as $k => $dev) {
    if ($dev['mode'] === 'totp') {
      // Verify it
      $otp = new \Otp\Otp();
      if ($otp->checkTotp(\Base32\Base32::decode($dev['secret']), $token, TWOFA_WINDOW)) {
        return true;
      }
    }
  }

  return false;
}

function twofa_json($data) {
  header('Content-Type: application/json');
  echo json_encode($data);
  exit(0);
}
