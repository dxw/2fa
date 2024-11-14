<?php

// Force users into the setup page until they've activated a device
// But not for existing sessions

// Once a user has 2fa activated on their account, all subsequent sessions must use 2fa
add_filter('attach_session_information', function ($info, $user_id) {
	if (twofa_user_enabled($user_id)) {
		$info['2fa_forced'] = true;
	}

	return $info;
}, 10, 2);

// If their session says they must use 2fa, make them use it
add_action('admin_init', function () {
	$enabled = twofa_user_enabled(get_current_user_id());
	$activated = twofa_user_activated(get_current_user_id());
	$on_2fa_page = isset($_GET['page']) && $_GET['page'] === '2fa';
	$ajax = defined('DOING_AJAX') && DOING_AJAX;

	$instance = WP_User_Meta_Session_Tokens::get_instance(get_current_user_id());
	$session = $instance->get(wp_get_session_token());
	$forced_for_session = isset($session['2fa_forced']);

	// If 2FA gets deactivated while logged in, remove the 2fa_forced marker
	// This is to prevent problems from a user logging in, asking an admin to disable 2fa, then the admin enabling 2fa at a later date
	if (!$enabled) {
		unset($session['2fa_forced']);
		$instance->update(wp_get_session_token(), $session);
	}

	if ($enabled && !$activated && !$on_2fa_page && !$ajax && $forced_for_session) {
		wp_redirect(get_admin_url(0, 'users.php?page=2fa&step=setup'), 302, '2fa plugin');
		exit(0);
	}
});
