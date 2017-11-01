<?php

require_once(__DIR__.'/../helpers/wp_cli_command.php');
require_once(__DIR__.'/../helpers/wp_cli.php');

describe(\Dxw\TwoFa\WpCli\TwoFaCommand::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();
        $this->twoFaCommand = new \Dxw\TwoFa\WpCli\TwoFaCommand();
        \WP_CLI::$lines = [];
        \WP_CLI::$successes = [];
        \WP_CLI::$errors = [];
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    it('is a WP_CLI_Command', function () {
        expect($this->twoFaCommand)->is->an->instanceof(\WP_CLI_Command::class);
    });

    describe('->fails()', function () {
        it('shows failures', function () {
            $GLOBALS['wpdb'] = \Mockery::mock(\WP_DB::class, function ($mock) {
                $mock->users = 'my_users';
                $mock->usermeta = 'my_usermeta';
                $mock->shouldReceive('get_results')->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        WHERE meta_fails.meta_value > 0\n        ORDER BY meta_fails.meta_value +0 DESC\n        ")->andReturn([
                    (object)['devices' => serialize([['mode' => 'a']]), 'failures' => 5, 'user_login' => 'c'],
                    (object)['devices' => serialize([['mode' => 'x']]), 'failures' => 2, 'user_login' => 'z'],
                ]);
            });

            $this->twoFaCommand->fails([]);
            expect(\WP_CLI::$lines)->to->equal([
                ['   5 c (a)'],
                ['   2 z (x)'],
            ]);
            expect(\WP_CLI::$successes)->to->equal([]);
            expect(\WP_CLI::$errors)->to->equal([]);
        });
    });

    describe('->user()', function () {
        context('when no results', function () {
            it('does nothing', function () {
                $GLOBALS['wpdb'] = \Mockery::mock(\WP_DB::class, function ($mock) {
                    $mock->users = 'my_users';
                    $mock->usermeta = 'my_usermeta';
                    $mock->shouldReceive('prepare')->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices,\n        meta_override.meta_value AS override\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        LEFT JOIN my_usermeta AS meta_override ON ( meta_override.user_id=id AND meta_override.meta_key = '2fa_override' )\n        WHERE user_login = %s\n        ", 'alice')->andReturn('QUERY');
                    $mock->shouldReceive('get_results')->with("QUERY")->andReturn([]);
                });

                $this->twoFaCommand->user(['alice']);
                expect(\WP_CLI::$lines)->to->equal([]);
                expect(\WP_CLI::$successes)->to->equal([]);
                expect(\WP_CLI::$errors)->to->equal([]);
            });
        });

        context('when there are results', function () {
            it('prints a line', function () {
                $GLOBALS['wpdb'] = \Mockery::mock(\WP_DB::class, function ($mock) {
                    $mock->users = 'my_users';
                    $mock->usermeta = 'my_usermeta';
                    $mock->shouldReceive('prepare')->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices,\n        meta_override.meta_value AS override\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        LEFT JOIN my_usermeta AS meta_override ON ( meta_override.user_id=id AND meta_override.meta_key = '2fa_override' )\n        WHERE user_login = %s\n        ", 'alice')->andReturn('QUERY');
                    $mock->shouldReceive('get_results')->with('QUERY')->andReturn([
                        (object)['devices' => serialize([['mydevice']]), 'failures' => 5, 'user_login' => 'alice', 'override' => 'meow'],
                        (object)['a' => 'this will be ignored'],
                    ]);
                });

                $this->twoFaCommand->user(['alice']);
                expect(\WP_CLI::$lines)->to->equal([
                    ['user:alice override:meow failures:5 device:["mydevice"]'],
                ]);
                expect(\WP_CLI::$successes)->to->equal([]);
                expect(\WP_CLI::$errors)->to->equal([]);
            });
        });
    });

    describe('->reset()', function () {
        context('when there are no results', function () {
            it('does nothing', function () {
                $GLOBALS['wpdb'] = \Mockery::mock(\WP_DB::class, function ($mock) {
                    $mock->users = 'my_users';
                    $mock->usermeta = 'my_usermeta';
                    $mock->shouldReceive('prepare')->with("SELECT id FROM my_users WHERE user_login = %s LIMIT 1", 'bob')->andReturn('QUERY');
                    $mock->shouldReceive('get_results')->with('QUERY')->andReturn([]);
                    $mock->shouldReceive('get_results')->never();
                });

                $this->twoFaCommand->reset(['bob']);
                expect(\WP_CLI::$lines)->to->equal([]);
                expect(\WP_CLI::$successes)->to->equal([]);
                expect(\WP_CLI::$errors)->to->equal([['User bob could not be found.']]);
            });
        });

        context('when there are results', function () {
            it('resets 2fa settings', function () {
                $GLOBALS['wpdb'] = \Mockery::mock(\WP_DB::class, function ($mock) {
                    $mock->users = 'my_users';
                    $mock->usermeta = 'my_usermeta';
                    $mock->shouldReceive('prepare')->with("SELECT id FROM my_users WHERE user_login = %s LIMIT 1", 'bob')->andReturn('QUERY1');
                    $mock->shouldReceive('get_results')->with('QUERY1')->andReturn([
                        (object)['id' => 7],
                        (object)['a' => 'this will be ignored'],
                    ]);
                    $mock->shouldReceive('prepare')->with("\n            DELETE FROM my_usermeta WHERE user_id=%d AND meta_key LIKE '2fa_%'\n            ", 7)->andReturn('QUERY2');
                    $mock->shouldReceive('query')->once()->with('QUERY2');
                });

                $this->twoFaCommand->reset(['bob']);
                expect(\WP_CLI::$lines)->to->equal([]);
                expect(\WP_CLI::$successes)->to->equal([['Reset 2fa for user bob.']]);
                expect(\WP_CLI::$errors)->to->equal([]);
            });
        });
    });
});
