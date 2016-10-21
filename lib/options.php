<?php

// The maximum number of devices each user is allowed
// If you set this below 2 the user will be stuck with the first device they activate
if (!defined('TWOFA_MAX_DEVICES')) {
    define('TWOFA_MAX_DEVICES', 2);
}

// Allows increasing the TOTP window (to allow for minor clock differences or users who are slow at entering the token)
if (!defined('TWOFA_WINDOW')) {
    define('TWOFA_WINDOW', 2);
}

// Keep tokens in the blacklist for this amount of seconds
if (!defined('TWOFA_BLACKLIST_DURATION')) {
    define('TWOFA_BLACKLIST_DURATION', 60*5);
}
