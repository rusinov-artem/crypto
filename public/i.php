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

$bot = new \Crypto\Bot\BotNext();
$bot->id = "SMART";

$inOrder = new \Crypto\Exchange\Order();
$inOrder->side = 'buy';
$inOrder->pairID = "SMARTUSD";
$inOrder->price = 0.0179;
$inOrder->value = 1000;

$bot->inOrder = $inOrder;

$outOrder = clone $inOrder;
$outOrder -> side = 'sell';
$outOrder->price = 0.02;

$bot->outOrder = $outOrder;
$bs->saveBot($bot);