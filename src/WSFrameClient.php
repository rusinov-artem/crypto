<?php


namespace Crypto;


use HemiFrame\Lib\WebSocket\Client;

class WSFrameClient
{

    public $socket;
    private $host;
    private $port;
    private $path;
    public $userAgent = "HITBOT";
    public $headers;
    public $casertPath = "C:\web\php\cacert.pem";

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
        $header .= "Host: {$this->port}:{$this->host}\r\n";
        $header .= "User-Agent: {$this->userAgent}\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Sec-WebSocket-Protocol: chat, superchat\r\n";
        $header .= "Sec-WebSocket-Extensions: deflate-stream\r\n";
        $header .= "Connection: Upgrade\r\n";

        if (!empty($this->eaders)) {
            foreach ($this->headers as $headerKey => $value) {
                $header .= "$headerKey: " . $value . "\r\n";
            }
        }
        $header .= "Sec-WebSocket-Key: " . $key . "\r\n";
        $header .= "Sec-WebSocket-Version: 13\r\n\r\n";


        $context = stream_context_create();
        stream_context_set_option($context, 'ssl', 'verify_host', true);
        stream_context_set_option($context, 'ssl', 'cafile', $this->casertPath);
        stream_context_set_option($context, 'ssl', 'verify_peer', true);


        if ($this->socket = stream_socket_client(
                $host.':'.$port,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            ))
        {
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
            fwrite($this->socket, $header);
            echo $response =  fread($this->socket,8192);
        } else {
            throw new \Exception("Unable to create socket", 1);
        }
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

    private function encode($text)
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
        if(strlen($this->currentStr) < 1)
        {
            $this->currentStr = $this->read();
        }

        if(strlen($this->currentStr)<1)
        {
            return false;
        }

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

}