<?php


namespace Crypto;

use Event;
use EventBase;

class Listener
{
    public $key;
    public $secret;
    public $lastTime;
    public static $isRuning = false;

    /**
     * @var WSFrameClient
     */
    public $wsClient;
    /**
     * @var Listener[]
     */
    public static $listeners;
    public static $increment=0;

    /**
     * @var EventBase
     */
    public static $eventBase;
    public static $proxyList;
    public $event = null;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    private function getProxy()
    {
        if(!static::$proxyList){
            $file = __DIR__ . "/../public/proxy.data";
            static::$proxyList = $data = unserialize(file_get_contents($file));
        }

        $proxy = array_shift(static::$proxyList);
        array_push(static::$proxyList, $proxy);
        $proxy = "tcp://".$proxy;
        $p = parse_url($proxy);
        return $proxy;
    }

    public function init()
    {

        $n = 0;
        m1:
        $this->log('INIT: Loop m1');
        try{

            if(!Listener::$isRuning){
                Listener::$isRuning=true;
                Listener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
                Listener::$isRuning = false;
            }

            $proxy =  $this->getProxy();
            $client = new \Crypto\WSFrameClient('api.hitbtc.com', 443, '/api/2/ws', $proxy);
            $this->log('INIT: client created');
        }
        catch (\Throwable $t)
        {
            $this->log('INIT: '.exceptionToString($t));
            var_dump($t->getMessage());
            if($t->getCode() === 429 ){
                $n+=$n*(0.1);
            }

            if($t->getCode() === -2){
                var_dump($proxy);
                $p = array_pop(static::$proxyList);
                var_dump($p);
            }

            if(!Listener::$isRuning){
                Listener::$isRuning = true;
                Listener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
                Listener::$isRuning = false;
            }

            if(count(static::$proxyList)>0){
                goto m1;
            }
            else
            {
                var_dump("NO PROXY");
            }

        }

        static::$increment++;
        $nonce = 'HELLO';
        $hash = hash_hmac('sha256', $nonce, $this->secret);

        $message = "{
              \"method\": \"login\",
              \"params\": {
                \"algo\": \"HS256\",
                \"pKey\": \"{$this->key}\",
                \"nonce\": \"{$nonce}\",
                \"signature\": \"{$hash}\"
              }
            }";

        $r = $client->send($message);
        if(!$r){
            $this->log("Unable to send $message");
        }

        $id = static::$increment;
        $message = "{ \"method\": \"getTradingBalance\", \"params\": {}, \"id\": {$id} }";

        $r = $client->send($message);
        if(!$r){
            $this->log("Unable to send $message");
        }

        $message = json_encode([ 'method'=>'subscribeReports', 'params'=>[], 'id'=>$id, ]);
        $r = $client->send($message);
        if(!$r){
            $this->log("Unable to send $message");
        }
        stream_set_timeout($client->socket, 100);
        stream_set_blocking($client->socket, false);

        $client->onFrameReady('main', function ($frame, WSFrameClient $client){

            if($frame && (9 != $frame->opcode))
            {
                $msg = $frame->getData();
                $msg = substr($msg, 0, 100);
                $dt = (new \DateTime())->format("Y-m-d H:i:s");
                $this->log("#[{$client->id}] [{$frame->opcode}] [length={$frame->dataLength}] {$msg}");
            }

        });

        /**
         * @var $event Event
         */
        $me = $this;
        $this->event = new Event(Listener::$eventBase, $client->socket, Event::READ | Event::PERSIST, function($socket, $n, $x)use($client, &$me){
            try{

                if(strlen($client->currentStr)){
                    $a = 0;
                }

                $frame = $client->getFrame();
                if(!$frame){
                    $a = 0;
                }
            }
            catch (Throwable $t)
            {
                $frame = null;
                $this->log(exceptionToString($t));
                $this->reinit();
                $this->log("inited");
                return;
            }

            if(!$me->event->pending){
                $this->log("event => false");
            }

        });

        unset($this->wsClient);
        $this->wsClient = $client;
        Listener::$listeners[$this->key] = &$this;
        $this->event->add(1);
    }

    public function reinit($timeout=1){

        $me = $this;
        $te = Event::timer(Listener::$eventBase, function ($n) use(&$te, &$me){
            $this->log("REINIT {$me->key}");
            $me->init();
        }, null);
        $te->addTimer($timeout);

    }

    public function log($m)
    {
        $dt = (new \DateTime())->format("Y-m-d H:i:s");
        $m =  "[{$dt}] {$this->key} {$m}\n";
        echo $m;
    }

    public function __destruct()
    {
        var_dump("Listener destructed");
        $this->wsClient = null;

    }
}