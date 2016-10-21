<?php

describe(\Dxw\TwoFa\SetupEmail::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();

        $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([]));

        $this->userId = 123;
        $this->userEmail = 'test@dxw.com';
        \WP_Mock::wpFunction('get_current_user_id', [
            'return' => $this->userId,
        ]);
        $this->token = '123123';
        $this->invalidToken = '456456';

        $this->now = 777;
        $this->nonce = 'foobar';

        $this->expiration = 2*60;

        \phpmock\mockery\PHPMockery::mock('\\Dxw\\TwoFa', 'time')->andReturn($this->now);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
        \Mockery::close();
    });

    it('is registerable', function () {
        expect($this->setupEmail)->to->be->instanceof(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        it('registers the hooks', function () {
            \WP_Mock::expectActionAdded('wp_ajax_2fa_email_send_verification', [$this->setupEmail, 'sendVerification']);
            \WP_Mock::expectActionAdded('wp_ajax_2fa_email_verify', [$this->setupEmail, 'verify']);
            $this->setupEmail->register();
        });
    });

    describe('->sendVerification()', function () {
        context('with empty nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                ]));
            });

            it('gives an invalid nonce error if nonce empty', function () {
                \WP_Mock::wpFunction('twofa_json', [
                    'times' => 1,
                    'args' => [
                        [
                            'error' => true,
                            'reason' => 'invalid nonce',
                        ],
                    ]
                ]);
                $this->setupEmail->sendVerification();
            });
        });

        context('with invalid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                \WP_Mock::wpFunction('wp_verify_nonce', [
                    'return' => false,
                ]);
            });

            it('gives nonce error if does not match', function () {
                \WP_Mock::wpFunction('twofa_json', [
                    'times' => 1,
                    'args' => [
                        [
                            'error' => true,
                            'reason' => 'invalid nonce',
                        ],
                    ]
                ]);
                $this->setupEmail->sendVerification();
            });
        });

        context('with valid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));

                \WP_Mock::wpFunction('wp_verify_nonce', [
                    'return' => true,
                    'args' => [$this->nonce, '2fa_email_send_verification'],
                ]);
            });

            context('get_user_by returns error', function () {
                beforeEach(function () {
                    \WP_Mock::wpFunction('get_user_by', [
                        'args' => ['ID', $this->userId],
                        'return' => false,
                    ]);
                });

                it('calls twofa_json and does not send email', function () {
                    \WP_Mock::wpFunction('update_user_meta', [
                        'times' => 1,
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token',
                            $this->token
                        ],
                    ]);
                    \WP_Mock::wpFunction('update_user_meta', [
                        'times' => 1,
                        'args' => [
                            $this->userId,
                            '2fa_email_temporary_token_time',
                            $this->now,
                        ],
                    ]);
                    \WP_Mock::wpFunction('twofa_generate_token', [
                        'return' => $this->token,
                    ]);

                    \WP_Mock::wpFunction('wp_mail', [
                        'times' => 0,
                    ]);
                    \WP_Mock::wpFunction('twofa_json', [
                        'times' => 1,
                        'args' => [[
                            'error' => true,
                            'reason' => 'get_user_by failed',
                        ]],
                    ]);

                    $this->setupEmail->sendVerification();
                });
            });

            context('get_user_by returns user', function () {
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

                context('wp_mail fails', function () {
                    it('updates user_meta and sends email', function () {
                        \WP_Mock::wpFunction('update_user_meta', [
                            'times' => 1,
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token',
                                $this->token,
                            ],
                        ]);
                        \WP_Mock::wpFunction('update_user_meta', [
                            'times' => 1,
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token_time',
                                $this->now,
                            ],
                        ]);
                        \WP_Mock::wpFunction('twofa_generate_token', [
                            'return' => $this->token,
                        ]);
                        \WP_Mock::wpFunction('twofa_json', [
                            'times' => 0,
                        ]);

                        \WP_Mock::wpFunction('wp_mail', [
                            'times' => 1,
                            'returns' => false,
                        ]);

                        \WP_Mock::wpFunction('twofa_json', [
                            'args' => [[
                                'error' => true,
                                'reason' => 'wp_mail failed',
                            ]],
                            'times' => 1,
                        ]);

                        $this->setupEmail->sendVerification();
                    });
                });

                context('wp_mail succeeds', function () {
                    it('updates user_meta and sends email', function () {
                        \WP_Mock::wpFunction('update_user_meta', [
                            'times' => 1,
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token',
                                $this->token,
                            ],
                        ]);
                        \WP_Mock::wpFunction('update_user_meta', [
                            'times' => 1,
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token_time',
                                $this->now,
                            ],
                        ]);
                        \WP_Mock::wpFunction('twofa_generate_token', [
                            'return_in_order' => [$this->token, '777777'],
                        ]);
                        \WP_Mock::wpFunction('twofa_json', [
                            'times' => 0,
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

                        \WP_Mock::wpFunction('twofa_json', [
                            'args' => [[
                                'email_sent' => true,
                            ]],
                            'times' => 1,
                        ]);

                        $this->setupEmail->sendVerification();
                    });
                });
            });
        });
    });

    describe('->verify()', function () {
        context('with empty nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([]));
            });

            it('gives an invalid nonce error', function () {
                \WP_Mock::wpFunction('twofa_json', [
                    'times' => 1,
                    'args' => [
                        [
                            'error' => true,
                            'reason' => 'invalid nonce',
                        ],
                    ]
                ]);
                $this->setupEmail->verify();
            });
        });

        context('with invalid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                \WP_Mock::wpFunction('wp_verify_nonce', [
                    'return' => false,
                ]);
            });

            it('gives an invalid nonce error', function () {
                \WP_Mock::wpFunction('twofa_json', [
                    'times' => 1,
                    'args' => [
                        [
                            'error' => true,
                            'reason' => 'invalid nonce',
                        ],
                    ]
                ]);
                $this->setupEmail->verify();
            });
        });

        context('with valid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                \WP_Mock::wpFunction('wp_verify_nonce', [
                    'args' => [$this->nonce, '2fa_email_verify'],
                    'return' => true,
                ]);
            });

            context('with empty token', function () {
                beforeEach(function () {
                    $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                        'nonce' => $this->nonce,
                        'token' => '',
                    ]));
                });

                it('produces error', function () {
                    \WP_Mock::wpFunction('twofa_json', [
                        'times' => 1,
                        'args' => [[
                            'error' => true,
                            'reason' => 'missing token',
                        ]],
                    ]);

                    $this->setupEmail->verify();
                });
            });

            context('with a token', function () {
                beforeEach(function () {
                    $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                        'nonce' => $this->nonce,
                        'token' => $this->token,
                    ]));
                });

                context('(which is expired)', function () {
                    beforeEach(function () {
                        \WP_Mock::wpFunction('get_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token_time',
                                true,
                            ],
                            'return' => $this->now - ($this->expiration + 1),
                        ]);
                    });

                    it('reports invalid token', function () {
                        \WP_Mock::wpFunction('twofa_json', [
                            'times' => 1,
                            'args' => [[
                                'valid' => false,
                            ]],
                        ]);
                        $this->setupEmail->verify();
                    });
                });

                context('(which is invalid)', function () {
                    beforeEach(function () {
                        \WP_Mock::wpFunction('get_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token_time',
                                true,
                            ],
                            'return' => $this->now - $this->expiration,
                        ]);

                        \WP_Mock::wpFunction('get_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token',
                                true,
                            ],
                            'return' => $this->invalidToken,
                        ]);
                    });

                    it('reports invalid token', function () {
                        \WP_Mock::wpFunction('twofa_json', [
                            'times' => 1,
                            'args' => [[
                                'valid' => false,
                            ]],
                        ]);
                        $this->setupEmail->verify();
                    });
                });

                context('(which is valid)', function () {
                    beforeEach(function () {
                        \WP_Mock::wpFunction('get_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token_time',
                                true,
                            ],
                            'return' => $this->now - $this->expiration,
                        ]);

                        \WP_Mock::wpFunction('get_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_email_temporary_token',
                                true,
                            ],
                            'return' => $this->token,
                        ]);
                    });

                    it('reports valid token', function () {
                        \WP_Mock::wpFunction('twofa_json', [
                            'times' => 1,
                            'args' => [[
                                'valid' => true,
                            ]],
                        ]);

                        \WP_Mock::wpFunction('twofa_add_device', [
                            'args' => [
                                $this->userId,
                                [
                                    'mode' => 'email',
                                ],
                            ],
                            'times' => 1,
                        ]);

                        \WP_Mock::wpFunction('delete_user_meta', [
                            'args' => [
                                $this->userId,
                                '2fa_temporary_token',
                            ],
                            'times' => 1,
                        ]);

                        $this->setupEmail->verify();
                    });
                });
            });
        });
    });
});
