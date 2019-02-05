<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";
//$pair = "EOSUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.005, 63.099, 0.031, 0.03, 500);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, -0.005, 63.2, 0.031, 0.03, 500);
foreach ($bots as $bot) {$bs->saveBot($bot);}



