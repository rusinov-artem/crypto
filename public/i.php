<?php

use Crypto\Bot\BotFactory;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


//$pair = "BCHSVUSD";
$pair = "EDOUSD";
//$pair = "BTCUSD";
//$pair = "EOSUSD";
//$pair = "ETHUSD";
//$pair = "TRXUSD";
//$pair = "PBTTBTC";
//$pair = "LTCUSD";
//$pair = 'CLOUSD';
//$pair = 'DOGEUSD';
//$pair = 'XRPUSDT';
//$pair = 'XLMUSD';
//$pair = "DASHUSD";
//$pair = 'BTTUSD';
//$pair = 'BCHABCUSD';
//$pair = 'ZECUSD';
//$pair = "NEOUSD";
//$pair = "ETCUSD";
//$pair = "ONTUSD";
//$pair = "XEMUSD";
//$pair = "ZRXUSD";

$bPrice = 0.789875;
$bots = BotFactory::spreadAttack($pair,  1 / $bPrice, $bPrice, $bPrice * 0.001, $bPrice * 0.05, 100);
foreach($bots as $bot){$bs->saveBot($bot);};

var_dump($pair, $bPrice);




