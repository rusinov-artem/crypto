<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */

use Crypto\Bot\BotStorage;
use Crypto\HitBTC\Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";


$counter = 0;
$bs = new BotStorage();
$hit = new Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$handler = new RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new Logger("hitbtc.client");
$logger->pushHandler($handler);

$botLogger = new Logger("BotNext");
$botLogger->pushHandler($handler);

$hit->setLogger($logger);

$orders = $hit->getOrdersHistory();