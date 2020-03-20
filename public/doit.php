<?php

use Crypto\Listener;
use Crypto\WSFrameClient;

include __DIR__."/../vendor/autoload.php";
ini_set('display_startup_errors',1);
ini_set('display_errors', 1);
ini_set('default_socket_timeout', 5);

$keys = file_get_contents(__DIR__."/../storage/hitkeys.data");
$keys = unserialize($keys);
shuffle($keys);
var_dump($keys[0]);

set_error_handler(function($no, $str, $file, $line, $context){
    $msg =  "$str, $file:$line $no";
    echo "$msg\n";
    throw new \Exception($msg);
});



Listener::$eventBase = new EventBase();

$counter = 0;
var_dump("count api keys: ".count($keys));


/**
 * @var $te Event
 */
$timerCounter = 0;
$te = Event::timer(Listener::$eventBase, function ($n) use(&$te, &$timerCounter){

    try{
        $timerCounter++;
        $dt = new DateTime();
        $dt->sub(new DateInterval("PT30S"));

        $redTime = clone $dt;
        $redTime->sub(new DateInterval("PT5M"));

        $reinitCounter = 0;
        foreach (Listener::$listeners as $listener){

            if(!$listener->wsClient){
                continue;
            }

            if($listener->wsClient->lastTime < $redTime){
                $reinitCounter+=2;
                $listener->log("RED LIMIT [{$timerCounter}]");
                $listener->log("Reinit [{$timerCounter}]");
                $listener->wsClient = null;
                $listener->reinit($reinitCounter);
            }
            elseif($listener->wsClient->lastTime < $dt){
                $listener->log("No messages for 30 sec [{$timerCounter}] ".$listener->wsClient->lastTime->format("Y-m-d H:i:s"));
                try{
                    $r = $listener->wsClient->ping();
                }
                catch (\Throwable $t){
                    $listener->log($t->getMessage());
                    $r = 0;
                }

                $listener->log("Ping $r [{$timerCounter}]");
                if(!$r){
                    $reinitCounter+=2;
                    $listener->log("Reinit [{$timerCounter}]");
                    $listener->wsClient = null;
                    $listener->reinit($reinitCounter);
                }
            }



        }

        $msg = (new \DateTime())->format("Y-m-d H:i:s")." \n";
        $msg .= " listeners = ".count(Listener::$listeners)." \n";
        $msg .= " proxy count ".count(Listener::$proxyList)." \n";
        $msg .= "\n";
        echo($msg);
    }catch (\Throwable $t){
        var_dump("TIMER WARNING ".$t->getMessage()." ".$t->getFile()." ".$t->getLine());
    }

    $r = $te->add(10);
    if(!$r){
        var_dump("TIMER WARNING! $r");
        sleep(1);
        $r = $te->add(10);
        var_dump("TIMER ASSERT $r");
    }


}, null);
$te->addTimer(10);
foreach ($keys as $apiKey)
{
        $counter++;
        $l = new Listener($apiKey['key'], $apiKey['secret']);
        try{
            $l->init();
        }
        catch (\Throwable $t)
        {
            $str = $t->getMessage() . "\n" . $t->getFile() . ":" . $t->getLine();
            var_dump($str);
        }

        if(!Listener::$isRuning){
            Listener::$isRuning = true;
            Listener::$eventBase->loop(EventBase::LOOP_NONBLOCK);
            Listener::$isRuning = false;
        }

        var_dump("counter = $counter");
        gc_collect_cycles();
}

$ipsGood = serialize(Listener::$proxyList);
file_put_contents('goodproxy.data', $ipsGood);

Listener::$isRuning = true;
Listener::$eventBase->dispatch();