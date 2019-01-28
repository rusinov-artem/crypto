<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, -0.001, 63.2, 0.05, 0.05, 1000);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.001, 63.15, 0.05, 0.05, 200);
foreach ($bots as $bot) {$bs->saveBot($bot);}
