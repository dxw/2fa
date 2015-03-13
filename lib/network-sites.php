<?php

// Display blog enabled/disabled status in list

add_filter('wpmu_blogs_columns', function ($columns) {
  $columns['2fa'] = '2FA';
  return $columns;
});

add_action('manage_sites_custom_column', function ($column_name, $blawg_id) {
  if ($column_name === '2fa') {
    echo twofa_blog_enabled($blawg_id) ? 'Enabled' : 'Disabled';
  }
}, 10, 2);

// Enabled/disabled status option

add_action('wpmueditblogaction', function ($id) {
  ?>

  <tr class="form-field">
    <th scope="row">2FA</th>
    <td><label><input name="2fa_enabled" type="checkbox" value="yes" <?php echo twofa_blog_enabled($id) ? 'checked' : '' ?>> Enabled</label></td>
  </tr>

  <?php
});

// Fired after nonce checks
add_action('wpmu_update_blog_options', function () {
  if (isset($_POST['2fa_enabled']) && $_POST['2fa_enabled'] === 'yes') {
    update_option('2fa_enabled', 'yes');
  } else {
    update_option('2fa_enabled', 'no');
  }
});
