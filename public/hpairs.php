<?php
require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pairs = $hit->getPairs();
foreach ($pairs as $pair)
{
    /**
     * @var $pair \Crypto\Exchange\Pair
     */
    if(false !== strpos($pair->baseCurrency, "XLM"))
    {
        var_dump($pair->id);
        var_dump($pair->limit->lotSize);
    }

//    if(false !== strpos($pair->quoteCurrency, "XRP"))
//    {
//        var_dump($pair->id);
//    }
//
//    if(false !== strpos($pair->id, "XRP2"))
//    {
//        var_dump($pair->id);
//    }
}