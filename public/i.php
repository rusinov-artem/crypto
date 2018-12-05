<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$bots = \Crypto\Bot\BotFactory::spreadAttack("BCHSVUSD", 0.1, 91.0, 0.1, 0.1, 10);
foreach ($bots as $bot)
    $bs->saveBot($bot);


$bot = \Crypto\Bot\BotFactory::simple("BTCUSD", 0.01, 4020, 20);
$bs->saveBot($bot);