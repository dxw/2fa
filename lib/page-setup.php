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
  ?>
  <div ng-app="2fa" ng-controller="Setup" ng-init="step=0">
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
              <div ng-switch on="$parent.mode">
                <div ng-switch-when="totp">
                  <p>Please install the app before proceeding to the next step:</p>
                  <ul>
                    <li>Android: <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator</a></li>
                    <li>BlackBerry: to install Google Authenticator open the web browser on your device and visit <code>m.google.com/authenticator</code></li>
                    <li>iPhone: <a href="https://itunes.apple.com/us/app/google-authenticator/id388497605">Google Authenticator</a></li>
                    <li>Windows Phone: <a href="http://www.windowsphone.com/en-us/store/app/authenticator/e7994dbc-2336-4950-91ba-ca22d653759b">Microsoft Authenticator</a></li>
                  </ul>
                </div>
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
            <p>TODO (2)</p>
            <p><button ng-click="$parent.step = 3" ng-disabled="true">Next</button></p>
          </div>
          <div ng-switch-when="sms">
            <p>Current step: {{step}}/3</p>
            <p>TODO: SMS activation not implemented yet</p>
          </div>
        </div>
      </div>

      <!-- STEP 3 -->

      <div ng-switch-when="3">
        <p>Current step: {{step}}/3</p>
        <p>TODO (3)</p>
      </div>
    </div>
  </div>
  <?php
}

?>
