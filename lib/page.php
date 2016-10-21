<?php

// Add the page to the menu, output the appropriate templates
add_action('admin_menu', function () {
    add_users_page('2 Factor Authentication', '2 Factor Authentication', 'read', '2fa', function () {
        ?>

    <div class="wrap">
      <h2>2 factor authentication</h2>

      <?php
      if (isset($_GET['step']) && $_GET['step'] === 'setup') {
          require(__DIR__.'/../views/setup.php');
      } elseif (isset($_GET['step']) && $_GET['step'] === 'deactivate') {
          require(__DIR__.'/../views/deactivate.php');
      } else {
          require(__DIR__.'/../views/devices.php');
      } ?>
    </div>

    <?php

    });
});
