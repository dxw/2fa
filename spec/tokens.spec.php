<?php

describe(\Dxw\TwoFa\Tokens::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();

        $this->tokens = new \Dxw\TwoFa\Tokens();
        $this->namespace = 'tricorder';
        $this->token = '123123';
        $this->wrongToken = '999999';
        $this->now = 1476969672;
        $this->expiry = 2*60;
        $this->userId = 3;

        \phpmock\mockery\PHPMockery::mock('\\Dxw\\TwoFa', 'time')->andReturn($this->now);

        \WP_Mock::wpFunction('get_user_meta', [
            'args' => [$this->userId, '2fa_'.$this->namespace.'_temporary_token', true],
            'return' => $this->token,
        ]);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    describe('->tokenIsValid()', function () {
        context('when token is expired', function () {
            beforeEach(function () {
                \WP_Mock::wpFunction('get_user_meta', [
                    'args' => [$this->userId, '2fa_'.$this->namespace.'_temporary_token_time', true],
                    'return' => $this->now - ($this->expiry + 1),
                ]);
            });

            it('returns false', function () {
                $result = $this->tokens->isValid($this->namespace, $this->userId, $this->token);
                expect($result)->to->equal(false);
            });
        });

        context('when token is current', function () {
            beforeEach(function () {
                \WP_Mock::wpFunction('get_user_meta', [
                    'args' => [$this->userId, '2fa_'.$this->namespace.'_temporary_token_time', true],
                    'return' => $this->now - $this->expiry,
                ]);
            });

            context('and invalid', function () {
                it('returns false', function () {
                    $result = $this->tokens->isValid($this->namespace, $this->userId, $this->wrongToken);
                    expect($result)->to->equal(false);
                });
            });

            context('and valid', function () {
                it('returns true', function () {
                    $result = $this->tokens->isValid($this->namespace, $this->userId, $this->token);
                    expect($result)->to->equal(true);
                });
            });
        });
    });
});
