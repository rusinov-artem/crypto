<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$handler = new RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new Logger("hitbtc.client");
$logger->pushHandler($handler);
