<?php

// Gets the filtered value of the redirect_to parameter (string)
// Taken from wp-login.php
$get_redirect_to = function ($user) {
  if ( isset( $_REQUEST['redirect_to'] ) ) {
    $redirect_to = $_REQUEST['redirect_to'];
    // // Redirect to https if user wants ssl
    // if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') ) {
    //   $redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
    // }
  } else {
    $redirect_to = admin_url();
  }

  $requested_redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
  return apply_filters( 'login_redirect', $redirect_to, $requested_redirect_to, $user );
};

// Uses wp_safe_redirect()
// Taken from wp-login.php
$redirect = function ($user) use ($get_redirect_to) {
  global $interim_login;

  if ($interim_login) {
    $message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
    $interim_login = 'success';
    login_header( '', $message );
    ?>
    <?php
    do_action( 'login_footer' );
    ?>
    </body></html>
    <?php
    exit;
  }

  $redirect_to = $get_redirect_to($user);
  // Copied verbatim from wp-login.php

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

// Renders the HTML of the login form
$render = function ($phase, $errors, $rememberme, $user_id) use ($get_redirect_to) {
  global $interim_login;

  $first_phase = $phase === 1;

  $user = get_user_by('id', $user_id);
  $user_login = '';
  if ($user !== false) {
    $user_login = $user->user_login;
  }

  $redirect_to = $get_redirect_to($user);

  do_action( 'login_enqueue_scripts' );
  do_action( 'login_head' );

  $errors = apply_filters( 'wp_login_errors', $errors, $redirect_to );
  login_header(__('Log In'), '', $errors);

  if ($first_phase) {
    // Phase 1 - user/pass form

    ?>

    <form method="POST" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')) ?>" id="loginform" name="loginform">
      <p>
        <label for="user_login">
          <?php _e('Username') ?>
          <br>
          <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" autofocus>
        </label>
      </p>
      <p>
        <label for="user_pass">
          <?php _e('Password') ?>
          <br>
          <input type="password" name="pwd" id="user_pass" class="input" value="" size="20">
        </label>
      </p>
      <?php do_action( 'login_form' ) ?>
      <p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php checked($rememberme); ?>> <?php esc_attr_e('Remember Me') ?></label></p>
      <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>">
        <?php if ($interim_login) : ?>
          <input type="hidden" name="interim-login" value="1">
        <?php else : ?>
          <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
        <?php endif ?>
        <input type="hidden" name="testcookie" value="1">
      </p>
    </form>

    <?php

  } else {
    // Phase 2 - token input
    ?>

    <form method="POST" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')) ?>" id="loginform" name="loginform">
      <p>
        <label for="token">
          <?php _e('Enter the token shown on your device') ?>
          <br>
          <input type="text" name="token" id="token" class="input" size="20" autofocus>
        </label>
      </p>
      <?php do_action( 'login_form' ) ?>
      <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Verify'); ?>">
        <input type="hidden" name="user_id" value="<?php echo esc_attr(absint($user_id)) ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('2fa_phase2_'.$user_id)) ?>">
        <input type="hidden" name="rememberme" value="<?php echo isset($_POST['rememberme']) ? 'yes' : 'no' ?>">
      </p>
    </form>

    <?php

  }

  login_footer();
  exit(0);
};

// Replaces the stock WordPress login form with our own
add_action('login_form_login', function () use ($redirect, $render) {
  global $interim_login;

  // An interim login is where you're in the middle of editing a post and you get logged out
  // An interim login prompt appears and asks you to sign in again
  $interim_login = isset($_REQUEST['interim-login']);

  $errors = new WP_Error;

  // Phase 1

  if (isset($_POST['log']) && isset($_POST['pwd'])) {
    // Verify credentials
    $user = wp_authenticate($_POST['log'], $_POST['pwd']);

    if (is_wp_error($user)) {
      $render(1, $user, null, null);
    }

    if (twofa_user_activated($user->ID)) {
      // If the user has 2fa activated, send them to phase 2

      $render(2, null, null, $user->ID);
    } else {
      // If the user has 2fa deactivated, log them in

      $rememberme = isset($_POST['rememberme']);
      wp_set_auth_cookie($user->ID, $rememberme);

      $redirect($user);
    }
  }

  // Phase 2

  if (isset($_POST['token']) && isset($_POST['user_id']) && isset($_POST['nonce'])) {
    $user_id = absint($_POST['user_id']);

    if ($user_id <= 0) {
      $errors->add('bad_user_id', __('An error has occurred. Please try again.'));
      $render(1, $errors, null, null);
    }

    if (!wp_verify_nonce($_POST['nonce'], '2fa_phase2_'.$user_id)) {
      $errors->add('invalid_nonce', __('An error occurred. Please try again.'));
      $render(1, $errors, null, null);
    }

    $token = preg_replace('_[^0-9]_', '', $_POST['token']);
    if (!twofa_user_verify_token($user_id, $token)) {
      $errors->add('invalid_token', __('Invalid token. Try again.'));

      // Report failed login attempt
      twofa_log_failure('TOTP', $user_id, $token);

      $render(2, $errors, null, $user_id);
    }

    $rememberme = isset($_POST['rememberme']) && $_POST['rememberme'] === 'yes';
    wp_set_auth_cookie($user_id, $rememberme);

    $redirect(get_user_by('id', $user_id));
  }

  $render(1, $errors, null, null);
  exit(0);
});
