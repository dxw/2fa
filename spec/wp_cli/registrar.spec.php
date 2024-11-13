<?php

require_once(__DIR__.'/../helpers/wp_cli.php');

describe(\Dxw\TwoFa\WpCli\Registrar::class, function () {
    beforeEach(function () {
        $this->registrar = new \Dxw\TwoFa\WpCli\Registrar();
    });

    it('is registerable', function () {
        expect($this->registrar)->toBeAnInstanceOf(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        context('when WP_CLI is not defined', function () {
            beforeEach(function () {
                allow('class_exists')->toBeCalled()->andReturn(false);
            });

            it('does nothing', function () {
                \WP_CLI::$commands = [];
                $this->registrar->register();
                expect(\WP_CLI::$commands)->toBe([]);
            });
        });

        context('when WP_CLI is defined', function () {
            beforeEach(function () {
                allow('class_exists')->toBeCalled()->andReturn(true);
            });

            it('registers WpCli modules', function () {
                \WP_CLI::$commands = [];
                $this->registrar->register();
                expect(\WP_CLI::$commands)->toBe([['2fa', \Dxw\TwoFa\WpCli\TwoFaCommand::class]]);
            });
        });
    });
});
