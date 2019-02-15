<?php

use HemiFrame\Lib\WebSocket\Client as Client;

require __DIR__."/../vendor/autoload.php";

$pn = getprotobyname('udp');
var_dump($pn);

$ws = new \HemiFrame\Lib\WebSocket\WebSocket('api.hitbtc.com', 443);
$ws->setEnableLogging(true);


$client = $ws->connect('/api/2/ws');

$ws->on("receive", function($client, $data)  {
    var_dump($data);
});

$ws->on(/**
 * @param Client $client
 */
    'send', function (Client $client)use(&$ws){
        $ws->loopRead($client);
});

$ws->sendData($client, '{  "method": "subscribeTrades",  "params": {"symbol": "BCHSVUSD"  },  "id": 123}');

var_dump("OK");