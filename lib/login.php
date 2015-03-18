<?php

// Show our own login form
add_action('login_form_login', function () {

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

      // TODO: redirect to the correct URL
      wp_redirect(get_admin_url());
    }
  }

  // Phase 2

  if (isset($_POST['token']) && isset($_POST['user_id']) && isset($_POST['nonce'])) {
    $first_phase = false;

    $user_id = absint($_POST['user_id']);

    if ($user_id <= 0) {
      wp_die('TODO: bad user_id');
    }

    if (!wp_verify_nonce('2fa_phase2_'.$user_id, $_POST['nonce'])) {
      wp_die('TODO: invalid nonce');
    }

    if (!twofa_user_verify_token($user_id, $_POST['token'])) {
      wp_die('TODO: invalid token');
    }

    $rememberme = isset($_POST['rememberme']) && $_POST['rememberme'] === 'yes';
    wp_set_auth_cookie($user_id, $rememberme);

    //TODO: redirect to the correct URL
    wp_redirect(get_admin_url());
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
        <input type="hidden" name="user_id" value="<?php echo esc_attr(absint($user->ID)) ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('2fa_phase2_'.$user->ID)) ?>">
        <input type="hidden" name="rememberme" value="<?php echo isset($_POST['rememberme']) ? 'yes' : 'no' ?>">
      </label>

      <input type="submit" value="Verify">
    </form>

    <?php

  }

  exit(0);
});
