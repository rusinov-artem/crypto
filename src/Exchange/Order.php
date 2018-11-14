<?php


namespace Crypto\Exchange;


class Order
{
    public $id;
    public $date;
    public $price;
    public $value;
    public $pairID;
    public $side;
    public $traded = 0;
    public $status; // new, suspended, partiallyFilled, filled, canceled, expired
}