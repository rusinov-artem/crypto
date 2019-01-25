<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "EDOUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.01, 73.3, 0.5, 0.5, 20);
foreach ($bots as $bot) {$bs->saveBot($bot);}
