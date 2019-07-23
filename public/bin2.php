<?php
require __DIR__."/../vendor/autoload.php";


$config = include __DIR__ . "/../config.php";



$bin2 = new \Crypto\Binance\Client();
$bin2->apiKey = $config['binance.api.key'];
$bin2->secretKey = $config['binance.api.secret'];
$lk2 = $bin2->getActiveOrders();
//$lk2 = $bin2->getNonZeroBalance();

/**
 * @var \Crypto\Exchange\Order $order
 */
foreach ($lk2 as &$order)
{
    if($order->side==='buy')
    {
        $sOrder = clone $order;
    }
}




$sOrder->id = null;
$r = $bin2->createOrder($sOrder);
var_dump($r);

$r = $bin2->closeOrder($r );
var_dump($r);
