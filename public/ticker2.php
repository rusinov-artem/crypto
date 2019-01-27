<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */

use Crypto\Bot\CircleBot as CircleBot;
use Crypto\Exchange\Order;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";


$counter = 0;
$bs = new \Crypto\Bot\BotStorage();
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$handler = new \Monolog\Handler\RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new \Monolog\Logger("hitbtc.client");
$logger->pushHandler($handler);

$botLogger = new \Monolog\Logger("BotNext");
$botLogger->pushHandler($handler);

$hit->setLogger($logger);



while(1)
{

    $bots = $bs->getAll();
    if(count($bots)<1)
    {
        sleep(1); continue;
    }

    $timeout = (1) * pow(10, 6);

    $botsList = [];
    foreach ($bots as $botID)
    {
        $botsList[] = $bs->getBot($botID);
    }

    $sellBots=[];
    $buyBots=[];

    /**
     * @var $bot CircleBot
     */
    foreach ($botsList as $bot)
    {
        if($bot->isFinished()) continue;

        /**
         * @var $activeOrders Order[]
         */
        $activeOrders = $bot->getActiveOrders();
        if(count($activeOrders)===1)
        {
            if(current($activeOrders)->side === 'sell')
            {
                $sellBots[] = $bot;
            }

            if(current($activeOrders)->side === 'buy')
            {
                $buyBots[] = $bot;
            }
        }
    }

    $r = usort($sellBots, function(CircleBot $a, CircleBot $b) {
        $aPrice = current($a->getActiveOrders())->price;
        $bPrice = current($b->getActiveOrders())->price;
        return $aPrice <=> $bPrice;
    });

    $r = usort($buyBots, function(CircleBot $a, CircleBot $b) {
        $aPrice = current($a->getActiveOrders())->price;
        $bPrice = current($b->getActiveOrders())->price;
        return $bPrice <=> $aPrice;
    });

    $si = 0;
    foreach ($sellBots as $bot)
    {
        try {
            $activeOrder = current($bot->getActiveOrders());
            $orderBefore = clone $activeOrder;

            if($hit->isOrderCanceled($activeOrder))
            {
                $bot->setFinished();
                $bs->saveBot($bot);
                continue;
            }

            $bot->client = $hit;
            $bot->tick();
            usleep($timeout);
            $si++;$counter++;
            $orderAfter = clone current($bot->getActiveOrders());

            $bs->saveBot($bot);

            if ($si >= 3) {
                break;
            }

        } catch (\Throwable $t) {

        }

    }

    $si = 0;
    foreach ($buyBots as $bot)
    {
        try {
            $activeOrder = current($bot->getActiveOrders());
            $orderBefore = clone $activeOrder;

            if($hit->isOrderCanceled($activeOrder))
            {
                $bot->setFinished();
                $bs->saveBot($bot);
                continue;
            }
            $bot->client = $hit;
            $bot->tick();
            usleep($timeout);
            $si++;$counter++;
            $orderAfter = clone current($bot->getActiveOrders());

            $bs->saveBot($bot);

            if ($si >= 3) {
                break;
            }

        } catch (\Throwable $t) {

        }

    }

    var_dump($counter);
}