# 2fa

WordPress plugin for 2 factor authentication (TOTP and SMS)

## Installation

At the moment this plugin must be installed on a multisite installation.

If you don't have a Twilio account, there's currently no way to hide SMS from the setup page.

To enable SMS authentication add these constants to your wp-config.php:

    define('TWILIO_ACCOUNT_SID', 'AC...');
    define('TWILIO_AUTH_TOKEN', '...');
    define('TWILIO_NUMBER', '...');

You can find those [here](https://www.twilio.com/user/account/voice-sms-mms/getting-started).

## Usage

Super admins can decide which users must use 2FA. Users cannot opt to start using 2FA if it has not been enabled for their account.

Super admins can do this in two ways - setting an option on the user's profile to "enabled", or by checking the checkbox in the list of sites which forces all users of a site to use 2FA.

Users will then be forced to setup 2FA the next time they log in (it will not interrupt a user who is already logged in).

They have the option of using TOTP (apps like Google Authenticator) or SMS. And they can configure up to 2 devices (controlled by a constant).

Admins can also set the number of days users can skip requests for their second factor when logging in.

## Tests

Install the [drone CLI tool](https://github.com/drone/drone#installation) and run:

    drone exec

## Licence

MIT
