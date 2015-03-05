<?php

if (!twofa_user_enabled(get_current_user_id())) {
  ?>
  <p>You cannot use 2FA because it has not been set up for your account yet.</p>
  <?php
} else if (twofa_user_activated(get_current_user_id()) > TWOFA_MAX_DEVICES) {
  ?>
  <p>You already have the maximum number of devices activated. Please deactivate one before setting up a new device.</p>
  <?php
} else {
  ?>
  <p>TODO: display the form</p>
  <?php
}

?>
