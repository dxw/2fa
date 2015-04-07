<?php

// Display the user's enabled/activated status on the user list

add_filter('wpmu_users_columns', function ($columns) {
  $columns['2fa'] = '2FA';
  return $columns;
});

add_filter('manage_users_columns', function ($columns) {
  $columns['2fa'] = '2FA';
  return $columns;
});

add_filter('manage_users_custom_column', function ($value, $column_name, $user_id) {
  if ($column_name === '2fa') {
    $s = '';

    $s .= twofa_user_enabled($user_id) ? 'Enabled' : 'Disabled';
    $s .= ' - ';
    $s .= twofa_user_activated($user_id) ? 'Activated' : 'Not activated';
    return $s;
  }

  return $value;
}, 10, 3);
