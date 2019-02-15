<?php


namespace Crypto;


use HemiFrame\Lib\WebSocket\Client;

class WSFrameReader
{

    public $socket;
    private $host;
    private $port;
    private $path;

    private $currentStr = '';

    public function __construct($host, $port, $path)
    {
        $this->path = $path;
       $this->host = $host;
       $this->port = $port;
       $this->initSocket();
       //$this->handshake();
    }
    public function initSocket()
    {

        $symbols = "1234567890qwertyuiopasdfgjklzxcvbnm";
        $key = base64_encode(substr(str_shuffle($symbols), 0, 16));
        $host = $this->host;
        $port = $this->port;

        $path = $this->path;
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




        if ($this->socket = stream_socket_client(
            ''.$host.':'.$port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context)
        ) {
            $r = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);

            fwrite($this->socket, $header);

            echo $response =  fread($this->socket,8192);
            // fclose($socket);
        } else {
            echo "ERROR: $errno - $errstr\n";
        }
    }


    private function parseHeaders(string $headers) : array
    {
        $headersArray = explode("\r\n", $headers);
        $array = [];
        if (count($headersArray) > 1) {
            foreach ($headersArray as $header) {
                $headerContentArray = explode(":", $header, 2);
                if (!empty($headerContentArray[1])) {
                    $array[$headerContentArray[0]] = trim($headerContentArray[1]);
                }
            }
        }

        return $array;
    }

    public function read()
    {
        $r =  fread($this->socket, 8192);
        return $r;
    }

    public function write($message)
    {
       return  fwrite($this->socket, $message);
    }

    public function send($message)
    {
        $message = $this->encode($message);
        return fwrite($this->socket, $message, strlen($message));
    }

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

    public function getFrame()
    {
        while(strlen($this->currentStr) < 1)
            $this->currentStr = $this->read();

        if($this->currentStr)
            return $this->buildFrame($this->currentStr);

        return false;

    }

    public function buildFrame($str)
    {

        $frame = new WSFrame();
        $frame->initHeaderInfo($str);

        m1:
        if(strlen($str) > $frame->offset + $frame->dataLength)
        {
            $rawData = substr($str, $frame->offset, $frame->dataLength);
            $this->currentStr = substr($str, $frame->offset + $frame->dataLength);
            $frame->rawData = $rawData;
            return $frame;
        }
        elseif(strlen($str) == $frame->offset + $frame->dataLength)
        {
            $frame->rawData = substr($str, $frame->offset);
            $this->currentStr = '';
            return $frame;
        }
        elseif(strlen($str) < $frame->offset + $frame->dataLength)
        {
            do{
                $str .= $this->read();
                $len = strlen($str);

            }while( $len < $frame->offset + $frame->dataLength);
            goto m1;
        }

    }

    private function hybi10Encode($payload, $type = 'text', $masked = true)
    {
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if ($frameHead[2] > 127) {
               // $this->disconnectClient($client, 1004);
                return false;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        // append payload to frame:
        $framePayload = array();
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        return $frame;
    }
}