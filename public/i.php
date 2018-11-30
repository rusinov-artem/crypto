<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$bots = \Crypto\Bot\BotFactory::spreadAttack("BCHSVUSD", 0.1, 101, 0.2, 2, 10);
foreach ($bots as $bot)
    $bs->saveBot($bot);

$bots = \Crypto\Bot\BotFactory::spreadAttack("BCHSVUSD", 0.1, 99, 0.4, 2, 10);
foreach ($bots as $bot)
    $bs->saveBot($bot);

$bots = \Crypto\Bot\BotFactory::spreadAttack("BCHSVUSD", 0.1, 95, 0.5, 2, 10);
foreach ($bots as $bot)
    $bs->saveBot($bot);