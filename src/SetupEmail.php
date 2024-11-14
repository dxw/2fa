<?php

namespace Dxw\TwoFa;

class SetupEmail implements \Dxw\Iguana\Registerable
{
	private $post;

	public function __construct(\Dxw\Iguana\Value\Post $__post)
	{
		$this->post = $__post;
	}

	public function register()
	{
		add_action('wp_ajax_2fa_email_send_verification', [$this, 'sendVerification']);
		add_action('wp_ajax_2fa_email_verify', [$this, 'verify']);
	}

	public function sendVerification()
	{
		if (!isset($this->post['nonce']) || !wp_verify_nonce($this->post['nonce'], '2fa_email_send_verification')) {
			twofa_json([
				'error' => true,
				'reason' => 'invalid nonce',
			]);

			return;
		}

		$token = twofa_generate_token();

		update_user_meta(get_current_user_id(), '2fa_email_temporary_token', $token);
		update_user_meta(get_current_user_id(), '2fa_email_temporary_token_time', time());

		$user = get_user_by('ID', get_current_user_id());
		if ($user === false) {
			twofa_json([
				'error' => true,
				'reason' => 'get_user_by failed',
			]);
			return;
		}

		$result = wp_mail(
			$user->data->user_email,
			'2FA',
			'Verification code: '.$token
		);

		if ($result) {
			twofa_json([
				'email_sent' => true,
			]);
		} else {
			twofa_json([
				'error' => true,
				'reason' => 'wp_mail failed',
			]);
		}
	}

	public function verify()
	{
		if (!isset($this->post['nonce']) || !wp_verify_nonce($this->post['nonce'], '2fa_email_verify')) {
			twofa_json([
				'error' => true,
				'reason' => 'invalid nonce',
			]);
			return;
		}

		if (empty($this->post['token'])) {
			twofa_json([
				'error' => true,
				'reason' => 'missing token',
			]);
			return;
		}

		if (!$this->tokenIsValid($this->post['token'])) {
			twofa_json([
				'valid' => false,
			]);
			return;
		}

		twofa_add_device(get_current_user_id(), [
			'mode' => 'email',
		]);

		delete_user_meta(get_current_user_id(), '2fa_temporary_token');

		twofa_json([
			'valid' => true,
		]);
	}

	private function tokenIsValid($token)
	{
		if (time() > get_user_meta(get_current_user_id(), '2fa_email_temporary_token_time', true) + 2 * 60) {
			return false;
		}

		return $this->post['token'] === get_user_meta(get_current_user_id(), '2fa_email_temporary_token', true);
	}
}
