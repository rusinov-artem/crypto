<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$bin = new \Crypto\Binance\Client();
$bin->apiKey = $config['binance.api.key'];
$bin->secretKey = $config['binance.api.secret'];

$data = $bin->getBalance();
var_dump($data);

//var_dump($bin->getNonZeroBalance());
//var_dump($bin->getMinimalValue($order->pairID, 4000));

//
//$o = $bin->createOrder($order);

$orders = $bin->getActiveOrders();



