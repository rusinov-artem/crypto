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
    sleep(1);

    $bots = $bs->getAll();
    var_dump($bots);

    foreach ($bots as $botID)
    {
        var_dump($botID);
        $bot = $bs->getBot($botID);
        $bot->client = $hit;
        $bot->tick();
        $bs->saveBot($bot);
    }


    $counter++;
    var_dump($counter);
}