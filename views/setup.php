<?php

if (!twofa_user_enabled(get_current_user_id())) {
	?>
  <p>You cannot use 2FA because it has not been set up for your account yet.</p>
  <?php
} elseif (twofa_user_activated(get_current_user_id()) >= TWOFA_MAX_DEVICES) {
	?>
  <p>You already have the maximum number of devices activated. Please deactivate one before setting up a new device.</p>
  <?php
} else {
	?>
  <div ng-app="2fa" id="ng-app" ng-controller="Setup" class="twofa-setup">
    <?php # data?>

    <input type="hidden" id="2fa_generate_secret" value="<?php echo esc_attr(wp_create_nonce('2fa_generate_secret')) ?>">
    <input type="hidden" id="2fa_sms_send_verification" value="<?php echo esc_attr(wp_create_nonce('2fa_sms_send_verification')) ?>">
    <input type="hidden" id="2fa_email_send_verification" value="<?php echo esc_attr(wp_create_nonce('2fa_email_send_verification')) ?>">
    <input type="hidden" id="2fa_verify" value="<?php echo esc_attr(wp_create_nonce('2fa_verify')) ?>">
    <input type="hidden" id="2fa_sms_verify" value="<?php echo esc_attr(wp_create_nonce('2fa_sms_verify')) ?>">
    <input type="hidden" id="2fa_email_verify" value="<?php echo esc_attr(wp_create_nonce('2fa_email_verify')) ?>">

    <?php # steps?>

    <div ng-switch on="step">

      <?php # explanation and stuff?>

      <div ng-switch-default class="step">
        <p>To increase the security on this blog 2 factor authentication (also known as 2-step verification) has now been enabled for your account. Please follow the steps to activate a device for 2 factor authentication.</p>

        <p><button class="button button-primary" ng-click="$root.step = 'start'">Start activation</button></p>
      </div>

      <?php # STEP 1?>

      <div ng-switch-when="start" class="step">
        <p>What kind of device are you using?</p>

        <ul>
        <?php if (!defined('2FA_SMART_DEVICE_DISABLED')): ?>
          <li>
            <div>
              <label>
                <input type="radio" name="2fa_setup_device" value="totp" ng-model="$root.mode">
                smartphone or tablet (download and use an app to log in)
              </label>
              <div ng-show="$root.mode === 'totp'">
                <p>Go to the app store on your device and install the app before proceeding to the next step:</p>

                <ul>
                  <li>Android: <a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator</a></li>
                  <li>BlackBerry: to install Google Authenticator open the web browser on your device and visit <code>m.google.com/authenticator</code></li>
                  <li>BlackBerry (smartphone): <a target="_blank" href="https://appworld.blackberry.com/webstore/content/29401059/?countrycode=US&amp;countrycode=US&amp;lang=en">2 Steps Authenticator</a>
                  <li>iPhone: <a target="_blank" href="https://itunes.apple.com/us/app/google-authenticator/id388497605">Google Authenticator</a></li>
                  <li>Windows Phone: <a target="_blank" href="http://www.windowsphone.com/en-us/store/app/authenticator/e7994dbc-2336-4950-91ba-ca22d653759b">Microsoft Authenticator</a></li>
                </ul>
              </div>
            </div>
          </li>
          <?php endif; ?>
          <?php if (!defined('2FA_SMS_DISABLED')): ?>
          <li>
            <label>
              <input type="radio" name="2fa_setup_device" value="sms" ng-model="$root.mode">
              other mobile (log in with a text message)
            </label>
            <div ng-show="$root.mode === 'sms'">
              <p>Enter your mobile phone number: <input type="text" ng-model="$root.sms_number"></p>
              <p>Give your mobile a name that you can later use to identify it: <input type="text" ng-model="$root.device_name"></p>
            </div>
          </li>
          <?php endif; ?>
          <li>
            <label>
              <input type="radio" name="2fa_setup_device" value="email" ng-model="$root.mode">
              I don't have a phone I can use at work.
            </label>
            <div ng-show="$root.mode === 'email'">
              <p>The activation code email will be sent to this email address: <strong><?php echo esc_html(wp_get_current_user()->user_email) ?></strong></p>
            </div>
          </li>

        </ul>

        <div ng-switch on="$root.mode">
          <div ng-switch-default>
            <p><button class="button button-primary" ng-disabled="true">Next</button></p>
          </div>
          <div ng-switch-when="totp">
            <p><button class="button button-primary" ng-click="$root.step = 'totp-2'">Next</button></p>
          </div>
          <div ng-switch-when="sms">
            <p><button class="button button-primary" ng-click="$root.step = 'sms-2'" ng-disabled="$root.sms_number === undefined || $root.sms_number.length === 0 || $root.device_name === undefined || $root.device_name.length === 0">Next</button></p>
          </div>
          <div ng-switch-when="email">
            <p><button class="button button-primary" ng-click="$root.step = 'email-2'">Next</button></p>
          </div>
        </div>
      </div>

      <?php # TOTP STEP 2?>

      <div ng-switch-when="totp-2" class="step">
        <div ng-show="!$root.totp_secret">
          <p>Generating secret...</p>
        </div>
        <div ng-show="$root.totp_secret">

          <p>Open the authenticator app on your device.</p>
          <p>Set up or add an account and scan the barcode.</p>

          <p><img src="<?php echo esc_attr(get_admin_url(null, 'admin-ajax.php?action=2fa_qr')) ?>&amp;cache={{$root.rand()}}"></p>

          <p>If you can’t scan the barcode, enter this key manually (make sure you choose the ‘time based’ option): <code>{{$root.prettyPrintSecret($root.totp_secret)}}</code></p>

          <p><label><input type="checkbox" value="1" ng-model="scanned"> I've scanned the code into my device or entered the key</label></p>

          <p><button class="button button-primary" ng-click="$root.step = 'totp-3'" ng-disabled="!scanned">Next</button></p>
          <p><button class="button" ng-click="$root.step = 'start'">Go back</button></p>
        </div>
      </div>

      <?php # TOTP STEP 3?>

      <div ng-switch-when="totp-3" class="step">

        <p>
          <label>
            Give your device a name that you can later use to identify it:
            <input type="text" ng-model="device_name" ng-disabled="$root.verification === 'valid'" autofocus>
          </label>
        </p>

        <p>
          <label>
            Please enter the code that appears in the app:
            <input type="text" ng-model="token" ng-disabled="$root.verification === 'valid'">
          </label>
          <button class="button" ng-click="$root.verify(token, device_name)" ng-disabled="device_name.length === 0 || token.length !== 6 || $root.verification === 'verifying' || $root.verification === 'valid'">Verify</button>
        </p>

        <div ng-switch on="$root.verification">
          <div ng-switch-when="verifying">
            <p>Verifying...</p>
          </div>
          <div ng-switch-when="invalid">
            <p>Invalid! Please try again, or click ‘go back’ and scan the barcode or enter the key into your app again.</p>
          </div>
          <div ng-switch-when="valid">
            <p>Valid!</p>
          </div>
        </div>

        <p><button class="button button-primary" ng-click="$root.step = 'finished'" ng-disabled="$root.verification !== 'valid'">Finish</button></p>
        <p><button class="button" ng-click="$root.totp_secret = null; $root.step = 'totp-2'" ng-disabled="$root.verification === 'valid'">Go back</button></p>

      </div>

      <?php # SMS STEP 2?>

      <div ng-switch-when="sms-2" class="step">
        <div ng-switch on="$root.sms_sent">
          <div ng-switch-default>
            <p>Sending verification SMS...</p>
          </div>
          <div ng-switch-when="error">
            <p>Sending verification SMS failed. Please go back and try again.</p>
          </div>
          <div ng-switch-when="sent">
            <p>Sent verification SMS!</p>
            <label>
              Please enter the code that is sent to you:
              <input type="text" ng-model="token" ng-disabled="$root.verification === 'valid'" autofocus>
            </label>
            <button class="button" ng-click="$root.sms_verify(token, $root.device_name)" ng-disabled="token.length !== 6 || $root.verification === 'verifying' || $root.verification === 'valid'">Verify</button>

            <div ng-switch on="$root.verification">
              <div ng-switch-when="verifying">
                <p>Verifying...</p>
              </div>
              <div ng-switch-when="invalid">
                <p>Invalid! Please try again, or click ‘go back’ and enter another phone number.</p>
              </div>
              <div ng-switch-when="valid">
                <p>Valid!</p>
              </div>
            </div>

            <p><button class="button button-primary" ng-click="$root.step = 'finished'" ng-disabled="$root.verification !== 'valid'">Finish</button></p>
          </div>
        </div>
        <p><button class="button" ng-click="$root.step = 'start'">Go back</button></p>
      </div>

      <?php # EMAIL STEP 2?>

      <div ng-switch-when="email-2" class="step">
        <div ng-switch on="$root.email_sent">
          <div ng-switch-default>
            <p>Sending verification email...</p>
          </div>
          <div ng-switch-when="error">
            <p>Sending verification email failed. Please go back and try again.</p>
          </div>
          <div ng-switch-when="sent">
            <p>Sent verification email!</p>
            <label>
              Please enter the code that is sent to you:
              <input type="text" ng-model="token" ng-disabled="$root.verification === 'valid'" autofocus>
            </label>
            <button class="button" ng-click="$root.email_verify(token)" ng-disabled="token.length !== 6 || $root.verification === 'verifying' || $root.verification === 'valid'">Verify</button>

            <div ng-switch on="$root.verification">
              <div ng-switch-when="verifying">
                <p>Verifying...</p>
              </div>
              <div ng-switch-when="invalid">
                <p>Invalid! Please try again, or check that your email address is set correctly on <a href="<?php admin_url('profile.php') ?>">your profile</a>.</p>
              </div>
              <div ng-switch-when="valid">
                <p>Valid!</p>
              </div>
            </div>

            <p><button class="button button-primary" ng-click="$root.step = 'finished'" ng-disabled="$root.verification !== 'valid'">Finish</button></p>
          </div>
        </div>
        <p><button class="button" ng-click="$root.step = 'start'">Go back</button></p>
      </div>


      <?php # finished?>

      <div ng-switch-when="finished" class="step">
        <p>Finished!</p>
        <p>You can now go to your blog <a href="index.php">dashboard</a>.</p>
        <p>Go to your <a href="profile.php?page=2fa">2 factor authentication homepage</a> to view your activated devices or activate a new device.</a>
      </div>

      <div ng-switch-when="3.14159265">
        <!-- IE8 workaround -->
      </div>

    </div>
  </div>
  <?php
}
