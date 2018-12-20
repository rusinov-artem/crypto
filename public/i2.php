<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";

$bots = \Crypto\Bot\BotFactory::spreadMartinSV(92, $hit);
//foreach ($bots as $bot) {$bs->saveBot($bot);}
