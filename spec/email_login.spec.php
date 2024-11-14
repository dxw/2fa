<?php

describe(\Dxw\TwoFa\EmailLogin::class, function () {
	beforeEach(function () {
		$this->emailLogin = new \Dxw\TwoFa\EmailLogin();
		$this->userId = 8;
		$this->token = '111111';
		$this->now = 777;
		$this->userEmail = 'test@dxw.com';

		allow('time')->toBeCalled()->andReturn($this->now);
		allow('twofa_generate_token')->toBeCalled()->andReturn($this->token, null);
	});

	describe('->sendLoginTokens()', function () {
		context('when there are no "email" devices', function () {
			beforeEach(function () {
				allow('twofa_user_devices')->toBeCalled()->andReturn([
					[
						'mode' => 'sms',
					],
					[
						'mode' => 'totp',
					],
				]);
			});

			it('does nothing', function () {
				expect('twofa_generate_token')->not->toBeCalled();
				$this->emailLogin->sendLoginTokens($this->userId);
			});
		});

		context('when there is one "email" device', function () {
			beforeEach(function () {
				allow('twofa_user_devices')->toBeCalled()->andReturn([
					[
						'mode' => 'sms',
					],
					[
						'mode' => 'email',
					],
				]);
			});

			context('(get_user_by returns false)', function () {
				beforeEach(function () {
					allow('get_user_by')->toBeCalled()->andReturn(false);
				});

				it('does not call wp_mail', function () {
					allow('update_user_meta')->toBeCalled();
					expect('update_user_meta')->toBeCalled()->once()->with(
						$this->userId,
						'2fa_email_temporary_token',
						$this->token
					);
					expect('update_user_meta')->toBeCalled()->once()->with(
						$this->userId,
						'2fa_email_temporary_token_time',
						$this->now
					);
					expect('wp_mail')->not->toBeCalled();

					$this->emailLogin->sendLoginTokens($this->userId);
				});
			});

			context('(get_user_by returns user)', function () {
				beforeEach(function () {
					allow('get_user_by')->toBeCalled()->andReturn((object) [
						'data' => (object) [
							'user_email' => $this->userEmail,
						],
					]);
				});

				it('sends an email', function () {
					allow('update_user_meta')->toBeCalled();
					expect('update_user_meta')->toBeCalled()->once()->with(
						$this->userId,
						'2fa_email_temporary_token',
						$this->token
					);
					expect('update_user_meta')->toBeCalled()->once()->with(
						$this->userId,
						'2fa_email_temporary_token_time',
						$this->now
					);
					allow('wp_mail')->toBeCalled()->andReturn(true);
					expect('wp_mail')->toBeCalled()->once()->with(
						$this->userEmail,
						'2FA',
						'Verification code: '.$this->token
					);

					$this->emailLogin->sendLoginTokens($this->userId);
				});
			});
		});
	});
});
