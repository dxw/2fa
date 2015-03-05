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

      <div ng-switch-when="1">
        <p>Current step: {{step}}/3</p>
        <p>TODO (1)</p>
      </div>

      <div ng-switch-when="2">
        <p>Current step: {{step}}/3</p>
        <p>TODO (2)</p>
      </div>

      <div ng-switch-when="3">
        <p>Current step: {{step}}/3</p>
        <p>TODO (3)</p>
      </div>
    </div>
  </div>
  <?php
}

?>
