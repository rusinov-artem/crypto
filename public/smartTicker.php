<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */

use Crypto\ServiceProvider;

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

$bots = $bs->getAll();

$orders = [];
foreach ($bots as $botID)
{
    $bot = $bs->getBot($botID);
    $orders = array_merge($orders, $bot->getOrders());
}
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///  == Получить список пользователей, боты которых должн быть обработанны
///  == Получить список пар по которым есть работающие боты
//
///
///        По каждой паре выполнить
///
//////////////////////////////////////////////////////////
// 1. Получить все трейды начиная с последней проверки
//      по текущему клиенту
//
// 2. Для каждого трейда создать запись в таблице трейдов, с флагом не обработан
// 3. Для каждого трейда найти ордер из таблицы ордеров, и обновить запись ордера
//      Найти бота, которому принадлежит обновленный ордер, запустить tick() бота
//        Отметить обработанный трейд флагом
//
///////////////////////////////////////////////////////////

$pair = "BCHSVUSD";


$tm = new \Crypto\HitBTC\TradeManager($hit, ServiceProvider::getCryptonRepository(), ServiceProvider::getEventDispatcher());

$botManager = new \Crypto\Bot\BotsManager();
$botManager->subscribeTrades($tm);

$orderManager = new \Crypto\HitBTC\OrderManager(ServiceProvider::getDBALConnection(), ServiceProvider::getEventDispatcher());
$orderManager->subscribeTrades($tm);


$tm->loadTrades(0, $pair);

