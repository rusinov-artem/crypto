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
$bot = new \Crypto\Bot\EDOBot($hit);
$bot->id = "EDOBot130";
$bot->initBuyPrice = 1.3;
$bs->saveBot($bot);
$r = $bs->getBot('EDOBot130');
//$r = $bs->getAll();
var_dump($r);
//$bot->tick();
