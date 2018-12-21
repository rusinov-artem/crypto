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
use PDO;

class ServiceProvider
{

    public static $services;

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

    /**
     * @return PDO
     * @throws \Exception
     */
    public static function &getDB()
    {
        if(isset(static::$services['db']))
        {

            $now = new \DateTime();

            /**
             * @var $connected_at \DateTime
             */
            $connectedAt = static::$services['db.connected_at'];

            /**
             * @var $pdo PDO
             */
            $pdo = &static::$services['db'];

            if($now->sub(new \DateInterval("PT10M"))->getTimezone() > $connectedAt)
            {
                unset(static::$services['db']);
            }
            else
            {
                return $pdo;
            }

        }

        $config = include __DIR__."/../config.php";

        $dsn = "mysql:dbname={$config['db.schema']};host={$config['db.host']};charset=UTF8";

        $pdo = new PDO($dsn, $config['db.user'], $config['db.password']);
        static::$services['db'] = $pdo;
        static::$services['db.connected_at'] = new \DateTime();

        return $pdo;
    }
}