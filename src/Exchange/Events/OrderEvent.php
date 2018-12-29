<?php


namespace Crypto\Exchange\Events;


use Crypto\Exchange\Order;
use Symfony\Component\EventDispatcher\Event;

class OrderEvent extends Event
{
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}