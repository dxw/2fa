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

  // Blacklist it, but ignore the return value
  twofa_token_blacklist($_POST['token']);

    if (!$valid) {
        twofa_json([
      'valid' => false,
    ]);
    }

    twofa_add_device(get_current_user_id(), [
    'mode' => 'totp',
    'name' => stripslashes($_POST['deviceName']),
    'secret' => $secret,
  ]);

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

  // Store it temporarily
  update_user_meta(get_current_user_id(), '2fa_sms_temporary_number', $number);

  // Send a verification token
  $err = twofa_sms_send_token(get_current_user_id(), [$number]);

    if ($err !== null) {
        twofa_json([
      'error' => $err,
    ]);
    }

  // Report that it has been sent
  twofa_json([
    'sms_sent' => true,
  ]);
});

// Checks that the token given by the user is valid according to 2fa_temporary_token
// Then stores the temporary phone number (2fa_temporary_number) to permanent (2fa_devices) and removes the temporary values
add_action('wp_ajax_2fa_sms_verify', function () {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_sms_verify')) {
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

  // Verify it
  if (!twofa_sms_verify_token(get_current_user_id(), stripslashes($_POST['token']))) {
      twofa_json([
      'valid' => false,
    ]);
  }

    twofa_add_device(get_current_user_id(), [
    'mode' => 'sms',
    'name' => stripslashes($_POST['deviceName']),
    'number' => get_user_meta(get_current_user_id(), '2fa_sms_temporary_number', true),
  ]);

    delete_user_meta(get_current_user_id(), '2fa_temporary_token');
    delete_user_meta(get_current_user_id(), '2fa_temporary_number');

    twofa_json([
    'valid' => true,
  ]);
});
