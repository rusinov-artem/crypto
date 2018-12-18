<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "BTCUSD";
//$pair = "VOCOUSD";
//$pair = "EOSUSD";
//$pair = "EDOUSD";

$analytics = \Crypto\ServiceProvider::getAnalytics($hit);

var_dump($analytics->getUltraIndex($pair, 0.01, new DateInterval("PT5M")));
