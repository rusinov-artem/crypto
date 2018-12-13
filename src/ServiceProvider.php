<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 12/8/2018
 * Time: 1:23 AM
 */

namespace Crypto;


use Crypto\Exchange\Analytics;
use Crypto\Exchange\ClientInterface;

class ServiceProvider
{
    public static function getLogger($file, $title)
    {
        $handler = new \Monolog\Handler\RotatingFileHandler(__DIR__."/../storage/log/$file.log", 3);
        $logger = new \Monolog\Logger($title);
        $logger->pushHandler($handler);
        return $logger;
    }

    public static function getAnalytics(ClientInterface $client)
    {
        return new Analytics($client);
    }
}