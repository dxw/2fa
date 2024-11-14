<?php
/*
Plugin Name: 2FA
Description: Enables 2 factor authentication
Author: dxw
Author URI: http://dxw.com
Network: true
Version: 2.0.0
*/

$registrar = require __DIR__.'/src/load.php';
$registrar->register();

require(__DIR__."/lib/options.php");
require(__DIR__."/lib/helpers.php");
require(__DIR__."/lib/assets.php");
require(__DIR__."/lib/setup.php");
require(__DIR__."/lib/login.php");

require(__DIR__."/lib/sites-list.php");
require(__DIR__."/lib/user-profile.php");
require(__DIR__."/lib/page.php");
require(__DIR__."/lib/force-setup.php");
require(__DIR__."/lib/users-list.php");
require(__DIR__."/lib/settings.php");
