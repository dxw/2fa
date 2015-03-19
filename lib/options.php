<?php

if (!defined('TWOFA_MAX_DEVICES')) {
  define('TWOFA_MAX_DEVICES', 2);
}

if (!defined('TWOFA_WINDOW')) {
  define('TWOFA_WINDOW', 2);
}

if (!defined('TWOFA_BLACKLIST_DURATION')) {
  define('TWOFA_BLACKLIST_DURATION', 60*5);
}
