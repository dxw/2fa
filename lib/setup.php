<?php

// Generates a secret for TOTP, stores it in 2fa_temporary_secret, and outputs it to the user
add_action('wp_ajax_2fa_generate_secret', function () {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_generate_secret')) {
    twofa_json([
      'error' => true,
      'reason' => 'invalid nonce',
    ]);
  }

  // Generate shared secret (16 digit base32)
  $secret = strtoupper(twofa_generate_secret());

  // Store it temporarily
  update_user_meta(get_current_user_id(), '2fa_temporary_secret', $secret);

  // Output it
  twofa_json([
    'secret' => $secret,
  ]);
});

// Checks that the token given by the user is valid according to 2fa_temporary_secret
// Then promotes the temporary value (2fa_temporary_secret) to permanent (2fa_devices) and removes the temporary value
add_action('wp_ajax_2fa_verify', function () {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_verify')) {
    twofa_json([
      'error' => true,
      'reason' => 'invalid nonce',
    ]);
  }

  if (empty($_POST['token']) || empty($_POST['deviceName'])) {
    twofa_json([
      'error' => true,
      'reason' => 'missing values',
    ]);
  }

  // Get shared secret
  $secret = get_user_meta(get_current_user_id(), '2fa_temporary_secret', true);

  // Verify it
  $valid = twofa_verify_token($secret, stripslashes($_POST['token']));

  if (!$valid) {
    twofa_json([
      'valid' => false,
    ]);
  }

  $devices = get_user_meta(get_current_user_id(), '2fa_devices', true);
  if (!is_array($devices)) {
    $devices = [];
  }
  if (count($devices) >= TWOFA_MAX_DEVICES) {
    twofa_json([
      'error' => true,
      'reason' => 'max devices exceeded',
    ]);
  }
  $devices[] = [
    'mode' => 'totp',
    'name' => stripslashes($_POST['deviceName']),
    'secret' => $secret,
  ];

  update_user_meta(get_current_user_id(), '2fa_devices', $devices);
  delete_user_meta(get_current_user_id(), '2fa_temporary_secret');

  twofa_json([
    'valid' => true,
  ]);
});

// Outputs a QR code as a PNG containing the value from 2fa_temporary_secret
add_action('wp_ajax_2fa_qr', function () {
  $secret = get_user_meta(get_current_user_id(), '2fa_temporary_secret', true);

  header('Content-Type: image/png');

  $qrCode = new \Endroid\QrCode\QrCode();
  $qrCode
  ->setText('otpauth://totp/'.rawurlencode(get_bloginfo('name')).'?secret='.$secret)
  ->setSize(300)
  ->setPadding(30)
  ->setErrorCorrection('high')
  ->render();
  exit(0);
});

// Sends an SMS
add_action('wp_ajax_2fa_sms_send_verification', function () {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_sms_send_verification')) {
    twofa_json([
      'error' => true,
      'reason' => 'invalid nonce',
    ]);
  }

  $number = stripslashes($_POST['number']);

  // Generate a token
  $token = strtoupper(twofa_generate_token());

  // Store it temporarily
  update_user_meta(get_current_user_id(), '2fa_sms_temporary_number', $number);
  update_user_meta(get_current_user_id(), '2fa_sms_temporary_token', $token);

  // Send it

  if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_NUMBER')) {
    twofa_json([
      'error' => true,
      'reason' => 'bad configuration',
    ]);
  }

  $client = new Services_Twilio(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

  $message = $client->account->messages->sendMessage(TWILIO_NUMBER, $number, 'Verification code: '.$token);

  //TODO: what do we do with $message to figure out if it was sent successfully or not?

  // Report that it has been sent
  twofa_json([
    'sms_sent' => true,
  ]);
});
