<?php
/**
* Manage data about 2fa logins

* @package dxw-2fa
*/
class TWOfa_Command extends WP_CLI_Command {
  /**
  * List failures
  *
  * @subcommand fails
  */
  public function fails($args) {
    global $wpdb;
    $twofafails = $wpdb->get_results("
    SELECT user_login,
    meta_fails.meta_value AS failures,
    meta_dev.meta_value AS devices
    FROM $wpdb->users
    LEFT JOIN $wpdb->usermeta AS meta_fails ON (meta_fails.user_id=id AND meta_fails.meta_key='2fa_bruteforce_failed_attempts')
    LEFT JOIN $wpdb->usermeta AS meta_dev ON (meta_dev.user_id=id AND meta_dev.meta_key='2fa_devices')
    WHERE meta_fails.meta_value > 0
    ORDER BY meta_fails.meta_value +0 DESC
    ");
    foreach($twofafails as $twofafails) {
      $device_arr = $this->device_info($twofafails->devices);
      WP_CLI::line(sprintf("%4d %s (%s)", $twofafails->failures, $twofafails->user_login, $device_arr["mode"]));
    }
  }

  /**
  * Display user 2fa details
  * @subcommand user
  * @synopsis <username>
  */
  public function user($args) {
    global $wpdb;
    $username = $args[0];
    $result = $wpdb->get_results($wpdb->prepare("
    SELECT user_login,
    meta_fails.meta_value AS failures,
    meta_dev.meta_value AS devices,
    meta_override.meta_value AS override
    FROM $wpdb->users
    LEFT JOIN $wpdb->usermeta AS meta_fails ON (meta_fails.user_id=id AND meta_fails.meta_key='2fa_bruteforce_failed_attempts')
    LEFT JOIN $wpdb->usermeta AS meta_dev ON (meta_dev.user_id=id AND meta_dev.meta_key='2fa_devices')
    LEFT JOIN $wpdb->usermeta AS meta_override ON (meta_override.user_id=id AND meta_override.meta_key='2fa_override')
    WHERE user_login=%s
    ", $username));
    if (count($result) > 0) {
      $user_info = array_shift($result);
      $device_arr = $this->device_info($user_info->devices);
      $failures = 0;
      if ($user_info->failures > 0) $failures = $user_info->failures;
      WP_CLI::line(sprintf("user:%s override:%s failures:%d device:%s", $user_info->user_login,$user_info->override,$user_info->failures,json_encode($device_arr)));
    }
  }

  /**
  * Reset a user 2fa details
  * @subcommand reset
  * @synopsis <username>
  */
  public function reset($args) {
    global $wpdb;
    $username = $args[0];
    $result = $wpdb->get_results($wpdb->prepare("SELECT id FROM $wpdb->users WHERE user_login=%s LIMIT 1", $username));
    if (count($result) > 0) {
      $user_info = array_shift($result);
      $query2 = "
      DELETE FROM $wpdb->usermeta WHERE user_id='$user_info->id' AND meta_key LIKE '2fa_%'
      ";
      //WP_CLI::line(sprintf("%s",$query2));
      $wpdb->get_results($query2);
    }
  }

  private function device_info($data) {
    /* TODO it's possible that there are multiple devices, but we only return the first */
    return array_shift(unserialize($data));
  }
}

WP_CLI::add_command('2fa', 'TWOfa_Command');
