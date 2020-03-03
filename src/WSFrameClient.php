<?php


namespace Crypto;


use HemiFrame\Lib\WebSocket\Client;

class WSFrameClient
{

    /**
     * @var \DateTime
     */
    public $lastTime;
    public $socket;
    private $host;
    private $port;
    private $path;
    public $userAgent = "HITBOT";
    public $headers;
    public $casertPath = __DIR__."/cacert.pem";
    public $proxy = null;

    public $currentStr = '';

    public $onFrameReady = [];

    public function __construct($host, $port, $path, $proxy = null)
    {
        $this->lastTime = new \DateTime();
        $this->path = $path;
       $this->host = $host;
       $this->port = $port;
       $this->proxy = $proxy;
       $this->initSocket();

    }

    public function __destruct()
    {
        var_dump("DESTRUCTOR");
        fclose($this->socket);
    }

    public function getHeaders()
    {
        $symbols = "1234567890qwertyuiopasdfgjklzxcvbnm";
        $key = base64_encode(substr(str_shuffle($symbols), 0, 16));


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
        return $header;
    }

    public function initSocket()
    {

        $symbols = "1234567890qwertyuiopasdfgjklzxcvbnm";
        $key = base64_encode(substr(str_shuffle($symbols), 0, 16));
        $host = $this->host;
        $port = $this->port;

        $path = $this->path;
        $header = "GET $path HTTP/1.1\r\n";
        $header .= "Host: {$this->host}:{$this->port}\r\n";
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
        stream_context_set_option($context, 'ssl', 'verify_host', false);
        stream_context_set_option($context, 'ssl', 'cafile', $this->casertPath);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);



        //  stream_context_set_option($context, 'ssl', 'peer_name ', $host);



        if($this->proxy)
        {
            $p = parse_url($this->proxy);
            $this->socket = stream_socket_client(
                "{$p['host']}:{$p['port']}",
                $errno,
                $errstr,
                1,
                STREAM_CLIENT_CONNECT,
                $context
            );



            $hash = base64_encode("{$p['user']}:{$p['pass']}");
            $proxyh = "CONNECT {$host}:{$port} HTTP/1.1\r\n";
            $proxyh .= "Proxy-Authorization: Basic {$hash}\r\n\n";

            $r = fwrite($this->socket, $proxyh);
            $response =  fread($this->socket,8192);
            $r = (stripos($response, "HTTP/1.0 200 Connection established" ) !== false);
            if(!$r){
                throw  new \Exception("PROXY ERROR. $response", -2);
            }
            stream_set_blocking ($this->socket, true);
            $m = stream_get_meta_data($this->socket);
            $r = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
            $a = 0;

        }
        else{
            $this->socket = stream_socket_client(
                $host.':'.$port,
                $errno,
                $errstr,
                1,
                STREAM_CLIENT_CONNECT,
                $context
            );
            $r = stream_socket_enable_crypto($this->socket, true, SSL_VERSION_TLSv1_2);
        }



        if ($this->socket)
        {

            $r = fwrite($this->socket, $header);
            $response =  fread($this->socket,8192);
            $r = preg_match("/HTTP\/1\.1\ 101\ Switching\ Protocols/", $response);
            if(!$r)
            {
                //var_dump($response);
                if(preg_match("/Too many requests/", $response))
                {
                    throw new \Exception("Too many requests", 429);
                }
            }

            stream_set_blocking($this->socket, false);

        } else {
            throw new \Exception("Unable to create socket", -1);
        }
    }

    public function read()
    {
        $s = [$this->socket];
        $k= [];
        //stream_set_blocking($this->socket,true);
        $r =  fread($this->socket, 40960);
        //stream_set_blocking($this->socket,false);
        if(false === $r){
            throw  new \Exception("Unable to fread", -1);
        }
        return $r;
    }

    public function write($message)
    {
       return  fwrite($this->socket, $message);
    }

    public function send($message)
    {
        echo "SEND $message\n";
        $message = $this->encode($message);
        return $r = fwrite($this->socket, $message, strlen($message));
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

    public function pong()
    {
        $frame = chr(bindec("10001010")) . chr(bindec("10000000")) ;
        return $r = fwrite($this->socket, $frame, strlen($frame));
    }

    public function ping()
    {
        $frame = chr(bindec("10001001")) . chr(bindec("10000000")) ;
        return $r = fwrite($this->socket, $frame, strlen($frame));
    }

    public function getFrame()
    {
        if(empty($this->currentStr))
        {
            $this->currentStr = $this->read();
        }

        if(empty($this->currentStr))
        {
            return false;
        }

        if(!empty($this->currentStr)){
            $this->lastTime = new \DateTime();

            $frame =  $this->buildFrame($this->currentStr);
            if($frame instanceof WSFrame){
                $this->triggerFrameReady($frame);
                return $frame;
            }

        }

        return false;

    }

    public function buildFrame($str)
    {

        $frame = new WSFrame();
        $frame->initHeaderInfo($str);

        if($frame->opcode === 0x9)
        {
            $r = $this->pong();
        }

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
            $stopper = 0; $hl = 990000;
            do{

               // usleep(0.1 * pow(10, 6));
                $data = $this->read();
                $l = strlen($str);
                if(strlen($data)<1)
                {
                    $stopper++;
                    //echo "empty data! len={$frame->offset} + {$frame->dataLength} \n";
                    //usleep(0.01 * pow(10, 6));
                }

                $str .= $data;
                $len = strlen($str);

                $sum =  $frame->offset + $frame->dataLength;
            }while( ($len < $sum) && $stopper < $hl);
            $dt = (new \DateTime())->format("Y-m-d H:i:s");
            if($stopper >= $hl){
                echo "\n{$dt} do while warning $stopper\n";
                $ss = substr($str, -100);
                echo "\n{$dt} do while warning $ss\n";
                echo "\n{$dt} do while warning $len < $sum \n";
                var_dump($this->socket);
                throw new \Exception("Unable to build frame", -1);
            }
            echo "\n{$dt} goto warning 1\n";
            goto m1;
        }

    }

    public function onFrameReady($callbackName, callable $callback ){
        $this->onFrameReady[$callbackName] = $callback;
    }

    public function triggerFrameReady($frame)
    {
        foreach ($this->onFrameReady as $callback){
            call_user_func_array($callback, ['frame'=>$frame]);
        }
    }

}