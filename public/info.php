<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/13/2018
 * Time: 5:42 AM
 */

use Crypto\A1;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


var_dump($hit->getNonZeroBalance());
