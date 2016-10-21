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

// Enabled/disabled option

add_action('wpmueditblogaction', function ($id) {
    ?>

    <tr class="form-field">
        <th scope="row">2FA</th>
        <td><label><input name="2fa_enabled" type="checkbox" value="yes" <?php echo twofa_blog_enabled($id) ? 'checked' : '' ?>> Enabled</label></td>
    </tr>

    <?php

});

// Update the blog option
// Action is fired after nonce checks
add_action('wpmu_update_blog_options', function () {
    if (isset($_POST['2fa_enabled']) && $_POST['2fa_enabled'] === 'yes') {
        update_option('2fa_enabled', 'yes');
    } else {
        update_option('2fa_enabled', 'no');
    }
});

// Handler for JS
add_action('wp_ajax_2fa_network_site_toggle', function () {
    if (!isset($_POST['blog_id']) || !isset($_POST['enabled']) || !isset($_POST['nonce'])) {
        twofa_json([
            'error' => true,
            'reason' => 'bad request',
        ]);
    }

    $blawg_id = absint($_POST['blog_id']);
    $enabled = $_POST['enabled'] === 'yes';

    if (!wp_verify_nonce($_POST['nonce'], '2fa_toggle-'.$blawg_id)) {
        twofa_json([
            'error' => true,
            'reason' => 'bad nonce',
        ]);
    }

    if (!current_user_can('manage_sites')) {
        twofa_json([
            'error' => true,
            'reason' => 'permissions error',
        ]);
    }

    update_blog_option($blawg_id, '2fa_enabled', $enabled ? 'yes' : 'no');

    twofa_json([
        'success' => true,
    ]);
});
