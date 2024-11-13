<?php

describe(\Dxw\TwoFa\Tokens::class, function () {
    beforeEach(function () {
        $this->tokens = new \Dxw\TwoFa\Tokens();
        $this->namespace = 'tricorder';
        $this->token = '123123';
        $this->wrongToken = '999999';
        $this->now = 1476969672;
        $this->expiry = 2*60;
        $this->userId = 3;

        allow('time')->toBeCalled()->andReturn($this->now);
    });

    describe('->tokenIsValid()', function () {
        context('when token is expired', function () {
            beforeEach(function () {
                allow('get_user_meta')->toBeCalled()->andReturn($this->now - ($this->expiry + 1));
            });

            it('returns false', function () {
                $result = $this->tokens->isValid($this->namespace, $this->userId, $this->token);
                expect($result)->toBe(false);
            });
        });

        context('when token is current', function () {
            beforeEach(function () {
                allow('get_user_meta')->toBeCalled()->andReturn($this->now - $this->expiry, $this->token);
            });

            context('and invalid', function () {
                it('returns false', function () {
                    $result = $this->tokens->isValid($this->namespace, $this->userId, $this->wrongToken);
                    expect($result)->toBe(false);
                });
            });

            context('and valid', function () {
                it('returns true', function () {
                    $result = $this->tokens->isValid($this->namespace, $this->userId, $this->token);
                    expect($result)->toBe(true);
                });
            });
        });

        context('when token is not an int', function () {
            beforeEach(function () {
                allow('get_user_meta')->toBeCalled()->andReturn('hello');
            });

            it('does something', function () {
                $result = $this->tokens->isValid($this->namespace, $this->userId, $this->token);
                expect($result)->toBe(false);
            });
        });
    });
});
