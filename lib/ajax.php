<?php

add_action('wp_ajax_2fa_generate_secret', function () {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '2fa_generate_secret')) {
    echo json_encode([
      'error' => true,
    ]);
    exit(0);
  }

  // Generate shared secret
  $secret = bin2hex(\Base32\Base32::decode(\Otp\GoogleAuthenticator::generateRandom(40)));

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
    ]);
    exit(0);
  }

  // Get shared secret
  $secret = get_user_meta(get_current_user_id(), '2fa_temporary_secret', true);

  $otp = new \Otp\Otp();

  // Verify it
  echo json_encode([
    'valid' => $otp->checkTotp(hex2bin($secret), $_POST['token']),
  ]);
  exit(0);
});
