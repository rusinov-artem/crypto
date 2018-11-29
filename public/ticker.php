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

while(1)
{


    $bots = $bs->getAll();


    foreach ($bots as $botID)
    {
        usleep((2/ count($bots) )* pow(10, 6));
        var_dump($botID);
        try{
            $bot = $bs->getBot($botID);
            $bot->client = $hit;
            $bot->tick();
            $bs->saveBot($bot);
        }
        catch (\Throwable $t)
        {
            var_dump($t);
        }

    }


    $counter++;
    var_dump($counter);
}