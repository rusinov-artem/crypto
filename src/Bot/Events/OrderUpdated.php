<?php


namespace Crypto\Bot\Events;


use Crypto\Exchange\Order;
use Symfony\Component\EventDispatcher\Event;

class OrderUpdated extends Event
{
    public const NAME = "Order.Updated";
    public $order;

    public function __construct(Order &$order)
    {
        $this->order = $order;
    }
}