<?php

use GuzzleHttp\Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$handler = new RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new Logger("hitbtc.client");
$logger->pushHandler($handler);
$logger->pushProcessor(function($record){
    var_dump($record);
    $record['context']['bot']="BOT #2";
    return $record;
});

$logger->err("Hello");

$client = new Client();
$client->get('https://ya.ru');

