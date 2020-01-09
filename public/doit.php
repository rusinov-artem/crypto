<?php

use Crypto\WSFrameClient;

include __DIR__."/../vendor/autoload.php";
ini_set('display_startup_errors',1);
ini_set('display_errors', 1);

$keys = file_get_contents(__DIR__."/../storage/hitkeys.data");
$keys = unserialize($keys);
shuffle($keys);
var_dump($keys[0]);

class TradeListener
{
    public $key;
    public $secret;
    public $lastTime;

    /**
     * @var WSFrameClient
     */
    public $wsClient;
    /**
     * @var TradeListener[]
     */
    public static $listeners;
    public static $id=0;
    /**
     * @var EventBase
     */
    public static $eventBase;
    public static $proxyList;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    private function getProxy()
    {
        if(!static::$proxyList){
            $file = __DIR__ . "/proxy.data";
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
        try{
            TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
            $proxy =  $this->getProxy();
            $client = new \Crypto\WSFrameClient('api.hitbtc.com', 443, '/api/2/ws', $proxy);
        }catch (\Throwable $t)
        {
            if($t->getCode() === 429 ){
                $n+=$n*(0.1);
            }

            if($t->getCode() === -2){
                var_dump($proxy);
                $p = array_pop(static::$proxyList);
                var_dump($p);
            }

                for($i = 0; $i<= $n; $i++) {
                    TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
                    var_dump("$i - $n");
                }

                goto m1;
            }

        static::$id++;
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

        $client->send($message);

        //var_dump($client->getFrame()->getData());

        $id = static::$id;
        $message = "{ \"method\": \"getTradingBalance\", \"params\": {}, \"id\": {$id} }";

        $client->send($message);
        //var_dump($client->getFrame()->getData());

        $message = json_encode([ 'method'=>'subscribeReports', 'params'=>[], 'id'=>$id, ]);
        $client->send($message);
        //var_dump($client->getFrame()->getData());
        stream_set_timeout($client->socket, 10);
        stream_set_blocking($client->socket, false);

        $event = new Event(static::$eventBase, $client->socket, Event::READ, function($socket, $n, $x)use($client, &$event){
            try{
                $frame = $client->getFrame();
            }
            catch (Throwable $t)
            {
                var_dump($t->getMessage());
                $this->log($t->getMessage());

                if($t->getCode() === -1){
                    $this->init();
                }

                return;
            }

            if($frame)
            {
                $msg = $frame->getData();
                $this->log("[{$frame->opcode}] [length={$frame->dataLength}] {$msg}");
            }
            $event->add(1);
        });

        $this->wsClient = $client;
        TradeListener::$listeners[] = &$this;
        $event->add(1);
    }

    public function log($m)
    {

        $dt = (new \DateTime())->format("Y-m-d H:i:s");
        $name = md5($this->key);
        file_put_contents(__DIR__."/../storage/log/doit/{$name}.log", "[{$dt}] {$m}\n", FILE_APPEND);
    }
}

TradeListener::$eventBase = new EventBase();

$counter = 0;
var_dump("count api keys: ".count($keys));


/**
 * @var $te Event
 */
$te = Event::timer(TradeListener::$eventBase, function ($n) use(&$te){

    $dt = new DateTime();
    $dt->sub(new DateInterval("PT30S"));

    $redTime = clone $dt;
    $redTime->sub(new DateInterval("PT5M"));

    foreach (TradeListener::$listeners as $listener){
        if($listener->wsClient->lastTime < $dt){
            $listener->log("No messages for 30 sec");
            $r = $listener->wsClient->ping();
            $listener->log("Ping $r");
            if(!$r){
                $listener->log("Reinit");
                fclose( $listener->wsClient->socket );
                $listener->wsClient->socket = null;
                $listener->init();
            }
        }

        if($listener->wsClient->lastTime < $redTime){
            $listener->log("RED LIMIT");
            $listener->log("Reinit");
            fclose( $listener->wsClient->socket );
            $listener->wsClient->socket = null;
            $listener->init();
        }

    }

    $te->addTimer(20);
}, null);
$te->addTimer(20);

foreach ($keys as $apiKey)
{

        $counter++;
        $l = new TradeListener($apiKey['key'], $apiKey['secret']);
        try{
            $l->init();
        }
        catch (\Throwable $t)
        {
            $str = $t->getMessage() . "\n" . $t->getFile() . ":" . $t->getLine();
            var_dump($str);
        }
        TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);



    var_dump("counter = $counter");
    TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
}

TradeListener::$eventBase->dispatch();