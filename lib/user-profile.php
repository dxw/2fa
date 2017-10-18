<?php

// Display an option on user profiles
// Note that this can't be done with ACF because ACF can't display options on network pages

add_action('personal_options', function ($user) {
    $enabled = twofa_user_override($user->ID);

    $disabled = !current_user_can('manage_sites'); ?>

    <tr class="user-2fa-enabled">
        <th scope="row">2FA</th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span>2FA</span><br></legend>
                <label for="2fa_override_yes">
                    <input name="2fa_override" type="radio" id="2fa_override_yes" value="yes" <?php echo $enabled === 'yes' ? 'checked' : '' ?> <?php echo $disabled ? 'disabled' : '' ?>>
                    Enabled (this user must use 2FA even if they belong to no sites which require 2FA)
                </label>
                <br>
                <label for="2fa_override_default">
                    <input name="2fa_override" type="radio" id="2fa_override_default" value="default" <?php echo $enabled === 'default' ? 'checked' : '' ?> <?php echo $disabled ? 'disabled' : '' ?>>
                    Default (if the user is a member of a site which requires 2FA they will use it, if they don't they won't)
                </label>
                <br>
                <label for="2fa_override_no">
                    <input name="2fa_override" type="radio" id="2fa_override_no" value="no" <?php echo $enabled === 'no' ? 'checked' : '' ?> <?php echo $disabled ? 'disabled' : '' ?>>
                    Disabled (this user will not use 2FA even if they belong to a site which requires it)
                </label>
                <br>
            </fieldset>
        </td>
    </tr>

    <?php
});

$fn = function ($user_id) {
    if (!current_user_can('manage_sites')) {
        return;
    }

    if (isset($_POST['2fa_override'])) {
        $value = 'default';

        switch ($_POST['2fa_override']) {
            case 'yes':
            $value = 'yes';
            break;
            case 'no':
            $value = 'no';
            break;
        }

        update_user_meta($user_id, '2fa_override', $value);
    }
};

// Called after nonce checks
add_action('personal_options_update', $fn);
add_action('edit_user_profile_update', $fn);
