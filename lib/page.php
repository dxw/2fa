<?php

add_action('admin_menu', function () {
  add_users_page('2 Factor Authentication', '2FA', 'read', '2fa', function () {
    ?>

    <div class="wrap">
      <h2>2 Factor Authentication</h2>

      <?php
      if (isset($_GET['step']) && $_GET['step'] === 'setup') {
        require(__DIR__.'/page-setup.php');
      } else {
        require(__DIR__.'/page-devices.php');
      }
      ?>
    </div>

    <?php
  });
});
