<?php
require_once __DIR__."/../vendor/autoload.php";

function makeRequest()
{
    $config = include __DIR__."/../config.php";
    for($i=0; $i<100; $i++)
    {
        $hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);
        $pairs = $hit->getPairs();
        $book = $hit->getOrderBook("BTCUSD", 100, true);
        $orders = $hit->getActiveOrders(true);
    }

    for($i=0; $i<10000; $i++)
    {
        $a = serialize($book);
        $a = unserialize($a);
    }
}

for($i=1; $i<1000; $i++)
{
    var_dump(memory_get_peak_usage(true)/pow(10, 6)."MB " . memory_get_usage(true)/pow(10,6)." MB" );
    makeRequest();
}

