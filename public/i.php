<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";
//$pair = "EOSUSD";
//$pair = "ETHUSD";
$pair = "TRXUSD";

//$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 1, 0.65, 0.01, 0.03, 30);
//foreach ($bots as $bot) {$bs->saveBot($bot);}

//$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.05, 58.6, 0.1, 0.2, 60);
//foreach ($bots as $bot) {$bs->saveBot($bot);}
//
//$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, -0.005, 63.2, 0.031, 0.03, 500);
//foreach ($bots as $bot) {$bs->saveBot($bot);}


$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 25, 0.0255454	, 0.0003, 0.00005, 100);
foreach ($bots as $bot) {$bs->saveBot($bot);}

