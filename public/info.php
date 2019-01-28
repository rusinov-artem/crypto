<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/13/2018
 * Time: 5:42 AM
 */

use Crypto\A1;
use Crypto\Exchange\Order;
use Crypto\Exchange\Trade;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);
$pair = "BCHSVUSD";

$balances = $hit->getNonZeroBalance();

/**
 * @var $lastTrade Trade
 */
$lastTrade = null;
$hit->getPairTrades($pair, function (Trade $trade) use (&$lastTrade)
{
    $lastTrade = $trade;
    return false;
});

$tProfit = 0;
$profit = 0;
$tBalance = 0;
$hit->chunkAccountTrades($pair, function (Trade $trade) use (&$profit, &$tBalance)
{


    if($trade->side == 'sell')
    {
        $profit += $trade->value * $trade->price;
        $tBalance -= $trade->value;
    }

    if($trade->side == 'buy')
    {
        $profit -= $trade->value * $trade->price;
        $tBalance += $trade->value;
    }

    if($trade->date->getTimezone() < (new \DateTime("2018-12-15 00:00:00"))->getTimezone())
        return false;

});
$tProfit = $profit;
$profit += $tBalance * $lastTrade->price;

$active = $hit->getActiveOrders();
foreach ($active as $k => $order)
{
    if($order->side == 'buy' || $order->pairID !== $pair)
    {
        unset($active[$k]);
    }
}

usort($active, function(Order $a, Order $b){
   return $b->price <=> $a->price;
});

$usdBalance = $balances['USD'];
$pairBalance = $balances['BCHSV'];

$usdEstimate = ($usdBalance->reserved + $usdBalance->available)
    +($pairBalance->available + $pairBalance->reserved) * $lastTrade->price;

$usdBestCase = ($usdBalance->reserved + $usdBalance->available);

$ultimateCase = ($usdBalance->reserved + $usdBalance->available)
    + ($pairBalance->available + $pairBalance->reserved) * current($active)->price;


foreach ($active as $order)
{
    $usdBestCase += $order->price * $order->value;
    $tProfit += $order->price * $order->value;
}

$bchsvBalance = $pairBalance->available + $pairBalance->reserved;

$result =
("\nUSD now $usdEstimate\n").
("USD best case $usdBestCase\n").
("USD ultimate case $ultimateCase\n").
("current profit $profit\n").
"tprofit = $tProfit\n".
"BCHSV balance = $bchsvBalance";

var_dump($result);

