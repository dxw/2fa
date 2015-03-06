<?php
/*
Plugin Name: 2FA
Description: Enables 2 factor authentication
Author: dxw
Author URI: http://dxw.com
*/

require(__DIR__."/vendor.phar");

require(__DIR__."/lib/options.php");
require(__DIR__."/lib/model.php");
require(__DIR__."/lib/assets.php");
require(__DIR__."/lib/ajax.php");

require(__DIR__."/lib/network-sites.php");
require(__DIR__."/lib/user-profile.php");
require(__DIR__."/lib/page.php");
