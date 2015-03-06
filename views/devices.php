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
  $devices = get_user_meta(get_current_user_id(), '2fa_devices', true);
  ?>
  <table>
    <thead>
      <tr><th>Device ID</th><th>Type</th></tr>
    </thead>
    <tbody>
      <?php foreach ($devices as $id => $device) : ?>
        <tr><td><?php echo esc_html(absint($id+1)) ?></td><td><?php echo esc_html($device['mode']) ?></td></tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php
}

?>
