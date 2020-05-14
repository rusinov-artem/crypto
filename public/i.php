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
//$pair = "USDUSDC";
//$pair = 'CLOUSD';
//$pair = 'DOGEUSD';
$pair = 'XRPUSDT';
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

$bPrice = 0.224559;
//$sPrice = 8800.86;

//$bot = BotFactory::simple($pair, 0.00002, $bPrice, $bPrice - $sPrice, 1);
//var_dump("{$bot->inOrder->price} - {$bot->outOrder->price}");
////$bs->saveBot($bot);
//die();

$bots = BotFactory::spreadAttackEx($pair, -0.1, $bPrice, 1, 15, 65);
foreach($bots as $bot){
    $bs->saveBot($bot);
    var_dump("{$bot->inOrder->price} - {$bot->outOrder->price}");
};

var_dump($pair, $bPrice);
