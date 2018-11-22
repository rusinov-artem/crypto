<?php


namespace Crypto\Exchange\Exceptions;


use Crypto\Exchange\Order;
use Throwable;

class OrderRejected extends \Exception
{

    /**
     * @var Order
     */
    public $order;

    public function __construct(Order $order, $message)
    {
        parent::__construct($message, 3);
        $this->order = $order;
    }
}