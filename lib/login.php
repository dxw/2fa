<?php

// Show our own login form
add_action('login_form_login', function () {

  $first_phase = true;

  // Logging in

  if (isset($_POST['log']) && isset($_POST['pwd'])) {
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


    // Phase 2
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

    ?>

    <form method="POST">
      <label>
        Enter the token shown on your device
        <input type="text" name="token" autofocus>
      </label>

      <input type="submit" value="Verify">
    </form>

    <?php

  }

  exit(0);
});
