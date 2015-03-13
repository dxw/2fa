<?php

// Display blog enabled/disabled status in list

add_filter('wpmu_blogs_columns', function ($columns) {
  $columns['2fa'] = '2FA';
  return $columns;
});

add_action('manage_sites_custom_column', function ($column_name, $blawg_id) {
  if ($column_name === '2fa') {
    ?>
    <div class="js-2fa-toggle">
      <input type="hidden" class="blog_id" value="<?php echo esc_attr($blawg_id) ?>">
      <input type="hidden" class="nonce" value="<?php echo esc_attr(wp_create_nonce('2fa_toggle-'.$blawg_id)) ?>">
      <input type="hidden" class="enabled" value="<?php echo esc_attr(twofa_blog_enabled($blawg_id)) ?>">
      <div class="js-2fa-inner">
        <?php echo twofa_blog_enabled($blawg_id) ? 'Enabled' : 'Disabled' ?>
      </div>
    </div>
    <?php
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
