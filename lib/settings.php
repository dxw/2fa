<?php

add_action('network_admin_menu', function () {
  add_submenu_page('settings.php', '2 Factor Authentication', '2 Factor Authentication', 'manage_options', '2fa-settings', function () {
    ?>

    <div class="wrap">
      <h2>2 Factor Authentication</h2>

      <p>This page controls the 2 factor authentication (2FA) settings for the GOV.UK blog platform. It allows you to specify the length of time for which users can choose to skip 2FA on a specific computer.</p>

      <p>The default is 30 days. Do not change this unless there is a specific reason to. Any change will affect all users on the platform that have 2FA enabled.</p>

      <form>

        <table class="form-table">
          <tr>
            <td>
              <label>
                Users can skip 2FA on a specific computer for <input type="number" min="0" value="<?php echo esc_attr(twofa_skip_days()) ?>"> days.
              </label>
            </td>
          </tr>
        </table>

        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>

      </form>
    </div>

    <?php
  });
});
