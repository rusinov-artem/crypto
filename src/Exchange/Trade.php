<?php


namespace Crypto\Exchange;


class Trade
{
    public $id;
    public $pairID;
    public $orderID;
    public $side;
    public $price;
    public $value;

    /**
     * @var \DateTime
     */
    public $date;
    public $fee;
    public $feeCurrency;
}