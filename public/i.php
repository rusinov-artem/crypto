<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.5, 102.55, 0.3, 1, 10);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.1, 109.9, 0.1, 0.1, 10);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.5, 98.9, 0.5, 1 );
$bs->saveBot($bot);