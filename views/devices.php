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
  $devices = twofa_user_devices(get_current_user_id());
  ?>
  <table>
    <thead>
      <tr><th>Device ID</th><th>Type</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($devices as $device) : ?>
        <tr>
          <td><?php echo esc_html($device['id']) ?></td>
          <td><?php echo esc_html($device['mode']) ?></td>
          <td><a href="?page=2fa&step=deactivate&device_id=<?php echo esc_attr($device['id']) ?>">Deactivate</a></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <p>You are using <?php echo sprintf(_n('%d of %d allowed device', '%d of %d allowed devices', TWOFA_MAX_DEVICES), count($devices), TWOFA_MAX_DEVICES) ?>.</p>
  <?php if (count($devices) < TWOFA_MAX_DEVICES) : ?>
    <p>You may <a href="?page=2fa&step=setup">activate another</a>.</p>
  <?php endif ?>
  <?php
}

?>
