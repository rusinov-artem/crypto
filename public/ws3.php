<?php
require __DIR__."/../vendor/autoload.php";
ini_set('display_errors',1);
ini_set('display_startup_errors', 1);
$client = new \Crypto\WSFrameClient('api.hitbtc.com', 443, '/api/2/ws');
$client->initSocket();
$message = "
{
  \"method\": \"subscribeTrades\",
  \"params\": {
    \"symbol\": \"BTCUSD\"
  },
  \"id\": 123
}\r\n\r\n\r\n";
$r = $client->send($message);

$r = socket_get_status($client->socket);
$r = $client->read();
//$r = $client->write('���Լ��������掰�������������ړ��������������ç�・Ь���̢��ش�����������������');

$frame = $client->getFrame();

var_dump($frame);