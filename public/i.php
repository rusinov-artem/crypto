<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttackStatic($pair, 0.1, 103.5, 0.1, 0.2, 30, 5);
foreach ($bots as $bot) {$bs->saveBot($bot);}

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 100, 0.5, 1);
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 98, 1, 1);
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 95, 1, 1);
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 91, 1, 1);
$bs->saveBot($bot);
