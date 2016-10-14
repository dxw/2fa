<?php

$registrar->addInstance(new \Dxw\TwoFa\SetupEmail($registrar->getInstance(\Dxw\Iguana\Value\Post::class)));
