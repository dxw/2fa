<?php

$finder = \PhpCsFixer\Finder::create()
->exclude('vendor')
->exclude('tests')
->in(__DIR__);

return \Dxw\PhpCsFixerConfig\Config::create()
->setFinder($finder);
