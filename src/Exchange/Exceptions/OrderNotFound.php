<?php


namespace Crypto\Exchange\Exceptions;


use Throwable;

class OrderNotFound extends \Exception
{
    public function __construct($orderID)
    {
        parent::__construct("Order $orderID not found", 1);
    }
}