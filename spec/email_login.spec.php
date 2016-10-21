<?php

describe(\Dxw\TwoFa\EmailLogin::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();

        $this->emailLogin = new \Dxw\TwoFa\EmailLogin();
        $this->userId = 8;
        $this->token = '111111';
        $this->now = 777;
        $this->userEmail = 'test@dxw.com';

        \phpmock\mockery\PHPMockery::mock('\\Dxw\\TwoFa', 'time')->andReturn($this->now);

        \WP_Mock::wpFunction('twofa_generate_token', [
            'return_in_order' => [$this->token, null],
        ]);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    describe('->sendLoginTokens()', function () {
        context('when there are no "email" devices', function () {
            beforeEach(function () {
                \WP_Mock::wpFunction('twofa_user_devices', [
                    'args' => [$this->userId],
                    'return' => [
                        [
                            'mode' => 'sms',
                        ],
                        [
                            'mode' => 'totp',
                        ],
                    ],
                ]);
            });

            it('does nothing', function () {
                $this->emailLogin->sendLoginTokens($this->userId);
            });
        });

        context('when there is one "email" device', function () {
            beforeEach(function () {
                \WP_Mock::wpFunction('twofa_user_devices', [
                    'args' => [$this->userId],
                    'return' => [
                        [
                            'mode' => 'sms',
                        ],
                        [
                            'mode' => 'email',
                        ],
                    ],
                ]);
            });

            context('(get_user_by returns false)', function () {
                beforeEach(function () {
                    \WP_Mock::wpFunction('get_user_by', [
                        'args' => ['ID', $this->userId],
                        'return' => false,
                    ]);
                });

                it('does not call wp_mail', function () {
                    \WP_Mock::wpFunction('update_user_meta', [
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token',
                            $this->token,
                        ],
                        'times' => 1,
                    ]);
                    \WP_Mock::wpFunction('update_user_meta', [
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token_time',
                            $this->now,
                        ],
                        'times' => 1,
                    ]);

                    \WP_Mock::wpFunction('wp_mail', [
                        'times' => 0,
                    ]);

                    $this->emailLogin->sendLoginTokens($this->userId);
                });
            });

            context('(get_user_by returns user)', function () {
                beforeEach(function () {
                    \WP_Mock::wpFunction('get_user_by', [
                        'args' => ['ID', $this->userId],
                        'return' => (object) [
                            'data' => (object) [
                                'user_email' => $this->userEmail,
                            ],
                        ],
                    ]);
                });

                it('sends an email', function () {
                    \WP_Mock::wpFunction('update_user_meta', [
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token',
                            $this->token,
                        ],
                        'times' => 1,
                    ]);
                    \WP_Mock::wpFunction('update_user_meta', [
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token_time',
                            $this->now,
                        ],
                        'times' => 1,
                    ]);

                    \WP_Mock::wpFunction('wp_mail', [
                        'args' => [
                            $this->userEmail,
                            '2FA',
                            'Verification code: '.$this->token,
                        ],
                        'times' => 1,
                        'return' => true,
                    ]);

                    $this->emailLogin->sendLoginTokens($this->userId);
                });
            });
        });
    });
});
