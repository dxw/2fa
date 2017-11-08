<?php

require_once(__DIR__.'/../helpers/wp_cli.php');

describe(\Dxw\TwoFa\WpCli\Registrar::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();
        $this->registrar = new \Dxw\TwoFa\WpCli\Registrar();
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    it('is registerable', function () {
        expect($this->registrar)->is->an->instanceof(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        context('when WP_CLI is not defined', function () {
            beforeEach(function () {
                \phpmock\mockery\PHPMockery::mock(\Dxw\TwoFa\WpCli::class, 'class_exists')->with('WP_CLI')->andReturn(false);
            });

            it('does nothing', function () {
                \WP_CLI::$commands = [];
                $this->registrar->register();
                expect(\WP_CLI::$commands)->to->equal([]);
            });
        });

        context('when WP_CLI is defined', function () {
            beforeEach(function () {
                \phpmock\mockery\PHPMockery::mock(\Dxw\TwoFa\WpCli::class, 'class_exists')->with('WP_CLI')->andReturn(true);
            });

            it('registers WpCli modules', function () {
                \WP_CLI::$commands = [];
                $this->registrar->register();
                expect(\WP_CLI::$commands)->to->equal([['2fa', \Dxw\TwoFa\WpCli\TwoFaCommand::class]]);
            });
        });
    });
});
