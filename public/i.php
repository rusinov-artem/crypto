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
//$pair = "VOCOUSD";
//$pair = "ADAUSD";

$bPrice = 0.96;
$bots = BotFactory::spreadAttack($pair,  10 / $bPrice, $bPrice, $bPrice * 0.002, 1.02, 1);
foreach($bots as $bot){$bs->saveBot($bot);};

var_dump($pair, $bPrice);




