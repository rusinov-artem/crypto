<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 12/8/2018
 * Time: 1:23 AM
 */

namespace Crypto;


use Crypto\Crypton\CryptonRepository;
use Crypto\Exchange\Analytics;
use Crypto\Exchange\ClientInterface;
use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ServiceProvider
{

    public static $services;

    /**
     * @var EventDispatcher
     */
    public static $dispatcher;

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

    public static function getCryptonRepository()
    {
        $repo = new CryptonRepository(self::getDBALConnection());
        return $repo;
    }

    public static function getDBALConnection()
    {
        $c= include __DIR__."/../config.php";

        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname' => $c['db.schema'],
            'user' => $c['db.user'],
            'password' => $c['db.password'],
            'host' => $c['db.host'],
            'driver' => 'pdo_mysql',
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);


        return $conn;
    }

    public static function getEventDispatcher()
    {
        if(!self::$dispatcher)
        {
            self::$dispatcher = new EventDispatcher();
        }

        return self::$dispatcher;
    }

}