<?php
var_dump(0.3/ 0.1);
$db = new \PDO("sqlite:".__DIR__."/../storage/db/crypton.db");


die();
require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$bin = new \Crypto\Binance\Client();
$bin->apiKey = $config['binance.api.key'];
$bin->secretKey = $config['binance.api.secret'];

$pairs = $bin->getPairs();
foreach ($pairs as $pair)
{
    if(strpos($pair->id,"BTCUSDT") !== false)
    {
        var_dump($pair->id);
        var_dump($pair->limit);
    }
}



$order = new \Crypto\Exchange\Order();
$order->pairID = "BTCUSDT";
$order->type = "limit";
$order->value = 0.0025;
$order->price = 4000;
$order->side = 'sell';
$order->id = "90YuiMMWy2nnGdPfSbIRho";

//var_dump($bin->getNonZeroBalance());
//var_dump($bin->getMinimalValue($order->pairID, 4000));

//
//$o = $bin->createOrder($order);

$orders = $bin->getActiveOrders();

$o = $bin->closeOrder($o);


