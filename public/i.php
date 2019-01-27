<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
$pair = "EDOUSD";
$pair = "BTCUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.00001, 3550.12, 20, 20, 7);
foreach ($bots as $bot) {$bs->saveBot($bot);}
