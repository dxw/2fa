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

    // Reset 2FA button
    $disabled = !current_user_can('remove_users');
    $s .= '<br>';
    $s .= '<button type="button" class="button button-secondary js-2fa-deactivate" ' . ($disabled ? 'disabled' : '') . ' data-user="' . esc_attr($user_id) . '" data-nonce="' . esc_attr(wp_create_nonce('2fa_deactivate')) . '">';
    $s .= "Deactivate this user's 2FA devices";
    $s .= '</button>';

    return $s;
  }

  return $value;
}, 10, 3);

// Allow deactivating devices
add_action('wp_ajax_2fa_deactivate', function () {
  if (!isset($_POST['user']) || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_deactivate')) {
    twofa_json([
      'error' => 'invalid request',
    ]);
  }

  if (!current_user_can('remove_users')) {
    twofa_json([
      'error' => 'permission error',
    ]);
  }

  $user_id = absint(stripslashes($_POST['user']));

  $ret = update_user_meta($user_id, '2fa_devices', null);

  twofa_json([
    'success' => $ret !== false,
  ]);
});
