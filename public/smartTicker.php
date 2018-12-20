<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/10/2018
 * Time: 4:43 PM
 */
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
