<?php

use Crypto\Bot\BotStorage as BotStorage;
use Crypto\HitBTC\Client as Client;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";


$counter = 0;
$bs = new BotStorage();
$hit = new Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

var_dump($hit->getTransactionHistory());
