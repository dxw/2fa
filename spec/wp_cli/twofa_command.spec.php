<?php

require_once(__DIR__.'/../helpers/wp_cli_command.php');
require_once(__DIR__.'/../helpers/wp_cli.php');

describe(\Dxw\TwoFa\WpCli\TwoFaCommand::class, function () {
	beforeEach(function () {
		$this->twoFaCommand = new \Dxw\TwoFa\WpCli\TwoFaCommand();
		\WP_CLI::$lines = [];
		\WP_CLI::$successes = [];
		\WP_CLI::$errors = [];
	});

	it('is a WP_CLI_Command', function () {
		expect($this->twoFaCommand)->toBeAnInstanceOf(\WP_CLI_Command::class);
	});

	describe('->fails()', function () {
		it('shows failures', function () {
			$mock = \Kahlan\Plugin\Double::instance();
			$GLOBALS['wpdb'] = $mock;
			$mock->users = 'my_users';
			$mock->usermeta = 'my_usermeta';
			allow($mock)->toReceive('get_results')->andReturn([
				(object)['devices' => serialize([['mode' => 'a']]), 'failures' => 5, 'user_login' => 'c'],
				(object)['devices' => serialize([['mode' => 'x']]), 'failures' => 2, 'user_login' => 'z'],
			]);
			expect($mock)->toReceive('get_results')->once()->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        WHERE meta_fails.meta_value > 0\n        ORDER BY meta_fails.meta_value +0 DESC\n        ");

			$this->twoFaCommand->fails([]);
			expect(\WP_CLI::$lines)->toBe([
				['   5 c (a)'],
				['   2 z (x)'],
			]);
			expect(\WP_CLI::$successes)->toBe([]);
			expect(\WP_CLI::$errors)->toBe([]);
		});
	});

	describe('->user()', function () {
		context('when no results', function () {
			it('does nothing', function () {
				$mock = \Kahlan\Plugin\Double::instance();
				$GLOBALS['wpdb'] = $mock;
				$mock->users = 'my_users';
				$mock->usermeta = 'my_usermeta';
				allow($mock)->toReceive('prepare')->andReturn('QUERY');
				allow($mock)->toReceive('get_results')->andReturn([]);
				expect($mock)->toReceive('prepare')->once()->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices,\n        meta_override.meta_value AS override\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        LEFT JOIN my_usermeta AS meta_override ON ( meta_override.user_id=id AND meta_override.meta_key = '2fa_override' )\n        WHERE user_login = %s\n        ", 'alice');

				$this->twoFaCommand->user(['alice']);
				expect(\WP_CLI::$lines)->toBe([]);
				expect(\WP_CLI::$successes)->toBe([]);
				expect(\WP_CLI::$errors)->toBe([]);
			});
		});

		context('when there are results', function () {
			it('prints a line', function () {
				$mock = \Kahlan\Plugin\Double::instance();
				$GLOBALS['wpdb'] = $mock;
				$mock->users = 'my_users';
				$mock->usermeta = 'my_usermeta';
				allow($mock)->toReceive('prepare')->andReturn('QUERY');
				expect($mock)->toReceive('prepare')->once()->with("\n        SELECT user_login,\n        meta_fails.meta_value AS failures,\n        meta_dev.meta_value AS devices,\n        meta_override.meta_value AS override\n        FROM my_users\n        LEFT JOIN my_usermeta AS meta_fails ON ( meta_fails.user_id=id AND meta_fails.meta_key = '2fa_bruteforce_failed_attempts' )\n        LEFT JOIN my_usermeta AS meta_dev ON ( meta_dev.user_id=id AND meta_dev.meta_key = '2fa_devices' )\n        LEFT JOIN my_usermeta AS meta_override ON ( meta_override.user_id=id AND meta_override.meta_key = '2fa_override' )\n        WHERE user_login = %s\n        ", 'alice');
				allow($mock)->toReceive('get_results')->andReturn([
					(object)['devices' => serialize([['mydevice']]), 'failures' => 5, 'user_login' => 'alice', 'override' => 'meow'],
					(object)['a' => 'this will be ignored'],
				]);
				expect($mock)->toReceive('get_results')->once()->with('QUERY');

				$this->twoFaCommand->user(['alice']);
				expect(\WP_CLI::$lines)->toBe([
					['user:alice override:meow failures:5 device:["mydevice"]'],
				]);
				expect(\WP_CLI::$successes)->toBe([]);
				expect(\WP_CLI::$errors)->toBe([]);
			});
		});
	});

	describe('->reset()', function () {
		context('when there are no results', function () {
			it('does nothing', function () {
				$mock = \Kahlan\Plugin\Double::instance();
				$GLOBALS['wpdb'] = $mock;
				$mock->users = 'my_users';
				$mock->usermeta = 'my_usermeta';
				allow($mock)->toReceive('prepare')->andReturn('QUERY');
				expect($mock)->toReceive('prepare')->once()->with("SELECT id FROM my_users WHERE user_login = %s LIMIT 1", 'bob');
				allow($mock)->toReceive('get_results')->andReturn([]);
				expect($mock)->toReceive('get_results')->once()->with('QUERY');
				$mock->shouldReceive('get_results')->never();

				$this->twoFaCommand->reset(['bob']);
				expect(\WP_CLI::$lines)->toBe([]);
				expect(\WP_CLI::$successes)->toBe([]);
				expect(\WP_CLI::$errors)->toBe([['User bob could not be found.']]);
			});
		});

		context('when there are results', function () {
			it('resets 2fa settings', function () {
				$mock = \Kahlan\Plugin\Double::instance();
				$GLOBALS['wpdb'] = $mock;
				$mock->users = 'my_users';
				$mock->usermeta = 'my_usermeta';
				allow($mock)->toReceive('prepare')->andReturn('QUERY1', 'QUERY2');
				expect($mock)->toReceive('prepare')->once()->with("SELECT id FROM my_users WHERE user_login = %s LIMIT 1", 'bob');
				allow($mock)->toReceive('get_results')->andReturn([
					(object)['id' => 7],
					(object)['a' => 'this will be ignored'],
				]);
				expect($mock)->toReceive('get_results')->once()->with('QUERY1');
				expect($mock)->toReceive('prepare')->once()->with("\n            DELETE FROM my_usermeta WHERE user_id=%d AND meta_key LIKE '2fa_%'\n            ", 7);
				allow($mock)->toReceive('query');
				expect($mock)->toReceive('query')->once()->with('QUERY2');

				$this->twoFaCommand->reset(['bob']);
				expect(\WP_CLI::$lines)->toBe([]);
				expect(\WP_CLI::$successes)->toBe([['Reset 2fa for user bob.']]);
				expect(\WP_CLI::$errors)->toBe([]);
			});
		});
	});
});
