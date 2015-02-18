<?php

$dir_self     = dirname(__FILE__);
$inc_autoload = $dir_self . '/../vendor/autoload.php';

if (!is_file($inc_autoload)) throw new \ErrorException("Can't find vendor autoload.");

require_once $inc_autoload;

$app = new \Symfony\Component\Console\Application('berk');
$app->add(new \Berk\Command\InfoCommand());

$app->run();