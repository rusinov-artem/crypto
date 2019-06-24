<?php
require __DIR__."/../vendor/autoload.php";


$config = include __DIR__ . "/../config.php";

$bin1 = new \Crypto\Binance\Client();
$bin1->apiKey = $config['il1.binance.api.key'];
$bin1->secretKey = $config['il1.binance.api.secret'];
$lk1 = $bin1->getListenKey();
var_dump($lk1);

$bin2 = new \Crypto\Binance\Client();
$bin2->apiKey = $config['il2.binance.api.key'];
$bin2->secretKey = $config['il2.binance.api.secret'];
$lk2 = $bin2->getListenKey();
var_dump($lk2);
