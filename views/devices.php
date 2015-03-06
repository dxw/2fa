<?php

if (!twofa_user_enabled(get_current_user_id())) {
  ?>
  <p>You cannot use 2FA because it has not been set up for your account yet.</p>
  <?php
} else if (!twofa_user_activated(get_current_user_id())) {
  ?>
  <p>You don't have any devices activated yet. Please <a href="?page=2fa&step=setup">activate one</a></p>
  <?php
} else {
  ?>
  <p>TODO: display a list of devices</p>
  <?php
}

?>
