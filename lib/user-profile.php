<?php

// Display an option on user profiles
// Note that this can't be done with ACF because ACF can't display options on network pages

add_action('personal_options', function ($user) {
  $enabled = get_user_meta($user->ID, '2fa_enabled', true);

  $yes = $no = $default = false;
  switch ($enabled) {
    case 'yes':
    $yes = true;
    break;

    case 'no':
    $no = true;
    break;

    default:
    $default = true;
    break;
  }
  ?>

  <tr class="user-2fa-enabled">
    <th scope="row">2FA</th>
    <td>
      <fieldset>
        <legend class="screen-reader-text"><span>2FA</span></legend>
        <label for="2fa_enabled_yes">
          <input name="2fa_enabled" type="radio" id="2fa_enabled_yes" value="yes" <?php echo $yes ? 'checked' : '' ?>>
          Enabled (this user must use 2FA even if they belong to no sites which require 2FA)
        </label>
        <label for="2fa_enabled_default">
          <input name="2fa_enabled" type="radio" id="2fa_enabled_default" value="default" <?php echo $default ? 'checked' : '' ?>>
          Default (if the user is a member of a site which requires 2FA they will use it, if they don't they won't)
        </label>
        <label for="2fa_enabled_no">
          <input name="2fa_enabled" type="radio" id="2fa_enabled_no" value="no" <?php echo $no ? 'checked' : '' ?>>
          Disabled (this user will not use 2FA even if they belong to a site which requires it)
        </label>
        <br>
      </fieldset>
    </td>
  </tr>

  <?php
});
