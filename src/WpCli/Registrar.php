<?php

namespace Dxw\TwoFa\WpCli;

class Registrar implements \Dxw\Iguana\Registerable
{
	public function register()
	{
		if (!class_exists(\WP_CLI::class)) {
			return;
		}

		\WP_CLI::add_command('2fa', TwoFaCommand::class);
	}
}
