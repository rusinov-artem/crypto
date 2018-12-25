<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/9/2018
 * Time: 9:10 PM
 */

use Monolog\Logger;

require __DIR__.'/../vendor/autoload.php';

$logger = new \Monolog\Logger("test.logger");
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__.'/app.log'));
$handler = new \Monolog\Handler\StreamHandler(__DIR__ . '/app2.log');
$handler->setLevel(300);
$logger->pushHandler($handler);
$logger->log(Logger::CRITICAL, "SLDKFJ");

$dt = new DateTime();
$dt->setTimezone(new DateTimeZone("UTC33"));
var_dump($dt->getTimezone()->getName());