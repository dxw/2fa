<?php

$registrar->addInstance(new \Dxw\TwoFa\SetupEmail($registrar->getInstance(\Dxw\Iguana\Value\Post::class)));
$registrar->addInstance(new \Dxw\TwoFa\WpCli\Registrar());
