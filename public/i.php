<?php

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);


$pair = "BCHSVUSD";
//$pair = "EDOUSD";
//$pair = "BTCUSD";
//$pair = "EOSUSD";
//$pair = "ETHUSD";
//$pair = "TRXUSD";
//$pair = "PBTTBTC";
$pair = "LTCUSD";

$bots = \Crypto\Bot\BotFactory::spreadAttack($pair, 0.05, 43.7, 43.7*0.005, 43.7*0.02, 20);
foreach($bots as $bot){$bs->saveBot($bot);};

die();
$bot = \Crypto\Bot\BotFactory::simple($pair, 0.1, 66, 3, 10, "BCHSV-1");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.2, 65.5, 4, 10, "BCHSV-2");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.3, 65, 5, 10, "BCHSV-3");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.4, 64, 2, 10, "BCHSV-4");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.5, 63, 2.5, 10, "BCHSV-5");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.6, 62, 3, 10, "BCHSV-6");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.7, 60, 4, 10, "BCHSV-7");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.8, 59, 5, 10, "BCHSV-8");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 0.9, 58, 6, 10, "BCHSV-9");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 57, 18, 10, "BCHSV-10");
$bot->groupID = "SP";
$bs->saveBot($bot);

$bot = \Crypto\Bot\BotFactory::simple($pair, 1, 56, 19, 10, "BCHSV-11");
$bot->groupID = "SP";
$bs->saveBot($bot);





