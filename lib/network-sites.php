<?php

add_filter('wpmu_blogs_columns', function ($columns) {
  $columns['2fa'] = '2FA';
  return $columns;
});

add_action('manage_sites_custom_column', function ($column_name, $blawg_id) {
  if ($column_name === '2fa') {
    $enabled = get_blog_option($blawg_id, '2fa_enabled') === 'yes';

    echo $enabled ? 'Enabled' : 'Disabled';
  }
}, 10, 2);
