<?php
include __DIR__."/../vendor/autoload.php";

$keys = file_get_contents(__DIR__."/../storage/hitkeys.data");
$keys = unserialize($keys);
shuffle($keys);
var_dump($keys[0]);

class TradeListener
{
    public $key;
    public $secret;
    public $wsClient;
    /**
     * @var EventBase
     */
    public static $eventBase;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function init()
    {
        $n = 6;
        m1:
        try{
            TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
            $client = new \Crypto\WSFrameClient('api.hitbtc.com', 443, '/api/2/ws');
        }catch (\Throwable $t)
        {
            if($t->getCode() === 429 )

                $n+=$n*(0.1);
                for($i = 0; $i<= $n; $i++) {
                    TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
                    sleep(1);
                    var_dump("i - $n");
                }

                goto m1;
            }

        $message = "{
              \"method\": \"login\",
              \"params\": {
                \"algo\": \"BASIC\",
                \"pKey\": \"{$this->key}\",
                \"sKey\": \"{$this->secret}\"
              }
            }";

        $client->send($message);

        //var_dump($client->getFrame()->getData());

        $message = "{ \"method\": \"getTradingBalance\", \"params\": {}, \"id\": 123 }";
        $client->send($message);
        //var_dump($client->getFrame()->getData());

        $message = json_encode([ 'method'=>'subscribeReports', 'params'=>[], 'id'=>123, ]);
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

    var_dump("counter = $counter");
    TradeListener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
}

TradeListener::$eventBase->dispatch();