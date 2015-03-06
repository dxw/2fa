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
  if (!isset($_POST['nonce']) || !isset($_POST['token']) || !isset($_POST['device_id']) || !wp_verify_nonce($_POST['nonce'], '2fa_verify')) {
    echo json_encode([
      'error' => true,
    ]);
    exit(0);
  }

  $id = absint($_POST['device_id']);
  if ($id > TWOFA_MAX_DEVICES || $id < 1) {
    echo json_encode([
      'error' => true,
    ]);
    exit(0);
  }

  // Get shared secret
  $secret = get_user_meta(get_current_user_id(), '2fa_temporary_secret', true);

  // Verify it
  $otp = new \Otp\Otp();
  $valid = $otp->checkTotp(hex2bin($secret), $_POST['token']);

  update_user_meta(get_current_user_id(), '2fa_permanent_secret-'.$id, $secret);

  echo json_encode([
    'valid' => $valid,
  ]);
  exit(0);
});
