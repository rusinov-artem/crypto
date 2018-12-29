<?php


namespace Crypto\Bot;


use Crypto\Exchange\Events\NewTrade;

class BotsManager
{

    public function init()
    {

    }


    public function getAll()
    {

    }

    public function getWorkingPairs()
    {

    }

    public function subscribeTrades(\Crypto\HitBTC\TradeManager $tm)
    {
        $tm->dispatcher->addListener("TradeManager.NewTrade", [$this, 'tradeListener']);
    }

    public function tradeListener(NewTrade $event)
    {
        var_dump("Got {$event->trade->orderID}");
    }
}