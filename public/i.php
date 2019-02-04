<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
$pair = "TRXUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 1, 0.026, 0.0001, 0.0002, 10);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, -1, 0.027,  0.0001, 0.0005, 4);
foreach ($bots as $bot) {$bs->saveBot($bot);}


$pair = "EDOUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.01, 0.64, 0.01, 0.1, 10);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, -0.01, 0.75,  0.01, 0.1, 4);
foreach ($bots as $bot) {$bs->saveBot($bot);}