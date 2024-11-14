<?php

class WP_CLI
{
	public static $lines = [];
	public static $commands = [];
	public static $successes = [];
	public static $errors = [];

	public static function line()
	{
		self::$lines[] = func_get_args();
	}

	public static function add_command()
	{
		self::$commands[] = func_get_args();
	}

	public static function success()
	{
		self::$successes[] = func_get_args();
	}

	public static function error()
	{
		self::$errors[] = func_get_args();
	}
}
