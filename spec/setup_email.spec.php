<?php

describe(\Dxw\TwoFa\SetupEmail::class, function () {
    beforeEach(function () {
        $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([]));

        $this->userId = 123;
        $this->userEmail = 'test@dxw.com';
        allow('get_current_user_id')->toBeCalled()->andReturn($this->userId);
        $this->token = '123123';
        $this->invalidToken = '456456';

        $this->now = 777;
        $this->nonce = 'foobar';

        $this->expiration = 2*60;
        allow('time')->toBeCalled()->andReturn($this->now);
    });

    it('is registerable', function () {
        expect($this->setupEmail)->toBeAnInstanceOf(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        it('registers the hooks', function () {
            allow('add_action')->toBeCalled();
            expect('add_action')->toBeCalled()->once()->with('wp_ajax_2fa_email_send_verification', [$this->setupEmail, 'sendVerification']);
            expect('add_action')->toBeCalled()->once()->with('wp_ajax_2fa_email_verify', [$this->setupEmail, 'verify']);
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
                allow('twofa_json')->toBeCalled();
                expect('twofa_json')->toBeCalled()->once()->with([
                    'error' => true,
                    'reason' => 'invalid nonce',
                ]);

                $this->setupEmail->sendVerification();
            });
        });

        context('with invalid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                allow('wp_verify_nonce')->toBeCalled()->andReturn(false);
            });

            it('gives nonce error if does not match', function () {
                allow('twofa_json')->toBeCalled();
                expect('twofa_json')->toBeCalled()->once()->with( [
                    'error' => true,
                    'reason' => 'invalid nonce',
                ]);
                $this->setupEmail->sendVerification();
            });
        });

        context('with valid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));

                allow('wp_verify_nonce')->toBeCalled()->andReturn(true);
            });

            context('get_user_by returns error', function () {
                beforeEach(function () {
                    allow('get_user_by')->toBeCalled()->andReturn(false);
                });

                it('calls twofa_json and does not send email', function () {
                    allow('update_user_meta')->toBeCalled();
                    expect('update_user_meta')->toBeCalled()->once()->with($this->userId, '2fa_email_temporary_token', $this->token);
                    expect('update_user_meta')->toBeCalled()->once()->with($this->userId, '2fa_email_temporary_token_time', $this->now);
                    allow('twofa_generate_token')->toBeCalled()->andReturn($this->token);
                    expect('wp_mail')->not->toBeCalled();
                    allow('twofa_json')->toBeCalled();
                    expect('twofa_json')->toBeCalled()->once()->with([
                        'error' => true,
                        'reason' => 'get_user_by failed'
                    ]);

                    $this->setupEmail->sendVerification();
                });
            });

            context('get_user_by returns user', function () {
                beforeEach(function () {
                    allow('get_user_by')->toBeCalled()->andReturn((object) [
                        'data' => (object) [
                            'user_email' => $this->userEmail,
                        ],
                    ]);
                });

                context('wp_mail fails', function () {
                    it('updates user_meta and sends email', function () {
                        allow('update_user_meta')->toBeCalled();
                        expect('update_user_meta')->toBeCalled()->once()->with($this->userId,'2fa_email_temporary_token',$this->token);
                        expect('update_user_meta')->toBeCalled()->once()->with($this->userId,'2fa_email_temporary_token_time',$this->now);
                        allow('twofa_generate_token')->toBeCalled()->andReturn($this->token);
                        allow('wp_mail')->toBeCalled()->andReturn(false);
                       allow('twofa_json')->toBeCalled();
                       expect('twofa_json')->toBeCalled()->once()->with([
                        'error' => true,
                        'reason' => 'wp_mail failed'
                       ]);

                        $this->setupEmail->sendVerification();
                    });
                });

                context('wp_mail succeeds', function () {
                    it('updates user_meta and sends email', function () {
                        allow('update_user_meta')->toBeCalled();
                        expect('update_user_meta')->toBeCalled()->once()->with($this->userId, '2fa_email_temporary_token', $this->token);
                        expect('update_user_meta')->toBeCalled()->once()->with($this->userId, '2fa_email_temporary_token_time', $this->now);
                        allow('twofa_generate_token')->toBeCalled()->andReturn($this->token, '777777');
                        allow('wp_mail')->toBeCalled()->andReturn(true);
                        expect('wp_mail')->toBeCalled()->once()->with($this->userEmail, '2FA', 'Verification code: ' . $this->token);
                        allow('twofa_json')->toBeCalled();
                        expect('twofa_json')->toBeCalled()->once()->with([
                            'email_sent' => true
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
                allow('twofa_json')->toBeCalled();
                expect('twofa_json')->toBeCalled()->once()->with([
                    'error' => true,
                    'reason' => 'invalid nonce'
                ]);

                $this->setupEmail->verify();
            });
        });

        context('with invalid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                allow('wp_verify_nonce')->toBeCalled()->andReturn(false);
            });

            it('gives an invalid nonce error', function () {
                allow('twofa_json')->toBeCalled();
                expect('twofa_json')->toBeCalled()->once()->with([
                    'error' => true,
                    'reason' => 'invalid nonce'
                ]);

                $this->setupEmail->verify();
            });
        });

        context('with valid nonce', function () {
            beforeEach(function () {
                $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                    'nonce' => $this->nonce,
                ]));
                allow('wp_verify_nonce')->toBeCalled()->andReturn(true);
            });

            context('with empty token', function () {
                beforeEach(function () {
                    $this->setupEmail = new \Dxw\TwoFa\SetupEmail(new \Dxw\Iguana\Value\Post([
                        'nonce' => $this->nonce,
                        'token' => '',
                    ]));
                });

                it('produces error', function () {
                    allow('twofa_json')->toBeCalled();
                    expect('twofa_json')->toBeCalled()->once()->with([
                        'error' => true,
                        'reason' => 'missing token'
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
                        allow('get_user_meta')->toBeCalled()->andReturn($this->now - ($this->expiration + 1));
                    });

                    it('reports invalid token', function () {
                        allow('twofa_json')->toBeCalled();
                        expect('twofa_json')->toBeCalled()->once()->with([
                            'valid' => false
                        ]);

                        $this->setupEmail->verify();
                    });
                });

                context('(which is invalid)', function () {
                    beforeEach(function () {
                        allow('get_user_meta')->toBeCalled()->andReturn($this->now - $this->expiration, $this->invalidToken);
                    });

                    it('reports invalid token', function () {
                        allow('twofa_json')->toBeCalled();
                        expect('twofa_json')->toBeCalled()->once()->with([
                            'valid' => false
                        ]);
                        $this->setupEmail->verify();
                    });
                });

                context('(which is valid)', function () {
                    beforeEach(function () {
                        allow('get_user_meta')->toBeCalled()->andReturn($this->now - $this->expiration, $this->token);
                    });

                    it('reports valid token', function () {
                        allow('twofa_json')->toBeCalled();
                        expect('twofa_json')->toBeCalled()->once()->with([
                            'valid' => true
                        ]);

                        allow('twofa_add_device')->toBeCalled();
                        expect('twofa_add_device')->toBeCalled()->once()->with($this->userId, [
                            'mode' => 'email'
                        ]);

                        allow('delete_user_meta')->toBeCalled();
                        expect('delete_user_meta')->toBeCalled()->once()->with($this->userId, '2fa_temporary_token');

                        $this->setupEmail->verify();
                    });
                });
            });
        });
    });
});
