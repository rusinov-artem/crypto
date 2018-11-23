<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/9/2018
 * Time: 7:53 PM
 */

use Crypto\A1;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


var_dump($hit->getPairs());

$bot = new \Crypto\Bot\BotNext();
$bot->id = "BCHSVUSD";

$inOrder = new \Crypto\Exchange\Order();
$inOrder->side = 'buy';
$inOrder->pairID = "BCHSVUSD";
$inOrder->price = 48.5;
$inOrder->value = 1;

$bot->inOrder = $inOrder;

$outOrder = clone $inOrder;
$outOrder -> side = 'sell';
$outOrder->price += 0.5;

$bot->outOrder = $outOrder;
$bs->saveBot($bot);