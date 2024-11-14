<?php

namespace Dxw\TwoFa\WpCli;

/**
* Manage data about 2fa logins

* @package dxw-2fa
*/
class TwoFaCommand extends \WP_CLI_Command
{
	/**
	* List failures
	*
	* @subcommand fails
	*/
	public function fails($args)
	{
		global $wpdb;
		$query = "
        SELECT user_login,
        meta_fails.meta_value AS failures,
        meta_dev.meta_value AS devices
        FROM $wpdb->users
        LEFT JOIN $wpdb->usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )
        LEFT JOIN $wpdb->usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )
        WHERE meta_fails.meta_value > 0
        ORDER BY meta_fails.meta_value +0 DESC
        ";
		$twofafails = $wpdb->get_results($query);
		foreach ($twofafails as $twofafails) {
			$device_arr = $this->device_info($twofafails->devices);
			\WP_CLI::line(sprintf("%4d %s (%s)", $twofafails->failures, $twofafails->user_login, $device_arr["mode"]));
		}
	}

	/**
	* Display user 2fa details
	* @subcommand user
	* @synopsis <username>
	*/
	public function user($args)
	{
		global $wpdb;
		$username = $args[0];
		$query = "
        SELECT user_login,
        meta_fails.meta_value AS failures,
        meta_dev.meta_value AS devices,
        meta_override.meta_value AS override
        FROM $wpdb->users
        LEFT JOIN $wpdb->usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )
        LEFT JOIN $wpdb->usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )
        LEFT JOIN $wpdb->usermeta AS meta_override ON ( meta_override.user_id=id AND meta_override.meta_key = '2fa_override' )
        WHERE user_login = %s
        ";
		$result = $wpdb->get_results($wpdb->prepare($query, $username));
		if (count($result) > 0) {
			$user_info = array_shift($result);
			$device_arr = $this->device_info($user_info->devices);
			\WP_CLI::line(sprintf("user:%s override:%s failures:%d device:%s", $user_info->user_login, $user_info->override, $user_info->failures, json_encode($device_arr)));
		}
	}

	/**
	* Reset a user 2fa details
	* @subcommand reset
	* @synopsis <username>
	*/
	public function reset($args)
	{
		global $wpdb;
		$username = $args[0];
		$query = "SELECT id FROM $wpdb->users WHERE user_login = %s LIMIT 1";
		$result = $wpdb->get_results($wpdb->prepare($query, $username));
		if (count($result) > 0) {
			$user_info = array_shift($result);
			$query2 = "
            DELETE FROM $wpdb->usermeta WHERE user_id=%d AND meta_key LIKE '2fa_%'
            ";
			$wpdb->query($wpdb->prepare($query2, $user_info->id));
			\WP_CLI::success(sprintf('Reset 2fa for user %s.', $username));
			return;
		}
		\WP_CLI::error(sprintf('User %s could not be found.', $username));
	}

	private function device_info($data)
	{
		$x = unserialize($data);
		return array_shift($x);
	}
}
