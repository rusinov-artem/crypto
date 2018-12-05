<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$order = new \Crypto\Exchange\Order();
$order->pairID = "BTCUSD";
$order->price = 0.01;
$order->value = 0.0001;
$order->side = 'buy';

$data = $hit->createOrder($order);