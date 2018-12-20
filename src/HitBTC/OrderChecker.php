<?php


namespace Crypto\HitBTC;


use Crypto\Bot\Events\OrderUpdated;
use Crypto\Exchange\ClientInterface as ClientInterfaceAlias;
use Crypto\Exchange\Order;
use Symfony\Component\EventDispatcher\EventDispatcher;

class OrderChecker
{


    public $orders;
    public $client;
    public $dispatcher;

    /**
     * @param ClientInterfaceAlias $client
     * @param EventDispatcher $dispatcher
     */
    public function __construct(ClientInterfaceAlias $client, EventDispatcher $dispatcher)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher;
    }

    public function init(array $orders)
    {
        $this->orders = [];

        $counter = 0;
        foreach ($orders as $order)
        {
            if($order->isActive() || $order->status ==='canceled')
            {
                $this->orders[$order->pairID][$order->side][] = $order;
                $counter ++;
            }
        }

        if($counter < 1) return false;

        foreach ($this->orders as &$pairOrders)
        {
            if(isset($pairOrders['sell']))
            $r = usort($pairOrders['sell'], function(Order $a, Order $b){
                return $a->price <=> $b->price;
            });

            if(isset($pairOrders['buy']))
            usort($pairOrders['buy'], function($a, $b){
                return $b->price <=> $a->price;
            });
        }

        return $this->orders;
    }

    public function check()
    {
        foreach ($this->orders as &$pairOrders)
        {
            if(isset($pairOrders['buy']))
                $this->checkOrders($pairOrders['buy']);

            if(isset($pairOrders['sell']))
                $this->checkOrders($pairOrders['sell']);
        }
    }

    private function checkOrders(&$orders)
    {
        /**
         * @var $order Order
         */
        foreach ($orders as &$order)
        {
            $this->client->getOrderStatus($order);
            $this->dispatcher->dispatch(OrderUpdated::NAME, new OrderUpdated($order));
            if($order->status !== 'filled')
            {
                break;
            }
        }
    }

}