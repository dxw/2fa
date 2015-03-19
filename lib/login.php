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

  $errors = [];

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
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
        <input type="hidden" name="testcookie" value="1">
      </p>
    </form>

    <?php

  } else {
    // Phase 2 - token input

    //TODO: nonces are proprably inappropriate for this task
    ?>

    <form method="POST" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')) ?>" id="loginform" name="loginform">
      <p>
        <label for="user_login">
          <?php _e('Enter the token shown on your device') ?>
          <br>
          <input type="text" name="token" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" autofocus>
        </label>
      </p>
      <?php do_action( 'login_form' ) ?>
      <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Verify'); ?>">
        <input type="hidden" name="user_id" value="<?php echo esc_attr(absint($user->ID)) ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('2fa_phase2_'.$user->ID)) ?>">
        <input type="hidden" name="rememberme" value="<?php echo isset($_POST['rememberme']) ? 'yes' : 'no' ?>">
      </p>
    </form>

    <?php

  }

  login_footer();

  exit(0);
});
