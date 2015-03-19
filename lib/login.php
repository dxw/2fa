<?php

$redirect = function ($user) {
  // Copied verbatim from wp-login.php

  if ( isset( $_REQUEST['redirect_to'] ) ) {
    $redirect_to = $_REQUEST['redirect_to'];
    // Redirect to https if user wants ssl
    if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') )
    $redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
  } else {
    $redirect_to = admin_url();
  }

  $requested_redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
  $redirect_to = apply_filters( 'login_redirect', $redirect_to, $requested_redirect_to, $user );

  if ( ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) ) {
    // If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
    if ( is_multisite() && !get_active_blog_for_user($user->ID) && !is_super_admin( $user->ID ) )
    $redirect_to = user_admin_url();
    elseif ( is_multisite() && !$user->has_cap('read') )
    $redirect_to = get_dashboard_url( $user->ID );
    elseif ( !$user->has_cap('edit_posts') )
    $redirect_to = admin_url('profile.php');
  }

  wp_safe_redirect($redirect_to);
  exit();
};


// Show our own login form
add_action('login_form_login', function () use ($redirect) {

  $first_phase = true;

  // Phase 1

  if ($first_phase && isset($_POST['log']) && isset($_POST['pwd'])) {
    // Verify credentials
    $user = wp_authenticate($_POST['log'], $_POST['pwd']);

    if (is_wp_error($user)) {
      wp_die('TODO: could not authenticate');
    }

    if (twofa_user_activated($user->ID)) {
      // If the user has 2fa activated, send them to phase 2

      $first_phase = false;
    } else {
      // If the user has 2fa deactivated, log them in

      $rememberme = isset($_POST['rememberme']);
      wp_set_auth_cookie($user->ID, $rememberme);

      $redirect($user);
    }
  }

  // Phase 2

  if (isset($_POST['token']) && isset($_POST['user_id']) && isset($_POST['nonce'])) {
    $first_phase = false;

    $user_id = absint($_POST['user_id']);

    if ($user_id <= 0) {
      wp_die('TODO: bad user_id');
    }

    if (!wp_verify_nonce($_POST['nonce'], '2fa_phase2_'.$user_id)) {
      wp_die('TODO: invalid nonce');
    }

    if (!twofa_user_verify_token($user_id, $_POST['token'])) {
      wp_die('TODO: invalid token');
    }

    $rememberme = isset($_POST['rememberme']) && $_POST['rememberme'] === 'yes';
    wp_set_auth_cookie($user_id, $rememberme);

    $redirect(get_user_by('id', $user_id));
  }

  // Templates

  if ($first_phase) {
    // Phase 1 - user/pass form

    ?>

    <form method="POST">
      <label>
        Username
        <input type="text" name="log" autofocus>
      </label>
      <label>
        Password
        <input type="text" name="pwd">
      </label>
      <label>
        <input type="checkbox" name="rememberme" value="yes">
        Remember Me
      </label>

      <input type="submit" value="Log In">
    </form>

    <?php

  } else {
    // Phase 2 - token input

    //TODO: nonces are proprably inappropriate for this task
    ?>

    <form method="POST">
      <label>
        Enter the token shown on your device
        <input type="text" name="token" autofocus>
      </label>

      <input type="hidden" name="user_id" value="<?php echo esc_attr(absint($user->ID)) ?>">
      <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('2fa_phase2_'.$user->ID)) ?>">
      <input type="hidden" name="rememberme" value="<?php echo isset($_POST['rememberme']) ? 'yes' : 'no' ?>">

      <input type="submit" value="Verify">
    </form>

    <?php

  }

  exit(0);
});
