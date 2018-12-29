<?php


namespace Crypto\Bot;


use Crypto\Bot\Events\OrderUpdated;
use Crypto\Exchange\Events\NewTrade;
use Crypto\HitBTC\OrderManager;

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

    public function subscribeOrders(OrderManager $om)
    {
        $om->dispatcher->addListener("OrderManager.OrderUpdated", [$this, 'orderListener']);
    }

    public function tradeListener(NewTrade $event)
    {
        var_dump("Got {$event->trade->eClientOrderID}");
    }

    public function orderListener(OrderUpdated $orderUpdated)
    {

    }
}