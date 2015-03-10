<?php

// Force users into the setup page until they've activated a device

add_action('admin_init', function() {
  $enabled = twofa_user_enabled(get_current_user_id());
  $activated = twofa_user_activated(get_current_user_id());
  $on_2fa_page = isset($_GET['page']) && $_GET['page'] === '2fa';
  $ajax = defined('DOING_AJAX') && DOING_AJAX;

  if ($enabled && !$activated && !$on_2fa_page && !$ajax) {
    wp_redirect(get_admin_url(0, 'users.php?page=2fa&step=setup'));
    exit(0);
  }
});
