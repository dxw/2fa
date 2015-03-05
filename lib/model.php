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

  trigger_error('TODO', E_USER_ERROR);
}
