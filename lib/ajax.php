<?php

add_action('wp_ajax_2fa_generate_secret', function () {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_generate_secret')) {
    echo json_encode([
      'error' => true,
      'reason' => 'invalid nonce',
    ]);
    exit(0);
  }

  // Generate shared secret (base32)
  $secret = \Otp\GoogleAuthenticator::generateRandom(16);

  // Store it temporarily
  update_user_meta(get_current_user_id(), '2fa_temporary_secret', $secret);

  // Output it
  echo json_encode([
    'secret' => $secret,
  ]);
  exit(0);
});

add_action('wp_ajax_2fa_verify', function () {
  if (!isset($_POST['nonce']) || !isset($_POST['token']) || !wp_verify_nonce($_POST['nonce'], '2fa_verify')) {
    echo json_encode([
      'error' => true,
      'reason' => 'invalid nonce',
    ]);
    exit(0);
  }

  // Get shared secret
  $secret = get_user_meta(get_current_user_id(), '2fa_temporary_secret', true);

  // Verify it
  $otp = new \Otp\Otp();
  $valid = $otp->checkTotp(\Base32\Base32::decode($secret), $_POST['token']);

  if (!$valid) {
    echo json_encode([
      'valid' => false,
    ]);
    exit(0);
  }

  $devices = get_user_meta(get_current_user_id(), '2fa_devices', true);
  if (!is_array($devices)) {
    $devices = [];
  }
  if (count($devices) >= TWOFA_MAX_DEVICES) {
    echo json_encode([
      'error' => true,
      'reason' => 'max devices exceeded',
    ]);
    exit(0);
  }
  $devices[] = [
    'mode' => 'totp',
    'secret' => $secret,
  ];

  update_user_meta(get_current_user_id(), '2fa_devices', $devices);
  delete_user_meta(get_current_user_id(), '2fa_temporary_secret');

  echo json_encode([
    'valid' => true,
  ]);
  exit(0);
});

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
});
