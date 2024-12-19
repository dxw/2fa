# 2fa

WordPress plugin for 2 factor authentication (TOTP and SMS)

## Installation

At the moment this plugin must be installed on a multisite installation.

To enable SMS authentication add these constants to your wp-config.php:

    define('TWILIO_ACCOUNT_SID', 'AC...');
    define('TWILIO_AUTH_TOKEN', '...');
    define('TWILIO_NUMBER', '...');

You can find those [here](https://www.twilio.com/user/account/voice-sms-mms/getting-started).

To disable 2FA options from being selectable, add constants as follows:
* `2FA_SMS_DISABLED` to disable SMS (for example if no Twilio account is setup)


## Usage

Super admins can decide which users must use 2FA. Users cannot opt to start using 2FA if it has not been enabled for their account.

Super admins can do this in two ways - setting an option on the user's profile to "enabled", or by checking the checkbox in the list of sites which forces all users of a site to use 2FA.

Users will then be forced to setup 2FA the next time they log in (it will not interrupt a user who is already logged in).

They have the option of using TOTP (apps like Google Authenticator) or SMS. And they can configure up to 2 devices (controlled by a constant).

Admins can also set the number of days users can skip requests for their second factor when logging in.

## wp-cli command

```
% wp 2fa fails
% wp 2fa user alice
% wp 2fa reset bob
```

## Tests

Unit tests and linter:

```
% composer install
% vendor/bin/peridot spec
% vendor/bin/php-cs-fixer fix --dry-run -v --diff
```

Integration tests:

```
% tests/run-with-docker.sh
```

## Licence

MIT
