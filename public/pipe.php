<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$pairs = $hit->getPairs();
//var_dump($pairs["DASHUSD"]);
//var_dump($pairs["DASHBTC"]);die();

//var_dump($argv[1])
$token = "EDO";
if(isset($argv[1]))
{
    $token = $argv[1];
}

$tokens =
    [
      "BCHSV", "BCHABC", "VOCO", "EDO", "ETC", "LTC", "EOS", "ZEC",
      "TRX", "DASH", "XLM", "DOGE", "XDN", "XEM", "MAID", "BTG",
      "BNT", "ONT", "SMART", "NEO",
      "XMR", "ADA", "QTUM", "IOTA",  "BITS", "PPC",
      "WIKI", "EDG", "TDS", "FUN", "STX", "LOC", "DIM"
    ];

while(1)
foreach($tokens as $token) {

    $pair1 = "BTCUSD";
    $pair2 = "{$token}BTC";
    $pair3 = "{$token}USD";



    usleep(0.5 * pow(10, 6));
    $v1 = 0.01; // BTC
    $px1 = $hit->getBuyPrice($pair1, $v1);
    $p1 = $px1/ $v1;

    $p2 = $hit->getOrderBook($pair2)->getBestAsk()->price;
    $px2 = $hit->getBuyPrice($pair2, $v1 / $p2 );
    $p2 = $px2 / ($v1 / $p2);


    $p3 = $hit->getOrderBook($pair3)->getBestBid()->price;
    $px3 = $hit->getSellPrice($pair3, ($v1 / $p2));

    $before = $v1 * $p1; // USD


    $v2 = $v1 / $p2; //BCHSV volume

    $result = $v2 * $p3;

//var_dump("Before = $before");
//var_dump("Result = $result");

    $fee = ($before * 0.001) * 2;
    $profit = $result - $before - $fee;
    //var_dump($fee);
    //var_dump($before - $result);

    $list[$token] = $profit;



    if ($profit > 0) {
        var_dump("$ => BTC => BCHSV => $ PROFIT = " . ($profit));
    }
    else
    {
        //var_dump($profit, $fee);
    }

}

asort($list);
$list = array_reverse($list);
var_dump($list);