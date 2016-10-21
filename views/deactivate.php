<?php

$devices = twofa_user_devices(get_current_user_id());

if (isset($_POST['device_id']) && wp_verify_nonce($_POST['_wpnonce'], '2fa_deactivate-'.absint($_POST['device_id']))) {
    $id = absint($_POST['device_id']);
    $new_devices = [];
    foreach ($devices as $device) {
        if ($device['id'] === $id) {
            $mode = $device['mode'];
        } else {
            $new_devices[] = $device;
        }
    }
    update_user_meta(get_current_user_id(), '2fa_devices', $new_devices); ?>
  <?php if ($mode === 'totp') : ?>
    <p>The device has been deactivated. Make sure you delete the account from your authenticator app.</p>
  <?php else : ?>
    <p>The device has been deactivated.</p>
  <?php endif ?>
  <p>Return to your <a href="profile.php?page=2fa">2 factor authentication homepage</a>.</p>
  <?php

} else {
    $missing_device = true;

    $id = 0;
    if (isset($_GET['device_id'])) {
        $id = absint($_GET['device_id']);

        foreach ($devices as $device) {
            if ($device['id'] === $id) {
                $missing_device = false;
                $name = $device['name'];
                break;
            }
        }
    }

    if ($missing_device) {
        ?>
    <p>No such device.</p>
    <?php

    } elseif (count($devices) < 2) {
        ?>
    <p>You can’t deactivate your only device. <a href="profile.php?page=2fa&amp;step=setup">Activate another first</a>.</p>
    <p>Or return to your <a href="profile.php?page=2fa">2 factor authentication homepage</a>.</p>
    <?php

    } else {
        ?>
    <p>Are you sure you want to deactivate your <strong><?php echo esc_html($name) ?></strong> device?</p>
    <p>You won't be able to use it to log in with from now on.</p>
    <form method="POST">
      <?php wp_nonce_field('2fa_deactivate-'.$id) ?>
      <input type="hidden" name="device_id" value="<?php echo esc_attr($id) ?>">
      <input type="submit" value="Deactivate the device">
    </form>
    <?php

    }
}
