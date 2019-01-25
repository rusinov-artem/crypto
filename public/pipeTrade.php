<?php

use Crypto\Exchange\Order as Order;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$pairs = $hit->getPairs();

$v1 = 0.001;
$pair1 = 'BTCUSD';
$pair2 = 'XEMBTC';
$pair3 = "XEMUSD";

var_dump($pairs['XEMUSD']);
var_dump($pairs['XEMBTC']);

$pair2OB = $hit->getOrderBook($pair2);
$pair3OB = $hit->getOrderBook($pair3);

$altCoinsVolume = $v1 / $pair2OB->getBestAsk()->price;
var_dump($altCoinsVolume);
$altCoinsRoundVolume = floor($altCoinsVolume / $pairs[$pair2]->limit->lotSize) * $pairs[$pair2]->limit->lotSize;
var_dump($altCoinsRoundVolume);

$order = new Order();
$order->pairID = $pair1;
$order->value = $v1;
$order->type = 'market';
$order->side = 'buy';

$hit->createOrder($order);


$order = new Order();
$order->pairID = $pair2;
$order->value = $altCoinsRoundVolume;
$order->type = 'limit';
$order->side = 'buy';
$order->price = $pair2OB->getBestAsk()->price + $pairs[$pair2]->limit->priceTick;
$hit->createOrder($order);


$order = new Order();
$order->pairID = $pair3;
$order->value = $altCoinsRoundVolume;
$order->type = 'market';
$order->side = 'sell';

$hit->createOrder($order);



