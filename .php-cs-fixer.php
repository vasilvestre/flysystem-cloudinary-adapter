<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['bin', 'vendor', 'tests'])
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
    '@Symfony' => true,
])
    ->setFinder($finder)
    ->setRiskyAllowed(true);