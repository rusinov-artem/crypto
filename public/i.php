<?php

use Crypto\Bot\BotFactory;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


//$pair = "BCHSVUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";
//$pair = "EOSUSD";
//$pair = "ETHUSD";
//$pair = "TRXUSD";
//$pair = "PBTTBTC";
//$pair = "LTCUSD";
$pair = "USDUSDC";
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

//edo ada eos xrp xlm ltc

$bPrice = 38.81;
$bots = BotFactory::spreadAttackEx($pair, -0.001, $bPrice, 0.35,  1, 351);
foreach($bots as $bot){
    //$bs->saveBot($bot);
    var_dump("{$bot->inOrder->price} - {$bot->outOrder->price}");
};

var_dump($pair, $bPrice);




