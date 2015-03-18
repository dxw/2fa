<?php

// Show our own login form
add_action('login_form_login', function () {

  $first_phase = true;

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
        <input type="checkbox" name="rememberme">
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
