<?php

if (!twofa_user_enabled(get_current_user_id())) {
  ?>
  <p>You cannot use 2FA because it has not been set up for your account yet.</p>
  <?php
} else if (twofa_user_activated(get_current_user_id()) >= TWOFA_MAX_DEVICES) {
  ?>
  <p>You already have the maximum number of devices activated. Please deactivate one before setting up a new device.</p>
  <?php
} else {

  ?>
  <div ng-app="2fa" ng-controller="Setup" class="twofa-setup">
    <?php# templates ?>

    <script type="text/ng-template" id="/current-step.html">
      <ol class="position">
        <li ng-repeat="i in [1,2,3]" ng-class="{current: i===step}">{{i}}</li>
      </ol>
    </script>

    <?php# data ?>

    <input type="hidden" id="2fa_generate_secret" value="<?php echo esc_attr(wp_create_nonce('2fa_generate_secret')) ?>">
    <input type="hidden" id="2fa_verify" value="<?php echo esc_attr(wp_create_nonce('2fa_verify')) ?>">

    <?php# explanation and stuff ?>

    <div ng-switch on="step">
      <div ng-switch-default class="step">
        <p>TODO: some explanation about what's about to happen goes here.</p>
        <p><button class="button button-primary" ng-click="$parent.step = 1">Start setup</button></p>
      </div>

      <?php# STEP 1 ?>

      <div ng-switch-when="1" class="step">
        <div ng-include src="'/current-step.html'"></div>
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

        <p><button class="button button-primary" ng-click="$parent.step = 2" ng-disabled="$parent.mode === undefined">Next</button></p>
      </div>

      <?php# STEP 2 ?>

      <div ng-switch-when="2" class="step">
        <div ng-switch on="$parent.mode">
          <div ng-switch-when="totp">
            <div ng-include src="'/current-step.html'"></div>
            <div ng-show="!$parent.totp_secret">
              <p>Generating secret...</p>
            </div>
            <div ng-show="$parent.totp_secret">
              <p><img src="<?php echo esc_attr(get_admin_url(null, 'admin-ajax.php?action=2fa_qr')) ?>&cache={{$parent.rand()}}"></p>
              <p><a ng-click="text = 1">Can't scan it? Show the text instead</a></p>
              <div ng-show="text">
                <p>Set up a new account using this key and selecting the "time based" option:</p>
                <p>{{$parent.totp_secret}}</p>
              </div>
              <p><label><input type="checkbox" value="1" ng-model="scanned"> I've scanned this code into my device</label</p>
              <p><button class="button button-primary" ng-click="$parent.$parent.step = 3" ng-disabled="!scanned">Next</button></p>
              <p><button class="button" ng-click="$parent.$parent.step = 1">Go back</button></p>
            </div>
          </div>
          <div ng-switch-when="sms">
            <div ng-include src="'/current-step.html'"></div>
            <p>TODO: SMS activation not implemented yet</p>
            <p><button class="button" ng-click="$parent.$parent.step = 1">Go back</button></p>
          </div>
        </div>
      </div>

      <?php# STEP 3 ?>

      <div ng-switch-when="3" class="step">
        <div ng-switch on="$parent.mode">
          <div ng-switch-when="totp">
            <div ng-include src="'/current-step.html'"></div>

            <label>
              Please enter the code that appears in the app:
              <input type="text" ng-model="token" ng-disabled="$parent.verification === 'valid'">
            </label>
            <button class="button" ng-click="$parent.verify(token)" ng-disabled="token.length !== 6 || $parent.verification === 'verifying' || $parent.verification === 'valid'">Verify</button>

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

            <p><button class="button button-primary" ng-click="$parent.finish()" ng-disabled="$parent.verification !== 'valid'">Finish</button></p>
            <p><button class="button" ng-click="$parent.$parent.totp_secret = null; $parent.$parent.step = 2" ng-disabled="$parent.verification === 'valid'">Go back</button></p>

          </div>
          <div ng-switch-when="sms">
            <div ng-include src="'/current-step.html'"></div>
            <p>TODO: SMS activation not implemented yet</p>
            <p><button class="button" ng-click="$parent.$parent.step = 2">Go back</button></p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
}

?>
