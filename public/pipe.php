<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

while(1) {
    usleep(0.5 * pow(10, 6));
    $v1 = 0.01; // BTC
    $p1 = $hit->getOrderBook("BTCUSD")->getBestAsk()->price;
    $p2 = $hit->getOrderBook("BTGBTC")->getBestAsk()->price;
    $p3 = $hit->getOrderBook("BTGUSD")->getBestBid()->price;


    $before = $v1 * $p1; // USD

    $v2 = $v1 / $p2; //BCHSV volume


    $result = $v2 * $p3;
//var_dump("Before = $before");
//var_dump("Result = $result");

    $fee = ($before * 0.001) * 3;
    $profit = $before - $result - $fee;
    //var_dump($fee);
    //var_dump($before - $result);
    if ($profit > 0) {
        var_dump("$ => BTC => BCHSV => $ PROFIT = " . ($profit));
    }
    else
    {
        //var_dump($profit, $fee);
    }

}