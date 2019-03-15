<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */

use Crypto\Bot\BotStorage as BotStorage;
use Crypto\Bot\CircleBot;
use Crypto\Bot\Exceptions\InOrderBadPrice;
use Crypto\Exchange\Exceptions\OrderRejected as OrderRejected;
use Crypto\Exchange\Order;
use Crypto\HitBTC\Client as Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";


$counter = 0;
$bs = new BotStorage();
$hit = new Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$handler = new RotatingFileHandler(__DIR__."/../storage/log/main.log", 3);
$logger = new Logger("hitbtc.client");
$logger->pushHandler($handler);

$botLogger = new Logger("BotNext");
$botLogger->pushHandler($handler);

$hit->setLogger($logger);

function tickBots(array $bots, $hit, $bs, $timeout, $logger, $limit=3)
{
    /**
     * @var $hit Client
     * @var $activeOrder Order
     * @var $bs BotStorage
     * @var $bots \Crypto\Bot\BotNext[]
     */

    $placedOrders = $hit->getActiveOrders();

    $si = 0;
    foreach ($bots as $bot)
    {

        if($si < $limit)
        {
            try {
                $activeOrder = current($bot->getActiveOrders());

                if($hit->isOrderCanceled($activeOrder))
                {
                    $bot->setFinished();
                    $bs->saveBot($bot);
                    continue;
                }
                $bot->client = $hit;
                $bot->setLogger($logger);
                try{
                    $bot->tick();
                }
                catch (OrderRejected $e)
                {
                   $bot->finished = true;
                   $bs->saveBot($bot);
                   continue;
                }
                catch (InOrderBadPrice $e)
                {
                    //var_dump($e->getMessage());
                    continue;
                }


                $si++;

                if($activeOrder->eClientOrderID === null && $activeOrder->eOrderID === null)
                {
                    $activeOrder->status = null;
                }

                $bs->saveBot($bot);


            } catch (\Throwable $t) {
                var_dump($t->getMessage());
            }
        }
        else
        {
            try{

                $activeOrders = ($bot->getActiveOrders());

                if(count($activeOrders)<1) {
                    continue;
                }

                $activeOrder = &$activeOrders[key($activeOrders)];

                if(!array_key_exists($activeOrder->eClientOrderID, $placedOrders))
                {
                    continue;
                }

                if($activeOrder->traded > 0 ) continue;

                $hit->closeOrder($activeOrder);
                $activeOrder->status = null;
                $activeOrder->eClientOrderID = null;
                $activeOrder->eOrderID = null;
                $bs->saveBot($bot);
            }
            catch (\Throwable $t){
                var_dump($t->getMessage());
            }
        }

    }
}

function main(Client $hit, BotStorage $bs, Logger $logger, Logger $botLogger)
{
    try{
            $hit->activeOrders = null;

            $bots = $bs->getAll();
            if(count($bots)<1)
            {
               return;
            }

            $timeout = 0;

            $botsList = [];
            foreach ($bots as $botID)
            {
                /**
                 * @var $bot CircleBot
                 */
                $bot = $bs->getBot($botID);


                if($bot)
                {
                    $botsList[] = $bot;
                }
                else
                {
                    var_dump($botID." Broken");
                    $logger->error("$botID is broken");
                    $bs->deleteBot($botID);
                }

            }

            $sellBots=[];
            $buyBots=[];

            /**
             * @var $bot CircleBot
             */
            $botPairs = [];
            foreach ($botsList as $bot)
            {
                if($bot->isFinished()) continue;

                /**
                 * @var $activeOrders Order[]
                 */
                $activeOrders = $bot->getActiveOrders();
                if(count($activeOrders)===1)
                {
                    $botPairs[$bot->inOrder->pairID] = 1;
                    if(current($activeOrders)->side === 'sell')
                    {
                        if(!isset($sellBots[$bot->inOrder->pairID]))
                        {
                            $sellBots[$bot->inOrder->pairID] = [];
                        }

                        $sellBots[$bot->inOrder->pairID][] = $bot;
                    }

                    if(current($activeOrders)->side === 'buy')
                    {
                        if(!isset($buyBots[$bot->inOrder->pairID]))
                        {
                            $buyBots[$bot->inOrder->pairID] = [];
                        }

                        $buyBots[$bot->inOrder->pairID][] = $bot;
                    }
                }
            }

            foreach ($sellBots as &$sellBotsPair)
            {
                $r = usort($sellBotsPair, function(CircleBot $a, CircleBot $b) {
                    $aPrice = current($a->getActiveOrders())->price;
                    $bPrice = current($b->getActiveOrders())->price;
                    return $aPrice <=> $bPrice;
                });


                tickBots($sellBotsPair, $hit, $bs, $timeout, $botLogger, 20);
            }

            foreach ($buyBots as &$buyBotsPair)
            {
                $r = usort($buyBotsPair, function(CircleBot $a, CircleBot $b) {
                    $aPrice = current($a->getActiveOrders())->price;
                    $bPrice = current($b->getActiveOrders())->price;
                    return $bPrice <=> $aPrice;
                });

                tickBots($buyBotsPair, $hit, $bs, $timeout, $botLogger, 5);

            }


            $hit->clearCache();

    }
    catch (\Throwable $t)
    {
        var_dump($t);
    }
}


