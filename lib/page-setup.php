<?php

if (!twofa_user_enabled(get_current_user_id())) {
  ?>
  <p>You cannot use 2FA because it has not been set up for your account yet.</p>
  <?php
} else if (twofa_user_activated(get_current_user_id()) > TWOFA_MAX_DEVICES) {
  ?>
  <p>You already have the maximum number of devices activated. Please deactivate one before setting up a new device.</p>
  <?php
} else {
  $id = 1;
  if (isset($_POST['id'])) {
    $id = absint($_POST['id']);
  }
  if ($id > TWOFA_MAX_DEVICES || $id < 1) {
    $id = 1;
  }

  ?>
  <div ng-app="2fa" ng-controller="Setup">

    <!-- data -->

    <input type="hidden" id="2fa_device_id" value="<?php echo esc_attr($id) ?>">
    <input type="hidden" id="2fa_generate_secret" value="<?php echo esc_attr(wp_create_nonce('2fa_generate_secret')) ?>">
    <input type="hidden" id="2fa_verify" value="<?php echo esc_attr(wp_create_nonce('2fa_verify')) ?>">

    <!-- explanation and stuff -->

    <div ng-switch on="step">
      <div ng-switch-default>
        <p>TODO: some explanation about what's about to happen goes here.</p>
        <p><button ng-click="$parent.step = 1">Start setup</button></p>
      </div>

      <!-- STEP 1 -->

      <div ng-switch-when="1">
        <p>Current step: {{step}}/3</p>
        <p>What kind of device do you have?</p>
        <ul>
          <li>
            <div>
              <label>
                <input type="radio" name="2fa_setup_device" value="totp" ng-model="$parent.mode">
                Smartphone (use an app to log in)
              </label>
              <div ng-show="$parent.mode === 'totp'">
                <p>Please install the app before proceeding to the next step:</p>
                <ul>
                  <li>Android: <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator</a></li>
                  <li>BlackBerry: to install Google Authenticator open the web browser on your device and visit <code>m.google.com/authenticator</code></li>
                  <li>iPhone: <a href="https://itunes.apple.com/us/app/google-authenticator/id388497605">Google Authenticator</a></li>
                  <li>Windows Phone: <a href="http://www.windowsphone.com/en-us/store/app/authenticator/e7994dbc-2336-4950-91ba-ca22d653759b">Microsoft Authenticator</a></li>
                </ul>
              </div>
            </div>
          </li>
          <li>
            <label>
              <input type="radio" name="2fa_setup_device" value="sms" ng-model="$parent.mode">
              Other mobile (log in with a text message)
            </label>
          </li>
        </ul>

        <p><button ng-click="$parent.step = 2" ng-disabled="$parent.mode === undefined">Next</button></p>
      </div>

      <!-- STEP 2 -->

      <div ng-switch-when="2">
        <div ng-switch on="$parent.mode">
          <div ng-switch-when="totp">
            <p>Current step: {{step}}/3</p>
            <div ng-show="!$parent.totp_secret">
              <p>Generating secret...</p>
            </div>
            <div ng-show="$parent.totp_secret">
              <p>TODO: show a QR code</p>
              <p><a href="#" ng-click="text = 1">Can't scan it? Show the text instead</a></p>
              <p ng-show="text">{{$parent.totp_secret}}</p>
              <p><label><input type="checkbox" value="1" ng-model="scanned"> I've scanned this code into my device</label</p>
              <p><button ng-click="$parent.$parent.step = 3" ng-disabled="!scanned">Next</button></p>
              <p><button ng-click="$parent.$parent.step = 1">Go back</button></p>
            </div>
          </div>
          <div ng-switch-when="sms">
            <p>Current step: {{step}}/3</p>
            <p>TODO: SMS activation not implemented yet</p>
            <p><button ng-click="$parent.$parent.step = 1">Go back</button></p>
          </div>
        </div>
      </div>

      <!-- STEP 3 -->

      <div ng-switch-when="3">
        <div ng-switch on="$parent.mode">
          <div ng-switch-when="totp">
            <p>Current step: {{step}}/3</p>

            <label>
              Please enter the code that appears in the app:
              <input type="text" ng-model="token" ng-disabled="$parent.verification === 'valid'">
            </label>
            <button ng-click="$parent.verify(token)" ng-disabled="token.length !== 6 || $parent.verification === 'verifying' || $parent.verification === 'valid'">Verify</button>

            <div ng-switch on="$parent.verification">
              <div ng-switch-when="verifying">
                <p>Verifying...</p>
              </div>
              <div ng-switch-when="invalid">
                <p>Invalid! TODO: explain what to do here</p>
              </div>
              <div ng-switch-when="valid">
                <p>Valid!</p>
              </div>
            </div>

            <p><button ng-click="$parent.finish()" ng-disabled="$parent.verification !== 'valid'">Finish</button></p>
            <p><button ng-click="$parent.$parent.step = 2" ng-disabled="$parent.verification === 'valid'">Go back</button></p>

          </div>
          <div ng-switch-when="sms">
            <p>Current step: {{step}}/3</p>
            <p>TODO: SMS activation not implemented yet</p>
            <p><button ng-click="$parent.$parent.step = 2">Go back</button></p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
}

?>
