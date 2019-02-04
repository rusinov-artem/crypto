<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */

use Crypto\Bot\BotStorage as BotStorage;
use Crypto\Bot\CircleBot as CircleBot;
use Crypto\Exchange\Order;
use Crypto\HitBTC\Client as Client;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";


$counter = 0;
$bs = new BotStorage();
$hit = new Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$handler = new \Monolog\Handler\RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new \Monolog\Logger("hitbtc.client");
$logger->pushHandler($handler);

$botLogger = new \Monolog\Logger("BotNext");
$botLogger->pushHandler($handler);

$hit->setLogger($logger);

$bots = $bs->getAll();


$botsList = [];
$profit = 0;
$mProfit = 0;
$mProfitBot = null;
$botsWithProfit = 0;
foreach ($bots as $botID)
{
    /**
     * @var $bot CircleBot
     */
    $botsList[] = $bot =  $bs->getBot($botID);
    $profit += $bot->profit;
    if($bot->profit > $mProfit) {
        $mProfit = $bot->profit;
        $mProfitBot = clone $bot;
    }
    if($bot->profit > 0 ) $botsWithProfit++;

    if($bot->inOrder->value === 0.1)
    {
        $bot->inOrder->value = 0.05;
        $bot->outOrder->value = 0.05;
        $bs->saveBot($bot);
    }



}
/**
 * @var $mProfitBot CircleBot
 */
//$mProfitBot->inOrder->value = 0.5;
//$mProfitBot->outOrder->value = 0.5;
//$bs->saveBot($mProfitBot);

var_dump($profit);
var_dump($mProfit);
var_dump($botsWithProfit);
//var_dump($mProfitBot);