<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$pairs = $hit->getPairs();
foreach ($pairs as $pair)
{
    if(strpos($pair->baseCurrency, "SPC") !== false)
    {
        var_dump($pair);
    }

    if(strpos($pair->quoteCurrency, "SPC") !== false)
    {
        var_dump($pair);
    }


}
//var_dump($pairs["DASHUSD"]);
//var_dump($pairs["DASHBTC"]);die();

//var_dump($argv[1])
$token = "EDO";
if(isset($argv[1]))
{
    $token = $argv[1];
}

$pairs =
    [
        ["EOSETH", "EOSUSD"],
        ["BCNETH", "BCNUSD"],
        ["NXTETH", "NXTUSD"],
        ["DASHETH", "DASHUSD"],
        ["ZECETH", "ZECUSD"],
        ["XLMETH", "XLMUSD"],
        ["DOGEETH", "DOGEUSD"],
        ["TRXETH", "TRXUSD"],
        ["ETCETH", "ETCUSD"],
        ["XEMETH", "XEMUSD"],
        ["EOSETH", "EOSUSD"],
        ["ADAETH", "ADAUSD"],
        ["BITSETH", "BITSUSD"],
        ["VOCOETH", "VOCOUSD"],
        ["LTCETH", "LTCUSD"],
        ["EDOETH", "EDOUSD"],
        ["XRPETH", "XRPUSDT"],
        ["TRXETH", "TRXUSD"],
        ["PATETH", "PATUSD"],
        ["CSMETH", "CSMUSD"],
        ["KMDETH", "KMDUSD"],
        ["STXETH", "STXUSD"],
        ["STXETH", "STXUSD"],
        ["XDN"."ETH", "XDN"."USD"],

    ];


$pairs =
    [
        ["EOSBTC", "EOSUSD"],
        ["BCHABCBTC", "BCHABCUSD"],
        ["BCNBTC", "BCNUSD"],
        ["NXTBTC", "NXTUSD"],
        ["ETHBTC", "ETHUSD"],
        ["DASHBTC", "DASHUSD"],
        ["ZECBTC", "ZECUSD"],
        ["XLMBTC", "XLMUSD"],
        ["DOGEBTC", "DOGEUSD"],
        ["TRXBTC", "TRXUSD"],
        ["ETCBTC", "ETCUSD"],
        ["XEMBTC", "XEMUSD"],
        ["EOSBTC", "EOSUSD"],
        ["ADABTC", "ADAUSD"],
        ["BITSBTC", "BITSUSD"],
        ["BCHSVBTC", "BCHSVUSD"],
        ["VOCOBTC", "VOCOUSD"],
        ["LTCBTC", "LTCUSD"],
        ["EDOBTC", "EDOUSD"],
        ["XRPBTC", "XRPUSDT"],
        ["TRXBTC", "TRXUSD"],
        ["PATBTC", "PATUSD"],
        ["CSMBTC", "CSMUSD"],
        ["KMDBTC", "KMDUSD"],
        ["STXBTC", "STXUSD"],
        ["STXBTC", "STXUSD"],
        ["XDN"."BTC", "XDN"."USD"],
        ["DCN"."BTC", "DCN"."USD"],
        ["CMCT"."BTC", "CMCT"."USD"],
        ["KMD"."BTC", "KMD"."USD"],
        ["B2X"."BTC", "B2X"."USD"],
        ["SBTC"."BTC", "SBTC"."USDT"],
        ["SPC"."BTC", "SPCUSD"],
        ["CAT"."BTC", "CAT"."USD"],
        ["MTX"."BTC", "MTX"."USD"],

    ];


while(1)
foreach($pairs as $pair) {

    $pair1 = "BTCUSD";
    $pair2 = $pair[0];
    $pair3 = $pair[1];



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



    $dt = (new DateTime)->format("Y-m-d H:i:s");
    if ($profit > 0) {
        var_dump("$dt $ => $pair2 => $pair3 => $ PROFIT = " . ($profit));
    }
    else
    {
        //var_dump("$pair2 $profit, $fee");
    }

}

asort($list);
$list = array_reverse($list);
var_dump($list);