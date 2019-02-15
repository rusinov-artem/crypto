<?php
require __DIR__."/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";

$client = new \Crypto\WSFrameClient('api.hitbtc.com', 443, '/api/2/ws');
$socket = $client->socket ;
$sKey = '';
$message = "{
  \"method\": \"login\",
  \"params\": {
    \"algo\": \"BASIC\",
    \"pKey\": \"{$config['hitbtc.api.key']}\",
    \"sKey\": \"{$config['hitbtc.api.secret']}\"
  }
}";

$client->send($message);
$frame = $client->getFrame();

$message = "{ \"method\": \"getTradingBalance\", \"params\": {}, \"id\": 123 }";
$client->send($message);
//stream_set_blocking($client->socket, false);


$r = socket_get_status($socket);
while(1)
{
    $frame = $client->getFrame();
    if(!$frame) continue;
    $r = $frame->getData();
    $r1 = json_decode($r, true);
    $a = 0;
    //var_dump($r1);
}


fclose($socket);
