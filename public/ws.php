<?php
require __DIR__."/../vendor/autoload.php";
/*
$symbols = "1234567890qwertyuiopasdfgjklzxcvbnm";
$key = base64_encode(substr(str_shuffle($symbols), 0, 16));
$host = 'api.hitbtc.com';
$port = 443;
$timeout = 30;
$path = '/api/2/ws';
$header = "GET $path HTTP/1.1\r\n";
$header .= "Host: " . $host . ":" . $port . "\r\n";
$header .= "User-Agent: " . "BOT1" . "\r\n";
$header .= "Upgrade: websocket\r\n";
$header .= "Sec-WebSocket-Protocol: chat, superchat\r\n";
$header .= "Sec-WebSocket-Extensions: deflate-stream\r\n";
$header .= "Connection: Upgrade\r\n";
if (!empty($origin)) {
    $header .= "Origin: " . $origin . "\r\n";
}
if (!empty($headers)) {
    foreach ($headers as $headerKey => $value) {
        $header .= "$headerKey: " . $value . "\r\n";
    }
}
$header .= "Sec-WebSocket-Key: " . $key . "\r\n";
$header .= "Sec-WebSocket-Version: 13\r\n\r\n";

$cert = 'C:\web\php\cacert.pem'; // Path to certificate
$context = stream_context_create();
$result = stream_context_set_option($context, 'ssl', 'verify_host', true);
if (1) {
    $result = stream_context_set_option($context, 'ssl', 'cafile', $cert);
    $result = stream_context_set_option($context, 'ssl', 'verify_peer', true);
} else {
    $result = stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
}




if ($socket = stream_socket_client(
    ''.$host.':'.$port,
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT,
    $context)
) {
    $r = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);

    fwrite($socket, $header);

    echo $response =  fread($socket,8192);
   // fclose($socket);
} else {
    echo "ERROR: $errno - $errstr\n";
}
/***/

$client = new \Crypto\WSFrameReader('api.hitbtc.com', 443, '/api/2/ws');
$socket = $client->socket ;
$message = "{
  \"method\": \"subscribeTrades\",
  \"params\": {
    \"symbol\": \"BTCUSD\"
  },
  \"id\": 123
}";
//$message = encode($message);
$client->send($message);
$r = $client->getFrame()->getData();
//$r =fwrite($socket, $message);

//$r = $client->getFrame();

$r = socket_get_status($socket);
while(1)
{
    $r = $client->getFrame()->getData();
    $r1 = json_decode($r, true);
    //socket_recvmsg($client->socket, $m);
    //$buf =  (fread($socket,8192));
    var_dump($r1);
}


fclose($socket);


 function encode($text)
{
    $b = 129; // FIN + text frame
    $len = strlen($text);
    if ($len < 126) {
        return pack('CC', $b, $len) . $text;
    } elseif ($len < 65536) {
        return pack('CCn', $b, 126, $len) . $text;
    } else {
        return pack('CCNN', $b, 127, 0, $len) . $text;
    }
}

 function decode($data)
{
    $payloadLength = '';
    $mask = '';
    $unmaskedPayload = '';
    $decodedData = array();

    // estimate frame type:
    $firstByteBinary = sprintf('%08b', ord($data[0]));
    $secondByteBinary = sprintf('%08b', ord($data[1]));
    $opcode = bindec(substr($firstByteBinary, 4, 4));
    $isMasked = ($secondByteBinary[0] == '1') ? true : false;
    $payloadLength = ord($data[1]) & 127;

    // close connection if unmasked frame is received:
    if ($isMasked === false) {

    }

    switch ($opcode) {
        // text frame:
        case 1:
            $decodedData['type'] = 'text';
            break;

        // connection close frame:
        case 8:
            $decodedData['type'] = 'close';
            break;

        // ping frame:
        case 9:
            $decodedData['type'] = 'ping';
            break;

        // pong frame:
        case 10:
            $decodedData['type'] = 'pong';
            break;

        default:
            // Close connection on unknown opcode:
           // $this->disconnectClient($client, 1003);
            $a = 'alsdkfj';
            break;
    }

    if ($payloadLength === 126) {
        $mask = substr($data, 4, 4);
        $payloadOffset = 8;
        $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
    } elseif ($payloadLength === 127) {
        $mask = substr($data, 10, 4);
        $payloadOffset = 14;
        $tmp = '';
        for ($i = 0; $i < 8; $i++) {
            $tmp .= sprintf('%08b', ord($data[$i + 2]));
        }
        $dataLength = bindec($tmp) + $payloadOffset;
        unset($tmp);
    } else {
        $mask = substr($data, 2, 4);
        $payloadOffset = 6;
        $dataLength = $payloadLength + $payloadOffset;
    }

    if ($isMasked === true) {
        for ($i = $payloadOffset; $i < $dataLength; $i++) {
            $j = $i - $payloadOffset;
            $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
        }
        $decodedData['payload'] = $unmaskedPayload;
    } else {
        $payloadOffset = $payloadOffset - 4;
        $decodedData['payload'] = substr($data, $payloadOffset);
    }

    return $decodedData;
}
